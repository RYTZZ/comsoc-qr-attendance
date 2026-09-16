<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class QrCode extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'membership_id',
        'token',
        'status',
        'batch_number',
        'activated_at',
        'revoked_at',
        'expired_at',
        'revoke_reason',
    ];

    protected $casts = [
        'activated_at' => 'datetime',
        'revoked_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    public function membership(): BelongsTo
    {
        return $this->belongsTo(Membership::class);
    }

    public function card(): HasOne
    {
        return $this->hasOne(Card::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public static function generateToken(): string
    {
        do {
            $token = Str::random(48);
        } while (static::where('token', $token)->exists());

        return $token;
    }

    public function revoke(string $reason, User $actor): void
    {
        $this->update([
            'status' => 'revoked',
            'revoked_at' => now(),
            'revoke_reason' => $reason,
        ]);
    }
}
