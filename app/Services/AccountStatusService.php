<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserSession;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Super-Admin-only suspension, reactivation, soft-deletion and restoration of student accounts.
 * Never hard-deletes and never touches historical records (attendance, activities, EXP, Points, redemptions).
 */
class AccountStatusService
{
    public function suspend(User $target, User $actor, string $reason): User
    {
        $cleanReason = trim($reason);
        if ($cleanReason === '') {
            throw new InvalidArgumentException('A suspension reason is required.');
        }

        return $this->transition($target, $actor, 'account.suspended', $cleanReason, function (User $u) use ($actor, $cleanReason) {
            if ($u->trashed()) {
                throw new InvalidArgumentException('A deleted account must be restored before it can be suspended.');
            }
            if ($u->isSuspended()) {
                throw new InvalidArgumentException('This account is already suspended.');
            }

            $u->forceFill([
                'is_active' => false,
                'suspended_at' => now(),
                'suspended_by' => $actor->id,
                'suspension_reason' => $cleanReason,
            ])->save();

            $this->terminateAccess($u);
        });
    }

    public function reactivate(User $target, User $actor, ?string $reason = null): User
    {
        $cleanReason = $reason !== null && trim($reason) !== '' ? trim($reason) : 'Account reactivated by Super Administrator.';

        return $this->transition($target, $actor, 'account.reactivated', $cleanReason, function (User $u) use ($actor) {
            if ($u->trashed()) {
                throw new InvalidArgumentException('A deleted account must be restored before it can be reactivated.');
            }
            if (! $u->isSuspended() && $u->is_active) {
                throw new InvalidArgumentException('Only suspended or inactive accounts can be reactivated.');
            }

            // Invalidated sessions and tokens are NOT restored; student must log in again.
            $u->forceFill([
                'is_active' => true,
                'suspended_at' => null,
                'suspended_by' => null,
                'suspension_reason' => null,
                'reactivated_at' => now(),
                'reactivated_by' => $actor->id,
            ])->save();
        });
    }

    public function delete(User $target, User $actor, string $reason): User
    {
        $cleanReason = trim($reason);
        if ($cleanReason === '') {
            throw new InvalidArgumentException('A deletion reason is required.');
        }

        return $this->transition($target, $actor, 'account.soft_deleted', $cleanReason, function (User $u) use ($actor, $cleanReason) {
            if ($u->trashed()) {
                throw new InvalidArgumentException('This account is already deleted.');
            }

            $u->forceFill([
                'is_active' => false,
                'deleted_by' => $actor->id,
                'deletion_reason' => $cleanReason,
            ])->save();

            $this->terminateAccess($u);
            $u->delete(); // Laravel SoftDelete only
        });
    }

    public function restore(User $target, User $actor, ?string $reason = null): User
    {
        $cleanReason = $reason !== null && trim($reason) !== '' ? trim($reason) : 'Account restored by Super Administrator.';

        return $this->transition($target, $actor, 'account.restored', $cleanReason, function (User $u) use ($actor) {
            if (! $u->trashed()) {
                throw new InvalidArgumentException('Only soft-deleted accounts can be restored.');
            }

            $u->restore();

            // Restore active database record while keeping old sessions/tokens invalidated
            $u->forceFill([
                'is_active' => true,
                'suspended_at' => null,
                'suspended_by' => null,
                'suspension_reason' => null,
                'reactivated_at' => now(),
                'reactivated_by' => $actor->id,
                'deleted_by' => null,
                'deletion_reason' => null,
                'active_session_hash' => null,
                'remember_token' => Str::random(60),
            ])->save();
        });
    }

    /**
     * Acquires a row lock, enforces administrative safeguards, executes the state transition, and writes an AuditLog record.
     */
    private function transition(User $target, User $actor, string $action, ?string $reason, callable $change): User
    {
        $this->authorize($actor, $target);

        return DB::transaction(function () use ($target, $actor, $action, $reason, $change) {
            /** @var User $locked */
            $locked = User::withTrashed()->whereKey($target->getKey())->lockForUpdate()->firstOrFail();

            $this->authorize($actor, $locked);

            $previousStatus = $locked->accountStatus();
            $oldIsActive = (bool) $locked->is_active;

            $change($locked);
            $locked->refresh();

            $newStatus = $locked->accountStatus();

            $auditLog = AuditLogger::log(
                action: $action,
                auditable: $locked,
                oldValues: [
                    'account_status' => $previousStatus,
                    'is_active' => $oldIsActive,
                ],
                newValues: [
                    'account_status' => $newStatus,
                    'is_active' => (bool) $locked->is_active,
                    'target_user_id' => $locked->id,
                    'administrator_id' => $actor->id,
                ],
                reason: $reason
            );

            if ($auditLog->user_id !== $actor->id) {
                $auditLog->update(['user_id' => $actor->id]);
            }

            return $locked;
        });
    }

    private function authorize(User $actor, User $target): void
    {
        if (! $actor->isSuperAdmin()) {
            throw new AuthorizationException('Unauthorized: Only Super Administrators can suspend, reactivate, delete, or restore accounts.');
        }

        if ((string) $actor->id === (string) $target->id) {
            throw new AuthorizationException('You cannot suspend or delete your own account.');
        }

        if ($target->role === 'super_admin') {
            $otherActiveSuperAdmins = User::where('role', 'super_admin')
                ->where('is_active', true)
                ->where('id', '!=', $target->id)
                ->count();

            if ($otherActiveSuperAdmins === 0) {
                throw new AuthorizationException('Cannot suspend or delete the last active Super Admin account.');
            }

            throw new AuthorizationException('Super Admin accounts cannot be suspended or deleted through student account management.');
        }

        if ($target->role !== 'student') {
            throw new AuthorizationException('Only student accounts can be suspended or deleted through student account management.');
        }
    }

    /**
     * Invalidate all active sessions, device sessions, and remember-me tokens.
     */
    public function terminateAccess(User $user): void
    {
        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        if (Schema::hasTable('user_sessions')) {
            UserSession::where('user_id', $user->id)
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);
        }

        if (method_exists($user, 'tokens')) {
            $user->tokens()->delete();
        }

        $user->forceFill([
            'active_session_hash' => null,
            'remember_token' => Str::random(60),
        ])->save();
    }
}
