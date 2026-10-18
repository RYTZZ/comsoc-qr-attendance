<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RewardRedemption extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'reward_id',
        'season_id',
        'points_spent',
        'claim_code',
        'item_title_snapshot',
        'status',
        'notes',
        'rejection_reason',
        'fulfilled_at',
        'fulfilled_by',
        'collected_at',
        'collected_by',
    ];

    protected $casts = [
        'points_spent' => 'integer',
        'fulfilled_at' => 'datetime',
        'collected_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reward(): BelongsTo
    {
        return $this->belongsTo(Reward::class);
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function fulfiller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fulfilled_by');
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    public function allocations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ExpAllocation::class, 'reward_redemption_id');
    }
}
