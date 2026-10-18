<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeasonLeaderboardSnapshot extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'season_id',
        'user_id',
        'final_rank',
        'final_xp',
        'final_points',
        'final_level',
        'snapshot_at',
    ];

    protected $casts = [
        'snapshot_at' => 'datetime',
        'final_rank' => 'integer',
        'final_xp' => 'integer',
        'final_points' => 'integer',
        'final_level' => 'integer',
    ];

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
