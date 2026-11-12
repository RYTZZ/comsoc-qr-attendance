<x-app-layout>
@php
    $letters = ['A', 'B', 'C', 'D', 'E', 'F'];
    $tz = \App\Models\TriviaQuestion::TZ;
    $me = auth()->user();
@endphp
<div class="py-8 min-h-screen bg-[#0B1120]">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        <div class="border-b border-slate-800 pb-5">
            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-500/10 text-amber-300 border border-amber-500/20">Weekly Brain Teaser</span>
            <h1 class="text-2xl font-bold font-brand-display text-white mt-1">Friday Trivia</h1>
            <p class="text-xs text-slate-400 mt-0.5">A new tech question drops every Friday. One answer per question, climb the leaderboard.</p>
        </div>

        @if (session('success'))
            <div role="status" class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-xs text-emerald-200">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div role="alert" class="rounded-xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-xs text-rose-200">{{ session('error') }}</div>
        @endif
        @if (isset($errors) && $errors->any())
            <div role="alert" class="rounded-xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-xs text-rose-200">{{ $errors->first() }}</div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">

                {{-- Live question(s) --}}
                @forelse ($live as $q)
                    @php $mine = $myAnswers->get($q->id); @endphp
                    <section class="card bg-[#172033] border border-slate-800 rounded-2xl p-5 space-y-4" data-testid="live-question">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-[#0B1120] text-slate-300 border border-slate-800">{{ \App\Models\TriviaQuestion::CATEGORIES[$q->category] ?? 'Tech' }}</span>
                            <span class="text-[11px] text-slate-400">Closes {{ $q->closes_at->timezone($tz)->format('D, M j · g:i A') }} · {{ $q->points }} pts</span>
                        </div>
                        <h2 class="text-base sm:text-lg font-bold text-white">{{ $q->question }}</h2>

                        @if ($mine)
                            <ul class="space-y-2">
                                @foreach ($q->options as $i => $opt)
                                    @php
                                        $isMine = $mine->selected_index === $i;
                                        $cls = 'border-slate-800 bg-[#0B1120] text-slate-300';
                                        if ($isMine && $q->feedback_mode === 'immediate') {
                                            $cls = $mine->is_correct ? 'border-emerald-500/50 bg-emerald-500/10 text-emerald-200' : 'border-rose-500/50 bg-rose-500/10 text-rose-200';
                                        } elseif ($isMine) {
                                            $cls = 'border-indigo-500/50 bg-indigo-500/10 text-indigo-200';
                                        }
                                    @endphp
                                    <li class="flex items-center gap-3 rounded-xl border px-3 py-2.5 text-sm {{ $cls }}">
                                        <span class="font-mono text-xs text-slate-400">{{ $letters[$i] }}</span>
                                        <span class="flex-1">{{ $opt }}</span>
                                        @if ($isMine) <span class="text-[10px] font-bold uppercase">Your answer</span> @endif
                                    </li>
                                @endforeach
                            </ul>
                            <p class="text-xs {{ $q->feedback_mode === 'immediate' ? ($mine->is_correct ? 'text-emerald-300' : 'text-rose-300') : 'text-indigo-300' }}">
                                @if ($q->feedback_mode === 'immediate')
                                    {{ $mine->is_correct ? "Correct! You earned {$mine->points_awarded} points." : 'Not quite right. The correct answer and explanation are revealed when the question closes.' }}
                                @else
                                    Answer recorded. The result and explanation will be revealed after the answering period.
                                @endif
                            </p>
                        @else
                            <form method="POST" action="{{ route('trivia.answer', $q) }}" class="space-y-3" x-data="{ submitting: false, picked: null }" @submit="if (picked === null) { $event.preventDefault(); return; } submitting = true">
                                @csrf
                                <div class="space-y-2">
                                    @foreach ($q->options as $i => $opt)
                                        <label class="flex items-center gap-3 rounded-xl border border-slate-800 bg-[#0B1120] px-3 py-2.5 text-sm text-slate-200 cursor-pointer hover:border-slate-600 transition has-[:checked]:border-indigo-500/60 has-[:checked]:bg-indigo-500/10">
                                            <input type="radio" name="selected_index" value="{{ $i }}" x-model.number="picked" class="accent-indigo-500" required>
                                            <span class="font-mono text-xs text-slate-400">{{ $letters[$i] }}</span>
                                            <span>{{ $opt }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                <button type="submit" :disabled="submitting || picked === null" class="btn-primary text-xs py-2 px-5 disabled:opacity-50">
                                    <span x-text="submitting ? 'Submitting...' : 'Submit Answer'">Submit Answer</span>
                                </button>
                            </form>
                        @endif
                    </section>
                @empty
                    <section class="card bg-[#172033] border border-slate-800 rounded-2xl p-8 text-center space-y-1">
                        <p class="text-sm font-bold text-white">No trivia question is open right now</p>
                        <p class="text-xs text-slate-400">
                            @if ($upcoming)
                                Next question drops {{ $upcoming->publish_at->timezone($tz)->format('l, M j \a\t g:i A') }}.
                            @else
                                Check back this Friday for a fresh question!
                            @endif
                        </p>
                    </section>
                @endforelse

                {{-- Archive --}}
                <section class="space-y-3">
                    <h2 class="text-sm font-bold text-white uppercase tracking-wider">Archive</h2>
                    @forelse ($archive as $q)
                        @php $mine = $myAnswers->get($q->id); @endphp
                        <article class="card bg-[#172033] border border-slate-800 rounded-2xl p-5 space-y-3" data-testid="archive-question">
                            <div class="flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-400">
                                <span>{{ $q->publish_at->timezone($tz)->format('M j, Y') }} · {{ \App\Models\TriviaQuestion::CATEGORIES[$q->category] ?? 'Tech' }}</span>
                                @if ($mine)
                                    <span class="font-bold {{ $mine->is_correct ? 'text-emerald-300' : 'text-rose-300' }}">{{ $mine->is_correct ? "Correct +{$mine->points_awarded}" : 'Incorrect' }}</span>
                                @else
                                    <span>Not answered</span>
                                @endif
                            </div>
                            <h3 class="text-sm font-bold text-white">{{ $q->question }}</h3>
                            <ul class="space-y-1.5">
                                @foreach ($q->options as $i => $opt)
                                    <li class="flex items-center gap-2 rounded-lg px-3 py-2 text-xs border {{ $i === $q->correct_index ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-200' : (($mine && $mine->selected_index === $i) ? 'border-rose-500/40 bg-rose-500/10 text-rose-200' : 'border-slate-800 bg-[#0B1120] text-slate-300') }}">
                                        <span class="font-mono text-slate-400">{{ $letters[$i] }}</span>
                                        <span class="flex-1">{{ $opt }}</span>
                                        @if ($i === $q->correct_index) <span class="text-[10px] font-bold uppercase">Correct</span> @endif
                                    </li>
                                @endforeach
                            </ul>
                            @if ($q->explanation)
                                <p class="text-xs text-slate-300 leading-relaxed"><span class="font-bold text-amber-300">Explanation:</span> {{ $q->explanation }}</p>
                            @endif
                        </article>
                    @empty
                        <div class="card bg-[#172033] border border-slate-800 rounded-2xl p-6 text-center text-xs text-slate-400">Past questions and their answers will appear here.</div>
                    @endforelse
                    {{ $archive->links() }}
                </section>
            </div>

            {{-- Leaderboard --}}
            <aside class="space-y-3">
                <div class="card bg-[#172033] border border-slate-800 rounded-2xl p-5">
                    <h2 class="text-sm font-bold text-white uppercase tracking-wider mb-3">Top Trivia Masters</h2>
                    @forelse ($leaderboard as $row)
                        <div class="flex items-center gap-3 py-2 border-b border-slate-800/70 last:border-0 {{ (string) $row->user->id === (string) $me->id ? 'text-amber-200' : 'text-slate-200' }}">
                            <span class="w-6 text-center text-xs font-bold {{ $row->rank <= 3 ? 'text-amber-300' : 'text-slate-500' }}">{{ $row->rank }}</span>
                            <span class="flex-1 truncate text-xs font-semibold">{{ $row->user->username ?: $row->user->name }}</span>
                            <span class="text-xs font-mono">{{ $row->total_points }} pts</span>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400">Scores appear once a question's answering period ends.</p>
                    @endforelse
                    @if ($myStanding)
                        <p class="mt-3 text-[11px] text-slate-400">You: rank #{{ $myStanding->rank }} · {{ $myStanding->correct_count }}/{{ $myStanding->answered_count }} correct · {{ $myStanding->total_points }} pts</p>
                    @endif
                </div>
            </aside>
        </div>
    </div>
</div>
</x-app-layout>

