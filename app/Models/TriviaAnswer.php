<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TriviaAnswer extends Model
{
    use HasUuids;

    protected $fillable = [
        'trivia_question_id', 'user_id', 'selected_index', 'is_correct', 'points_awarded',
    ];

    protected function casts(): array
    {
        return [
            'selected_index' => 'integer',
            'is_correct' => 'boolean',
            'points_awarded' => 'integer',
        ];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(TriviaQuestion::class, 'trivia_question_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

