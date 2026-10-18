<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RaffleWinner extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'raffle_id',
        'raffle_entry_id',
        'user_id',
        'draw_order',
        'points_awarded',
        'is_claimed',
        'claimed_at',
        'claimed_by_staff_id',
        'claim_notes',
    ];

    protected $casts = [
        'draw_order' => 'integer',
        'points_awarded' => 'integer',
        'is_claimed' => 'boolean',
        'claimed_at' => 'datetime',
    ];

    public function raffle(): BelongsTo
    {
        return $this->belongsTo(Raffle::class);
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(RaffleEntry::class, 'raffle_entry_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function claimedByStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claimed_by_staff_id');
    }
}
