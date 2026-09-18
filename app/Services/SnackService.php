<?php

namespace App\Services;

use App\Models\Kiosk;
use App\Models\SnackClaim;
use App\Models\SnackInventory;
use App\Models\SnackSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SnackService
{
    public function __construct(private QrCodeService $qrService) {}

    public function claim(string $token, SnackSession $session, SnackInventory $inventory, Kiosk $kiosk, User $staff): array
    {
        $participant = $this->qrService->resolveParticipant($token);

        if (!$participant) {
            return ['success' => false, 'message' => 'Invalid or expired QR code.', 'code' => 'invalid_qr'];
        }

        $existing = SnackClaim::where('snack_session_id', $session->id)
            ->where(function ($q) use ($participant, $token) {
                $q->where('qr_token', $token)
                    ->orWhere(function ($sub) use ($participant) {
                        $sub->where('participant_type', $participant['type'])
                            ->where('participant_id', $participant['id']);
                    });
            })
            ->first();

        if ($existing) {
            $claimedAt = $existing->claimed_at ? $existing->claimed_at->format('g:i A') : 'earlier';
            return [
                'success' => false,
                'message' => "Already claimed a snack for this session at {$claimedAt}.",
                'code' => 'duplicate'
            ];
        }

        if (!$inventory->hasStock()) {
            return ['success' => false, 'message' => 'No remaining stock for this item.', 'code' => 'no_stock'];
        }

        try {
            $claim = DB::transaction(function () use ($token, $session, $inventory, $participant, $kiosk, $staff) {
                $lockedInventory = SnackInventory::where('id', $inventory->id)->lockForUpdate()->first();
                if (!$lockedInventory || !$lockedInventory->hasStock()) {
                    throw new \RuntimeException('No remaining stock for this item.');
                }

                $claim = SnackClaim::create([
                    'snack_session_id' => $session->id,
                    'snack_inventory_id' => $lockedInventory->id,
                    'qr_token' => $token,
                    'participant_type' => $participant['type'],
                    'participant_id' => $participant['id'],
                    'quantity' => 1,
                    'kiosk_id' => $kiosk->id,
                    'distributed_by' => $staff->id,
                    'claimed_at' => now(),
                ]);

                $lockedInventory->increment('distributed_quantity');

                return $claim;
            });

            return [
                'success' => true,
                'message' => 'Snack distributed to ' . $participant['name'],
                'name' => $participant['name'],
                'claim' => $claim,
            ];
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            return ['success' => false, 'message' => 'Participant already claimed this snack.', 'code' => 'duplicate'];
        } catch (\RuntimeException $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'code' => 'no_stock'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Claim failed. Please try again.', 'code' => 'error'];
        }
    }
}
