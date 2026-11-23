<?php

namespace App\Services;

use App\Models\TriviaAutomationLog;
use App\Models\TriviaQuestion;
use App\Models\TriviaSession;
use App\Models\TriviaSessionQuestion;
use App\Support\TriviaSchema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FridayTriviaAutomationService
{
    public const QUESTIONS_PER_SESSION = 5;

    public const FIXED_EXP_REWARD = 15;

    /**
     * Automatically prepare, shuffle, publish, and activate the weekly Friday Trivia session
     * using the existing database question bank.
     *
     * @return array{status: string, session: ?TriviaSession, message: string}
     */
    public function activateWeeklySession(?Carbon $nowManila = null, bool $manual = false): array
    {
        TriviaSchema::ensureTables();

        $manila = ($nowManila ?? Carbon::now(TriviaQuestion::TZ))->copy()->setTimezone(TriviaQuestion::TZ);
        $weekKey = $manila->format('o-\WW');
        $action = $manual ? 'manual_trigger' : 'weekly_activation';

        // Duplicate weekly session protection
        $existing = TriviaSession::where('week_key', $weekKey)->first();
        if ($existing) {
            $msg = "Weekly trivia session for {$weekKey} already exists ({$existing->title}).";
            TriviaAutomationLog::create([
                'action' => $action,
                'status' => 'skipped',
                'week_key' => $weekKey,
                'trivia_session_id' => $existing->id,
                'summary' => $msg,
                'executed_at' => now(),
            ]);

            return [
                'status' => 'skipped',
                'session' => $existing,
                'message' => $msg,
            ];
        }

        $fridayStart = $manila->copy()->startOfDay();
        $weekEnd = $fridayStart->copy()->addDays(6)->endOfDay();

        try {
            $session = DB::transaction(function () use ($weekKey, $manila, $fridayStart, $weekEnd) {
                // Archive any previously active sessions
                TriviaSession::where('status', 'active')->update([
                    'status' => 'archived',
                    'archived_at' => now(),
                ]);

                $selected = $this->selectQuestionsForWeek(self::QUESTIONS_PER_SESSION);
                if ($selected->isEmpty()) {
                    throw new \RuntimeException('No active trivia questions available in the question bank.');
                }

                $session = TriviaSession::create([
                    'week_key' => $weekKey,
                    'title' => 'Friday Trivia — ' . $manila->format('M j, Y'),
                    'theme' => 'Computing, IT & Tech Challenge',
                    'status' => 'active',
                    'exp_reward' => self::FIXED_EXP_REWARD,
                    'starts_at' => $fridayStart->copy()->utc(),
                    'ends_at' => $weekEnd->copy()->utc(),
                    'activated_at' => now(),
                ]);

                $shuffled = $selected->shuffle()->values();
                foreach ($shuffled as $pos => $question) {
                    $optionKeys = array_keys($question->options ?? []);
                    shuffle($optionKeys);

                    TriviaSessionQuestion::create([
                        'trivia_session_id' => $session->id,
                        'trivia_question_id' => $question->id,
                        'position' => $pos + 1,
                        'option_order' => array_values($optionKeys),
                    ]);

                    $question->increment('usage_count');
                    $question->forceFill(['last_used_at' => now()])->save();
                }

                return $session;
            });

            $msg = "Activated {$session->title} ({$weekKey}) with " . self::QUESTIONS_PER_SESSION . ' shuffled questions and ' . self::FIXED_EXP_REWARD . ' EXP reward.';
            TriviaAutomationLog::create([
                'action' => $action,
                'status' => 'success',
                'week_key' => $weekKey,
                'trivia_session_id' => $session->id,
                'summary' => $msg,
                'executed_at' => now(),
            ]);
            Log::info('Friday Trivia automated activation succeeded.', ['week_key' => $weekKey, 'session_id' => $session->id]);

            return [
                'status' => 'activated',
                'session' => $session->load('sessionQuestions.question'),
                'message' => $msg,
            ];
        } catch (\Throwable $e) {
            Log::error('Friday Trivia automated activation failed.', [
                'week_key' => $weekKey,
                'error' => $e->getMessage(),
            ]);

            TriviaAutomationLog::create([
                'action' => $action,
                'status' => 'failed',
                'week_key' => $weekKey,
                'summary' => 'Activation failed: ' . $e->getMessage(),
                'executed_at' => now(),
            ]);

            throw $e;
        }
    }

    /**
     * Prioritize unused questions, avoid consecutive-week repetition, and randomize selection.
     *
     * @return Collection<int, TriviaQuestion>
     */
    private function selectQuestionsForWeek(int $count): Collection
    {
        $lastSession = TriviaSession::orderByDesc('starts_at')->first();
        $lastQuestionIds = $lastSession
            ? TriviaSessionQuestion::where('trivia_session_id', $lastSession->id)
                ->pluck('trivia_question_id')
                ->all()
            : [];

        // Tier 1: Never-used active questions
        $unused = TriviaQuestion::where('is_active', true)
            ->where('usage_count', 0)
            ->when(! empty($lastQuestionIds), fn ($q) => $q->whereNotIn('id', $lastQuestionIds))
            ->get()
            ->shuffle();

        $picked = $unused->take($count);

        // Tier 2: Previously used questions NOT in the immediately preceding session
        if ($picked->count() < $count) {
            $remaining = $count - $picked->count();
            $older = TriviaQuestion::where('is_active', true)
                ->whereNotIn('id', $picked->pluck('id')->all())
                ->when(! empty($lastQuestionIds), fn ($q) => $q->whereNotIn('id', $lastQuestionIds))
                ->orderBy('usage_count')
                ->orderBy('last_used_at')
                ->get()
                ->shuffle()
                ->take($remaining);

            $picked = $picked->concat($older);
        }

        // Tier 3: Only if the entire bank is smaller than $count, allow consecutive reuse
        if ($picked->count() < $count) {
            $remaining = $count - $picked->count();
            $fallback = TriviaQuestion::where('is_active', true)
                ->whereNotIn('id', $picked->pluck('id')->all())
                ->orderBy('usage_count')
                ->get()
                ->shuffle()
                ->take($remaining);

            $picked = $picked->concat($fallback);
        }

        return $picked->values();
    }
}
