<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\ExpAllocation;
use App\Models\ExpCredit;
use App\Models\Setting;
use App\Models\User;
use App\Models\XpTransaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ExpLedgerService
{
    /**
     * Credit EXP to a user with authoritative ledger transaction and credit record.
     * Sources: 'attendance', 'mission', 'task', 'contribution', 'adjustment'
     */
    public function creditExp(
        User $user,
        int $amount,
        string $sourceType,
        ?string $sourceId = null,
        string $description = '',
        ?int $expirationDays = null,
        ?string $idempotencyKey = null,
        ?User $actor = null
    ): array {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Credit EXP amount must be greater than zero.');
        }

        return DB::transaction(function () use (
            $user,
            $amount,
            $sourceType,
            $sourceId,
            $description,
            $expirationDays,
            $idempotencyKey,
            $actor
        ) {
            // Idempotency check: prevent duplicate awards
            if ($idempotencyKey) {
                $existingTx = XpTransaction::where('idempotency_key', $idempotencyKey)->first();
                if ($existingTx) {
                    return [
                        'awarded' => false,
                        'reason' => 'duplicate_award_prevented',
                        'amount' => 0,
                    ];
                }
            }

            // Expiration timestamp: null means non-expiring
            $expiresAt = $expirationDays ? now()->addDays($expirationDays) : null;

            // 1. Create atomic EXP Credit record
            $credit = ExpCredit::create([
                'user_id' => $user->id,
                'initial_amount' => $amount,
                'remaining_amount' => $amount,
                'expires_at' => $expiresAt,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'status' => 'active',
                'created_by' => $actor?->id,
            ]);

            // 2. Record authoritative ledger entry
            $transaction = XpTransaction::create([
                'user_id' => $user->id,
                'exp_credit_id' => $credit->id,
                'amount' => $amount,
                'spendable_delta' => $amount,
                'lifetime_delta' => $amount,
                'activity_type' => $sourceType,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'description' => $description ?: "EXP credited from {$sourceType}",
                'idempotency_key' => $idempotencyKey,
                'created_by' => $actor?->id,
            ]);

            return [
                'awarded' => true,
                'amount' => $amount,
                'credit' => $credit,
                'transaction' => $transaction,
                'spendable_exp' => $user->getSpendableExp(),
                'lifetime_exp' => $user->getLifetimeExp(),
            ];
        });
    }

    /**
     * Consume Spendable EXP using FEFO (First Expiring, First Out).
     * Rule: Consume valid credits with earliest expiration date first; non-expiring credits last.
     */
    public function allocateFefo(User $user, int $expRequired, string $redemptionId): array
    {
        if ($expRequired <= 0) {
            throw new InvalidArgumentException('Required EXP must be greater than zero.');
        }

        // Must run within an active database transaction with row locks
        $spendableAvailable = $user->getSpendableExp();
        if ($spendableAvailable < $expRequired) {
            throw new InvalidArgumentException("Insufficient Spendable EXP. Required: {$expRequired}, Available: {$spendableAvailable}");
        }

        // Fetch active, unexpired credits ordered by FEFO:
        // 1. Earliest expiration first (expires_at ASC, non-null)
        // 2. Non-expiring (expires_at IS NULL) afterward
        $credits = ExpCredit::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('remaining_amount', '>', 0)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->orderByRaw('CASE WHEN expires_at IS NULL THEN 1 ELSE 0 END ASC, expires_at ASC')
            ->lockForUpdate()
            ->get();

        $remainingToDeduct = $expRequired;
        $allocations = [];

        foreach ($credits as $credit) {
            if ($remainingToDeduct <= 0) break;

            $deductFromCredit = min($credit->remaining_amount, $remainingToDeduct);
            $credit->remaining_amount -= $deductFromCredit;
            if ($credit->remaining_amount === 0) {
                $credit->status = 'exhausted';
            }
            $credit->save();

            $allocation = ExpAllocation::create([
                'reward_redemption_id' => $redemptionId,
                'exp_credit_id' => $credit->id,
                'amount_allocated' => $deductFromCredit,
            ]);

            $allocations[] = $allocation;
            $remainingToDeduct -= $deductFromCredit;
        }

        if ($remainingToDeduct > 0) {
            $activeSeason = \App\Models\Season::active();
            if ($activeSeason) {
                $stat = $user->seasonStats()->where('season_id', $activeSeason->id)->first();
                if ($stat && $stat->redeemable_points >= $remainingToDeduct) {
                    $stat->decrement('redeemable_points', $remainingToDeduct);
                    $remainingToDeduct = 0;
                }
            }
        }

        if ($remainingToDeduct > 0) {
            throw new \RuntimeException('Allocation failed: Inconsistent available credit balance.');
        }

        // Record negative spendable ledger transaction (lifetime_delta = 0 on normal redemptions)
        XpTransaction::create([
            'user_id' => $user->id,
            'amount' => -$expRequired,
            'spendable_delta' => -$expRequired,
            'lifetime_delta' => 0,
            'activity_type' => 'reward_redemption',
            'source_type' => 'reward_redemption',
            'source_id' => $redemptionId,
            'description' => "Redeemed reward (FEFO allocated)",
        ]);

        return $allocations;
    }

    /**
     * Restore allocated EXP credits upon approved redemption cancellation / refund.
     */
    public function restoreAllocatedCredits(string $redemptionId, User $actor, string $reason = ''): void
    {
        $allocations = ExpAllocation::where('reward_redemption_id', $redemptionId)->get();
        $totalRestored = 0;

        foreach ($allocations as $allocation) {
            $credit = $allocation->credit;
            if ($credit) {
                $credit->remaining_amount += $allocation->amount_allocated;
                if ($credit->status === 'exhausted') {
                    $credit->status = 'active';
                }
                $credit->save();
                $totalRestored += $allocation->amount_allocated;
            }
        }

        if ($totalRestored > 0 && $allocations->isNotEmpty()) {
            $user = $allocations->first()->credit->user;
            XpTransaction::create([
                'user_id' => $user->id,
                'amount' => $totalRestored,
                'spendable_delta' => $totalRestored,
                'lifetime_delta' => 0,
                'activity_type' => 'redemption_refund',
                'source_type' => 'reward_redemption',
                'source_id' => $redemptionId,
                'description' => "Refunded reward redemption: {$reason}",
                'created_by' => $actor->id,
            ]);
        }
    }

    /**
     * Auditable reversal of an invalid award.
     * Rule: Never silently delete or rewrite invalid EXP awards.
     * If already redeemed: deduct remaining available spendable without allowing Spendable to become negative.
     * Lifetime EXP must never become negative.
     */
    public function reverseAward(ExpCredit $credit, string $reason, User $actor): array
    {
        return DB::transaction(function () use ($credit, $reason, $actor) {
            $user = $credit->user;
            $originalAmount = $credit->initial_amount;
            $alreadyRedeemed = $originalAmount - $credit->remaining_amount;

            // Spendable deduction: deduct whatever is left in this credit
            $spendableDeduction = $credit->remaining_amount;

            // Calculate Lifetime deduction: cannot exceed user's current Lifetime EXP (Lifetime EXP must never become negative)
            $currentLifetime = $user->getLifetimeExp();
            $lifetimeDeduction = min($originalAmount, max(0, $currentLifetime));

            // Mark credit reversed
            $credit->update([
                'status' => 'reversed',
                'remaining_amount' => 0,
            ]);

            // Create auditable reversal ledger transaction
            $reversalTx = XpTransaction::create([
                'user_id' => $user->id,
                'exp_credit_id' => $credit->id,
                'amount' => -$spendableDeduction,
                'spendable_delta' => -$spendableDeduction,
                'lifetime_delta' => -$lifetimeDeduction,
                'activity_type' => 'reversal',
                'is_reversal' => true,
                'reversal_reason' => $reason,
                'source_type' => $credit->source_type,
                'source_id' => $credit->source_id,
                'description' => "Reversal of invalid award ({$credit->source_type}): {$reason}" . ($alreadyRedeemed > 0 ? " [Discrepancy: {$alreadyRedeemed} EXP was already redeemed]" : ''),
                'created_by' => $actor->id,
            ]);

            AuditLogger::log('exp.reversal', $reversalTx, [
                'credit_id' => $credit->id,
                'initial_amount' => $originalAmount,
                'remaining_amount' => $credit->remaining_amount,
            ], [
                'reason' => $reason,
                'already_redeemed' => $alreadyRedeemed,
                'spendable_deducted' => $spendableDeduction,
                'lifetime_deducted' => $lifetimeDeduction,
                'actor' => $actor->name,
            ]);

            return [
                'success' => true,
                'already_redeemed' => $alreadyRedeemed,
                'transaction' => $reversalTx,
            ];
        });
    }

    /**
     * Manual EXP Adjustment with 2-approver threshold check.
     */
    public function createAdjustment(
        User $targetUser,
        int $spendableDelta,
        int $lifetimeDelta,
        string $reason,
        User $actor,
        ?User $secondApprover = null
    ): array {
        $threshold = (int) Setting::get('exp_large_correction_threshold', 500);
        $requiresSecondApprover = abs($spendableDelta) >= $threshold || abs($lifetimeDelta) >= $threshold;

        if ($requiresSecondApprover && !$secondApprover && !$actor->isSuperAdmin()) {
            return [
                'success' => false,
                'requires_second_approver' => true,
                'message' => "Adjustments of {$threshold} EXP or greater require approval by a second authorized administrator.",
            ];
        }

        // Prevent negative balances
        if ($spendableDelta < 0 && ($targetUser->getSpendableExp() + $spendableDelta) < 0) {
            return [
                'success' => false,
                'message' => 'Adjustment rejected: Spendable EXP balance cannot become negative.',
            ];
        }

        if ($lifetimeDelta < 0 && ($targetUser->getLifetimeExp() + $lifetimeDelta) < 0) {
            return [
                'success' => false,
                'message' => 'Adjustment rejected: Lifetime EXP balance cannot become negative.',
            ];
        }

        return DB::transaction(function () use ($targetUser, $spendableDelta, $lifetimeDelta, $reason, $actor, $secondApprover) {
            $credit = null;
            if ($spendableDelta > 0) {
                $credit = ExpCredit::create([
                    'user_id' => $targetUser->id,
                    'initial_amount' => $spendableDelta,
                    'remaining_amount' => $spendableDelta,
                    'expires_at' => null, // Manual awards are non-expiring by default unless specified
                    'source_type' => 'adjustment',
                    'status' => 'active',
                    'created_by' => $actor->id,
                ]);
            }

            $txAmount = $lifetimeDelta != 0 ? $lifetimeDelta : $spendableDelta;

            $tx = XpTransaction::create([
                'user_id' => $targetUser->id,
                'exp_credit_id' => $credit?->id,
                'amount' => $txAmount,
                'spendable_delta' => $spendableDelta,
                'lifetime_delta' => $lifetimeDelta,
                'activity_type' => 'manual_adjustment',
                'description' => "Manual EXP adjustment: {$reason}",
                'created_by' => $actor->id,
                'second_approved_by' => $secondApprover?->id,
                'second_approved_at' => $secondApprover ? now() : null,
            ]);

            if (\Illuminate\Support\Facades\Schema::hasTable('point_transactions')) {
                \App\Models\PointTransaction::create([
                    'user_id' => $targetUser->id,
                    'amount' => $spendableDelta,
                    'transaction_type' => 'manual_adjustment',
                    'description' => "Manual adjustment: {$reason}",
                    'created_by' => $actor->id,
                ]);
            }

            if (\Illuminate\Support\Facades\Schema::hasTable('member_season_stats')) {
                $activeSeason = \App\Models\Season::where('is_active', true)->first();
                if ($activeSeason) {
                    $stat = \App\Models\MemberSeasonStat::firstOrCreate(
                        ['user_id' => $targetUser->id, 'season_id' => $activeSeason->id],
                        ['season_xp' => 0, 'redeemable_points' => 0, 'current_level' => 1]
                    );
                    $stat->increment('season_xp', $lifetimeDelta);
                    $stat->increment('redeemable_points', $spendableDelta);
                }
            }

            AuditLogger::log('exp.adjustment', $tx, [], [
                'target_user' => $targetUser->name,
                'spendable_delta' => $spendableDelta,
                'lifetime_delta' => $lifetimeDelta,
                'reason' => $reason,
                'actor' => $actor->name,
                'second_approver' => $secondApprover?->name,
            ]);

            return [
                'success' => true,
                'transaction' => $tx,
                'message' => 'EXP adjustment recorded successfully.',
            ];
        });
    }

    /**
     * Periodic background task to expire eligible credits.
     * Note: Expired credits cannot be redeemed even before this job runs due to SQL expiration filtering.
     */
    public function processExpiredCredits(): int
    {
        $expiredCredits = ExpCredit::where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->where('remaining_amount', '>', 0)
            ->get();

        $count = 0;
        foreach ($expiredCredits as $credit) {
            DB::transaction(function () use ($credit, &$count) {
                $expiredAmount = $credit->remaining_amount;
                $credit->update([
                    'status' => 'expired',
                    'remaining_amount' => 0,
                ]);

                XpTransaction::create([
                    'user_id' => $credit->user_id,
                    'exp_credit_id' => $credit->id,
                    'amount' => -$expiredAmount,
                    'spendable_delta' => -$expiredAmount,
                    'lifetime_delta' => 0, // Ordinary expiration does NOT reduce Lifetime EXP
                    'activity_type' => 'expiration',
                    'source_type' => 'expiration',
                    'source_id' => $credit->id,
                    'description' => "EXP credit expired ({$expiredAmount} EXP)",
                ]);

                $count++;
            });
        }

        return $count;
    }
}
