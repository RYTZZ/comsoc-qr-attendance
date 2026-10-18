<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RaffleEntry extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'raffle_id',
        'user_id',
        'attendance_record_id',
        'source_type',
        'points_deducted',
        'eligible_at',
    ];

    protected $casts = [
        'points_deducted' => 'integer',
        'eligible_at' => 'datetime',
    ];

    public function raffle(): BelongsTo
    {
        return $this->belongsTo(Raffle::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class);
    }

    public function winner(): HasOne
    {
        return $this->hasOne(RaffleWinner::class);
    }
}
