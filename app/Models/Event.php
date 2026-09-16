<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property \Illuminate\Support\Carbon|null $event_date
 * @property \Illuminate\Support\Carbon|null $starts_at
 * @property \Illuminate\Support\Carbon|null $ends_at
 */
class Event extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'academic_year_id',
        'created_by',
        'name',
        'event_type',
        'organizer',
        'contact_info',
        'logo_path',
        'description',
        'location',
        'venue_name',
        'venue_address',
        'venue_details',
        'event_date',
        'starts_at',
        'ends_at',
        'requires_registration',
        'registration_opens_at',
        'registration_deadline',
        'attendance_starts_at',
        'attendance_ends_at',
        'max_participants',
        'allow_non_students',
        'is_published',
        'status',
        'attendance_enabled',
        'snack_distribution_enabled',
    ];

    protected $casts = [
        'event_date' => 'date',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'registration_opens_at' => 'datetime',
        'registration_deadline' => 'datetime',
        'requires_registration' => 'boolean',
        'allow_non_students' => 'boolean',
        'is_published' => 'boolean',
        'attendance_enabled' => 'boolean',
        'snack_distribution_enabled' => 'boolean',
        'max_participants' => 'integer',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attendanceSessions(): HasMany
    {
        return $this->hasMany(AttendanceSession::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function snackSessions(): HasMany
    {
        return $this->hasMany(SnackSession::class);
    }

    public function eventRegistrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    public function getEventDayAttribute(): ?string
    {
        return $this->event_date?->format('l');
    }

    public function getFormattedScheduleDayAttribute(): string
    {
        if (!$this->event_date) {
            return 'Date TBA';
        }
        return $this->event_date->format('F j, Y') . ' — ' . $this->event_date->format('l');
    }

    public function getEffectiveVenueAttribute(): string
    {
        return $this->venue_name ?: ($this->location ?: 'TBA');
    }

    public function getEffectiveStatusAttribute(): string
    {
        if (in_array($this->status, ['completed', 'archived'])) {
            return $this->status;
        }

        if (!$this->is_published || $this->status === 'draft') {
            return 'draft';
        }

        if ($this->ends_at && now()->gt($this->ends_at)) {
            return 'completed';
        }

        if ($this->starts_at && now()->gte($this->starts_at) && (!$this->ends_at || now()->lte($this->ends_at))) {
            return 'ongoing';
        }

        if ($this->event_date && now()->isSameDay($this->event_date)) {
            return 'ongoing';
        }

        if ($this->isRegistrationOpen()) {
            return 'registration_open';
        }

        if ($this->requires_registration) {
            return 'registration_closed';
        }

        return 'ongoing';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->effective_status) {
            'draft' => 'Draft',
            'registration_open' => 'Registration Open',
            'registration_closed' => 'Registration Closed',
            'ongoing' => 'Ongoing',
            'completed' => 'Completed',
            'archived' => 'Archived',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->effective_status) {
            'registration_open', 'ongoing' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
            'registration_closed' => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
            'completed' => 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20',
            'archived' => 'bg-slate-500/10 text-slate-400 border-slate-500/20',
            default => 'bg-slate-700/20 text-slate-400 border-slate-700/30',
        };
    }

    public function getParticipantCountAttribute(): int
    {
        return $this->eventRegistrations()->whereIn('status', ['pending', 'approved'])->count();
    }

    public function isFull(): bool
    {
        if (!$this->max_participants || $this->max_participants <= 0) {
            return false;
        }
        return $this->participant_count >= $this->max_participants;
    }

    public function isRegistrationOpen(): bool
    {
        if (!$this->is_published || in_array($this->status, ['draft', 'archived', 'completed', 'registration_closed'])) {
            return false;
        }

        if ($this->registration_opens_at && now()->lt($this->registration_opens_at)) {
            return false;
        }

        if ($this->isFull()) {
            return false;
        }

        $deadline = $this->effectiveRegistrationDeadline();
        if ($deadline && now()->gte($deadline)) {
            return false;
        }

        return true;
    }

    public function effectiveRegistrationDeadline(): ?\Illuminate\Support\Carbon
    {
        if ($this->registration_deadline) {
            return $this->registration_deadline;
        }

        if ($this->starts_at) {
            return $this->starts_at;
        }

        if ($this->event_date) {
            return $this->event_date->copy()->endOfDay();
        }

        return null;
    }

    public function getRegistrationStatusAttribute(): string
    {
        if (!$this->is_published || $this->status === 'draft') {
            return 'Registration Not Open';
        }

        if ($this->status === 'archived' || $this->status === 'completed') {
            return 'Registration Closed';
        }

        if ($this->registration_opens_at && now()->lt($this->registration_opens_at)) {
            return 'Registration Opening Soon';
        }

        if ($this->isFull()) {
            return 'Registration Full';
        }

        $deadline = $this->effectiveRegistrationDeadline();
        if ($deadline && now()->gte($deadline)) {
            return 'Registration Closed';
        }

        if ($this->status === 'registration_closed') {
            return 'Registration Closed';
        }

        if ($deadline && now()->diffInHours($deadline, false) <= 24 && now()->lt($deadline)) {
            return 'Closing Soon';
        }

        return 'Registration Open';
    }

    public function isEnded(): bool
    {
        return ($this->ends_at && now()->gt($this->ends_at)) || $this->status === 'completed';
    }
}
