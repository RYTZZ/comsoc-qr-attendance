<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Achievement extends Model
{
    use HasFactory, HasUuids;

    public const CATEGORIES = [
        'Milestone',
        'Participation',
        'Quests',
        'Community',
        'Honor',
    ];

    public const CRITERIA_TYPES = [
        'event_attendance' => 'Events Attended',
        'mission_completion' => 'Missions Completed',
        'xp_earned' => 'Total Season XP',
        'reward_redemption' => 'Rewards Claimed',
        'custom' => 'Manual / Special Award',
    ];

    protected $fillable = [
        'slug',
        'title',
        'description',
        'icon',
        'category',
        'criteria_type',
        'criteria_threshold',
        'xp_bonus',
        'points_bonus',
        'is_active',
    ];

    protected $casts = [
        'criteria_threshold' => 'integer',
        'xp_bonus' => 'integer',
        'points_bonus' => 'integer',
        'is_active' => 'boolean',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_achievements')->withPivot('unlocked_at')->withTimestamps();
    }
}
