<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Level;
use App\Models\MemberSeasonStat;
use App\Models\Mission;
use App\Models\MissionSubmission;
use App\Models\PointTransaction;
use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Models\RewardRule;
use App\Models\Season;
use App\Models\User;
use App\Models\XpTransaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RewardService
{
    public function awardForEventAttendance(User $user, Event $event, ?string $sessionId = null, ?User $actor = null): array
    {
        $rule = RewardRule::where('activity_type', 'event_attendance')
            ->where('is_active', true)
            ->first();

        $xpAmount = $event->reward_xp ?? ($rule?->xp_amount ?? 50);
        $pointsAmount = $event->reward_points ?? ($rule?->points_amount ?? 20);

        $idempotencyKey = 'event_' . $event->id . '_user_' . $user->id;
        if ($sessionId) {
            $idempotencyKey .= '_session_' . $sessionId;
        }

        return $this->grantReward(
            user: $user,
            activityType: 'event_attendance',
            xpAmount: $xpAmount,
            pointsAmount: $pointsAmount,
            description: 'Attended event: ' . $event->name,
            sourceType: Event::class,
            sourceId: $event->id,
            idempotencyKey: $idempotencyKey,
            actor: $actor
        );
    }

    public function awardForMissionCompletion(User $user, Mission $mission, MissionSubmission $submission, ?User $actor = null): array
    {
        $idempotencyKey = 'mission_' . $mission->id . '_user_' . $user->id;

        return $this->grantReward(
            user: $user,
            activityType: 'mission_completion',
            xpAmount: $mission->xp_reward,
            pointsAmount: $mission->points_reward,
            description: 'Completed mission: ' . $mission->title,
            sourceType: Mission::class,
            sourceId: $mission->id,
            idempotencyKey: $idempotencyKey,
            actor: $actor
        );
    }

    public function grantReward(
        User $user,
        string $activityType,
        int $xpAmount,
        int $pointsAmount,
        string $description,
        ?string $sourceType = null,
        ?string $sourceId = null,
        ?string $idempotencyKey = null,
        ?User $actor = null
    ): array {
        if ($xpAmount < 0 || $pointsAmount < 0) {
            throw new InvalidArgumentException('Reward amounts cannot be negative.');
        }

        $activeSeason = Season::where('is_active', true)->first() ?: Season::latest('start_date')->first();

        return DB::transaction(function () use (
            $user,
            $activeSeason,
            $activityType,
            $xpAmount,
            $pointsAmount,
            $description,
            $sourceType,
            $sourceId,
            $idempotencyKey,
            $actor
        ) {
            if ($idempotencyKey) {
                $existingXp = XpTransaction::where('idempotency_key', $idempotencyKey)->first();
                if ($existingXp) {
                    return [
                        'awarded' => false,
                        'reason' => 'already_rewarded',
                        'xp' => 0,
                        'points' => 0,
                    ];
                }
            }

            if ($xpAmount > 0) {
                XpTransaction::create([
                    'user_id' => $user->id,
                    'season_id' => $activeSeason?->id,
                    'amount' => $xpAmount,
                    'activity_type' => $activityType,
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                    'description' => $description,
                    'idempotency_key' => $idempotencyKey,
                    'created_by' => $actor?->id,
                ]);
            }

            if ($pointsAmount > 0) {
                PointTransaction::create([
                    'user_id' => $user->id,
                    'season_id' => $activeSeason?->id,
                    'amount' => $pointsAmount,
                    'transaction_type' => $activityType,
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                    'description' => $description,
                    'idempotency_key' => $idempotencyKey ? $idempotencyKey . '_points' : null,
                    'created_by' => $actor?->id,
                ]);
            }

            $currentSeasonXp = 0;
            $currentSeasonPoints = 0;
            $newLevel = 1;

            if ($activeSeason) {
                $stat = MemberSeasonStat::firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'season_id' => $activeSeason->id,
                    ],
                    [
                        'season_xp' => 0,
                        'redeemable_points' => 0,
                        'current_level' => 1,
                    ]
                );

                if ($xpAmount > 0) {
                    $stat->increment('season_xp', $xpAmount);
                }
                if ($pointsAmount > 0) {
                    $stat->increment('redeemable_points', $pointsAmount);
                }

                $stat->refresh();
                $currentSeasonXp = $stat->season_xp;
                $currentSeasonPoints = $stat->redeemable_points;

                $oldLevel = $stat->current_level;
                $calculatedLevel = Level::forXp($currentSeasonXp)->level;
                if ($calculatedLevel !== $stat->current_level) {
                    $stat->update(['current_level' => $calculatedLevel]);
                    if ($calculatedLevel > $oldLevel) {
                        \App\Services\NotificationService::send(
                            $user,
                            'level_up',
                            'Level Up! Reached Level ' . $calculatedLevel,
                            'Congratulations! Your XP progression unlocked Level ' . $calculatedLevel . '.',
                            route('dashboard')
                        );
                    }
                }
                $newLevel = $stat->current_level;
            }

            if ($activityType !== 'achievement_unlocked') {
                try {
                    app(\App\Services\AchievementService::class)->evaluateAutomatedAchievements($user);
                } catch (\Throwable $e) {
                }
            }

            return [
                'awarded' => true,
                'xp' => $xpAmount,
                'points' => $pointsAmount,
                'current_season_xp' => $currentSeasonXp,
                'current_season_points' => $currentSeasonPoints,
                'level' => $newLevel,
            ];
        });
    }

    public function adjustBalances(
        User $user,
        int $xpAdjustment,
        int $pointsAdjustment,
        string $reason,
        User $admin
    ): array {
        if (!$admin->isAdmin()) {
            abort(403, 'Unauthorized balance adjustment.');
        }

        $activeSeason = Season::where('is_active', true)->first() ?: Season::latest('start_date')->first();

        return DB::transaction(function () use ($user, $activeSeason, $xpAdjustment, $pointsAdjustment, $reason, $admin) {
            if ($xpAdjustment !== 0) {
                XpTransaction::create([
                    'user_id' => $user->id,
                    'season_id' => $activeSeason?->id,
                    'amount' => $xpAdjustment,
                    'activity_type' => 'manual_adjustment',
                    'description' => 'Admin adjustment: ' . $reason,
                    'created_by' => $admin->id,
                ]);
            }

            if ($pointsAdjustment !== 0) {
                PointTransaction::create([
                    'user_id' => $user->id,
                    'season_id' => $activeSeason?->id,
                    'amount' => $pointsAdjustment,
                    'transaction_type' => 'manual_adjustment',
                    'description' => 'Admin adjustment: ' . $reason,
                    'created_by' => $admin->id,
                ]);
            }

            if ($activeSeason) {
                $stat = MemberSeasonStat::firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'season_id' => $activeSeason->id,
                    ],
                    [
                        'season_xp' => 0,
                        'redeemable_points' => 0,
                        'current_level' => 1,
                    ]
                );

                $newXp = max(0, $stat->season_xp + $xpAdjustment);
                $newPoints = max(0, $stat->redeemable_points + $pointsAdjustment);
                $calculatedLevel = Level::forXp($newXp)->level;

                $stat->update([
                    'season_xp' => $newXp,
                    'redeemable_points' => $newPoints,
                    'current_level' => $calculatedLevel,
                ]);
            }

            return [
                'success' => true,
                'xp_adjustment' => $xpAdjustment,
                'points_adjustment' => $pointsAdjustment,
            ];
        });
    }

    public function spendPoints(User $user, int $amount, string $description, ?string $sourceType = null, ?string $sourceId = null): array
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Points amount to spend must be greater than zero.');
        }

        $activeSeason = Season::where('is_active', true)->first() ?: Season::latest('start_date')->first();

        return DB::transaction(function () use ($user, $activeSeason, $amount, $description, $sourceType, $sourceId) {
            $stat = $activeSeason
                ? MemberSeasonStat::where('user_id', $user->id)->where('season_id', $activeSeason->id)->first()
                : null;

            if ($stat) {
                $stat = \App\Support\RowLock::acquire(MemberSeasonStat::class, $stat->id);
            }

            $currentBalance = $stat?->redeemable_points ?? 0;

            if ($currentBalance < $amount) {
                return [
                    'success' => false,
                    'message' => 'Insufficient redeemable points balance.',
                ];
            }

            PointTransaction::create([
                'user_id' => $user->id,
                'season_id' => $activeSeason?->id,
                'amount' => -$amount,
                'transaction_type' => 'reward_redemption',
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'description' => $description,
            ]);

            $stat->decrement('redeemable_points', $amount);

            return [
                'success' => true,
                'spent' => $amount,
                'remaining_balance' => $stat->fresh()->redeemable_points,
            ];
        });
    }

    public function redeemReward(User $user, Reward $reward, ?string $notes = null): array
    {
        return DB::transaction(function () use ($user, $reward, $notes) {
            $lockedReward = \App\Support\RowLock::acquire(Reward::class, $reward->id);

            if (!$lockedReward || !$lockedReward->isPublished()) {
                return [
                    'success' => false,
                    'message' => 'This reward is currently in draft or unavailable for redemption.',
                ];
            }

            if ($lockedReward->starts_at && $lockedReward->starts_at->isFuture()) {
                return [
                    'success' => false,
                    'message' => 'This reward is not yet available for redemption.',
                ];
            }

            if ($lockedReward->ends_at && $lockedReward->ends_at->isPast()) {
                return [
                    'success' => false,
                    'message' => 'The redemption period for this reward has expired.',
                ];
            }

            if ($lockedReward->stock !== null && $lockedReward->stock <= 0) {
                return [
                    'success' => false,
                    'message' => 'This reward is currently out of stock.',
                ];
            }

            if ($lockedReward->redemption_limit_per_member !== null && $lockedReward->redemption_limit_per_member > 0) {
                $userRedemptionsCount = RewardRedemption::where('user_id', $user->id)
                    ->where('reward_id', $lockedReward->id)
                    ->whereNotIn('status', ['cancelled', 'refunded', 'rejected'])
                    ->count();

                if ($userRedemptionsCount >= $lockedReward->redemption_limit_per_member) {
                    return [
                        'success' => false,
                        'message' => "Redemption limit reached. You can only redeem this reward up to {$lockedReward->redemption_limit_per_member} time(s).",
                    ];
                }
            }

            $activeSeason = Season::where('is_active', true)->first() ?: Season::latest('start_date')->first();

            $stat = $activeSeason
                ? MemberSeasonStat::where('user_id', $user->id)
                    ->where('season_id', $activeSeason->id)
                    ->lockForUpdate()
                    ->first()
                : null;

            $currentPoints = $stat?->redeemable_points ?? 0;
            $currentLevel = $stat?->current_level ?? 1;

            if ($lockedReward->min_level > $currentLevel) {
                return [
                    'success' => false,
                    'message' => 'Level ' . $lockedReward->min_level . ' is required to redeem this reward. (Current: Level ' . $currentLevel . ')',
                ];
            }

            if ($currentPoints < $lockedReward->points_cost) {
                return [
                    'success' => false,
                    'message' => 'Insufficient redeemable points. Required: ' . $lockedReward->points_cost . ', Available: ' . $currentPoints,
                ];
            }

            $redemption = RewardRedemption::create([
                'user_id' => $user->id,
                'reward_id' => $lockedReward->id,
                'season_id' => $activeSeason?->id,
                'points_spent' => $lockedReward->points_cost,
                'item_title_snapshot' => $lockedReward->title,
                'status' => 'pending',
                'notes' => $notes,
            ]);

            PointTransaction::create([
                'user_id' => $user->id,
                'season_id' => $activeSeason?->id,
                'amount' => -$lockedReward->points_cost,
                'transaction_type' => 'reward_redemption',
                'source_type' => RewardRedemption::class,
                'source_id' => $redemption->id,
                'description' => 'Redeemed: ' . $lockedReward->title,
            ]);

            $stat->decrement('redeemable_points', $lockedReward->points_cost);

            if ($lockedReward->stock !== null) {
                $lockedReward->decrement('stock', 1);
            }

            return [
                'success' => true,
                'redemption' => $redemption,
                'remaining_balance' => $stat->fresh()->redeemable_points,
            ];
        });
    }

    public function refundRedemption(RewardRedemption $redemption, string $reason, User $admin): array
    {
        return DB::transaction(function () use ($redemption, $reason, $admin) {
            $lockedRedemption = \App\Support\RowLock::acquire(RewardRedemption::class, $redemption->id);

            if (!$lockedRedemption || $lockedRedemption->status === 'rejected' || $lockedRedemption->status === 'cancelled') {
                return [
                    'success' => false,
                    'message' => 'This redemption request has already been cancelled or rejected.',
                ];
            }

            $reward = Reward::where('id', $lockedRedemption->reward_id)->lockForUpdate()->first();

            if ($reward && $reward->stock !== null) {
                $reward->increment('stock', 1);
            }

            $season = $lockedRedemption->season_id
                ? Season::find($lockedRedemption->season_id)
                : (Season::where('is_active', true)->first() ?: Season::latest('start_date')->first());

            if ($season) {
                $stat = MemberSeasonStat::firstOrCreate(
                    [
                        'user_id' => $lockedRedemption->user_id,
                        'season_id' => $season->id,
                    ],
                    [
                        'season_xp' => 0,
                        'redeemable_points' => 0,
                        'current_level' => 1,
                    ]
                );

                $stat->increment('redeemable_points', $lockedRedemption->points_spent);

                PointTransaction::create([
                    'user_id' => $lockedRedemption->user_id,
                    'season_id' => $season->id,
                    'amount' => $lockedRedemption->points_spent,
                    'transaction_type' => 'redemption_refund',
                    'source_type' => RewardRedemption::class,
                    'source_id' => $lockedRedemption->id,
                    'description' => 'Refund for: ' . $lockedRedemption->item_title_snapshot . ' (' . $reason . ')',
                    'created_by' => $admin->id,
                ]);
            }

            $lockedRedemption->update([
                'status' => 'rejected',
                'rejection_reason' => $reason,
                'fulfilled_by' => $admin->id,
            ]);

            return [
                'success' => true,
                'redemption' => $lockedRedemption,
            ];
        });
    }
}
