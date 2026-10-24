<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentSignature extends Model
{
    use HasUuids;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUPERSEDED = 'superseded';

    protected $fillable = [
        'user_id', 'encrypted_payload', 'image_format', 'data_version', 'width', 'height',
        'version', 'status', 'consent_acknowledged_at', 'consent_version', 'replaced_at',
    ];

    // Never serialize the ciphertext into API/JSON output.
    protected $hidden = ['encrypted_payload'];

    protected $casts = [
        'consent_acknowledged_at' => 'datetime',
        'replaced_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
