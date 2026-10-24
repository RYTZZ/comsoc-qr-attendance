<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrgAchievement extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'title',
        'description',
        'date_or_year',
        'image_path',
        'award_details',
        'verification_reference',
        'sort_order',
        'is_published',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_published' => 'boolean',
    ];

    public function scopePublic($query)
    {
        return $query->where('is_published', true)
            ->orderBy('sort_order')
            ->orderByDesc('created_at');
    }
}
