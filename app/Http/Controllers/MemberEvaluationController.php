<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventEvaluation;
use App\Services\EventEvaluationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberEvaluationController extends Controller
{
    public function __construct(
        private EventEvaluationService $evaluationService
    ) {}

    /**
     * Display the event evaluation form or completed summary for a student.
     */
    public function show(Request $request, Event $event): View|RedirectResponse
    {
        $user = $request->user();

        // 1. Verify eligibility
        if (!$this->evaluationService->isEligibleAttendee($event, $user)) {
            return redirect()->route('dashboard')
                ->with('error', "You are not an eligible attendee for event '{$event->name}'.");
        }

        // 2. Check if already completed
        $existingEvaluation = EventEvaluation::where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->with('answers')
            ->first();

        // 3. Resolve questions
        $questions = $this->evaluationService->getEventQuestions($event);

        if (empty($questions) && !$existingEvaluation) {
            return redirect()->route('dashboard')
                ->with('error', "Evaluation template for '{$event->name}' is not configured or unavailable.");
        }

        // Check if afternoon out session exists and its schedule
        $afternoonOutSession = $event->attendanceSessions()->where('type', 'afternoon_out')->first();

        return view('member.events.evaluation', compact(
            'event',
            'questions',
            'existingEvaluation',
            'afternoonOutSession'
        ));
    }

    /**
     * Submit answers for the event evaluation.
     */
    public function store(Request $request, Event $event): RedirectResponse
    {
        $user = $request->user();

        // Check if already submitted
        if ($this->evaluationService->hasCompletedEvaluation($event, $user)) {
            return redirect()->route('events.evaluation.show', $event)
                ->with('info', 'You have already completed this evaluation. You may now proceed to afternoon time-out scanning when the window opens.');
        }

        $answers = $request->input('answers', []);

        try {
            $evaluation = $this->evaluationService->submitEvaluation($event, $user, $answers);

            return redirect()->route('events.evaluation.show', $event)
                ->with('success', 'Thank you! Your evaluation has been saved successfully. Please present your Dynamic QR code or retry/rescan at the scanner to record your Afternoon Time-Out.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to save your evaluation. Please try again or contact support.');
        }
    }
}
