<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SnackInventory extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['snack_session_id', 'item_name', 'total_quantity', 'distributed_quantity'];

    public function snackSession(): BelongsTo
    {
        return $this->belongsTo(SnackSession::class);
    }

    public function claims(): HasMany
    {
        return $this->hasMany(SnackClaim::class);
    }

    public function getRemainingQuantityAttribute(): int
    {
        return max(0, $this->total_quantity - $this->distributed_quantity);
    }

    public function hasStock(): bool
    {
        return $this->remaining_quantity > 0;
    }
}
