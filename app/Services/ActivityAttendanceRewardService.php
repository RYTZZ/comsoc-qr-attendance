<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityAttendance;
use App\Models\ActivityRegistration;
use App\Models\ActivityReward;
use App\Models\ActivitySession;
use App\Models\ActivitySessionRegistration;
use App\Models\MemberSeasonStat;
use App\Models\PointTransaction;
use App\Models\Season;
use App\Models\User;
use App\Support\RowLock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ActivityAttendanceRewardService
{
    public function __construct(
        private DynamicQrService $dynamicQrService,
        private ExpLedgerService $expLedgerService
    ) {}

    /**
     * Process an organizer QR / manual token scan for a specific ActivitySession.
     */
    public function processSessionQrScan(
        Activity $activity,
        ActivitySession $session,
        string $qrTokenPayload,
        User $organizer,
        string $mode = 'auto', // auto, in, out
        bool $allowWalkInOverride = false
    ): array {
        if (!$activity->isUserAuthorizedOrganizer($organizer)) {
            return [
                'success' => false,
                'code' => 'unauthorized_organizer',
                'message' => 'You are not an authorized organizer for this activity.',
            ];
        }

        if ($session->activity_id !== $activity->id) {
            return [
                'success' => false,
                'code' => 'session_mismatch',
                'message' => 'Selected session does not belong to this activity.',
            ];
        }

        if (!$session->attendance_enabled || !$session->qr_attendance_enabled) {
            return [
                'success' => false,
                'code' => 'attendance_disabled',
                'message' => 'QR attendance is disabled for this session.',
            ];
        }

        $tokenValidation = $this->dynamicQrService->validateAndConsumeToken($qrTokenPayload, $organizer);
        if (!$tokenValidation['valid']) {
            return [
                'success' => false,
                'code' => $tokenValidation['code'],
                'message' => $tokenValidation['message'],
            ];
        }

        /** @var User $participant */
        $participant = $tokenValidation['user'];

        return $this->recordAttendanceForUser(
            activity: $activity,
            session: $session,
            participant: $participant,
            organizer: $organizer,
            mode: $mode,
            qrTokenHash: $tokenValidation['token_record']->token_hash ?? null,
            allowWalkInOverride: $allowWalkInOverride
        );
    }

    /**
     * Core session check-in / check-out recorder and completion/reward evaluator.
     */
    public function recordAttendanceForUser(
        Activity $activity,
        ActivitySession $session,
        User $participant,
        User $organizer,
        string $mode = 'auto',
        ?string $qrTokenHash = null,
        bool $allowWalkInOverride = false,
        ?int $overrideDurationMinutes = null
    ): array {
        if (!$activity->isUserAuthorizedOrganizer($organizer)) {
            return [
                'success' => false,
                'code' => 'unauthorized_organizer',
                'message' => 'You are not an authorized organizer for this activity.',
            ];
        }

        return DB::transaction(function () use (
            $activity,
            $session,
            $participant,
            $organizer,
            $mode,
            $qrTokenHash,
            $allowWalkInOverride,
            $overrideDurationMinutes
        ) {
            /** @var ActivitySession $lockedSession */
            $lockedSession = RowLock::acquire(ActivitySession::class, $session->id);

            $sessionReg = ActivitySessionRegistration::where('activity_session_id', $lockedSession->id)
                ->where('user_id', $participant->id)
                ->lockForUpdate()
                ->first();

            $isWalkIn = false;

            if (!$sessionReg || $sessionReg->status !== 'approved') {
                $walkInAllowed = $allowWalkInOverride || $lockedSession->allow_walk_in || $activity->allow_walk_in;

                if (!$walkInAllowed) {
                    $reason = !$sessionReg
                        ? "{$participant->name} is not registered for session '{$lockedSession->name}'."
                        : "Registration status for {$participant->name} in '{$lockedSession->name}' is '{$sessionReg->status}' (requires approved registration).";

                    return [
                        'success' => false,
                        'code' => 'not_eligible_or_registered',
                        'message' => $reason,
                    ];
                }

                // Authorized Walk-In Registration
                $isWalkIn = true;
                $parentReg = ActivityRegistration::firstOrCreate(
                    ['activity_id' => $activity->id, 'user_id' => $participant->id],
                    [
                        'registration_type' => 'walk_in',
                        'status' => 'approved',
                        'completion_status' => 'attended',
                        'reviewed_by' => $organizer->id,
                        'reviewed_at' => now(),
                    ]
                );

                if (!$sessionReg) {
                    if ($lockedSession->isFull()) {
                        return [
                            'success' => false,
                            'code' => 'session_full',
                            'message' => "Cannot walk-in: Session '{$lockedSession->name}' is at full capacity.",
                        ];
                    }

                    $sessionReg = ActivitySessionRegistration::create([
                        'activity_id' => $activity->id,
                        'activity_session_id' => $lockedSession->id,
                        'activity_registration_id' => $parentReg->id,
                        'user_id' => $participant->id,
                        'status' => 'approved',
                        'attendance_status' => 'not_recorded',
                        'completion_status' => 'registered',
                        'reviewed_by' => $organizer->id,
                        'reviewed_at' => now(),
                    ]);
                } else {
                    $sessionReg->update([
                        'status' => 'approved',
                        'reviewed_by' => $organizer->id,
                        'reviewed_at' => now(),
                    ]);
                }
            }

            $attendance = ActivityAttendance::where('activity_session_id', $lockedSession->id)
                ->where('user_id', $participant->id)
                ->lockForUpdate()
                ->first();

            $now = now();
            $effectiveMode = $mode;
            if ($effectiveMode === 'auto') {
                $effectiveMode = ($attendance && $attendance->check_in_at) ? 'out' : 'in';
            }

            if ($effectiveMode === 'in') {
                if ($attendance && $attendance->check_in_at) {
                    return [
                        'success' => false,
                        'code' => 'duplicate_check_in',
                        'message' => "Duplicate Check-In: {$participant->name} already checked in to '{$lockedSession->name}' at " . $attendance->check_in_at->format('g:i A') . '.',
                    ];
                }

                $checkInStatus = 'present';
                if ($lockedSession->starts_at && $lockedSession->late_threshold_minutes !== null) {
                    $lateCutoff = $lockedSession->starts_at->copy()->addMinutes($lockedSession->late_threshold_minutes);
                    if ($now->gt($lateCutoff)) {
                        $checkInStatus = 'late';
                    }
                }

                $attendance = ActivityAttendance::updateOrCreate(
                    [
                        'activity_session_id' => $lockedSession->id,
                        'user_id' => $participant->id,
                    ],
                    [
                        'activity_id' => $activity->id,
                        'activity_session_registration_id' => $sessionReg->id,
                        'qr_token_hash' => $qrTokenHash ?? ('MANUAL_' . Str::random(16)),
                        'check_in_at' => $now,
                        'check_in_status' => $checkInStatus,
                        'checked_in_by' => $organizer->id,
                        'is_walk_in' => $isWalkIn,
                    ]
                );

                $sessionReg->update([
                    'attendance_status' => $checkInStatus === 'late' ? 'late' : 'checked_in',
                    'completion_status' => 'attended',
                ]);

                if ($sessionReg->parentRegistration && $sessionReg->parentRegistration->completion_status === 'registered') {
                    $sessionReg->parentRegistration->update(['completion_status' => 'attended']);
                }

                // If session does not require check-out, evaluate completion right away
                $evaluation = ['completed' => false, 'exp_awarded' => 0, 'points_awarded' => 0];
                if (!$lockedSession->require_check_out) {
                    $evaluation = $this->evaluateSessionCompletionAndRewards($sessionReg->fresh(), $organizer);
                }

                AuditLogger::log('activity_attendance.check_in', $attendance, [], [
                    'activity_id' => $activity->id,
                    'session_id' => $lockedSession->id,
                    'user_id' => $participant->id,
                    'status' => $checkInStatus,
                    'is_walk_in' => $isWalkIn,
                ]);

                return [
                    'success' => true,
                    'action' => 'check_in',
                    'status' => $checkInStatus,
                    'completed' => $evaluation['completed'],
                    'exp_awarded' => $evaluation['exp_awarded'],
                    'points_awarded' => $evaluation['points_awarded'],
                    'message' => "Checked in {$participant->name} for '{$lockedSession->name}' ({$checkInStatus}).",
                    'attendance' => $attendance,
                ];
            }

            // Check-Out Flow
            if (!$attendance || !$attendance->check_in_at) {
                return [
                    'success' => false,
                    'code' => 'missing_check_in',
                    'message' => "Missing Check-In: {$participant->name} must check in to '{$lockedSession->name}' before checking out.",
                ];
            }

            if ($attendance->check_out_at) {
                return [
                    'success' => false,
                    'code' => 'duplicate_check_out',
                    'message' => "Duplicate Check-Out: {$participant->name} already checked out from '{$lockedSession->name}' at " . $attendance->check_out_at->format('g:i A') . '.',
                ];
            }

            $durationMinutes = $overrideDurationMinutes !== null
                ? $overrideDurationMinutes
                : max(1, (int) $attendance->check_in_at->diffInMinutes($now));

            $attendance->update([
                'check_out_at' => $now,
                'duration_minutes' => $durationMinutes,
                'checked_out_by' => $organizer->id,
            ]);

            $sessionReg->update([
                'attendance_status' => 'checked_out',
            ]);

            $evaluation = $this->evaluateSessionCompletionAndRewards($sessionReg->fresh(), $organizer);

            AuditLogger::log('activity_attendance.check_out', $attendance, [], [
                'activity_id' => $activity->id,
                'session_id' => $lockedSession->id,
                'user_id' => $participant->id,
                'duration_minutes' => $durationMinutes,
                'completed' => $evaluation['completed'],
                'exp_awarded' => $evaluation['exp_awarded'],
                'points_awarded' => $evaluation['points_awarded'],
            ]);

            $rewardSuffix = ($evaluation['exp_awarded'] > 0 || $evaluation['points_awarded'] > 0)
                ? " Awarded +{$evaluation['exp_awarded']} EXP & +{$evaluation['points_awarded']} Points!"
                : '';

            return [
                'success' => true,
                'action' => 'check_out',
                'status' => $sessionReg->fresh()->completion_status,
                'completed' => $evaluation['completed'],
                'exp_awarded' => $evaluation['exp_awarded'],
                'points_awarded' => $evaluation['points_awarded'],
                'message' => "Checked out {$participant->name} from '{$lockedSession->name}'.{$rewardSuffix}",
                'attendance' => $attendance->fresh(),
            ];
        });
    }

    /**
     * Evaluate whether a participant satisfies all configured completion requirements for a session.
     * If verified completed, atomically issue session EXP and Points rewards (once only).
     */
    public function evaluateSessionCompletionAndRewards(ActivitySessionRegistration $sessionReg, ?User $actor = null): array
    {
        return DB::transaction(function () use ($sessionReg, $actor) {
            /** @var ActivitySessionRegistration $lockedReg */
            $lockedReg = RowLock::acquire(ActivitySessionRegistration::class, $sessionReg->id);
            $session = $lockedReg->session;
            $activity = $lockedReg->activity;
            $attendance = ActivityAttendance::where('activity_session_id', $session->id)
                ->where('user_id', $lockedReg->user_id)
                ->first();

            // 1. Check attendance requirement
            if ($session->attendance_enabled) {
                if (!$attendance || !$attendance->check_in_at) {
                    return ['completed' => false, 'reason' => 'no_check_in', 'exp_awarded' => 0, 'points_awarded' => 0];
                }

                if ($session->require_check_out && !$attendance->check_out_at) {
                    $lockedReg->update(['completion_status' => 'attended']);
                    return ['completed' => false, 'reason' => 'awaiting_check_out', 'exp_awarded' => 0, 'points_awarded' => 0];
                }

                // Minimum attendance duration check
                if ($session->min_attendance_minutes !== null && $session->min_attendance_minutes > 0) {
                    $actualMinutes = (int) ($attendance->duration_minutes ?? 0);
                    if ($actualMinutes < $session->min_attendance_minutes) {
                        $lockedReg->update(['completion_status' => 'incomplete']);
                        return ['completed' => false, 'reason' => 'insufficient_duration', 'exp_awarded' => 0, 'points_awarded' => 0];
                    }
                }

                // Minimum attendance percentage check
                if ($session->min_attendance_percentage !== null && $session->min_attendance_percentage > 0) {
                    $scheduledMinutes = $session->getScheduledDurationMinutes();
                    if ($scheduledMinutes && $scheduledMinutes > 0) {
                        $actualMinutes = (int) ($attendance->duration_minutes ?? 0);
                        $pct = ($actualMinutes / $scheduledMinutes) * 100;
                        if ($pct < $session->min_attendance_percentage) {
                            $lockedReg->update(['completion_status' => 'incomplete']);
                            return ['completed' => false, 'reason' => 'insufficient_percentage', 'exp_awarded' => 0, 'points_awarded' => 0];
                        }
                    }
                }
            }

            // 2. Check required activity output / submission
            if ($session->require_output_submission && !$lockedReg->output_submitted) {
                $lockedReg->update(['completion_status' => 'incomplete']);
                return ['completed' => false, 'reason' => 'awaiting_output_submission', 'exp_awarded' => 0, 'points_awarded' => 0];
            }

            // 3. Check organizer verification requirement
            if ($session->require_organizer_verification && !$lockedReg->organizer_verified) {
                $lockedReg->update(['completion_status' => 'attended']);
                return ['completed' => false, 'reason' => 'awaiting_organizer_verification', 'exp_awarded' => 0, 'points_awarded' => 0];
            }

            // All completion requirements met!
            $lockedReg->update([
                'completion_status' => 'completed',
            ]);

            $rewardResult = $this->issueSessionRewards($lockedReg, $actor);

            // Re-evaluate parent activity completion and optional parent rewards
            if ($lockedReg->parentRegistration) {
                $this->evaluateParentCompletionAndRewards($lockedReg->parentRegistration, $actor);
            }

            return [
                'completed' => true,
                'reason' => 'completed',
                'exp_awarded' => $rewardResult['exp_awarded'],
                'points_awarded' => $rewardResult['points_awarded'],
            ];
        });
    }

    /**
     * Organizer verification & manual completion status update for a session registration.
     */
    public function verifySessionParticipant(
        ActivitySessionRegistration $sessionReg,
        User $organizer,
        bool $verified = true,
        bool $outputSubmitted = false,
        ?string $outputNotes = null,
        ?string $explicitStatus = null
    ): array {
        if (!$sessionReg->activity->isUserAuthorizedOrganizer($organizer)) {
            abort(403, 'Unauthorized organizer.');
        }

        return DB::transaction(function () use ($sessionReg, $organizer, $verified, $outputSubmitted, $outputNotes, $explicitStatus) {
            $updates = [
                'organizer_verified' => $verified,
                'organizer_verified_by' => $verified ? $organizer->id : null,
                'organizer_verified_at' => $verified ? now() : null,
                'output_submitted' => $outputSubmitted,
                'output_notes' => $outputNotes ?? $sessionReg->output_notes,
            ];

            if ($explicitStatus && in_array($explicitStatus, ActivitySessionRegistration::COMPLETION_STATUSES, true)) {
                $updates['completion_status'] = $explicitStatus;
            }

            $sessionReg->update($updates);

            AuditLogger::log('activity_session.completion_verified', $sessionReg, [], [
                'organizer_verified' => $verified,
                'output_submitted' => $outputSubmitted,
                'explicit_status' => $explicitStatus,
                'verified_by' => $organizer->id,
            ]);

            if ($explicitStatus === 'completed' && !$sessionReg->session->attendance_enabled) {
                $rewardResult = $this->issueSessionRewards($sessionReg->fresh(), $organizer);
                if ($sessionReg->parentRegistration) {
                    $this->evaluateParentCompletionAndRewards($sessionReg->parentRegistration, $organizer);
                }
                return [
                    'completed' => true,
                    'exp_awarded' => $rewardResult['exp_awarded'],
                    'points_awarded' => $rewardResult['points_awarded'],
                ];
            }

            return $this->evaluateSessionCompletionAndRewards($sessionReg->fresh(), $organizer);
        });
    }

    /**
     * Issue EXP and Points for a verified completed session registration with strict idempotency.
     */
    public function issueSessionRewards(ActivitySessionRegistration $sessionReg, ?User $actor = null): array
    {
        return DB::transaction(function () use ($sessionReg, $actor) {
            /** @var ActivitySessionRegistration $lockedReg */
            $lockedReg = RowLock::acquire(ActivitySessionRegistration::class, $sessionReg->id);

            if ($lockedReg->completion_status !== 'completed') {
                return ['awarded' => false, 'exp_awarded' => 0, 'points_awarded' => 0];
            }

            $session = $lockedReg->session;
            $activity = $lockedReg->activity;
            $user = $lockedReg->user;

            $idempotencyKey = "activity_session_{$session->id}_user_{$user->id}";

            if ($lockedReg->rewards_awarded || ActivityReward::where('idempotency_key', $idempotencyKey)->exists()) {
                return ['awarded' => false, 'reason' => 'already_awarded', 'exp_awarded' => 0, 'points_awarded' => 0];
            }

            $expToAward = max(0, (int) $session->exp_reward);
            $pointsToAward = max(0, (int) $session->points_reward);

            $xpTxId = null;
            $ptTxId = null;
            $actualExpAwarded = 0;
            $actualPointsAwarded = 0;

            if ($expToAward > 0) {
                $expResult = $this->expLedgerService->creditExp(
                    user: $user,
                    amount: $expToAward,
                    sourceType: 'attendance',
                    sourceId: $session->id,
                    description: "Activity Session Completed: {$activity->title} — {$session->name}",
                    expirationDays: null,
                    idempotencyKey: $idempotencyKey . '_exp',
                    actor: $actor
                );

                if ($expResult['awarded']) {
                    $actualExpAwarded = $expToAward;
                    $xpTxId = $expResult['transaction']->id ?? null;
                }
            }

            if ($pointsToAward > 0) {
                $pointsKey = $idempotencyKey . '_points';
                $existingPt = PointTransaction::where('idempotency_key', $pointsKey)->first();
                if (!$existingPt) {
                    $activeSeason = Season::where('is_active', true)->first();
                    $ptTx = PointTransaction::create([
                        'user_id' => $user->id,
                        'season_id' => $activeSeason?->id,
                        'amount' => $pointsToAward,
                        'transaction_type' => 'earn',
                        'source_type' => ActivitySession::class,
                        'source_id' => $session->id,
                        'description' => "Activity Session Points: {$activity->title} — {$session->name}",
                        'idempotency_key' => $pointsKey,
                        'created_by' => $actor?->id,
                    ]);
                    $ptTxId = $ptTx->id;
                    $actualPointsAwarded = $pointsToAward;

                    if ($activeSeason) {
                        $stat = MemberSeasonStat::firstOrCreate(
                            ['user_id' => $user->id, 'season_id' => $activeSeason->id],
                            ['season_xp' => 0, 'redeemable_points' => 0, 'current_level' => 1]
                        );
                        $stat->increment('redeemable_points', $pointsToAward);
                    }
                }
            }

            ActivityReward::create([
                'activity_id' => $activity->id,
                'activity_session_id' => $session->id,
                'activity_registration_id' => $lockedReg->activity_registration_id,
                'activity_session_registration_id' => $lockedReg->id,
                'user_id' => $user->id,
                'reward_scope' => 'session',
                'exp_amount' => $actualExpAwarded,
                'points_amount' => $actualPointsAwarded,
                'xp_transaction_id' => $xpTxId,
                'point_transaction_id' => $ptTxId,
                'idempotency_key' => $idempotencyKey,
                'awarded_by' => $actor?->id,
            ]);

            $lockedReg->update([
                'rewards_awarded' => true,
                'exp_awarded' => $actualExpAwarded,
                'points_awarded' => $actualPointsAwarded,
                'rewarded_at' => now(),
            ]);

            AuditLogger::log('activity_reward.session_issued', $lockedReg, [], [
                'activity_id' => $activity->id,
                'session_id' => $session->id,
                'user_id' => $user->id,
                'exp_awarded' => $actualExpAwarded,
                'points_awarded' => $actualPointsAwarded,
            ]);

            return [
                'awarded' => true,
                'exp_awarded' => $actualExpAwarded,
                'points_awarded' => $actualPointsAwarded,
            ];
        });
    }

    /**
     * Derive parent activity completion status from authoritative session records,
     * and issue optional parent-level bonus rewards if configured by Super Admin.
     */
    public function evaluateParentCompletionAndRewards(ActivityRegistration $parentReg, ?User $actor = null): void
    {
        DB::transaction(function () use ($parentReg, $actor) {
            /** @var ActivityRegistration $lockedParent */
            $lockedParent = RowLock::acquire(ActivityRegistration::class, $parentReg->id);
            $activity = $lockedParent->activity;
            $sessionRegs = $lockedParent->sessionRegistrations()->where('status', 'approved')->get();

            if ($sessionRegs->isEmpty()) {
                return;
            }

            $completedCount = $sessionRegs->where('completion_status', 'completed')->count();
            $attendedCount = $sessionRegs->whereIn('completion_status', ['attended', 'completed'])->count();
            $totalApproved = $sessionRegs->count();

            $isParentCompleted = match ($activity->completion_mode) {
                'all_selected_sessions' => $completedCount >= $totalApproved && $totalApproved > 0,
                'minimum_sessions' => $completedCount >= max(1, $activity->min_sessions_for_completion),
                'manual' => $lockedParent->completion_status === 'completed',
                default => $completedCount >= 1, // any_session
            };

            if ($isParentCompleted) {
                $lockedParent->update(['completion_status' => 'completed']);
            } elseif ($attendedCount > 0) {
                $lockedParent->update(['completion_status' => 'attended']);
            }

            if ($isParentCompleted && $activity->award_parent_rewards && !$lockedParent->parent_rewards_awarded) {
                $idempotencyKey = "activity_parent_{$activity->id}_user_{$lockedParent->user_id}";
                if (ActivityReward::where('idempotency_key', $idempotencyKey)->exists()) {
                    return;
                }

                $expAmount = max(0, (int) $activity->parent_exp_reward);
                $pointsAmount = max(0, (int) $activity->parent_points_reward);

                if ($expAmount === 0 && $pointsAmount === 0) {
                    return;
                }

                $user = $lockedParent->user;
                $xpTxId = null;
                $ptTxId = null;
                $actualExp = 0;
                $actualPoints = 0;

                if ($expAmount > 0) {
                    $expRes = $this->expLedgerService->creditExp(
                        user: $user,
                        amount: $expAmount,
                        sourceType: 'attendance',
                        sourceId: $activity->id,
                        description: "Parent Activity Completion Bonus: {$activity->title}",
                        expirationDays: null,
                        idempotencyKey: $idempotencyKey . '_exp',
                        actor: $actor
                    );
                    if ($expRes['awarded']) {
                        $actualExp = $expAmount;
                        $xpTxId = $expRes['transaction']->id ?? null;
                    }
                }

                if ($pointsAmount > 0) {
                    $pointsKey = $idempotencyKey . '_points';
                    if (!PointTransaction::where('idempotency_key', $pointsKey)->exists()) {
                        $activeSeason = Season::where('is_active', true)->first();
                        $ptTx = PointTransaction::create([
                            'user_id' => $user->id,
                            'season_id' => $activeSeason?->id,
                            'amount' => $pointsAmount,
                            'transaction_type' => 'earn',
                            'source_type' => Activity::class,
                            'source_id' => $activity->id,
                            'description' => "Parent Activity Completion Points: {$activity->title}",
                            'idempotency_key' => $pointsKey,
                            'created_by' => $actor?->id,
                        ]);
                        $ptTxId = $ptTx->id;
                        $actualPoints = $pointsAmount;

                        if ($activeSeason) {
                            $stat = MemberSeasonStat::firstOrCreate(
                                ['user_id' => $user->id, 'season_id' => $activeSeason->id],
                                ['season_xp' => 0, 'redeemable_points' => 0, 'current_level' => 1]
                            );
                            $stat->increment('redeemable_points', $pointsAmount);
                        }
                    }
                }

                ActivityReward::create([
                    'activity_id' => $activity->id,
                    'activity_session_id' => null,
                    'activity_registration_id' => $lockedParent->id,
                    'activity_session_registration_id' => null,
                    'user_id' => $user->id,
                    'reward_scope' => 'parent',
                    'exp_amount' => $actualExp,
                    'points_amount' => $actualPoints,
                    'xp_transaction_id' => $xpTxId,
                    'point_transaction_id' => $ptTxId,
                    'idempotency_key' => $idempotencyKey,
                    'awarded_by' => $actor?->id,
                ]);

                $lockedParent->update([
                    'parent_rewards_awarded' => true,
                    'parent_exp_awarded' => $actualExp,
                    'parent_points_awarded' => $actualPoints,
                    'parent_rewarded_at' => now(),
                ]);

                AuditLogger::log('activity_reward.parent_issued', $lockedParent, [], [
                    'activity_id' => $activity->id,
                    'user_id' => $user->id,
                    'exp_awarded' => $actualExp,
                    'points_awarded' => $actualPoints,
                ]);
            }
        });
    }
}

