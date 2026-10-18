<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Program;
use App\Services\QrPngRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberEventController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        $student = $user->student;

        $query = Event::where('is_published', true)
            ->whereNotIn('status', ['draft', 'archived']);

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereLike('name', "%{$search}%")
                  ->orWhereLike('description', "%{$search}%")
                  ->orWhereLike('venue_name', "%{$search}%")
                  ->orWhereLike('location', "%{$search}%");
            });
        }

        if ($type = $request->query('type')) {
            $query->where('event_type', $type);
        }

        $tab = $request->query('tab', 'upcoming');
        if ($tab === 'past') {
            $events = $query->where(function ($q) {
                $q->where('event_date', '<', now()->toDateString())
                  ->orWhere('status', 'completed');
            })->orderByDesc('event_date')->paginate(9)->withQueryString();
        } else {
            $events = $query->where(function ($q) {
                $q->where('event_date', '>=', now()->toDateString())
                  ->where('status', '!=', 'completed');
            })->orderBy('event_date', 'asc')->paginate(9)->withQueryString();
        }

        $userRegistrations = EventRegistration::where('user_id', $user->id)
            ->get()
            ->keyBy('event_id');

        $eventTypes = Event::where('is_published', true)
            ->whereNotNull('event_type')
            ->distinct()
            ->pluck('event_type')
            ->filter()
            ->values();

        return view('events.index', compact(
            'events',
            'userRegistrations',
            'eventTypes',
            'tab'
        ));
    }

    public function show(Event $event): View
    {
        abort_unless($event->is_published && !in_array($event->status, ['draft', 'archived']), 404);

        $user = auth()->user();
        $registration = EventRegistration::where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->first();

        $activeSessions = $event->attendanceSessions()
            ->where('is_active', true)
            ->orderBy('opens_at')
            ->get();

        $attendanceRecords = collect();
        if ($user->student) {
            $attendanceRecords = $event->attendanceRecords()
                ->where('participant_type', 'student')
                ->where('participant_id', $user->student->id)
                ->get();
        } elseif ($registration) {
            $attendanceRecords = $event->attendanceRecords()
                ->where('participant_type', 'non_student')
                ->where('participant_id', $registration->id)
                ->get();
        }

        return view('events.show', compact('event', 'registration', 'activeSessions', 'attendanceRecords'));
    }

    public function register(Request $request, Event $event): RedirectResponse
    {
        abort_unless($event->is_published && !in_array($event->status, ['draft', 'archived']), 404);

        if (!$event->isRegistrationOpen()) {
            return back()->with('error', 'Registration for this event is closed or at full capacity.');
        }

        $user = auth()->user();
        $student = $user->student;

        // Validate based on event field settings
        $rules = [];
        if ($event->tshirt_enabled) {
            $tshirtRule = ['nullable', 'string', 'in:' . implode(',', EventRegistration::TSHIRT_SIZES)];
            if ($event->tshirt_required) {
                $tshirtRule[0] = 'required';
            }
            $rules['tshirt_size'] = $tshirtRule;
        }

        if ($event->dietary_enabled) {
            $dietaryRule = ['nullable', 'string', 'in:' . implode(',', EventRegistration::FOOD_RESTRICTIONS)];
            if ($event->dietary_required) {
                $dietaryRule[0] = 'required';
            }
            $rules['food_restrictions'] = $dietaryRule;
            $rules['food_restriction_details'] = ['nullable', 'string', 'max:500'];
        }

        $validatedFields = $request->validate($rules);

        try {
            $registration = \Illuminate\Support\Facades\DB::transaction(function () use ($event, $user, $student, $validatedFields) {
                $lockedEvent = \App\Support\RowLock::acquire(Event::class, $event->id);
                if (!$lockedEvent || !$lockedEvent->isRegistrationOpen()) {
                    throw new \RuntimeException('Registration for this event is closed or at full capacity.');
                }

                $existing = EventRegistration::where('event_id', $lockedEvent->id)
                    ->where(function ($q) use ($user, $student) {
                        $q->where('user_id', $user->id)
                          ->orWhere('email', strtolower($user->email));
                        if ($student) {
                            $q->orWhere('email', strtolower($student->email));
                        }
                    })
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    throw new \RuntimeException('You are already registered for this event.');
                }

                $tshirtSize = null;
                if ($lockedEvent->tshirt_enabled) {
                    $tshirtSize = $validatedFields['tshirt_size'] ?? null;
                    if (!$tshirtSize && $lockedEvent->tshirt_required) {
                        $tshirtSize = 'L';
                    }
                }

                $foodRestrictions = null;
                $foodRestrictionDetails = null;
                if ($lockedEvent->dietary_enabled) {
                    $foodRestrictions = $validatedFields['food_restrictions'] ?? ($lockedEvent->dietary_required ? 'None' : null);
                    $foodRestrictionDetails = $validatedFields['food_restriction_details'] ?? null;
                }

                $fullName = $student ? "{$student->first_name} {$student->last_name}" : $user->name;
                $email = $student ? $student->email : $user->email;
                $program = $student ? $student->program : 'Bachelor of Science in Information Technology (BSIT)';
                $yearLevel = $student ? $student->year_level : '1st Year';

                return EventRegistration::create([
                    'event_id' => $lockedEvent->id,
                    'user_id' => $user->id,
                    'full_name' => $fullName,
                    'email' => strtolower($email),
                    'organization' => 'Computing Society',
                    'program' => $program,
                    'year_level' => $yearLevel,
                    'tshirt_size' => $tshirtSize,
                    'food_restrictions' => $foodRestrictions,
                    'food_restriction_details' => $foodRestrictionDetails,
                    'confirmed' => true,
                    'status' => 'approved',
                    'reviewed_by' => $user->id,
                    'reviewed_at' => now(),
                    'qr_token' => \Illuminate\Support\Str::random(48),
                    'qr_expires_at' => $lockedEvent->ends_at ?? ($lockedEvent->event_date ? $lockedEvent->event_date->endOfDay() : now()->addMonths(3)),
                ]);
            });

            \App\Services\NotificationService::send(
                $user,
                'event_registration',
                'Registration Confirmed: ' . $event->name,
                'Your registration for ' . $event->name . ' is confirmed. Your digital ticket QR is ready.',
                route('tickets.show', $registration)
            );

            return redirect()->route('tickets.show', $registration)->with('success', 'You have successfully registered for ' . $event->name . '!');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
