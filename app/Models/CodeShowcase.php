<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CodeShowcase extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'repository_url',
        'live_demo_url',
        'code_snippet',
        'language',
        'tech_stack',
        'review_goals',
        'bounty_exp',
        'status',
        'views_count',
        'reviews_count',
    ];

    protected $casts = [
        'tech_stack' => 'array',
        'review_goals' => 'array',
        'bounty_exp' => 'integer',
        'views_count' => 'integer',
        'reviews_count' => 'integer',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function critiques(): HasMany
    {
        return $this->hasMany(CodeCritique::class);
    }
}

