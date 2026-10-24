<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrgPartner extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'logo_path',
        'description',
        'collaboration_type',
        'website_url',
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
            ->orderBy('name');
    }
}
