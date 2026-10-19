<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'bio',
        'interests',
        'tech_stack',
        'github_url',
        'linkedin_url',
        'collaboration_preferences',
        'project_categories',
        'is_public',
        'visibility',
        'open_to_collaborate',
    ];

    protected $casts = [
        'interests' => 'array',
        'tech_stack' => 'array',
        'collaboration_preferences' => 'array',
        'project_categories' => 'array',
        'is_public' => 'boolean',
        'open_to_collaborate' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
