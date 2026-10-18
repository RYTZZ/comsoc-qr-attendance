<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectApplication extends Model
{
    use HasFactory, HasUuids;

    public const STATUSES = [
        'pending' => 'Pending Review',
        'under_review' => 'Under Review',
        'accepted' => 'Accepted',
        'declined' => 'Declined',
        'withdrawn' => 'Withdrawn',
    ];

    protected $fillable = [
        'project_id',
        'user_id',
        'preferred_role',
        'motivation',
        'skills_summary',
        'status',
        'feedback',
        'decided_at',
        'decided_by',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(CollaborationProject::class, 'project_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function decidedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
