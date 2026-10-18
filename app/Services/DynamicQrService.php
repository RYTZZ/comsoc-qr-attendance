<?php

namespace App\Services;

use App\Models\DynamicQrToken;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class DynamicQrService
{
    public const TOKEN_TTL_SECONDS = 30;

    /**
     * Generate a new rolling single-use QR payload for a member.
     * The payload is signed and encrypted with an expiration timestamp and unique token identifier.
     */
    public function generateTokenForUser(User $user): array
    {
        // Generate cryptographic token
        $rawEntropy = Str::random(32);
        $expiresAt = now()->addSeconds(self::TOKEN_TTL_SECONDS);
        $tokenHash = hash('sha256', $rawEntropy . $user->id . $expiresAt->timestamp);

        // Store active token record with TTL
        DynamicQrToken::create([
            'user_id' => $user->id,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
            'is_consumed' => false,
        ]);

        // Clean up stale tokens for this user older than 5 minutes to prevent bloat
        DynamicQrToken::where('user_id', $user->id)
            ->where('expires_at', '<', now()->subMinutes(5))
            ->delete();

        // Build signed payload string: "v1|userId|expiresAt|tokenHash"
        $payloadData = [
            'v' => 1,
            'uid' => $user->id,
            'exp' => $expiresAt->timestamp,
            'hash' => $tokenHash,
        ];

        $serialized = json_encode($payloadData);
        $signature = hash_hmac('sha256', $serialized, config('app.key'));
        $qrString = base64_encode($serialized . '::' . $signature);

        return [
            'qr_payload' => $qrString,
            'expires_at' => $expiresAt->toIso8601String(),
            'ttl' => self::TOKEN_TTL_SECONDS,
            'member' => [
                'name' => $user->display_name,
                'student_number' => $user->student_number ?? 'N/A',
                'is_eligible' => (bool)$user->is_eligible,
            ],
        ];
    }

    /**
     * Validate and atomically consume a dynamic QR token scanned by an organizer.
     */
    public function validateAndConsumeToken(string $scannedString, User $organizer): array
    {
        $decoded = base64_decode($scannedString, true);
        if (!$decoded || !str_contains($decoded, '::')) {
            return [
                'valid' => false,
                'code' => 'invalid_token_format',
                'message' => 'Malformed QR code format.',
                'user' => null,
            ];
        }

        [$serialized, $signature] = explode('::', $decoded, 2);

        // 1. Verify HMAC signature integrity
        $expectedSignature = hash_hmac('sha256', $serialized, config('app.key'));
        if (!hash_equals($expectedSignature, $signature)) {
            return [
                'valid' => false,
                'code' => 'signature_mismatch',
                'message' => 'Tampered or counterfeit QR code.',
                'user' => null,
            ];
        }

        $payload = json_decode($serialized, true);
        if (!$payload || !isset($payload['uid'], $payload['exp'], $payload['hash'])) {
            return [
                'valid' => false,
                'code' => 'invalid_payload',
                'message' => 'Invalid token structure.',
                'user' => null,
            ];
        }

        // 2. Validate expiration (allow 5-second grace window for slight network clock skew)
        if (now()->timestamp > ($payload['exp'] + 5)) {
            return [
                'valid' => false,
                'code' => 'expired_token',
                'message' => 'QR code has expired. Please refresh on the member device.',
                'user' => null,
            ];
        }

        // 3. Check database record & anti-replay single-use constraint
        $tokenRecord = DynamicQrToken::where('token_hash', $payload['hash'])
            ->where('user_id', $payload['uid'])
            ->first();

        if (!$tokenRecord) {
            return [
                'valid' => false,
                'code' => 'token_not_found',
                'message' => 'Unrecognized or superseded token.',
                'user' => null,
            ];
        }

        if ($tokenRecord->is_consumed) {
            return [
                'valid' => false,
                'code' => 'already_used',
                'message' => 'This QR code was already scanned (replay attempt detected).',
                'user' => null,
            ];
        }

        // 4. Resolve member user
        $member = User::find($payload['uid']);
        if (!$member) {
            return [
                'valid' => false,
                'code' => 'member_not_found',
                'message' => 'Member profile not found.',
                'user' => null,
            ];
        }

        if (!$member->is_active || !$member->is_eligible) {
            return [
                'valid' => false,
                'code' => 'ineligible_member',
                'message' => 'Member account is inactive or ineligible.',
                'user' => $member,
            ];
        }

        // 5. Atomically consume token
        $tokenRecord->update([
            'is_consumed' => true,
            'consumed_at' => now(),
            'consumed_by' => $organizer->id,
        ]);

        return [
            'valid' => true,
            'code' => 'valid',
            'message' => 'QR token authenticated successfully.',
            'user' => $member,
            'token_record' => $tokenRecord,
        ];
    }
}
