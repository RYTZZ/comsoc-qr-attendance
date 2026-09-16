<x-layouts.admin :title="'Audit Log #' . $auditLog->id">
<div class="page-header">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.audit-logs.index') }}" class="btn-secondary btn-sm">← Back to Logs</a>
        <h1 class="page-title">Audit Record #{{ $auditLog->id }}</h1>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="card space-y-3">
        <h2 class="text-sm font-semibold text-white border-b border-slate-800 pb-2">Log Metadata</h2>
        <div class="text-xs space-y-2">
            <div><span class="text-slate-400">Action:</span> <span class="font-mono text-brand-300 font-medium">{{ $auditLog->action }}</span></div>
            <div><span class="text-slate-400">Actor:</span> <span class="text-white">{{ $auditLog->user->name ?? 'System' }} ({{ $auditLog->user->email ?? 'N/A' }})</span></div>
            <div><span class="text-slate-400">Timestamp:</span> <span class="text-white">{{ $auditLog->created_at->format('F j, Y — H:i:s T') }}</span></div>
            <div><span class="text-slate-400">IP Address:</span> <span class="font-mono text-white">{{ $auditLog->ip_address ?? 'N/A' }}</span></div>
            <div><span class="text-slate-400">Target Type:</span> <span class="text-white">{{ $auditLog->auditable_type ?? 'N/A' }}</span></div>
            <div><span class="text-slate-400">Target ID:</span> <span class="text-white">{{ $auditLog->auditable_id ?? 'N/A' }}</span></div>
        </div>
    </div>

    <div class="card space-y-3">
        <h2 class="text-sm font-semibold text-white border-b border-slate-800 pb-2">Payload Details</h2>
        <pre class="bg-slate-950 p-4 rounded-xl border border-slate-800 text-[11px] font-mono text-slate-300 overflow-x-auto max-h-80">{{ json_encode($auditLog->payload ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
    </div>
</div>
</x-layouts.admin>
