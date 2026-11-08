<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ActivityRegistration extends Model
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
        'user_id',
        'activity_team_id',
        'registration_type',
        'registration_form_id',
        'form_version',
        'form_snapshot',
        'ticket_code',
        'status',
        'completion_status',
        'waitlist_position',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
        'cancelled_at',
        'withdrawn_at',
        'parent_rewards_awarded',
        'parent_exp_awarded',
        'parent_points_awarded',
        'parent_rewarded_at',
    ];

    protected $casts = [
        'form_version' => 'integer',
        'form_snapshot' => 'array',
        'waitlist_position' => 'integer',
        'reviewed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'withdrawn_at' => 'datetime',
        'parent_rewards_awarded' => 'boolean',
        'parent_exp_awarded' => 'integer',
        'parent_points_awarded' => 'integer',
        'parent_rewarded_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $reg) {
            if (empty($reg->ticket_code)) {
                $reg->ticket_code = 'ACT-' . strtoupper(Str::random(10));
            }
        });
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'activity_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(ActivityTeam::class, 'activity_team_id');
    }

    public function registrationForm(): BelongsTo
    {
        return $this->belongsTo(RegistrationForm::class, 'registration_form_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function sessionRegistrations(): HasMany
    {
        return $this->hasMany(ActivitySessionRegistration::class, 'activity_registration_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ActivityRegistrationAnswer::class, 'activity_registration_id');
    }

    public function parentAnswers(): HasMany
    {
        return $this->hasMany(ActivityRegistrationAnswer::class, 'activity_registration_id')
            ->whereNull('activity_session_registration_id');
    }
}

