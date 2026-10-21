<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UsernameRule implements ValidationRule
{
    public const RESERVED_USERNAMES = [
        'admin',
        'administrator',
        'superadmin',
        'super_admin',
        'super-admin',
        'root',
        'system',
        'official',
        'comsoc',
        'comsoc_admin',
        'comsocadmin',
        'president',
        'vice_president',
        'vicepresident',
        'secretary',
        'treasurer',
        'auditor',
        'officer',
        'staff',
        'kiosk',
        'support',
        'helpdesk',
        'moderator',
        'help',
        'api',
        'null',
        'undefined',
    ];

    public function __construct(protected ?string $ignoreUserId = null)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('The username must be a string.');
            return;
        }

        $trimmed = trim($value);

        if (strlen($trimmed) < 3 || strlen($trimmed) > 20) {
            $fail('The username must be between 3 and 20 characters.');
            return;
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $trimmed)) {
            $fail('The username may only contain letters, numbers, and underscores.');
            return;
        }

        $lower = strtolower($trimmed);

        if (in_array($lower, self::RESERVED_USERNAMES, true)) {
            $fail('This username is reserved for system roles and cannot be used.');
            return;
        }

        $existsQuery = User::whereRaw('LOWER(username) = ?', [$lower]);
        if ($this->ignoreUserId) {
            $existsQuery->where('id', '!=', $this->ignoreUserId);
        }

        if ($existsQuery->exists()) {
            $fail('This username is already taken. Please choose another username.');
            return;
        }
    }
}
