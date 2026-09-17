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
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
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

    public function publish(Event $event): RedirectResponse
    {
        $old = ['is_published' => $event->is_published, 'status' => $event->status];
        $event->update([
            'is_published' => true,
            'status' => 'registration_open',
        ]);

        AuditLogger::log('event.published', $event, $old, $event->fresh()->toArray(), 'Event published by super admin.');

        return back()->with('success', "Event '{$event->name}' has been published and is now open for registration.");
    }

    public function closeRegistration(Event $event): RedirectResponse
    {
        $old = ['status' => $event->status];
        $event->update([
            'status' => 'registration_closed',
            'registration_deadline' => now(),
        ]);

        AuditLogger::log('event.registration_closed', $event, $old, $event->fresh()->toArray(), 'Registration closed by super admin.');

        return back()->with('success', "Registration for event '{$event->name}' has been closed.");
    }

    public function complete(Event $event): RedirectResponse
    {
        $old = ['status' => $event->status];
        $event->update([
            'status' => 'completed',
        ]);

        AuditLogger::log('event.completed', $event, $old, $event->fresh()->toArray(), 'Event marked as completed by super admin.');

        return back()->with('success', "Event '{$event->name}' has been marked as completed.");
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
        $data = $request->validate([
            'type' => 'required|in:morning_in,morning_out,afternoon_in,afternoon_out',
            'opens_at' => 'required|date_format:H:i',
            'closes_at' => 'required|date_format:H:i|after:opens_at',
            'late_threshold' => 'nullable|date_format:H:i',
        ]);

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
            'allow_non_students' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
            'status' => ['nullable', 'in:draft,registration_open,registration_closed,ongoing,completed,archived'],
            'attendance_enabled' => ['nullable', 'boolean'],
            'snack_distribution_enabled' => ['nullable', 'boolean'],
            'logo' => ['nullable', 'image', 'max:3072'],
        ];

        $validated = $request->validate($rules);

        if (!empty($validated['registration_opens_at']) && !empty($validated['registration_deadline'])) {
            $opens = Carbon::parse($validated['registration_opens_at']);
            $deadline = Carbon::parse($validated['registration_deadline']);
            if ($opens->gt($deadline)) {
                back()->withErrors(['registration_opens_at' => 'Registration opening date & time must be before registration closing date & time.'])->throwResponse();
            }
        }

        if (!empty($validated['registration_deadline']) && !empty($validated['starts_at'])) {
            $deadline = Carbon::parse($validated['registration_deadline']);
            $starts = Carbon::parse($validated['starts_at']);
            if ($deadline->gt($starts)) {
                back()->withErrors(['registration_deadline' => 'Registration closing date & time must be before or equal to event start time.'])->throwResponse();
            }
        }

        if (!empty($validated['attendance_starts_at']) && !empty($validated['attendance_ends_at'])) {
            if ($validated['attendance_starts_at'] >= $validated['attendance_ends_at']) {
                back()->withErrors(['attendance_ends_at' => 'Attendance end time must be after attendance start time.'])->throwResponse();
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
                $opens = $sessionData['opens_at'] ?? null;
                $closes = $sessionData['closes_at'] ?? null;
                $late = $sessionData['late_threshold'] ?? null;

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
}

