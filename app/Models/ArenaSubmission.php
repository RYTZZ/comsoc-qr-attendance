<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArenaSubmission extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'arena_challenge_id',
        'user_id',
        'language',
        'source_code',
        'verdict',
        'tests_passed',
        'total_tests',
        'execution_time_ms',
        'compiler_output',
        'exp_earned',
    ];

    protected $casts = [
        'tests_passed' => 'integer',
        'total_tests' => 'integer',
        'execution_time_ms' => 'integer',
        'exp_earned' => 'integer',
    ];

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(ArenaChallenge::class, 'arena_challenge_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
