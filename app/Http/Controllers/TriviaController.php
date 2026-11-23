<?php

namespace App\Http\Controllers;

use App\Models\TriviaAnswer;
use App\Models\TriviaQuestion;
use App\Models\TriviaSession;
use App\Models\TriviaSessionCompletion;
use App\Services\ExpLedgerService;
use App\Services\TriviaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use InvalidArgumentException;

class TriviaController extends Controller
{
    public function index(Request $request, TriviaService $service): View
    {
        \App\Support\TriviaSchema::ensureTables();

        $user = $request->user();

        $activeSession = TriviaSession::currentActive();
        $sessionQuestions = collect();
        $sessionCompletion = null;
        $sessionAnswers = collect();

        if ($activeSession) {
            $sessionQuestions = $activeSession->sessionQuestions()->with('question')->get();
            $sessionCompletion = TriviaSessionCompletion::where('trivia_session_id', $activeSession->id)
                ->where('user_id', $user->id)
                ->first();
            $sessionAnswers = TriviaAnswer::where('trivia_session_id', $activeSession->id)
                ->where('user_id', $user->id)
                ->get()
                ->keyBy('trivia_question_id');
        }

        $live = TriviaQuestion::live()->orderByDesc('publish_at')->get();
        $archive = TriviaQuestion::closed()->orderByDesc('closes_at')->paginate(8);
        $pastSessions = TriviaSession::where('status', 'archived')->orderByDesc('starts_at')->take(5)->get();

        $ids = $live->pluck('id')->merge($archive->pluck('id'));
        $myAnswers = TriviaAnswer::where('user_id', $user->id)->whereIn('trivia_question_id', $ids)->get()->keyBy('trivia_question_id');

        $leaderboard = $service->leaderboard(10);
        $myStanding = $service->leaderboard(1000)->firstWhere(fn ($r) => (string) $r->user->id === (string) $user->id);

        $upcoming = TriviaQuestion::where('status', 'published')->where('publish_at', '>', now())->orderBy('publish_at')->first();

        return view('trivia.index', compact(
            'activeSession',
            'sessionQuestions',
            'sessionCompletion',
            'sessionAnswers',
            'pastSessions',
            'live',
            'archive',
            'myAnswers',
            'leaderboard',
            'myStanding',
            'upcoming'
        ));
    }

    public function submitSession(
        Request $request,
        TriviaSession $session,
        TriviaService $service,
        ExpLedgerService $expLedgerService
    ): RedirectResponse {
        $data = $request->validate([
            'answers' => ['required', 'array', 'min:1'],
            'answers.*' => ['required', 'integer', 'min:0', 'max:5'],
        ], [
            'answers.required' => 'Please answer all trivia questions before submitting.',
        ]);

        try {
            $completion = $service->submitSessionAnswers($request->user(), $session, $data['answers'], $expLedgerService);
        } catch (InvalidArgumentException $e) {
            return redirect()->route('trivia.index')->with('error', $e->getMessage());
        }

        return redirect()->route('trivia.index')->with(
            'success',
            "Friday Trivia completed! Score: {$completion->correct_count}/{$completion->answers_count}. You earned +{$completion->exp_awarded} EXP!"
        );
    }

    public function answer(Request $request, TriviaQuestion $question, TriviaService $service): RedirectResponse
    {
        $data = $request->validate([
            'selected_index' => ['required', 'integer', 'min:0', 'max:9'],
        ], [
            'selected_index.required' => 'Please choose an answer before submitting.',
        ]);

        try {
            $answer = $service->submitAnswer($request->user(), $question, (int) $data['selected_index']);
        } catch (InvalidArgumentException $e) {
            return redirect()->route('trivia.index')->with('error', $e->getMessage());
        }

        $message = $question->feedback_mode === 'immediate'
            ? ($answer->is_correct ? "Correct! +{$answer->points_awarded} points." : 'Not quite. Better luck next Friday!')
            : 'Answer recorded! Results will be revealed when the answering period ends.';

        return redirect()->route('trivia.index')->with('success', $message);
    }
}

