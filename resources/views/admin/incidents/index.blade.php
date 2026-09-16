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
                            <span class="badge
                                @if($inc->status === 'resolved') badge-resolved
                                @elseif($inc->status === 'archived') badge-archived
                                @else badge-open @endif">
                                @if($inc->status === 'resolved')✓ RESOLVED @elseif($inc->status === 'archived')📁 ARCHIVED @else⚠ ACTIVE @endif
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
