<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpCredit extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'initial_amount',
        'remaining_amount',
        'expires_at',
        'source_type',
        'source_id',
        'status',
        'created_by',
    ];

    protected $casts = [
        'initial_amount' => 'integer',
        'remaining_amount' => 'integer',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(ExpAllocation::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && now()->greaterThan($this->expires_at);
    }

    public function isAvailable(): bool
    {
        return $this->status === 'active' && $this->remaining_amount > 0 && !$this->isExpired();
    }
}
