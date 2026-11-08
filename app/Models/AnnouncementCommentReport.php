<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnnouncementCommentReport extends Model
{
    use HasFactory, HasUuids;

    public const STATUS_PENDING = 'pending';
    public const STATUS_REVIEWED = 'reviewed';
    public const STATUS_DISMISSED = 'dismissed';

    public const REASONS = [
        'harassment' => 'Harassment or Hate Speech',
        'spam' => 'Spam or Misleading Content',
        'inappropriate' => 'Inappropriate or Offensive Material',
        'academic_dishonesty' => 'Academic Dishonesty or Cheating',
        'other' => 'Other Violation',
    ];

    protected $fillable = [
        'comment_id',
        'reporter_id',
        'reason',
        'details',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function comment(): BelongsTo
    {
        return $this->belongsTo(AnnouncementComment::class, 'comment_id')->withTrashed();
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}

