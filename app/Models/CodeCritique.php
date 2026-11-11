<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CodeCritique extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'code_showcase_id',
        'reviewer_id',
        'summary',
        'suggested_diff',
        'line_number',
        'is_accepted',
        'helpful_votes',
    ];

    protected $casts = [
        'is_accepted' => 'boolean',
        'line_number' => 'integer',
        'helpful_votes' => 'integer',
    ];

    public function showcase(): BelongsTo
    {
        return $this->belongsTo(CodeShowcase::class, 'code_showcase_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}

