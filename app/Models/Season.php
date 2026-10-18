<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Season extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'academic_year_label',
        'start_date',
        'end_date',
        'is_active',
        'status',
        'settings',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    public function memberStats(): HasMany
    {
        return $this->hasMany(MemberSeasonStat::class);
    }

    public static function active(): ?self
    {
        return static::where('is_active', true)->first();
    }
}
