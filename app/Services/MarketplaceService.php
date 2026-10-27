<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\ExpAllocation;
use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\ExpLedgerService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MarketplaceService
{
    public function __construct(
        private ExpLedgerService $expLedgerService
    ) {}

    /**
     * Atomically process member reward redemption:
     * 1. Validate account eligibility and active status
     * 2. Lock reward row to prevent overselling on simultaneous requests
     * 3. Validate spendable EXP balance
     * 4. Deduct EXP atomically using FEFO (First Expiring, First Out)
     * 5. Decrement stock
     * 6. Generate unique, hard-to-guess single-use claim code (e.g. COMSOC-XXXX-XXXX)
     * 7. Create redemption in 'pending_collection' status
     * 8. Dispatch confirmation notification
     */
    public function redeemReward(User $user, Reward $reward, ?string $notes = null): array
    {
        // 1. Account eligibility check
        $isActive = $user->is_active ?? true;
        $isEligible = $user->is_eligible ?? true;
        if (!$isActive || !$isEligible) {
            return [
                'success' => false,
                'message' => 'Your account is currently inactive or ineligible for reward redemption.',
            ];
        }

        // 1b. Enforce COMSOC Membership and Fee Payment Requirements
        // Exclude system administrators (e.g. super_admin) if not student-linked
        if ($user->student || in_array($user->role, ['student', 'member'])) {
            $student = $user->student;
            $hasMembershipsInSystem = \App\Models\Membership::count() > 0;

            if ($student) {
                // Find current active membership record
                $activeYear = \App\Models\AcademicYear::active();
                $membershipQuery = \App\Models\Membership::where('student_id', $student->id);
                if ($activeYear) {
                    $membershipQuery->where('academic_year_id', $activeYear->id);
                }
                $membership = $membershipQuery->latest()->first();

                if (!$membership || $membership->status !== 'active') {
                    return [
                        'success' => false,
                        'message' => 'Membership approval required.',
                    ];
                }

                $requiredFee = (float) config('app.membership_fee', env('MEMBERSHIP_FEE', 100));
                $feePaid = (float) ($membership->fee_paid ?? 0);
                if ($feePaid < $requiredFee || empty($membership->paid_at)) {
                    return [
                        'success' => false,
                        'message' => 'Required membership fee not yet verified.',
                    ];
                }
            } elseif ($hasMembershipsInSystem) {
                return [
                    'success' => false,
                    'message' => 'Membership approval required. No student record linked to account.',
                ];
            }
        }

        return DB::transaction(function () use ($user, $reward, $notes) {
            // 2. Lock reward record for pessimistic concurrency safety
            /** @var Reward $lockedReward */
            $lockedReward = Reward::where('id', $reward->id)->lockForUpdate()->first();

            if (!$lockedReward || !$lockedReward->isPublished()) {
                return [
                    'success' => false,
                    'message' => 'This reward item is currently in draft or unavailable for redemption.',
                ];
            }

            if ($lockedReward->starts_at && $lockedReward->starts_at->isFuture()) {
                return [
                    'success' => false,
                    'message' => 'This reward item is not yet available for redemption.',
                ];
            }

            if ($lockedReward->ends_at && $lockedReward->ends_at->isPast()) {
                return [
                    'success' => false,
                    'message' => 'The redemption period for this reward has expired.',
                ];
            }

            // Check stock
            if ($lockedReward->stock !== null && $lockedReward->stock <= 0) {
                return [
                    'success' => false,
                    'message' => 'Item is currently out of stock. Please check back later.',
                ];
            }

            // Check per-member redemption limit
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

            // Check level requirement (driven by Lifetime EXP)
            $userLifetime = $user->getLifetimeExp();
            $userLevel = \App\Models\Level::forXp($userLifetime)->level;
            if ($lockedReward->min_level > $userLevel) {
                return [
                    'success' => false,
                    'message' => "Level {$lockedReward->min_level} required (Current: Level {$userLevel}).",
                ];
            }

            // 3. Check Spendable EXP balance
            $spendableExp = $user->getSpendableExp();
            $expPrice = $lockedReward->points_cost;

            if ($spendableExp < $expPrice) {
                return [
                    'success' => false,
                    'message' => "Insufficient Spendable EXP. Required: {$expPrice} EXP, Available: {$spendableExp} EXP.",
                ];
            }

            // 5. Generate unique cryptographically-random claim code
            $claimCode = 'CMS-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4));

            // 6. Create redemption record
            $redemption = RewardRedemption::create([
                'user_id' => $user->id,
                'reward_id' => $lockedReward->id,
                'points_spent' => $expPrice,
                'claim_code' => $claimCode,
                'item_title_snapshot' => $lockedReward->title,
                'status' => 'pending_collection',
                'notes' => $notes,
            ]);

            // 4. Atomically allocate FEFO credits
            $this->expLedgerService->allocateFefo($user, $expPrice, $redemption->id);

            // Reserve stock
            if ($lockedReward->stock !== null) {
                $lockedReward->decrement('stock', 1);
            }

            // Record legacy PointTransaction if legacy point tables are queried
            \App\Models\PointTransaction::create([
                'user_id' => $user->id,
                'amount' => -$expPrice,
                'transaction_type' => 'reward_redemption',
                'description' => "Redeemed: {$lockedReward->title}",
                'reference_id' => $redemption->id,
            ]);

            AuditLogger::log('reward.redeemed', $redemption, [], [
                'reward' => $lockedReward->title,
                'exp_spent' => $expPrice,
                'claim_code' => $claimCode,
                'user' => $user->name,
            ]);

            // 7. Dispatch confirmation notification
            NotificationService::send(
                $user,
                'redemption_confirmed',
                'Reward Claim Code: ' . $claimCode,
                "Your redemption for {$lockedReward->title} is confirmed. Claim Code: {$claimCode}. Present this code to authorized collection staff.",
                route('shop.show', $lockedReward)
            );

            // Send transactional email (wrapped so mail failure does not roll back transaction)
            try {
                app(\App\Services\OrganizationMailService::class)->sendRedemptionConfirmation($user, $redemption);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("Email dispatch skipped: " . $e->getMessage());
            }

            return [
                'success' => true,
                'redemption' => $redemption,
                'claim_code' => $claimCode,
                'message' => "Redemption successful! Your claim code is {$claimCode}.",
            ];
        });
    }

    /**
     * Verify single-use claim code and record physical collection.
     * Permission: Super Admin, COMSOC Admin, or explicitly authorized collection staff.
     */
    public function recordCollection(string $claimCode, User $staff): array
    {
        if (!$staff->canCollectRewards()) {
            return [
                'success' => false,
                'message' => 'Unauthorized: You do not have permission to verify or collect rewards.',
            ];
        }

        return DB::transaction(function () use ($claimCode, $staff) {
            $redemption = RewardRedemption::where('claim_code', strtoupper(trim($claimCode)))
                ->lockForUpdate()
                ->first();

            if (!$redemption) {
                return [
                    'success' => false,
                    'message' => 'Invalid claim code: No corresponding redemption found.',
                ];
            }

            if ($redemption->status === 'claimed') {
                $collectedAt = $redemption->collected_at ? $redemption->collected_at->format('M d, Y g:i A') : 'previously';
                return [
                    'success' => false,
                    'message' => "Claim code already used: This item was collected at {$collectedAt}.",
                ];
            }

            if ($redemption->status === 'cancelled' || $redemption->status === 'refunded') {
                return [
                    'success' => false,
                    'message' => "This redemption was previously {$redemption->status} and cannot be collected.",
                ];
            }

            // Mark redemption as Claimed
            $redemption->update([
                'status' => 'claimed',
                'collected_at' => now(),
                'collected_by' => $staff->id,
                'fulfilled_at' => now(),
                'fulfilled_by' => $staff->id,
            ]);

            AuditLogger::log('reward.collected', $redemption, [
                'status' => 'pending_collection',
            ], [
                'status' => 'claimed',
                'claim_code' => $claimCode,
                'collected_by' => $staff->name,
                'recipient' => $redemption->user->name,
            ]);

            NotificationService::send(
                $redemption->user,
                'reward_collected',
                'Reward Collection Confirmed',
                "Your {$redemption->item_title_snapshot} has been marked as collected by {$staff->name}.",
                route('shop.index')
            );

            // Send transactional email (wrapped so mail failure does not roll back transaction)
            try {
                app(\App\Services\OrganizationMailService::class)->sendCollectionConfirmation($redemption->user, $redemption, $staff);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("Collection email dispatch skipped: " . $e->getMessage());
            }

            return [
                'success' => true,
                'redemption' => $redemption,
                'message' => "Reward {$redemption->item_title_snapshot} collected successfully for {$redemption->user->name}!",
            ];
        });
    }

    /**
     * Cancel and refund redemption before physical collection.
     */
    public function cancelRedemption(RewardRedemption $redemption, string $reason, User $actor): array
    {
        if ($redemption->status === 'claimed') {
            return [
                'success' => false,
                'message' => 'Cancellation disallowed: Item has already been physically collected.',
            ];
        }

        if ($redemption->status === 'cancelled' || $redemption->status === 'refunded') {
            return [
                'success' => false,
                'message' => 'This redemption is already cancelled.',
            ];
        }

        return DB::transaction(function () use ($redemption, $reason, $actor) {
            $lockedRedemption = RewardRedemption::where('id', $redemption->id)->lockForUpdate()->first();

            // 1. Restore allocated FEFO credits
            $this->expLedgerService->restoreAllocatedCredits($lockedRedemption->id, $actor, $reason);

            // 2. Restore stock
            $reward = Reward::where('id', $lockedRedemption->reward_id)->lockForUpdate()->first();
            if ($reward && $reward->stock !== null) {
                $reward->increment('stock', 1);
            }

            // 3. Mark redemption refunded
            $lockedRedemption->update([
                'status' => 'refunded',
                'notes' => ($lockedRedemption->notes ? $lockedRedemption->notes . "\n" : '') . "Refunded: {$reason}",
            ]);

            AuditLogger::log('reward.refunded', $lockedRedemption, [
                'status' => 'pending_collection',
            ], [
                'status' => 'refunded',
                'reason' => $reason,
                'actor' => $actor->name,
            ]);

            return [
                'success' => true,
                'message' => 'Redemption cancelled and EXP credits restored to member balance.',
            ];
        });
    }
}
