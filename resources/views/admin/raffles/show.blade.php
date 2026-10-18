<x-layouts.admin :title="$raffle->title . ' — Raffle Details'">
<div class="page-header flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.raffles.index') }}" class="btn-ghost btn-xs">← Back to Raffles</a>
            <h1 class="page-title text-2xl font-bold text-white tracking-tight">{{ $raffle->title }}</h1>
        </div>
        <p class="text-xs text-slate-400 mt-1">
            Event: <strong class="text-slate-200">{{ $raffle->event->name ?? 'N/A' }}</strong> • 
            Prize: <strong class="text-amber-300">{{ $raffle->prize_name }}</strong> (+{{ number_format($raffle->points_bonus) }} pts) •
            Mode: <strong class="text-indigo-300 uppercase">{{ str_replace('_', ' ', $raffle->entry_type) }}</strong>
            @if($raffle->entry_type === 'points_purchase')
                (Cost: <strong class="text-amber-400">{{ $raffle->points_per_entry }} pts</strong>/entry, Max: {{ $raffle->max_entries_per_user }})
            @endif
            @if($raffle->reward)
                • Catalog Reward: <span class="text-emerald-400">{{ $raffle->reward->title }}</span>
            @endif
        </p>
    </div>
    <div>
        @if(!$raffle->isDrawn())
            <form action="{{ route('admin.raffles.draw', $raffle) }}" method="POST" onsubmit="return confirm('Execute server-side randomized draw among eligible entries? Winners cannot duplicate.');">
                @csrf
                <button type="submit" class="btn-primary text-xs flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    <span>Execute Randomized Draw</span>
                </button>
            </form>
        @else
            <span class="badge badge-success text-xs px-3 py-1.5">Draw Completed on {{ $raffle->drawn_at?->format('M d, Y h:i A') }} by {{ $raffle->drawer?->name ?? 'Administrator' }}</span>
        @endif
    </div>
</div>

@if(session('success'))
    <div class="mb-4 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 text-sm">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="mb-4 p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-300 text-sm">
        {{ session('error') }}
    </div>
@endif

<!-- Drawn Winners -->
@if($raffle->winners->isNotEmpty())
    <div class="card mb-6 border-indigo-500/30 bg-slate-900/80">
        <h2 class="text-lg font-bold text-white mb-3 flex items-center gap-2">
            <span>🎉 Official Winners Snapshot</span>
            <span class="text-xs font-normal text-slate-400">({{ $raffle->winners->count() }} selected)</span>
        </h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-900 text-slate-400 font-semibold border-b border-slate-800">
                    <tr>
                        <th class="p-3">Order</th>
                        <th class="p-3">Member Name</th>
                        <th class="p-3">Email</th>
                        <th class="p-3">Bonus Awarded</th>
                        <th class="p-3">Claim Status</th>
                        <th class="p-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @foreach($raffle->winners as $winner)
                        <tr>
                            <td class="p-3 font-bold text-amber-400">#{{ $winner->draw_order }}</td>
                            <td class="p-3 font-semibold text-white">{{ $winner->user->name ?? 'Unknown' }}</td>
                            <td class="p-3 text-slate-300">{{ $winner->user->email ?? 'N/A' }}</td>
                            <td class="p-3 text-emerald-400 font-mono">+{{ number_format($winner->points_awarded) }} pts</td>
                            <td class="p-3">
                                @if($winner->is_claimed)
                                    <span class="badge badge-success">Claimed ({{ $winner->claimed_at?->format('M d, Y') }})</span>
                                    @if($winner->claimedByStaff)
                                        <div class="text-[10px] text-slate-400">Staff: {{ $winner->claimedByStaff->name }}</div>
                                    @endif
                                @else
                                    <span class="badge badge-warning">Unclaimed</span>
                                @endif
                            </td>
                            <td class="p-3 text-right">
                                @if(!$winner->is_claimed)
                                    <form action="{{ route('admin.raffles.claim', $winner) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="btn-ghost btn-xs text-indigo-400">Mark Claimed</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

<!-- Attendee Entry Pool -->
<div class="card">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="text-base font-bold text-white">Eligible Entry Pool Preview</h2>
            <p class="text-xs text-slate-400">Preview eligible entries before executing the server-side draw. Max {{ $raffle->max_entries_per_user }} entry per student.</p>
        </div>
        <span class="text-xs px-2.5 py-1 rounded bg-slate-800 text-slate-300 border border-slate-700">
            Total Entries: {{ $raffle->entries->count() }}
        </span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="bg-slate-900/50 text-slate-400 font-semibold border-b border-slate-800">
                <tr>
                    <th class="p-3">Member Name</th>
                    <th class="p-3">Email</th>
                    <th class="p-3">Entry Source</th>
                    <th class="p-3">Eligible Since</th>
                    <th class="p-3">Draw Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
                @forelse($raffle->entries as $entry)
                    <tr class="hover:bg-slate-800/20">
                        <td class="p-3 font-semibold text-white">{{ $entry->user->name ?? 'Unknown' }}</td>
                        <td class="p-3 text-slate-300">{{ $entry->user->email ?? 'N/A' }}</td>
                        <td class="p-3 text-slate-400 capitalize">
                            {{ str_replace('_', ' ', $entry->source_type ?? 'attendance') }}
                            @if($entry->points_deducted > 0)
                                <span class="text-amber-400 font-mono">({{ $entry->points_deducted }} pts)</span>
                            @endif
                        </td>
                        <td class="p-3 text-slate-400">{{ $entry->eligible_at?->format('M d, Y h:i A') }}</td>
                        <td class="p-3">
                            @if($entry->winner)
                                <span class="badge badge-success">Winner (#{{ $entry->winner->draw_order }})</span>
                            @else
                                <span class="badge badge-neutral">Entered</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-6 text-center text-slate-500">
                            No eligible entries recorded yet in this raffle pool.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</x-layouts.admin>
