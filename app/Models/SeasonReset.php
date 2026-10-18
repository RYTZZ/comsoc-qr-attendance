<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeasonReset extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'closing_season_id',
        'next_season_id',
        'executed_by',
        'members_affected_count',
        'points_expired_sum',
        'status',
        'details',
        'executed_at',
    ];

    protected $casts = [
        'details' => 'array',
        'executed_at' => 'datetime',
        'members_affected_count' => 'integer',
        'points_expired_sum' => 'integer',
    ];

    public function closingSeason(): BelongsTo
    {
        return $this->belongsTo(Season::class, 'closing_season_id');
    }

    public function nextSeason(): BelongsTo
    {
        return $this->belongsTo(Season::class, 'next_season_id');
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by');
    }
}
