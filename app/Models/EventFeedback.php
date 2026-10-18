<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventFeedback extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'event_feedback';

    protected $fillable = [
        'event_id',
        'user_id',
        'rating',
        'organization_rating',
        'content_rating',
        'positive_feedback',
        'suggestions',
    ];

    protected $casts = [
        'rating' => 'integer',
        'organization_rating' => 'integer',
        'content_rating' => 'integer',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
