<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSession extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'session_id_hash',
        'device_type',
        'platform',
        'browser',
        'device_signature',
        'ip_address',
        'user_agent',
        'first_authenticated_at',
        'last_active_at',
        'revoked_at',
    ];

    protected $casts = [
        'first_authenticated_at' => 'datetime',
        'last_active_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isRevoked(): bool
    {
        return !is_null($this->revoked_at);
    }

    public function isCurrent(string $currentSessionId): bool
    {
        return hash_equals($this->session_id_hash, hash('sha256', $currentSessionId));
    }

    public function scopeActive($query)
    {
        return $query->whereNull('revoked_at');
    }
}
