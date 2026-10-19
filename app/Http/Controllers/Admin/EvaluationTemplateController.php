<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EvaluationTemplate;
use App\Models\EvaluationQuestion;
use App\Models\Event;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EvaluationTemplateController extends Controller
{
    public function index(): View
    {
        $templates = EvaluationTemplate::withCount(['questions', 'events', 'evaluations'])
            ->with('creator')
            ->latest()
            ->paginate(15);

        return view('admin.evaluations.templates.index', compact('templates'));
    }

    public function create(): View
    {
        return view('admin.evaluations.templates.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'questions' => 'required|array|min:1',
            'questions.*.question_text' => 'required|string|max:255',
            'questions.*.question_type' => 'required|in:single_choice,multiple_choice,rating,short_text,long_text',
            'questions.*.options' => 'nullable|string',
            'questions.*.rating_min' => 'nullable|integer|min:1|max:10',
            'questions.*.rating_max' => 'nullable|integer|min:2|max:10',
            'questions.*.rating_min_label' => 'nullable|string|max:100',
            'questions.*.rating_max_label' => 'nullable|string|max:100',
            'questions.*.is_required' => 'nullable|boolean',
        ]);

        $template = EvaluationTemplate::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'is_active' => true,
            'version' => 1,
            'created_by' => auth()->id(),
        ]);

        foreach ($validated['questions'] as $order => $qData) {
            $options = null;
            if (in_array($qData['question_type'], ['single_choice', 'multiple_choice']) && !empty($qData['options'])) {
                $options = array_values(array_filter(array_map('trim', explode("\n", str_replace("\r", "", $qData['options'])))));
            }

            $template->questions()->create([
                'question_text' => $qData['question_text'],
                'question_type' => $qData['question_type'],
                'options' => $options,
                'rating_min' => $qData['rating_min'] ?? 1,
                'rating_max' => $qData['rating_max'] ?? 5,
                'rating_min_label' => $qData['rating_min_label'] ?? 'Poor',
                'rating_max_label' => $qData['rating_max_label'] ?? 'Excellent',
                'is_required' => !empty($qData['is_required']),
                'sort_order' => $order + 1,
            ]);
        }

        AuditLogger::log('evaluation.template_created', $template, [], $template->toArray(), 'Evaluation template created.');

        return redirect()->route('admin.evaluation-templates.index')
            ->with('success', "Evaluation template '{$template->title}' created successfully.");
    }

    public function show(EvaluationTemplate $evaluation_template): View
    {
        $evaluation_template->load(['questions', 'creator', 'events']);
        return view('admin.evaluations.templates.show', ['template' => $evaluation_template]);
    }

    public function edit(EvaluationTemplate $evaluation_template): View
    {
        $evaluation_template->load('questions');
        return view('admin.evaluations.templates.edit', ['template' => $evaluation_template]);
    }

    public function update(Request $request, EvaluationTemplate $evaluation_template): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'questions' => 'required|array|min:1',
            'questions.*.question_text' => 'required|string|max:255',
            'questions.*.question_type' => 'required|in:single_choice,multiple_choice,rating,short_text,long_text',
            'questions.*.options' => 'nullable|string',
            'questions.*.rating_min' => 'nullable|integer|min:1|max:10',
            'questions.*.rating_max' => 'nullable|integer|min:2|max:10',
            'questions.*.rating_min_label' => 'nullable|string|max:100',
            'questions.*.rating_max_label' => 'nullable|string|max:100',
            'questions.*.is_required' => 'nullable|boolean',
        ]);

        $old = $evaluation_template->toArray();

        // Increment version number to protect immutable snapshots of past events
        $evaluation_template->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'version' => $evaluation_template->version + 1,
        ]);

        // Recreate questions for this template version
        $evaluation_template->questions()->delete();

        foreach ($validated['questions'] as $order => $qData) {
            $options = null;
            if (in_array($qData['question_type'], ['single_choice', 'multiple_choice']) && !empty($qData['options'])) {
                $options = array_values(array_filter(array_map('trim', explode("\n", str_replace("\r", "", $qData['options'])))));
            }

            $evaluation_template->questions()->create([
                'question_text' => $qData['question_text'],
                'question_type' => $qData['question_type'],
                'options' => $options,
                'rating_min' => $qData['rating_min'] ?? 1,
                'rating_max' => $qData['rating_max'] ?? 5,
                'rating_min_label' => $qData['rating_min_label'] ?? 'Poor',
                'rating_max_label' => $qData['rating_max_label'] ?? 'Excellent',
                'is_required' => !empty($qData['is_required']),
                'sort_order' => $order + 1,
            ]);
        }

        AuditLogger::log('evaluation.template_updated', $evaluation_template, $old, $evaluation_template->fresh()->toArray(), 'Evaluation template updated to version ' . $evaluation_template->version . '.');

        return redirect()->route('admin.evaluation-templates.index')
            ->with('success', "Evaluation template '{$evaluation_template->title}' updated to version {$evaluation_template->version}.");
    }

    public function toggleActive(EvaluationTemplate $evaluation_template): RedirectResponse
    {
        $old = ['is_active' => $evaluation_template->is_active];
        $evaluation_template->update(['is_active' => !$evaluation_template->is_active]);

        $statusStr = $evaluation_template->is_active ? 'activated' : 'deactivated';
        AuditLogger::log('evaluation.template_status_toggled', $evaluation_template, $old, ['is_active' => $evaluation_template->is_active], "Template {$statusStr}.");

        return back()->with('success', "Template '{$evaluation_template->title}' has been {$statusStr}.");
    }
}
