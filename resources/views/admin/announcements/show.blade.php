<x-layouts.admin :title="'Read Receipts & Acknowledgment Audit'">
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <a href="{{ route('admin.announcements.index') }}" class="text-xs text-[#df747a] hover:underline flex items-center gap-1 mb-1 font-semibold">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                Back to Announcements
            </a>
            <h1 class="text-2xl font-extrabold text-white font-brand-display tracking-tight">
                Read Receipts & Acknowledgment Audit
            </h1>
            <p class="text-xs text-slate-400 mt-1">
                Announcement: <span class="font-bold text-white">{{ $announcement->title }}</span>
            </p>
        </div>
    </div>

    <div class="py-6 space-y-6">

        <!-- Stat Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <div class="card bg-slate-900 border border-slate-800 p-4">
                <span class="block text-xs text-slate-400">Total Members</span>
                <span class="text-2xl font-black text-white font-brand-display">{{ number_format($totalEligibleMembers) }}</span>
            </div>

            <div class="card bg-slate-900 border border-blue-500/30 p-4">
                <span class="block text-xs text-blue-400">Opened / Read Count</span>
                <span class="text-2xl font-black text-blue-300 font-brand-display">{{ number_format($readCount) }}</span>
                <span class="text-[10px] text-slate-400 mt-1 block">
                    {{ $totalEligibleMembers > 0 ? round(($readCount / $totalEligibleMembers) * 100, 1) : 0 }}% Open Rate
                </span>
            </div>

            <div class="card bg-slate-900 border border-emerald-500/30 p-4">
                <span class="block text-xs text-emerald-400">Explicit Acknowledgments</span>
                <span class="text-2xl font-black text-emerald-300 font-brand-display">{{ number_format($acknowledgedCount) }}</span>
                <span class="text-[10px] text-slate-400 mt-1 block">
                    {{ $announcement->requires_acknowledgment ? 'Required Action' : 'Optional' }}
                </span>
            </div>

            <div class="card bg-slate-900 border border-amber-500/30 p-4">
                <span class="block text-xs text-amber-400">Unread Count</span>
                <span class="text-2xl font-black text-amber-300 font-brand-display">{{ max(0, $totalEligibleMembers - $readCount) }}</span>
            </div>
        </div>

        <!-- Receipts Table -->
        <div class="card bg-slate-900 border border-slate-800 overflow-hidden shadow-xl">
            <div class="px-5 py-4 border-b border-slate-800 flex items-center justify-between">
                <h3 class="text-xs font-bold text-white uppercase tracking-wider">Member Read Log</h3>
                <span class="text-xs text-slate-400">{{ $receipts->total() }} recorded reads</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-950 text-slate-400 uppercase text-[10px] tracking-wider border-b border-slate-800">
                        <tr>
                            <th class="px-5 py-3">Member Name</th>
                            <th class="px-5 py-3">Student #</th>
                            <th class="px-5 py-3">Program / Year</th>
                            <th class="px-5 py-3">Read Timestamp</th>
                            <th class="px-5 py-3">Acknowledged Timestamp</th>
                            <th class="px-5 py-3 text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @forelse($receipts as $rcp)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-5 py-3 font-bold text-white whitespace-nowrap">
                                {{ $rcp->user?->name }}
                            </td>
                            <td class="px-5 py-3 font-mono text-slate-300 whitespace-nowrap">
                                {{ auth()->user()?->isSuperAdmin() ? ($rcp->user?->student?->student_number ?? 'N/A') : ($rcp->user?->member_id ?? 'N/A') }}
                            </td>
                            <td class="px-5 py-3 text-slate-300 whitespace-nowrap">
                                {{ $rcp->user?->student?->program ?? 'N/A' }} - Yr {{ $rcp->user?->student?->year_level ?? 'N/A' }}
                            </td>
                            <td class="px-5 py-3 text-slate-300 whitespace-nowrap">
                                {{ $rcp->read_at ? $rcp->read_at->format('M d, Y H:i:s') : 'Unread' }}
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap">
                                @if($rcp->acknowledged_at)
                                <span class="text-emerald-400 font-semibold">{{ $rcp->acknowledged_at->format('M d, Y H:i:s') }}</span>
                                @else
                                <span class="text-slate-500">Not Acknowledged</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                @if($rcp->acknowledged_at)
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                    Acknowledged
                                </span>
                                @elseif($rcp->read_at)
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-500/20 text-blue-300 border border-blue-500/30">
                                    Opened
                                </span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-400">No member has opened this announcement yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-800">
                {{ $receipts->links() }}
            </div>
        </div>

    </div>
</div>
</x-layouts.admin>
