<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SnackClaim extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'snack_session_id',
        'snack_inventory_id',
        'qr_token',
        'participant_type',
        'participant_id',
        'quantity',
        'kiosk_id',
        'distributed_by',
        'claimed_at',
    ];

    protected $casts = ['claimed_at' => 'datetime'];

    public function snackSession(): BelongsTo
    {
        return $this->belongsTo(SnackSession::class);
    }

    public function snackInventory(): BelongsTo
    {
        return $this->belongsTo(SnackInventory::class);
    }

    public function kiosk(): BelongsTo
    {
        return $this->belongsTo(Kiosk::class);
    }

    public function distributedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'distributed_by');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'participant_id');
    }

    public function eventRegistration(): BelongsTo
    {
        return $this->belongsTo(EventRegistration::class, 'participant_id');
    }
}
