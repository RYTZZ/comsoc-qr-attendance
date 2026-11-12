<?php

namespace App\Http\Controllers;

use App\Models\TriviaAnswer;
use App\Models\TriviaQuestion;
use App\Services\TriviaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use InvalidArgumentException;

class TriviaController extends Controller
{
    public function index(Request $request, TriviaService $service): View
    {
        $user = $request->user();

        $live = TriviaQuestion::live()->orderByDesc('publish_at')->get();
        $archive = TriviaQuestion::closed()->orderByDesc('closes_at')->paginate(8);

        $ids = $live->pluck('id')->merge($archive->pluck('id'));
        $myAnswers = TriviaAnswer::where('user_id', $user->id)->whereIn('trivia_question_id', $ids)->get()->keyBy('trivia_question_id');

        $leaderboard = $service->leaderboard(10);
        $myStanding = $service->leaderboard(1000)->firstWhere(fn ($r) => (string) $r->user->id === (string) $user->id);

        $upcoming = TriviaQuestion::where('status', 'published')->where('publish_at', '>', now())->orderBy('publish_at')->first();

        return view('trivia.index', compact('live', 'archive', 'myAnswers', 'leaderboard', 'myStanding', 'upcoming'));
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

