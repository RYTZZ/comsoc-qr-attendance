<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TriviaQuestion;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TriviaManagementController extends Controller
{
    public function index(): View
    {
        \App\Support\TriviaSchema::ensureTables();

        $questions = TriviaQuestion::withCount('answers')->orderByDesc('publish_at')->orderByDesc('created_at')->paginate(15);

        return view('admin.trivia.index', compact('questions'));
    }

    public function create(): View
    {
        \App\Support\TriviaSchema::ensureTables();

        return view('admin.trivia.form', ['question' => new TriviaQuestion([
            'options' => ['', '', '', ''],
            'points' => 10,
            'category' => 'general_tech',
            'feedback_mode' => 'after_close',
            'status' => 'draft',
            'publish_at' => TriviaQuestion::defaultPublishAt(),
            'closes_at' => TriviaQuestion::defaultClosesAt(),
        ])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['created_by'] = $request->user()->id;

        $question = TriviaQuestion::create($data);
        AuditLogger::log('trivia.created', $question, [], ['status' => $question->status]);

        return redirect()->route('admin.trivia.index')->with('success', 'Trivia question saved.');
    }

    public function edit(TriviaQuestion $trivia): View
    {
        \App\Support\TriviaSchema::ensureTables();

        return view('admin.trivia.form', [
            'question' => $trivia,
            'locked' => $trivia->answers()->exists(),
        ]);
    }

    public function update(Request $request, TriviaQuestion $trivia): RedirectResponse
    {
        $data = $this->validated($request, $trivia);

        if ($trivia->answers()->exists()) {
            // Fairness: answer key, options and points cannot change once members have answered.
            unset($data['options'], $data['correct_index'], $data['points']);
        }

        $before = $trivia->only(['status', 'publish_at', 'closes_at']);
        $trivia->update($data);
        AuditLogger::log('trivia.updated', $trivia, $before, $trivia->only(['status', 'publish_at', 'closes_at']));

        return redirect()->route('admin.trivia.index')->with('success', 'Trivia question updated.');
    }

    public function toggle(TriviaQuestion $trivia): RedirectResponse
    {
        if ($trivia->status === 'published') {
            $trivia->update(['status' => 'draft']);
            $msg = 'Trivia question unpublished.';
        } else {
            if (! $trivia->publish_at || ! $trivia->closes_at) {
                return back()->with('error', 'Set a publish and closing time before publishing.');
            }
            $trivia->update(['status' => 'published']);
            $msg = 'Trivia question published'.($trivia->publish_at->isFuture() ? ' and scheduled.' : '.');
        }
        AuditLogger::log('trivia.status_changed', $trivia, [], ['status' => $trivia->status]);

        return back()->with('success', $msg);
    }

    public function destroy(TriviaQuestion $trivia): RedirectResponse
    {
        if ($trivia->answers()->exists()) {
            return back()->with('error', 'This question already has member answers. Unpublish it instead of deleting to preserve scores.');
        }

        $trivia->delete();

        return redirect()->route('admin.trivia.index')->with('success', 'Trivia question deleted.');
    }

    private function validated(Request $request, ?TriviaQuestion $existing = null): array
    {
        $request->merge([
            'options' => array_values(array_filter(
                array_map(fn ($o) => is_string($o) ? trim($o) : '', (array) $request->input('options', [])),
                fn ($o) => $o !== ''
            )),
        ]);

        $v = $request->validate([
            'question' => ['required', 'string', 'min:10', 'max:600'],
            'category' => ['required', Rule::in(array_keys(TriviaQuestion::CATEGORIES))],
            'options' => ['required', 'array', 'min:2', 'max:6'],
            'options.*' => ['string', 'max:200', 'distinct'],
            'correct_index' => ['required', 'integer', 'min:0'],
            'explanation' => ['nullable', 'string', 'max:1500'],
            'points' => ['required', 'integer', 'min:1', 'max:100'],
            'feedback_mode' => ['required', Rule::in(array_keys(TriviaQuestion::FEEDBACK_MODES))],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'publish_at' => ['required', 'date'],
            'closes_at' => ['required', 'date'],
        ], [
            'options.min' => 'Provide at least 2 answer choices.',
            'options.*.distinct' => 'Answer choices must be different from each other.',
        ]);

        if ($v['correct_index'] >= count($v['options'])) {
            throw ValidationException::withMessages(['correct_index' => 'Select which listed choice is the correct answer.']);
        }

        $publish = Carbon::parse($v['publish_at'], TriviaQuestion::TZ);
        $closes = Carbon::parse($v['closes_at'], TriviaQuestion::TZ);

        if (! $publish->isFriday()) {
            throw ValidationException::withMessages(['publish_at' => 'Friday Trivia must be published on a Friday.']);
        }
        if ($closes->lte($publish)) {
            throw ValidationException::withMessages(['closes_at' => 'The closing time must be after the publish time.']);
        }

        $clash = TriviaQuestion::whereBetween('publish_at', [
            $publish->copy()->startOfDay()->utc(),
            $publish->copy()->endOfDay()->utc(),
        ])->when($existing, fn ($q) => $q->whereKeyNot($existing->id))->exists();
        if ($clash) {
            throw ValidationException::withMessages(['publish_at' => 'A trivia question is already scheduled for that Friday.']);
        }

        $v['publish_at'] = $publish->utc();
        $v['closes_at'] = $closes->utc();

        return $v;
    }
}

