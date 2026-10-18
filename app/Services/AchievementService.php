<?php

namespace App\Services;

use App\Models\Achievement;
use App\Models\AttendanceRecord;
use App\Models\MemberSeasonStat;
use App\Models\MissionSubmission;
use App\Models\RewardRedemption;
use App\Models\Season;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AchievementService
{
    public function award(User $user, Achievement $achievement, ?User $actor = null): bool
    {
        return DB::transaction(function () use ($user, $achievement, $actor) {
            $alreadyUnlocked = DB::table('user_achievements')
                ->where('user_id', $user->id)
                ->where('achievement_id', $achievement->id)
                ->lockForUpdate()
                ->exists();

            if ($alreadyUnlocked) {
                return false;
            }

            DB::table('user_achievements')->insert([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'user_id' => $user->id,
                'achievement_id' => $achievement->id,
                'unlocked_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($achievement->xp_bonus > 0 || $achievement->points_bonus > 0) {
                $rewardService = app(RewardService::class);
                $rewardService->grantReward(
                    user: $user,
                    activityType: 'achievement_unlocked',
                    xpAmount: $achievement->xp_bonus,
                    pointsAmount: $achievement->points_bonus,
                    description: 'Achievement unlocked: ' . $achievement->title,
                    sourceType: Achievement::class,
                    sourceId: $achievement->id,
                    idempotencyKey: 'achievement_' . $achievement->id . '_user_' . $user->id,
                    actor: $actor
                );
            }

            \App\Services\NotificationService::send(
                $user,
                'achievement_unlocked',
                'Achievement Unlocked: ' . $achievement->title,
                'Congratulations! You unlocked "' . $achievement->title . '" (' . $achievement->tier . ' tier).',
                route('leaderboard.index', ['tab' => 'achievements'])
            );

            return true;
        });
    }

    public function evaluateAutomatedAchievements(User $user): array
    {
        $unlocked = [];
        $activeAchievements = Achievement::where('is_active', true)
            ->where('criteria_type', '!=', 'custom')
            ->get();

        if ($activeAchievements->isEmpty()) {
            return $unlocked;
        }

        $activeSeason = Season::where('is_active', true)->first() ?: Season::latest('start_date')->first();
        $stat = $activeSeason
            ? MemberSeasonStat::where('user_id', $user->id)->where('season_id', $activeSeason->id)->first()
            : null;

        $student = $user->student;
        $attendanceCount = $student
            ? AttendanceRecord::where('participant_id', $student->id)->count()
            : \App\Models\EventRegistration::where('user_id', $user->id)->where('status', 'approved')->count();
        $missionsCompletedCount = MissionSubmission::where('user_id', $user->id)->where('status', 'approved')->count();
        $redemptionsCount = RewardRedemption::where('user_id', $user->id)->whereIn('status', ['pending', 'fulfilled'])->count();
        $totalXp = $stat?->season_xp ?? 0;

        foreach ($activeAchievements as $achievement) {
            $qualifies = false;

            switch ($achievement->criteria_type) {
                case 'event_attendance':
                    $qualifies = $attendanceCount >= $achievement->criteria_threshold;
                    break;
                case 'mission_completion':
                    $qualifies = $missionsCompletedCount >= $achievement->criteria_threshold;
                    break;
                case 'xp_earned':
                    $qualifies = $totalXp >= $achievement->criteria_threshold;
                    break;
                case 'reward_redemption':
                    $qualifies = $redemptionsCount >= $achievement->criteria_threshold;
                    break;
            }

            if ($qualifies) {
                if ($this->award($user, $achievement)) {
                    $unlocked[] = $achievement;
                }
            }
        }

        return $unlocked;
    }

    public function getMemberProgress(User $user): array
    {
        $activeSeason = Season::where('is_active', true)->first() ?: Season::latest('start_date')->first();
        $stat = $activeSeason
            ? MemberSeasonStat::where('user_id', $user->id)->where('season_id', $activeSeason->id)->first()
            : null;

        $student = $user->student;
        $attendanceCount = $student
            ? AttendanceRecord::where('participant_id', $student->id)->count()
            : \App\Models\EventRegistration::where('user_id', $user->id)->where('status', 'approved')->count();
        $missionsCompletedCount = MissionSubmission::where('user_id', $user->id)->where('status', 'approved')->count();
        $redemptionsCount = RewardRedemption::where('user_id', $user->id)->whereIn('status', ['pending', 'fulfilled'])->count();
        $totalXp = $stat?->season_xp ?? 0;

        $unlockedIds = DB::table('user_achievements')
            ->where('user_id', $user->id)
            ->pluck('achievement_id')
            ->all();

        $achievements = Achievement::where('is_active', true)->get();

        $result = [];
        foreach ($achievements as $ach) {
            $isUnlocked = in_array($ach->id, $unlockedIds);
            $currentVal = match ($ach->criteria_type) {
                'event_attendance' => $attendanceCount,
                'mission_completion' => $missionsCompletedCount,
                'xp_earned' => $totalXp,
                'reward_redemption' => $redemptionsCount,
                default => $isUnlocked ? 1 : 0,
            };

            $threshold = max(1, $ach->criteria_threshold);
            $percent = $isUnlocked ? 100 : min(100, (int) round(($currentVal / $threshold) * 100));

            $result[] = [
                'achievement' => $ach,
                'is_unlocked' => $isUnlocked,
                'current' => min($currentVal, $threshold),
                'threshold' => $threshold,
                'percent' => $percent,
            ];
        }

        return $result;
    }
}
