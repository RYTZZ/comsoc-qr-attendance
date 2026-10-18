<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    use HasFactory, HasUuids;

    public const CATEGORIES = [
        'attendance' => 'Attendance Missing or Incorrect',
        'qr_scan' => 'Dynamic QR or Scanning Problem',
        'exp_dispute' => 'EXP Transaction Dispute',
        'reward' => 'Reward Redemption or Collection Issue',
        'event_reg' => 'Event Registration Problem',
        'account' => 'Account or Profile Issue',
        'technical' => 'Technical Issue',
        'other' => 'Other COMSOC Concern',
    ];

    public const STATUSES = [
        'open' => 'Open',
        'in_review' => 'In Review',
        'awaiting_member' => 'Awaiting Member',
        'resolved' => 'Resolved',
        'closed' => 'Closed',
    ];

    public const PRIORITIES = [
        'low' => 'Low',
        'normal' => 'Normal',
        'high' => 'High',
        'urgent' => 'Urgent',
    ];

    protected $fillable = [
        'ticket_number',
        'user_id',
        'category',
        'subject',
        'description',
        'status',
        'priority',
        'event_id',
        'xp_transaction_id',
        'reward_redemption_id',
        'attendance_record_id',
        'assigned_to',
        'is_escalated',
        'escalation_reason',
        'resolution_code',
        'resolution_summary',
        'resolved_by',
        'resolved_at',
        'closed_at',
    ];

    protected $casts = [
        'is_escalated' => 'boolean',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function resolvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function xpTransaction(): BelongsTo
    {
        return $this->belongsTo(XpTransaction::class);
    }

    public function rewardRedemption(): BelongsTo
    {
        return $this->belongsTo(RewardRedemption::class);
    }

    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class)->orderBy('created_at', 'asc');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(SupportTicketHistory::class)->orderBy('created_at', 'desc');
    }

    public static function generateTicketNumber(): string
    {
        $year = date('Y');
        $random = strtoupper(bin2hex(random_bytes(3)));
        return "TKT-{$year}-{$random}";
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst($this->category);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }
}
