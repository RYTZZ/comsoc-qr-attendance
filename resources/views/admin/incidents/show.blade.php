<x-layouts.admin :title="'Incident #' . $incident->id">
<div class="page-header">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.incidents.index') }}" class="btn-secondary btn-sm">← Back</a>
        <h1 class="page-title">Incident #{{ $incident->id }}</h1>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="md:col-span-2 space-y-6">
        <div class="card space-y-4">
            <div class="flex items-start justify-between">
                <div>
                    <span class="badge uppercase tracking-wider
                        @if($incident->severity === 'high') bg-red-500/10 text-red-400 border-red-500/20
                        @elseif($incident->severity === 'medium') bg-amber-500/10 text-amber-400 border-amber-500/20
                        @else bg-blue-500/10 text-blue-400 border-blue-500/20 @endif">
                        {{ $incident->severity }} severity
                    </span>
                    <h2 class="text-base font-semibold text-white mt-2">{{ $incident->category ?? 'General Report' }}</h2>
                </div>
                <span class="badge
                    @if($incident->status === 'resolved') badge-active
                    @elseif($incident->status === 'archived') bg-slate-500/10 text-slate-400 border-slate-500/20
                    @else bg-yellow-500/10 text-yellow-400 border-yellow-500/20 @endif">
                    {{ strtoupper($incident->status) }}
                </span>
            </div>

            <div class="bg-slate-950 p-4 rounded-xl border border-slate-800 text-xs text-slate-200 leading-relaxed whitespace-pre-wrap">
                {{ $incident->description }}
            </div>

            @if($incident->resolution_notes)
                <div class="border-t border-slate-800 pt-4">
                    <h3 class="text-xs font-semibold text-emerald-400 mb-1">Resolution Notes</h3>
                    <p class="text-xs text-slate-300 bg-slate-950 p-3 rounded-lg border border-slate-800">{{ $incident->resolution_notes }}</p>
                    <p class="text-[11px] text-slate-500 mt-1">Resolved at: {{ $incident->resolved_at?->format('M j, Y H:i') }}</p>
                </div>
            @endif
        </div>

        @if($incident->status === 'open' && auth()->user()->role === 'super_admin')
            <div class="card">
                <h3 class="text-sm font-semibold text-white mb-3">Resolve Incident</h3>
                <form method="POST" action="{{ route('admin.incidents.resolve', $incident) }}" class="space-y-3">
                    @csrf
                    <div>
                        <label for="resolution_notes" class="label">Resolution Summary / Action Taken <span class="text-red-400">*</span></label>
                        <textarea id="resolution_notes" name="resolution_notes" rows="3" required class="input" placeholder="Document the action taken..."></textarea>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="btn-primary">Mark as Resolved</button>
                    </div>
                </form>
            </div>
        @endif
    </div>

    <div class="space-y-6">
        <div class="card space-y-3">
            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Incident Details</h3>
            <div class="text-xs space-y-2">
                <div><span class="text-slate-500">Reported By:</span> <p class="text-white font-medium">{{ $incident->reporter?->name ?? 'System' }}</p></div>
                <div><span class="text-slate-500">Reported At:</span> <p class="text-white">{{ $incident->created_at->format('M j, Y H:i:s') }}</p></div>
                <div><span class="text-slate-500">Subject Type:</span> <p class="text-white">{{ class_basename($incident->subject_type ?? '') ?: 'None' }}</p></div>
                @if($incident->subject)
                    <div><span class="text-slate-500">Subject Name:</span> <p class="text-white font-medium">{{ $incident->subject->display_name ?? $incident->subject->name }}</p></div>
                @endif
            </div>
        </div>
    </div>
</div>
</x-layouts.admin>
