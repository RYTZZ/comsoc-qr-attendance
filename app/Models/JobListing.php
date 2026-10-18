<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobListing extends Model
{
    use HasFactory, HasUuids;

    public const EMPLOYMENT_TYPES = [
        'Internship',
        'Full-Time',
        'Part-Time',
        'Freelance / Contract',
        'Fellowship',
    ];

    protected $fillable = [
        'title',
        'company',
        'job_type',
        'location',
        'is_remote',
        'compensation',
        'description',
        'required_skills',
        'source_url',
        'application_url',
        'deadline_at',
        'is_active',
        'is_verified',
        'posted_by',
    ];

    protected $casts = [
        'is_remote' => 'boolean',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'required_skills' => 'array',
        'deadline_at' => 'datetime',
    ];

    public function bookmarks(): HasMany
    {
        return $this->hasMany(JobBookmark::class);
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function isExpired(): bool
    {
        return $this->deadline_at && $this->deadline_at->isPast();
    }
}
