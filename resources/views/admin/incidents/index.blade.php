<x-layouts.admin :title="'Incidents'">
<div class="page-header">
    <div>
        <h1 class="page-title">Incident Reports</h1>
        <p class="text-xs text-slate-400 mt-1">Review behavioral infractions, scan anomalies, and administrative disputes.</p>
    </div>
</div>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="bg-slate-950/60 text-slate-400 border-b border-slate-800">
                <tr>
                    <th class="py-3 px-4">Date</th>
                    <th class="py-3 px-4">Category</th>
                    <th class="py-3 px-4">Subject</th>
                    <th class="py-3 px-4">Severity</th>
                    <th class="py-3 px-4">Status</th>
                    <th class="py-3 px-4">Reported By</th>
                    <th class="py-3 px-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
                @forelse($incidents as $inc)
                    <tr class="hover:bg-slate-800/30 transition">
                        <td class="py-3 px-4 text-slate-400 whitespace-nowrap">{{ $inc->created_at->format('M j, Y H:i') }}</td>
                        <td class="py-3 px-4 text-white font-medium">{{ $inc->category ?? 'General' }}</td>
                        <td class="py-3 px-4 text-slate-300">
                            {{ $inc->subject?->display_name ?? $inc->subject?->name ?? '—' }}
                        </td>
                        <td class="py-3 px-4">
                            <span class="badge
                                @if($inc->severity === 'high') badge-rejected
                                @elseif($inc->severity === 'medium') badge-pending
                                @else bg-blue-950 text-blue-300 ring-1 ring-blue-500/30 @endif">
                                ● {{ strtoupper($inc->severity) }}
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            <span class="badge inline-flex items-center gap-1
                                @if($inc->status === 'resolved') badge-resolved
                                @elseif($inc->status === 'archived') badge-archived
                                @else badge-open @endif">
                                @if($inc->status === 'resolved')
                                    <svg class="w-3 h-3 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    RESOLVED
                                @elseif($inc->status === 'archived')
                                    <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                                    ARCHIVED
                                @else
                                    <svg class="w-3 h-3 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    ACTIVE
                                @endif
                            </span>
                        </td>
                        <td class="py-3 px-4 text-slate-400">{{ $inc->reporter?->name ?? 'Staff' }}</td>
                        <td class="py-3 px-4 text-right">
                            <a href="{{ route('admin.incidents.show', $inc) }}" class="btn-secondary btn-sm">Review</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-500">No incident reports recorded.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($incidents, 'links'))
        <div class="p-4 border-t border-slate-800">
            {{ $incidents->links() }}
        </div>
    @endif
</div>
</x-layouts.admin>
