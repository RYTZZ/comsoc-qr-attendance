<?php

namespace App\Console\Commands;

use App\Models\EmailVerificationCode;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Console\Command;

/**
 * Audits accounts whose email_verified_at was stamped without a successful OTP validation
 * (legacy default-to-now behaviour) and optionally forces them through OTP re-verification.
 */
class AuditEmailVerificationCommand extends Command
{
    protected $signature = 'email:audit-verification
        {--apply : Reset unproven verifications to unverified (default is a read-only dry run)}
        {--include-staff : Also reset super_admin/admin/officer accounts (skipped by default to avoid lock-out)}';

    protected $description = 'Find accounts marked email-verified without a consumed OTP and require them to re-verify.';

    private const STAFF_ROLES = ['super_admin', 'admin', 'officer'];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $includeStaff = (bool) $this->option('include-staff');

        $proven = 0;
        $reset = 0;
        $skippedStaff = 0;

        User::query()->whereNotNull('email_verified_at')->orderBy('id')->chunk(200, function ($users) use ($apply, $includeStaff, &$proven, &$reset, &$skippedStaff) {
            foreach ($users as $user) {
                $hasProof = EmailVerificationCode::where('user_id', $user->id)
                    ->where('email', strtolower(trim((string) $user->email)))
                    ->whereNotNull('consumed_at')
                    ->exists();

                if ($hasProof) {
                    $proven++;

                    continue;
                }

                if (! $includeStaff && in_array($user->role, self::STAFF_ROLES, true)) {
                    $skippedStaff++;
                    $this->line("  [staff skipped] {$user->email}");

                    continue;
                }

                $reset++;
                $this->line(($apply ? '  [reset] ' : '  [would reset] ').$user->email);

                if ($apply) {
                    $user->forceFill(['email_verified_at' => null])->saveQuietly();
                    AuditLogger::log('email_verification.legacy_reset', $user, [], [
                        'reason' => 'No successful OTP validation on record; re-verification required.',
                    ]);
                }
            }
        });

        $this->info("OTP-proven: {$proven} | ".($apply ? 'Reset' : 'Would reset').": {$reset} | Staff skipped: {$skippedStaff}");
        if (! $apply) {
            $this->comment('Dry run only. Re-run with --apply to enforce re-verification.');
        }

        return self::SUCCESS;
    }
}

