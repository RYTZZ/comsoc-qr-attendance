<x-layouts.admin :title="'Evaluation Results & Analytics'">
<div class="page-header">
    <div>
        <h1 class="page-title">Event Evaluation Results</h1>
        <p class="text-xs text-slate-400 mt-0.5">Attendee feedback and required survey completions before afternoon time-out.</p>
    </div>
    @if($selectedEvent)
    <div class="flex items-center gap-2">
        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
            {{ $evaluations->total() ?? $evaluations->count() }} Submissions
        </span>
    </div>
    @endif
</div>

@if($events->isEmpty())
<div class="card p-8 text-center text-slate-500">
    <i data-lucide="calendar-x" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
    <p class="font-medium text-slate-400">No events currently have an evaluation template assigned.</p>
    <p class="text-xs mt-1">Configure an event and assign an evaluation template to view results here.</p>
</div>
@else
<div class="card p-4 mb-6">
    <form method="GET" action="{{ route('admin.evaluations.results.index') }}" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-3 w-full sm:w-auto">
            <label class="text-xs font-bold text-slate-300 uppercase tracking-wider whitespace-nowrap">Select Event:</label>
            <select name="event_id" onchange="this.form.submit()" class="input text-xs py-1.5 w-full sm:w-80">
                @foreach($events as $ev)
                    <option value="{{ $ev->id }}" {{ $selectedEvent?->id === $ev->id ? 'selected' : '' }}>
                        {{ $ev->name }} ({{ $ev->event_date?->format('M d, Y') ?? 'TBA' }})
                    </option>
                @endforeach
            </select>
        </div>
        @if($selectedEvent && $selectedEvent->evaluationTemplate)
        <div class="text-xs text-slate-400">
            Active Template: <span class="font-bold text-white">{{ $selectedEvent->evaluationTemplate->title }}</span> (v{{ $selectedEvent->evaluation_snapshot['template_version'] ?? $selectedEvent->evaluationTemplate->version }})
        </div>
        @endif
    </form>
</div>

@if($selectedEvent)
    <!-- Rating Summary Statistics if any rating questions exist -->
    @php
        $ratingQuestions = array_filter($questions, fn($q) => $q['question_type'] === 'rating');
    @endphp

    @if(!empty($ratingQuestions))
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        @foreach($ratingQuestions as $rq)
        <div class="card p-4 bg-slate-900 border border-slate-800">
            <span class="text-[11px] font-semibold text-slate-400 block line-clamp-1" title="{{ $rq['question_text'] }}">{{ $rq['question_text'] }}</span>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl font-black text-amber-400 font-mono">{{ $ratingAverages[$rq['id']] ?? 0 }}</span>
                <span class="text-xs text-slate-500 font-medium">/ {{ $rq['rating_max'] ?? 5 }} Avg</span>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <div class="card overflow-hidden">
        <div class="border-b border-slate-800 p-4 flex items-center justify-between">
            <h2 class="section-title text-sm font-brand-display">Attendee Submissions ({{ $evaluations->total() ?? $evaluations->count() }})</h2>
            <span class="text-xs text-slate-500">Sorted by newest submission</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-950 text-slate-400 border-b border-slate-800 uppercase tracking-wider font-semibold">
                    <tr>
                        <th class="p-3.5">Student Attendee</th>
                        <th class="p-3.5">Submitted At</th>
                        <th class="p-3.5">Version</th>
                        <th class="p-3.5">Key Answers</th>
                        <th class="p-3.5 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800 text-slate-300">
                    @forelse($evaluations as $eval)
                    <tr class="hover:bg-slate-800/40 transition">
                        <td class="p-3.5">
                            <div class="font-bold text-white text-sm font-brand-display">{{ $eval->user?->name ?? 'Unknown Student' }}</div>
                            <span class="text-[11px] text-slate-400 font-mono">{{ $eval->user?->student?->student_number ?? $eval->user?->email }}</span>
                        </td>
                        <td class="p-3.5 text-slate-300 font-mono text-[11px]">
                            {{ $eval->submitted_at ? $eval->submitted_at->format('M d, Y h:i A') : 'N/A' }}
                        </td>
                        <td class="p-3.5 font-mono text-slate-400">
                            v{{ $eval->template_version }}
                        </td>
                        <td class="p-3.5">
                            <div class="space-y-1 max-w-md">
                                @foreach($eval->answers->take(2) as $ans)
                                <div class="text-[11px] truncate">
                                    <span class="text-slate-400">{{ Str::limit($ans->question_text, 30) }}:</span>
                                    <span class="font-semibold text-slate-200">
                                        {{ $ans->rating_value ? $ans->rating_value . ' / 5' : ($ans->answer_text ?: (is_array($ans->selected_options) ? implode(', ', $ans->selected_options) : 'Answered')) }}
                                    </span>
                                </div>
                                @endforeach
                            </div>
                        </td>
                        <td class="p-3.5 text-right">
                            <a href="{{ route('admin.evaluations.results.show', $eval) }}" class="btn-secondary btn-xs">
                                View Full Survey
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="p-8 text-center text-slate-500">
                            <i data-lucide="clipboard-check" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                            <p class="font-medium text-slate-400">No evaluations submitted yet for this event.</p>
                            <p class="text-[11px] mt-1">Students will complete the evaluation before recording their afternoon time-out.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($evaluations->hasPages())
        <div class="p-4 border-t border-slate-800">
            {{ $evaluations->links() }}
        </div>
        @endif
    </div>
@endif
@endif
</x-layouts.admin>
