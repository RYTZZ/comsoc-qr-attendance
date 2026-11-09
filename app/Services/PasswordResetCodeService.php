<?php

namespace App\Services;

use App\Mail\PasswordResetTokenMail;
use App\Models\PasswordResetCode;
use App\Models\User;
use App\Support\RowLock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

class PasswordResetCodeService
{
    public const CODE_LENGTH = 6;

    public const EXPIRATION_MINUTES = 15;

    public const MAX_ATTEMPTS = 5;

    public const RESEND_COOLDOWN_SECONDS = 60;

    /**
     * Unambiguous character set excluding O, 0, I, 1.
     * Letters: A-H, J-N, P-Z (24 uppercase letters)
     * Digits: 2-9 (8 digits)
     * Total pool: 32 characters
     */
    public const ALLOWED_CHARS = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    /**
     * Generate a cryptographically secure 6-character uppercase alphanumeric code.
     */
    public function generateCode(): string
    {
        $chars = self::ALLOWED_CHARS;
        $maxIndex = strlen($chars) - 1;
        $code = '';

        for ($i = 0; $i < self::CODE_LENGTH; $i++) {
            $code .= $chars[random_int(0, $maxIndex)];
        }

        return $code;
    }

    /**
     * Seconds remaining in 60-second resend cooldown for this user/email.
     */
    public function remainingResendCooldownSeconds(User $user, ?string $email = null): int
    {
        $targetEmail = strtolower(trim($email ?: (string) $user->email));

        $latest = PasswordResetCode::where('user_id', $user->id)
            ->where('email', $targetEmail)
            ->latest('created_at')
            ->first();

        if (! $latest || ! $latest->created_at) {
            return 0;
        }

        $elapsed = (int) $latest->created_at->diffInSeconds(now(), false);
        if ($elapsed < 0) {
            return self::RESEND_COOLDOWN_SECONDS;
        }

        return max(0, self::RESEND_COOLDOWN_SECONDS - $elapsed);
    }

    /**
     * Invalidate any active, unconsumed reset codes for a user.
     */
    public function invalidateActiveCodes(User $user, ?string $email = null): int
    {
        $targetEmail = $email !== null ? strtolower(trim($email)) : null;

        $query = PasswordResetCode::where('user_id', $user->id)
            ->whereNull('consumed_at')
            ->whereNull('invalidated_at');

        if ($targetEmail !== null) {
            $query->where('email', $targetEmail);
        }

        return $query->update([
            'invalidated_at' => now(),
        ]);
    }

    /**
     * Issue a 6-character reset code and send the branded email.
     *
     * @return array{sent: bool, status: string, message: string, cooldown_seconds?: int}
     */
    public function issueAndSend(User $user, ?string $ipAddress = null, bool $enforceCooldown = true): array
    {
        $email = strtolower(trim((string) $user->email));

        // Enforce 60-second cooldown
        if ($enforceCooldown) {
            $remaining = $this->remainingResendCooldownSeconds($user, $email);
            if ($remaining > 0) {
                return [
                    'sent' => false,
                    'status' => 'throttled',
                    'message' => "Please wait {$remaining} seconds before requesting a new password reset code.",
                    'cooldown_seconds' => $remaining,
                ];
            }
        }

        // Rate limiter per user/ip: max 5 code requests per 15 minutes
        $rateLimitKey = 'prc-issue:' . $user->id . '|' . ($ipAddress ?? '0.0.0.0');
        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            return [
                'sent' => false,
                'status' => 'rate_limited',
                'message' => "Too many password reset requests. Please try again in {$seconds} seconds.",
                'cooldown_seconds' => $seconds,
            ];
        }

        RateLimiter::hit($rateLimitKey, 900); // 15-minute window

        $plainCode = $this->generateCode();
        $codeHash = Hash::make($plainCode);
        $expiresAt = now()->addMinutes(self::EXPIRATION_MINUTES);

        DB::transaction(function () use ($user, $email, $codeHash, $expiresAt, $ipAddress) {
            // Invalidate any existing active codes for this user/email
            $this->invalidateActiveCodes($user, $email);

            PasswordResetCode::create([
                'user_id' => $user->id,
                'email' => $email,
                'code_hash' => $codeHash,
                'attempts' => 0,
                'expires_at' => $expiresAt,
                'consumed_at' => null,
                'invalidated_at' => null,
                'ip_address' => $ipAddress,
            ]);
        });

