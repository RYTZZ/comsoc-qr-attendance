<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Raffle extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'event_id',
        'created_by',
        'title',
        'description',
        'prize_name',
        'points_bonus',
        'winner_count',
        'reward_id',
        'entry_type',
        'points_per_entry',
        'max_entries_per_user',
        'status',
        'drawn_at',
        'drawn_by',
    ];

    protected $casts = [
        'points_bonus' => 'integer',
        'winner_count' => 'integer',
        'points_per_entry' => 'integer',
        'max_entries_per_user' => 'integer',
        'drawn_at' => 'datetime',
    ];

    public function reward(): BelongsTo
    {
        return $this->belongsTo(Reward::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function drawer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'drawn_by');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(RaffleEntry::class);
    }

    public function winners(): HasMany
    {
        return $this->hasMany(RaffleWinner::class);
    }

    public function isDrawn(): bool
    {
        return $this->status === 'drawn';
    }
}
