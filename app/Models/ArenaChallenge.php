<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArenaChallenge extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'title',
        'slug',
        'difficulty',
        'category',
        'description_markdown',
        'sample_input',
        'sample_output',
        'time_limit_ms',
        'memory_limit_mb',
        'exp_reward',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'time_limit_ms' => 'integer',
        'memory_limit_mb' => 'integer',
        'exp_reward' => 'integer',
    ];

    public function testCases(): HasMany
    {
        return $this->hasMany(ArenaTestCase::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(ArenaSubmission::class);
    }
}

