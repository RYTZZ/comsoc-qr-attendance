<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpAllocation extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'reward_redemption_id',
        'exp_credit_id',
        'amount_allocated',
    ];

    protected $casts = [
        'amount_allocated' => 'integer',
    ];

    public function redemption(): BelongsTo
    {
        return $this->belongsTo(RewardRedemption::class, 'reward_redemption_id');
    }

    public function credit(): BelongsTo
    {
        return $this->belongsTo(ExpCredit::class, 'exp_credit_id');
    }
}
