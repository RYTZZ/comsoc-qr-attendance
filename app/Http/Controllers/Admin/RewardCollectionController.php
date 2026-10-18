<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RewardRedemption;
use App\Services\MarketplaceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RewardCollectionController extends Controller
{
    public function __construct(
        private MarketplaceService $marketplaceService
    ) {}

    /**
     * Dedicated reward collection workspace for authorized collection staff.
     */
    public function index(Request $request): View
    {
        $staff = $request->user();

        if (!$staff->canCollectRewards()) {
            abort(403, 'Unauthorized: You do not have permission to verify or collect rewards.');
        }

        $search = $request->input('search');
        $statusFilter = $request->input('status', 'pending_collection');

        $redemptionsQuery = RewardRedemption::with(['user', 'reward', 'collector', 'allocations.credit'])
            ->orderBy('created_at', 'desc');

        if ($statusFilter && $statusFilter !== 'all') {
            $redemptionsQuery->where('status', $statusFilter);
        }

        if ($search) {
            $redemptionsQuery->where(function ($q) use ($search) {
                $q->where('claim_code', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($u) use ($search) {
                      $u->where('name', 'like', "%{$search}%")
                        ->orWhere('student_number', 'like', "%{$search}%");
                  });
            });
        }

        $redemptions = $redemptionsQuery->paginate(15)->withQueryString();

        $counts = [
            'pending' => RewardRedemption::where('status', 'pending_collection')->count(),
            'claimed' => RewardRedemption::where('status', 'claimed')->count(),
            'refunded' => RewardRedemption::whereIn('status', ['refunded', 'cancelled'])->count(),
        ];

        return view('admin.rewards.collection', [
            'redemptions' => $redemptions,
            'counts' => $counts,
            'statusFilter' => $statusFilter,
            'search' => $search,
        ]);
    }

    /**
     * Verify single-use claim code and mark as physically claimed.
     */
    public function verify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'claim_code' => ['required', 'string', 'min:5', 'max:32'],
        ]);

        $result = $this->marketplaceService->recordCollection($validated['claim_code'], $request->user());

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Cancel and refund redemption before physical collection.
     */
    public function cancel(Request $request, RewardRedemption $redemption): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:255'],
        ]);

        $result = $this->marketplaceService->cancelRedemption($redemption, $validated['reason'], $request->user());

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        return back()->with('success', $result['message']);
    }
}
