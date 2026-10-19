<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationQuestion extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'evaluation_template_id',
        'question_text',
        'question_type',
        'options',
        'rating_min',
        'rating_max',
        'rating_min_label',
        'rating_max_label',
        'is_required',
        'sort_order',
    ];

    protected $casts = [
        'options' => 'array',
        'rating_min' => 'integer',
        'rating_max' => 'integer',
        'is_required' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(EvaluationTemplate::class, 'evaluation_template_id');
    }
}
