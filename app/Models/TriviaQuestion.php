<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class TriviaQuestion extends Model
{
    use HasUuids;

    public const TZ = 'Asia/Manila';

    public const CATEGORIES = [
        'information_technology' => 'Information Technology',
        'programming' => 'Programming',
        'computer_science' => 'Computer Science',
        'cybersecurity' => 'Cybersecurity',
        'general_tech' => 'General Technology',
        'other_computing' => 'Other Computing Topics',
    ];

    public const FEEDBACK_MODES = [
        'immediate' => 'Immediate (right/wrong shown right after answering)',
        'after_close' => 'Scheduled (result shown after the answering period)',
    ];

    protected $fillable = [
        'question', 'category', 'options', 'correct_index', 'explanation', 'points',
        'feedback_mode', 'status', 'publish_at', 'closes_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'correct_index' => 'integer',
            'points' => 'integer',
            'publish_at' => 'datetime',
            'closes_at' => 'datetime',
        ];
    }

    public function answers(): HasMany
    {
        return $this->hasMany(TriviaAnswer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Published, already started and still accepting answers. */
    public function scopeLive(Builder $q): Builder
    {
        return $q->where('status', 'published')
            ->where('publish_at', '<=', now())
            ->where('closes_at', '>', now());
    }

    /** Published and the answering period has ended (answers become public). */
    public function scopeClosed(Builder $q): Builder
    {
        return $q->where('status', 'published')->where('closes_at', '<=', now());
    }

    /** draft | scheduled | live | closed */
    public function getStateAttribute(): string
    {
        if ($this->status !== 'published') {
            return 'draft';
        }
        if ($this->publish_at && $this->publish_at->isFuture()) {
            return 'scheduled';
        }
        if ($this->closes_at && $this->closes_at->isPast()) {
            return 'closed';
        }

        return 'live';
    }

    public function isLive(): bool
    {
        return $this->state === 'live';
    }

    public function isClosed(): bool
    {
        return $this->state === 'closed';
    }

    public static function defaultPublishAt(): Carbon
    {
        return Carbon::now(self::TZ)->next(Carbon::FRIDAY)->setTime(8, 0);
    }

    public static function defaultClosesAt(): Carbon
    {
        return Carbon::now(self::TZ)->next(Carbon::FRIDAY)->setTime(23, 59);
    }
}

