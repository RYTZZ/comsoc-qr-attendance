<?php

namespace App\Services;

use App\Models\MemberSeasonStat;
use App\Models\Season;
use App\Models\User;
use App\Models\XpTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LeaderboardService
{
    /**
     * Semester Leaderboard:
     * Based strictly on valid EXP earned during the configured semester start and end dates.
     * Redemption and ordinary expiration do NOT reduce valid semester earned totals.
     * Student numbers are NEVER exposed. Only preferred nickname (or name).
     */
    public function getSemesterLeaderboard(?Season $season = null, int $perPage = 25, int $page = 1): LengthAwarePaginator
    {
        $season = $season ?: (Season::where('is_active', true)->first() ?: Season::latest('start_date')->first());

        if (!$season) {
            return new LengthAwarePaginator([], 0, $perPage, $page);
        }

        $startDate = $season->start_date ? $season->start_date . ' 00:00:00' : '2000-01-01 00:00:00';
        $endDate = $season->end_date ? $season->end_date . ' 23:59:59' : '2099-12-31 23:59:59';

        $users = User::query()
            ->leftJoin('user_profiles', 'user_profiles.user_id', '=', 'users.id')
            ->leftJoin('member_season_stats', function ($join) use ($season) {
                $join->on('member_season_stats.user_id', '=', 'users.id')
                     ->where('member_season_stats.season_id', '=', $season->id);
            })
            ->select('users.id', 'users.name', 'users.nickname', 'users.avatar_style', 'users.avatar_seed')
            ->whereIn('users.role', ['member', 'student'])
            ->where('users.is_active', true)
            ->where('users.is_eligible', true)
            ->where(function ($q) {
                $q->whereNull('user_profiles.is_public')
                  ->orWhere('user_profiles.is_public', true);
            })
            ->selectSub(function ($q) use ($startDate, $endDate) {
                $q->from('xp_transactions')
                    ->whereColumn('xp_transactions.user_id', 'users.id')
                    ->where('xp_transactions.lifetime_delta', '>', 0)
                    ->whereBetween('xp_transactions.created_at', [$startDate, $endDate])
                    ->selectRaw('COALESCE(SUM(lifetime_delta), 0)');
            }, 'tx_exp')
            ->addSelect(DB::raw('COALESCE(member_season_stats.season_xp, 0) as stat_exp'))
            ->get()
            ->map(function ($u) {
                $u->semester_exp = max((int) $u->tx_exp, (int) $u->stat_exp);
                return $u;
            })
            ->filter(fn ($u) => $u->semester_exp > 0)
            ->sortByDesc('semester_exp')
            ->values();

        $sliced = $users->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator($sliced, $users->count(), $perPage, $page, [
            'path' => request()->url(),
            'query' => request()->query(),
        ]);
    }

    /**
     * Lifetime Leaderboard:
     * Cumulative valid Lifetime EXP earned since account creation.
     * Unaffected by ordinary redemption and expiration.
     */
    public function getLifetimeLeaderboard(int $perPage = 25, int $page = 1): LengthAwarePaginator
    {
        $sub = User::query()
            ->leftJoin('user_profiles', 'user_profiles.user_id', '=', 'users.id')
            ->select('users.id', 'users.name', 'users.nickname', 'users.avatar_style', 'users.avatar_seed')
            ->whereIn('users.role', ['member', 'student'])
            ->where('users.is_active', true)
            ->where('users.is_eligible', true)
            ->where(function ($q) {
                $q->whereNull('user_profiles.is_public')
                  ->orWhere('user_profiles.is_public', true);
            })
            ->selectSub(function ($q) {
                $q->from('xp_transactions')
                    ->whereColumn('xp_transactions.user_id', 'users.id')
                    ->selectRaw('COALESCE(SUM(lifetime_delta), 0)');
            }, 'lifetime_exp');

        $query = DB::table(DB::raw("({$sub->toSql()}) as ranked_members"))
            ->mergeBindings($sub->getQuery())
            ->where('lifetime_exp', '>', 0)
            ->orderByDesc('lifetime_exp');

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get user rank in a specific leaderboard type ('semester' or 'lifetime').
     */
    public function getUserRank(User $user, mixed $type = 'semester', mixed $season = null): ?array
    {
        $resolvedSeason = null;
        $resolvedType = 'semester';

        if ($type instanceof Season) {
            $resolvedSeason = $type;
            $resolvedType = is_string($season) ? $season : 'semester';
        } elseif (is_string($type)) {
            $resolvedType = $type;
            $resolvedSeason = $season instanceof Season ? $season : null;
        }

        if ($resolvedType === 'semester' || $resolvedType === 'season_xp') {
            $resolvedSeason = $resolvedSeason ?: (Season::where('is_active', true)->first() ?: Season::latest('start_date')->first());
            if (!$resolvedSeason) return null;

            $stat = MemberSeasonStat::where('user_id', $user->id)->where('season_id', $resolvedSeason->id)->first();
            if ($stat && $stat->season_xp > 0) {
                $userScore = (int) $stat->season_xp;
                $higherCount = MemberSeasonStat::where('season_id', $resolvedSeason->id)
                    ->where(function ($q) use ($userScore, $stat) {
                        $q->where('season_xp', '>', $userScore)
                          ->orWhere(function ($q2) use ($userScore, $stat) {
                              $q2->where('season_xp', '=', $userScore)
                                 ->where('created_at', '<', $stat->created_at);
                          });
                    })
                    ->count();

                return [
                    'rank' => $higherCount + 1,
                    'score' => $userScore,
                ];
            }

            $startDate = $resolvedSeason->start_date ? $resolvedSeason->start_date . ' 00:00:00' : '2000-01-01 00:00:00';
            $endDate = $resolvedSeason->end_date ? $resolvedSeason->end_date . ' 23:59:59' : '2099-12-31 23:59:59';

            $userScore = (int) XpTransaction::where('user_id', $user->id)
                ->where('lifetime_delta', '>', 0)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('lifetime_delta');

            $higherCount = DB::table('xp_transactions')
                ->join('users', 'users.id', '=', 'xp_transactions.user_id')
                ->whereIn('users.role', ['member', 'student'])
                ->where('users.is_active', true)
                ->where('users.is_eligible', true)
                ->where('xp_transactions.lifetime_delta', '>', 0)
                ->whereBetween('xp_transactions.created_at', [$startDate, $endDate])
                ->groupBy('xp_transactions.user_id')
                ->havingRaw('SUM(xp_transactions.lifetime_delta) > ?', [$userScore])
                ->selectRaw('xp_transactions.user_id')
                ->get()
                ->count();

            return [
                'rank' => $higherCount + 1,
                'score' => $userScore,
            ];
        }

        $userLifetime = $user->getLifetimeExp();
        $higherCount = DB::table('xp_transactions')
            ->join('users', 'users.id', '=', 'xp_transactions.user_id')
            ->whereIn('users.role', ['member', 'student'])
            ->where('users.is_active', true)
            ->where('users.is_eligible', true)
            ->groupBy('xp_transactions.user_id')
            ->havingRaw('SUM(xp_transactions.lifetime_delta) > ?', [$userLifetime])
            ->selectRaw('xp_transactions.user_id')
            ->get()
            ->count();

        return [
            'rank' => $higherCount + 1,
            'score' => $userLifetime,
        ];
    }

    /**
     * Verified Attendance Rankings for Individuals, Blocks, Year Levels, and Academic Programs.
     * Calculated as: (verified attendance count / eligible event opportunities) * 100
     */
    public function getAttendanceLeaderboard(string $group = 'individual', ?Season $season = null): Collection
    {
        $season = $season ?: (Season::where('is_active', true)->first() ?: Season::latest('start_date')->first());
        $startDate = $season?->start_date ? $season->start_date . ' 00:00:00' : '2000-01-01 00:00:00';
        $endDate = $season?->end_date ? $season->end_date . ' 23:59:59' : '2099-12-31 23:59:59';

        // 1. Eligible Events: published or ongoing, non-cancelled, non-draft, within season range
        $eligibleEvents = \App\Models\Event::whereNotIn('status', ['draft', 'cancelled', 'archived'])
            ->whereBetween('event_date', [substr($startDate, 0, 10), substr($endDate, 0, 10)])
            ->get();

        $totalEligibleEventsCount = $eligibleEvents->count();

        if ($group === 'individual') {
            $users = User::query()
                ->with('student')
                ->whereIn('role', ['member', 'student'])
                ->where('is_active', true)
                ->where('is_eligible', true)
                ->get();

            return $users->map(function ($u) use ($eligibleEvents, $totalEligibleEventsCount) {
                $student = $u->student;
                $verifiedCount = 0;
                $eligibleCount = 0;

                foreach ($eligibleEvents as $event) {
                    $isEligible = true;
                    if ($event->requires_registration) {
                        $isRegistered = \App\Models\EventRegistration::where('event_id', $event->id)
                            ->where('status', 'approved')
                            ->where(function ($q) use ($u, $student) {
                                $q->where('user_id', $u->id)
                                  ->orWhere('email', strtolower($u->email));
                                if ($student) $q->orWhere('email', strtolower($student->email));
                            })
                            ->exists();
                        if (!$isRegistered) $isEligible = false;
                    }

                    if ($isEligible) {
                        $eligibleCount++;
                        $attended = \App\Models\AttendanceRecord::where('event_id', $event->id)
                            ->where(function ($q) use ($u, $student) {
                                $q->where('user_id', $u->id);
                                if ($student) $q->orWhere('participant_id', $student->id);
                            })
                            ->exists();
                        if ($attended) $verifiedCount++;
                    }
                }

                $rate = $eligibleCount > 0 ? round(($verifiedCount / $eligibleCount) * 100, 1) : 0.0;

                return (object) [
                    'id' => $u->id,
                    'display_username' => $u->display_username,
                    'name' => $u->display_name,
                    'avatar_seed' => $u->avatar_seed,
                    'avatar_style' => $u->avatar_style,
                    'program' => $student?->program ?? 'Computing Society',
                    'year_level' => $student?->year_level ?? '—',
                    'block' => $student?->block ?? '—',
                    'verified_attendance' => $verifiedCount,
                    'eligible_events' => $eligibleCount,
                    'attendance_rate' => $rate,
                ];
            })
            ->sortBy([
                ['attendance_rate', 'desc'],
                ['verified_attendance', 'desc'],
                ['display_username', 'asc'],
            ])
            ->values();
        }

        if ($group === 'blocks') {
            $students = \App\Models\Student::whereNotNull('block')->whereNotNull('year_level')->whereNotNull('program')->get();
            $grouped = $students->groupBy(fn($s) => "{$s->program} - Year {$s->year_level} (Block {$s->block})");

            return $grouped->map(function ($groupStudents, $key) use ($eligibleEvents) {
                $memberCount = $groupStudents->count();
                $studentIds = $groupStudents->pluck('id')->toArray();
                $totalEligibleOpportunities = $memberCount * $eligibleEvents->count();

                $verifiedCount = \App\Models\AttendanceRecord::whereIn('event_id', $eligibleEvents->pluck('id'))
                    ->whereIn('participant_id', $studentIds)
                    ->distinct()
                    ->count('event_id');

                $rate = $totalEligibleOpportunities > 0
                    ? round(($verifiedCount / $totalEligibleOpportunities) * 100, 1)
                    : 0.0;

                return (object) [
                    'group_name' => $key,
                    'member_count' => $memberCount,
                    'verified_attendance' => $verifiedCount,
                    'eligible_opportunities' => $totalEligibleOpportunities,
                    'attendance_rate' => $rate,
                ];
            })
            ->sortByDesc('attendance_rate')
            ->values();
        }

        if ($group === 'year_levels') {
            $yearLevels = \App\Models\Student::YEAR_LEVELS;
            return collect($yearLevels)->map(function ($year) use ($eligibleEvents) {
                $students = \App\Models\Student::where('year_level', $year)->get();
                $memberCount = $students->count();
                $studentIds = $students->pluck('id')->toArray();
                $totalOpportunities = $memberCount * $eligibleEvents->count();

                $verifiedCount = \App\Models\AttendanceRecord::whereIn('event_id', $eligibleEvents->pluck('id'))
                    ->whereIn('participant_id', $studentIds)
                    ->count();

                $rate = $totalOpportunities > 0
                    ? round(($verifiedCount / $totalOpportunities) * 100, 1)
                    : 0.0;

                return (object) [
                    'group_name' => $year,
                    'member_count' => $memberCount,
                    'verified_attendance' => $verifiedCount,
                    'eligible_opportunities' => $totalOpportunities,
                    'attendance_rate' => $rate,
                ];
            })
            ->sortByDesc('attendance_rate')
            ->values();
        }

        if ($group === 'programs') {
            $programs = ['BSIT', 'BSCS', 'BSIS', 'BTVTEd'];
            return collect($programs)->map(function ($prog) use ($eligibleEvents) {
                $students = \App\Models\Student::where(function ($q) use ($prog) {
                    $q->where('program', $prog)
                      ->orWhere('program', 'like', "%{$prog}%");
                })->get();

                $memberCount = $students->count();
                $studentIds = $students->pluck('id')->toArray();
                $totalOpportunities = $memberCount * $eligibleEvents->count();

                $verifiedCount = \App\Models\AttendanceRecord::whereIn('event_id', $eligibleEvents->pluck('id'))
                    ->whereIn('participant_id', $studentIds)
                    ->count();

                $rate = $totalOpportunities > 0
                    ? round(($verifiedCount / $totalOpportunities) * 100, 1)
                    : 0.0;

                return (object) [
                    'group_name' => $prog,
                    'member_count' => $memberCount,
                    'verified_attendance' => $verifiedCount,
                    'eligible_opportunities' => $totalOpportunities,
                    'attendance_rate' => $rate,
                ];
            })
            ->sortByDesc('attendance_rate')
            ->values();
        }

        return collect();
    }
}
