<?php

namespace App\Http\Controllers;

use App\Models\MemberSeasonStat;
use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Models\Season;
use App\Services\RewardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberShopController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $spendableExp = $user->getSpendableExp();
        $lifetimeExp = $user->getLifetimeExp();
        $currentLevel = \App\Models\Level::forXp($lifetimeExp)->level;

        $rawCategory = $request->input('category');
        $selectedCategory = ($rawCategory !== null && trim((string)$rawCategory) !== '' && trim((string)$rawCategory) !== 'all')
            ? trim((string)$rawCategory)
            : 'all';
        $search = $request->input('search');
        $statusTab = $request->input('tab', 'shop');

        $rewardsQuery = Reward::published();

        if ($selectedCategory !== 'all') {
            $rewardsQuery->where(function ($q) use ($selectedCategory) {
                $q->where('category', $selectedCategory)
                  ->orWhereRaw('LOWER(TRIM(category)) = ?', [strtolower(trim($selectedCategory))]);
            });
        }

        if ($search) {
            $rewardsQuery->where(function ($q) use ($search) {
                $q->whereLike('title', "%{$search}%")
                    ->orWhereLike('description', "%{$search}%");
            });
        }

        $rewards = $rewardsQuery->orderBy('points_cost', 'asc')->get();

        $myRedemptions = RewardRedemption::with('reward')
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(15);

        return view('shop.index', [
            'rewards' => $rewards,
            'myRedemptions' => $myRedemptions,
            'spendableExp' => $spendableExp,
            'currentPoints' => $spendableExp, // legacy alias for view compatibility
            'lifetimeExp' => $lifetimeExp,
            'currentLevel' => $currentLevel,
            'selectedCategory' => $selectedCategory,
            'search' => $search,
            'statusTab' => $statusTab,
            'categories' => Reward::CATEGORIES,
        ]);
    }

    public function show(Request $request, Reward $reward): View
    {
        if (!$reward->isPublished()) {
            abort(404, 'Reward item not available or published.');
        }

        $user = $request->user();

        $spendableExp = $user->getSpendableExp();
        $lifetimeExp = $user->getLifetimeExp();
        $currentLevel = \App\Models\Level::forXp($lifetimeExp)->level;

        $isEligibleLevel = $currentLevel >= $reward->min_level;
        $hasEnoughPoints = $spendableExp >= $reward->points_cost;
        $hasStock = $reward->stock === null || $reward->stock > 0;

        $userRedemptionsCount = RewardRedemption::where('user_id', $user->id)
            ->where('reward_id', $reward->id)
            ->count();

        return view('shop.show', [
            'reward' => $reward,
            'spendableExp' => $spendableExp,
            'currentPoints' => $spendableExp,
            'currentLevel' => $currentLevel,
            'isEligibleLevel' => $isEligibleLevel,
            'hasEnoughPoints' => $hasEnoughPoints,
            'hasStock' => $hasStock,
            'userRedemptionsCount' => $userRedemptionsCount,
        ]);
    }

    public function redeem(Request $request, Reward $reward, \App\Services\MarketplaceService $marketplaceService): RedirectResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $result = $marketplaceService->redeemReward(
            user: $request->user(),
            reward: $reward,
            notes: $validated['notes'] ?? null
        );

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        return redirect()->route('shop.show', $reward)->with('success', "Reward redeemed successfully! Your Claim Code is {$result['claim_code']}. Present this code to claim your item.");
    }
}
