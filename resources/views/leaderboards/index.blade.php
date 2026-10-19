<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-burgundy-deep text-[#df747a] border border-[#8F3033]/60">
                        Honor & Hall of Fame
                    </span>
                    <span class="text-xs text-[#94A3B8]">Computing Society • Bulan Campus</span>
                </div>
                <h1 class="text-2xl font-extrabold text-[#F8FAFC] font-brand-display tracking-tight mt-1">
                    Leaderboards & Achievements
                </h1>
                <p class="text-xs text-[#94A3B8] mt-0.5">
                    Recognizing active member involvement, event participation, and milestone achievements.
                </p>
            </div>

            @if($userRank && $userRank['score'] > 0)
                <div class="flex items-center gap-3 px-4 py-2.5 rounded-2xl bg-[#172033] border border-[#8F3033]/50 shadow-xl">
                    <div class="w-8 h-8 rounded-xl bg-burgundy-gradient border border-[#8F3033]/50 flex items-center justify-center text-white font-black text-xs font-brand-display shadow-md">
                        #{{ $userRank['rank'] }}
                    </div>
                    <div>
                        <div class="text-[10px] uppercase font-bold text-[#94A3B8] tracking-wider">Your Standing</div>
                        <div class="text-xs font-black text-white font-mono tabular-nums">
                            {{ number_format($userRank['score']) }} EXP
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </x-slot>

    <div class="py-8 bg-[#0B1120]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="card bg-[#172033] border border-[#334155] p-4 rounded-2xl shadow-xl">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('leaderboard.index', ['tab' => 'semester', 'season_id' => $selectedSeasonId]) }}"
                           class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 border {{ $tab === 'semester' ? 'bg-burgundy-gradient text-white border-[#8F3033] shadow-lg shadow-[#560E0F]/30' : 'bg-[#0B1120] text-[#94A3B8] hover:text-white border-[#334155]' }}">
                            <i data-lucide="calendar" class="w-4 h-4"></i>
                            <span>Semester Leaderboard</span>
                        </a>

                        <a href="{{ route('leaderboard.index', ['tab' => 'lifetime']) }}"
                           class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 border {{ $tab === 'lifetime' ? 'bg-burgundy-gradient text-white border-[#8F3033] shadow-lg shadow-[#560E0F]/30' : 'bg-[#0B1120] text-[#94A3B8] hover:text-white border-[#334155]' }}">
                            <i data-lucide="crown" class="w-4 h-4 text-amber-400"></i>
                            <span>Lifetime Leaderboard</span>
                        </a>

                        <a href="{{ route('leaderboard.index', ['tab' => 'achievements']) }}"
                           class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 border {{ $tab === 'achievements' ? 'bg-burgundy-gradient text-white border-[#8F3033] shadow-lg shadow-[#560E0F]/30' : 'bg-[#0B1120] text-[#94A3B8] hover:text-white border-[#334155]' }}">
                            <i data-lucide="award" class="w-4 h-4 text-[#df747a]"></i>
                            <span>Milestones & Badges ({{ $unlockedCount }}/{{ $totalCount }})</span>
                        </a>
                    </div>

                    @if($tab === 'semester' && $seasons->isNotEmpty())
                        <form method="GET" action="{{ route('leaderboard.index') }}" class="flex items-center gap-2">
                            <input type="hidden" name="tab" value="semester">
                            <span class="text-xs text-[#94A3B8] font-medium">Semester:</span>
                            <select name="season_id" onchange="this.form.submit()" class="bg-[#0B1120] border border-[#334155] text-xs rounded-xl px-3 py-1.5 text-white focus:outline-none focus:ring-2 focus:ring-[#8F3033]">
                                @foreach($seasons as $s)
                                    <option value="{{ $s->id }}" {{ $selectedSeasonId === $s->id ? 'selected' : '' }}>
                                        {{ $s->name }} ({{ \Carbon\Carbon::parse($s->start_date)->format('M Y') }} – {{ \Carbon\Carbon::parse($s->end_date)->format('M Y') }})
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    @endif
                </div>
            </div>

            @if($tab === 'achievements')
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($achievementProgress as $ach)
                        <div class="card bg-[#172033] border rounded-2xl p-5 relative overflow-hidden transition hover:border-[#8F3033]/60 {{ $ach['is_unlocked'] ? 'border-amber-500/40 bg-gradient-to-br from-[#172033] to-amber-950/20' : 'border-[#334155] opacity-75' }}">
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 border {{ $ach['is_unlocked'] ? 'bg-amber-500/20 border-amber-500/30 text-amber-400' : 'bg-[#0B1120] border-[#334155] text-slate-500' }}">
                                    <i data-lucide="{{ $ach['achievement']->badge_icon ?? 'award' }}" class="w-6 h-6"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <h3 class="text-sm font-bold text-white font-brand-display">{{ $ach['achievement']->title }}</h3>
                                        @if($ach['is_unlocked'])
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                                Unlocked
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#0B1120] text-slate-400 border border-[#334155]">
                                                Locked
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-[#94A3B8] mt-1">{{ $ach['achievement']->description }}</p>

                                    <div class="mt-3 flex items-center justify-between text-[11px] text-[#94A3B8]">
                                        <span>Completion Reward</span>
                                        <span class="font-bold text-amber-400 tabular-nums">+{{ $ach['achievement']->xp_reward }} EXP</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                @php
                    $top3 = $leaderboard ? $leaderboard->take(3) : collect();
                @endphp

                @if($top3->isNotEmpty())
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-4 pb-2 items-end">
                        @if($top3->count() >= 2)
                            @php $second = $top3->get(1); @endphp
                            <div class="order-2 md:order-1 bg-gradient-to-t from-[#172033] via-[#0B1120] to-[#1E293B]/40 border border-slate-600/80 rounded-3xl p-5 text-center relative shadow-xl">
                                <div class="w-12 h-12 mx-auto -mt-10 mb-2 rounded-2xl bg-slate-700 border-2 border-slate-400 flex items-center justify-center font-black text-white text-base shadow-lg font-brand-display">
                                    2
                                </div>
                                <div class="w-12 h-12 mx-auto mb-2 flex items-center justify-center">
                                    <x-user-avatar :user="$second" size="lg" class="border-2 border-slate-400 shadow-md" />
                                </div>
                                <h3 class="text-sm font-bold text-white truncate font-brand-display">{{ $second->nickname ?: $second->name }}</h3>
                                <p class="text-xs font-mono font-bold text-slate-300 mt-1 tabular-nums">
                                    {{ number_format($tab === 'semester' ? $second->semester_exp : $second->lifetime_exp) }} EXP
                                    <span class="sr-only">{{ number_format($tab === 'semester' ? $second->semester_exp : $second->lifetime_exp) }} XP</span>
                                </p>
                                <span class="inline-block mt-2 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-700/60 text-slate-200 border border-slate-500">
                                    Silver Rank
                                </span>
                            </div>
                        @endif

                        @if($top3->isNotEmpty())
                            @php $first = $top3->get(0); @endphp
                            <div class="order-1 md:order-2 bg-gradient-to-t from-[#172033] via-burgundy-deep to-amber-950/30 border-2 border-amber-500/70 rounded-3xl p-6 text-center relative shadow-2xl scale-105 z-10">
                                <div class="w-14 h-14 mx-auto -mt-12 mb-2 rounded-2xl bg-amber-500 border-2 border-amber-300 flex items-center justify-center font-black text-slate-950 text-xl shadow-lg shadow-amber-500/40">
                                    <i data-lucide="crown" class="w-7 h-7"></i>
                                </div>
                                <div class="w-14 h-14 mx-auto mb-2 flex items-center justify-center">
                                    <x-user-avatar :user="$first" size="lg" class="w-14 h-14 border-2 border-amber-400 shadow-lg" />
                                </div>
                                <h3 class="text-base font-extrabold text-white truncate font-brand-display">{{ $first->nickname ?: $first->name }}</h3>
                                <p class="text-sm font-mono font-black text-amber-300 mt-1 tabular-nums">
                                    {{ number_format($tab === 'semester' ? $first->semester_exp : $first->lifetime_exp) }} EXP
                                    <span class="sr-only">{{ number_format($tab === 'semester' ? $first->semester_exp : $first->lifetime_exp) }} XP</span>
                                </p>
                                <span class="inline-block mt-2 px-3 py-1 rounded-full text-xs font-bold bg-amber-500/20 text-amber-300 border border-amber-500/40 animate-pulse">
                                    Season Champion
                                </span>
                            </div>
                        @endif

                        @if($top3->count() >= 3)
                            @php $third = $top3->get(2); @endphp
                            <div class="order-3 bg-gradient-to-t from-[#172033] via-[#0B1120] to-amber-950/20 border border-amber-800/60 rounded-3xl p-5 text-center relative shadow-xl">
                                <div class="w-12 h-12 mx-auto -mt-10 mb-2 rounded-2xl bg-amber-800 border-2 border-amber-600 flex items-center justify-center font-black text-white text-base shadow-lg font-brand-display">
                                    3
                                </div>
                                <div class="w-12 h-12 mx-auto mb-2 flex items-center justify-center">
                                    <x-user-avatar :user="$third" size="lg" class="border-2 border-amber-600 shadow-md" />
                                </div>
                                <h3 class="text-sm font-bold text-white truncate font-brand-display">{{ $third->nickname ?: $third->name }}</h3>
                                <p class="text-xs font-mono font-bold text-amber-300 mt-1 tabular-nums">
                                    {{ number_format($tab === 'semester' ? $third->semester_exp : $third->lifetime_exp) }} EXP
                                    <span class="sr-only">{{ number_format($tab === 'semester' ? $third->semester_exp : $third->lifetime_exp) }} XP</span>
                                </p>
                                <span class="inline-block mt-2 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-900/40 text-amber-300 border border-amber-700">
                                    Bronze Rank
                                </span>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="card bg-[#172033] border border-[#334155] rounded-2xl overflow-hidden shadow-xl">
                    <div class="p-4 border-b border-[#334155] flex items-center justify-between">
                        <div>
                            <h2 class="text-sm font-bold text-white font-brand-display">
                                {{ $tab === 'semester' ? 'Official Semester Rankings' : 'All-Time Lifetime Rankings' }}
                            </h2>
                            <p class="text-[11px] text-[#94A3B8]">
                                Public display names only. Student IDs are kept strictly private per organizational privacy policy.
                            </p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="border-b border-[#334155] text-[10px] uppercase tracking-wider text-[#94A3B8] bg-[#0B1120]/80">
                                    <th class="py-3 px-4 w-16 text-center">Rank</th>
                                    <th class="py-3 px-4">Member</th>
                                    <th class="py-3 px-4 text-right">Valid EXP Earned</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#334155]/60">
                                @forelse($leaderboard as $index => $row)
                                    @php
                                        $rank = ($leaderboard->currentPage() - 1) * $leaderboard->perPage() + $index + 1;
                                        $isMe = $row->id === auth()->id();
                                    @endphp
                                    <tr class="transition {{ $isMe ? 'bg-[#560E0F]/20 border-l-4 border-l-[#8F3033]' : 'hover:bg-[#1E293B]/40' }}">
                                        <td class="py-3 px-4 text-center">
                                            @if($rank === 1)
                                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-500 text-slate-950 font-black text-xs">1</span>
                                            @elseif($rank === 2)
                                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-300 text-slate-950 font-black text-xs">2</span>
                                            @elseif($rank === 3)
                                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-700 text-white font-black text-xs">3</span>
                                            @else
                                                <span class="font-mono text-[#94A3B8] font-semibold tabular-nums">{{ $rank }}</span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="flex items-center gap-2.5">
                                                <x-user-avatar :user="$row" size="xs" class="w-7 h-7 rounded-lg border border-[#8F3033]/60 shadow-sm" />
                                                <span class="font-bold text-white font-brand-display">{{ $row->nickname ?: $row->name }}</span>
                                                @if($isMe)
                                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-[#560E0F] text-white border border-[#8F3033]">YOU</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="py-3 px-4 text-right font-mono font-bold text-[#df747a] tabular-nums">
                                            {{ number_format($tab === 'semester' ? $row->semester_exp : $row->lifetime_exp) }} EXP
                                            <span class="sr-only">{{ number_format($tab === 'semester' ? $row->semester_exp : $row->lifetime_exp) }} XP</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="py-8 text-center text-[#94A3B8] text-xs">
                                            No ranked scores recorded yet. Attend verified organization events to qualify!
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="p-4 border-t border-[#334155]">
                        {{ $leaderboard->links() }}
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
