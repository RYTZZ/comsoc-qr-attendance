<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventEvaluation extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'event_id',
        'user_id',
        'evaluation_template_id',
        'template_version',
        'submitted_at',
    ];

    protected $casts = [
        'template_version' => 'integer',
        'submitted_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(EvaluationTemplate::class, 'evaluation_template_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(EventEvaluationAnswer::class);
    }
}
