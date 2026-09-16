<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Organization;
use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicRegistrationController extends Controller
{
    public function index(Request $request): View
    {
        $eventsQuery = Event::where('allow_non_students', true)
            ->where('is_published', true)
            ->orderBy('event_date', 'asc');

        $events = $eventsQuery->get();

        $selectedEventId = $request->query('event_id');
        $event = null;

        if ($selectedEventId) {
            $event = $events->firstWhere('id', $selectedEventId);
        }

        if (!$event && $events->isNotEmpty()) {
            $openEvent = $events->first(fn($e) => $e->isRegistrationOpen());
            $event = $openEvent ?: $events->first();
        }

        return $this->renderRegistrationView($event, $events);
    }

    public function show(Event $event): View
    {
        abort_unless($event->allow_non_students && $event->is_published, 404);

        $events = Event::where('allow_non_students', true)
            ->where('is_published', true)
            ->orderBy('event_date', 'asc')
            ->get();

        return $this->renderRegistrationView($event, $events);
    }

    private function renderRegistrationView(?Event $event, $events): View
    {
        $tshirtSizes = EventRegistration::TSHIRT_SIZES;
        $programs = Program::activeOptions();
        if (empty($programs)) {
            $programs = EventRegistration::PROGRAMS;
        }
        $yearLevels = EventRegistration::YEAR_LEVELS;
        $foodRestrictions = EventRegistration::FOOD_RESTRICTIONS;
        $organizations = Organization::where('is_active', true)->orderBy('name')->get();

        $isOpen = $event ? $event->isRegistrationOpen() : false;
        $registrationStatus = $event ? $event->registration_status : 'Registration Closed';
        $deadline = $event ? $event->effectiveRegistrationDeadline() : null;

        return view('public.register', compact(
            'event',
            'events',
            'organizations',
            'tshirtSizes',
            'programs',
            'yearLevels',
            'foodRestrictions',
            'isOpen',
            'registrationStatus',
            'deadline'
        ));
    }

    public function store(Request $request, ?Event $event = null): RedirectResponse
    {
        if (!$event || !$event->exists) {
            $eventId = $request->input('event_id');
            $event = Event::where('id', $eventId)
                ->where('allow_non_students', true)
                ->where('is_published', true)
                ->first();

            if (!$event) {
                return back()->with('error', 'Please select a valid event for registration.')->withInput();
            }
        }

        abort_unless($event->allow_non_students && $event->is_published, 404);

        if (!$event->isRegistrationOpen()) {
            if ($event->isFull()) {
                return back()->with('error', 'Registration for this event is full.')->withInput();
            }
            return back()->with('error', 'Registration for this event is now closed.')->withInput();
        }

        if ($event->isFull()) {
            return back()->with('error', 'Registration is full for this event. No additional registrations can be accepted.')->withInput();
        }

        $activeOrgNames = Organization::where('is_active', true)->pluck('name')->toArray();
        $validOrgChoices = array_merge($activeOrgNames, ['Other']);
        $validPrograms = array_values(array_unique(array_merge(Program::activeOptions(), EventRegistration::PROGRAMS)));

        $validated = $request->validate([
            'event_id' => ['nullable', 'exists:events,id'],
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'organization' => ['required', 'string', 'in:' . implode(',', $validOrgChoices)],
            'custom_organization' => ['nullable', 'string', 'max:255', 'required_if:organization,Other'],
            'program' => ['required', 'string', 'in:' . implode(',', $validPrograms)],
            'year_level' => ['required', 'string', 'in:' . implode(',', EventRegistration::YEAR_LEVELS)],
            'tshirt_size' => ['required', 'string', 'in:' . implode(',', EventRegistration::TSHIRT_SIZES)],
            'food_restrictions' => ['required', 'string', 'in:' . implode(',', EventRegistration::FOOD_RESTRICTIONS)],
            'food_restriction_details' => ['nullable', 'string', 'max:500', 'required_if:food_restrictions,Allergies,Other'],
            'confirmed' => ['accepted'],
        ], [
            'confirmed.accepted' => 'You must confirm that your information is accurate and agree to participate.',
            'food_restriction_details.required_if' => 'Please provide details for your food restrictions or allergies.',
            'tshirt_size.in' => 'Please select a valid T-Shirt size from the options provided.',
            'year_level.in' => 'Please select a valid year level from the options provided.',
            'organization.required' => 'Please select your School / University.',
            'organization.in' => 'Please select a valid School / University from the list or choose Other.',
            'custom_organization.required_if' => 'Please enter your School / University name.',
            'program.required' => 'Please select your Program.',
            'program.in' => 'Please select a valid Program from the options provided.',
        ]);

        $exists = EventRegistration::where('event_id', $event->id)
            ->where('email', strtolower(trim($validated['email'])))
            ->exists();

        if ($exists) {
            return back()->with('error', 'You have already registered for this event.')->withInput();
        }

        $selectedOrg = null;
        $finalOrgDisplayName = $validated['organization'];
        $customOrgName = null;

        if ($validated['organization'] === 'Other') {
            $customOrgName = trim($validated['custom_organization']);
            $finalOrgDisplayName = $customOrgName;
        } else {
            $selectedOrg = Organization::where('name', $validated['organization'])->first();
        }

        $registration = EventRegistration::create([
            'event_id' => $event->id,
            'full_name' => trim($validated['full_name']),
            'email' => strtolower(trim($validated['email'])),
            'phone' => $request->input('phone'),
            'organization_id' => $selectedOrg?->id,
            'organization' => $finalOrgDisplayName,
            'custom_organization' => $customOrgName,
            'program' => trim($validated['program']),
            'year_level' => $validated['year_level'],
            'tshirt_size' => $validated['tshirt_size'],
            'food_restrictions' => $validated['food_restrictions'],
            'food_restriction_details' => in_array($validated['food_restrictions'], ['Allergies', 'Other'])
                ? trim($validated['food_restriction_details'] ?? '')
                : null,
            'confirmed' => true,
            'status' => 'pending',
        ]);

        return redirect()->route('public.register.success', [
            'event' => $event,
            'registration' => $registration->id,
        ])->with('success', 'Registration submitted. You will be notified upon approval.');
    }

    public function success(Request $request, ?Event $event = null): View
    {
        if (!$event || !$event->exists) {
            $event = Event::find($request->query('event_id')) ?: Event::where('allow_non_students', true)->latest('event_date')->first();
        }

        $registration = null;
        if ($request->has('registration') && $event) {
            $registration = EventRegistration::where('event_id', $event->id)
                ->where('id', $request->query('registration'))
                ->first();
        }

        return view('public.register-success', compact('event', 'registration'));
    }
}
