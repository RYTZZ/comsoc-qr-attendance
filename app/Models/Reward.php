<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reward extends Model
{
    use HasFactory, HasUuids;

    public const CATEGORIES = [
        'Tote bags',
        'Caps',
        'Pins',
        'Mouse pads',
        'Discount vouchers',
        'Other merchandise',
        'Others',
    ];

    protected $fillable = [
        'title',
        'category',
        'event_id',
        'description',
        'points_cost',
        'min_level',
        'eligibility_notes',
        'stock',
        'initial_quantity',
        'image_url',
        'image_path',
        'is_active',
        'is_archived',
        'fulfillment_instructions',
    ];

    protected $casts = [
        'points_cost' => 'integer',
        'min_level' => 'integer',
        'stock' => 'integer',
        'initial_quantity' => 'integer',
        'is_active' => 'boolean',
        'is_archived' => 'boolean',
    ];

    public function redemptions(): HasMany
    {
        return $this->hasMany(RewardRedemption::class);
    }

    public function event(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function getAvailableQuantityAttribute(): ?int
    {
        return $this->stock;
    }

    public function getReservedQuantityAttribute(): int
    {
        return $this->redemptions()->whereIn('status', ['pending', 'pending_collection'])->count();
    }

    public function getClaimedQuantityAttribute(): int
    {
        return $this->redemptions()->whereIn('status', ['fulfilled', 'claimed'])->count();
    }

    public function getAwardedQuantityAttribute(): int
    {
        return $this->redemptions()->count();
    }

    public function getRemainingQuantityAttribute(): ?int
    {
        return $this->stock;
    }

    public function getEffectiveImageUrlAttribute(): ?string
    {
        if ($this->image_path) {
            return asset('storage/' . $this->image_path);
        }

        return $this->image_url;
    }
}
