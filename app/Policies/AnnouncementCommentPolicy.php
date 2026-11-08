<?php

namespace App\Policies;

use App\Models\AnnouncementComment;
use App\Models\User;

class AnnouncementCommentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, AnnouncementComment $comment): bool
    {
        return $comment->status === AnnouncementComment::STATUS_ACTIVE || $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->is_active ?? true;
    }

    public function update(User $user, AnnouncementComment $comment): bool
    {
        return $user->id === $comment->user_id;
    }

    public function delete(User $user, AnnouncementComment $comment): bool
    {
        return $user->id === $comment->user_id || $user->isAdmin();
    }

    public function moderate(User $user, AnnouncementComment $comment): bool
    {
        return $user->isAdmin();
    }

    public function report(User $user, AnnouncementComment $comment): bool
    {
        return $user->id !== $comment->user_id;
    }
}

