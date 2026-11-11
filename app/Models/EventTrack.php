<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventTrack extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'event_id',
        'title',
        'track_category',
        'description',
        'speaker_name',
        'room_or_venue',
        'starts_at',
        'ends_at',
        'capacity_limit',
        'exp_points',
        'is_active',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
        'capacity_limit' => 'integer',
        'exp_points' => 'integer',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(EventTrackAttendance::class);
    }

    public function isFull(): bool
    {
        if ($this->capacity_limit === null) {
            return false;
        }

        return $this->attendances()->whereIn('status', ['registered', 'attended'])->count() >= $this->capacity_limit;
    }

    public function seatsRemaining(): ?int
    {
        if ($this->capacity_limit === null) {
            return null;
        }

        $taken = $this->attendances()->whereIn('status', ['registered', 'attended'])->count();
        return max(0, $this->capacity_limit - $taken);
    }
}

