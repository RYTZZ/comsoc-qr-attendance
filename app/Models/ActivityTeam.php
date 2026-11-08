<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivityTeam extends Model
{
    use HasFactory, HasUuids;

    public const STATUSES = [
        'forming',
        'ready',
        'submitted',
        'approved',
        'rejected',
        'cancelled',
    ];

    protected $fillable = [
        'activity_id',
        'captain_user_id',
        'name',
        'project_title',
        'project_description',
        'track',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'activity_id');
    }

    public function captain(): BelongsTo
    {
        return $this->belongsTo(User::class, 'captain_user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function members(): HasMany
    {
        return $this->hasMany(ActivityTeamMember::class, 'activity_team_id');
    }

    public function acceptedMembers(): HasMany
    {
        return $this->hasMany(ActivityTeamMember::class, 'activity_team_id')
            ->where('invitation_status', 'accepted');
    }

    public function pendingMembers(): HasMany
    {
        return $this->hasMany(ActivityTeamMember::class, 'activity_team_id')
            ->where('invitation_status', 'pending');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(ActivityRegistration::class, 'activity_team_id');
    }
}

