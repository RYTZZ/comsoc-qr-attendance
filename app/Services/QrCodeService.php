<?php

namespace App\Services;

use App\Models\Membership;
use App\Models\QrCode;
use Illuminate\Support\Str;

class QrCodeService
{
    public function generateForMembership(Membership $membership, int $batchNumber): QrCode
    {
        $existing = $membership->qrCodes()->where('status', 'active')->first();
        if ($existing) {
            return $existing;
        }

        $token = $this->uniqueToken();

        $qr = QrCode::create([
            'membership_id' => $membership->id,
            'token' => $token,
            'status' => 'active',
            'batch_number' => $batchNumber,
            'activated_at' => now(),
        ]);

        \App\Models\Card::firstOrCreate([
            'qr_code_id' => $qr->id,
        ], [
            'status' => 'for_claiming',
        ]);

        $membership->update(['status' => 'active']);

        return $qr;
    }

    public function revoke(QrCode $qrCode, string $reason, $actor): QrCode
    {
        $qrCode->update([
            'status' => 'revoked',
            'revoked_at' => now(),
            'revoke_reason' => $reason,
        ]);

        return $qrCode;
    }

    public function reissue(QrCode $revokedQr, int $batchNumber, $actor): QrCode
    {
        $qr = QrCode::create([
            'membership_id' => $revokedQr->membership_id,
            'token' => $this->uniqueToken(),
            'status' => 'active',
            'batch_number' => $batchNumber,
            'activated_at' => now(),
        ]);

        \App\Models\Card::create([
            'qr_code_id' => $qr->id,
            'status' => 'for_claiming',
        ]);

        return $qr;
    }

    private function uniqueToken(): string
    {
        do {
            $token = Str::random(48);
        } while (QrCode::where('token', $token)->exists());

        return $token;
    }

    public function resolveParticipant(string $token): ?array
    {
        $qr = QrCode::with(['membership.student', 'membership.academicYear'])
            ->where('token', $token)
            ->where('status', 'active')
            ->first();

        if ($qr) {
            return [
                'type' => 'student',
                'id' => $qr->membership->student_id,
                'qr_token' => $token,
                'name' => $qr->membership->student->display_name,
                'membership' => $qr->membership,
                'qr' => $qr,
            ];
        }

        $reg = \App\Models\EventRegistration::where('qr_token', $token)
            ->where('status', 'approved')
            ->where(function ($q) {
                $q->whereNull('qr_expires_at')
                  ->orWhere('qr_expires_at', '>', now()->subDay());
            })
            ->first();

        if ($reg) {
            return [
                'type' => 'non_student',
                'id' => $reg->id,
                'qr_token' => $token,
                'name' => $reg->full_name,
                'registration' => $reg,
            ];
        }

        return null;
    }
}
