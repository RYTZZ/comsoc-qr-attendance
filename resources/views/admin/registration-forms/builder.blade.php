<x-layouts.admin :title="$form ? 'Edit Form — ' . $form->title : 'Create Registration Form'">
@php
    $initialFields = [];
    if ($form) {
        $initialFields = $form->fields->map(fn($f) => [
            'id' => $f->id,
            'field_key' => $f->field_key,
            'label' => $f->label,
            'help_text' => $f->help_text ?? '',
            'placeholder' => $f->placeholder ?? '',
            'question_type' => $f->question_type,
            'is_required' => (bool) $f->is_required,
            'is_enabled' => (bool) $f->is_enabled,
            'accepted_file_types' => is_array($f->validation_rules['accepted_file_types'] ?? null)
                ? implode(',', $f->validation_rules['accepted_file_types'])
                : ($f->validation_rules['accepted_file_types'] ?? 'pdf,png,jpg,jpeg,doc,docx,zip'),
            'max_file_size_kb' => $f->validation_rules['max_file_size_kb'] ?? 5120,
            'options' => $f->options->map(fn($o) => [
                'id' => $o->id,
                'label' => $o->label,
                'value' => $o->value,
                'is_enabled' => (bool) $o->is_enabled,
            ])->values()->all(),
        ])->values()->all();
    }
@endphp

