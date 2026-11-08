<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityTeamMember extends Model
{
    use HasFactory, HasUuids;

    public const INVITATION_STATUSES = [
        'pending',
        'accepted',
        'rejected',
        'removed',
    ];

    protected $fillable = [
        'activity_team_id',
        'activity_id',
        'user_id',
        'role',
        'invitation_status',
        'invited_by',
        'responded_at',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(ActivityTeam::class, 'activity_team_id');
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'activity_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }
}

