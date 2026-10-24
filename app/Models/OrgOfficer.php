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

    public function scopePublic($query)
    {
        return $query->where('is_current', true)
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->orderBy('name');
    }
}
