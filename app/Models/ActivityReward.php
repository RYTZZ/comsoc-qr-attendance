<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityReward extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'activity_id',
        'activity_session_id',
        'activity_registration_id',
        'activity_session_registration_id',
        'user_id',
        'reward_scope',
        'exp_amount',
        'points_amount',
        'xp_transaction_id',
        'point_transaction_id',
        'idempotency_key',
        'awarded_by',
    ];

    protected $casts = [
        'exp_amount' => 'integer',
        'points_amount' => 'integer',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'activity_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ActivitySession::class, 'activity_session_id');
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(ActivityRegistration::class, 'activity_registration_id');
    }

    public function sessionRegistration(): BelongsTo
    {
        return $this->belongsTo(ActivitySessionRegistration::class, 'activity_session_registration_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function xpTransaction(): BelongsTo
    {
        return $this->belongsTo(XpTransaction::class, 'xp_transaction_id');
    }

    public function pointTransaction(): BelongsTo
    {
        return $this->belongsTo(PointTransaction::class, 'point_transaction_id');
    }

    public function awarder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'awarded_by');
    }
}

