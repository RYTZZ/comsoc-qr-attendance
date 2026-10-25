<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrgOfficer extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'name',
        'position',
        'program',
        'bio',
        'portrait_path',
        'social_link',
        'category',
        'sort_order',
        'is_current',
        'is_published',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_current' => 'boolean',
        'is_published' => 'boolean',
    ];

    public const CATEGORIES = [
        'executive' => 'Executive Officer',
        'board' => 'Board Member / Program Representative',
        'adviser' => 'Faculty Adviser',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getPortraitUrlAttribute(): ?string
    {
        if (!$this->portrait_path) {
            return null;
        }

        if (str_starts_with($this->portrait_path, 'http://') || str_starts_with($this->portrait_path, 'https://')) {
            return $this->portrait_path;
        }

        if (str_starts_with($this->portrait_path, 'images/')) {
            return asset($this->portrait_path);
        }

        return asset('storage/' . $this->portrait_path);
    }

    public function getInitialsAttribute(): string
    {
        $words = preg_split('/\s+/', trim($this->name ?? ''));
        $initials = '';
        foreach ($words as $w) {
            if (!empty($w)) {
                $initials .= mb_substr($w, 0, 1);
            }
            if (mb_strlen($initials) >= 2) {
                break;
            }
        }
        return strtoupper($initials ?: '?');
    }

    public function scopePublic($query)
    {
        return $query->where('is_current', true)
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->orderBy('name');
    }
}

