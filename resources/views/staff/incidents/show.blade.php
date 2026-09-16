<x-layouts.admin :title="'Incident Report #' . $incident->id">
<div class="page-header">
    <div class="flex items-center gap-3">
        <a href="{{ route('staff.incidents.my') }}" class="btn-secondary btn-sm">← Back</a>
        <h1 class="page-title">Incident #{{ $incident->id }}</h1>
    </div>
</div>

<div class="card max-w-2xl space-y-4">
    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
        <div>
            <span class="badge uppercase text-[10px]
                @if($incident->status === 'resolved') badge-active
                @elseif($incident->status === 'archived') bg-slate-500/10 text-slate-400 border-slate-500/20
                @else bg-yellow-500/10 text-yellow-400 border-yellow-500/20 @endif">
                {{ strtoupper($incident->status) }}
            </span>
            <h2 class="text-sm font-semibold text-white mt-1">{{ ucwords(str_replace('_', ' ', $incident->category)) }}</h2>
        </div>
        <div class="text-right text-[11px] text-slate-400">
            <div>Filed: {{ $incident->created_at->format('M j, Y H:i') }}</div>
            @if($incident->event)
                <div class="text-brand-400">{{ $incident->event->name }}</div>
            @endif
        </div>
    </div>

    <div>
        <h3 class="text-xs font-semibold text-slate-400 mb-1">Narrative</h3>
        <p class="text-xs text-slate-200 bg-slate-950 p-4 rounded-xl border border-slate-800 leading-relaxed whitespace-pre-wrap">{{ $incident->description }}</p>
    </div>

    @if($incident->resolution_notes)
        <div class="border-t border-slate-800 pt-3">
            <h3 class="text-xs font-semibold text-emerald-400 mb-1">Administrative Resolution</h3>
            <p class="text-xs text-slate-300 bg-slate-950 p-3 rounded-lg border border-slate-800">{{ $incident->resolution_notes }}</p>
            <p class="text-[10px] text-slate-500 mt-1">Resolved by: {{ $incident->resolvedByUser?->name ?? 'Super Admin' }} on {{ $incident->resolved_at?->format('M j, Y') }}</p>
        </div>
    @endif
</div>
</x-layouts.admin>
