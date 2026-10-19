<x-layouts.admin :title="'Preview Template: ' . $template->title">
<div class="page-header">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.evaluation-templates.index') }}" class="btn-secondary btn-sm">← Back</a>
        <div>
            <h1 class="page-title">Preview: {{ $template->title }}</h1>
            <p class="text-xs text-slate-400 mt-0.5">Version {{ $template->version }} • {{ $template->is_active ? 'Active' : 'Inactive' }}</p>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('admin.evaluation-templates.edit', $template) }}" class="btn-primary btn-sm flex items-center gap-1.5">
            <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
            <span>Edit Template</span>
        </a>
    </div>
</div>

<div class="max-w-3xl space-y-6">
    <div class="card p-6 border-l-4 border-l-[#8F3033]">
        <div class="flex items-center justify-between mb-2">
            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-burgundy-deep text-[#df747a] border border-[#8F3033]/60">
                Evaluation Preview
            </span>
            <span class="text-xs text-slate-400 font-mono">v{{ $template->version }}</span>
        </div>
        <h2 class="text-xl font-bold text-white font-brand-display">{{ $template->title }}</h2>
        <p class="text-xs text-slate-300 mt-1">{{ $template->description ?: 'Thank you for attending. Please evaluate this event before completing your afternoon attendance time-out.' }}</p>
    </div>

    <div class="space-y-4">
        @foreach($template->questions as $idx => $q)
        <div class="card p-5 space-y-3">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-start gap-2.5">
                    <span class="w-6 h-6 rounded-lg bg-slate-800 text-slate-200 text-xs font-bold flex items-center justify-center font-mono shrink-0 mt-0.5">
                        {{ $idx + 1 }}
                    </span>
                    <div>
                        <h3 class="text-sm font-semibold text-white">
                            {{ $q->question_text }}
                            @if($q->is_required)
                                <span class="text-red-400 font-bold ml-1" title="Required">*</span>
                            @endif
                        </h3>
                        <p class="text-[11px] text-slate-500 capitalize">{{ str_replace('_', ' ', $q->question_type) }}</p>
                    </div>
                </div>
            </div>

            <div class="pt-2">
                @if($q->question_type === 'rating')
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-xs text-slate-400 max-w-sm">
                            <span>{{ $q->rating_min_label ?: 'Poor' }} ({{ $q->rating_min }})</span>
                            <span>{{ $q->rating_max_label ?: 'Excellent' }} ({{ $q->rating_max }})</span>
                        </div>
                        <div class="flex items-center gap-2">
                            @for($i = $q->rating_min; $i <= $q->rating_max; $i++)
                                <div class="w-10 h-10 rounded-xl border border-slate-700 bg-slate-900/60 flex items-center justify-center font-bold text-slate-300 text-sm">
                                    {{ $i }}
                                </div>
                            @endfor
                        </div>
                    </div>
                @elseif($q->question_type === 'single_choice')
                    <div class="space-y-2">
                        @foreach($q->options ?? [] as $opt)
                            <div class="flex items-center gap-2.5 text-xs text-slate-300 p-2 rounded-lg bg-slate-900/40 border border-slate-800">
                                <div class="w-4 h-4 rounded-full border border-slate-600"></div>
                                <span>{{ $opt }}</span>
                            </div>
                        @endforeach
                    </div>
                @elseif($q->question_type === 'multiple_choice')
                    <div class="space-y-2">
                        @foreach($q->options ?? [] as $opt)
                            <div class="flex items-center gap-2.5 text-xs text-slate-300 p-2 rounded-lg bg-slate-900/40 border border-slate-800">
                                <div class="w-4 h-4 rounded border border-slate-600"></div>
                                <span>{{ $opt }}</span>
                            </div>
                        @endforeach
                    </div>
                @elseif($q->question_type === 'short_text')
                    <input type="text" disabled placeholder="Student single-line answer preview" class="input text-xs opacity-60 cursor-not-allowed">
                @elseif($q->question_type === 'long_text')
                    <textarea disabled rows="3" placeholder="Student multiline comment preview" class="input text-xs opacity-60 cursor-not-allowed resize-none"></textarea>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
</x-layouts.admin>
