<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberSeasonStat extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'season_id',
        'season_xp',
        'redeemable_points',
        'current_level',
    ];

    protected $casts = [
        'season_xp' => 'integer',
        'redeemable_points' => 'integer',
        'current_level' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }
}
