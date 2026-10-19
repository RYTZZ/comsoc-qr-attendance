<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventEvaluationAnswer extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'event_evaluation_id',
        'evaluation_question_id',
        'question_text',
        'question_type',
        'answer_text',
        'rating_value',
        'selected_options',
    ];

    protected $casts = [
        'rating_value' => 'integer',
        'selected_options' => 'array',
    ];

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(EventEvaluation::class, 'event_evaluation_id');
    }
}
