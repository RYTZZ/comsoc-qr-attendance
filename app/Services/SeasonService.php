<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\MemberSeasonStat;
use App\Models\PointTransaction;
use App\Models\RewardRedemption;
use App\Models\Season;
use App\Models\SeasonLeaderboardSnapshot;
use App\Models\SeasonReset;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SeasonService
{
    public function previewSeasonClose(Season $season): array
    {
        $memberCount = MemberSeasonStat::where('season_id', $season->id)->count();
        $totalSeasonXp = (int) MemberSeasonStat::where('season_id', $season->id)->sum('season_xp');
        $totalExpiringPoints = (int) MemberSeasonStat::where('season_id', $season->id)->sum('redeemable_points');

        $pendingRedemptions = RewardRedemption::where('season_id', $season->id)
            ->where('status', 'pending')
            ->count();

        $topMembers = MemberSeasonStat::where('season_id', $season->id)
            ->with(['user.student'])
            ->orderByDesc('season_xp')
            ->take(5)
            ->get();

        return [
            'season' => $season,
            'member_count' => $memberCount,
            'total_season_xp' => $totalSeasonXp,
            'total_expiring_points' => $totalExpiringPoints,
            'pending_redemptions' => $pendingRedemptions,
            'top_members' => $topMembers,
            'can_close' => true,
        ];
    }

    public function executeSeasonReset(Season $closingSeason, ?Season $nextSeason, User $executor): SeasonReset
    {
        return DB::transaction(function () use ($closingSeason, $nextSeason, $executor) {
            $lockedSeason = Season::where('id', $closingSeason->id)->lockForUpdate()->first();

            $existingReset = SeasonReset::where('closing_season_id', $lockedSeason->id)->first();
            if ($existingReset) {
                return $existingReset;
            }

            $memberStats = MemberSeasonStat::where('season_id', $lockedSeason->id)
                ->orderByDesc('season_xp')
                ->lockForUpdate()
                ->get();

            $rank = 1;
            $pointsExpiredTotal = 0;
            $membersAffected = 0;

            foreach ($memberStats as $stat) {
                SeasonLeaderboardSnapshot::create([
                    'season_id' => $lockedSeason->id,
                    'user_id' => $stat->user_id,
                    'final_rank' => $rank++,
                    'final_xp' => $stat->season_xp,
                    'final_points' => $stat->redeemable_points,
                    'final_level' => $stat->current_level,
                    'snapshot_at' => now(),
                ]);

                if ($stat->redeemable_points > 0) {
                    PointTransaction::create([
                        'user_id' => $stat->user_id,
                        'season_id' => $lockedSeason->id,
                        'amount' => -$stat->redeemable_points,
                        'transaction_type' => 'season_reset_expiration',
                        'source_type' => Season::class,
                        'source_id' => $lockedSeason->id,
                        'description' => 'Season closed: Unused redeemable points expired for ' . $lockedSeason->name,
                        'created_by' => $executor->id,
                    ]);

                    $pointsExpiredTotal += $stat->redeemable_points;
                }

                $membersAffected++;
            }

            $lockedSeason->update([
                'is_active' => false,
                'status' => 'closed',
            ]);

            if ($nextSeason) {
                Season::where('is_active', true)->where('id', '!=', $nextSeason->id)->update(['is_active' => false]);
                $nextSeason->update([
                    'is_active' => true,
                    'status' => 'active',
                ]);
            }

            $resetRecord = SeasonReset::create([
                'closing_season_id' => $lockedSeason->id,
                'next_season_id' => $nextSeason?->id,
                'executed_by' => $executor->id,
                'members_affected_count' => $membersAffected,
                'points_expired_sum' => $pointsExpiredTotal,
                'status' => 'completed',
                'details' => [
                    'closed_season_name' => $lockedSeason->name,
                    'next_season_name' => $nextSeason?->name,
                    'top_rank_count' => min(10, $membersAffected),
                ],
                'executed_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => $executor->id,
                'action' => 'executed_season_reset',
                'details' => json_encode([
                    'closing_season_id' => $lockedSeason->id,
                    'members_affected' => $membersAffected,
                    'points_expired' => $pointsExpiredTotal,
                ]),
            ]);

            return $resetRecord;
        });
    }

    public function getSemesterReportData(?string $seasonId = null): array
    {
        $season = $seasonId ? Season::find($seasonId) : Season::active();
        if (!$season) {
            $season = Season::latest('start_date')->first();
        }

        $startDate = $season?->start_date?->startOfDay() ?? now()->subMonths(6);
        $endDate = $season?->end_date?->endOfDay() ?? now();

        $eventsCount = \App\Models\Event::whereBetween('created_at', [$startDate, $endDate])->count();
        $registrationsCount = \App\Models\EventRegistration::whereBetween('created_at', [$startDate, $endDate])->count();
        $attendanceScansCount = \App\Models\AttendanceRecord::whereBetween('scanned_at', [$startDate, $endDate])->count();

        $missionsCreatedCount = \App\Models\Mission::whereBetween('created_at', [$startDate, $endDate])->count();
        $missionsApprovedCount = \App\Models\MissionSubmission::where('status', 'approved')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->count();

        $xpDistributed = (int) \App\Models\XpTransaction::whereBetween('created_at', [$startDate, $endDate])->sum('amount');
        $pointsDistributed = (int) \App\Models\PointTransaction::where('amount', '>', 0)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');

        $redemptionsCount = \App\Models\RewardRedemption::whereBetween('created_at', [$startDate, $endDate])->count();
        $redemptionsFulfilled = \App\Models\RewardRedemption::where('status', 'fulfilled')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->count();

        $allSeasons = Season::orderByDesc('start_date')->get();

        return [
            'season' => $season,
            'all_seasons' => $allSeasons,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'events_count' => $eventsCount,
            'registrations_count' => $registrationsCount,
            'attendance_scans_count' => $attendanceScansCount,
            'missions_created_count' => $missionsCreatedCount,
            'missions_approved_count' => $missionsApprovedCount,
            'xp_distributed' => $xpDistributed,
            'points_distributed' => $pointsDistributed,
            'redemptions_count' => $redemptionsCount,
            'redemptions_fulfilled' => $redemptionsFulfilled,
        ];
    }
}
