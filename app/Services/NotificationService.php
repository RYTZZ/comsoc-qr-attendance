<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    public static function send(User|string $user, string $type, string $title, string $body, ?string $targetUrl = null): Notification
    {
        $userId = $user instanceof User ? $user->id : $user;

        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'target_url' => $targetUrl,
        ]);
    }

    public static function broadcast(iterable $users, string $type, string $title, string $body, ?string $targetUrl = null): void
    {
        foreach ($users as $user) {
            self::send($user, $type, $title, $body, $targetUrl);
        }
    }
}
