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

    public function getBadgeClassAttribute(): string
    {
        return match($this->collaboration_type) {
            'Organization under COMSOC' => 'bg-[#df747a]/15 text-[#df747a] border-[#df747a]/30',
            'Government Collaborator' => 'bg-sky-500/15 text-sky-300 border-sky-500/30',
            'Community Collaborator' => 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30',
            'Academic Collaborator' => 'bg-indigo-500/15 text-indigo-300 border-indigo-500/30',
            default => 'bg-[#172033] text-slate-300 border-[#334155]/60',
        };
    }

    public function scopePublic($query)
    {
        return $query->where('is_published', true)
            ->orderBy('sort_order')
            ->orderBy('name');
    }
}


