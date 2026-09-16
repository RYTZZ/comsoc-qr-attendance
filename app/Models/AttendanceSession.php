<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceSession extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['event_id', 'type', 'opens_at', 'closes_at', 'late_threshold', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function getLabelAttribute(): string
    {
        return match ($this->type) {
            'morning_in' => 'Morning IN',
            'morning_out' => 'Morning OUT',
            'afternoon_in' => 'Afternoon IN',
            'afternoon_out' => 'Afternoon OUT',
            default => ucwords(str_replace('_', ' ', $this->type)),
        };
    }

    public function isInSession(): bool
    {
        return in_array($this->type, ['morning_in', 'afternoon_in']);
    }

    public function determineStatus(string $scanTime): string
    {
        if (!$this->late_threshold || !$this->isInSession()) {
            return 'present';
        }
        return $scanTime > $this->late_threshold ? 'late' : 'present';
    }
}
