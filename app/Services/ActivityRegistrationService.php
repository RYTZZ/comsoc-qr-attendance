<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityRegistration;
use App\Models\ActivitySession;
use App\Models\ActivitySessionRegistration;
use App\Models\ActivityTeam;
use App\Models\ActivityTeamMember;
use App\Models\User;
use App\Support\RowLock;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ActivityRegistrationService
{
    public function __construct(
        private DynamicFormService $formService
    ) {}

    /**
     * Validate student eligibility against activity registration rules & profile attributes.
     */
    public function validateEligibility(Activity $activity, User $user, bool $isWalkIn = false): void
    {
        if (!$user->is_active) {
            throw ValidationException::withMessages(['eligibility' => 'Your account is not active.']);
        }

        if (!$isWalkIn && !$activity->isRegistrationWindowOpen()) {
            throw ValidationException::withMessages([
                'registration' => 'Registration for this activity is currently closed or outside the registration period.',
            ]);
        }

        // Check Program eligibility from authenticated User/Student profile
        $eligiblePrograms = array_filter($activity->eligible_programs ?? []);
        if (!empty($eligiblePrograms)) {
            $userProgram = $user->getProfileProgram();
            if (!$userProgram || !in_array($userProgram, $eligiblePrograms, true)) {
                throw ValidationException::withMessages([
                    'program' => 'Your program (' . ($userProgram ?: 'Unspecified') . ') is not eligible for this activity.',
                ]);
            }
        }

        // Check Year Level eligibility from authenticated User/Student profile
        $eligibleYears = array_filter($activity->eligible_year_levels ?? []);
        if (!empty($eligibleYears)) {
            $userYear = $user->getProfileYearLevel();
            if (!$userYear || !in_array($userYear, $eligibleYears, true)) {
                throw ValidationException::withMessages([
                    'year_level' => 'Your year level (' . ($userYear ?: 'Unspecified') . ') is not eligible for this activity.',
                ]);
            }
        }
    }

    /**
     * Validate selected sessions and check for schedule conflicts.
     *
     * @param Collection<int, ActivitySession> $selectedSessions
     * @return array{warnings: array<int, string>}
     */
    public function validateSessionSelection(Activity $activity, User $user, Collection $selectedSessions, bool $isIndependent = false): array
    {
        $warnings = [];
        $hasSessionsConfigured = $activity->sessions()->exists();

        if ($hasSessionsConfigured && $activity->require_session_selection && $selectedSessions->isEmpty()) {
            throw ValidationException::withMessages([
                'session_ids' => 'Please select at least one session to register.',
            ]);
        }

        if (!$activity->allow_multiple_sessions && $selectedSessions->count() > 1) {
            throw ValidationException::withMessages([
                'session_ids' => 'Only one session may be selected for this activity.',
            ]);
        }

        if ($activity->max_sessions_per_student !== null && $selectedSessions->count() > $activity->max_sessions_per_student) {
            throw ValidationException::withMessages([
                'session_ids' => "You may select at most {$activity->max_sessions_per_student} session(s) for this activity.",
            ]);
        }

        foreach ($selectedSessions as $session) {
            if ($session->activity_id !== $activity->id) {
                throw ValidationException::withMessages([
                    'session_ids' => "Selected session '{$session->name}' does not belong to this activity.",
                ]);
            }

            if (!$session->isAvailableForRegistration()) {
                throw ValidationException::withMessages([
                    'session_ids' => "Registration for session '{$session->name}' is closed.",
                ]);
            }
        }

        // Schedule conflict validation among newly selected sessions AND already registered active sessions
        if ($activity->conflict_policy !== 'ignore' && $selectedSessions->isNotEmpty()) {
            $existingActiveSessions = ActivitySession::whereHas('sessionRegistrations', function ($q) use ($user, $activity) {
                $q->where('user_id', $user->id)
                    ->whereIn('status', ['pending', 'approved'])
                    ->where('activity_id', $activity->id);
            })->get();

            $allToCompare = $selectedSessions->values();

            // Compare pairs within selected sessions
            for ($i = 0; $i < $allToCompare->count(); $i++) {
                for ($j = $i + 1; $j < $allToCompare->count(); $j++) {
                    $s1 = $allToCompare[$i];
                    $s2 = $allToCompare[$j];
                    if ($s1->overlapsWith($s2)) {
                        $msg = "Schedule conflict detected between '{$s1->name}' and '{$s2->name}'.";
                        if ($activity->conflict_policy === 'block') {
                            throw ValidationException::withMessages(['session_ids' => $msg]);
                        }
                        $warnings[] = $msg;
                    }
                }
            }

            // Compare selected against existing active sessions (for independent session adds)
            foreach ($selectedSessions as $selected) {
                foreach ($existingActiveSessions as $existing) {
                    if ($selected->id !== $existing->id && $selected->overlapsWith($existing)) {
                        $msg = "Schedule conflict detected between '{$selected->name}' and your already registered session '{$existing->name}'.";
                        if ($activity->conflict_policy === 'block') {
                            throw ValidationException::withMessages(['session_ids' => $msg]);
                        }
                        $warnings[] = $msg;
                    }
                }
            }
        }

        return ['warnings' => array_values(array_unique($warnings))];
    }

    /**
     * Submit an individual or team registration for a parent activity and/or selected sessions.
     */
    public function registerStudent(
        Activity $activity,
        User $user,
        array $sessionIds = [],
        array $parentAnswers = [],
        array $parentFiles = [],
        array $sessionAnswersMap = [],
        array $sessionFilesMap = [],
        ?ActivityTeam $team = null,
        bool $isIndependentSessionMode = false
    ): array {
        $this->validateEligibility($activity, $user);

        if ($isIndependentSessionMode && !$activity->allow_independent_session_registration && $activity->require_parent_registration) {
            throw ValidationException::withMessages([
                'registration' => 'Independent session registration is not enabled for this activity.',
            ]);
        }

        if ($team !== null) {
            if (!in_array($activity->registration_mode, ['team', 'both'], true)) {
                throw ValidationException::withMessages(['team' => 'Team registration is not enabled for this activity.']);
            }
            $this->validateTeamReadyForSubmission($activity, $team, $user);
        } elseif ($activity->registration_mode === 'team') {
            throw ValidationException::withMessages(['team' => 'This activity requires team registration.']);
        }

        $selectedSessions = !empty($sessionIds)
            ? ActivitySession::where('activity_id', $activity->id)->whereIn('id', $sessionIds)->get()
            : collect();

        // Auto-select single session if activity has exactly 1 session and none explicitly passed
        if ($selectedSessions->isEmpty() && !$activity->is_multi_session && $activity->sessions()->count() === 1) {
            $selectedSessions = $activity->sessions()->get();
        }

        $conflictCheck = $this->validateSessionSelection($activity, $user, $selectedSessions, $isIndependentSessionMode);

        // Validate dynamic form answers BEFORE entering DB lock
        $parentSchema = $activity->getEffectiveFormSnapshot();
        $validatedParentAnswers = [];
        if ($parentSchema && (!$isIndependentSessionMode || $activity->require_parent_registration)) {
            $validatedParentAnswers = $this->formService->validateSubmissionAgainstSchema(
                $parentSchema,
                $parentAnswers,
                $parentFiles,
                'answers'
            );
        }

        $validatedSessionAnswers = [];
        foreach ($selectedSessions as $session) {
            $sessionSchema = $session->getEffectiveFormSnapshot();
            if ($sessionSchema) {
                $validatedSessionAnswers[$session->id] = $this->formService->validateSubmissionAgainstSchema(
                    $sessionSchema,
                    $sessionAnswersMap[$session->id] ?? [],
                    $sessionFilesMap[$session->id] ?? [],
                    "session_answers.{$session->id}"
                );
            }
        }

        return DB::transaction(function () use (
            $activity,
            $user,
            $selectedSessions,
            $parentSchema,
            $validatedParentAnswers,
            $parentFiles,
            $validatedSessionAnswers,
            $sessionFilesMap,
            $team,
            $isIndependentSessionMode,
            $conflictCheck
        ) {
            /** @var Activity $lockedActivity */
            $lockedActivity = RowLock::acquire(Activity::class, $activity->id);
            if (!$lockedActivity || !$lockedActivity->isRegistrationWindowOpen()) {
                throw ValidationException::withMessages(['registration' => 'Registration is closed.']);
            }

            // Determine target users to register (1 user for individual, or all accepted team members for team registration)
            $participants = collect([$user]);
            if ($team !== null) {
                $team->load('acceptedMembers.user');
                $participants = $team->acceptedMembers->map(fn ($m) => $m->user)->filter()->values();
                if (!$participants->contains('id', $user->id)) {
                    $participants->prepend($user);
                }
            }

            $createdRegistrations = [];

            foreach ($participants as $participant) {
                $existingParentReg = ActivityRegistration::where('activity_id', $lockedActivity->id)
                    ->where('user_id', $participant->id)
                    ->lockForUpdate()
                    ->first();

                if ($existingParentReg && !in_array($existingParentReg->status, ['cancelled', 'withdrawn', 'rejected'], true)) {
                    if (!$isIndependentSessionMode) {
                        throw ValidationException::withMessages([
                            'registration' => $participant->id === $user->id
                                ? 'You are already registered for this activity.'
                                : "{$participant->name} is already registered for this activity.",
                        ]);
                    }
                    $parentReg = $existingParentReg;
                } else {
                    // Check overall parent capacity
                    $parentStatus = $lockedActivity->requires_approval ? 'pending' : 'approved';
                    $waitlistPos = null;

                    if ($lockedActivity->isFull()) {
                        if ($lockedActivity->waitlist_enabled) {
                            $parentStatus = 'waitlisted';
                            $waitlistPos = $lockedActivity->registrations()->where('status', 'waitlisted')->count() + 1;
                        } else {
                            throw ValidationException::withMessages([
                                'registration' => 'This activity has reached its overall capacity limit.',
                            ]);
                        }
                    }

                    $regType = $team ? 'team' : ($isIndependentSessionMode ? 'independent_session' : 'individual');

                    if ($existingParentReg) {
                        $existingParentReg->update([
                            'activity_team_id' => $team?->id,
                            'registration_type' => $regType,
                            'registration_form_id' => $lockedActivity->registration_form_id,
                            'form_version' => $lockedActivity->form_version ?? $lockedActivity->registrationForm?->version,
                            'form_snapshot' => $parentSchema,
                            'status' => $parentStatus,
                            'completion_status' => 'registered',
                            'waitlist_position' => $waitlistPos,
                            'cancelled_at' => null,
                            'withdrawn_at' => null,
                            'rejection_reason' => null,
                        ]);
                        $parentReg = $existingParentReg;
                    } else {
                        $parentReg = ActivityRegistration::create([
                            'activity_id' => $lockedActivity->id,
                            'user_id' => $participant->id,
                            'activity_team_id' => $team?->id,
                            'registration_type' => $regType,
                            'registration_form_id' => $lockedActivity->registration_form_id,
                            'form_version' => $lockedActivity->form_version ?? $lockedActivity->registrationForm?->version,
                            'form_snapshot' => $parentSchema,
                            'status' => $parentStatus,
                            'completion_status' => 'registered',
                            'waitlist_position' => $waitlistPos,
                        ]);
                    }
                }

                if ($parentSchema) {
                    $this->formService->storeValidatedAnswers(
                        $parentReg,
                        null,
                        $parentSchema,
                        $validatedParentAnswers,
                        $parentFiles
                    );
                }

                // Register for each selected session with independent row lock and capacity enforcement
                foreach ($selectedSessions as $session) {
                    /** @var ActivitySession $lockedSession */
                    $lockedSession = RowLock::acquire(ActivitySession::class, $session->id);

                    $existingSessionReg = ActivitySessionRegistration::where('activity_session_id', $lockedSession->id)
                        ->where('user_id', $participant->id)
                        ->lockForUpdate()
                        ->first();

                    if ($existingSessionReg && !in_array($existingSessionReg->status, ['cancelled', 'withdrawn', 'rejected'], true)) {
                        throw ValidationException::withMessages([
                            'session_ids' => "Already registered for session '{$lockedSession->name}'.",
                        ]);
                    }

                    $needsApproval = $lockedActivity->require_separate_session_approval
                        || $lockedSession->requires_approval
                        || $lockedActivity->requires_approval;

                    $sessionStatus = $needsApproval ? 'pending' : 'approved';
                    $sessionWaitlistPos = null;

                    if ($lockedSession->isFull()) {
                        if ($lockedSession->waitlist_enabled || $lockedActivity->waitlist_enabled) {
                            $sessionStatus = 'waitlisted';
                            $sessionWaitlistPos = $lockedSession->sessionRegistrations()->where('status', 'waitlisted')->count() + 1;
                        } else {
                            throw ValidationException::withMessages([
                                'session_ids' => "Session '{$lockedSession->name}' has reached its maximum capacity of {$lockedSession->capacity} slots.",
                            ]);
                        }
                    }

                    $sessionSchema = $lockedSession->getEffectiveFormSnapshot();

                    if ($existingSessionReg) {
                        $existingSessionReg->update([
                            'activity_registration_id' => $parentReg->id,
                            'activity_team_id' => $team?->id,
                            'registration_form_id' => $lockedSession->registration_form_id,
                            'form_version' => $lockedSession->form_version ?? $lockedSession->registrationForm?->version,
                            'form_snapshot' => $sessionSchema,
                            'status' => $sessionStatus,
                            'completion_status' => 'registered',
                            'waitlist_position' => $sessionWaitlistPos,
                            'rejection_reason' => null,
                        ]);
                        $sessionReg = $existingSessionReg;
                    } else {
                        $sessionReg = ActivitySessionRegistration::create([
                            'activity_id' => $lockedActivity->id,
                            'activity_session_id' => $lockedSession->id,
                            'activity_registration_id' => $parentReg->id,
                            'user_id' => $participant->id,
                            'activity_team_id' => $team?->id,
                            'registration_form_id' => $lockedSession->registration_form_id,
                            'form_version' => $lockedSession->form_version ?? $lockedSession->registrationForm?->version,
                            'form_snapshot' => $sessionSchema,
                            'status' => $sessionStatus,
                            'attendance_status' => 'not_recorded',
                            'completion_status' => 'registered',
                            'waitlist_position' => $sessionWaitlistPos,
                        ]);
                    }

                    if ($sessionSchema && isset($validatedSessionAnswers[$lockedSession->id])) {
                        $this->formService->storeValidatedAnswers(
                            $parentReg,
                            $sessionReg,
                            $sessionSchema,
                            $validatedSessionAnswers[$lockedSession->id],
                            $sessionFilesMap[$lockedSession->id] ?? []
                        );
                    }
                }

                $createdRegistrations[] = $parentReg->fresh(['sessionRegistrations.session', 'answers']);

                NotificationService::send(
                    $participant,
                    'activity_registration',
                    "Registration Recorded: {$lockedActivity->title}",
                    "Your registration for {$lockedActivity->title} ({$parentReg->ticket_code}) has been submitted with status: " . strtoupper($parentReg->status) . ".",
                    route('activities.my-registrations')
                );
            }

            if ($team !== null) {
                $team->update([
                    'status' => $lockedActivity->requires_approval ? 'submitted' : 'approved',
                ]);
            }

            AuditLogger::log('activity.registration_submitted', $createdRegistrations[0], [], [
                'activity_id' => $lockedActivity->id,
                'user_id' => $user->id,
                'team_id' => $team?->id,
                'sessions_selected' => $selectedSessions->pluck('name')->all(),
                'status' => $createdRegistrations[0]->status,
            ]);

            return [
                'registration' => $createdRegistrations[0],
                'all_registrations' => $createdRegistrations,
                'warnings' => $conflictCheck['warnings'],
            ];
        });
    }

    /**
     * Create a team and invite existing COMSOC Hub members.
     */
    public function createTeam(
        Activity $activity,
        User $captain,
        array $data,
        array $invitedUserIds = []
    ): ActivityTeam {
        $this->validateEligibility($activity, $captain);

        if (!in_array($activity->registration_mode, ['team', 'both'], true)) {
            throw ValidationException::withMessages(['team' => 'Team registration is not enabled for this activity.']);
        }

        // Prevent duplicate team membership for captain
        $existingMembership = ActivityTeamMember::where('activity_id', $activity->id)
            ->where('user_id', $captain->id)
            ->whereIn('invitation_status', ['pending', 'accepted'])
            ->whereHas('team', fn ($q) => $q->whereNotIn('status', ['cancelled', 'rejected']))
            ->exists();

        if ($existingMembership) {
            throw ValidationException::withMessages([
                'team' => 'You are already part of an active team or have a pending team invitation for this activity.',
            ]);
        }

        // Check duplicate team name
        if (ActivityTeam::where('activity_id', $activity->id)->where('name', trim($data['name']))->exists()) {
            throw ValidationException::withMessages([
                'name' => 'A team with this name already exists for this activity.',
            ]);
        }

        $invitedUserIds = array_values(array_unique(array_filter($invitedUserIds, fn ($id) => $id && $id !== $captain->id)));

        if ((count($invitedUserIds) + 1) > $activity->max_team_size) {
            throw ValidationException::withMessages([
                'members' => "Team size cannot exceed the maximum of {$activity->max_team_size} members (including captain).",
            ]);
        }

        return DB::transaction(function () use ($activity, $captain, $data, $invitedUserIds) {
            $team = ActivityTeam::create([
                'activity_id' => $activity->id,
                'captain_user_id' => $captain->id,
                'name' => trim($data['name']),
                'project_title' => $data['project_title'] ?? null,
                'project_description' => $data['project_description'] ?? null,
                'track' => $data['track'] ?? null,
                'status' => 'forming',
            ]);

            // Add captain as accepted member
            ActivityTeamMember::create([
                'activity_team_id' => $team->id,
                'activity_id' => $activity->id,
                'user_id' => $captain->id,
                'role' => 'captain',
                'invitation_status' => 'accepted',
                'invited_by' => $captain->id,
                'responded_at' => now(),
            ]);

            foreach ($invitedUserIds as $memberUserId) {
                $invitee = User::findOrFail($memberUserId);
                $this->inviteMemberToTeam($team, $captain, $invitee);
            }

            AuditLogger::log('activity_team.created', $team, [], [
                'activity_id' => $activity->id,
                'team_name' => $team->name,
                'captain_id' => $captain->id,
                'invited_count' => count($invitedUserIds),
            ]);

            return $team->load('members.user');
        });
    }

    public function inviteMemberToTeam(ActivityTeam $team, User $captain, User $invitee): ActivityTeamMember
    {
        $activity = $team->activity;

        if ($team->captain_user_id !== $captain->id) {
            throw ValidationException::withMessages(['team' => 'Only the team captain can invite members.']);
        }

        $this->validateEligibility($activity, $invitee);

        $activeMemberCount = $team->members()->whereIn('invitation_status', ['pending', 'accepted'])->count();
        if ($activeMemberCount >= $activity->max_team_size) {
            throw ValidationException::withMessages([
                'members' => "Team has reached the maximum size of {$activity->max_team_size} members.",
            ]);
        }

        $alreadyInAnotherTeam = ActivityTeamMember::where('activity_id', $activity->id)
            ->where('user_id', $invitee->id)
            ->where('invitation_status', 'accepted')
            ->whereHas('team', fn ($q) => $q->whereNotIn('status', ['cancelled', 'rejected']))
            ->exists();

        if ($alreadyInAnotherTeam) {
            throw ValidationException::withMessages([
                'members' => "{$invitee->name} is already an accepted member of a team for this activity.",
            ]);
        }

        $alreadyRegisteredIndividually = ActivityRegistration::where('activity_id', $activity->id)
            ->where('user_id', $invitee->id)
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($alreadyRegisteredIndividually) {
            throw ValidationException::withMessages([
                'members' => "{$invitee->name} is already registered for this activity.",
            ]);
        }

        $memberRecord = ActivityTeamMember::updateOrCreate(
            [
                'activity_team_id' => $team->id,
                'user_id' => $invitee->id,
            ],
            [
                'activity_id' => $activity->id,
                'role' => 'member',
                'invitation_status' => 'pending',
                'invited_by' => $captain->id,
                'responded_at' => null,
            ]
        );

        NotificationService::send(
            $invitee,
            'team_invitation',
            "Team Invitation: {$team->name}",
            "{$captain->name} invited you to join team '{$team->name}' for {$activity->title}.",
            route('activities.show', $activity)
        );

        AuditLogger::log('activity_team.member_invited', $team, [], [
            'invited_user_id' => $invitee->id,
            'invited_by' => $captain->id,
        ]);

        return $memberRecord;
    }

    public function respondToTeamInvitation(ActivityTeamMember $membership, User $user, string $decision): ActivityTeamMember
    {
        if ($membership->user_id !== $user->id) {
            abort(403, 'Unauthorized team invitation response.');
        }

        if ($membership->invitation_status !== 'pending') {
            throw ValidationException::withMessages(['invitation' => 'This invitation has already been processed.']);
        }

        if (!in_array($decision, ['accepted', 'rejected'], true)) {
            throw ValidationException::withMessages(['decision' => 'Invalid invitation decision.']);
        }

        return DB::transaction(function () use ($membership, $user, $decision) {
            if ($decision === 'accepted') {
                $alreadyAcceptedElsewhere = ActivityTeamMember::where('activity_id', $membership->activity_id)
                    ->where('user_id', $user->id)
                    ->where('id', '!=', $membership->id)
                    ->where('invitation_status', 'accepted')
                    ->whereHas('team', fn ($q) => $q->whereNotIn('status', ['cancelled', 'rejected']))
                    ->exists();

                if ($alreadyAcceptedElsewhere) {
                    throw ValidationException::withMessages([
                        'invitation' => 'You are already a member of another team for this activity.',
                    ]);
                }
            }

            $membership->update([
                'invitation_status' => $decision,
                'responded_at' => now(),
            ]);

            AuditLogger::log("activity_team.invitation_{$decision}", $membership->team, [], [
                'member_user_id' => $user->id,
                'decision' => $decision,
            ]);

            return $membership->fresh();
        });
    }

    public function validateTeamReadyForSubmission(Activity $activity, ActivityTeam $team, User $actor): void
    {
        if ($team->captain_user_id !== $actor->id) {
            throw ValidationException::withMessages(['team' => 'Only the team captain can submit the team registration.']);
        }

        if (in_array($team->status, ['submitted', 'approved'], true)) {
            throw ValidationException::withMessages(['team' => 'This team has already submitted its registration.']);
        }

        $acceptedCount = $team->acceptedMembers()->count();
        $pendingCount = $team->pendingMembers()->count();

        if ($activity->require_all_team_invites_accepted && $pendingCount > 0) {
            throw ValidationException::withMessages([
                'team' => 'All invited team members must accept their invitations before the team registration can be submitted.',
            ]);
        }

        if ($acceptedCount < $activity->min_team_size) {
            throw ValidationException::withMessages([
                'team' => "Team must have at least {$activity->min_team_size} accepted member(s). Current accepted: {$acceptedCount}.",
            ]);
        }

        if ($acceptedCount > $activity->max_team_size) {
            throw ValidationException::withMessages([
                'team' => "Team cannot exceed {$activity->max_team_size} accepted members.",
            ]);
        }
    }

    /**
     * Approve a parent registration (does NOT automatically approve sessions unless configured or requested).
     */
    public function approveParentRegistration(ActivityRegistration $registration, User $reviewer, bool $cascadeSessions = false): ActivityRegistration
    {
        return DB::transaction(function () use ($registration, $reviewer, $cascadeSessions) {
            $oldStatus = $registration->status;
            $activity = $registration->activity;

            $registration->update([
                'status' => 'approved',
                'waitlist_position' => null,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'rejection_reason' => null,
            ]);

            if ($cascadeSessions || !$activity->require_separate_session_approval) {
                foreach ($registration->sessionRegistrations()->where('status', 'pending')->get() as $sessionReg) {
                    if (!$sessionReg->session->requires_approval || $cascadeSessions) {
                        $this->approveSessionRegistration($sessionReg, $reviewer);
                    }
                }
            }

            NotificationService::send(
                $registration->user,
                'activity_registration_approved',
                "Registration Approved: {$activity->title}",
                "Your registration for {$activity->title} has been approved.",
                route('activities.my-registrations')
            );

            AuditLogger::log('activity_registration.approved', $registration, ['status' => $oldStatus], [
                'status' => 'approved',
                'reviewed_by' => $reviewer->id,
            ]);

            return $registration->fresh('sessionRegistrations');
        });
    }

    public function rejectParentRegistration(ActivityRegistration $registration, User $reviewer, string $reason): ActivityRegistration
    {
        return DB::transaction(function () use ($registration, $reviewer, $reason) {
            $oldStatus = $registration->status;

            $registration->update([
                'status' => 'rejected',
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
            ]);

            $registration->sessionRegistrations()->whereIn('status', ['pending', 'waitlisted'])->update([
                'status' => 'rejected',
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
            ]);

            NotificationService::send(
                $registration->user,
                'activity_registration_rejected',
                "Registration Update: {$registration->activity->title}",
                "Your registration for {$registration->activity->title} was not approved. Reason: {$reason}",
                route('activities.my-registrations')
            );

            AuditLogger::log('activity_registration.rejected', $registration, ['status' => $oldStatus], [
                'status' => 'rejected',
                'reason' => $reason,
                'reviewed_by' => $reviewer->id,
            ]);

            return $registration->fresh('sessionRegistrations');
        });
    }

    public function approveSessionRegistration(ActivitySessionRegistration $sessionReg, User $reviewer): ActivitySessionRegistration
    {
        return DB::transaction(function () use ($sessionReg, $reviewer) {
            $lockedSession = RowLock::acquire(ActivitySession::class, $sessionReg->activity_session_id);

            if ($sessionReg->status !== 'approved' && $lockedSession->isFull() && $sessionReg->status !== 'pending') {
                throw ValidationException::withMessages([
                    'capacity' => "Session '{$lockedSession->name}' is currently at full capacity.",
                ]);
            }

            $oldStatus = $sessionReg->status;
            $sessionReg->update([
                'status' => 'approved',
                'waitlist_position' => null,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'rejection_reason' => null,
            ]);

            AuditLogger::log('activity_session_registration.approved', $sessionReg, ['status' => $oldStatus], [
                'status' => 'approved',
                'session_id' => $lockedSession->id,
                'reviewed_by' => $reviewer->id,
            ]);

            return $sessionReg->fresh();
        });
    }

    public function rejectSessionRegistration(ActivitySessionRegistration $sessionReg, User $reviewer, string $reason): ActivitySessionRegistration
    {
        return DB::transaction(function () use ($sessionReg, $reviewer, $reason) {
            $oldStatus = $sessionReg->status;
            $sessionReg->update([
                'status' => 'rejected',
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
            ]);

            $this->promoteNextWaitlistedForSession($sessionReg->session, $reviewer);

            AuditLogger::log('activity_session_registration.rejected', $sessionReg, ['status' => $oldStatus], [
                'status' => 'rejected',
                'reason' => $reason,
                'reviewed_by' => $reviewer->id,
            ]);

            return $sessionReg->fresh();
        });
    }

    public function cancelRegistrationByStudent(ActivityRegistration $registration, User $student): ActivityRegistration
    {
        if ($registration->user_id !== $student->id) {
            abort(403, 'Unauthorized.');
        }

        if (!$registration->activity->allow_cancellation) {
            throw ValidationException::withMessages([
                'cancellation' => 'Cancellation is not permitted for this activity.',
            ]);
        }

        return DB::transaction(function () use ($registration, $student) {
            $oldStatus = $registration->status;
            $registration->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            foreach ($registration->sessionRegistrations as $sessionReg) {
                $sessionReg->update(['status' => 'cancelled']);
                $this->promoteNextWaitlistedForSession($sessionReg->session, $student);
            }

            AuditLogger::log('activity_registration.cancelled', $registration, ['status' => $oldStatus], [
                'status' => 'cancelled',
            ]);

            return $registration->fresh('sessionRegistrations');
        });
    }

    public function promoteNextWaitlistedForSession(ActivitySession $session, ?User $actor = null): ?ActivitySessionRegistration
    {
        if ($session->isFull()) {
            return null;
        }

        $next = $session->sessionRegistrations()
            ->where('status', 'waitlisted')
            ->orderBy('waitlist_position')
            ->orderBy('created_at')
            ->first();

        if (!$next) {
            return null;
        }

        $newStatus = ($session->requires_approval || $session->activity->require_separate_session_approval)
            ? 'pending'
            : 'approved';

        $next->update([
            'status' => $newStatus,
            'waitlist_position' => null,
        ]);

        if ($next->parentRegistration && $next->parentRegistration->status === 'waitlisted') {
            $next->parentRegistration->update([
                'status' => $session->activity->requires_approval ? 'pending' : 'approved',
                'waitlist_position' => null,
            ]);
        }

        AuditLogger::log('activity_session_registration.waitlist_promoted', $next, ['status' => 'waitlisted'], [
            'status' => $newStatus,
        ]);

        return $next;
    }

    public function switchSession(ActivitySessionRegistration $sessionReg, ActivitySession $targetSession, User $student): ActivitySessionRegistration
    {
        if ($sessionReg->user_id !== $student->id) {
            abort(403, 'Unauthorized session registration.');
        }

        $activity = $sessionReg->activity;
        if (!$activity || !$activity->allow_session_change) {
            throw ValidationException::withMessages([
                'session' => 'Session switching is disabled for this activity.',
            ]);
        }

        if ($targetSession->activity_id !== $activity->id || $targetSession->id === $sessionReg->activity_session_id) {
            throw ValidationException::withMessages([
                'session' => 'Invalid target session selected.',
            ]);
        }

        if (!$targetSession->isAvailableForRegistration()) {
            throw ValidationException::withMessages([
                'session' => "Session '{$targetSession->name}' is closed for registration.",
            ]);
        }

        if (!in_array($sessionReg->attendance_status, ['not_recorded', null], true) || $sessionReg->completion_status === 'completed') {
            throw ValidationException::withMessages([
                'session' => 'Cannot switch a session that has already been attended or completed.',
            ]);
        }

        return DB::transaction(function () use ($sessionReg, $targetSession, $activity, $student) {
            /** @var ActivitySession $lockedTarget */
            $lockedTarget = RowLock::acquire(ActivitySession::class, $targetSession->id);
            $oldSession = $sessionReg->session;

            $alreadyInTarget = ActivitySessionRegistration::where('activity_session_id', $lockedTarget->id)
                ->where('user_id', $student->id)
                ->whereNotIn('status', ['cancelled', 'withdrawn', 'rejected'])
                ->exists();

            if ($alreadyInTarget) {
                throw ValidationException::withMessages([
                    'session' => "You are already enrolled in session '{$lockedTarget->name}'.",
                ]);
            }

            if ($activity->conflict_policy === 'block') {
                $otherActiveSessions = ActivitySessionRegistration::with('session')
                    ->where('activity_id', $activity->id)
                    ->where('user_id', $student->id)
                    ->where('id', '!=', $sessionReg->id)
                    ->whereIn('status', ['pending', 'approved'])
                    ->get()
                    ->pluck('session')
                    ->filter();

                foreach ($otherActiveSessions as $otherSess) {
                    if ($lockedTarget->overlapsWith($otherSess)) {
                        throw ValidationException::withMessages([
                            'session' => "Schedule conflict: '{$lockedTarget->name}' overlaps with '{$otherSess->name}'.",
                        ]);
                    }
                }
            }

            $needsApproval = $activity->require_separate_session_approval
                || $lockedTarget->requires_approval
                || $activity->requires_approval;

            $newStatus = $needsApproval ? 'pending' : 'approved';
            $waitlistPos = null;

            if ($lockedTarget->isFull()) {
                if ($lockedTarget->waitlist_enabled || $activity->waitlist_enabled) {
                    $newStatus = 'waitlisted';
                    $waitlistPos = $lockedTarget->sessionRegistrations()->where('status', 'waitlisted')->count() + 1;
                } else {
                    throw ValidationException::withMessages([
                        'session' => "Target session '{$lockedTarget->name}' is full.",
                    ]);
                }
            }

            $oldSessionId = $sessionReg->activity_session_id;
            $sessionReg->update([
                'activity_session_id' => $lockedTarget->id,
                'status' => $newStatus,
                'waitlist_position' => $waitlistPos,
            ]);

            if ($oldSession) {
                $this->promoteNextWaitlistedForSession($oldSession, $student);
            }

            AuditLogger::log('activity_session_registration.switched', $sessionReg, [
                'activity_session_id' => $oldSessionId,
            ], [
                'activity_session_id' => $lockedTarget->id,
                'status' => $newStatus,
            ]);

            return $sessionReg->fresh('session');
        });
    }
}

