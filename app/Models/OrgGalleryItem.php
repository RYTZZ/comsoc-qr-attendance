<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrgGalleryItem extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'event_id',
        'title',
        'image_path',
        'caption',
        'alt_text',
        'sort_order',
        'is_published',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_published' => 'boolean',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function scopePublic($query)
    {
        return $query->where('is_published', true)
            ->orderBy('sort_order')
            ->orderByDesc('created_at');
    }
}
