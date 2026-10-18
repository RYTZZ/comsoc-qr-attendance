<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VolunteerApplication extends Model
{
    use HasFactory, HasUuids;

    public const STATUSES = [
        'applied' => 'Application Submitted',
        'under_review' => 'Under Review',
        'accepted' => 'Accepted & Assigned',
        'declined' => 'Declined',
        'withdrawn' => 'Withdrawn',
        'completed' => 'Completed & Verified',
    ];

    protected $fillable = [
        'volunteer_opportunity_id',
        'user_id',
        'motivation_statement',
        'relevant_skills',
        'assigned_shift',
        'status',
        'notes',
        'reviewed_at',
        'reviewed_by',
        'actual_hours_submitted',
        'approved_service_hours',
        'completion_evidence',
        'hours_approval_reason',
        'hours_approved_by',
        'hours_approved_at',
        'awarded_exp',
        'xp_transaction_id',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'hours_approved_at' => 'datetime',
        'actual_hours_submitted' => 'decimal:2',
        'approved_service_hours' => 'decimal:2',
        'awarded_exp' => 'integer',
    ];

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(VolunteerOpportunity::class, 'volunteer_opportunity_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function hoursApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hours_approved_by');
    }

    public function xpTransaction(): BelongsTo
    {
        return $this->belongsTo(XpTransaction::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }
}
