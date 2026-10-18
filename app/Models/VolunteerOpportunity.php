<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VolunteerOpportunity extends Model
{
    use HasFactory, HasUuids;

    public const CATEGORIES = [
        'ushering' => 'Event Ushering',
        'tech_support' => 'Technical Support & AV',
        'registration' => 'Registration & Attendance Pass Desk',
        'multimedia' => 'Multimedia, Photo & Video Documentation',
        'publicity' => 'Publicity, Social Media & Design',
        'logistics' => 'Logistics & Physical Setup',
        'workshop' => 'Workshop Facilitation & Teaching Assistant',
        'project_dev' => 'Project Development & IT Support',
        'community' => 'Community Service & Tech Outreach',
    ];

    public const STATUSES = [
        'open' => 'Open for Applications',
        'filled' => 'Capacity Filled',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

    protected $fillable = [
        'title',
        'description',
        'event_id',
        'category',
        'required_skills',
        'responsibilities',
        'location_type',
        'location_details',
        'starts_at',
        'ends_at',
        'application_deadline',
        'capacity',
        'expected_hours',
        'eligibility_requirements',
        'coordinator_id',
        'status',
        'is_active',
        'exp_reward_per_hour',
    ];

    protected $casts = [
        'required_skills' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'application_deadline' => 'datetime',
        'capacity' => 'integer',
        'expected_hours' => 'decimal:2',
        'is_active' => 'boolean',
        'exp_reward_per_hour' => 'integer',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinator_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(VolunteerApplication::class, 'volunteer_opportunity_id');
    }

    public function acceptedApplications(): HasMany
    {
        return $this->hasMany(VolunteerApplication::class, 'volunteer_opportunity_id')
            ->whereIn('status', ['accepted', 'completed']);
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst($this->category);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function isExpired(): bool
    {
        return $this->application_deadline && $this->application_deadline->isPast();
    }
}
