<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TriviaSessionQuestion extends Model
{
    use HasUuids;

    protected $fillable = [
        'trivia_session_id',
        'trivia_question_id',
        'position',
        'option_order',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'option_order' => 'array',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(TriviaSession::class, 'trivia_session_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(TriviaQuestion::class, 'trivia_question_id');
    }

    /**
     * Return options in the shuffled display order for this session.
     *
     * @return array<int, string>
     */
    public function shuffledOptions(): array
    {
        $raw = $this->question?->options ?? [];
        $order = $this->option_order ?? array_keys($raw);
        $result = [];
        foreach ($order as $origIdx) {
            if (array_key_exists($origIdx, $raw)) {
                $result[] = $raw[$origIdx];
            }
        }

        return $result;
    }

    /**
     * Map a display index (0..3) back to the original option index.
     */
    public function originalIndexFor(int $displayIndex): ?int
    {
        $order = $this->option_order ?? [];

        return isset($order[$displayIndex]) ? (int) $order[$displayIndex] : null;
    }

    /**
     * Find the display index of the correct answer.
     */
    public function correctDisplayIndex(): ?int
    {
        $correctOrig = (int) ($this->question?->correct_index ?? -1);
        $order = $this->option_order ?? [];
        $pos = array_search($correctOrig, $order, true);

        return $pos === false ? null : (int) $pos;
    }
}