<div class="page-header mb-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <a href="{{ route('admin.registration-forms.index') }}" class="text-xs text-slate-400 hover:text-white inline-flex items-center gap-1 mb-1">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                Back to Registration Form Builder
            </a>
            <h1 class="page-title font-heading text-2xl font-bold text-white">
                {{ $form ? "Configure Form: {$form->title} (v{$form->version})" : 'Create Dynamic Registration Form' }}
            </h1>
        </div>

        @if($form)
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.registration-forms.preview', $form) }}" class="btn-secondary text-xs">
                    <i data-lucide="eye" class="w-4 h-4 mr-1"></i> Preview Form
                </a>
                <form method="POST" action="{{ route('admin.registration-forms.duplicate', $form) }}" class="inline">
                    @csrf
                    <input type="hidden" name="as_template" value="1">
                    <button type="submit" class="btn-secondary text-xs">Save Copy as Template</button>
                </form>
            </div>
        @endif
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6"
     x-data="{
        fields: {{ Js::from($initialFields) }},
        addQuestion() {
            this.fields.push({
                id: null,
                field_key: 'question_' + (this.fields.length + 1) + '_' + Math.random().toString(36).substring(2, 6),
                label: '',
                help_text: '',
                placeholder: '',
                question_type: 'short_answer',
                is_required: true,
                is_enabled: true,
                accepted_file_types: 'pdf,png,jpg,jpeg,doc,docx,zip',
                max_file_size_kb: 5120,
                options: [
                    { id: null, label: 'Option 1', value: 'Option 1', is_enabled: true }
                ]
            });
        },
        removeQuestion(index) {
            this.fields.splice(index, 1);
        },
        moveQuestionUp(index) {
            if (index > 0) {
                const temp = this.fields[index];
                this.fields.splice(index, 1);
                this.fields.splice(index - 1, 0, temp);
            }
        },
        moveQuestionDown(index) {
            if (index < this.fields.length - 1) {
                const temp = this.fields[index];
                this.fields.splice(index, 1);
                this.fields.splice(index + 1, 0, temp);
            }
        },
        supportsOptions(type) {
            return ['dropdown', 'multiple_choice', 'checkboxes'].includes(type);
        },
        addOption(qIndex) {
            const count = this.fields[qIndex].options.length + 1;
            this.fields[qIndex].options.push({
                id: null,
                label: 'Option ' + count,
                value: 'Option ' + count,
                is_enabled: true
            });
        },
        removeOption(qIndex, oIndex) {
            this.fields[qIndex].options.splice(oIndex, 1);
        },
        moveOptionUp(qIndex, oIndex) {
            if (oIndex > 0) {
                const opts = this.fields[qIndex].options;
                const temp = opts[oIndex];
                opts.splice(oIndex, 1);
                opts.splice(oIndex - 1, 0, temp);
            }
        },
        moveOptionDown(qIndex, oIndex) {
            const opts = this.fields[qIndex].options;
            if (oIndex < opts.length - 1) {
                const temp = opts[oIndex];
                opts.splice(oIndex, 1);
                opts.splice(oIndex + 1, 0, temp);
            }
        }
     }">

    <!-- Left 2 Columns: Form Metadata & Dynamic Questions Editor -->
    <div class="lg:col-span-2 space-y-6">
        <form method="POST"
              action="{{ $form ? route('admin.registration-forms.update', $form) : route('admin.registration-forms.store') }}"
              class="space-y-6">
            @csrf
            @if($form) @method('PUT') @endif

            <!-- General Form Settings -->
            <div class="card bg-[#172033] border border-slate-800 p-5 space-y-4">
                <h2 class="text-sm font-bold text-white uppercase tracking-wider border-b border-slate-800 pb-2">
                    Form Identity & Configuration
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Form Title *</label>
                        <input type="text" name="title" value="{{ old('title', $form?->title) }}" required
                               placeholder="e.g., COMSOC Hackathon 2026 Team & Project Registration Form"
                               class="input w-full text-xs bg-[#0B1120] border-slate-700 text-white">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Description / Instructions</label>
                        <textarea name="description" rows="2"
                                  placeholder="Instructions displayed to students at the top of this registration form..."
                                  class="input w-full text-xs bg-[#0B1120] border-slate-700 text-white">{{ old('description', $form?->description) }}</textarea>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Intended Activity Categories</label>
                        @php $selectedCats = old('intended_categories', $form?->intended_categories ?? []); @endphp
                        <div class="flex flex-wrap gap-2">
                            @foreach($categories as $cat)
                                <label class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-[#0B1120] border border-slate-700 text-xs text-slate-300 cursor-pointer">
                                    <input type="checkbox" name="intended_categories[]" value="{{ $cat }}"
                                           @checked(in_array($cat, $selectedCats, true))
                                           class="rounded bg-slate-900 border-slate-700 text-[#560E0F]">
                                    <span>{{ $cat }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label class="inline-flex items-center gap-2 text-xs text-slate-200 cursor-pointer">
                            <input type="checkbox" name="is_template" value="1"
                                   @checked(old('is_template', $form?->is_template))
                                   class="rounded bg-slate-900 border-slate-700 text-[#560E0F]">
                            <span>Mark as Reusable Form Template</span>
                        </label>
                    </div>
                </div>

                <div class="p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-[11px] text-emerald-200">
                    <strong>Automatic Profile Integration Active:</strong> Student Full Name, Student ID, Email Address, Program, and Year Level are automatically retrieved from the authenticated COMSOC Hub account. Do not add fields for those attributes.
                </div>
            </div>

            <!-- Dynamic Questions Builder -->
            <div class="card bg-[#172033] border border-slate-800 p-5 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div>
                        <h2 class="text-sm font-bold text-white uppercase tracking-wider">Dynamic Questions</h2>
                        <p class="text-[11px] text-slate-400">Add, reorder, enable/disable questions, and configure answer options or file rules.</p>
                    </div>
                    <button type="button" @click="addQuestion()" class="btn-primary btn-sm text-xs flex items-center gap-1.5">
                        <i data-lucide="plus-circle" class="w-4 h-4"></i>
                        <span>Add Question</span>
                    </button>
                </div>

                <template x-if="fields.length === 0">
                    <div class="p-8 text-center rounded-xl bg-[#0B1120]/60 border border-dashed border-slate-700 text-xs text-slate-400">
                        No questions added yet. Click <strong>Add Question</strong> to add your first activity-specific question.
                    </div>
                </template>

                <div class="space-y-4">
                    <template x-for="(field, qIndex) in fields" :key="qIndex">
                        <div class="p-4 rounded-xl bg-[#0B1120] border border-slate-700/80 space-y-3">
                            <input type="hidden" :name="`fields[${qIndex}][id]`" x-model="field.id">
                            <input type="hidden" :name="`fields[${qIndex}][field_key]`" x-model="field.field_key">
                            <input type="hidden" :name="`fields[${qIndex}][sort_order]`" :value="qIndex">

                            <div class="flex flex-wrap items-center justify-between gap-2 pb-2 border-b border-slate-800">
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 rounded bg-[#560E0F] text-white text-[10px] font-bold" x-text="`Q${qIndex + 1}`"></span>
                                    <span class="text-xs font-semibold text-white" x-text="field.label || 'Untitled Question'"></span>
                                </div>

                                <div class="flex items-center gap-1.5">
                                    <button type="button" @click="moveQuestionUp(qIndex)" :disabled="qIndex === 0"
                                            class="px-2 py-1 rounded bg-slate-800 text-slate-300 hover:text-white text-[11px] disabled:opacity-40">
                                        ↑ Up
                                    </button>
                                    <button type="button" @click="moveQuestionDown(qIndex)" :disabled="qIndex === fields.length - 1"
                                            class="px-2 py-1 rounded bg-slate-800 text-slate-300 hover:text-white text-[11px] disabled:opacity-40">
                                        ↓ Down
                                    </button>
                                    <button type="button" @click="removeQuestion(qIndex)"
                                            class="px-2 py-1 rounded bg-rose-500/20 text-rose-300 hover:bg-rose-500/30 text-[11px]">
                                        Delete
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div class="md:col-span-2">
                                    <label class="block text-[11px] font-semibold text-slate-400 mb-1">Question Label *</label>
                                    <input type="text" :name="`fields[${qIndex}][label]`" x-model="field.label" required
                                           placeholder="e.g., Preferred Workshop Track / Problem Statement"
                                           class="input w-full text-xs bg-[#172033] border-slate-700 text-white">
                                </div>

                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-400 mb-1">Answer Type *</label>
                                    <select :name="`fields[${qIndex}][question_type]`" x-model="field.question_type"
                                            class="select w-full text-xs bg-[#172033] border-slate-700 text-white">
                                        @foreach($questionTypes as $typeKey => $typeLabel)
                                            <option value="{{ $typeKey }}">{{ $typeLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-[11px] font-semibold text-slate-400 mb-1">Help Text / Guidance</label>
                                    <input type="text" :name="`fields[${qIndex}][help_text]`" x-model="field.help_text"
                                           placeholder="Optional explanation or hint for students"
                                           class="input w-full text-xs bg-[#172033] border-slate-700 text-white">
                                </div>

                                <div class="flex items-center gap-4 pt-4">
                                    <label class="inline-flex items-center gap-1.5 text-xs text-slate-300 cursor-pointer">
                                        <input type="checkbox" :name="`fields[${qIndex}][is_required]`" value="1" x-model="field.is_required"
                                               class="rounded bg-slate-900 border-slate-700 text-[#560E0F]">
                                        <span>Required</span>
                                    </label>

                                    <label class="inline-flex items-center gap-1.5 text-xs text-slate-300 cursor-pointer">
                                        <input type="checkbox" :name="`fields[${qIndex}][is_enabled]`" value="1" x-model="field.is_enabled"
                                               class="rounded bg-slate-900 border-slate-700 text-[#560E0F]">
                                        <span>Enabled</span>
                                    </label>
                                </div>
                            </div>

                            <!-- File Upload Configuration -->
                            <template x-if="field.question_type === 'file_upload'">
                                <div class="p-3 rounded-lg bg-[#172033] border border-slate-700 grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-300 mb-1">Accepted File Extensions (comma-separated)</label>
                                        <input type="text" :name="`fields[${qIndex}][accepted_file_types]`" x-model="field.accepted_file_types"
                                               placeholder="pdf,png,jpg,jpeg,doc,docx,zip"
                                               class="input w-full text-xs bg-[#0B1120] border-slate-700 text-white">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-300 mb-1">Maximum File Size (KB)</label>
                                        <input type="number" :name="`fields[${qIndex}][max_file_size_kb]`" x-model="field.max_file_size_kb"
                                               min="64" max="51200"
                                               class="input w-full text-xs bg-[#0B1120] border-slate-700 text-white">
                                    </div>
                                </div>
                            </template>

                            <!-- Options Manager for Dropdown, Multiple Choice, Checkboxes -->
                            <template x-if="supportsOptions(field.question_type)">
                                <div class="p-3 rounded-lg bg-[#172033] border border-slate-700 space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[11px] font-bold text-slate-300 uppercase tracking-wider">Answer Options</span>
                                        <button type="button" @click="addOption(qIndex)"
                                                class="px-2.5 py-1 rounded bg-[#560E0F] text-white text-[11px] font-semibold hover:bg-[#731416]">
                                            + Add Option
                                        </button>
                                    </div>

                                    <div class="space-y-2">
                                        <template x-for="(opt, oIndex) in field.options" :key="oIndex">
                                            <div class="flex items-center gap-2">
                                                <input type="hidden" :name="`fields[${qIndex}][options][${oIndex}][id]`" x-model="opt.id">
                                                <input type="hidden" :name="`fields[${qIndex}][options][${oIndex}][sort_order]`" :value="oIndex">
                                                <input type="text"
                                                       :name="`fields[${qIndex}][options][${oIndex}][label]`"
                                                       x-model="opt.label"
                                                       @input="opt.value = opt.label"
                                                       placeholder="Option label (e.g., Web Development)"
                                                       required
                                                       class="input flex-1 text-xs bg-[#0B1120] border-slate-700 text-white py-1.5">
                                                <input type="hidden" :name="`fields[${qIndex}][options][${oIndex}][value]`" :value="opt.label">

                                                <button type="button" @click="moveOptionUp(qIndex, oIndex)" :disabled="oIndex === 0"
                                                        class="px-2 py-1 rounded bg-slate-800 text-slate-300 text-[10px] disabled:opacity-40">↑</button>
                                                <button type="button" @click="moveOptionDown(qIndex, oIndex)" :disabled="oIndex === field.options.length - 1"
                                                        class="px-2 py-1 rounded bg-slate-800 text-slate-300 text-[10px] disabled:opacity-40">↓</button>
                                                <button type="button" @click="removeOption(qIndex, oIndex)"
                                                        class="px-2 py-1 rounded bg-rose-500/20 text-rose-300 text-[10px]">✕</button>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-3 pt-4 border-t border-slate-800">
                    <button type="submit" name="publish_now" value="0" class="btn-secondary text-xs py-2.5 px-5">
                        Save Draft
                    </button>
                    <button type="submit" name="publish_now" value="1" class="btn-primary text-xs py-2.5 px-5">
                        Save & Publish Form Snapshot
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Right Column: Form Assignment & Version Info -->
    <div class="space-y-6">
        @if($form)
            <div class="card bg-[#172033] border border-slate-800 p-5 space-y-4">
                <h3 class="text-sm font-bold text-white uppercase tracking-wider border-b border-slate-800 pb-2">
                    Assign Form to Activity or Session
                </h3>

                <form method="POST" action="{{ route('admin.registration-forms.assign', $form) }}" class="space-y-3" x-data="{ targetType: 'activity' }">
                    @csrf
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-400 mb-1">Assignment Level</label>
                        <select name="target_type" x-model="targetType" class="select w-full text-xs bg-[#0B1120] border-slate-700 text-white">
                            <option value="activity">Parent Activity / Event</option>
                            <option value="session">Individual Activity Session</option>
                        </select>
                    </div>

                    <div x-show="targetType === 'activity'">
                        <label class="block text-[11px] font-semibold text-slate-400 mb-1">Select Parent Activity</label>
                        <select :name="targetType === 'activity' ? 'target_id' : ''" class="select w-full text-xs bg-[#0B1120] border-slate-700 text-white">
                            @foreach($activities as $act)
                                <option value="{{ $act->id }}">{{ $act->title }} ({{ $act->category }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div x-show="targetType === 'session'" x-cloak>
                        <label class="block text-[11px] font-semibold text-slate-400 mb-1">Select Activity Session</label>
                        <select :name="targetType === 'session' ? 'target_id' : ''" class="select w-full text-xs bg-[#0B1120] border-slate-700 text-white">
                            @foreach($activities as $act)
                                @foreach($act->sessions as $sess)
                                    <option value="{{ $sess->id }}">{{ $act->title }} → {{ $sess->name }}</option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="btn-primary w-full text-xs py-2">
                        Assign Form Now
                    </button>
                </form>

                <div class="pt-3 border-t border-slate-800 space-y-2 text-xs">
                    <div class="font-semibold text-slate-300">Currently Assigned To:</div>
                    @forelse($form->activities as $act)
                        <div class="p-2 rounded bg-[#0B1120] border border-slate-800 text-slate-300 flex items-center justify-between">
                            <span>Parent: {{ $act->title }}</span>
                            <a href="{{ route('admin.activities.show', $act) }}" class="text-[#dfa6a9] hover:underline text-[11px]">View</a>
                        </div>
                    @empty
                    @endforelse
                    @forelse($form->sessions as $sess)
                        <div class="p-2 rounded bg-[#0B1120] border border-slate-800 text-slate-300 flex items-center justify-between">
                            <span>Session: {{ $sess->activity?->title }} — {{ $sess->name }}</span>
                        </div>
                    @empty
                    @endforelse
                </div>
            </div>
        @endif
    </div>
</div>
</x-layouts.admin>

