<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\ActivityAttendance;
use App\Models\ActivityRegistration;
use App\Models\ActivityRegistrationAnswer;
use App\Models\ActivityReward;
use App\Models\ActivitySession;
use App\Models\ActivitySessionRegistration;
use App\Models\ActivityTeam;
use App\Models\Program;
use App\Models\RegistrationForm;
use App\Models\Student;
use App\Models\User;
use App\Services\ActivityAttendanceRewardService;
use App\Services\ActivityRegistrationService;
use App\Services\AuditLogger;
use App\Services\DynamicFormService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityManagementController extends Controller
{
    public function __construct(
        private DynamicFormService $formService,
        private ActivityRegistrationService $registrationService,
        private ActivityAttendanceRewardService $attendanceRewardService
    ) {}

    private function assertCanManageActivity(Request $request, ?Activity $activity = null): void
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return;
        }

        if ($activity && $activity->isUserAuthorizedOrganizer($user)) {
            return;
        }

        abort(403, 'You do not have permission to manage this activity.');
    }

    /**
     * Super Admin & Admin Activity Dashboard + List
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $query = Activity::withCount(['sessions', 'registrations', 'sessionRegistrations', 'attendances'])
            ->with(['registrationForm', 'creator']);

        if (!$user->isSuperAdmin() && !$user->isAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                    ->orWhereHas('organizers', fn ($oq) => $oq->where('users.id', $user->id));
            });
        }

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($activityId = $request->query('activity_id')) {
            $query->where('id', $activityId);
        }

        if ($dateFrom = $request->query('date_from')) {
            $query->whereDate('starts_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->query('date_to')) {
            $query->whereDate('starts_at', '<=', $dateTo);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('venue', 'like', "%{$search}%")
                    ->orWhere('organizer_name', 'like', "%{$search}%");
            });
        }

        $activities = $query->orderByDesc('created_at')->paginate(12)->withQueryString();

        // Real database dashboard overview metrics
        $stats = [
            'total_parent_events' => Activity::count(),
            'total_sessions' => ActivitySession::count(),
            'active_events' => Activity::whereIn('status', ['published', 'registration_open', 'ongoing'])->count(),
            'published_forms' => RegistrationForm::where('status', RegistrationForm::STATUS_PUBLISHED)->count(),
            'total_registrations' => ActivityRegistration::count(),
            'pending_applications' => ActivityRegistration::where('status', 'pending')->count()
                + ActivitySessionRegistration::where('status', 'pending')->count(),
            'approved_registrations' => ActivityRegistration::where('status', 'approved')->count(),
            'waitlisted_participants' => ActivityRegistration::where('status', 'waitlisted')->count()
                + ActivitySessionRegistration::where('status', 'waitlisted')->count(),
            'total_attendances' => ActivityAttendance::whereNotNull('check_in_at')->count(),
            'completed_sessions' => ActivitySessionRegistration::where('completion_status', 'completed')->count(),
            'total_exp_distributed' => (int) ActivityReward::sum('exp_amount'),
            'total_points_distributed' => (int) ActivityReward::sum('points_amount'),
        ];

        $categories = Activity::CATEGORIES;
        $statuses = Activity::STATUSES;
        $allActivitiesForFilter = Activity::select('id', 'title')->orderBy('title')->get();

        return view('admin.activities.index', compact(
            'activities',
            'stats',
            'categories',
            'statuses',
            'allActivitiesForFilter'
        ));
    }

    public function create(Request $request): View
    {
        $this->assertCanManageActivity($request);

        $categories = Activity::CATEGORIES;
        $programs = Program::activeOptions();
        $yearLevels = Student::YEAR_LEVELS;
        $publishedForms = RegistrationForm::where('status', RegistrationForm::STATUS_PUBLISHED)
            ->orderBy('title')
            ->get();
        $organizers = User::whereIn('role', ['super_admin', 'comsoc_admin', 'admin', 'president', 'executive_secretary', 'event_organizer', 'staff'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.activities.form', [
            'activity' => null,
            'categories' => $categories,
            'programs' => $programs,
            'yearLevels' => $yearLevels,
            'publishedForms' => $publishedForms,
            'organizers' => $organizers,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->assertCanManageActivity($request);

        $validated = $this->validateActivityRequest($request);
        $user = $request->user();

        $activity = DB::transaction(function () use ($request, $validated, $user) {
            $slug = $this->generateUniqueSlug($validated['title']);
            $activeYear = AcademicYear::where('is_active', true)->first();

            $bannerPath = null;
            if ($request->hasFile('banner')) {
                $bannerPath = $request->file('banner')->store('activity-banners', 'public');
            }

            $formId = null;
            $formVersion = null;
            $formSnapshot = null;

            if ($user->isSuperAdmin() && !empty($validated['registration_form_id'])) {
                $form = RegistrationForm::find($validated['registration_form_id']);
                if ($form) {
                    $formId = $form->id;
                    $formVersion = $form->version;
                    $formSnapshot = $form->getActiveSchema();
                }
            }

            $activity = Activity::create([
                'academic_year_id' => $activeYear?->id,
                'title' => $validated['title'],
                'slug' => $slug,
                'description' => $validated['description'] ?? null,
                'category' => $validated['category'],
                'banner_path' => $bannerPath,
                'venue' => $validated['venue'] ?? null,
                'organizer_name' => $validated['organizer_name'] ?? 'COMSOC',
                'general_requirements' => $validated['general_requirements'] ?? null,
                'starts_at' => $validated['starts_at'] ?? null,
                'ends_at' => $validated['ends_at'] ?? null,
                'registration_opens_at' => $validated['registration_opens_at'] ?? null,
                'registration_closes_at' => $validated['registration_closes_at'] ?? null,
                'status' => $validated['status'] ?? 'draft',
                'is_multi_session' => $request->boolean('is_multi_session', false),
                'registration_mode' => $validated['registration_mode'] ?? 'individual',
                'min_team_size' => (int) ($validated['min_team_size'] ?? 1),
                'max_team_size' => (int) ($validated['max_team_size'] ?? 5),
                'require_all_team_invites_accepted' => $request->boolean('require_all_team_invites_accepted', true),
                'allow_multiple_sessions' => $request->boolean('allow_multiple_sessions', true),
                'max_sessions_per_student' => $validated['max_sessions_per_student'] ?? null,
                'require_session_selection' => $request->boolean('require_session_selection', true),
                'allow_session_change' => $request->boolean('allow_session_change', false),
                'require_separate_session_approval' => $request->boolean('require_separate_session_approval', false),
                'require_parent_registration' => $request->boolean('require_parent_registration', true),
                'allow_independent_session_registration' => $request->boolean('allow_independent_session_registration', false),
                'conflict_policy' => $validated['conflict_policy'] ?? 'block',
                'overall_capacity' => $validated['overall_capacity'] ?? null,
                'requires_approval' => $request->boolean('requires_approval', false),
                'waitlist_enabled' => $request->boolean('waitlist_enabled', false),
                'allow_submission_editing' => $request->boolean('allow_submission_editing', false),
                'allow_cancellation' => $request->boolean('allow_cancellation', true),
                'allow_walk_in' => $request->boolean('allow_walk_in', false),
                'eligible_programs' => $validated['eligible_programs'] ?? [],
                'eligible_year_levels' => $validated['eligible_year_levels'] ?? [],
                'registration_form_id' => $formId,
                'form_version' => $formVersion,
                'form_snapshot' => $formSnapshot,
                'completion_mode' => $validated['completion_mode'] ?? 'any_session',
                'min_sessions_for_completion' => (int) ($validated['min_sessions_for_completion'] ?? 1),
                'award_parent_rewards' => $request->boolean('award_parent_rewards', false),
                'parent_exp_reward' => (int) ($validated['parent_exp_reward'] ?? 0),
                'parent_points_reward' => (int) ($validated['parent_points_reward'] ?? 0),
                'created_by' => $user->id,
            ]);

            if (!empty($validated['organizer_ids'])) {
                $syncData = [];
                foreach ($validated['organizer_ids'] as $orgId) {
                    $syncData[$orgId] = ['id' => (string) Str::uuid(), 'assigned_by' => $user->id];
                }
                $activity->organizers()->sync($syncData);
            }

            // Create default single session if not multi-session and no sessions passed
            if (!$activity->is_multi_session) {
                $activity->sessions()->create([
                    'name' => $activity->title . ' — Main Session',
                    'description' => $activity->description,
                    'track_or_category' => $activity->category,
                    'venue' => $activity->venue,
                    'starts_at' => $activity->starts_at,
                    'ends_at' => $activity->ends_at,
                    'registration_opens_at' => $activity->registration_opens_at,
                    'registration_closes_at' => $activity->registration_closes_at,
                    'capacity' => $activity->overall_capacity,
                    'waitlist_enabled' => $activity->waitlist_enabled,
                    'is_registration_open' => true,
                    'requires_approval' => $activity->requires_approval,
                    'allow_walk_in' => $activity->allow_walk_in,
                    'attendance_enabled' => true,
                    'qr_attendance_enabled' => true,
                    'require_check_out' => true,
                    'exp_reward' => (int) ($request->input('default_session_exp', 100)),
                    'points_reward' => (int) ($request->input('default_session_points', 10)),
                    'status' => 'open',
                    'sort_order' => 0,
                ]);
            }

            AuditLogger::log('activity.created', $activity, [], [
                'title' => $activity->title,
                'category' => $activity->category,
                'is_multi_session' => $activity->is_multi_session,
            ]);

            return $activity;
        });

        return redirect()->route('admin.activities.show', $activity)
            ->with('success', "Activity '{$activity->title}' created successfully.");
    }

    public function show(Request $request, Activity $activity): View
    {
        $this->assertCanManageActivity($request, $activity);

        $activity->load([
            'sessions' => fn ($q) => $q->withCount([
                'sessionRegistrations',
                'sessionRegistrations as approved_registrations_count' => fn ($sq) => $sq->where('status', 'approved'),
                'sessionRegistrations as waitlisted_registrations_count' => fn ($sq) => $sq->where('status', 'waitlisted'),
                'sessionRegistrations as completed_registrations_count' => fn ($sq) => $sq->where('completion_status', 'completed'),
                'attendances as checked_in_count' => fn ($aq) => $aq->whereNotNull('check_in_at'),
                'attendances as checked_out_count' => fn ($aq) => $aq->whereNotNull('check_out_at'),
            ])->with('registrationForm'),
            'registrationForm',
            'organizers',
            'teams.members.user',
            'teams.captain',
        ]);

        $regQuery = ActivityRegistration::with([
            'user.student',
            'team.members.user',
            'sessionRegistrations.session',
            'sessionRegistrations.attendance',
            'answers',
        ])->where('activity_id', $activity->id);

        if ($statusFilter = $request->query('reg_status')) {
            $regQuery->where('status', $statusFilter);
        }

        if ($sessionFilter = $request->query('session_id')) {
            $regQuery->whereHas('sessionRegistrations', fn ($sq) => $sq->where('activity_session_id', $sessionFilter));
        }

        if ($search = $request->query('search')) {
            $regQuery->where(function ($q) use ($search) {
                $q->where('ticket_code', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                    ->orWhereHas('team', fn ($tq) => $tq->where('name', 'like', "%{$search}%"));
            });
        }

        $registrations = $regQuery->latest()->paginate(20)->withQueryString();
        $publishedForms = RegistrationForm::where('status', RegistrationForm::STATUS_PUBLISHED)->orderBy('title')->get();

        return view('admin.activities.show', compact('activity', 'registrations', 'publishedForms'));
    }

    public function edit(Request $request, Activity $activity): View
    {
        $this->assertCanManageActivity($request, $activity);

        $activity->load(['organizers', 'registrationForm']);
        $categories = Activity::CATEGORIES;
        $programs = Program::activeOptions();
        $yearLevels = Student::YEAR_LEVELS;
        $publishedForms = RegistrationForm::where('status', RegistrationForm::STATUS_PUBLISHED)->orderBy('title')->get();
        $organizers = User::whereIn('role', ['super_admin', 'comsoc_admin', 'admin', 'president', 'executive_secretary', 'event_organizer', 'staff'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.activities.form', compact(
            'activity',
            'categories',
            'programs',
            'yearLevels',
            'publishedForms',
            'organizers'
        ));
    }

    public function update(Request $request, Activity $activity): RedirectResponse
    {
        $this->assertCanManageActivity($request, $activity);

        $validated = $this->validateActivityRequest($request, $activity);
        $user = $request->user();

        DB::transaction(function () use ($request, $activity, $validated, $user) {
            $oldValues = $activity->only(['title', 'status', 'category', 'overall_capacity', 'registration_form_id']);

            $updates = [
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'category' => $validated['category'],
                'venue' => $validated['venue'] ?? null,
                'organizer_name' => $validated['organizer_name'] ?? $activity->organizer_name,
                'general_requirements' => $validated['general_requirements'] ?? null,
                'starts_at' => $validated['starts_at'] ?? null,
                'ends_at' => $validated['ends_at'] ?? null,
                'registration_opens_at' => $validated['registration_opens_at'] ?? null,
                'registration_closes_at' => $validated['registration_closes_at'] ?? null,
                'status' => $validated['status'] ?? $activity->status,
                'is_multi_session' => $request->boolean('is_multi_session', false),
                'registration_mode' => $validated['registration_mode'] ?? 'individual',
                'min_team_size' => (int) ($validated['min_team_size'] ?? 1),
                'max_team_size' => (int) ($validated['max_team_size'] ?? 5),
                'require_all_team_invites_accepted' => $request->boolean('require_all_team_invites_accepted', false),
                'allow_multiple_sessions' => $request->boolean('allow_multiple_sessions', false),
                'max_sessions_per_student' => $validated['max_sessions_per_student'] ?? null,
                'require_session_selection' => $request->boolean('require_session_selection', false),
                'allow_session_change' => $request->has('allow_session_change')
                    ? $request->boolean('allow_session_change')
                    : (bool) $activity->allow_session_change,
                'require_separate_session_approval' => $request->boolean('require_separate_session_approval', false),
                'require_parent_registration' => $request->has('require_parent_registration')
                    ? $request->boolean('require_parent_registration')
                    : (bool) $activity->require_parent_registration,
                'allow_independent_session_registration' => $request->boolean('allow_independent_session_registration', false),
                'conflict_policy' => $validated['conflict_policy'] ?? 'block',
                'overall_capacity' => $validated['overall_capacity'] ?? null,
                'requires_approval' => $request->boolean('requires_approval', false),
                'waitlist_enabled' => $request->boolean('waitlist_enabled', false),
                'allow_submission_editing' => $request->boolean('allow_submission_editing', false),
                'allow_cancellation' => $request->boolean('allow_cancellation', false),
                'allow_walk_in' => $request->boolean('allow_walk_in', false),
                'eligible_programs' => $validated['eligible_programs'] ?? [],
                'eligible_year_levels' => $validated['eligible_year_levels'] ?? [],
                'completion_mode' => $validated['completion_mode'] ?? 'any_session',
                'min_sessions_for_completion' => (int) ($validated['min_sessions_for_completion'] ?? 1),
                'award_parent_rewards' => $request->boolean('award_parent_rewards', false),
                'parent_exp_reward' => (int) ($validated['parent_exp_reward'] ?? 0),
                'parent_points_reward' => (int) ($validated['parent_points_reward'] ?? 0),
            ];

            if ($request->hasFile('banner')) {
                $updates['banner_path'] = $request->file('banner')->store('activity-banners', 'public');
            }

            // Form assignment exclusively controlled by Super Admin
            if ($user->isSuperAdmin() && array_key_exists('registration_form_id', $validated)) {
                $form = !empty($validated['registration_form_id'])
                    ? RegistrationForm::find($validated['registration_form_id'])
                    : null;
                $this->formService->assignFormToActivity($activity, $form, $user);
            }

            $activity->update($updates);

            if (array_key_exists('organizer_ids', $validated)) {
                $syncData = [];
                foreach ($validated['organizer_ids'] ?? [] as $orgId) {
                    $syncData[$orgId] = ['id' => (string) Str::uuid(), 'assigned_by' => $user->id];
                }
                $activity->organizers()->sync($syncData);
            }

            AuditLogger::log('activity.updated', $activity, $oldValues, [
                'title' => $activity->title,
                'status' => $activity->status,
            ]);
        });

        return redirect()->route('admin.activities.show', $activity)
            ->with('success', "Activity '{$activity->title}' updated.");
    }

    /**
     * Store a new Session under a Parent Activity
     */
    public function storeSession(Request $request, Activity $activity): RedirectResponse
    {
        $this->assertCanManageActivity($request, $activity);

        $validated = $this->validateSessionRequest($request);
        $user = $request->user();

        $formId = null;
        $formVersion = null;
        $formSnapshot = null;

        if ($user->isSuperAdmin() && !empty($validated['registration_form_id'])) {
            $form = RegistrationForm::find($validated['registration_form_id']);
            if ($form) {
                $formId = $form->id;
                $formVersion = $form->version;
                $formSnapshot = $form->getActiveSchema();
            }
        }

        $session = $activity->sessions()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'track_or_category' => $validated['track_or_category'] ?? null,
            'venue' => $validated['venue'] ?? $activity->venue,
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'registration_opens_at' => $validated['registration_opens_at'] ?? null,
            'registration_closes_at' => $validated['registration_closes_at'] ?? null,
            'capacity' => $validated['capacity'] ?? null,
            'waitlist_enabled' => $request->boolean('waitlist_enabled', false),
            'is_registration_open' => $request->boolean('is_registration_open', true),
            'requires_approval' => $request->boolean('requires_approval', false),
            'allow_walk_in' => $request->boolean('allow_walk_in', false),
            'registration_form_id' => $formId,
            'form_version' => $formVersion,
            'form_snapshot' => $formSnapshot,
            'attendance_enabled' => $request->boolean('attendance_enabled', true),
            'qr_attendance_enabled' => $request->boolean('qr_attendance_enabled', true),
            'require_check_out' => $request->boolean('require_check_out', true),
            'late_threshold_minutes' => $validated['late_threshold_minutes'] ?? null,
            'min_attendance_minutes' => $validated['min_attendance_minutes'] ?? null,
            'min_attendance_percentage' => $validated['min_attendance_percentage'] ?? null,
            'require_organizer_verification' => $request->boolean('require_organizer_verification', false),
            'require_output_submission' => $request->boolean('require_output_submission', false),
            'exp_reward' => (int) ($validated['exp_reward'] ?? 100),
            'points_reward' => (int) ($validated['points_reward'] ?? 10),
            'status' => $validated['status'] ?? 'open',
            'sort_order' => (int) ($validated['sort_order'] ?? $activity->sessions()->count()),
        ]);

        if ($activity->sessions()->count() > 1 && !$activity->is_multi_session) {
            $activity->update(['is_multi_session' => true]);
        }

        AuditLogger::log('activity_session.created', $session, [], [
            'activity_id' => $activity->id,
            'name' => $session->name,
            'capacity' => $session->capacity,
            'exp_reward' => $session->exp_reward,
            'points_reward' => $session->points_reward,
        ]);

        return back()->with('success', "Session '{$session->name}' added to '{$activity->title}'.");
    }

    public function updateSession(Request $request, Activity $activity, ActivitySession $session): RedirectResponse
    {
        $this->assertCanManageActivity($request, $activity);
        abort_unless($session->activity_id === $activity->id, 404);

        $validated = $this->validateSessionRequest($request);
        $user = $request->user();
        $oldValues = $session->only(['name', 'capacity', 'exp_reward', 'points_reward', 'status']);

        $updates = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'track_or_category' => $validated['track_or_category'] ?? null,
            'venue' => $validated['venue'] ?? null,
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'registration_opens_at' => $validated['registration_opens_at'] ?? null,
            'registration_closes_at' => $validated['registration_closes_at'] ?? null,
            'capacity' => $validated['capacity'] ?? null,
            'waitlist_enabled' => $request->boolean('waitlist_enabled', false),
            'is_registration_open' => $request->boolean('is_registration_open', false),
            'requires_approval' => $request->boolean('requires_approval', false),
            'allow_walk_in' => $request->boolean('allow_walk_in', false),
            'attendance_enabled' => $request->boolean('attendance_enabled', false),
            'qr_attendance_enabled' => $request->boolean('qr_attendance_enabled', false),
            'require_check_out' => $request->boolean('require_check_out', false),
            'late_threshold_minutes' => $validated['late_threshold_minutes'] ?? null,
            'min_attendance_minutes' => $validated['min_attendance_minutes'] ?? null,
            'min_attendance_percentage' => $validated['min_attendance_percentage'] ?? null,
            'require_organizer_verification' => $request->boolean('require_organizer_verification', false),
            'require_output_submission' => $request->boolean('require_output_submission', false),
            'exp_reward' => (int) ($validated['exp_reward'] ?? $session->exp_reward),
            'points_reward' => (int) ($validated['points_reward'] ?? $session->points_reward),
            'status' => $validated['status'] ?? $session->status,
            'sort_order' => (int) ($validated['sort_order'] ?? $session->sort_order),
        ];

        if ($user->isSuperAdmin() && array_key_exists('registration_form_id', $validated)) {
            $form = !empty($validated['registration_form_id'])
                ? RegistrationForm::find($validated['registration_form_id'])
                : null;
            $this->formService->assignFormToSession($session, $form, $user);
        }

        $session->update($updates);

        AuditLogger::log('activity_session.updated', $session, $oldValues, [
            'name' => $session->name,
            'capacity' => $session->capacity,
            'exp_reward' => $session->exp_reward,
            'points_reward' => $session->points_reward,
        ]);

        return back()->with('success', "Session '{$session->name}' updated.");
    }

    public function destroySession(Request $request, Activity $activity, ActivitySession $session): RedirectResponse
    {
        $this->assertCanManageActivity($request, $activity);
        abort_unless($session->activity_id === $activity->id, 404);

        $name = $session->name;
        AuditLogger::log('activity_session.deleted', $session, ['name' => $name], []);
        $session->delete();

        return back()->with('success', "Session '{$name}' removed.");
    }

    /**
     * Registration Management Actions (Approve, Reject, Cancel, Session Approve/Reject, Team Review, Completion Verification)
     */
    public function approveRegistration(Request $request, ActivityRegistration $registration): RedirectResponse
    {
        $this->assertCanManageActivity($request, $registration->activity);

        $this->registrationService->approveParentRegistration(
            $registration,
            $request->user(),
            $request->boolean('cascade_sessions', false)
        );

        return back()->with('success', "Registration {$registration->ticket_code} approved.");
    }

    public function rejectRegistration(Request $request, ActivityRegistration $registration): RedirectResponse
    {
        $this->assertCanManageActivity($request, $registration->activity);

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        $this->registrationService->rejectParentRegistration($registration, $request->user(), $validated['rejection_reason']);

        return back()->with('success', "Registration {$registration->ticket_code} rejected.");
    }

    public function approveSessionRegistration(Request $request, ActivitySessionRegistration $sessionRegistration): RedirectResponse
    {
        $this->assertCanManageActivity($request, $sessionRegistration->activity);

        $this->registrationService->approveSessionRegistration($sessionRegistration, $request->user());

        return back()->with('success', "Session registration approved for {$sessionRegistration->user->name} ({$sessionRegistration->session->name}).");
    }

    public function rejectSessionRegistration(Request $request, ActivitySessionRegistration $sessionRegistration): RedirectResponse
    {
        $this->assertCanManageActivity($request, $sessionRegistration->activity);

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        $this->registrationService->rejectSessionRegistration($sessionRegistration, $request->user(), $validated['rejection_reason']);

        return back()->with('success', "Session registration rejected for {$sessionRegistration->user->name}.");
    }

    public function reviewTeam(Request $request, ActivityTeam $team): RedirectResponse
    {
        $this->assertCanManageActivity($request, $team->activity);

        $validated = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'rejection_reason' => ['required_if:decision,rejected', 'nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($team, $validated, $request) {
            $reviewer = $request->user();
            $team->update([
                'status' => $validated['decision'],
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'rejection_reason' => $validated['rejection_reason'] ?? null,
            ]);

            foreach ($team->registrations as $reg) {
                if ($validated['decision'] === 'approved') {
                    $this->registrationService->approveParentRegistration($reg, $reviewer, true);
                } else {
                    $this->registrationService->rejectParentRegistration($reg, $reviewer, $validated['rejection_reason'] ?? 'Team application rejected.');
                }
            }

            AuditLogger::log('activity_team.reviewed', $team, [], [
                'decision' => $validated['decision'],
                'reviewed_by' => $reviewer->id,
            ]);
        });

        return back()->with('success', "Team '{$team->name}' {$validated['decision']}.");
    }

    public function verifySessionCompletion(Request $request, ActivitySessionRegistration $sessionRegistration): RedirectResponse
    {
        $this->assertCanManageActivity($request, $sessionRegistration->activity);

        $validated = $request->validate([
            'organizer_verified' => ['nullable', 'boolean'],
            'output_submitted' => ['nullable', 'boolean'],
            'output_notes' => ['nullable', 'string', 'max:1000'],
            'completion_status' => ['nullable', Rule::in(ActivitySessionRegistration::COMPLETION_STATUSES)],
        ]);

        $result = $this->attendanceRewardService->verifySessionParticipant(
            $sessionRegistration,
            $request->user(),
            $request->boolean('organizer_verified', true),
            $request->boolean('output_submitted', $sessionRegistration->output_submitted),
            $validated['output_notes'] ?? null,
            $validated['completion_status'] ?? null
        );

        $msg = "Updated verification for {$sessionRegistration->user->name} in '{$sessionRegistration->session->name}'.";
        if (($result['exp_awarded'] ?? 0) > 0 || ($result['points_awarded'] ?? 0) > 0) {
            $msg .= " Awarded +{$result['exp_awarded']} EXP & +{$result['points_awarded']} Points!";
        }

        return back()->with('success', $msg);
    }

    public function exportRegistrationsCsv(Request $request, Activity $activity): StreamedResponse
    {
        $this->assertCanManageActivity($request, $activity);

        $registrations = ActivityRegistration::with([
            'user.student',
            'team',
            'sessionRegistrations.session',
            'sessionRegistrations.attendance',
            'answers',
        ])->where('activity_id', $activity->id)->orderBy('created_at')->get();

        $canViewStudentId = User::canViewStudentId($request->user());
        $filename = Str::slug($activity->title) . '-registrations-' . now()->format('Ymd-His') . '.csv';

        AuditLogger::log('activity_registrations.exported', $activity, [], ['count' => $registrations->count()]);

        return response()->streamDownload(function () use ($registrations, $canViewStudentId) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'Ticket Code',
                'Participant Name',
                'Member ID',
                'Student ID',
                'Email',
                'Program',
                'Year Level',
                'Registration Mode',
                'Team Name',
                'Parent Status',
                'Selected Sessions',
                'Session Statuses',
                'Completion Status',
                'EXP Earned',
                'Points Earned',
                'Submitted Answers',
                'Registered At',
            ]);

            foreach ($registrations as $reg) {
                $user = $reg->user;
                $sessionsList = $reg->sessionRegistrations->map(fn ($sr) => $sr->session?->name)->filter()->implode(' | ');
                $sessionStatuses = $reg->sessionRegistrations->map(fn ($sr) => ($sr->session?->name . ': ' . $sr->status . '/' . $sr->completion_status))->implode(' | ');
                $totalExp = $reg->parent_exp_awarded + $reg->sessionRegistrations->sum('exp_awarded');
                $totalPoints = $reg->parent_points_awarded + $reg->sessionRegistrations->sum('points_awarded');
                $answersSummary = $reg->answers->map(fn ($a) => "{$a->field_label}: {$a->displayValue()}")->implode('; ');

                fputcsv($out, [
                    $reg->ticket_code,
                    $user?->name,
                    $user?->member_id,
                    $canViewStudentId ? ($user?->student_number ?? $user?->student?->student_number ?? 'N/A') : 'REDACTED',
                    $user?->email,
                    $user?->getProfileProgram() ?? 'N/A',
                    $user?->getProfileYearLevel() ?? 'N/A',
                    $reg->registration_type,
                    $reg->team?->name ?? 'N/A',
                    $reg->status,
                    $sessionsList,
                    $sessionStatuses,
                    $reg->completion_status,
                    $totalExp,
                    $totalPoints,
                    $answersSummary,
                    $reg->created_at?->toDateTimeString(),
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function downloadAnswerAttachment(Request $request, ActivityRegistrationAnswer $answer)
    {
        $registration = $answer->registration;
        $user = $request->user();

        $isOwner = $registration->user_id === $user->id;
        $isAuthorizedOrganizer = $registration->activity->isUserAuthorizedOrganizer($user);

        abort_unless($isOwner || $isAuthorizedOrganizer, 403, 'Unauthorized file access.');
        abort_unless($answer->file_path && Storage::disk('local')->exists($answer->file_path), 404, 'File not found.');

        return Storage::disk('local')->download($answer->file_path, $answer->file_original_name ?: basename($answer->file_path));
    }

    private function validateActivityRequest(Request $request, ?Activity $activity = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category' => ['required', 'string', 'max:100'],
            'banner' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'venue' => ['nullable', 'string', 'max:255'],
            'organizer_name' => ['nullable', 'string', 'max:255'],
            'general_requirements' => ['nullable', 'string', 'max:3000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'registration_opens_at' => ['nullable', 'date'],
            'registration_closes_at' => ['nullable', 'date', 'after_or_equal:registration_opens_at'],
            'status' => ['required', Rule::in(Activity::STATUSES)],
            'is_multi_session' => ['nullable', 'boolean'],
            'registration_mode' => ['required', Rule::in(Activity::REGISTRATION_MODES)],
            'min_team_size' => ['nullable', 'integer', 'min:1', 'max:20'],
            'max_team_size' => ['nullable', 'integer', 'min:1', 'max:20'],
            'require_all_team_invites_accepted' => ['nullable', 'boolean'],
            'allow_multiple_sessions' => ['nullable', 'boolean'],
            'max_sessions_per_student' => ['nullable', 'integer', 'min:1', 'max:50'],
            'require_session_selection' => ['nullable', 'boolean'],
            'allow_session_change' => ['nullable', 'boolean'],
            'require_separate_session_approval' => ['nullable', 'boolean'],
            'require_parent_registration' => ['nullable', 'boolean'],
            'allow_independent_session_registration' => ['nullable', 'boolean'],
            'conflict_policy' => ['required', Rule::in(Activity::CONFLICT_POLICIES)],
            'overall_capacity' => ['nullable', 'integer', 'min:1'],
            'requires_approval' => ['nullable', 'boolean'],
            'waitlist_enabled' => ['nullable', 'boolean'],
            'allow_submission_editing' => ['nullable', 'boolean'],
            'allow_cancellation' => ['nullable', 'boolean'],
            'allow_walk_in' => ['nullable', 'boolean'],
            'eligible_programs' => ['nullable', 'array'],
            'eligible_programs.*' => ['string', 'max:100'],
            'eligible_year_levels' => ['nullable', 'array'],
            'eligible_year_levels.*' => ['string', 'max:50'],
            'registration_form_id' => ['nullable', 'uuid', 'exists:registration_forms,id'],
            'completion_mode' => ['nullable', Rule::in(Activity::COMPLETION_MODES)],
            'min_sessions_for_completion' => ['nullable', 'integer', 'min:1'],
            'award_parent_rewards' => ['nullable', 'boolean'],
            'parent_exp_reward' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'parent_points_reward' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'organizer_ids' => ['nullable', 'array'],
            'organizer_ids.*' => ['uuid', 'exists:users,id'],
        ]);
    }

    private function validateSessionRequest(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'track_or_category' => ['nullable', 'string', 'max:100'],
            'venue' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'registration_opens_at' => ['nullable', 'date'],
            'registration_closes_at' => ['nullable', 'date', 'after_or_equal:registration_opens_at'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'waitlist_enabled' => ['nullable', 'boolean'],
            'is_registration_open' => ['nullable', 'boolean'],
            'requires_approval' => ['nullable', 'boolean'],
            'allow_walk_in' => ['nullable', 'boolean'],
            'registration_form_id' => ['nullable', 'uuid', 'exists:registration_forms,id'],
            'attendance_enabled' => ['nullable', 'boolean'],
            'qr_attendance_enabled' => ['nullable', 'boolean'],
            'require_check_out' => ['nullable', 'boolean'],
            'late_threshold_minutes' => ['nullable', 'integer', 'min:0', 'max:360'],
            'min_attendance_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'min_attendance_percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'require_organizer_verification' => ['nullable', 'boolean'],
            'require_output_submission' => ['nullable', 'boolean'],
            'exp_reward' => ['required', 'integer', 'min:0', 'max:10000'],
            'points_reward' => ['required', 'integer', 'min:0', 'max:10000'],
            'status' => ['nullable', Rule::in(ActivitySession::STATUSES)],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function generateUniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'activity';
        $slug = $base;
        $counter = 1;

        while (Activity::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base . '-' . (++$counter);
        }

        return $slug;
    }
}

