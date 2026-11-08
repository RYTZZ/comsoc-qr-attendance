<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivitySession;
use App\Models\RegistrationForm;
use App\Models\RegistrationFormField;
use App\Services\AuditLogger;
use App\Services\DynamicFormService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RegistrationFormBuilderController extends Controller
{
    public function __construct(
        private DynamicFormService $formService
    ) {}

    private function assertSuperAdmin(Request $request): void
    {
        abort_unless($request->user() && $request->user()->isSuperAdmin(), 403, 'Only the Super Admin can access the Registration Form Builder.');
    }

    public function index(Request $request): View
    {
        $this->assertSuperAdmin($request);

        $query = RegistrationForm::withCount(['fields', 'activities', 'sessions'])
            ->with('creator')
            ->latest('updated_at');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($category = $request->query('category')) {
            $query->whereJsonContains('intended_categories', $category);
        }

        if ($request->boolean('templates_only')) {
            $query->where('is_template', true);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $forms = $query->paginate(12)->withQueryString();
        $categories = RegistrationForm::CATEGORIES;

        return view('admin.registration-forms.index', compact('forms', 'categories'));
    }

    public function create(Request $request): View
    {
        $this->assertSuperAdmin($request);

        $categories = RegistrationForm::CATEGORIES;
        $questionTypes = RegistrationFormField::QUESTION_TYPES;
        $templates = RegistrationForm::where('is_template', true)
            ->where('status', '!=', RegistrationForm::STATUS_ARCHIVED)
            ->orderBy('title')
            ->get();

        return view('admin.registration-forms.builder', [
            'form' => null,
            'categories' => $categories,
            'questionTypes' => $questionTypes,
            'templates' => $templates,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->assertSuperAdmin($request);

        $validated = $this->validateFormPayload($request);
        $form = $this->formService->createForm($validated, $request->user());

        if ($request->boolean('publish_now')) {
            $form = $this->formService->publishForm($form, $request->user());
            return redirect()->route('admin.registration-forms.edit', $form)
                ->with('success', "Registration form '{$form->title}' created and published (v{$form->version}).");
        }

        return redirect()->route('admin.registration-forms.edit', $form)
            ->with('success', "Registration form draft '{$form->title}' saved.");
    }

    public function edit(Request $request, RegistrationForm $registrationForm): View
    {
        $this->assertSuperAdmin($request);

        $registrationForm->load(['fields.options', 'activities', 'sessions.activity']);
        $categories = RegistrationForm::CATEGORIES;
        $questionTypes = RegistrationFormField::QUESTION_TYPES;
        $activities = Activity::with('sessions')->orderByDesc('created_at')->get();

        return view('admin.registration-forms.builder', [
            'form' => $registrationForm,
            'categories' => $categories,
            'questionTypes' => $questionTypes,
            'activities' => $activities,
        ]);
    }

    public function update(Request $request, RegistrationForm $registrationForm): RedirectResponse
    {
        $this->assertSuperAdmin($request);

        $validated = $this->validateFormPayload($request);
        $form = $this->formService->updateForm($registrationForm, $validated, $request->user());

        if ($request->boolean('publish_now')) {
            $form = $this->formService->publishForm($form, $request->user());
            return redirect()->route('admin.registration-forms.edit', $form)
                ->with('success', "Form '{$form->title}' updated and published as v{$form->version}.");
        }

        return redirect()->route('admin.registration-forms.edit', $form)
            ->with('success', "Form '{$form->title}' updated.");
    }

    public function preview(Request $request, RegistrationForm $registrationForm): View
    {
        $this->assertSuperAdmin($request);

        $registrationForm->load('fields.options');
        $schema = $registrationForm->buildSnapshot();

        return view('admin.registration-forms.preview', [
            'form' => $registrationForm,
            'schema' => $schema,
        ]);
    }

    public function publish(Request $request, RegistrationForm $registrationForm): RedirectResponse
    {
        $this->assertSuperAdmin($request);

        $form = $this->formService->publishForm($registrationForm, $request->user());

        return back()->with('success', "Form '{$form->title}' published as version {$form->version}.");
    }

    public function archive(Request $request, RegistrationForm $registrationForm): RedirectResponse
    {
        $this->assertSuperAdmin($request);

        $form = $this->formService->archiveForm($registrationForm, $request->user());

        return back()->with('success', "Form '{$form->title}' archived.");
    }

    public function duplicate(Request $request, RegistrationForm $registrationForm): RedirectResponse
    {
        $this->assertSuperAdmin($request);

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'as_template' => 'nullable|boolean',
        ]);

        $copy = $this->formService->duplicateForm(
            $registrationForm,
            $request->user(),
            $validated['title'] ?? null,
            (bool) ($validated['as_template'] ?? false)
        );

        return redirect()->route('admin.registration-forms.edit', $copy)
            ->with('success', "Form duplicated as '{$copy->title}'.");
    }

    public function assign(Request $request, RegistrationForm $registrationForm): RedirectResponse
    {
        $this->assertSuperAdmin($request);

        $validated = $request->validate([
            'target_type' => ['required', Rule::in(['activity', 'session'])],
            'target_id' => ['required', 'uuid'],
        ]);

        if ($registrationForm->status !== RegistrationForm::STATUS_PUBLISHED) {
            $registrationForm = $this->formService->publishForm($registrationForm, $request->user());
        }

        if ($validated['target_type'] === 'activity') {
            $activity = Activity::findOrFail($validated['target_id']);
            $this->formService->assignFormToActivity($activity, $registrationForm, $request->user());
            return back()->with('success', "Assigned form '{$registrationForm->title}' (v{$registrationForm->version}) to parent activity '{$activity->title}'.");
        }

        $session = ActivitySession::findOrFail($validated['target_id']);
        $this->formService->assignFormToSession($session, $registrationForm, $request->user());

        return back()->with('success', "Assigned form '{$registrationForm->title}' (v{$registrationForm->version}) to session '{$session->name}'.");
    }

    public function destroy(Request $request, RegistrationForm $registrationForm): RedirectResponse
    {
        $this->assertSuperAdmin($request);

        $title = $registrationForm->title;
        AuditLogger::log('registration_form.deleted', $registrationForm, ['title' => $title], []);
        $registrationForm->delete();

        return redirect()->route('admin.registration-forms.index')
            ->with('success', "Form '{$title}' moved to trash.");
    }

    private function validateFormPayload(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'intended_categories' => ['nullable', 'array'],
            'intended_categories.*' => ['string', 'max:100'],
            'is_template' => ['nullable', 'boolean'],
            'fields' => ['nullable', 'array'],
            'fields.*.id' => ['nullable', 'uuid'],
            'fields.*.field_key' => ['nullable', 'string', 'max:100'],
            'fields.*.label' => ['required_with:fields', 'string', 'max:255'],
            'fields.*.help_text' => ['nullable', 'string', 'max:1000'],
            'fields.*.placeholder' => ['nullable', 'string', 'max:255'],
            'fields.*.question_type' => ['required_with:fields', Rule::in(array_keys(RegistrationFormField::QUESTION_TYPES))],
            'fields.*.is_required' => ['nullable', 'boolean'],
            'fields.*.is_enabled' => ['nullable', 'boolean'],
            'fields.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'fields.*.accepted_file_types' => ['nullable'],
            'fields.*.max_file_size_kb' => ['nullable', 'integer', 'min:64', 'max:51200'],
            'fields.*.validation_rules' => ['nullable', 'array'],
            'fields.*.options' => ['nullable', 'array'],
            'fields.*.options.*.id' => ['nullable', 'uuid'],
            'fields.*.options.*.label' => ['nullable', 'string', 'max:255'],
            'fields.*.options.*.value' => ['nullable', 'string', 'max:255'],
            'fields.*.options.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'fields.*.options.*.is_enabled' => ['nullable', 'boolean'],
        ]);
    }
}

