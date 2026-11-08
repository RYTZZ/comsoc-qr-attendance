<?php

namespace App\Services;

use App\Mail\EmailVerificationOtpMail;
use App\Models\EmailVerificationCode;
use App\Models\User;
use App\Support\RowLock;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

class EmailVerificationOtpService
{
    public const OTP_LENGTH = 6;

    public const EXPIRATION_MINUTES = 15;

    public const MAX_ATTEMPTS = 5;

    public const RESEND_COOLDOWN_SECONDS = 60;

    /**
     * Generate a cryptographically secure 6-digit numeric OTP string preserving leading zeroes.
     */
    public function generateOtpCode(): string
    {
        return str_pad((string) random_int(0, 999999), self::OTP_LENGTH, '0', STR_PAD_LEFT);
    }

    /**
     * Check how many seconds remain in the 60-second resend cooldown for this user.
     */
    public function remainingResendCooldownSeconds(User $user): int
    {
        $latest = EmailVerificationCode::where('user_id', $user->id)
            ->where('email', strtolower(trim((string) $user->email)))
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
     * Invalidate all active verification codes for a user (optionally scoped to a specific email or all emails).
     */
    public function invalidateActiveCodes(User $user, ?string $onlyEmail = null): int
    {
        $query = EmailVerificationCode::where('user_id', $user->id)
            ->whereNull('consumed_at')
            ->whereNull('invalidated_at');

        if ($onlyEmail !== null) {
            $query->where('email', strtolower(trim($onlyEmail)));
        }

        return $query->update([
            'invalidated_at' => now(),
        ]);
    }

    /**
     * Issue a new 6-digit OTP challenge and send the verification email.
     *
     * @param  bool  $enforceCooldown  Whether to enforce the 60-second resend cooldown (true for resends, false for initial registration / email change).
     * @return array{sent: bool, status: string, message: string, cooldown_seconds?: int, code_record?: EmailVerificationCode}
     */
    public function issueAndSend(User $user, ?string $ipAddress = null, bool $enforceCooldown = true): array
    {
        if ($user->hasVerifiedEmail()) {
            return [
                'sent' => false,
                'status' => 'already_verified',
                'message' => 'Your email address is already verified.',
                'cooldown_seconds' => 0,
            ];
        }

        $email = strtolower(trim((string) $user->email));
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return [
                'sent' => false,
                'status' => 'invalid_email',
                'message' => 'A valid email address is required before sending a verification code.',
                'cooldown_seconds' => 0,
            ];
        }

        // Abuse protection: rate limit repeated OTP issuance per user + IP (do not reset on new OTP requests)
        $abuseKey = $this->issueRateLimitKey($user, $ipAddress);
        if (RateLimiter::tooManyAttempts($abuseKey, 10)) {
            $retryAfter = RateLimiter::availableIn($abuseKey);

            return [
                'sent' => false,
                'status' => 'rate_limited',
                'message' => "Too many verification requests. Please wait {$retryAfter} seconds before requesting another code.",
                'cooldown_seconds' => $retryAfter,
            ];
        }

        $issueOutcome = DB::transaction(function () use ($user, $email, $ipAddress, $enforceCooldown) {
            RowLock::acquire(User::class, $user->id);

            if ($enforceCooldown) {
                $latest = EmailVerificationCode::where('user_id', $user->id)
                    ->where('email', $email)
                    ->latest('created_at')
                    ->lockForUpdate()
                    ->first();

                if ($latest && $latest->created_at) {
                    $elapsed = (int) $latest->created_at->diffInSeconds(now(), false);
                    $remaining = $elapsed < 0 ? self::RESEND_COOLDOWN_SECONDS : max(0, self::RESEND_COOLDOWN_SECONDS - $elapsed);

                    if ($remaining > 0) {
                        return [
                            'allowed' => false,
                            'cooldown_seconds' => $remaining,
                        ];
                    }
                }
            }

            // Invalidate all previously active codes for this user
            EmailVerificationCode::where('user_id', $user->id)
                ->whereNull('consumed_at')
                ->whereNull('invalidated_at')
                ->update(['invalidated_at' => now()]);

            $plainOtp = $this->generateOtpCode();

            $record = EmailVerificationCode::create([
                'user_id' => $user->id,
                'email' => $email,
                'code_hash' => Hash::make($plainOtp),
                'attempts' => 0,
                'expires_at' => now()->addMinutes(self::EXPIRATION_MINUTES),
                'ip_address' => $ipAddress,
            ]);

            return [
                'allowed' => true,
                'plain_otp' => $plainOtp,
                'record' => $record,
            ];
        });

        if (! $issueOutcome['allowed']) {
            $wait = (int) $issueOutcome['cooldown_seconds'];

            return [
                'sent' => false,
                'status' => 'cooldown',
                'message' => "Please wait {$wait} seconds before requesting another verification code.",
                'cooldown_seconds' => $wait,
            ];
        }

        RateLimiter::hit($abuseKey, 900); // 15-minute window for issuance abuse protection

        /** @var EmailVerificationCode $record */
        $record = $issueOutcome['record'];
        $plainOtp = $issueOutcome['plain_otp'];

        $studentName = $this->resolveStudentName($user);

        try {
            Mail::to($email)->send(new EmailVerificationOtpMail(
                studentName: $studentName,
                otpCode: $plainOtp,
                expirationMinutes: self::EXPIRATION_MINUTES,
            ));

            AuditLogger::log('email_verification.otp_sent', $user, [], [
                'email' => $email,
                'code_id' => $record->id,
                'expires_at' => $record->expires_at?->toIso8601String(),
                'ip' => $ipAddress,
            ]);

            return [
                'sent' => true,
                'status' => 'sent',
                'message' => 'A six-digit verification code has been sent to your email address.',
                'cooldown_seconds' => self::RESEND_COOLDOWN_SECONDS,
                'code_record' => $record,
            ];
        } catch (Throwable $e) {
            // Never log plaintext OTP values
            Log::error('Failed to deliver COMSOC Hub email verification OTP.', [
                'user_id' => $user->id,
                'email' => $email,
                'code_id' => $record->id,
                'exception' => get_class($e),
                'error' => $e->getMessage(),
            ]);

            return [
                'sent' => false,
                'status' => 'delivery_failed',
                'message' => 'We were unable to send the verification email right now. Please try resending in a moment.',
                'cooldown_seconds' => 0,
                'code_record' => $record,
            ];
        }
    }

