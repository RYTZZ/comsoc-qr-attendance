<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SnackSession extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['event_id', 'name', 'type', 'available_from', 'available_until', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(SnackInventory::class);
    }

    public function claims(): HasMany
    {
        return $this->hasMany(SnackClaim::class);
    }
}

