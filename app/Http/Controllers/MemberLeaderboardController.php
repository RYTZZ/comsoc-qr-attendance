<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\Season;
use App\Services\AchievementService;
use App\Services\LeaderboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberLeaderboardController extends Controller
{
    public function index(
        Request $request,
        LeaderboardService $leaderboardService,
        AchievementService $achievementService
    ): View {
        $user = $request->user();

        $seasons = Season::orderBy('start_date', 'desc')->get();
        $selectedSeasonId = $request->input('season_id');

        $activeSeason = Season::where('is_active', true)->first() ?: Season::latest('start_date')->first();
        $season = $selectedSeasonId
            ? ($seasons->firstWhere('id', $selectedSeasonId) ?: $activeSeason)
            : $activeSeason;

        $tab = $request->input('tab', 'semester'); // 'semester', 'lifetime', 'achievements'

        $leaderboard = null;
        $userRank = null;

        if ($tab === 'lifetime') {
            $leaderboard = $leaderboardService->getLifetimeLeaderboard(
                perPage: 25,
                page: (int) $request->input('page', 1)
            );
            $userRank = $leaderboardService->getUserRank(user: $user, type: 'lifetime');
        } else {
            // Default: Semester Leaderboard
            $leaderboard = $leaderboardService->getSemesterLeaderboard(
                season: $season,
                perPage: 25,
                page: (int) $request->input('page', 1)
            );
            $userRank = $leaderboardService->getUserRank(user: $user, type: 'semester', season: $season);
        }

        $achievementProgress = $achievementService->getMemberProgress($user);
        $unlockedAchievementsCount = collect($achievementProgress)->where('is_unlocked', true)->count();
        $totalAchievementsCount = count($achievementProgress);

        return view('leaderboards.index', [
            'seasons' => $seasons,
            'currentSeason' => $season,
            'selectedSeasonId' => $season?->id,
            'tab' => $tab,
            'leaderboard' => $leaderboard,
            'userRank' => $userRank,
            'achievementProgress' => $achievementProgress,
            'unlockedCount' => $unlockedAchievementsCount,
            'totalCount' => $totalAchievementsCount,
        ]);
    }
}