    /**
     * Validate a submitted 6-digit OTP for the authenticated user transactionally.
     *
     * @return array{verified: bool, status: string, message: string, remaining_attempts?: int}
     */
    public function verify(User $user, string $submittedCode, ?string $ipAddress = null): array
    {
        $cleanCode = trim($submittedCode);

        if (! preg_match('/^\d{6}$/', $cleanCode)) {
            return [
                'verified' => false,
                'status' => 'invalid_format',
                'message' => 'Please enter a valid 6-digit numeric verification code.',
            ];
        }

        // Abuse protection across OTP resets: prevent brute-force flooding per user + IP
        $verifyAbuseKey = $this->verifyRateLimitKey($user, $ipAddress);
        if (RateLimiter::tooManyAttempts($verifyAbuseKey, 20)) {
            $retryAfter = RateLimiter::availableIn($verifyAbuseKey);

            return [
                'verified' => false,
                'status' => 'rate_limited',
                'message' => "Too many verification attempts. Please wait {$retryAfter} seconds before trying again.",
            ];
        }

        $outcome = DB::transaction(function () use ($user, $cleanCode, $ipAddress, $verifyAbuseKey) {
            /** @var User|null $lockedUser */
            $lockedUser = RowLock::acquire(User::class, $user->id);

            if (! $lockedUser) {
                return [
                    'verified' => false,
                    'status' => 'user_not_found',
                    'message' => 'Unable to verify account.',
                ];
            }

            if ($lockedUser->email_verified_at !== null) {
                return [
                    'verified' => false,
                    'status' => 'already_verified',
                    'message' => 'This email address has already been verified.',
                ];
            }

            $currentEmail = strtolower(trim((string) $lockedUser->email));

            /** @var EmailVerificationCode|null $codeRecord */
            $codeRecord = EmailVerificationCode::where('user_id', $lockedUser->id)
                ->where('email', $currentEmail)
                ->latest('created_at')
                ->lockForUpdate()
                ->first();

            if (! $codeRecord) {
                RateLimiter::hit($verifyAbuseKey, 600);

                return [
                    'verified' => false,
                    'status' => 'no_active_code',
                    'message' => 'No active verification code was found. Please request a new code.',
                ];
            }

            if ($codeRecord->isConsumed()) {
                RateLimiter::hit($verifyAbuseKey, 600);

                return [
                    'verified' => false,
                    'status' => 'consumed',
                    'message' => 'This verification code has already been used. Please request a new code.',
                ];
            }

            if ($codeRecord->isInvalidated() || $codeRecord->attempts >= self::MAX_ATTEMPTS) {
                RateLimiter::hit($verifyAbuseKey, 600);

                return [
                    'verified' => false,
                    'status' => 'invalidated',
                    'message' => 'This verification code is no longer valid due to too many failed attempts or a newer code being issued. Please request a new code.',
                ];
            }

            if ($codeRecord->isExpired()) {
                $codeRecord->update(['invalidated_at' => now()]);
                RateLimiter::hit($verifyAbuseKey, 600);

                return [
                    'verified' => false,
                    'status' => 'expired',
                    'message' => 'This verification code has expired (codes are valid for 15 minutes). Please request a new code.',
                ];
            }

            // Check the submitted 6-digit code against the stored bcrypt hash
            if (! Hash::check($cleanCode, $codeRecord->code_hash)) {
                $newAttempts = $codeRecord->attempts + 1;
                $updates = ['attempts' => $newAttempts];

                if ($newAttempts >= self::MAX_ATTEMPTS) {
                    $updates['invalidated_at'] = now();
                }

                $codeRecord->update($updates);
                RateLimiter::hit($verifyAbuseKey, 600);

                $remaining = max(0, self::MAX_ATTEMPTS - $newAttempts);

                if ($newAttempts >= self::MAX_ATTEMPTS) {
                    AuditLogger::log('email_verification.otp_exhausted', $lockedUser, [], [
                        'email' => $currentEmail,
                        'code_id' => $codeRecord->id,
                        'attempts' => $newAttempts,
                        'ip' => $ipAddress,
                    ]);

                    return [
                        'verified' => false,
                        'status' => 'attempts_exhausted',
                        'remaining_attempts' => 0,
                        'message' => 'Maximum verification attempts (5) reached. This code has been invalidated. Please request a new code.',
                    ];
                }

                return [
                    'verified' => false,
                    'status' => 'invalid_code',
                    'remaining_attempts' => $remaining,
                    'message' => "Invalid verification code. You have {$remaining} ".($remaining === 1 ? 'attempt' : 'attempts').' remaining.',
                ];
            }

            // Valid OTP! Consume the code, invalidate any other open records, and mark email verified.
            $now = now();
            $codeRecord->update([
                'consumed_at' => $now,
            ]);

            EmailVerificationCode::where('user_id', $lockedUser->id)
                ->where('id', '!=', $codeRecord->id)
                ->whereNull('consumed_at')
                ->whereNull('invalidated_at')
                ->update(['invalidated_at' => $now]);

            $lockedUser->forceFill([
                'email_verified_at' => $now,
            ])->save();

            AuditLogger::log('email_verification.verified', $lockedUser, [], [
                'email' => $currentEmail,
                'code_id' => $codeRecord->id,
                'ip' => $ipAddress,
            ]);

            return [
                'verified' => true,
                'status' => 'verified',
                'message' => 'Your email address has been verified successfully!',
                'user' => $lockedUser,
            ];
        });

        if ($outcome['verified']) {
            $verifiedUser = $outcome['user'] ?? $user->fresh();
            event(new Verified($verifiedUser));

            // Automatically evaluate profile completion rewards in case verifying email completes 100% of the profile
            try {
                app(ProfileCompletionService::class)->evaluateAndAwardIfComplete($verifiedUser, $verifiedUser);
            } catch (Throwable $e) {
                Log::warning('Profile completion evaluation after email verification encountered an issue: '.$e->getMessage(), [
                    'user_id' => $verifiedUser->id,
                ]);
            }
        }

        return $outcome;
    }

