<x-layouts.admin :title="'Season Standings Snapshot'">
    <div class="space-y-6">

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.seasons.index') }}" class="p-2 rounded-lg bg-slate-800 text-slate-400 hover:text-white transition">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </a>
            <div>
                <span class="text-xs text-slate-400 uppercase tracking-wider font-semibold">Archived Season Standings</span>
                <h1 class="text-2xl font-bold text-white tracking-tight">{{ $season->name }} Standings Snapshot</h1>
            </div>
        </div>

        <div class="card bg-[#172033] border border-slate-800 overflow-hidden shadow-xl">
            <div class="p-4 border-b border-slate-800 flex items-center justify-between">
                <h2 class="text-sm font-bold text-white uppercase tracking-wider font-brand-display">Final Rankings Archive</h2>
                <span class="text-xs text-slate-400 font-mono">{{ $snapshots->total() }} records archived</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-900/80 text-slate-400 border-b border-slate-800 uppercase tracking-wider text-[11px]">
                        <tr>
                            <th class="py-3 px-4">Rank</th>
                            <th class="py-3 px-4">Member</th>
                            <th class="py-3 px-4">Student ID</th>
                            <th class="py-3 px-4">Final XP</th>
                            <th class="py-3 px-4">Final Points</th>
                            <th class="py-3 px-4">Final Level</th>
                            <th class="py-3 px-4">Archived At</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300 font-mono">
                        @forelse($snapshots as $snap)
                        <tr class="hover:bg-slate-900/40 transition">
                            <td class="py-3 px-4 font-bold text-white">
                                <span class="w-6 h-6 rounded-full inline-flex items-center justify-center text-[10px] {{ $snap->final_rank <= 3 ? 'bg-amber-500/20 text-amber-300 border border-amber-500/40' : 'bg-slate-800 text-slate-400' }}">
                                    #{{ $snap->final_rank }}
                                </span>
                            </td>
                            <td class="py-3 px-4 font-sans font-semibold text-white">
                                {{ $snap->user->name }}
                            </td>
                            <td class="py-3 px-4 text-slate-400">
                                {{ $snap->user->student?->student_number ?? '--' }}
                            </td>
                            <td class="py-3 px-4 font-bold text-[#dfa6a9]">
                                {{ number_format($snap->final_xp) }} XP
                            </td>
                            <td class="py-3 px-4 text-amber-300">
                                {{ number_format($snap->final_points) }}
                            </td>
                            <td class="py-3 px-4 text-slate-300">
                                Level {{ $snap->final_level }}
                            </td>
                            <td class="py-3 px-4 text-slate-500 text-[11px]">
                                {{ $snap->snapshot_at->format('M d, Y h:i A') }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-500 font-sans">
                                No final snapshots recorded for this season yet.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($snapshots->hasPages())
            <div class="p-4 border-t border-slate-800">
                {{ $snapshots->links() }}
            </div>
            @endif
        </div>

    </div>
</x-layouts.admin>
