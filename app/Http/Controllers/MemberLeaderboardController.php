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

        $rawTab = $request->input('tab', 'semester');
        // Support legacy 'achievements' query parameter by mapping to 'milestones' or 'badges'
        $tab = match ($rawTab) {
            'lifetime' => 'lifetime',
            'milestones' => 'milestones',
            'badges' => 'badges',
            'achievements' => 'milestones',
            default => 'semester',
        };

        $leaderboard = null;
        $userRank = null;

        if ($tab === 'lifetime') {
            $leaderboard = $leaderboardService->getLifetimeLeaderboard(
                perPage: 25,
                page: (int) $request->input('page', 1)
            );
            $userRank = $leaderboardService->getUserRank(user: $user, type: 'lifetime');
        } elseif ($tab === 'semester') {
            $leaderboard = $leaderboardService->getSemesterLeaderboard(
                season: $season,
                perPage: 25,
                page: (int) $request->input('page', 1)
            );
            $userRank = $leaderboardService->getUserRank(user: $user, type: 'semester', season: $season);
        }

        $allProgress = $achievementService->getMemberProgress($user);
        
        // Split progress into Milestones vs Badges
        $milestoneProgress = collect($allProgress)->filter(function ($item) {
            return ($item['achievement']->category ?? '') === 'Milestone';
        })->values()->all();

        $badgeProgress = collect($allProgress)->filter(function ($item) {
            return ($item['achievement']->category ?? '') !== 'Milestone';
        })->values()->all();

        // If no milestones are explicitly categorized, preserve all in milestones and badges
        if (empty($milestoneProgress) && !empty($allProgress)) {
            $milestoneProgress = $allProgress;
            $badgeProgress = $allProgress;
        }

        $unlockedAchievementsCount = collect($allProgress)->where('is_unlocked', true)->count();
        $totalAchievementsCount = count($allProgress);

        return view('leaderboards.index', [
            'seasons' => $seasons,
            'currentSeason' => $season,
            'selectedSeasonId' => $season?->id,
            'tab' => $tab,
            'leaderboard' => $leaderboard,
            'userRank' => $userRank,
            'achievementProgress' => $allProgress,
            'milestoneProgress' => $milestoneProgress,
            'badgeProgress' => $badgeProgress,
            'unlockedCount' => $unlockedAchievementsCount,
            'totalCount' => $totalAchievementsCount,
        ]);
    }
}
