<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityRegistration;
use App\Models\ActivityRegistrationAnswer;
use App\Models\ActivitySession;
use App\Models\ActivitySessionRegistration;
use App\Models\RegistrationForm;
use App\Models\RegistrationFormField;
use App\Models\RegistrationFormOption;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DynamicFormService
{
    public function createForm(array $data, User $superAdmin): RegistrationForm
    {
        return DB::transaction(function () use ($data, $superAdmin) {
            $slug = $this->generateUniqueSlug($data['title']);

            $form = RegistrationForm::create([
                'title' => $data['title'],
                'slug' => $slug,
                'description' => $data['description'] ?? null,
                'intended_categories' => $data['intended_categories'] ?? [],
                'status' => RegistrationForm::STATUS_DRAFT,
                'version' => 1,
                'is_template' => (bool) ($data['is_template'] ?? false),
                'created_by' => $superAdmin->id,
                'updated_by' => $superAdmin->id,
            ]);

            if (!empty($data['fields']) && is_array($data['fields'])) {
                $this->syncFields($form, $data['fields']);
            }

            AuditLogger::log('registration_form.created', $form, [], [
                'title' => $form->title,
                'status' => $form->status,
                'is_template' => $form->is_template,
            ]);

            return $form->load('fields.options');
        });
    }

    public function updateForm(RegistrationForm $form, array $data, User $superAdmin): RegistrationForm
    {
        return DB::transaction(function () use ($form, $data, $superAdmin) {
            $oldValues = $form->only(['title', 'description', 'intended_categories', 'status', 'version', 'is_template']);

            $form->update([
                'title' => $data['title'] ?? $form->title,
                'description' => array_key_exists('description', $data) ? $data['description'] : $form->description,
                'intended_categories' => $data['intended_categories'] ?? $form->intended_categories,
                'is_template' => array_key_exists('is_template', $data) ? (bool) $data['is_template'] : $form->is_template,
                'updated_by' => $superAdmin->id,
            ]);

            if (array_key_exists('fields', $data) && is_array($data['fields'])) {
                $this->syncFields($form, $data['fields']);
            }

            AuditLogger::log('registration_form.updated', $form, $oldValues, [
                'title' => $form->title,
                'status' => $form->status,
                'version' => $form->version,
            ]);

            return $form->fresh(['fields.options']);
        });
    }

    public function syncFields(RegistrationForm $form, array $fieldsPayload): void
    {
        $keptFieldIds = [];

        foreach (array_values($fieldsPayload) as $idx => $fieldData) {
            if (empty($fieldData['label']) || empty($fieldData['question_type'])) {
                continue;
            }

            $fieldId = $fieldData['id'] ?? null;
            $existingField = $fieldId
                ? $form->fields()->whereKey($fieldId)->first()
                : null;

            $fieldKey = !empty($fieldData['field_key'])
                ? Str::slug($fieldData['field_key'], '_')
                : ($existingField?->field_key ?: 'q_' . ($idx + 1) . '_' . Str::lower(Str::random(5)));

            $validationRules = $fieldData['validation_rules'] ?? [];
            if ($fieldData['question_type'] === 'file_upload') {
                $validationRules['accepted_file_types'] = $validationRules['accepted_file_types']
                    ?? ($fieldData['accepted_file_types'] ?? ['pdf', 'png', 'jpg', 'jpeg', 'doc', 'docx', 'zip']);
                if (is_string($validationRules['accepted_file_types'])) {
                    $validationRules['accepted_file_types'] = array_values(array_filter(array_map(
                        fn ($ext) => strtolower(trim($ext, " .")),
                        explode(',', $validationRules['accepted_file_types'])
                    )));
                }
                $validationRules['max_file_size_kb'] = (int) ($validationRules['max_file_size_kb']
                    ?? ($fieldData['max_file_size_kb'] ?? 5120));
            }

            $attributes = [
                'field_key' => $fieldKey,
                'label' => $fieldData['label'],
                'help_text' => $fieldData['help_text'] ?? null,
                'placeholder' => $fieldData['placeholder'] ?? null,
                'question_type' => $fieldData['question_type'],
                'is_required' => (bool) ($fieldData['is_required'] ?? false),
                'is_enabled' => array_key_exists('is_enabled', $fieldData) ? (bool) $fieldData['is_enabled'] : true,
                'sort_order' => (int) ($fieldData['sort_order'] ?? $idx),
                'validation_rules' => $validationRules,
            ];

            if ($existingField) {
                $existingField->update($attributes);
                $field = $existingField;
            } else {
                $field = $form->fields()->create($attributes);
            }

            $keptFieldIds[] = $field->id;

            if (in_array($field->question_type, RegistrationFormField::OPTION_TYPES, true)) {
                $this->syncOptions($field, $fieldData['options'] ?? []);
            } else {
                $field->options()->delete();
            }
        }

        if (!empty($keptFieldIds)) {
            $form->fields()->whereNotIn('id', $keptFieldIds)->delete();
        } else {
            $form->fields()->delete();
        }
    }

    public function syncOptions(RegistrationFormField $field, array $optionsPayload): void
    {
        $keptOptionIds = [];

        foreach (array_values($optionsPayload) as $idx => $optData) {
            if (is_string($optData)) {
                $optData = ['label' => $optData, 'value' => $optData];
            }

            $label = trim((string) ($optData['label'] ?? ''));
            if ($label === '') {
                continue;
            }

            $value = trim((string) ($optData['value'] ?? $label));
            $optId = $optData['id'] ?? null;
            $existingOpt = $optId ? $field->options()->whereKey($optId)->first() : null;

            $attrs = [
                'label' => $label,
                'value' => $value,
                'sort_order' => (int) ($optData['sort_order'] ?? $idx),
                'is_enabled' => array_key_exists('is_enabled', $optData) ? (bool) $optData['is_enabled'] : true,
            ];

            if ($existingOpt) {
                $existingOpt->update($attrs);
                $keptOptionIds[] = $existingOpt->id;
            } else {
                $created = $field->options()->create($attrs);
                $keptOptionIds[] = $created->id;
            }
        }

        if (!empty($keptOptionIds)) {
            $field->options()->whereNotIn('id', $keptOptionIds)->delete();
        } else {
            $field->options()->delete();
        }
    }

    public function publishForm(RegistrationForm $form, User $superAdmin): RegistrationForm
    {
        return DB::transaction(function () use ($form, $superAdmin) {
            $nextVersion = $form->status === RegistrationForm::STATUS_PUBLISHED
                ? $form->version + 1
                : max(1, $form->version);

            $form->version = $nextVersion;
            $snapshot = $form->buildSnapshot();

            $form->update([
                'status' => RegistrationForm::STATUS_PUBLISHED,
                'version' => $nextVersion,
                'published_snapshot' => $snapshot,
                'published_at' => now(),
                'updated_by' => $superAdmin->id,
            ]);

            AuditLogger::log('registration_form.published', $form, [], [
                'version' => $form->version,
                'fields_count' => count($snapshot['fields'] ?? []),
            ]);

            return $form->fresh(['fields.options']);
        });
    }

    public function archiveForm(RegistrationForm $form, User $superAdmin): RegistrationForm
    {
        $oldStatus = $form->status;
        $form->update([
            'status' => RegistrationForm::STATUS_ARCHIVED,
            'archived_at' => now(),
            'updated_by' => $superAdmin->id,
        ]);

        AuditLogger::log('registration_form.archived', $form, ['status' => $oldStatus], ['status' => 'archived']);

        return $form;
    }

    public function duplicateForm(RegistrationForm $source, User $superAdmin, ?string $newTitle = null, bool $asTemplate = false): RegistrationForm
    {
        return DB::transaction(function () use ($source, $superAdmin, $newTitle, $asTemplate) {
            $source->loadMissing('fields.options');
            $title = $newTitle ?: ($source->title . ' (Copy)');

            $copy = RegistrationForm::create([
                'title' => $title,
                'slug' => $this->generateUniqueSlug($title),
                'description' => $source->description,
                'intended_categories' => $source->intended_categories,
                'status' => RegistrationForm::STATUS_DRAFT,
                'version' => 1,
                'is_template' => $asTemplate,
                'parent_form_id' => $source->id,
                'created_by' => $superAdmin->id,
                'updated_by' => $superAdmin->id,
            ]);

            foreach ($source->fields as $field) {
                $clonedField = $copy->fields()->create([
                    'field_key' => $field->field_key,
                    'label' => $field->label,
                    'help_text' => $field->help_text,
                    'placeholder' => $field->placeholder,
                    'question_type' => $field->question_type,
                    'is_required' => $field->is_required,
                    'is_enabled' => $field->is_enabled,
                    'sort_order' => $field->sort_order,
                    'validation_rules' => $field->validation_rules,
                ]);

                foreach ($field->options as $option) {
                    $clonedField->options()->create([
                        'label' => $option->label,
                        'value' => $option->value,
                        'sort_order' => $option->sort_order,
                        'is_enabled' => $option->is_enabled,
                    ]);
                }
            }

            AuditLogger::log('registration_form.duplicated', $copy, ['source_form_id' => $source->id], [
                'title' => $copy->title,
                'is_template' => $copy->is_template,
            ]);

            return $copy->load('fields.options');
        });
    }

    public function assignFormToActivity(Activity $activity, ?RegistrationForm $form, User $superAdmin): Activity
    {
        $oldFormId = $activity->registration_form_id;

        if ($form === null) {
            $activity->update([
                'registration_form_id' => null,
                'form_version' => null,
                'form_snapshot' => null,
            ]);
        } else {
            $snapshot = $form->getActiveSchema();
            $activity->update([
                'registration_form_id' => $form->id,
                'form_version' => $form->version,
                'form_snapshot' => $snapshot,
            ]);
        }

        AuditLogger::log('registration_form.assigned_to_activity', $activity, [
            'registration_form_id' => $oldFormId,
        ], [
            'registration_form_id' => $form?->id,
            'form_version' => $form?->version,
        ]);

        return $activity;
    }

    public function assignFormToSession(ActivitySession $session, ?RegistrationForm $form, User $superAdmin): ActivitySession
    {
        $oldFormId = $session->registration_form_id;

        if ($form === null) {
            $session->update([
                'registration_form_id' => null,
                'form_version' => null,
                'form_snapshot' => null,
            ]);
        } else {
            $snapshot = $form->getActiveSchema();
            $session->update([
                'registration_form_id' => $form->id,
                'form_version' => $form->version,
                'form_snapshot' => $snapshot,
            ]);
        }

        AuditLogger::log('registration_form.assigned_to_session', $session, [
            'registration_form_id' => $oldFormId,
        ], [
            'registration_form_id' => $form?->id,
            'form_version' => $form?->version,
        ]);

        return $session;
    }

    /**
     * Validate user-submitted answers against a form schema snapshot.
     * Enforces required fields, option whitelists, number/date formats, and file upload types & sizes.
     */
    public function validateSubmissionAgainstSchema(array $schema, array $inputAnswers, array $uploadedFiles = [], string $inputPrefix = 'answers'): array
    {
        $fields = $schema['fields'] ?? [];
        if (empty($fields)) {
            return [];
        }

        $rules = [];
        $messages = [];
        $attributes = [];
        $payload = [$inputPrefix => []];

        foreach ($fields as $field) {
            if (!($field['is_enabled'] ?? true)) {
                continue;
            }

            $key = $field['field_key'];
            $label = $field['label'];
            $type = $field['question_type'];
            $required = (bool) ($field['is_required'] ?? false);
            $fieldRules = $field['validation_rules'] ?? [];
            $options = collect($field['options'] ?? [])->pluck('value')->all();

            $attrPath = "{$inputPrefix}.{$key}";
            $attributes[$attrPath] = $label;

            if ($type === 'file_upload') {
                $payload[$inputPrefix][$key] = $uploadedFiles[$key] ?? null;
                $r = [$required ? 'required' : 'nullable', 'file'];

                $acceptedExts = $fieldRules['accepted_file_types'] ?? ['pdf', 'png', 'jpg', 'jpeg', 'doc', 'docx', 'zip'];
                if (is_string($acceptedExts)) {
                    $acceptedExts = array_filter(array_map('trim', explode(',', $acceptedExts)));
                }
                if (!empty($acceptedExts)) {
                    $r[] = 'mimes:' . implode(',', $acceptedExts);
                }

                $maxKb = (int) ($fieldRules['max_file_size_kb'] ?? 5120);
                if ($maxKb > 0) {
                    $r[] = 'max:' . $maxKb;
                }

                $rules[$attrPath] = $r;
                continue;
            }

            $payload[$inputPrefix][$key] = $inputAnswers[$key] ?? null;
            $baseRule = [$required ? 'required' : 'nullable'];

            switch ($type) {
                case 'short_answer':
                    $baseRule[] = 'string';
                    $baseRule[] = 'max:255';
                    break;
                case 'paragraph':
                    $baseRule[] = 'string';
                    $baseRule[] = 'max:5000';
                    break;
                case 'number':
                    $baseRule[] = 'numeric';
                    if (isset($fieldRules['min']) && $fieldRules['min'] !== '') {
                        $baseRule[] = 'min:' . $fieldRules['min'];
                    }
                    if (isset($fieldRules['max']) && $fieldRules['max'] !== '') {
                        $baseRule[] = 'max:' . $fieldRules['max'];
                    }
                    break;
                case 'date':
                    $baseRule[] = 'date';
                    break;
                case 'yes_no':
                    $baseRule[] = Rule::in(['Yes', 'No', 'yes', 'no', '1', '0', 1, 0, true, false]);
                    break;
                case 'dropdown':
                case 'multiple_choice':
                    $baseRule[] = 'string';
                    $baseRule[] = Rule::in($options);
                    break;
                case 'checkboxes':
                    $baseRule[] = 'array';
                    if ($required) {
                        $baseRule[] = 'min:1';
                    }
                    $rules["{$attrPath}.*"] = ['required', 'string', Rule::in($options)];
                    $attributes["{$attrPath}.*"] = $label;
                    break;
            }

            $rules[$attrPath] = $baseRule;
        }

        $validator = Validator::make($payload, $rules, $messages, $attributes);
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated()[$inputPrefix] ?? [];

        return $validated;
    }

    /**
     * Persist validated dynamic answers for a parent registration or session registration.
     */
    public function storeValidatedAnswers(
        ActivityRegistration $registration,
        ?ActivitySessionRegistration $sessionRegistration,
        array $schema,
        array $validatedAnswers,
        array $uploadedFiles = []
    ): void {
        $fields = collect($schema['fields'] ?? [])->keyBy('field_key');

        // Clear previous answers for this scope (supports submission editing)
        $deleteQuery = ActivityRegistrationAnswer::where('activity_registration_id', $registration->id);
        if ($sessionRegistration) {
            $deleteQuery->where('activity_session_registration_id', $sessionRegistration->id);
        } else {
            $deleteQuery->whereNull('activity_session_registration_id');
        }
        $deleteQuery->delete();

        foreach ($fields as $key => $field) {
            if (!($field['is_enabled'] ?? true)) {
                continue;
            }

            $type = $field['question_type'];
            $value = $validatedAnswers[$key] ?? null;

            $answerData = [
                'activity_registration_id' => $registration->id,
                'activity_session_registration_id' => $sessionRegistration?->id,
                'registration_form_field_id' => $field['id'] ?? null,
                'field_key' => $key,
                'field_label' => $field['label'],
                'question_type' => $type,
                'answer_text' => null,
                'answer_json' => null,
                'file_path' => null,
                'file_original_name' => null,
                'file_mime_type' => null,
                'file_size' => null,
            ];

            if ($type === 'file_upload') {
                /** @var UploadedFile|null $file */
                $file = $uploadedFiles[$key] ?? ($value instanceof UploadedFile ? $value : null);
                if (!$file) {
                    continue;
                }
                $storedPath = $file->store("activity-registrations/{$registration->activity_id}/{$registration->user_id}", 'local');
                $answerData['file_path'] = $storedPath;
                $answerData['file_original_name'] = $file->getClientOriginalName();
                $answerData['file_mime_type'] = $file->getClientMimeType();
                $answerData['file_size'] = $file->getSize();
                $answerData['answer_text'] = $file->getClientOriginalName();
            } elseif ($type === 'checkboxes') {
                $arr = is_array($value) ? array_values($value) : [];
                $answerData['answer_json'] = $arr;
                $answerData['answer_text'] = implode(', ', $arr);
            } elseif ($type === 'yes_no') {
                if ($value !== null && $value !== '') {
                    $normalized = in_array($value, ['Yes', 'yes', '1', 1, true], true) ? 'Yes' : 'No';
                    $answerData['answer_text'] = $normalized;
                }
            } else {
                $answerData['answer_text'] = $value !== null ? (string) $value : null;
            }

            ActivityRegistrationAnswer::create($answerData);
        }
    }

    private function generateUniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'registration-form';
        $slug = $base;
        $counter = 1;

        while (RegistrationForm::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base . '-' . (++$counter);
        }

        return $slug;
    }
}

