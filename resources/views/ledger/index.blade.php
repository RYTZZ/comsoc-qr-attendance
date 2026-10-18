<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-burgundy-deep text-[#df747a] border border-[#8F3033]/60">
                        {{ $activeSeason ? $activeSeason->name : 'Active Semester' }}
                    </span>
                    <span class="text-xs text-[#94A3B8]">Audit Ledger & Activity Logs</span>
                </div>
                <h1 class="text-2xl font-extrabold text-[#F8FAFC] font-brand-display tracking-tight mt-1">
                    EXP & Attendance Ledger
                </h1>
                <p class="text-xs text-[#94A3B8] mt-0.5">
                    Immutable chronological record of verified event attendance, mission rewards, and marketplace redemptions.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('shop.index') }}" class="btn-primary text-xs py-2 px-3.5 shadow-lg">
                    <i data-lucide="gift" class="w-4 h-4"></i>
                    <span>Rewards Shop</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-[#0B1120]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div class="card bg-burgundy-deep border border-[#8F3033]/50 shadow-xl p-5 relative overflow-hidden flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-bold text-[#df747a] uppercase tracking-wider">Level Status</span>
                            <span class="px-2.5 py-1 rounded-lg bg-burgundy-gradient text-white font-extrabold text-xs shadow border border-[#8F3033]/50 font-brand-display">
                                Level {{ $currentLevel->level }}
                            </span>
                        </div>
                        <div class="flex items-baseline gap-2 mb-2">
                            <span class="text-3xl font-black text-white font-brand-display tracking-tight tabular-nums">{{ number_format($lifetimeExp) }}</span>
                            <span class="text-xs font-bold text-[#df747a]">Lifetime EXP</span>
                        </div>
                        <p class="text-xs text-slate-200 font-semibold mb-3">{{ $currentLevel->title }}</p>

                        <div class="w-full bg-[#0B1120] rounded-full h-2.5 overflow-hidden border border-[#334155] mb-2 p-0.5">
                            <div class="bg-burgundy-gradient h-full rounded-full transition-all duration-500" style="width: {{ $levelProgress }}%"></div>
                        </div>
                        <div class="flex justify-between text-[11px] text-[#94A3B8]">
                            <span>{{ $levelProgress }}% to next rank</span>
                            <span class="text-slate-300 font-medium">{{ $nextLevel ? number_format($nextLevel->min_xp - $lifetimeExp) . ' EXP needed' : 'Max Rank' }}</span>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-[#334155]/60 flex items-center justify-between text-xs text-[#94A3B8]">
                        <span>Rank based on cumulative EXP</span>
                        <a href="{{ route('leaderboard.index', ['tab' => 'lifetime']) }}" class="text-[#df747a] hover:text-white font-semibold flex items-center gap-1 transition">
                            <span>Rankings</span>
                            <i data-lucide="arrow-right" class="w-3 h-3"></i>
                        </a>
                    </div>
                </div>

                <div class="card bg-[#172033] border border-amber-500/40 shadow-xl p-5 relative overflow-hidden flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-bold text-amber-400 uppercase tracking-wider">Spendable EXP</span>
                            <div class="w-7 h-7 rounded-lg bg-amber-500/20 text-amber-400 flex items-center justify-center border border-amber-500/30">
                                <i data-lucide="coins" class="w-4 h-4"></i>
                            </div>
                        </div>
                        <div class="flex items-baseline gap-2 mb-2">
                            <span class="text-3xl font-black text-amber-300 font-brand-display tracking-tight tabular-nums">{{ number_format($spendableExp) }}</span>
                            <span class="text-xs font-bold text-amber-400/80">Available EXP</span>
                        </div>
                        <p class="text-xs text-[#94A3B8] mb-3">Valid, unexpired balance available for marketplace merchandise redemption.</p>
                    </div>

                    <div class="pt-3 border-t border-[#334155] flex items-center justify-between text-xs">
                        <span class="text-[#94A3B8]">Expiring within 7 days:</span>
                        <span class="text-amber-400 font-mono font-bold tabular-nums">{{ number_format($expiringSoon) }} EXP</span>
                    </div>
                </div>

                <div class="card bg-[#172033] border border-[#334155] shadow-xl p-5 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-bold text-[#94A3B8] uppercase tracking-wider">Accounting Policy</span>
                            <div class="w-7 h-7 rounded-lg bg-[#560E0F]/30 text-[#df747a] flex items-center justify-center border border-[#8F3033]/40">
                                <i data-lucide="shield" class="w-4 h-4"></i>
                            </div>
                        </div>
                        <div class="flex items-baseline gap-2 mb-2">
                            <span class="text-3xl font-black text-white font-brand-display tracking-tight tabular-nums">{{ number_format($lifetimeExp) }}</span>
                            <span class="text-xs text-[#94A3B8]">Cumulative</span>
                        </div>
                        <p class="text-xs text-[#94A3B8]">Permanent record across all SSU Computing Society academic years. Ordinary redemptions do not decrease Lifetime totals.</p>
                    </div>

                    <div class="pt-3 border-t border-[#334155] text-[11px] text-[#94A3B8] flex items-center justify-between">
                        <span>Allocation Mode:</span>
                        <span class="font-bold text-slate-200">FEFO (Earliest Expiring First)</span>
                    </div>
                </div>
            </div>

            <div class="card bg-[#172033] border border-[#334155] p-6 space-y-6 shadow-xl">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#334155] pb-4">
                    <div class="flex items-center gap-3">
                        <a href="{{ route('ledger.index', ['tab' => 'xp']) }}"
                           class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 {{ $tab !== 'points' ? 'bg-burgundy-gradient text-white shadow-lg shadow-[#560E0F]/30 border border-[#8F3033]' : 'bg-[#0B1120] text-[#94A3B8] hover:text-white border border-[#334155]' }}">
                            <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                            <span>Experience Points (EXP) Ledger</span>
                        </a>
                        <a href="{{ route('ledger.index', ['tab' => 'points']) }}"
                           class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 {{ $tab === 'points' ? 'bg-amber-600 text-white shadow' : 'bg-[#0B1120] text-[#94A3B8] hover:text-white border border-[#334155]' }}">
                            <i data-lucide="receipt" class="w-3.5 h-3.5"></i>
                            <span>Points Ledger</span>
                        </a>
                    </div>

                    <span class="text-xs text-[#94A3B8]">
                        Showing {{ $transactions->total() }} recorded entry(ies)
                    </span>
                </div>

                @if($transactions->isEmpty())
                    <div class="p-12 text-center border border-dashed border-[#334155] rounded-2xl">
                        <i data-lucide="inbox" class="w-10 h-10 mx-auto text-[#94A3B8] mb-2 opacity-50"></i>
                        <p class="text-sm font-bold text-white font-brand-display">No ledger transactions recorded yet</p>
                        <p class="text-xs text-[#94A3B8] mt-1">Participate in verified events or finish missions to earn rewards.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="border-b border-[#334155] text-[#94A3B8] uppercase tracking-wider text-[10px] bg-[#0B1120]/80">
                                    <th class="py-3 px-4">Timestamp</th>
                                    <th class="py-3 px-4">Activity / Reason</th>
                                    <th class="py-3 px-4">Category</th>
                                    <th class="py-3 px-4 text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#334155]/60 text-slate-300">
                                @foreach($transactions as $tx)
                                <tr class="hover:bg-[#1E293B]/40 transition">
                                    <td class="py-3.5 px-4 font-mono text-[#94A3B8] whitespace-nowrap">
                                        {{ $tx->created_at->format('M d, Y • g:i A') }}
                                    </td>
                                    <td class="py-3.5 px-4 font-medium text-white font-brand-display">
                                        {{ $tx->description }}
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-mono uppercase bg-[#0B1120] text-slate-300 border border-[#334155]">
                                            {{ $tab === 'points' ? $tx->transaction_type : $tx->activity_type }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-right whitespace-nowrap font-bold font-mono tabular-nums {{ $tx->amount >= 0 ? ($tab === 'points' ? 'text-amber-400' : 'text-[#df747a]') : 'text-rose-400' }}">
                                        {{ $tx->amount > 0 ? '+' : '' }}{{ number_format($tx->amount) }} {{ $tab === 'points' ? 'Pts' : 'EXP' }}
                                        @if($tab !== 'points')
                                            <span class="sr-only">{{ number_format($tx->amount) }} XP</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $transactions->links() }}
                    </div>
                @endif
            </div>

            <div class="card bg-[#172033] border border-[#334155] p-6 space-y-4 shadow-xl">
                <div class="flex items-center gap-2">
                    <i data-lucide="layers" class="w-4 h-4 text-[#df747a]"></i>
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider font-brand-display">Level Thresholds & Progression Tiers</h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                    @foreach($allLevels as $lvl)
                    <div class="p-3.5 rounded-xl border {{ $currentLevel->level >= $lvl->level ? 'bg-burgundy-deep border-[#8F3033]/60 text-white' : 'bg-[#0B1120] border-[#334155] text-[#94A3B8]' }}">
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-[10px] font-bold uppercase tracking-wider font-mono">Rank {{ $lvl->level }}</span>
                            @if($currentLevel->level === $lvl->level)
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-[#560E0F] text-white border border-[#8F3033]">Current</span>
                            @elseif($currentLevel->level > $lvl->level)
                                <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-400"></i>
                            @endif
                        </div>
                        <h4 class="text-xs font-bold font-brand-display {{ $currentLevel->level >= $lvl->level ? 'text-white' : 'text-[#94A3B8]' }}">{{ $lvl->title }}</h4>
                        <p class="text-[11px] font-mono mt-1 text-[#94A3B8] tabular-nums">{{ number_format($lvl->min_xp) }} EXP minimum</p>
                    </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
