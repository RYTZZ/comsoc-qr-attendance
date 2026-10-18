<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotificationPreference extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'notify_expiration_7d',
        'notify_expiration_1d',
        'notify_redemption',
        'notify_achievements',
        'notify_events',
    ];

    protected $casts = [
        'notify_expiration_7d' => 'boolean',
        'notify_expiration_1d' => 'boolean',
        'notify_redemption' => 'boolean',
        'notify_achievements' => 'boolean',
        'notify_events' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
