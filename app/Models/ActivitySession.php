<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActivitySession extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public const STATUSES = [
        'scheduled',
        'open',
        'ongoing',
        'completed',
        'cancelled',
    ];

    protected $fillable = [
        'activity_id',
        'name',
        'description',
        'track_or_category',
        'venue',
        'starts_at',
        'ends_at',
        'registration_opens_at',
        'registration_closes_at',
        'capacity',
        'waitlist_enabled',
        'is_registration_open',
        'requires_approval',
        'allow_walk_in',
        'registration_form_id',
        'form_version',
        'form_snapshot',
        'attendance_enabled',
        'qr_attendance_enabled',
        'require_check_out',
        'late_threshold_minutes',
        'min_attendance_minutes',
        'min_attendance_percentage',
        'require_organizer_verification',
        'require_output_submission',
        'exp_reward',
        'points_reward',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'registration_opens_at' => 'datetime',
        'registration_closes_at' => 'datetime',
        'capacity' => 'integer',
        'waitlist_enabled' => 'boolean',
        'is_registration_open' => 'boolean',
        'requires_approval' => 'boolean',
        'allow_walk_in' => 'boolean',
        'form_version' => 'integer',
        'form_snapshot' => 'array',
        'attendance_enabled' => 'boolean',
        'qr_attendance_enabled' => 'boolean',
        'require_check_out' => 'boolean',
        'late_threshold_minutes' => 'integer',
        'min_attendance_minutes' => 'integer',
        'min_attendance_percentage' => 'integer',
        'require_organizer_verification' => 'boolean',
        'require_output_submission' => 'boolean',
        'exp_reward' => 'integer',
        'points_reward' => 'integer',
        'sort_order' => 'integer',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'activity_id');
    }

    public function registrationForm(): BelongsTo
    {
        return $this->belongsTo(RegistrationForm::class, 'registration_form_id');
    }

    public function sessionRegistrations(): HasMany
    {
        return $this->hasMany(ActivitySessionRegistration::class, 'activity_session_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(ActivityAttendance::class, 'activity_session_id');
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(ActivityReward::class, 'activity_session_id');
    }

    public function activeRegistrationsCount(): int
    {
        return $this->sessionRegistrations()
            ->whereIn('status', ['pending', 'approved'])
            ->count();
    }

    public function approvedRegistrationsCount(): int
    {
        return $this->sessionRegistrations()
            ->where('status', 'approved')
            ->count();
    }

    public function waitlistedCount(): int
    {
        return $this->sessionRegistrations()
            ->where('status', 'waitlisted')
            ->count();
    }

    public function remainingSlots(): ?int
    {
        if ($this->capacity === null) {
            return null;
        }

        return max(0, $this->capacity - $this->activeRegistrationsCount());
    }

    public function isFull(): bool
    {
        if ($this->capacity === null) {
            return false;
        }

        return $this->activeRegistrationsCount() >= $this->capacity;
    }

    public function isAvailableForRegistration(): bool
    {
        if (!$this->is_registration_open || in_array($this->status, ['completed', 'cancelled'], true)) {
            return false;
        }

        $now = now();
        if ($this->registration_opens_at && $now->lt($this->registration_opens_at)) {
            return false;
        }
        if ($this->registration_closes_at && $now->gt($this->registration_closes_at)) {
            return false;
        }

        return true;
    }

    public function overlapsWith(self $other): bool
    {
        if (!$this->starts_at || !$this->ends_at || !$other->starts_at || !$other->ends_at) {
            return false;
        }

        if ($this->id === $other->id) {
            return false;
        }

        return $this->starts_at->lt($other->ends_at) && $other->starts_at->lt($this->ends_at);
    }

    public function getScheduledDurationMinutes(): ?int
    {
        if (!$this->starts_at || !$this->ends_at) {
            return null;
        }

        return max(1, (int) $this->starts_at->diffInMinutes($this->ends_at));
    }

    public function getEffectiveFormSnapshot(): ?array
    {
        if (!empty($this->form_snapshot['fields'])) {
            return $this->form_snapshot;
        }

        if ($this->registrationForm) {
            return $this->registrationForm->getActiveSchema();
        }

        return null;
    }
}

