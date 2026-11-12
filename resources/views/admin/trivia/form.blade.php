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
</x-layouts.admin>

