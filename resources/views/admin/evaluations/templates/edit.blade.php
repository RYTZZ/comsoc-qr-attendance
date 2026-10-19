<x-layouts.admin :title="'Edit Evaluation Template: ' . $template->title">
<div class="page-header">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.evaluation-templates.index') }}" class="btn-secondary btn-sm">← Back</a>
        <div>
            <h1 class="page-title">Edit Template: {{ $template->title }}</h1>
            <p class="text-xs text-slate-400 mt-0.5">Currently at Version {{ $template->version }}. Saving modifications will automatically publish version {{ $template->version + 1 }} while preserving past events' immutable snapshots.</p>
        </div>
    </div>
</div>

@if($errors->any())
    <div class="mb-5 p-4 rounded-xl bg-red-500/10 border border-red-500/30 text-red-300 text-xs space-y-1">
        <p class="font-bold text-red-400">Please correct the following errors:</p>
        <ul class="list-disc list-inside space-y-0.5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('admin.evaluation-templates.update', $template) }}" class="space-y-6 max-w-4xl" x-data="templateEditor()">
    @csrf
    @method('PUT')

    <div class="card space-y-4">
        <div class="border-b border-slate-800 pb-3 flex items-center justify-between">
            <h2 class="section-title text-base font-brand-display">Template Overview</h2>
            <div class="flex items-center gap-2">
                <span class="text-xs text-slate-400">Status:</span>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $template->is_active) ? 'checked' : '' }} class="sr-only peer">
                    <div class="w-9 h-5 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#560E0F]"></div>
                </label>
            </div>
        </div>

        <div class="space-y-4">
            <div>
                <label for="title" class="label">Template Title <span class="text-red-400">*</span></label>
                <input id="title" name="title" type="text" value="{{ old('title', $template->title) }}" required class="input" />
                @error('title')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="description" class="label">Description / Instructions</label>
                <textarea id="description" name="description" rows="2" class="input resize-none">{{ old('description', $template->description) }}</textarea>
                @error('description')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    <div class="card space-y-4">
        <div class="border-b border-slate-800 pb-3 flex items-center justify-between">
            <div>
                <h2 class="section-title text-base font-brand-display">Questions Builder (Version {{ $template->version + 1 }})</h2>
                <p class="text-xs text-slate-400">Add, edit, or adjust question options and requirements.</p>
            </div>
            <button type="button" @click="addQuestion()" class="btn-secondary btn-sm flex items-center gap-1.5">
                <i data-lucide="plus" class="w-3.5 h-3.5 text-emerald-400"></i>
                <span>Add Question</span>
            </button>
        </div>

        <div class="space-y-4">
            <template x-for="(q, index) in questions" :key="index">
                <div class="p-4 rounded-xl bg-slate-950/80 border border-slate-800 space-y-3 relative">
                    <div class="flex items-center justify-between gap-3 border-b border-slate-800/80 pb-2">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-slate-800 text-slate-200 text-xs font-bold flex items-center justify-center font-mono" x-text="index + 1"></span>
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-300">Question Item</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <label class="flex items-center gap-1.5 cursor-pointer text-xs text-slate-300">
                                <input type="checkbox" :name="'questions[' + index + '][is_required]'" value="1" x-model="q.is_required" class="rounded bg-slate-900 border-slate-700 text-[#560E0F] focus:ring-[#8F3033]">
                                <span>Required</span>
                            </label>
                            <button type="button" @click="removeQuestion(index)" class="text-slate-500 hover:text-red-400 transition" title="Remove Question" x-show="questions.length > 1">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="sm:col-span-2">
                            <label class="label text-xs">Question Prompt <span class="text-red-400">*</span></label>
                            <input type="text" :name="'questions[' + index + '][question_text]'" x-model="q.question_text" required class="input text-xs" />
                        </div>
                        <div>
                            <label class="label text-xs">Question Format <span class="text-red-400">*</span></label>
                            <select :name="'questions[' + index + '][question_type]'" x-model="q.question_type" class="input text-xs">
                                <option value="rating">Rating Scale (1-5)</option>
                                <option value="single_choice">Single Choice (Radio)</option>
                                <option value="multiple_choice">Multiple Choice (Checkboxes)</option>
                                <option value="short_text">Short Text Response</option>
                                <option value="long_text">Long Text / Comments</option>
                            </select>
                        </div>
                    </div>

                    <div x-show="q.question_type === 'single_choice' || q.question_type === 'multiple_choice'" class="space-y-1.5 pt-2 border-t border-slate-800/60">
                        <label class="label text-xs">Answer Options (Enter each choice on a new line) <span class="text-red-400">*</span></label>
                        <textarea :name="'questions[' + index + '][options]'" x-model="q.options" rows="3" class="input text-xs resize-none font-mono"></textarea>
                    </div>

                    <div x-show="q.question_type === 'rating'" class="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-2 border-t border-slate-800/60">
                        <div>
                            <label class="label text-[11px]">Min Rating</label>
                            <input type="number" :name="'questions[' + index + '][rating_min]'" x-model="q.rating_min" min="1" max="5" class="input text-xs" />
                        </div>
                        <div>
                            <label class="label text-[11px]">Max Rating</label>
                            <input type="number" :name="'questions[' + index + '][rating_max]'" x-model="q.rating_max" min="2" max="10" class="input text-xs" />
                        </div>
                        <div>
                            <label class="label text-[11px]">Min Label</label>
                            <input type="text" :name="'questions[' + index + '][rating_min_label]'" x-model="q.rating_min_label" class="input text-xs" />
                        </div>
                        <div>
                            <label class="label text-[11px]">Max Label</label>
                            <input type="text" :name="'questions[' + index + '][rating_max_label]'" x-model="q.rating_max_label" class="input text-xs" />
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <div class="flex items-center justify-end gap-3">
        <a href="{{ route('admin.evaluation-templates.index') }}" class="btn-secondary">Cancel</a>
        <button type="submit" class="btn-primary shadow-lg">Save Changes (Publish Version {{ $template->version + 1 }})</button>
    </div>
</form>

<script>
function templateEditor() {
    return {
        questions: @json($template->questions->map(fn($q) => [
            'question_text' => $q->question_text,
            'question_type' => $q->question_type,
            'options' => is_array($q->options) ? implode("\n", $q->options) : '',
            'rating_min' => $q->rating_min,
            'rating_max' => $q->rating_max,
            'rating_min_label' => $q->rating_min_label,
            'rating_max_label' => $q->rating_max_label,
            'is_required' => (bool)$q->is_required
        ])),

        addQuestion() {
            this.questions.push({
                question_text: '',
                question_type: 'rating',
                options: '',
                rating_min: 1,
                rating_max: 5,
                rating_min_label: 'Poor',
                rating_max_label: 'Excellent',
                is_required: true
            });
            this.$nextTick(() => {
                if (window.lucideIcons) window.createIcons({ icons: window.lucideIcons });
            });
        },

        removeQuestion(index) {
            if (this.questions.length > 1) {
                this.questions.splice(index, 1);
            }
        }
    };
}
</script>
</x-layouts.admin>
