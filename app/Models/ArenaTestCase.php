<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArenaTestCase extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'arena_challenge_id',
        'input_data',
        'expected_output',
        'is_hidden',
    ];

    protected $casts = [
        'is_hidden' => 'boolean',
    ];

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(ArenaChallenge::class, 'arena_challenge_id');
    }
}

