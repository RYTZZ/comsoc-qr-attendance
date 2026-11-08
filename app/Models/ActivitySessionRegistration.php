<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ActivitySessionRegistration extends Model
{
    use HasFactory, HasUuids;

    public const STATUSES = [
        'pending',
        'approved',
        'rejected',
        'waitlisted',
        'cancelled',
        'withdrawn',
    ];

    public const ATTENDANCE_STATUSES = [
        'not_recorded',
        'checked_in',
        'checked_out',
        'present',
        'late',
        'absent',
    ];

    public const COMPLETION_STATUSES = [
        'not_started',
        'registered',
        'attended',
        'completed',
        'incomplete',
        'absent',
    ];

    protected $fillable = [
        'activity_id',
        'activity_session_id',
        'activity_registration_id',
        'user_id',
        'activity_team_id',
        'registration_form_id',
        'form_version',
        'form_snapshot',
        'status',
        'attendance_status',
        'completion_status',
        'waitlist_position',
        'organizer_verified',
        'organizer_verified_by',
        'organizer_verified_at',
        'output_submitted',
        'output_notes',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
        'rewards_awarded',
        'exp_awarded',
        'points_awarded',
        'rewarded_at',
    ];

    protected $casts = [
        'form_version' => 'integer',
        'form_snapshot' => 'array',
        'waitlist_position' => 'integer',
        'organizer_verified' => 'boolean',
        'organizer_verified_at' => 'datetime',
        'output_submitted' => 'boolean',
        'reviewed_at' => 'datetime',
        'rewards_awarded' => 'boolean',
        'exp_awarded' => 'integer',
        'points_awarded' => 'integer',
        'rewarded_at' => 'datetime',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'activity_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ActivitySession::class, 'activity_session_id');
    }

    public function parentRegistration(): BelongsTo
    {
        return $this->belongsTo(ActivityRegistration::class, 'activity_registration_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(ActivityTeam::class, 'activity_team_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_verified_by');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ActivityRegistrationAnswer::class, 'activity_session_registration_id');
    }

    public function attendance(): HasOne
    {
        return $this->hasOne(ActivityAttendance::class, 'activity_session_registration_id');
    }
}

