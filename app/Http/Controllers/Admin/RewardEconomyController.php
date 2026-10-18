<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Level;
use App\Models\RewardRule;
use App\Models\Season;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\RewardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RewardEconomyController extends Controller
{
    public function __construct(private RewardService $rewardService) {}

    public function index(): View
    {
        $rules = RewardRule::orderBy('activity_type')->get();
        $levels = Level::orderBy('level')->get();
        $seasons = Season::orderByDesc('start_date')->get();
        $activeSeason = Season::active();

        $recentXpAdjustments = \App\Models\XpTransaction::with(['user', 'creator'])
            ->where('activity_type', 'manual_adjustment')
            ->orderByDesc('created_at')
            ->take(10)
            ->get();

        return view('admin.economy.index', compact(
            'rules',
            'levels',
            'seasons',
            'activeSeason',
            'recentXpAdjustments'
        ));
    }

    public function storeRule(Request $request): RedirectResponse
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized: Only Super Administrators can create reward rules.');
        }

        $validated = $request->validate([
            'activity_type' => ['required', 'string', 'max:100', 'unique:reward_rules,activity_type'],
            'name' => ['required', 'string', 'max:255'],
            'xp_amount' => ['required', 'integer', 'min:0'],
            'points_amount' => ['required', 'integer', 'min:0'],
        ]);

        $rule = RewardRule::create($validated);
        AuditLogger::log('reward_rule.created', $rule, [], $validated);

        return back()->with('success', "Reward rule '{$rule->name}' created.");
    }

    public function updateRule(Request $request, RewardRule $rule): RedirectResponse
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized: Only Super Administrators can modify reward rules.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'xp_amount' => ['required', 'integer', 'min:0'],
            'points_amount' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $rule->update($validated);
        AuditLogger::log('reward_rule.updated', $rule, [], $validated);

        return back()->with('success', "Reward rule '{$rule->name}' updated.");
    }

    public function storeLevel(Request $request): RedirectResponse
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized: Only Super Administrators can create levels.');
        }

        $validated = $request->validate([
            'level' => ['required', 'integer', 'min:1', 'unique:levels,level'],
            'title' => ['required', 'string', 'max:255'],
            'min_xp' => ['required', 'integer', 'min:0'],
            'badge_icon' => ['nullable', 'string', 'max:100'],
        ]);

        $level = Level::create($validated);
        AuditLogger::log('level.created', $level, [], $validated);

        return back()->with('success', "Level {$level->level} ({$level->title}) created.");
    }

    public function updateLevel(Request $request, Level $level): RedirectResponse
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized: Only Super Administrators can update levels.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'min_xp' => ['required', 'integer', 'min:0'],
            'badge_icon' => ['nullable', 'string', 'max:100'],
        ]);

        $level->update($validated);
        AuditLogger::log('level.updated', $level, [], $validated);

        return back()->with('success', "Level {$level->level} updated.");
    }

    public function adjustMemberBalance(Request $request): RedirectResponse
    {
        $actor = auth()->user();
        if (!$actor->isSuperAdmin() && !$actor->isAdmin()) {
            abort(403, 'Unauthorized: Only Super Administrators and authorized administrators can perform manual economy adjustments.');
        }

        // Support legacy parameter names if submitted
        if ($request->has('xp_adjustment') && !$request->has('lifetime_delta')) {
            $request->merge(['lifetime_delta' => $request->input('xp_adjustment')]);
        }
        if ($request->has('points_adjustment') && !$request->has('spendable_delta')) {
            $request->merge(['spendable_delta' => $request->input('points_adjustment')]);
        }

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'spendable_delta' => ['required', 'integer'],
            'lifetime_delta' => ['required', 'integer'],
            'reason' => ['required', 'string', 'min:5', 'max:255'],
            'second_approver_id' => ['nullable', 'exists:users,id'],
        ]);

        $targetUser = User::findOrFail($validated['user_id']);

        if ($targetUser->id === $actor->id) {
            return back()->with('error', 'Conflict of interest: You cannot adjust your own XP balance.');
        }

        $secondApprover = null;
        if (!empty($validated['second_approver_id'])) {
            $secondApprover = User::find($validated['second_approver_id']);
            if ($secondApprover->id === $actor->id) {
                return back()->with('error', 'Second approver must be a distinct authorized administrator.');
            }
        }

        /** @var \App\Services\ExpLedgerService $expLedgerService */
        $expLedgerService = app(\App\Services\ExpLedgerService::class);

        $result = $expLedgerService->createAdjustment(
            targetUser: $targetUser,
            spendableDelta: (int)$validated['spendable_delta'],
            lifetimeDelta: (int)$validated['lifetime_delta'],
            reason: $validated['reason'],
            actor: $actor,
            secondApprover: $secondApprover
        );

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        $spendableStr = ($validated['spendable_delta'] >= 0 ? '+' : '') . $validated['spendable_delta'] . ' Spendable EXP';
        $lifetimeStr = ($validated['lifetime_delta'] >= 0 ? '+' : '') . $validated['lifetime_delta'] . ' Lifetime EXP';

        \App\Services\NotificationService::send(
            $targetUser,
            'balance_adjusted',
            'EXP Balance Adjusted (' . $spendableStr . ')',
            'An officer adjusted your balance: ' . $spendableStr . ', ' . $lifetimeStr . '. Reason: ' . $validated['reason'],
            route('ledger.index')
        );

        return back()->with('success', "EXP balances successfully adjusted for {$targetUser->name}.");
    }
}
