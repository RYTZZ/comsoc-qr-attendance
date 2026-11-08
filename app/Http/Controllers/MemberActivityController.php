<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityRegistration;
use App\Models\ActivitySession;
use App\Models\ActivityTeam;
use App\Models\ActivityTeamMember;
use App\Models\User;
use App\Services\ActivityRegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberActivityController extends Controller
{
    public function __construct(
        private ActivityRegistrationService $registrationService
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('home');
        }

        $query = Activity::withCount('sessions')
            ->with('sessions')
            ->whereNotIn('status', ['draft', 'archived', 'cancelled']);

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('venue', 'like', "%{$search}%");
            });
        }

        $activities = $query->orderBy('starts_at', 'asc')->orderByDesc('created_at')->paginate(9)->withQueryString();

        $userRegistrations = ActivityRegistration::with('sessionRegistrations')
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('activity_id');

        $pendingInvitations = ActivityTeamMember::with(['team.activity', 'inviter'])
            ->where('user_id', $user->id)
            ->where('invitation_status', 'pending')
            ->get();

        $categories = Activity::CATEGORIES;

        return view('activities.index', compact(
            'activities',
            'userRegistrations',
            'pendingInvitations',
            'categories'
        ));
    }

    public function show(Request $request, Activity $activity): View
    {
        abort_unless(!in_array($activity->status, ['draft', 'archived'], true), 404);

        $user = $request->user();
        $activity->load([
            'sessions' => fn ($q) => $q->with('registrationForm'),
            'registrationForm',
        ]);

        $registration = ActivityRegistration::with([
            'sessionRegistrations.session',
            'sessionRegistrations.attendance',
            'sessionRegistrations.answers',
            'parentAnswers',
            'team.members.user',
        ])->where('activity_id', $activity->id)
            ->where('user_id', $user->id)
            ->first();

        $userTeamMembership = ActivityTeamMember::with(['team.members.user', 'team.captain'])
            ->where('activity_id', $activity->id)
            ->where('user_id', $user->id)
            ->whereIn('invitation_status', ['pending', 'accepted'])
            ->first();

        $eligibleMembers = collect();
        if (in_array($activity->registration_mode, ['team', 'both'], true)) {
            $eligibleMembers = User::with('student')
                ->where('is_active', true)
                ->whereIn('role', ['student', 'member'])
                ->where('id', '!=', $user->id)
                ->orderBy('name')
                ->limit(250)
                ->get();
        }

        return view('activities.show', compact(
            'activity',
            'registration',
            'userTeamMembership',
            'eligibleMembers'
        ));
    }

    public function register(Request $request, Activity $activity): RedirectResponse
    {
        abort_unless(!in_array($activity->status, ['draft', 'archived'], true), 404);

        $user = $request->user();

        if (!$user->isStudent() && !$user->isMember()) {
            abort(403, 'Only students can submit activity registrations.');
        }

        $sessionIds = array_values(array_filter((array) $request->input('session_ids', [])));
        $parentAnswers = (array) $request->input('answers', []);
        $parentFiles = (array) $request->file('answers', []);
        $sessionAnswersMap = (array) $request->input('session_answers', []);
        $sessionFilesMap = (array) $request->file('session_answers', []);
        $isIndependent = $request->boolean('independent_session_mode', false);

        $team = null;
        if ($teamId = $request->input('team_id')) {
            $team = ActivityTeam::where('activity_id', $activity->id)->whereKey($teamId)->firstOrFail();
        }

        $result = $this->registrationService->registerStudent(
            activity: $activity,
            user: $user,
            sessionIds: $sessionIds,
            parentAnswers: $parentAnswers,
            parentFiles: $parentFiles,
            sessionAnswersMap: $sessionAnswersMap,
            sessionFilesMap: $sessionFilesMap,
            team: $team,
            isIndependentSessionMode: $isIndependent
        );

        $reg = $result['registration'];
        $warnings = $result['warnings'] ?? [];

        $msg = "Registration submitted successfully ({$reg->ticket_code}). Status: " . strtoupper($reg->status) . ".";
        if (!empty($warnings)) {
            $msg .= ' Note: ' . implode(' ', $warnings);
        }

        return redirect()->route('activities.my-registrations')
            ->with('success', $msg);
    }

    public function createTeam(Request $request, Activity $activity): RedirectResponse
    {
        $user = $request->user();
        if (!$user->isStudent() && !$user->isMember()) {
            abort(403, 'Only students can create activity teams.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'project_title' => ['nullable', 'string', 'max:255'],
            'project_description' => ['nullable', 'string', 'max:3000'],
            'track' => ['nullable', 'string', 'max:150'],
            'invited_user_ids' => ['nullable', 'array'],
            'invited_user_ids.*' => ['uuid', 'exists:users,id'],
        ]);

        $team = $this->registrationService->createTeam(
            $activity,
            $user,
            $validated,
            $validated['invited_user_ids'] ?? []
        );

        return redirect()->route('activities.show', $activity)
            ->with('success', "Team '{$team->name}' created! Invited members can now accept their invitations.");
    }

    public function inviteTeamMember(Request $request, ActivityTeam $team): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'uuid', 'exists:users,id'],
        ]);

        $invitee = User::findOrFail($validated['user_id']);
        $this->registrationService->inviteMemberToTeam($team, $request->user(), $invitee);

        return back()->with('success', "Invitation sent to {$invitee->name}.");
    }

    public function respondTeamInvitation(Request $request, ActivityTeamMember $membership): RedirectResponse
    {
        $validated = $request->validate([
            'decision' => ['required', 'in:accepted,rejected'],
        ]);

        $this->registrationService->respondToTeamInvitation($membership, $request->user(), $validated['decision']);

        return back()->with('success', "You have {$validated['decision']} the invitation to join team '{$membership->team->name}'.");
    }

    public function cancelRegistration(Request $request, ActivityRegistration $registration): RedirectResponse
    {
        $this->registrationService->cancelRegistrationByStudent($registration, $request->user());

        return back()->with('success', "Your registration ({$registration->ticket_code}) has been cancelled.");
    }

    public function switchSession(Request $request, \App\Models\ActivitySessionRegistration $sessionRegistration): RedirectResponse
    {
        $validated = $request->validate([
            'target_session_id' => ['required', 'uuid', 'exists:activity_sessions,id'],
        ]);

        $target = ActivitySession::findOrFail($validated['target_session_id']);
        $this->registrationService->switchSession($sessionRegistration, $target, $request->user());

        return back()->with('success', "Session switched to '{$target->name}'.");
    }

    public function myRegistrations(Request $request): View
    {
        $user = $request->user();

        $registrations = ActivityRegistration::with([
            'activity.sessions',
            'team.members.user',
            'sessionRegistrations.session',
            'sessionRegistrations.attendance',
            'answers',
        ])->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        $pendingInvitations = ActivityTeamMember::with(['team.activity', 'team.captain'])
            ->where('user_id', $user->id)
            ->where('invitation_status', 'pending')
            ->get();

        $totalExpEarned = $registrations->sum('parent_exp_awarded')
            + $registrations->flatMap(fn ($r) => $r->sessionRegistrations)->sum('exp_awarded');
        $totalPointsEarned = $registrations->sum('parent_points_awarded')
            + $registrations->flatMap(fn ($r) => $r->sessionRegistrations)->sum('points_awarded');

        return view('activities.my-registrations', compact(
            'registrations',
            'pendingInvitations',
            'totalExpEarned',
            'totalPointsEarned'
        ));
    }
}

