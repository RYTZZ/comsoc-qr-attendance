<x-layouts.admin :title="'Season Reset Preview'">
    <div class="space-y-6">

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.seasons.index') }}" class="p-2 rounded-lg bg-slate-800 text-slate-400 hover:text-white transition">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </a>
            <div>
                <span class="text-xs text-rose-400 uppercase tracking-wider font-semibold">Season Closure Workflow</span>
                <h1 class="text-2xl font-bold text-white tracking-tight">Preview & Execute Reset: {{ $season->name }}</h1>
            </div>
        </div>

        <div class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-300 text-xs flex items-start gap-3 leading-relaxed">
            <i data-lucide="alert-triangle" class="w-5 h-5 flex-shrink-0 mt-0.5"></i>
            <div>
                <p class="font-bold">Important Academic-Year Season Reset Protocol:</p>
                <p class="mt-0.5">
                    Executing this reset will finalize final rankings, archive historical leaderboard standings into immutable snapshots, expire {{ number_format($preview['total_expiring_points']) }} unspent points according to published policy, and transition the active status to the next selected season. Lifetime XP and all event logs are permanently preserved.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="card p-5 bg-[#172033] border border-slate-800">
                <span class="text-xs text-slate-400 font-semibold uppercase tracking-wider block mb-1">Participating Members</span>
                <span class="text-2xl font-bold font-mono text-white">{{ number_format($preview['member_count']) }}</span>
            </div>

            <div class="card p-5 bg-[#172033] border border-slate-800">
                <span class="text-xs text-slate-400 font-semibold uppercase tracking-wider block mb-1">Total Season XP Recorded</span>
                <span class="text-2xl font-bold font-mono text-[#dfa6a9]">{{ number_format($preview['total_season_xp']) }}</span>
            </div>

            <div class="card p-5 bg-[#172033] border border-slate-800">
                <span class="text-xs text-slate-400 font-semibold uppercase tracking-wider block mb-1">Unspent Points to Expire</span>
                <span class="text-2xl font-bold font-mono text-amber-300">{{ number_format($preview['total_expiring_points']) }}</span>
                @if($preview['pending_redemptions'] > 0)
                <p class="text-[11px] text-rose-400 mt-1 font-semibold">
                    Warning: {{ $preview['pending_redemptions'] }} pending claims exist!
                </p>
                @endif
            </div>
        </div>

        <div class="card bg-[#172033] border border-slate-800 p-6 space-y-4">
            <h2 class="text-sm font-bold text-white uppercase tracking-wider font-brand-display">Top Season Standings Snapshot Preview</h2>

            <div class="space-y-2">
                @forelse($preview['top_members'] as $idx => $stat)
                <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800 flex items-center justify-between text-xs">
                    <div class="flex items-center gap-3">
                        <span class="w-6 h-6 rounded-full bg-[#560E0F] text-white flex items-center justify-center font-bold text-[11px]">
                            #{{ $idx + 1 }}
                        </span>
                        <div>
                            <p class="font-bold text-white">{{ $stat->user->name }}</p>
                            <p class="text-[11px] text-slate-500 font-mono">{{ auth()->user()?->isSuperAdmin() ? ($stat->user->student?->student_number ?? 'Student') : ($stat->user->member_id ?? 'Student') }}</p>
                        </div>
                    </div>
                    <div class="text-right font-mono">
                        <span class="font-bold text-[#dfa6a9]">{{ number_format($stat->season_xp) }} XP</span>
                        <span class="text-slate-500 ml-2">Level {{ $stat->current_level }}</span>
                    </div>
                </div>
                @empty
                <p class="text-xs text-slate-500 py-4 text-center">No member stats recorded in this season.</p>
                @endforelse
            </div>
        </div>

        <div class="card bg-[#172033] border border-rose-900/50 p-6 space-y-4">
            <h2 class="text-base font-bold text-white font-brand-display flex items-center gap-2 text-rose-400">
                <i data-lucide="shield-alert" class="w-5 h-5"></i>
                <span>Execute Final Season Closure</span>
            </h2>

            <form method="POST" action="{{ route('admin.seasons.execute-reset', $season) }}" class="space-y-4">
                @csrf

                <div class="space-y-1">
                    <label class="text-xs font-semibold text-slate-300">Next Season to Activate (Optional)</label>
                    @php
                        $nextOptions = [['value' => '', 'label' => 'None (Remain in Off-Season)']];
                        foreach($availableNextSeasons as $ns) {
                            $nextOptions[] = ['value' => $ns->id, 'label' => $ns->name . ' (' . $ns->academic_year_label . ')'];
                        }
                    @endphp
                    <x-custom-dropdown
                        name="next_season_id"
                        :options="$nextOptions"
                        placeholder="Select Next Season"
                    />
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-semibold text-slate-300">
                        Type <span class="font-mono text-white font-bold bg-slate-900 px-1.5 py-0.5 rounded border border-slate-700">{{ $season->name }}</span> to confirm closure:
                    </label>
                    <input type="text" name="confirm_name" required class="input-dark w-full text-xs">
                </div>

                <div class="pt-2">
                    <button type="submit" class="btn-primary text-xs py-2.5 px-5 bg-rose-700 hover:bg-rose-800 border-rose-600 font-bold flex items-center gap-2"
                            onclick="return confirm('Are you strictly sure? This process is irreversible and creates permanent leaderboard snapshots.');">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        <span>Confirm and Finalize Season Reset</span>
                    </button>
                </div>
            </form>
        </div>

    </div>
</x-layouts.admin>
