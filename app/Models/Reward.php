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
        'Computing Society merchandise',
        'Apparel',
        'T-shirts',
        'Tote bags',
        'Caps',
        'Pins',
        'Mouse pads',
        'Kits and bundles',
        'Discount vouchers',
        'Event merchandise',
        'Other merchandise',
        'Others',
    ];

    public const TYPES = [
        'physical' => 'Physical Item',
        'merchandise' => 'Society Merchandise',
        'kit' => 'Kit or Bundle',
        'voucher' => 'Discount Voucher',
        'digital' => 'Digital Reward',
    ];

    public const STATUSES = [
        'draft' => 'Draft',
        'published' => 'Published',
        'archived' => 'Archived',
    ];

    protected $fillable = [
        'title',
        'category',
        'reward_type',
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
        'status',
        'published_at',
        'starts_at',
        'ends_at',
        'redemption_limit_per_member',
        'fulfillment_instructions',
    ];

    protected $casts = [
        'points_cost' => 'integer',
        'min_level' => 'integer',
        'stock' => 'integer',
        'initial_quantity' => 'integer',
        'is_active' => 'boolean',
        'is_archived' => 'boolean',
        'published_at' => 'datetime',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'redemption_limit_per_member' => 'integer',
    ];

    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->where('is_active', true)
            ->where('is_archived', false)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            });
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft' || (!$this->is_active && !$this->is_archived);
    }

    public function isPublished(): bool
    {
        if ($this->status !== 'published' || !$this->is_active || $this->is_archived) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return false;
        }

        return true;
    }

    public function getNameAttribute(): string
    {
        return $this->title ?? '';
    }

    public function isArchived(): bool
    {
        return $this->status === 'archived' || $this->is_archived;
    }

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
