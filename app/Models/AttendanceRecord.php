<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'event_id',
        'attendance_session_id',
        'qr_token',
        'participant_type',
        'participant_id',
        'action',
        'status',
        'kiosk_id',
        'scanned_by',
        'scanned_at',
        'is_corrected',
        'corrected_by',
        'corrected_at',
        'correction_reason',
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
        'corrected_at' => 'datetime',
        'is_corrected' => 'boolean',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function attendanceSession(): BelongsTo
    {
        return $this->belongsTo(AttendanceSession::class);
    }

    public function kiosk(): BelongsTo
    {
        return $this->belongsTo(Kiosk::class);
    }

    public function scannedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scanned_by');
    }

    public function correctedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corrected_by');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'participant_id');
    }

    public function eventRegistration(): BelongsTo
    {
        return $this->belongsTo(EventRegistration::class, 'participant_id');
    }
}
