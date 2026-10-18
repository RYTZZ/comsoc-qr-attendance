<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Announcement extends Model
{
    use HasFactory, HasUuids;

    public const PRIORITIES = [
        'low' => 'Low',
        'normal' => 'Normal',
        'high' => 'High',
        'urgent' => 'Urgent',
    ];

    public const CATEGORIES = [
        'general' => 'Official COMSOC Announcement',
        'events' => 'Event Update',
        'registration' => 'Registration Deadline',
        'volunteer' => 'Volunteer Opportunity',
        'gamification' => 'XP & Season Update',
        'technical' => 'Technical Notice',
        'reward' => 'Reward Collection Reminder',
        'urgent' => 'Urgent Notice',
    ];

    protected $fillable = [
        'title',
        'body',
        'priority',
        'category',
        'target_url',
        'target_audience_type',
        'target_audience_values',
        'requires_acknowledgment',
        'is_pinned',
        'event_id',
        'published_at',
        'expires_at',
        'is_active',
        'posted_by',
    ];

    protected $casts = [
        'target_audience_values' => 'array',
        'requires_acknowledgment' => 'boolean',
        'is_pinned' => 'boolean',
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(AnnouncementReceipt::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function isAudienceEligible(User $user): bool
    {
        if ($this->target_audience_type === 'all' || empty($this->target_audience_type)) {
            return true;
        }

        $values = $this->target_audience_values ?? [];
        if (empty($values)) {
            return true;
        }

        if ($this->target_audience_type === 'role') {
            return in_array($user->role, $values);
        }

        $student = $user->student;
        if (!$student) {
            return false;
        }

        if ($this->target_audience_type === 'program') {
            return in_array($student->program, $values);
        }

        if ($this->target_audience_type === 'year_level') {
            return in_array((string)$student->year_level, array_map('strval', $values));
        }

        return true;
    }
}
