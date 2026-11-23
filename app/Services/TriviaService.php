<?php

namespace App\Services;

use App\Models\TriviaAnswer;
use App\Models\TriviaQuestion;
use App\Models\TriviaSession;
use App\Models\TriviaSessionCompletion;
use App\Models\User;
use App\Support\RowLock;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TriviaService
{
    /**
     * Record a member's single answer. Scoring is decided server-side only.
     *
     * @throws InvalidArgumentException with a member-safe message
     */
    public function submitAnswer(User $user, TriviaQuestion $question, int $selectedIndex): TriviaAnswer
    {
        try {
            return DB::transaction(function () use ($user, $question, $selectedIndex) {
                $q = TriviaQuestion::whereKey($question->id)->lockForUpdate()->firstOrFail();

                if (! $q->isLive()) {
                    throw new InvalidArgumentException(
                        $q->isClosed() ? 'The answering period for this question has ended.' : 'This trivia question is not open for answers.'
                    );
                }

                if (! array_key_exists($selectedIndex, $q->options ?? [])) {
                    throw new InvalidArgumentException('Please choose one of the listed answers.');
                }

                if (TriviaAnswer::where('trivia_question_id', $q->id)->where('user_id', $user->id)->exists()) {
                    throw new InvalidArgumentException('You have already answered this question.');
                }

                $correct = $selectedIndex === (int) $q->correct_index;

                return TriviaAnswer::create([
                    'trivia_question_id' => $q->id,
                    'user_id' => $user->id,
                    'selected_index' => $selectedIndex,
                    'is_correct' => $correct,
                    'points_awarded' => $correct ? (int) $q->points : 0,
                ]);
            });
        } catch (QueryException $e) {
            // Unique (question, user) constraint: concurrent double submit.
            throw new InvalidArgumentException('You have already answered this question.');
        }
    }

    /**
     * Leaderboard counts only questions whose answering period has ended, so results never leak early.
     *
     * @return Collection<int, object{rank:int,user:User,total_points:int,correct_count:int,answered_count:int}>
     */
    public function leaderboard(int $limit = 10): Collection
    {
        $rows = TriviaAnswer::query()
            ->join('trivia_questions', 'trivia_questions.id', '=', 'trivia_answers.trivia_question_id')
            ->join('users', function ($j) {
                $j->on('users.id', '=', 'trivia_answers.user_id')->whereNull('users.deleted_at');
            })
            ->where('trivia_questions.status', 'published')
            ->where('trivia_questions.closes_at', '<=', now())
            ->groupBy('trivia_answers.user_id')
            ->selectRaw('trivia_answers.user_id as user_id, SUM(trivia_answers.points_awarded) as total_points, '
                .'SUM(CASE WHEN trivia_answers.is_correct THEN 1 ELSE 0 END) as correct_count, COUNT(*) as answered_count')
            ->orderByDesc('total_points')
            ->orderByDesc('correct_count')
            ->orderBy('answered_count')
            ->orderBy('user_id')
            ->limit($limit)
            ->get();

        $users = User::whereIn('id', $rows->pluck('user_id'))->get()->keyBy('id');

        return $rows->values()->map(fn ($r, $i) => (object) [
            'rank' => $i + 1,
            'user' => $users[$r->user_id] ?? null,
            'total_points' => (int) $r->total_points,
            'correct_count' => (int) $r->correct_count,
            'answered_count' => (int) $r->answered_count,
        ])->filter(fn ($r) => $r->user !== null)->values();
    }

    /**
     * Submit all answers for a weekly Friday Trivia session and award exactly 15 EXP once.
     *
     * @param  array<string, int|string>  $submittedAnswers  Map of trivia_question_id => display_index
     *
     * @throws InvalidArgumentException
     */
    public function submitSessionAnswers(
        User $user,
        TriviaSession $session,
        array $submittedAnswers,
        ExpLedgerService $expLedgerService
    ): TriviaSessionCompletion {
        try {
            return DB::transaction(function () use ($user, $session, $submittedAnswers, $expLedgerService) {
                RowLock::acquire(User::class, $user->id);

                $lockedSession = TriviaSession::whereKey($session->id)->lockForUpdate()->firstOrFail();
                if ($lockedSession->status !== 'active') {
                    throw new InvalidArgumentException('This weekly Friday Trivia session is no longer active.');
                }

                if (TriviaSessionCompletion::where('trivia_session_id', $lockedSession->id)
                    ->where('user_id', $user->id)
                    ->exists()) {
                    throw new InvalidArgumentException('You have already completed this week\'s Friday Trivia.');
                }

                $sessionQuestions = $lockedSession->sessionQuestions()->with('question')->get();
                if ($sessionQuestions->isEmpty()) {
                    throw new InvalidArgumentException('This trivia session has no questions.');
                }

                $correctCount = 0;
                foreach ($sessionQuestions as $sq) {
                    $q = $sq->question;
                    if (! $q) {
                        continue;
                    }

                    if (! array_key_exists($q->id, $submittedAnswers)) {
                        throw new InvalidArgumentException('Please answer all questions before submitting.');
                    }

                    $displayIdx = (int) $submittedAnswers[$q->id];
                    $originalIdx = $sq->originalIndexFor($displayIdx);
                    if ($originalIdx === null) {
                        throw new InvalidArgumentException('Invalid answer choice selected.');
                    }

                    $isCorrect = $originalIdx === (int) $q->correct_index;
                    if ($isCorrect) {
                        $correctCount++;
                    }

                    TriviaAnswer::updateOrCreate(
                        [
                            'trivia_question_id' => $q->id,
                            'user_id' => $user->id,
                        ],
                        [
                            'trivia_session_id' => $lockedSession->id,
                            'selected_index' => $originalIdx,
                            'is_correct' => $isCorrect,
                            'points_awarded' => $isCorrect ? 10 : 0,
                        ]
                    );
                }

                $fixedExp = TriviaSession::FIXED_EXP_REWARD; // Strictly fixed at 15 EXP
                $idempotencyKey = "friday_trivia:{$lockedSession->id}:{$user->id}";

                $expResult = $expLedgerService->creditExp(
                    user: $user,
                    amount: $fixedExp,
                    sourceType: 'contribution',
                    sourceId: $lockedSession->id,
                    description: "Completed {$lockedSession->title} (Friday Trivia)",
                    expirationDays: null,
                    idempotencyKey: $idempotencyKey,
                    actor: $user
                );

                return TriviaSessionCompletion::create([
                    'trivia_session_id' => $lockedSession->id,
                    'user_id' => $user->id,
                    'answers_count' => $sessionQuestions->count(),
                    'correct_count' => $correctCount,
                    'exp_awarded' => $fixedExp,
                    'xp_transaction_id' => $expResult['transaction']->id ?? null,
                    'completed_at' => now(),
                ]);
            });
        } catch (QueryException $e) {
            throw new InvalidArgumentException('You have already completed this week\'s Friday Trivia.');
        }
    }
}

