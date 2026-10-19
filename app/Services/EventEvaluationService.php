<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventEvaluation;
use App\Models\EventEvaluationAnswer;
use App\Models\EvaluationTemplate;
use App\Models\User;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EventEvaluationService
{
    /**
     * Check if a user is eligible to evaluate an event.
     * Eligible attendee = has an approved registration OR a recorded attendance record (e.g. morning_in).
     */
    public function isEligibleAttendee(Event $event, User $user): bool
    {
        $student = $user->student;

        // 1. Check approved event registration
        $isRegistered = \App\Models\EventRegistration::where('event_id', $event->id)
            ->where('status', 'approved')
            ->where(function ($q) use ($user, $student) {
                $q->where('user_id', $user->id)
                  ->orWhere('email', strtolower($user->email));
                if ($student) {
                    $q->orWhere('email', strtolower($student->email));
                }
            })
            ->exists();

        if ($isRegistered) {
            return true;
        }

        // 2. Check if student already has any attendance record for this event
        $hasAttendance = AttendanceRecord::where('event_id', $event->id)
            ->where(function ($q) use ($user, $student) {
                $q->where('user_id', $user->id);
                if ($student) {
                    $q->orWhere('participant_id', $student->id);
                }
            })
            ->exists();

        return $hasAttendance;
    }

    /**
     * Check if the user has completed the mandatory evaluation for the event.
     */
    public function hasCompletedEvaluation(Event $event, User $user): bool
    {
        return EventEvaluation::where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Get the active evaluation questions for an event.
     * Prefers event's evaluation_snapshot if frozen, or the assigned template's questions.
     */
    public function getEventQuestions(Event $event): ?array
    {
        if (!empty($event->evaluation_snapshot) && !empty($event->evaluation_snapshot['questions'])) {
            return $event->evaluation_snapshot['questions'];
        }

        if ($event->evaluationTemplate && $event->evaluationTemplate->is_active) {
            return $event->evaluationTemplate->questions->map(function ($q) {
                return [
                    'id' => $q->id,
                    'question_text' => $q->question_text,
                    'question_type' => $q->question_type,
                    'options' => $q->options,
                    'rating_min' => $q->rating_min,
                    'rating_max' => $q->rating_max,
                    'rating_min_label' => $q->rating_min_label,
                    'rating_max_label' => $q->rating_max_label,
                    'is_required' => (bool)$q->is_required,
                    'sort_order' => $q->sort_order,
                ];
            })->all();
        }

        return null;
    }

    /**
     * Snapshot the template into the event if not already done.
     */
    public function snapshotEventTemplate(Event $event, EvaluationTemplate $template): void
    {
        $questions = $template->questions()->orderBy('sort_order')->get()->map(function ($q) {
            return [
                'id' => $q->id,
                'question_text' => $q->question_text,
                'question_type' => $q->question_type,
                'options' => $q->options,
                'rating_min' => $q->rating_min,
                'rating_max' => $q->rating_max,
                'rating_min_label' => $q->rating_min_label,
                'rating_max_label' => $q->rating_max_label,
                'is_required' => (bool)$q->is_required,
                'sort_order' => $q->sort_order,
            ];
        })->toArray();

        $event->update([
            'evaluation_template_id' => $template->id,
            'evaluation_snapshot' => [
                'template_id' => $template->id,
                'template_title' => $template->title,
                'template_version' => $template->version,
                'questions' => $questions,
                'snapshotted_at' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Submit and validate student evaluation responses.
     */
    public function submitEvaluation(Event $event, User $user, array $answersInput): EventEvaluation
    {
        // 1. Eligibility check
        if (!$this->isEligibleAttendee($event, $user)) {
            throw ValidationException::withMessages([
                'event' => 'You are not an eligible attendee for this event.',
            ]);
        }

        // 2. Check if already submitted (Idempotency)
        $existing = EventEvaluation::where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        // 3. Verify event evaluation configuration is valid
        $questions = $this->getEventQuestions($event);
        if (empty($questions)) {
            throw ValidationException::withMessages([
                'evaluation' => 'Evaluation for this event is not configured or unavailable.',
            ]);
        }

        // 4. Validate all questions and answers
        $validatedAnswers = [];
        $errors = [];

        foreach ($questions as $q) {
            $qId = $q['id'];
            $val = $answersInput[$qId] ?? null;

            if ($q['is_required']) {
                if ($val === null || $val === '' || (is_array($val) && empty($val))) {
                    $errors["answers.{$qId}"] = "The question \"{$q['question_text']}\" is required.";
                    continue;
                }
            }

            if ($val === null || $val === '' || (is_array($val) && empty($val))) {
                continue; // Optional and empty
            }

            // Validate based on question type
            $answerData = [
                'evaluation_question_id' => $qId,
                'question_text' => $q['question_text'],
                'question_type' => $q['question_type'],
                'answer_text' => null,
                'rating_value' => null,
                'selected_options' => null,
            ];

            switch ($q['question_type']) {
                case 'rating':
                    $numericVal = filter_var($val, FILTER_VALIDATE_INT);
                    $min = $q['rating_min'] ?? 1;
                    $max = $q['rating_max'] ?? 5;
                    if ($numericVal === false || $numericVal < $min || $numericVal > $max) {
                        $errors["answers.{$qId}"] = "Please provide a valid rating between {$min} and {$max}.";
                    } else {
                        $answerData['rating_value'] = $numericVal;
                    }
                    break;

                case 'single_choice':
                    $allowedOptions = is_array($q['options']) ? $q['options'] : [];
                    if (!in_array($val, $allowedOptions)) {
                        $errors["answers.{$qId}"] = "Invalid option selected for \"{$q['question_text']}\".";
                    } else {
                        $answerData['answer_text'] = (string)$val;
                    }
                    break;

                case 'multiple_choice':
                    $allowedOptions = is_array($q['options']) ? $q['options'] : [];
                    if (!is_array($val)) {
                        $errors["answers.{$qId}"] = "Please select valid options for \"{$q['question_text']}\".";
                    } else {
                        $diff = array_diff($val, $allowedOptions);
                        if (!empty($diff)) {
                            $errors["answers.{$qId}"] = "One or more invalid options selected.";
                        } else {
                            $answerData['selected_options'] = array_values($val);
                        }
                    }
                    break;

                case 'short_text':
                    if (strlen($val) > 255) {
                        $errors["answers.{$qId}"] = "Response cannot exceed 255 characters.";
                    } else {
                        $answerData['answer_text'] = trim($val);
                    }
                    break;

                case 'long_text':
                    if (strlen($val) > 2000) {
                        $errors["answers.{$qId}"] = "Response cannot exceed 2000 characters.";
                    } else {
                        $answerData['answer_text'] = trim($val);
                    }
                    break;

                default:
                    $answerData['answer_text'] = (string)$val;
            }

            $validatedAnswers[] = $answerData;
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        // 5. Atomic database transaction
        return DB::transaction(function () use ($event, $user, $validatedAnswers) {
            $templateVersion = $event->evaluation_snapshot['template_version'] ?? ($event->evaluationTemplate?->version ?? 1);
            $templateId = $event->evaluation_template_id;

            $evaluation = EventEvaluation::create([
                'event_id' => $event->id,
                'user_id' => $user->id,
                'evaluation_template_id' => $templateId,
                'template_version' => $templateVersion,
                'submitted_at' => now(),
            ]);

            foreach ($validatedAnswers as $ans) {
                $evaluation->answers()->create($ans);
            }

            AuditLogger::log('event.evaluation_submitted', $evaluation, [], [
                'event_id' => $event->id,
                'event_name' => $event->name,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'submitted_at' => now()->toIso8601String(),
            ], 'Student completed mandatory event evaluation.');

            return $evaluation;
        });
    }

    /**
     * Check whether an afternoon timeout scan is permitted with respect to evaluation requirements.
     * Returns an array: ['allowed' => bool, 'code' => string, 'message' => string]
     */
    public function verifyEvaluationForTimeOut(Event $event, User $user, AttendanceSession $session): array
    {
        // Only afternoon_out requires mandatory evaluation check
        if ($session->type !== 'afternoon_out') {
            return ['allowed' => true];
        }

        // 1. Check if event has a valid evaluation configured
        $questions = $this->getEventQuestions($event);
        if (empty($questions) || !$event->evaluation_template_id) {
            return [
                'allowed' => false,
                'code' => 'evaluation_unconfigured',
                'message' => 'Afternoon time-out is blocked: Event evaluation template is not configured or unavailable.',
                'result_type' => 'Ineligible',
            ];
        }

        // 2. Check if student completed the evaluation
        $hasCompleted = $this->hasCompletedEvaluation($event, $user);
        if (!$hasCompleted) {
            return [
                'allowed' => false,
                'code' => 'evaluation_required',
                'message' => 'Mandatory Event Evaluation Required: You must complete the event evaluation before Afternoon Time-Out can be recorded. Please complete the evaluation from your dashboard and rescan.',
                'result_type' => 'Ineligible',
                'evaluation_url' => route('events.evaluation.show', $event),
            ];
        }

        return ['allowed' => true];
    }
}
