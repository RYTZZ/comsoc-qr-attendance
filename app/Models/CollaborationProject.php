<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CollaborationProject extends Model
{
    use HasFactory, HasUuids;

    public const CATEGORIES = [
        'web_dev' => 'Web Development',
        'mobile' => 'Mobile Applications',
        'ui_ux' => 'UI/UX Design',
        'cybersecurity' => 'Cybersecurity',
        'networking' => 'Networking',
        'data_analytics' => 'Data Analytics',
        'ai' => 'Artificial Intelligence',
        'iot' => 'IoT & Hardware',
        'entrepreneurship' => 'Entrepreneurship',
        'community' => 'Community Technology Solutions',
    ];

    public const STAGES = [
        'idea' => 'Idea / Concept',
        'prototype' => 'Prototype',
        'mvp' => 'MVP',
        'active_development' => 'Active Development',
        'completed' => 'Completed',
    ];

    public const STATUSES = [
        'recruiting' => 'Recruiting',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'archived' => 'Archived',
        'moderated' => 'Moderated / Hidden',
    ];

    protected $fillable = [
        'owner_id',
        'title',
        'problem_statement',
        'description',
        'category',
        'skills_needed',
        'preferred_roles',
        'team_size_target',
        'stage',
        'expected_commitment',
        'repository_url',
        'demo_url',
        'application_deadline',
        'status',
        'is_recruiting',
        'moderation_notes',
        'moderated_by',
    ];

    protected $casts = [
        'skills_needed' => 'array',
        'preferred_roles' => 'array',
        'team_size_target' => 'integer',
        'is_recruiting' => 'boolean',
        'application_deadline' => 'datetime',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    public function members(): HasMany
    {
        return $this->hasMany(ProjectMember::class, 'project_id');
    }

    public function activeMembers(): HasMany
    {
        return $this->hasMany(ProjectMember::class, 'project_id')->where('is_active', true);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(ProjectApplication::class, 'project_id');
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(ProjectMilestone::class, 'project_id')->orderBy('due_date', 'asc');
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst($this->category);
    }

    public function getStageLabelAttribute(): string
    {
        return self::STAGES[$this->stage] ?? ucfirst($this->stage);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function isOwner(User $user): bool
    {
        return $this->owner_id === $user->id;
    }

    public function isMember(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->where('is_active', true)->exists();
    }
}
