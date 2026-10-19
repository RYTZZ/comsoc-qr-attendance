<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventEvaluation;
use App\Services\EventEvaluationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventEvaluationReportController extends Controller
{
    public function __construct(
        private EventEvaluationService $evaluationService
    ) {}

    public function index(Request $request): View
    {
        $events = Event::with('evaluationTemplate')
            ->whereNotNull('evaluation_template_id')
            ->orWhereNotNull('evaluation_snapshot')
            ->orderByDesc('event_date')
            ->get();

        $selectedEventId = $request->input('event_id', $events->first()?->id);
        $selectedEvent = $selectedEventId ? Event::with(['evaluationTemplate', 'evaluations.answers', 'evaluations.user'])->find($selectedEventId) : null;

        $evaluations = collect();
        $questions = [];
        $ratingAverages = [];

        if ($selectedEvent) {
            $questions = $this->evaluationService->getEventQuestions($selectedEvent) ?? [];
            $evaluations = EventEvaluation::where('event_id', $selectedEvent->id)
                ->with(['user.student', 'answers'])
                ->latest('submitted_at')
                ->paginate(20)
                ->withQueryString();

            // Calculate rating averages per question
            foreach ($questions as $q) {
                if ($q['question_type'] === 'rating') {
                    $avg = \App\Models\EventEvaluationAnswer::whereHas('evaluation', fn($sq) => $sq->where('event_id', $selectedEvent->id))
                        ->where('question_text', $q['question_text'])
                        ->whereNotNull('rating_value')
                        ->avg('rating_value');

                    $ratingAverages[$q['id']] = $avg ? round($avg, 2) : 0;
                }
            }
        }

        return view('admin.evaluations.results.index', compact(
            'events',
            'selectedEvent',
            'evaluations',
            'questions',
            'ratingAverages'
        ));
    }

    public function show(EventEvaluation $evaluation): View
    {
        $evaluation->load(['event.evaluationTemplate', 'user.student', 'answers']);
        return view('admin.evaluations.results.show', compact('evaluation'));
    }
}
