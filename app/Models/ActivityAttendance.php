<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityAttendance extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'activity_id',
        'activity_session_id',
        'activity_session_registration_id',
        'user_id',
        'qr_token_hash',
        'check_in_at',
        'check_out_at',
        'check_in_status',
        'duration_minutes',
        'checked_in_by',
        'checked_out_by',
        'is_walk_in',
        'notes',
    ];

    protected $casts = [
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
        'duration_minutes' => 'integer',
        'is_walk_in' => 'boolean',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'activity_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ActivitySession::class, 'activity_session_id');
    }

    public function sessionRegistration(): BelongsTo
    {
        return $this->belongsTo(ActivitySessionRegistration::class, 'activity_session_registration_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    public function checkedOutBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_out_by');
    }
}

