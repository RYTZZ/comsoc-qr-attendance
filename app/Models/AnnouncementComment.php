<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AnnouncementComment extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_HIDDEN = 'hidden';

    protected $fillable = [
        'announcement_id',
        'user_id',
        'parent_id',
        'content',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id')->withTrashed();
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->latest('created_at');
    }

    public function activeReplies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->where('status', self::STATUS_ACTIVE)
            ->latest('created_at');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(AnnouncementCommentReport::class, 'comment_id');
    }

    public function isReply(): bool
    {
        return !is_null($this->parent_id);
    }

    public function isMainComment(): bool
    {
        return is_null($this->parent_id);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeMainOnly(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }
}

