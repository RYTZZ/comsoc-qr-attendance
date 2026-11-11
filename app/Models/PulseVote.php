<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PulseVote extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'pulse_topic_id',
        'voter_hash',
    ];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(PulseTopic::class, 'pulse_topic_id');
    }
}
