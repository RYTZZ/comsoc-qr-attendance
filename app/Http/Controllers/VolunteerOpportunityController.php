<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\User;
use App\Models\VolunteerApplication;
use App\Models\VolunteerOpportunity;
use App\Services\ExpLedgerService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class VolunteerOpportunityController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $category = $request->input('category', 'all');
        $tab = $request->input('tab', 'available'); // available, my_applications, verified_hours
        $search = $request->input('search');

        $query = VolunteerOpportunity::with(['event', 'coordinator'])
            ->withCount('acceptedApplications');

        if ($tab === 'my_applications') {
            $query->whereHas('applications', fn($a) => $a->where('user_id', $user->id));
        } else {
            // available
            $query->where('is_active', true)->whereIn('status', ['open', 'in_progress']);
        }

        if ($category !== 'all' && !empty($category)) {
            $query->where('category', $category);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('responsibilities', 'like', "%{$search}%");
            });
        }

        $opportunities = $query->orderBy('starts_at', 'asc')->paginate(12)->withQueryString();

        // User applications lookup
        $myApplications = VolunteerApplication::where('user_id', $user->id)
            ->with('opportunity')
            ->get()
            ->keyBy('volunteer_opportunity_id');

        $verifiedHoursSum = (float) VolunteerApplication::where('user_id', $user->id)
            ->where('status', 'completed')
            ->sum('approved_service_hours');

        return view('volunteers.index', compact('opportunities', 'category', 'tab', 'search', 'myApplications', 'verifiedHoursSum'));
    }

    public function show(Request $request, VolunteerOpportunity $opportunity): View
    {
        $user = $request->user();
        $opportunity->load(['event', 'coordinator', 'acceptedApplications.user.student']);

        $myApplication = VolunteerApplication::where('volunteer_opportunity_id', $opportunity->id)
            ->where('user_id', $user->id)
            ->first();

        $isCoordinatorOrAdmin = $user->isAdmin() || $opportunity->coordinator_id === $user->id;

        $pendingApplications = collect();
        if ($isCoordinatorOrAdmin) {
            $pendingApplications = $opportunity->applications()
                ->with('user.student')
                ->where('status', 'applied')
                ->latest()
                ->get();
        }

        return view('volunteers.show', compact('opportunity', 'myApplication', 'isCoordinatorOrAdmin', 'pendingApplications'));
    }

    public function apply(Request $request, VolunteerOpportunity $opportunity): RedirectResponse
    {
        $user = $request->user();

        if (!$opportunity->is_active || $opportunity->status !== 'open') {
            return back()->withErrors(['message' => 'This volunteer opportunity is currently not accepting applications.']);
        }

        if ($opportunity->isExpired()) {
            return back()->withErrors(['message' => 'The application deadline for this opportunity has passed.']);
        }

        // Enforce capacity in transaction
        if ($opportunity->acceptedApplications()->count() >= $opportunity->capacity) {
            return back()->withErrors(['message' => 'This opportunity has already reached its volunteer capacity.']);
        }

        $validated = $request->validate([
            'motivation_statement' => ['required', 'string', 'min:10', 'max:1000'],
            'relevant_skills' => ['nullable', 'string', 'max:1000'],
            'assigned_shift' => ['nullable', 'string', 'max:100'],
        ]);

        $existing = VolunteerApplication::where('volunteer_opportunity_id', $opportunity->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing && $existing->status !== 'withdrawn') {
            return back()->withErrors(['message' => 'You have already applied for this volunteer opportunity.']);
        }

        DB::transaction(function () use ($opportunity, $user, $validated, $existing) {
            if ($existing) {
                $existing->update([
                    'motivation_statement' => $validated['motivation_statement'],
                    'relevant_skills' => $validated['relevant_skills'] ?? null,
                    'assigned_shift' => $validated['assigned_shift'] ?? null,
                    'status' => 'applied',
                ]);
            } else {
                VolunteerApplication::create([
                    'volunteer_opportunity_id' => $opportunity->id,
                    'user_id' => $user->id,
                    'motivation_statement' => $validated['motivation_statement'],
                    'relevant_skills' => $validated['relevant_skills'] ?? null,
                    'assigned_shift' => $validated['assigned_shift'] ?? null,
                    'status' => 'applied',
                ]);
            }

            if ($opportunity->coordinator_id) {
                NotificationService::send(
                    $opportunity->coordinator_id,
                    'volunteer_application',
                    'New Volunteer Application',
                    "{$user->name} applied for '{$opportunity->title}'.",
                    route('volunteers.show', $opportunity)
                );
            }
        });

        return back()->with('status', 'Your volunteer application has been submitted successfully.');
    }

    public function create(): View
    {
        $events = Event::where('is_published', true)->orderByDesc('event_date')->take(20)->get();
        return view('volunteers.create', compact('events'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (!$user->isAdmin() && !$user->isStaff()) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:10', 'max:5000'],
            'event_id' => ['nullable', 'uuid', 'exists:events,id'],
            'category' => ['required', 'string', 'in:' . implode(',', array_keys(VolunteerOpportunity::CATEGORIES))],
            'responsibilities' => ['required', 'string', 'min:10', 'max:5000'],
            'location_type' => ['required', 'string', 'in:onsite,hybrid,remote'],
            'location_details' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'application_deadline' => ['nullable', 'date'],
            'capacity' => ['required', 'integer', 'min:1', 'max:100'],
            'expected_hours' => ['required', 'numeric', 'min:0.5', 'max:40'],
            'exp_reward_per_hour' => ['required', 'integer', 'min:0', 'max:500'],
        ]);

        $opportunity = VolunteerOpportunity::create(array_merge($validated, [
            'coordinator_id' => $user->id,
            'status' => 'open',
            'is_active' => true,
        ]));

        return redirect()->route('volunteers.show', $opportunity)->with('status', 'Volunteer opportunity published.');
    }

    public function decideApplication(Request $request, VolunteerApplication $application): RedirectResponse
    {
        $user = $request->user();
        $opportunity = $application->opportunity;

        if ($opportunity->coordinator_id !== $user->id && !$user->isAdmin()) {
            abort(403, 'Unauthorized.');
        }

        $action = $request->input('action'); // accept, decline
        $shift = $request->input('assigned_shift');

        DB::transaction(function () use ($application, $opportunity, $user, $action, $shift) {
            if ($action === 'accept') {
                if ($opportunity->acceptedApplications()->count() >= $opportunity->capacity) {
                    throw new \Exception('Volunteer capacity reached.');
                }

                $application->update([
                    'status' => 'accepted',
                    'assigned_shift' => $shift ?? $application->assigned_shift,
                    'reviewed_by' => $user->id,
                    'reviewed_at' => now(),
                ]);

                NotificationService::send(
                    $application->user_id,
                    'volunteer_accepted',
                    'Volunteer Application Accepted!',
                    "You have been accepted as a volunteer for '{$opportunity->title}'. Shift: {$application->assigned_shift}",
                    route('volunteers.show', $opportunity)
                );
            } else {
                $application->update([
                    'status' => 'declined',
                    'reviewed_by' => $user->id,
                    'reviewed_at' => now(),
                ]);

                NotificationService::send(
                    $application->user_id,
                    'volunteer_declined',
                    'Volunteer Application Update',
                    "Your application for '{$opportunity->title}' was not accepted at this time.",
                    route('volunteers.show', $opportunity)
                );
            }
        });

        return back()->with('status', "Volunteer application {$action}ed.");
    }

    public function approveHours(Request $request, VolunteerApplication $application, ExpLedgerService $expService): RedirectResponse
    {
        $user = $request->user();
        $opportunity = $application->opportunity;

        if ($opportunity->coordinator_id !== $user->id && !$user->isAdmin()) {
            abort(403, 'Unauthorized to approve volunteer hours.');
        }

        $validated = $request->validate([
            'approved_service_hours' => ['required', 'numeric', 'min:0.5', 'max:50'],
            'hours_approval_reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        DB::transaction(function () use ($application, $opportunity, $user, $validated, $expService) {
            $hours = (float) $validated['approved_service_hours'];
            $expReward = (int) round($hours * ($opportunity->exp_reward_per_hour ?? 50));

            $volunteerUser = $application->user;

            // Authoritative EXP award via central ExpLedgerService with idempotency
            $idempotencyKey = "vol_hours_{$application->id}";
            $txId = null;

            if ($expReward > 0) {
                $res = $expService->creditExp(
                    $volunteerUser,
                    $expReward,
                    'contribution',
                    $opportunity->id,
                    "Volunteer Service: {$opportunity->title} ({$hours} verified hours)",
                    null,
                    $idempotencyKey,
                    $user
                );
                $txId = $res['transaction']->id ?? null;
            }

            $application->update([
                'status' => 'completed',
                'approved_service_hours' => $hours,
                'hours_approval_reason' => $validated['hours_approval_reason'],
                'hours_approved_by' => $user->id,
                'hours_approved_at' => now(),
                'awarded_exp' => $expReward,
                'xp_transaction_id' => $txId,
            ]);

            NotificationService::send(
                $volunteerUser->id,
                'volunteer_hours_approved',
                'Volunteer Hours & EXP Approved!',
                "Your {$hours} hours for '{$opportunity->title}' were approved! +{$expReward} EXP credited to your ledger.",
                route('volunteers.show', $opportunity)
            );
        });

        return back()->with('status', "Volunteer service hours approved and EXP credited successfully.");
    }
}
