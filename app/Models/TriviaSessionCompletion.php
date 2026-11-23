<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TriviaSessionCompletion extends Model
{
    use HasUuids;

    protected $fillable = [
        'trivia_session_id',
        'user_id',
        'answers_count',
        'correct_count',
        'exp_awarded',
        'xp_transaction_id',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'answers_count' => 'integer',
            'correct_count' => 'integer',
            'exp_awarded' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(TriviaSession::class, 'trivia_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
