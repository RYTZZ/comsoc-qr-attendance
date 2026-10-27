<?php

namespace App\Services;

use App\Models\DynamicQrToken;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class DynamicQrService
{
    public const TOKEN_TTL_SECONDS = 30;

    // Unambiguous character set for manual attendance token (excluding 0, O, 1, I, L)
    private const MANUAL_CHARSET = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';

    /**
     * Generate a new rolling single-use QR payload and short human-friendly manual token for a member.
     * The payload is signed and encrypted with an expiration timestamp and unique token identifier.
     * Optionally scoped to an event (for event-specific dynamic passes).
     */
    public function generateTokenForUser(User $user, ?\App\Models\Event $event = null): array
    {
        if ($event && ($event->isEnded() || in_array($event->status, ['cancelled', 'archived', 'draft']))) {
            throw new \InvalidArgumentException('Cannot issue attendance QR pass for an event that has ended, been cancelled, or is inactive.');
        }

        // Generate cryptographic token
        $rawEntropy = Str::random(32);
        $expiresAt = now()->addSeconds(self::TOKEN_TTL_SECONDS);
        $eventId = $event?->id;
        $tokenHash = hash('sha256', $rawEntropy . $user->id . ($eventId ?? '') . $expiresAt->timestamp);

        // Generate easy-to-type, unambiguous 6-character manual attendance token (e.g., "7K9-MP4")
        $manualToken = $this->generateUniqueManualToken();
        $formattedToken = substr($manualToken, 0, 3) . '-' . substr($manualToken, 3, 3);

        // Store active token record with TTL
        DynamicQrToken::create([
            'user_id' => $user->id,
            'event_id' => $eventId,
            'token_hash' => $tokenHash,
            'manual_token' => $manualToken,
            'expires_at' => $expiresAt,
            'is_consumed' => false,
        ]);

        // Clean up stale tokens for this user older than 5 minutes to prevent bloat
        DynamicQrToken::where('user_id', $user->id)
            ->where('expires_at', '<', now()->subMinutes(5))
            ->delete();

        // Build signed payload string: "v1|userId|eventId|expiresAt|tokenHash"
        $payloadData = [
            'v' => 1,
            'uid' => $user->id,
            'eid' => $eventId,
            'exp' => $expiresAt->timestamp,
            'hash' => $tokenHash,
            'man' => $manualToken,
        ];

        $serialized = json_encode($payloadData);
        $signature = hash_hmac('sha256', $serialized, config('app.key'));
        $qrString = base64_encode($serialized . '::' . $signature);

        return [
            'qr_payload' => $qrString,
            'manual_token' => $manualToken,
            'formatted_manual_token' => $formattedToken,
            'expires_at' => $expiresAt->toIso8601String(),
            'ttl' => self::TOKEN_TTL_SECONDS,
            'event_id' => $eventId,
            'member' => [
                'name' => $user->display_name,
                'student_number' => $user->student_number ?? 'N/A',
                'is_eligible' => (bool)$user->is_eligible,
            ],
        ];
    }

    /**
     * Generate an unambiguous 6-character manual token.
     */
    private function generateUniqueManualToken(): string
    {
        $chars = self::MANUAL_CHARSET;
        $len = strlen($chars);

        do {
            $token = '';
            for ($i = 0; $i < 6; $i++) {
                $token .= $chars[random_int(0, $len - 1)];
            }
        } while (DynamicQrToken::where('manual_token', $token)->where('expires_at', '>', now())->where('is_consumed', false)->exists());

        return $token;
    }

    /**
     * Normalize user-entered manual token (strip hyphens, whitespace, uppercase).
     */
    public function normalizeManualToken(?string $token): string
    {
        if (!$token) return '';
        // Replace common ambiguous lookalikes if entered
        $normalized = strtoupper(trim($token));
        $normalized = str_replace(['-', ' ', '_'], '', $normalized);
        $normalized = strtr($normalized, [
            '0' => 'O',
            '1' => 'I',
        ]);
        // Also allow direct characters without substitution if they match
        return str_replace(['-', ' ', '_'], '', strtoupper(trim($token)));
    }

    /**
     * Validate and atomically consume a dynamic QR token or manual token scanned/entered by an organizer.
     * Optionally validates that the token was issued for the expected event.
     */
    public function validateAndConsumeToken(string $scannedString, User $organizer, ?\App\Models\Event $expectedEvent = null): array
    {
        $raw = trim($scannedString);

        // Check if this is a manual token input (e.g., "7K9-MP4" or "7K9MP4")
        $cleanManual = preg_replace('/[^A-Za-z0-9]/', '', strtoupper($raw));
        if (strlen($cleanManual) === 6 && preg_match('/^[A-Z0-9]{6}$/', $cleanManual)) {
            return $this->validateAndConsumeManualToken($cleanManual, $organizer, $expectedEvent);
        }

        $decoded = base64_decode($raw, true);
        if (!$decoded || !str_contains($decoded, '::')) {
            // Check if string directly matches a manual token in database
            if (strlen($cleanManual) > 0) {
                return $this->validateAndConsumeManualToken($cleanManual, $organizer, $expectedEvent);
            }

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

        // 3. Event-specific scope check (if payload has event_id or expectedEvent is specified)
        if ($expectedEvent) {
            $payloadEventId = $payload['eid'] ?? null;
            if ($payloadEventId && $payloadEventId !== $expectedEvent->id) {
                return [
                    'valid' => false,
                    'code' => 'event_mismatch',
                    'message' => 'This QR pass was issued for a different event.',
                    'user' => null,
                ];
            }
        }

        // 4. Check database record & anti-replay single-use constraint
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

        if ($expectedEvent && $tokenRecord->event_id && $tokenRecord->event_id !== $expectedEvent->id) {
            return [
                'valid' => false,
                'code' => 'event_mismatch',
                'message' => 'This QR pass was issued for a different event.',
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

        // 5. Resolve member user
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

        // 6. Atomically consume token
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

    /**
     * Validate and atomically consume a human-friendly manual attendance token.
     */
    public function validateAndConsumeManualToken(string $rawToken, User $organizer, ?\App\Models\Event $expectedEvent = null): array
    {
        $normalized = preg_replace('/[^A-Za-z0-9]/', '', strtoupper($rawToken));

        $tokenQuery = function ($t) use ($expectedEvent) {
            $q = DynamicQrToken::where('manual_token', $t)
                ->where('expires_at', '>', now()->subSeconds(5))
                ->where('is_consumed', false);
            if ($expectedEvent) {
                $q->where(function ($sub) use ($expectedEvent) {
                    $sub->whereNull('event_id')->orWhere('event_id', $expectedEvent->id);
                });
            }
            return $q->latest()->first();
        };

        // Attempt exact match first
        $tokenRecord = $tokenQuery($normalized);

        // If not found, check with ambiguous replacements (O -> 0, I -> 1 or vice-versa)
        if (!$tokenRecord) {
            $trans = strtr($normalized, ['O' => '0', '0' => 'O', 'I' => '1', '1' => 'I', 'L' => '1']);
            $tokenRecord = $tokenQuery($trans);
        }

        // If still not found, check if it already expired, was consumed, or was for another event
        if (!$tokenRecord) {
            $consumedOrExpired = DynamicQrToken::where('manual_token', $normalized)->latest()->first();

            if ($consumedOrExpired) {
                if ($expectedEvent && $consumedOrExpired->event_id && $consumedOrExpired->event_id !== $expectedEvent->id) {
                    return [
                        'valid' => false,
                        'code' => 'event_mismatch',
                        'message' => 'This attendance token was issued for a different event.',
                        'user' => null,
                    ];
                }
                if ($consumedOrExpired->is_consumed) {
                    return [
                        'valid' => false,
                        'code' => 'already_used',
                        'message' => 'This attendance token has already been used.',
                        'user' => null,
                    ];
                }
                if ($consumedOrExpired->expires_at <= now()->subSeconds(5)) {
                    return [
                        'valid' => false,
                        'code' => 'expired_token',
                        'message' => 'Manual attendance token has expired. Please use the current token on the screen.',
                        'user' => null,
                    ];
                }
            }

            return [
                'valid' => false,
                'code' => 'token_not_found',
                'message' => 'Invalid or unrecognized manual attendance token.',
                'user' => null,
            ];
        }

        // Check if expired
        if ($tokenRecord->expires_at->timestamp < now()->subSeconds(5)->timestamp) {
            return [
                'valid' => false,
                'code' => 'expired_token',
                'message' => 'Manual attendance token has expired. Please use the current token on the screen.',
                'user' => null,
            ];
        }

        // Resolve member user
        $member = User::find($tokenRecord->user_id);
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

        // Atomically consume token
        $tokenRecord->update([
            'is_consumed' => true,
            'consumed_at' => now(),
            'consumed_by' => $organizer->id,
        ]);

        return [
            'valid' => true,
            'code' => 'valid',
            'message' => 'Manual token authenticated successfully.',
            'user' => $member,
            'token_record' => $tokenRecord,
        ];
    }
}
