<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Card extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['qr_code_id', 'status', 'claimed_by', 'claimed_at', 'reissued_by', 'reissued_at', 'notes'];

    protected $casts = ['claimed_at' => 'datetime', 'reissued_at' => 'datetime'];

    public function qrCode(): BelongsTo
    {
        return $this->belongsTo(QrCode::class);
    }

    public function claimedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claimed_by');
    }

    public function reissuedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reissued_by');
    }
}
