<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileCompletionReward extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'source',
        'reference_key',
        'xp_transaction_id',
        'point_transaction_id',
        'exp_awarded',
        'points_awarded',
        'completion_percentage',
        'awarded_at',
    ];

    protected function casts(): array
    {
        return [
            'exp_awarded' => 'integer',
            'points_awarded' => 'integer',
            'completion_percentage' => 'integer',
            'awarded_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function xpTransaction(): BelongsTo
    {
        return $this->belongsTo(XpTransaction::class, 'xp_transaction_id');
    }

    public function pointTransaction(): BelongsTo
    {
        return $this->belongsTo(PointTransaction::class, 'point_transaction_id');
    }
}

