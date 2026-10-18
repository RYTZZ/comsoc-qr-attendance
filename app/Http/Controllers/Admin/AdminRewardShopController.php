<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Services\RewardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AdminRewardShopController extends Controller
{
    public function index(Request $request): View
    {
        $statusFilter = $request->input('status', 'all');
        $tab = $request->input('tab', 'requests');
        $eventFilter = $request->input('event_id');
        $catalogStatusFilter = $request->input('catalog_status', 'all');
        $categoryFilter = $request->input('category', 'all');
        $search = $request->input('search');

        $redemptionsQuery = RewardRedemption::with(['user.student', 'reward', 'fulfiller']);

        if ($statusFilter && $statusFilter !== 'all') {
            $redemptionsQuery->where('status', $statusFilter);
        }

        if ($search && $tab === 'requests') {
            $redemptionsQuery->where(function ($q) use ($search) {
                $q->where('claim_code', 'like', "%{$search}%")
                    ->orWhere('item_title_snapshot', 'like', "%{$search}%")
                    ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$search}%"));
            });
        }

        $redemptions = $redemptionsQuery->latest()->paginate(15, ['*'], 'requests_page')->withQueryString();

        $rewardsQuery = Reward::with(['event'])->withCount('redemptions');
        if ($eventFilter) {
            $rewardsQuery->where('event_id', $eventFilter);
        }

        if ($catalogStatusFilter && $catalogStatusFilter !== 'all') {
            $rewardsQuery->where('status', $catalogStatusFilter);
        }

        if ($categoryFilter && $categoryFilter !== 'all') {
            $rewardsQuery->where('category', $categoryFilter);
        }

        if ($search && $tab !== 'requests') {
            $rewardsQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $rewards = $rewardsQuery->orderBy('created_at', 'desc')
            ->paginate(15, ['*'], 'catalog_page')->withQueryString();

        $pendingCount = RewardRedemption::whereIn('status', ['pending', 'pending_collection'])->count();
        $events = \App\Models\Event::orderByDesc('event_date')->get();

        // Economy Overview Real Metrics
        $summary = [
            'total_rewards' => Reward::count(),
            'published_rewards' => Reward::where('status', 'published')->where('is_active', true)->where('is_archived', false)->count(),
            'draft_rewards' => Reward::where('status', 'draft')->count(),
            'out_of_stock' => Reward::whereNotNull('stock')->where('stock', '<=', 0)->count(),
            'total_redemptions' => RewardRedemption::count(),
            'pending_claims' => $pendingCount,
            'fulfilled_claims' => RewardRedemption::whereIn('status', ['fulfilled', 'claimed'])->count(),
        ];

        return view('admin.shop.index', [
            'redemptions' => $redemptions,
            'rewards' => $rewards,
            'statusFilter' => $statusFilter,
            'catalogStatusFilter' => $catalogStatusFilter,
            'categoryFilter' => $categoryFilter,
            'search' => $search,
            'eventFilter' => $eventFilter,
            'tab' => $tab,
            'pendingCount' => $pendingCount,
            'categories' => Reward::CATEGORIES,
            'types' => Reward::TYPES,
            'statuses' => Reward::STATUSES,
            'events' => $events,
            'summary' => $summary,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'reward_type' => ['nullable', 'string', 'in:' . implode(',', array_keys(Reward::TYPES))],
            'event_id' => ['nullable', 'exists:events,id'],
            'points_cost' => ['required', 'integer', 'min:0'],
            'min_level' => ['required', 'integer', 'min:1', 'max:100'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:2000'],
            'eligibility_notes' => ['nullable', 'string', 'max:1000'],
            'fulfillment_instructions' => ['nullable', 'string', 'max:1000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'redemption_limit_per_member' => ['nullable', 'integer', 'min:1'],
            'image_url' => ['nullable', 'url', 'max:500'],
            'image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'action' => ['nullable', 'string', 'in:publish,draft'],
            'status' => ['nullable', 'string', 'in:draft,published,archived'],
        ]);

        $imagePath = null;
        if ($request->hasFile('image_file')) {
            $imagePath = $request->file('image_file')->store('reward-images', 'public');
        }

        $stock = $validated['stock'] !== null && $validated['stock'] !== '' ? (int) $validated['stock'] : null;

        $targetStatus = $request->input('action') === 'draft' ? 'draft' : ($validated['status'] ?? 'published');
        $isActive = ($targetStatus === 'published');

        $reward = Reward::create([
            'title' => $validated['title'],
            'category' => $validated['category'],
            'reward_type' => $validated['reward_type'] ?? 'physical',
            'event_id' => $validated['event_id'] ?? null,
            'points_cost' => $validated['points_cost'],
            'min_level' => $validated['min_level'],
            'stock' => $stock,
            'initial_quantity' => $stock,
            'description' => $validated['description'] ?? null,
            'eligibility_notes' => $validated['eligibility_notes'] ?? null,
            'fulfillment_instructions' => $validated['fulfillment_instructions'] ?? null,
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'redemption_limit_per_member' => $validated['redemption_limit_per_member'] ?? null,
            'image_url' => $validated['image_url'] ?? null,
            'image_path' => $imagePath,
            'is_active' => $isActive,
            'is_archived' => ($targetStatus === 'archived'),
            'status' => $targetStatus,
            'published_at' => $isActive ? now() : null,
        ]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'created_reward_item',
            'details' => json_encode([
                'reward_id' => $reward->id,
                'title' => $reward->title,
                'status' => $reward->status,
                'points_cost' => $reward->points_cost,
                'event_id' => $reward->event_id,
            ]),
        ]);

        $msg = $targetStatus === 'draft'
            ? "Reward '{$reward->title}' saved as draft (hidden from students)."
            : "Reward '{$reward->title}' created and published to catalog.";

        return redirect()->route('admin.shop.index', ['tab' => 'catalog'])->with('success', $msg);
    }

    public function update(Request $request, Reward $reward): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'reward_type' => ['nullable', 'string', 'in:' . implode(',', array_keys(Reward::TYPES))],
            'event_id' => ['nullable', 'exists:events,id'],
            'points_cost' => ['required', 'integer', 'min:0'],
            'min_level' => ['required', 'integer', 'min:1', 'max:100'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:2000'],
            'eligibility_notes' => ['nullable', 'string', 'max:1000'],
            'fulfillment_instructions' => ['nullable', 'string', 'max:1000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'redemption_limit_per_member' => ['nullable', 'integer', 'min:1'],
            'image_url' => ['nullable', 'url', 'max:500'],
            'image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'status' => ['nullable', 'string', 'in:draft,published,archived'],
        ]);

        if ($request->hasFile('image_file')) {
            if ($reward->image_path && Storage::disk('public')->exists($reward->image_path)) {
                Storage::disk('public')->delete($reward->image_path);
            }
            $reward->image_path = $request->file('image_file')->store('reward-images', 'public');
        }

        $stock = $validated['stock'] !== null && $validated['stock'] !== '' ? (int) $validated['stock'] : null;

        $targetStatus = $validated['status'] ?? $reward->status ?? 'published';

        $reward->title = $validated['title'];
        $reward->category = $validated['category'];
        if (isset($validated['reward_type'])) {
            $reward->reward_type = $validated['reward_type'];
        }
        $reward->event_id = $validated['event_id'] ?? null;
        $reward->points_cost = $validated['points_cost'];
        $reward->min_level = $validated['min_level'];
        $reward->stock = $stock;
        if ($reward->initial_quantity === null && $stock !== null) {
            $reward->initial_quantity = $stock;
        }
        $reward->description = $validated['description'] ?? null;
        $reward->eligibility_notes = $validated['eligibility_notes'] ?? null;
        $reward->fulfillment_instructions = $validated['fulfillment_instructions'] ?? null;
        $reward->starts_at = $validated['starts_at'] ?? null;
        $reward->ends_at = $validated['ends_at'] ?? null;
        $reward->redemption_limit_per_member = $validated['redemption_limit_per_member'] ?? null;

        if ($request->filled('image_url')) {
            $reward->image_url = $validated['image_url'];
        }

        $reward->status = $targetStatus;
        $reward->is_active = ($targetStatus === 'published');
        $reward->is_archived = ($targetStatus === 'archived');
        if ($targetStatus === 'published' && !$reward->published_at) {
            $reward->published_at = now();
        }

        $reward->save();

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'updated_reward_item',
            'details' => json_encode([
                'reward_id' => $reward->id,
                'title' => $reward->title,
                'status' => $reward->status,
                'event_id' => $reward->event_id,
            ]),
        ]);

        return redirect()->route('admin.shop.index', ['tab' => 'catalog'])->with('success', "Reward item '{$reward->title}' updated successfully.");
    }

    public function fulfill(Request $request, RewardRedemption $redemption): RedirectResponse
    {
        if ($redemption->user_id === $request->user()->id && !$request->user()->isSuperAdmin()) {
            return back()->with('error', 'Conflict of interest: You cannot fulfill your own reward redemption.');
        }

        $redemption->update([
            'status' => 'fulfilled',
            'fulfilled_at' => now(),
            'fulfilled_by' => $request->user()->id,
        ]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'fulfilled_redemption_request',
            'details' => json_encode([
                'redemption_id' => $redemption->id,
                'user_id' => $redemption->user_id,
                'reward_title' => $redemption->item_title_snapshot,
            ]),
        ]);

        \App\Services\NotificationService::send(
            $redemption->user_id,
            'redemption_fulfilled',
            'Reward Claim Ready: ' . $redemption->item_title_snapshot,
            'Your redemption for ' . $redemption->item_title_snapshot . ' has been fulfilled! Claim code: ' . $redemption->claim_code,
            route('shop.index', ['tab' => 'claims'])
        );

        return redirect()->route('admin.shop.index', ['tab' => 'requests'])->with('success', 'Redemption marked as fulfilled.');
    }

    public function reject(Request $request, RewardRedemption $redemption, RewardService $rewardService): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $result = $rewardService->refundRedemption(
            redemption: $redemption,
            reason: $validated['rejection_reason'],
            admin: $request->user()
        );

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'rejected_and_refunded_redemption',
            'details' => json_encode([
                'redemption_id' => $redemption->id,
                'reason' => $validated['rejection_reason'],
            ]),
        ]);

        \App\Services\NotificationService::send(
            $redemption->user_id,
            'redemption_rejected',
            'Redemption Refunded: ' . $redemption->item_title_snapshot,
            'Your redemption for ' . $redemption->item_title_snapshot . ' was cancelled and ' . $redemption->points_spent . ' points were refunded. Reason: ' . $validated['rejection_reason'],
            route('shop.index', ['tab' => 'claims'])
        );

        return redirect()->route('admin.shop.index', ['tab' => 'requests'])->with('success', 'Redemption rejected and points refunded to member.');
    }

    public function toggleActive(Reward $reward): RedirectResponse
    {
        $reward->update(['is_active' => !$reward->is_active]);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'toggled_reward_active_status',
            'details' => json_encode([
                'reward_id' => $reward->id,
                'title' => $reward->title,
                'is_active' => $reward->is_active,
            ]),
        ]);

        $status = $reward->is_active ? 'published' : 'unpublished';
        return back()->with('success', "Reward item '{$reward->title}' has been {$status}.");
    }

    public function publish(Reward $reward): RedirectResponse
    {
        $reward->update([
            'status' => 'published',
            'is_active' => true,
            'is_archived' => false,
            'published_at' => $reward->published_at ?? now(),
        ]);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'published_reward_item',
            'details' => json_encode([
                'reward_id' => $reward->id,
                'title' => $reward->title,
            ]),
        ]);

        return back()->with('success', "Reward item '{$reward->title}' is now published and available to eligible members.");
    }

    public function archive(Reward $reward): RedirectResponse
    {
        $reward->update([
            'status' => 'archived',
            'is_active' => false,
            'is_archived' => true,
        ]);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'archived_reward_item',
            'details' => json_encode([
                'reward_id' => $reward->id,
                'title' => $reward->title,
            ]),
        ]);

        return back()->with('success', "Reward item '{$reward->title}' has been archived.");
    }

    public function destroy(Request $request, Reward $reward): RedirectResponse
    {
        if (!$request->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized: Only Super Administrators can delete rewards.');
        }

        // Safe deletion check: prevent deletion if redemptions exist
        if ($reward->redemptions()->exists()) {
            return back()->with('error', "Cannot delete reward '{$reward->title}' because members have active or historical redemptions for it. Please archive it instead.");
        }

        if ($reward->image_path && Storage::disk('public')->exists($reward->image_path)) {
            Storage::disk('public')->delete($reward->image_path);
        }

        $title = $reward->title;
        $rewardId = $reward->id;
        $reward->delete();

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'deleted_reward_item',
            'details' => json_encode([
                'reward_id' => $rewardId,
                'title' => $title,
            ]),
        ]);

        return redirect()->route('admin.shop.index', ['tab' => 'catalog'])->with('success', "Reward '{$title}' has been permanently deleted.");
    }
}
