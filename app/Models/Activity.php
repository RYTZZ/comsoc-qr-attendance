<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Activity extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public const CATEGORIES = [
        'Workshop',
        'Multi-Workshop Event',
        'IDEATHON',
        'Hackathon',
        'Seminar',
        'Bootcamp',
        'Competition',
        'Industry Familiarization Tour',
        'General COMSOC Activity',
    ];

    public const STATUSES = [
        'draft',
        'published',
        'registration_open',
        'registration_closed',
        'ongoing',
        'completed',
        'archived',
    ];

    public const REGISTRATION_MODES = [
        'individual',
        'team',
        'both',
    ];

    public const CONFLICT_POLICIES = [
        'block',
        'warn',
        'ignore',
    ];

    public const COMPLETION_MODES = [
        'any_session',
        'all_selected_sessions',
        'minimum_sessions',
        'manual',
    ];

    protected $fillable = [
        'academic_year_id',
        'event_id',
        'title',
        'slug',
        'description',
        'category',
        'banner_path',
        'venue',
        'organizer_name',
        'general_requirements',
        'starts_at',
        'ends_at',
        'registration_opens_at',
        'registration_closes_at',
        'status',
        'is_multi_session',
        'registration_mode',
        'min_team_size',
        'max_team_size',
        'require_all_team_invites_accepted',
        'allow_multiple_sessions',
        'max_sessions_per_student',
        'require_session_selection',
        'allow_session_change',
        'require_separate_session_approval',
        'require_parent_registration',
        'allow_independent_session_registration',
        'conflict_policy',
        'overall_capacity',
        'requires_approval',
        'waitlist_enabled',
        'allow_submission_editing',
        'allow_cancellation',
        'allow_walk_in',
        'eligible_programs',
        'eligible_year_levels',
        'registration_form_id',
        'form_version',
        'form_snapshot',
        'completion_mode',
        'min_sessions_for_completion',
        'award_parent_rewards',
        'parent_exp_reward',
        'parent_points_reward',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'registration_opens_at' => 'datetime',
        'registration_closes_at' => 'datetime',
        'approved_at' => 'datetime',
        'is_multi_session' => 'boolean',
        'min_team_size' => 'integer',
        'max_team_size' => 'integer',
        'require_all_team_invites_accepted' => 'boolean',
        'allow_multiple_sessions' => 'boolean',
        'max_sessions_per_student' => 'integer',
        'require_session_selection' => 'boolean',
        'allow_session_change' => 'boolean',
        'require_separate_session_approval' => 'boolean',
        'require_parent_registration' => 'boolean',
        'allow_independent_session_registration' => 'boolean',
        'overall_capacity' => 'integer',
        'requires_approval' => 'boolean',
        'waitlist_enabled' => 'boolean',
        'allow_submission_editing' => 'boolean',
        'allow_cancellation' => 'boolean',
        'allow_walk_in' => 'boolean',
        'eligible_programs' => 'array',
        'eligible_year_levels' => 'array',
        'form_version' => 'integer',
        'form_snapshot' => 'array',
        'min_sessions_for_completion' => 'integer',
        'award_parent_rewards' => 'boolean',
        'parent_exp_reward' => 'integer',
        'parent_points_reward' => 'integer',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function registrationForm(): BelongsTo
    {
        return $this->belongsTo(RegistrationForm::class, 'registration_form_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ActivitySession::class, 'activity_id')->orderBy('sort_order')->orderBy('starts_at');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(ActivityRegistration::class, 'activity_id');
    }

    public function sessionRegistrations(): HasMany
    {
        return $this->hasMany(ActivitySessionRegistration::class, 'activity_id');
    }

    public function teams(): HasMany
    {
        return $this->hasMany(ActivityTeam::class, 'activity_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(ActivityAttendance::class, 'activity_id');
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(ActivityReward::class, 'activity_id');
    }

    public function organizers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'activity_organizers', 'activity_id', 'user_id')
            ->withPivot('assigned_by')
            ->withTimestamps();
    }

    public function activeRegistrationsCount(): int
    {
        return $this->registrations()
            ->whereIn('status', ['pending', 'approved'])
            ->count();
    }

    public function approvedRegistrationsCount(): int
    {
        return $this->registrations()
            ->where('status', 'approved')
            ->count();
    }

    public function remainingSlots(): ?int
    {
        if ($this->overall_capacity === null) {
            return null;
        }

        return max(0, $this->overall_capacity - $this->activeRegistrationsCount());
    }

    public function isFull(): bool
    {
        if ($this->overall_capacity === null) {
            return false;
        }

        return $this->activeRegistrationsCount() >= $this->overall_capacity;
    }

    public function isRegistrationWindowOpen(): bool
    {
        if (in_array($this->status, ['draft', 'registration_closed', 'completed', 'archived'], true)) {
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

    public function isUserAuthorizedOrganizer(User $user): bool
    {
        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return true;
        }

        if ($this->created_by === $user->id) {
            return true;
        }

        return $this->organizers()->where('users.id', $user->id)->exists();
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