        // Dispatch redesigned password reset email
        try {
            Mail::to($email)->send(
                new PasswordResetTokenMail(
                    userName: $user->name,
                    token: $plainCode,
                    email: $email,
                    expirationMinutes: self::EXPIRATION_MINUTES,
                )
            );

            AuditLogger::log('auth.password_reset_code_sent', $user, [], [
                'email' => $email,
                'ip' => $ipAddress,
            ]);

            return [
                'sent' => true,
                'status' => 'sent',
                'message' => 'Your 6-character password reset code has been sent to your registered email address.',
                'cooldown_seconds' => self::RESEND_COOLDOWN_SECONDS,
            ];
        } catch (Throwable $e) {
            Log::error('Failed to dispatch password reset code email', [
                'user_id' => $user->id,
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return [
                'sent' => false,
                'status' => 'mail_failed',
                'message' => 'Unable to send password reset code email. Please try again shortly.',
                'cooldown_seconds' => 0,
            ];
        }
    }

    /**
     * Validate an entered 6-character code server-side.
     *
     * @return array{valid: bool, status: string, message: string, code_record?: PasswordResetCode}
     */
    public function verify(User $user, string $rawCode, ?string $ipAddress = null): array
    {
        $normalizedCode = strtoupper(trim($rawCode));
        $email = strtolower(trim((string) $user->email));

        // Rate limiter on verification attempts per IP to avoid brute-forcing
        $ipVerifyKey = 'prc-verify-ip:' . ($ipAddress ?? '0.0.0.0');
        if (RateLimiter::tooManyAttempts($ipVerifyKey, 20)) {
            $seconds = RateLimiter::availableIn($ipVerifyKey);
            return [
                'valid' => false,
                'status' => 'rate_limited',
                'message' => "Too many verification attempts from your network. Please wait {$seconds} seconds.",
            ];
        }
        RateLimiter::hit($ipVerifyKey, 300);

        return DB::transaction(function () use ($user, $email, $normalizedCode, $ipAddress) {
            $record = PasswordResetCode::where('user_id', $user->id)
                ->where('email', $email)
                ->whereNull('consumed_at')
                ->whereNull('invalidated_at')
                ->latest('created_at')
                ->lockForUpdate()
                ->first();

            if (! $record) {
                return [
                    'valid' => false,
                    'status' => 'not_found',
                    'message' => 'No active password reset request was found for this account. Please request a new code.',
                ];
            }

            if ($record->isExpired()) {
                return [
                    'valid' => false,
                    'status' => 'expired',
                    'message' => 'This password reset code has expired. Please request a new code.',
                ];
            }

            if ($record->attempts >= self::MAX_ATTEMPTS) {
                $record->update(['invalidated_at' => now()]);
                return [
                    'valid' => false,
                    'status' => 'exhausted',
                    'message' => 'Too many consecutive incorrect attempts. This code has been invalidated for your security. Please request a new code.',
                ];
            }

            if (! Hash::check($normalizedCode, $record->code_hash)) {
                $record->increment('attempts');
                $remainingAttempts = self::MAX_ATTEMPTS - $record->attempts;

                if ($remainingAttempts <= 0) {
                    $record->update(['invalidated_at' => now()]);
                    return [
                        'valid' => false,
                        'status' => 'exhausted',
                        'message' => 'Too many consecutive incorrect attempts. This code has been invalidated. Please request a new code.',
                    ];
                }

                return [
                    'valid' => false,
                    'status' => 'incorrect',
                    'message' => "The verification code is incorrect. {$remainingAttempts} attempt(s) remaining.",
                ];
            }

            return [
                'valid' => true,
                'status' => 'verified',
                'message' => 'Code verified successfully.',
                'code_record' => $record,
            ];
        });
    }

    /**
     * Mark code as consumed and invalidate all pending codes upon successful password reset.
     */
    public function consume(PasswordResetCode $code): void
    {
        DB::transaction(function () use ($code) {
            $code->update(['consumed_at' => now()]);
            PasswordResetCode::where('user_id', $code->user_id)
                ->whereNull('consumed_at')
                ->whereNull('invalidated_at')
                ->update(['invalidated_at' => now()]);
        });
    }
}
