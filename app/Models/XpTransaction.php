<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class XpTransaction extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'season_id',
        'exp_credit_id',
        'amount',
        'spendable_delta',
        'lifetime_delta',
        'activity_type',
        'is_reversal',
        'reversal_reason',
        'source_type',
        'source_id',
        'description',
        'idempotency_key',
        'created_by',
        'second_approved_by',
        'second_approved_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'spendable_delta' => 'integer',
        'lifetime_delta' => 'integer',
        'is_reversal' => 'boolean',
        'second_approved_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function secondApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'second_approved_by');
    }

    public function credit(): BelongsTo
    {
        return $this->belongsTo(ExpCredit::class, 'exp_credit_id');
    }
}
