<x-layouts.admin :title="($question->exists ? 'Edit' : 'Create') . ' Friday Trivia Question'">
@php
    $tz = \App\Models\TriviaQuestion::TZ;
    $locked = $locked ?? false;
@endphp
<div class="page-header">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.trivia.index') }}" class="btn-ghost btn-sm p-1.5">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
        </a>
        <div>
            <h1 class="page-title font-heading">{{ $question->exists ? 'Edit Friday Trivia Question' : 'New Friday Trivia Question' }}</h1>
            <p class="text-xs text-slate-400 mt-0.5">Configure computing question, options, schedule, and answer validation.</p>
        </div>
    </div>
</div>

@if ($errors->any())
    <div role="alert" class="mb-4 rounded-xl border border-rose-500/30 bg-rose-500/10 p-4 text-xs text-rose-200">
        <strong class="font-bold">Please correct the following errors:</strong>
        <ul class="list-disc list-inside mt-1 space-y-0.5">
            @foreach ($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if ($locked)
    <div role="alert" class="mb-4 rounded-xl border border-amber-500/30 bg-amber-500/10 p-3 text-xs text-amber-200 flex items-center gap-2">
        <i data-lucide="lock" class="w-4 h-4 shrink-0 text-amber-400"></i>
        <span>Members have already answered this question. Options, answer key, and points are locked to protect tournament integrity.</span>
    </div>
@endif

<div x-data="{
    mode: 'single',
    rawText: '',
    parsing: false,
    importing: false,
    parseError: '',
    items: [],
    summary: null,
    batchConfig: {
        feedback_mode: 'immediate',
        points: 15,
        status: 'published'
    },
    fillExample() {
        this.rawText = `Q1. Which component is commonly referred to as the brain of the computer?\nA. RAM\nB. CPU\nC. SSD\nD. GPU\nCorrect Answer: B\nCategory: General Technology\nDifficulty: Easy\nExplanation: The CPU executes instructions and processes data.\n\nQ2. What does HTML stand for?\nA. Hyper Trainer Marking Language\nB. HyperText Markup Language\nC. HighText Machine Language\nD. HyperText Marketing Language\nCorrect Answer: B\nCategory: Web Development\nDifficulty: Easy\nExplanation: HTML is the standard markup language for structuring webpages.`;
    },
    clearBulk() {
        this.rawText = '';
        this.items = [];
        this.summary = null;
        this.parseError = '';
    },
    async parseQuestions() {
        this.parseError = '';
        if (!this.rawText.trim()) {
            this.parseError = 'Please paste at least one formatted question.';
            return;
        }
        this.parsing = true;
        try {
            const res = await window.axios.post('{{ route('admin.trivia.bulk-parse') }}', { raw_text: this.rawText });
            this.items = res.data.items || [];
            this.summary = res.data.summary || null;
        } catch (e) {
            this.parseError = e.response?.data?.message || 'Unable to parse questions. Please check the format.';
        } finally {
            this.parsing = false;
        }
    },
    removeItem(idx) {
        this.items.splice(idx, 1);
    },
    selectedValidCount() {
        return this.items.filter(i => i.selected && i.status === 'valid').length;
    }
}">

@if (! $question->exists)
    <div class="mb-5 inline-flex rounded-xl bg-[#0B1120] p-1 border border-slate-800">
        <button type="button"
                @click="mode = 'single'"
                :class="mode === 'single' ? 'bg-[#560E0F] text-white shadow' : 'text-slate-400 hover:text-white'"
                class="px-4 py-1.5 rounded-lg text-xs font-semibold transition">
            Single Question
        </button>
        <button type="button"
                @click="mode = 'bulk'"
                :class="mode === 'bulk' ? 'bg-[#560E0F] text-white shadow' : 'text-slate-400 hover:text-white'"
                class="px-4 py-1.5 rounded-lg text-xs font-semibold transition">
            Bulk Import
        </button>
    </div>
@endif

