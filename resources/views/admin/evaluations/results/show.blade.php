<x-layouts.admin :title="'Evaluation Response Details'">
<div class="page-header">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.evaluations.results.index', ['event_id' => $evaluation->event_id]) }}" class="btn-secondary btn-sm">← Results</a>
        <div>
            <h1 class="page-title">Evaluation Response</h1>
            <p class="text-xs text-slate-400 mt-0.5">{{ $evaluation->event?->name }} • {{ $evaluation->user?->name }}</p>
        </div>
    </div>
</div>

<div class="max-w-3xl space-y-6">
    <div class="card p-5 bg-slate-900 border border-slate-800">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
            <div>
                <span class="text-slate-500 block">Student Attendee</span>
                <span class="font-bold text-white text-sm">{{ $evaluation->user?->name }}</span>
                <span class="text-slate-400 block font-mono">{{ $evaluation->user?->student?->student_number ?? $evaluation->user?->email }}</span>
            </div>
            <div>
                <span class="text-slate-500 block">Event Name</span>
                <span class="font-semibold text-white">{{ $evaluation->event?->name }}</span>
                <span class="text-slate-400 block">{{ $evaluation->event?->event_date?->format('M d, Y') }}</span>
            </div>
            <div>
                <span class="text-slate-500 block">Submission Timestamp</span>
                <span class="font-mono text-emerald-400 font-semibold">{{ $evaluation->submitted_at?->format('M d, Y h:i:s A') }}</span>
                <span class="text-slate-400 block">Template Version {{ $evaluation->template_version }}</span>
            </div>
        </div>
    </div>

    <div class="space-y-4">
        @foreach($evaluation->answers as $idx => $ans)
        <div class="card p-5 space-y-2">
            <div class="flex items-start gap-2.5">
                <span class="w-6 h-6 rounded-lg bg-slate-800 text-slate-200 text-xs font-bold flex items-center justify-center font-mono shrink-0 mt-0.5">
                    {{ $idx + 1 }}
                </span>
                <div class="flex-1">
                    <h3 class="text-sm font-semibold text-white">{{ $ans->question_text }}</h3>
                    <div class="mt-2 p-3 rounded-xl bg-slate-950 border border-slate-800/80">
                        @if($ans->question_type === 'rating')
                            <div class="flex items-center gap-2">
                                <span class="text-xl font-black text-amber-400 font-mono">{{ $ans->rating_value }}</span>
                                <span class="text-xs text-slate-400">/ 5 Rating Score</span>
                            </div>
                        @elseif($ans->question_type === 'multiple_choice' && is_array($ans->selected_options))
                            <ul class="list-disc list-inside space-y-1 text-xs text-slate-200">
                                @foreach($ans->selected_options as $opt)
                                    <li>{{ $opt }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-xs text-slate-200 whitespace-pre-wrap">{{ $ans->answer_text ?: 'No response provided.' }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
</x-layouts.admin>
