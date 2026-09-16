<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Incident extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'event_id',
        'reported_by',
        'category',
        'subject_type',
        'subject_id',
        'description',
        'status',
        'resolved_by',
        'resolved_at',
        'resolution_notes',
        'archived_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function reportedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function resolvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'qr_identity_issue' => 'QR / Identity Issue',
            'attendance_issue' => 'Attendance Issue',
            'disruptive_conduct' => 'Disruptive Conduct',
            'harassment_bullying' => 'Harassment / Bullying',
            'property_issue' => 'Property Issue',
            'safety_concern' => 'Safety Concern',
            default => 'Other',
        };
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeArchivable($query)
    {
        return $query->where('status', 'open')
            ->where('created_at', '<=', now()->subDays(7));
    }
}