<div x-show="mode === 'single'">
<form method="POST" action="{{ $question->exists ? route('admin.trivia.update', $question) : route('admin.trivia.store') }}" class="space-y-6">
    @csrf
    @if ($question->exists)
        @method('PUT')
    @endif

    <div class="card p-6 space-y-4">
        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1">Trivia Question <span class="text-rose-400">*</span></label>
            <textarea name="question" rows="3" class="input text-xs w-full" placeholder="e.g. Which algorithm paradigm is utilized in Dijkstra's shortest path algorithm?" required>{{ old('question', $question->question) }}</textarea>
            @error('question') <p class="text-[11px] text-rose-400 mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Category <span class="text-rose-400">*</span></label>
                <select name="category" class="input text-xs w-full" required>
                    @foreach (\App\Models\TriviaQuestion::CATEGORIES as $key => $label)
                        <option value="{{ $key }}" @selected(old('category', $question->category) === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('category') <p class="text-[11px] text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Feedback Mode <span class="text-rose-400">*</span></label>
                <select name="feedback_mode" class="input text-xs w-full" required>
                    @foreach (\App\Models\TriviaQuestion::FEEDBACK_MODES as $mode => $modeLabel)
                        <option value="{{ $mode }}" @selected(old('feedback_mode', $question->feedback_mode) === $mode)>{{ $modeLabel }}</option>
                    @endforeach
                </select>
                @error('feedback_mode') <p class="text-[11px] text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <div class="flex items-center justify-between mb-2">
                <label class="block text-xs font-semibold text-slate-300">Answer Options & Correct Answer <span class="text-rose-400">*</span></label>
                <span class="text-[11px] text-slate-400">Select the radio button beside the correct choice</span>
            </div>

            @php
                $opts = old('options', $question->options ?? ['', '', '', '']);
                $correctIdx = (int) old('correct_index', $question->correct_index ?? 0);
            @endphp

            <div class="space-y-2.5">
                @for ($i = 0; $i < max(4, count($opts)); $i++)
                    <div class="flex items-center gap-3">
                        <label class="flex items-center gap-1.5 text-xs text-slate-300 cursor-pointer">
                            <input type="radio" name="correct_index" value="{{ $i }}" {{ $correctIdx === $i ? 'checked' : '' }} {{ $locked ? 'disabled' : '' }} class="accent-emerald-500" required>
                            <span class="font-mono text-slate-400">{{ chr(65 + $i) }}</span>
                        </label>
                        <input type="text" name="options[]" value="{{ $opts[$i] ?? '' }}" placeholder="Option {{ chr(65 + $i) }}" {{ $locked ? 'readonly' : '' }} class="input text-xs flex-1" required>
                    </div>
                @endfor
            </div>
            @error('correct_index') <p class="text-[11px] text-rose-400 mt-1">{{ $message }}</p> @enderror
            @error('options') <p class="text-[11px] text-rose-400 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1">Answer Explanation</label>
            <textarea name="explanation" rows="3" class="input text-xs w-full" placeholder="Explain why the selected choice is correct. This is revealed after the answering period.">{{ old('explanation', $question->explanation) }}</textarea>
            @error('explanation') <p class="text-[11px] text-rose-400 mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2 border-t border-slate-800">
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Points Awarded <span class="text-rose-400">*</span></label>
                <input type="number" name="points" min="1" max="100" value="{{ old('points', $question->points ?? 10) }}" {{ $locked ? 'readonly' : '' }} class="input text-xs w-full" required>
                @error('points') <p class="text-[11px] text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Publish Time (PHT - Must be Friday) <span class="text-rose-400">*</span></label>
                @php
                    $pub = $question->publish_at ? $question->publish_at->timezone($tz)->format('Y-m-d\TH:i') : \App\Models\TriviaQuestion::defaultPublishAt()->format('Y-m-d\TH:i');
                @endphp
                <input type="datetime-local" name="publish_at" value="{{ old('publish_at', $pub) }}" class="input text-xs w-full" required>
                @error('publish_at') <p class="text-[11px] text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Closes Time (PHT) <span class="text-rose-400">*</span></label>
                @php
                    $close = $question->closes_at ? $question->closes_at->timezone($tz)->format('Y-m-d\TH:i') : \App\Models\TriviaQuestion::defaultClosesAt()->format('Y-m-d\TH:i');
                @endphp
                <input type="datetime-local" name="closes_at" value="{{ old('closes_at', $close) }}" class="input text-xs w-full" required>
                @error('closes_at') <p class="text-[11px] text-rose-400 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1">Status <span class="text-rose-400">*</span></label>
            <div class="flex items-center gap-4 text-xs">
                <label class="flex items-center gap-1.5 cursor-pointer">
                    <input type="radio" name="status" value="draft" {{ old('status', $question->status) === 'draft' ? 'checked' : '' }} class="accent-indigo-500">
                    <span>Draft (hidden)</span>
                </label>
                <label class="flex items-center gap-1.5 cursor-pointer">
                    <input type="radio" name="status" value="published" {{ old('status', $question->status) === 'published' ? 'checked' : '' }} class="accent-emerald-500">
                    <span>Published (respects schedule)</span>
                </label>
            </div>
            @error('status') <p class="text-[11px] text-rose-400 mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="flex items-center justify-end gap-3">
        <a href="{{ route('admin.trivia.index') }}" class="btn-secondary text-xs py-2 px-4">Cancel</a>
        <button type="submit" class="btn-primary text-xs py-2 px-6">
            {{ $question->exists ? 'Update Trivia Question' : 'Save Trivia Question' }}
        </button>
    </div>
</form>
</div>

@if (! $question->exists)
<div x-show="mode === 'bulk'" x-cloak class="space-y-6">
    <div class="card p-6 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="text-sm font-bold text-white">Paste Questions</h2>
                <p class="text-xs text-slate-400 mt-0.5">Supports <code class="text-amber-300">Q1.</code>, <code class="text-amber-300">Question 1:</code>, or <code class="text-amber-300">1.</code> with choices <code class="text-amber-300">A–D</code>, <code class="text-amber-300">Correct Answer:</code>, <code class="text-amber-300">Category:</code>, <code class="text-amber-300">Difficulty:</code>, and optional <code class="text-amber-300">Explanation:</code>.</p>
            </div>
            <button type="button" @click="fillExample()" class="btn-secondary text-xs py-1.5 px-3">Load Example</button>
        </div>

        <textarea x-model="rawText"
                  rows="10"
                  class="input text-xs w-full font-mono"
                  placeholder="Paste formatted questions here..."></textarea>

        <template x-if="parseError">
            <p class="text-xs text-rose-400 font-medium" x-text="parseError"></p>
        </template>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-3 border-t border-slate-800">
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Batch Feedback Mode</label>
                <select x-model="batchConfig.feedback_mode" class="input text-xs w-full">
                    @foreach (\App\Models\TriviaQuestion::FEEDBACK_MODES as $mode => $modeLabel)
                        <option value="{{ $mode }}">{{ $modeLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Points Awarded (Scoring)</label>
                <input type="number" x-model.number="batchConfig.points" min="1" max="100" class="input text-xs w-full">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Question Bank Status</label>
                <select x-model="batchConfig.status" class="input text-xs w-full">
                    <option value="published">Published (Eligible for Friday Trivia)</option>
                    <option value="draft">Draft (Hidden until approved)</option>
                </select>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2">
            <button type="button" @click="clearBulk()" class="btn-secondary text-xs py-2 px-4">Clear</button>
            <button type="button" @click="parseQuestions()" :disabled="parsing" class="btn-primary text-xs py-2 px-5">
                <span x-text="parsing ? 'Parsing...' : 'Parse Questions'">Parse Questions</span>
            </button>
        </div>
    </div>

    {{-- Preview Before Saving --}}
    <template x-if="items.length > 0">
        <form method="POST" action="{{ route('admin.trivia.bulk-import') }}" class="card p-6 space-y-4" @submit="importing = true">
            @csrf
            <input type="hidden" name="feedback_mode" :value="batchConfig.feedback_mode">
            <input type="hidden" name="points" :value="batchConfig.points">
            <input type="hidden" name="status" :value="batchConfig.status">

            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 pb-3">
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <span class="font-bold text-white" x-text="items.length + ' detected'"></span>
                    <span class="px-2 py-0.5 rounded bg-emerald-500/15 text-emerald-300 font-semibold" x-text="items.filter(i => i.status === 'valid').length + ' valid'"></span>
                    <span class="px-2 py-0.5 rounded bg-amber-500/15 text-amber-300 font-semibold" x-text="items.filter(i => i.status === 'duplicate').length + ' duplicate'"></span>
                    <span class="px-2 py-0.5 rounded bg-rose-500/15 text-rose-300 font-semibold" x-text="items.filter(i => i.status === 'invalid').length + ' invalid'"></span>
                </div>
                <button type="submit" :disabled="importing || selectedValidCount() === 0" class="btn-primary text-xs py-2 px-5 disabled:opacity-50">
                    <span x-text="importing ? 'Importing...' : ('Import ' + selectedValidCount() + ' Selected Question(s)')"></span>
                </button>
            </div>

            <div class="space-y-4">
                <template x-for="(item, idx) in items" :key="idx">
                    <div class="rounded-xl border p-4 space-y-3"
                         :class="item.status === 'valid' ? 'border-slate-800 bg-[#0B1120]' : (item.status === 'duplicate' ? 'border-amber-500/40 bg-amber-500/5' : 'border-rose-500/40 bg-rose-500/5')">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <label class="flex items-center gap-2 text-xs font-semibold text-white cursor-pointer">
                                <input type="checkbox" x-model="item.selected" :name="'items[' + idx + '][selected]'" value="1" class="accent-emerald-500">
                                <span x-text="'Question #' + (idx + 1)"></span>
                            </label>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase"
                                      :class="item.status === 'valid' ? 'bg-emerald-500/20 text-emerald-300' : (item.status === 'duplicate' ? 'bg-amber-500/20 text-amber-300' : 'bg-rose-500/20 text-rose-300')"
                                      x-text="item.status"></span>
                                <button type="button" @click="removeItem(idx)" class="text-xs text-slate-400 hover:text-rose-400">Remove</button>
                            </div>
                        </div>

                        <template x-if="item.errors && item.errors.length > 0">
                            <ul class="text-[11px] text-rose-300 list-disc list-inside">
                                <template x-for="err in item.errors">
                                    <li x-text="err"></li>
                                </template>
                            </ul>
                        </template>

                        <input type="text" x-model="item.question" :name="'items[' + idx + '][question]'" class="input text-xs w-full" placeholder="Question text">

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <template x-for="(opt, oi) in item.options" :key="oi">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-xs text-slate-400" x-text="['A','B','C','D'][oi]"></span>
                                    <input type="text" x-model="item.options[oi]" :name="'items[' + idx + '][options][' + oi + ']'" class="input text-xs flex-1">
                                </div>
                            </template>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                            <div>
                                <label class="block text-[10px] text-slate-400 mb-0.5">Correct Answer</label>
                                <select x-model.number="item.correct_index" :name="'items[' + idx + '][correct_index]'" class="input text-xs w-full">
                                    <option value="0">A</option>
                                    <option value="1">B</option>
                                    <option value="2">C</option>
                                    <option value="3">D</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] text-slate-400 mb-0.5">Category</label>
                                <select x-model="item.category" :name="'items[' + idx + '][category]'" class="input text-xs w-full">
                                    @foreach (\App\Models\TriviaQuestion::CATEGORIES as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] text-slate-400 mb-0.5">Difficulty</label>
                                <select x-model="item.difficulty" :name="'items[' + idx + '][difficulty]'" class="input text-xs w-full">
                                    <option value="easy">Easy</option>
                                    <option value="medium">Medium</option>
                                    <option value="hard">Hard</option>
                                </select>
                            </div>
                        </div>

                        <input type="text" x-model="item.explanation" :name="'items[' + idx + '][explanation]'" class="input text-xs w-full" placeholder="Explanation (optional)">
                    </div>
                </template>
            </div>
        </form>
    </template>
</div>
@endif

</div>
</x-layouts.admin>

