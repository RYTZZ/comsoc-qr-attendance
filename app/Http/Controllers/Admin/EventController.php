<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\AcademicYear;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(Request $request): View
    {
        $academicYears = AcademicYear::orderByDesc('year_start')->get();
        $events = Event::with(['academicYear', 'createdBy'])
            ->withCount('eventRegistrations')
            ->when($request->academic_year_id, fn($q) => $q->where('academic_year_id', $request->academic_year_id))
            ->when($request->status, function ($q, $status) {
                if ($status === 'draft') {
                    $q->where(fn($sq) => $sq->where('status', 'draft')->orWhere('is_published', false));
                } else {
                    $q->where('status', $status);
                }
            })
            ->when($request->search, fn($q) => $q->whereLike('name', "%{$request->search}%"))
            ->orderByDesc('event_date')
            ->paginate(15)
            ->withQueryString();

        return view('admin.events.index', compact('events', 'academicYears'));
    }

    public function create(): View
    {
        $academicYears = AcademicYear::orderByDesc('year_start')->get();
        return view('admin.events.create', compact('academicYears'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateEventRequest($request);

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('events', 'public');
        }

        $data['created_by'] = auth()->id();
        if (empty($data['location']) && !empty($data['venue_name'])) {
            $data['location'] = $data['venue_name'];
        }

        $data['requires_registration'] = $request->boolean('requires_registration');
        $data['allow_non_students'] = $request->boolean('allow_non_students');
        $data['tshirt_enabled'] = $request->boolean('tshirt_enabled');
        $data['tshirt_required'] = $data['tshirt_enabled'] && $request->boolean('tshirt_required');
        $data['dietary_enabled'] = $request->boolean('dietary_enabled');
        $data['dietary_required'] = $data['dietary_enabled'] && $request->boolean('dietary_required');

        $event = Event::create($data);

        AuditLogger::log('event.created', $event, [], $event->toArray());

        if ($request->filled('sessions') && is_array($request->sessions)) {
            $this->syncSessions($event, $request->sessions);
        }

        return redirect()->route('admin.events.configure', $event)
            ->with('success', "Event '{$event->name}' created successfully. You can now complete its configuration.");
    }

    public function show(Event $event): View
    {
        $event->load(['academicYear', 'attendanceSessions', 'snackSessions.inventories', 'eventRegistrations']);
        return view('admin.events.show', compact('event'));
    }

    public function edit(Event $event): RedirectResponse
    {
        return redirect()->route('admin.events.configure', $event);
    }

    public function configure(Event $event): View
    {
        $event->load(['academicYear', 'attendanceSessions', 'snackSessions.inventories', 'eventRegistrations']);
        $academicYears = AcademicYear::orderByDesc('year_start')->get();
        return view('admin.events.configure', compact('event', 'academicYears'));
    }

    public function saveConfiguration(Request $request, Event $event): RedirectResponse
    {
        $data = $this->validateEventRequest($request, $event);

        if ($request->hasFile('logo')) {
            if ($event->logo_path && Storage::disk('public')->exists($event->logo_path)) {
                Storage::disk('public')->delete($event->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('events', 'public');
        }

        if (empty($data['location']) && !empty($data['venue_name'])) {
            $data['location'] = $data['venue_name'];
        }

        if (($data['status'] ?? null) === 'registration_open') {
            $dl = $data['registration_deadline'] ?? null;
            if ($dl && Carbon::parse($dl)->isPast()) {
                $data['registration_deadline'] = null;
            }
        }

        // Handle boolean checkboxes that may not be submitted when unchecked
        $data['requires_registration'] = $request->boolean('requires_registration');
        $data['allow_non_students'] = $request->boolean('allow_non_students');
        $data['tshirt_enabled'] = $request->boolean('tshirt_enabled');
        $data['tshirt_required'] = $data['tshirt_enabled'] && $request->boolean('tshirt_required');
        $data['dietary_enabled'] = $request->boolean('dietary_enabled');
        $data['dietary_required'] = $data['dietary_enabled'] && $request->boolean('dietary_required');

        $old = $event->toArray();
        $event->update($data);

        if ($request->has('sessions') && is_array($request->sessions)) {
            $this->syncSessions($event, $request->sessions);
        }

        AuditLogger::log('event.configured', $event, $old, $event->fresh()->toArray());

        return redirect()->route('admin.events.configure', $event)
            ->with('success', "Event configuration for '{$event->name}' updated successfully.");
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        return $this->saveConfiguration($request, $event);
    }

    public function approve(Request $request, Event $event): RedirectResponse
    {
        $user = auth()->user();
        if (!$user->isPresident() && !$user->isSuperAdmin()) {
            abort(403, 'Unauthorized: Only the President or Super Admin can approve events.');
        }

        if ($event->status !== 'draft') {
            return back()->with('error', "Cannot approve event: Only draft events can be approved (Current status: {$event->status_label}).");
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $old = ['status' => $event->status, 'approved_by' => $event->approved_by, 'approved_at' => $event->approved_at];
        $event->update([
            'status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'approval_notes' => $validated['notes'] ?? null,
            'is_published' => false,
        ]);

        AuditLogger::log('event.approved', $event, $old, $event->fresh()->toArray(), 'Event approved by ' . $user->name . '. Notes: ' . ($validated['notes'] ?? 'None'));

        return back()->with('success', "Event '{$event->name}' has been approved and is ready to be published.");
    }

    public function publish(Event $event): RedirectResponse
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !$user->isPresident() && !$user->isExecutiveSecretary() && !$user->isAdmin()) {
            abort(403, 'Unauthorized to publish events.');
        }

        // Prevent unapproved events from being published
        if (!in_array($event->status, ['approved', 'draft']) && $event->is_published) {
            return back()->with('info', "Event '{$event->name}' is already published.");
        }

        if ($event->status === 'draft' && !$event->approved_at) {
            // Auto-approval only if Super Admin or President is directly publishing
            if ($user->isSuperAdmin() || $user->isPresident()) {
                $event->update([
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                    'approval_notes' => 'Directly published by ' . $user->role,
                ]);
            } else {
                return redirect()->back()->with('error', "Workflow violation: Event '{$event->name}' must be reviewed and approved by the President before publishing.");
            }
        }

        $old = ['is_published' => $event->is_published, 'status' => $event->status];
        $event->update([
            'is_published' => true,
            'status' => 'published',
        ]);

        AuditLogger::log('event.published', $event, $old, $event->fresh()->toArray(), 'Event published by ' . $user->name . '.');

        return back()->with('success', "Event '{$event->name}' has been published and is now visible to participants.");
    }

    public function closeRegistration(Event $event): RedirectResponse
    {
        $old = ['status' => $event->status];
        $event->update([
            'status' => 'registration_closed',
        ]);

        AuditLogger::log('event.registration_closed', $event, $old, $event->fresh()->toArray(), 'Registration closed by ' . auth()->user()->name . '.');

        return back()->with('success', "Registration for event '{$event->name}' has been closed.");
    }

    public function complete(Event $event): RedirectResponse
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !$user->isPresident() && !$user->isExecutiveSecretary() && !$user->isAdmin()) {
            abort(403, 'Unauthorized to finalize event.');
        }

        if (!in_array($event->status, ['published', 'registration_open', 'registration_closed', 'ongoing'])) {
            return back()->with('error', "Only active or published events can transition to Completed.");
        }

        $old = ['status' => $event->status];
        $event->update([
            'status' => 'completed',
        ]);

        AuditLogger::log('event.completed', $event, $old, $event->fresh()->toArray(), 'Event marked as completed by ' . $user->name . '. Attendance reconciliation ready.');

        return back()->with('success', "Event '{$event->name}' has been marked as completed. Post-event reconciliation finalized.");
    }

    public function archive(Event $event): RedirectResponse
    {
        $old = ['status' => $event->status, 'is_published' => $event->is_published];
        $event->update([
            'status' => 'archived',
            'is_published' => false,
        ]);

        AuditLogger::log('event.archived', $event, $old, $event->fresh()->toArray(), 'Event archived by super admin.');

        return back()->with('success', "Event '{$event->name}' has been archived.");
    }

    public function destroy(Event $event): RedirectResponse
    {
        $name = $event->name;
        $old = $event->toArray();
        $event->delete();

        AuditLogger::log('event.deleted', null, $old, [], "Event '{$name}' deleted by super admin.");

        return redirect()->route('admin.events.index')->with('success', "Event '{$name}' deleted.");
    }

    public function storeSession(Request $request, Event $event): RedirectResponse
    {
        $opensAt = $this->normalizeTime($request->input('opens_at'));
        $closesAt = $this->normalizeTime($request->input('closes_at'));
        $lateThreshold = $this->normalizeTime($request->input('late_threshold'));

        $request->merge([
            'opens_at' => $opensAt,
            'closes_at' => $closesAt,
            'late_threshold' => $lateThreshold,
        ]);

        $data = $request->validate([
            'type' => 'required|in:morning_in,morning_out,afternoon_in,afternoon_out',
            'opens_at' => 'required|date_format:H:i',
            'closes_at' => 'required|date_format:H:i',
            'late_threshold' => 'nullable|date_format:H:i',
        ]);

        $opensMin = $this->parseTimeToMinutes($data['opens_at']);
        $closesMin = $this->parseTimeToMinutes($data['closes_at']);

        if ($opensMin === null || $closesMin === null || $opensMin >= $closesMin) {
            back()->withInput()->withErrors(['closes_at' => 'Closing time must be after opening time.'])->throwResponse();
        }

        if (!empty($data['late_threshold'])) {
            $lateMin = $this->parseTimeToMinutes($data['late_threshold']);
            if ($lateMin === null || $lateMin < $opensMin || $lateMin > $closesMin) {
                $sessionLabel = ucwords(str_replace('_', ' ', $data['type']));
                back()->withInput()->withErrors(['late_threshold' => "{$sessionLabel} Late Threshold must be between opening and closing times."])->throwResponse();
            }
        }

        $event->attendanceSessions()->updateOrCreate(['type' => $data['type']], $data);

        AuditLogger::log('event.session_saved', $event, [], $data);

        return back()->with('success', 'Attendance session saved.');
    }

    public function storeSnackSession(Request $request, Event $event): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:morning,afternoon,other',
            'available_from' => 'nullable|date_format:H:i',
            'available_until' => 'nullable|date_format:H:i',
        ]);

        $session = $event->snackSessions()->create($data);

        if ($request->filled('item_name')) {
            $session->inventories()->create([
                'item_name' => $request->item_name,
                'total_quantity' => $request->total_quantity ?? 0,
            ]);
        }

        AuditLogger::log('event.snack_session_added', $event, [], $data);

        return back()->with('success', 'Snack session added.');
    }

    private function validateEventRequest(Request $request, ?Event $existingEvent = null): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'event_type' => ['nullable', 'string', 'max:100'],
            'organizer' => ['nullable', 'string', 'max:255'],
            'contact_info' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'venue_name' => ['nullable', 'string', 'max:255'],
            'venue_address' => ['nullable', 'string', 'max:255'],
            'venue_details' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'event_date' => ['required', 'date'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'registration_opens_at' => ['nullable', 'date'],
            'registration_deadline' => ['nullable', 'date'],
            'attendance_starts_at' => ['nullable', 'date_format:H:i'],
            'attendance_ends_at' => ['nullable', 'date_format:H:i'],
            'max_participants' => ['nullable', 'integer', 'min:1'],
            'requires_registration' => ['nullable', 'boolean'],
            'tshirt_enabled' => ['nullable', 'boolean'],
            'tshirt_required' => ['nullable', 'boolean'],
            'dietary_enabled' => ['nullable', 'boolean'],
            'dietary_required' => ['nullable', 'boolean'],
            'allow_non_students' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
            'status' => ['nullable', 'in:draft,approved,published,registration_open,registration_closed,ongoing,completed,archived'],
            'attendance_enabled' => ['nullable', 'boolean'],
            'snack_distribution_enabled' => ['nullable', 'boolean'],
            'cover_image' => ['nullable', 'string', 'max:255'],
            'participation_requirements' => ['nullable', 'string'],
            'reward_xp' => ['nullable', 'integer', 'min:0'],
            'reward_points' => ['nullable', 'integer', 'min:0'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:3072'],
            'sessions' => ['nullable', 'array'],
            'sessions.*.enabled' => ['nullable', 'boolean'],
            'sessions.*.opens_at' => ['nullable'],
            'sessions.*.closes_at' => ['nullable'],
            'sessions.*.late_threshold' => ['nullable'],
        ];

        // Pre-normalize session times if provided in various valid formats (12-hr, seconds, etc.)
        if ($request->has('sessions') && is_array($request->input('sessions'))) {
            $sessions = $request->input('sessions');
            foreach ($sessions as $key => $session) {
                if (is_array($session)) {
                    foreach (['opens_at', 'closes_at', 'late_threshold'] as $timeField) {
                        if (isset($session[$timeField])) {
                            $sessions[$key][$timeField] = $this->normalizeTime($session[$timeField]);
                        }
                    }
                }
            }
            $request->merge(['sessions' => $sessions]);
        }

        if ($request->has('attendance_starts_at')) {
            $request->merge(['attendance_starts_at' => $this->normalizeTime($request->input('attendance_starts_at'))]);
        }
        if ($request->has('attendance_ends_at')) {
            $request->merge(['attendance_ends_at' => $this->normalizeTime($request->input('attendance_ends_at'))]);
        }

        $validated = $request->validate($rules);

        if (!empty($validated['registration_opens_at']) && !empty($validated['registration_deadline'])) {
            $opens = Carbon::parse($validated['registration_opens_at']);
            $deadline = Carbon::parse($validated['registration_deadline']);
            if ($opens->gt($deadline)) {
                back()->withInput()->withErrors(['registration_opens_at' => 'Registration opening date & time must be before or equal to registration closing date & time.'])->throwResponse();
            }
        }

        if (!empty($validated['registration_deadline'])) {
            $deadline = Carbon::parse($validated['registration_deadline']);

            // Validate against event completion/end time if provided, otherwise event start time
            if (!empty($validated['ends_at'])) {
                $ends = Carbon::parse($validated['ends_at']);
                if ($deadline->gt($ends)) {
                    back()->withInput()->withErrors(['registration_deadline' => 'Registration closing date & time cannot be after the event end time.'])->throwResponse();
                }
            } elseif (!empty($validated['starts_at'])) {
                $starts = Carbon::parse($validated['starts_at']);
                if ($deadline->gt($starts)) {
                    back()->withInput()->withErrors(['registration_deadline' => 'Registration closing date & time must be before or equal to event start time when no end time is specified.'])->throwResponse();
                }
            }
        }

        if (!empty($validated['attendance_starts_at']) && !empty($validated['attendance_ends_at'])) {
            if ($validated['attendance_starts_at'] >= $validated['attendance_ends_at']) {
                back()->withInput()->withErrors(['attendance_ends_at' => 'Attendance end time must be after attendance start time.'])->throwResponse();
            }
        }

        if (!empty($validated['starts_at']) && !empty($validated['ends_at'])) {
            if (Carbon::parse($validated['ends_at'])->lt(Carbon::parse($validated['starts_at']))) {
                back()->withInput()->withErrors(['ends_at' => 'Event End Time cannot be earlier than Event Start Time.'])->throwResponse();
            }
        }

        if ($request->boolean('attendance_enabled') && !empty($validated['sessions']) && is_array($validated['sessions'])) {
            $sess = $validated['sessions'];

            foreach (['morning_in', 'morning_out', 'afternoon_in', 'afternoon_out'] as $type) {
                if (!empty($sess[$type]['enabled'])) {
                    $opensMin = $this->parseTimeToMinutes($sess[$type]['opens_at'] ?? null);
                    $closesMin = $this->parseTimeToMinutes($sess[$type]['closes_at'] ?? null);

                    if ($opensMin === null) {
                        back()->withInput()->withErrors(["sessions.{$type}.opens_at" => ucwords(str_replace('_', ' ', $type)) . ' opening time is required.'])->throwResponse();
                    }
                    if ($closesMin === null) {
                        back()->withInput()->withErrors(["sessions.{$type}.closes_at" => ucwords(str_replace('_', ' ', $type)) . ' closing time is required.'])->throwResponse();
                    }
                    if ($opensMin >= $closesMin) {
                        back()->withInput()->withErrors(["sessions.{$type}.closes_at" => ucwords(str_replace('_', ' ', $type)) . ' closing time must be after opening time.'])->throwResponse();
                    }
                }
            }

            if (!empty($sess['morning_in']['enabled']) && !empty($sess['morning_in']['late_threshold'])) {
                $lateMin = $this->parseTimeToMinutes($sess['morning_in']['late_threshold']);
                $opensMin = $this->parseTimeToMinutes($sess['morning_in']['opens_at'] ?? null);
                $closesMin = $this->parseTimeToMinutes($sess['morning_in']['closes_at'] ?? null);

                if ($lateMin === null || $opensMin === null || $closesMin === null || $lateMin < $opensMin || $lateMin > $closesMin) {
                    back()->withInput()->withErrors(['sessions.morning_in.late_threshold' => 'Morning Late Threshold must be between Morning IN opening and closing times.'])->throwResponse();
                }
            }

            if (!empty($sess['afternoon_in']['enabled']) && !empty($sess['afternoon_in']['late_threshold'])) {
                $lateMin = $this->parseTimeToMinutes($sess['afternoon_in']['late_threshold']);
                $opensMin = $this->parseTimeToMinutes($sess['afternoon_in']['opens_at'] ?? null);
                $closesMin = $this->parseTimeToMinutes($sess['afternoon_in']['closes_at'] ?? null);

                if ($lateMin === null || $opensMin === null || $closesMin === null || $lateMin < $opensMin || $lateMin > $closesMin) {
                    back()->withInput()->withErrors(['sessions.afternoon_in.late_threshold' => 'Afternoon Late Threshold must be between Afternoon IN opening and closing times.'])->throwResponse();
                }
            }

            $mInOpens = !empty($sess['morning_in']['enabled']) ? $this->parseTimeToMinutes($sess['morning_in']['opens_at'] ?? null) : null;
            $mOutOpens = !empty($sess['morning_out']['enabled']) ? $this->parseTimeToMinutes($sess['morning_out']['opens_at'] ?? null) : null;
            $aInOpens = !empty($sess['afternoon_in']['enabled']) ? $this->parseTimeToMinutes($sess['afternoon_in']['opens_at'] ?? null) : null;
            $aOutOpens = !empty($sess['afternoon_out']['enabled']) ? $this->parseTimeToMinutes($sess['afternoon_out']['opens_at'] ?? null) : null;

            if ($mInOpens !== null && $mOutOpens !== null) {
                if ($mInOpens >= $mOutOpens) {
                    back()->withInput()->withErrors(['sessions.morning_out.opens_at' => 'Morning IN must occur earlier than Morning OUT.'])->throwResponse();
                }
            }

            if ($aInOpens !== null && $aOutOpens !== null) {
                if ($aInOpens >= $aOutOpens) {
                    back()->withInput()->withErrors(['sessions.afternoon_out.opens_at' => 'Afternoon IN must occur earlier than Afternoon OUT.'])->throwResponse();
                }
            }

            if ($mOutOpens !== null && $aInOpens !== null) {
                if ($mOutOpens > $aInOpens) {
                    back()->withInput()->withErrors(['sessions.afternoon_in.opens_at' => 'Morning OUT cannot be later than Afternoon IN.'])->throwResponse();
                }
            } elseif ($mInOpens !== null && $aInOpens !== null) {
                if ($mInOpens >= $aInOpens) {
                    back()->withInput()->withErrors(['sessions.afternoon_in.opens_at' => 'Morning IN must occur before Afternoon IN.'])->throwResponse();
                }
            }
        }

        $validated['requires_registration'] = $request->boolean('requires_registration');
        $validated['allow_non_students'] = $request->boolean('allow_non_students');
        $validated['is_published'] = $request->boolean('is_published');
        $validated['attendance_enabled'] = $request->boolean('attendance_enabled', true);
        $validated['snack_distribution_enabled'] = $request->boolean('snack_distribution_enabled', true);

        return $validated;
    }

    private function syncSessions(Event $event, array $sessions): void
    {
        foreach ($sessions as $type => $sessionData) {
            if (!empty($sessionData['enabled'])) {
                $opens = $this->normalizeTime($sessionData['opens_at'] ?? null);
                $closes = $this->normalizeTime($sessionData['closes_at'] ?? null);
                $late = $this->normalizeTime($sessionData['late_threshold'] ?? null);

                if ($opens && $closes) {
                    $event->attendanceSessions()->updateOrCreate(
                        ['type' => $type],
                        [
                            'opens_at' => $opens,
                            'closes_at' => $closes,
                            'late_threshold' => $late ?: null,
                            'is_active' => true,
                        ]
                    );
                }
            } else {
                $event->attendanceSessions()->where('type', $type)->delete();
            }
        }
    }

    /**
     * Normalize a time value into 24-hour 'H:i' format.
     */
    public function normalizeTime(?string $time): ?string
    {
        if ($time === null) {
            return null;
        }

        $time = trim($time);
        if ($time === '') {
            return null;
        }

        // Try standard 24-hr formats first
        if (preg_match('/^([01]?[0-9]|2[0-3]):([0-5][0-9])(:[0-5][0-9])?$/', $time, $matches)) {
            $h = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $m = $matches[2];
            return "{$h}:{$m}";
        }

        // Try parsing via Carbon for 12-hour (01:15 PM) or other valid strings
        try {
            return Carbon::parse($time)->format('H:i');
        } catch (\Throwable $e) {
            return $time;
        }
    }

    /**
     * Parse a time string into minutes from midnight (0..1439) for reliable comparisons.
     */
    public function parseTimeToMinutes(?string $time): ?int
    {
        $normalized = $this->normalizeTime($time);
        if ($normalized === null) {
            return null;
        }

        $parts = explode(':', $normalized);
        if (count($parts) < 2) {
            return null;
        }

        $h = (int) $parts[0];
        $m = (int) $parts[1];

        return ($h * 60) + $m;
    }
}

