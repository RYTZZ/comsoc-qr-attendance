<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Event;
use App\Models\Raffle;
use App\Models\RaffleEntry;
use App\Models\RaffleWinner;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RaffleService
{
    public function __construct(
        protected RewardService $rewardService
    ) {}

    /**
     * Create a new raffle tied to an event.
     */
    public function createRaffle(Event $event, array $data, User $creator): Raffle
    {
        return Raffle::create([
            'event_id' => $event->id,
            'reward_id' => $data['reward_id'] ?? null,
            'created_by' => $creator->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'prize_name' => $data['prize_name'],
            'points_bonus' => (int) ($data['points_bonus'] ?? 0),
            'winner_count' => max(1, (int) ($data['winner_count'] ?? 1)),
            'entry_type' => $data['entry_type'] ?? 'attendance',
            'points_per_entry' => max(0, (int) ($data['points_per_entry'] ?? 0)),
            'max_entries_per_user' => max(1, (int) ($data['max_entries_per_user'] ?? 1)),
            'status' => 'open',
        ]);
    }

    /**
     * Take an entry snapshot of all verified attendees of the event.
     * Respects max_entries_per_user.
     */
    public function syncEntriesFromAttendance(Raffle $raffle): int
    {
        if ($raffle->isDrawn()) {
            throw new InvalidArgumentException('Cannot sync entries for an already drawn raffle.');
        }

        // Get all unique users with verified attendance for this event
        $attendees = AttendanceRecord::where('event_id', $raffle->event_id)
            ->whereNotNull('user_id')
            ->select('user_id', 'id as attendance_record_id', 'created_at')
            ->orderBy('created_at', 'asc')
            ->get()
            ->unique('user_id');

        $count = 0;
        foreach ($attendees as $att) {
            $userEntryCount = RaffleEntry::where('raffle_id', $raffle->id)
                ->where('user_id', $att->user_id)
                ->count();

            if ($userEntryCount < $raffle->max_entries_per_user) {
                $entry = RaffleEntry::firstOrCreate(
                    [
                        'raffle_id' => $raffle->id,
                        'user_id' => $att->user_id,
                    ],
                    [
                        'attendance_record_id' => $att->attendance_record_id,
                        'source_type' => 'attendance',
                        'eligible_at' => $att->created_at ?? now(),
                    ]
                );

                if ($entry->wasRecentlyCreated) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Allow student to purchase raffle entry using spendable points.
     */
    public function purchaseEntryWithPoints(Raffle $raffle, User $user): RaffleEntry
    {
        if ($raffle->isDrawn()) {
            throw new InvalidArgumentException('This raffle has already ended.');
        }

        if ($raffle->entry_type !== 'points_purchase') {
            throw new InvalidArgumentException('This raffle does not accept points-based entries.');
        }

        $userEntryCount = RaffleEntry::where('raffle_id', $raffle->id)
            ->where('user_id', $user->id)
            ->count();

        if ($userEntryCount >= $raffle->max_entries_per_user) {
            throw new InvalidArgumentException("Maximum entry limit reached ({$raffle->max_entries_per_user} entries per participant).");
        }

        $cost = $raffle->points_per_entry;
        $spendable = $user->getSpendableExp();

        if ($spendable < $cost) {
            throw new InvalidArgumentException("Insufficient points balance. Required: {$cost} pts, Available: {$spendable} pts.");
        }

        return DB::transaction(function () use ($raffle, $user, $cost) {
            // Deduct points via FEFO ExpLedgerService
            /** @var \App\Services\ExpLedgerService $expLedgerService */
            $expLedgerService = app(\App\Services\ExpLedgerService::class);
            $expLedgerService->allocateFefo($user, $cost, (string) $raffle->id);

            return RaffleEntry::create([
                'raffle_id' => $raffle->id,
                'user_id' => $user->id,
                'source_type' => 'points_purchase',
                'points_deducted' => $cost,
                'eligible_at' => now(),
            ]);
        });
    }

    /**
     * Perform the random draw among verified entries and award bonus redeem points.
     * Prevents duplicate winners (one prize per participant in a single raffle).
     */
    public function executeDraw(Raffle $raffle, User $actor): array
    {
        if ($raffle->isDrawn()) {
            throw new InvalidArgumentException('This raffle has already been drawn.');
        }

        return DB::transaction(function () use ($raffle, $actor) {
            // If attendance-based, sync any newly verified attendees first
            if ($raffle->entry_type === 'attendance') {
                $this->syncEntriesFromAttendance($raffle);
            }

            // Fetch all entries for this raffle
            $entries = $raffle->entries()->with('user')->get();

            if ($entries->isEmpty()) {
                throw new InvalidArgumentException('No eligible entries found in the raffle pool.');
            }

            // Secure server-side randomized selection
            $shuffled = $entries->shuffle();

            // Prevent duplicate winners across entries (unique by user_id)
            $uniqueUserEntries = $shuffled->unique('user_id');

            $winnerCount = min($raffle->winner_count, $uniqueUserEntries->count());
            $winningEntries = $uniqueUserEntries->take($winnerCount);

            $winners = [];
            $order = 1;

            foreach ($winningEntries as $entry) {
                $bonusPoints = $raffle->points_bonus;

                $winner = RaffleWinner::create([
                    'raffle_id' => $raffle->id,
                    'raffle_entry_id' => $entry->id,
                    'user_id' => $entry->user_id,
                    'draw_order' => $order++,
                    'points_awarded' => $bonusPoints,
                    'is_claimed' => false,
                ]);

                // Award redeem points ledger entry if bonus points > 0
                if ($bonusPoints > 0) {
                    $this->rewardService->grantReward(
                        user: $entry->user,
                        activityType: 'raffle_win',
                        xpAmount: 0, // EXP is not awarded for raffles, strictly redeem points
                        pointsAmount: $bonusPoints,
                        description: "Won raffle: {$raffle->title} ({$raffle->prize_name})",
                        sourceType: Raffle::class,
                        sourceId: (string) $raffle->id,
                        idempotencyKey: "raffle_{$raffle->id}_winner_{$entry->user_id}",
                        actor: $actor
                    );
                }

                $winners[] = $winner;
            }

            $raffle->update([
                'status' => 'drawn',
                'drawn_at' => now(),
                'drawn_by' => $actor->id,
            ]);

            return $winners;
        });
    }

    /**
     * Mark prize claimed by staff.
     */
    public function claimPrize(RaffleWinner $winner, User $staff, ?string $notes = null): bool
    {
        return $winner->update([
            'is_claimed' => true,
            'claimed_at' => now(),
            'claimed_by_staff_id' => $staff->id,
            'claim_notes' => $notes,
        ]);
    }
}