    /**
     * Handle an email address change: invalidate all previous OTPs, mark email unverified, and issue a new OTP.
     */
    public function handleEmailAddressChanged(User $user, ?string $previousEmail = null, ?string $ipAddress = null): array
    {
        $this->invalidateActiveCodes($user);

        if ($user->email_verified_at !== null) {
            $user->forceFill(['email_verified_at' => null])->save();
        }

        AuditLogger::log('email_verification.email_changed_reset', $user, [
            'email' => $previousEmail,
        ], [
            'email' => $user->email,
            'ip' => $ipAddress,
        ]);

        return $this->issueAndSend($user, $ipAddress, enforceCooldown: false);
    }

    private function resolveStudentName(User $user): string
    {
        $user->loadMissing('student');
        if ($user->student) {
            $full = trim(($user->student->first_name ?? '').' '.($user->student->last_name ?? ''));
            if ($full !== '') {
                return $full;
            }
        }

        $name = trim((string) $user->name);

        return $name !== '' ? $name : 'COMSOC Member';
    }

    public function issueRateLimitKey(User $user, ?string $ipAddress = null): string
    {
        return 'otp-issue:'.$user->id.'|'.($ipAddress ?? '0.0.0.0');
    }

    public function verifyRateLimitKey(User $user, ?string $ipAddress = null): string
    {
        return 'otp-verify:'.$user->id.'|'.($ipAddress ?? '0.0.0.0');
    }
}

