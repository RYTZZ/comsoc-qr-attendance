<?php

namespace App\Policies;

use App\Models\StudentSignature;
use App\Models\User;

class StudentSignaturePolicy
{
    /** Only the owner may view/preview their signature through the member-facing endpoints. */
    public function view(User $user, StudentSignature $signature): bool
    {
        return $user->id === $signature->user_id;
    }
}
