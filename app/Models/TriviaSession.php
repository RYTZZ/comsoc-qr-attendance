<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TriviaSession extends Model
{
    use HasUuids;

    public const FIXED_EXP_REWARD = 15;

    protected $fillable = [
        'week_key',
        'title',
        'theme',
        'status',
        'exp_reward',
        'starts_at',
        'ends_at',
        'activated_at',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'exp_reward' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'activated_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function sessionQuestions(): HasMany
    {
        return $this->hasMany(TriviaSessionQuestion::class)->orderBy('position');
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(TriviaQuestion::class, 'trivia_session_questions')
            ->withPivot(['position', 'option_order'])
            ->orderByPivot('position');
    }

    public function completions(): HasMany
    {
        return $this->hasMany(TriviaSessionCompletion::class);
    }

    public static function currentActive(): ?self
    {
        return self::where('status', 'active')
            ->orderByDesc('starts_at')
            ->first();
    }
}
