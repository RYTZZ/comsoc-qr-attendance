<x-layouts.admin :title="'Audit Logs'">
<div class="page-header">
    <div>
        <h1 class="page-title">System Audit Trail</h1>
        <p class="text-xs text-slate-400 mt-1">Immutable record of administrative actions, masterlist imports, and card operations.</p>
    </div>
</div>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="bg-slate-950/60 text-slate-400 border-b border-slate-800">
                <tr>
                    <th class="py-3 px-4">Timestamp</th>
                    <th class="py-3 px-4">Action</th>
                    <th class="py-3 px-4">Actor</th>
                    <th class="py-3 px-4">Target Entity</th>
                    <th class="py-3 px-4">IP Address</th>
                    <th class="py-3 px-4 text-right">Details</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
                @forelse($logs as $log)
                    <tr class="hover:bg-slate-800/30 transition">
                        <td class="py-3 px-4 text-slate-400 whitespace-nowrap">{{ $log->created_at->format('M j, Y H:i:s') }}</td>
                        <td class="py-3 px-4 font-mono font-medium text-brand-300">{{ $log->action }}</td>
                        <td class="py-3 px-4 text-slate-300">{{ $log->user->name ?? 'System' }}</td>
                        <td class="py-3 px-4 text-slate-400">
                            {{ class_basename($log->auditable_type ?? '') }}
                            @if($log->auditable_id)<span class="text-slate-600">#{{ $log->auditable_id }}</span>@endif
                        </td>
                        <td class="py-3 px-4 font-mono text-slate-500">{{ $log->ip_address ?? '—' }}</td>
                        <td class="py-3 px-4 text-right">
                            <a href="{{ route('admin.audit-logs.show', $log) }}" class="btn-secondary btn-sm">Inspect</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-500">No audit records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($logs, 'links'))
        <div class="p-4 border-t border-slate-800">
            {{ $logs->links() }}
        </div>
    @endif
</div>
</x-layouts.admin>
