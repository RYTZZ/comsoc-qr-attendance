<x-layouts.admin :title="'Seasons & Term Cycles'">
    <div class="space-y-6">

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-white tracking-tight">Academic Seasons & Term Cycles</h1>
                <p class="text-xs text-slate-400 mt-1">
                    Manage computing society seasonal competitions, annual progression lifecycles, and leaderboard archive snapshots.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.seasons.report') }}" class="btn-secondary text-xs py-2 px-3.5 flex items-center gap-1.5">
                    <i data-lucide="bar-chart-2" class="w-4 h-4"></i>
                    <span>Semester Report</span>
                </a>

                @if(auth()->user()->isSuperAdmin())
                <button type="button" @click="$dispatch('open-modal', 'create-season-modal')"
                        class="btn-primary text-xs py-2 px-4 flex items-center gap-2">
                    <i data-lucide="plus-circle" class="w-4 h-4"></i>
                    <span>Create Season</span>
                </button>
                @endif
            </div>
        </div>

        @if($activeSeason)
        <div class="p-5 rounded-2xl bg-gradient-to-r from-red-950/40 via-[#172033] to-[#12141c] border border-[#560E0F]/60 shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        Active Season
                    </span>
                    <span class="text-xs text-slate-400 font-mono">{{ $activeSeason->code }}</span>
                </div>
                <h3 class="text-lg font-bold text-white font-brand-display">{{ $activeSeason->name }}</h3>
                <p class="text-xs text-slate-400">
                    Duration: {{ $activeSeason->start_date ? $activeSeason->start_date->format('M d, Y') : 'Start TBA' }} &mdash; {{ $activeSeason->end_date ? $activeSeason->end_date->format('M d, Y') : 'End TBA' }}
                    ({{ $activeSeason->academic_year_label }})
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.seasons.snapshots', $activeSeason) }}" class="btn-secondary text-xs py-2 px-3">
                    View Standings
                </a>

                @if(auth()->user()->isSuperAdmin())
                <a href="{{ route('admin.seasons.preview-reset', $activeSeason) }}" class="btn-primary text-xs py-2 px-3.5 bg-rose-700 hover:bg-rose-800 border-rose-600 flex items-center gap-1.5">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                    <span>Close & Reset Season</span>
                </a>
                @endif
            </div>
        </div>
        @endif

        <div class="card bg-[#172033] border border-slate-800 overflow-hidden shadow-xl">
            <div class="p-4 border-b border-slate-800 flex items-center justify-between">
                <h2 class="text-sm font-bold text-white uppercase tracking-wider font-brand-display">Season Directory & Historical Cycles</h2>
                <span class="text-xs text-slate-400">{{ $seasons->count() }} seasons recorded</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-900/80 text-slate-400 border-b border-slate-800 uppercase tracking-wider text-[11px]">
                        <tr>
                            <th class="py-3 px-4">Season Name</th>
                            <th class="py-3 px-4">Academic Term</th>
                            <th class="py-3 px-4">Date Range</th>
                            <th class="py-3 px-4">Registered Members</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300">
                        @forelse($seasons as $s)
                        <tr class="hover:bg-slate-900/40 transition">
                            <td class="py-3.5 px-4 font-bold text-white">
                                {{ $s->name }}
                            </td>
                            <td class="py-3.5 px-4 font-mono text-slate-400">
                                {{ $s->academic_year_label }}
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap text-slate-400">
                                {{ $s->start_date ? $s->start_date->format('M d, Y') : '--' }} &rarr; {{ $s->end_date ? $s->end_date->format('M d, Y') : '--' }}
                            </td>
                            <td class="py-3.5 px-4 font-mono">
                                {{ number_format($s->member_stats_count) }}
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                @if($s->is_active)
                                    <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-[10px] font-bold">
                                        Active
                                    </span>
                                @elseif($s->status === 'closed')
                                    <span class="px-2.5 py-0.5 rounded-full bg-slate-800 text-slate-400 border border-slate-700 text-[10px]">
                                        Archived
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/30 text-[10px]">
                                        Scheduled
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap text-right space-x-2">
                                <a href="{{ route('admin.seasons.snapshots', $s) }}" class="text-xs text-indigo-400 hover:text-indigo-300 font-semibold">
                                    Snapshots
                                </a>

                                @if($s->is_active && auth()->user()->isSuperAdmin())
                                <a href="{{ route('admin.seasons.preview-reset', $s) }}" class="text-xs text-rose-400 hover:text-rose-300 font-semibold">
                                    Reset
                                </a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-500">
                                No seasons configured.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    @if(auth()->user()->isSuperAdmin())
    <x-modal name="create-season-modal" :show="false" maxWidth="lg">
        <form method="POST" action="{{ route('admin.seasons.store') }}" class="p-6 space-y-4">
            @csrf
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <h3 class="text-base font-bold text-white">Configure New Season</h3>
                <button type="button" @click="$dispatch('close')" class="text-slate-400 hover:text-white">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <div class="space-y-1">
                <label class="text-xs font-semibold text-slate-300">Season Title</label>
                <input type="text" name="name" placeholder="Season 2026-2027: Cyber Odyssey" required class="input-dark w-full text-xs">
            </div>

            <div class="space-y-1">
                <label class="text-xs font-semibold text-slate-300">Academic Year Label</label>
                <input type="text" name="academic_year_label" placeholder="A.Y. 2026-2027" required class="input-dark w-full text-xs">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="text-xs font-semibold text-slate-300">Start Date</label>
                    <input type="date" name="start_date" required class="input-dark w-full text-xs">
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-semibold text-slate-300">End Date</label>
                    <input type="date" name="end_date" required class="input-dark w-full text-xs">
                </div>
            </div>

            <div class="pt-2">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" class="rounded border-slate-700 bg-slate-900 text-[#560E0F] focus:ring-[#560E0F]">
                    <span class="text-xs text-slate-300">Activate immediately (deactivates current active season)</span>
                </label>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
                <button type="button" @click="$dispatch('close')" class="btn-secondary text-xs py-2 px-4">
                    Cancel
                </button>
                <button type="submit" class="btn-primary text-xs py-2 px-4">
                    Create Season
                </button>
            </div>
        </form>
    </x-modal>
    @endif
</x-layouts.admin>
