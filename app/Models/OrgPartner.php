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

    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo_path) {
            return null;
        }

        if (str_starts_with($this->logo_path, 'http://') || str_starts_with($this->logo_path, 'https://')) {
            return $this->logo_path;
        }

        if (str_starts_with($this->logo_path, 'images/')) {
            return asset($this->logo_path);
        }

        return asset('storage/' . $this->logo_path);
    }

    public function scopePublic($query)
    {
        return $query->where('is_published', true)
            ->orderBy('sort_order')
            ->orderBy('name');
    }
}

