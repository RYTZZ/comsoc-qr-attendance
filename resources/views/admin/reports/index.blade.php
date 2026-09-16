<x-layouts.admin :title="'System Reports'">
<div class="page-header">
    <div>
        <h1 class="page-title">Reports & Data Exports</h1>
        <p class="text-xs text-slate-400 mt-1">Export official Excel, CSV, and PDF summaries for society archives.</p>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <div class="card space-y-3 hover:border-slate-700 transition">
        <div class="w-10 h-10 rounded-xl bg-blue-500/10 text-blue-400 flex items-center justify-center">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
        </div>
        <h2 class="text-sm font-semibold text-white">Membership Master Report</h2>
        <p class="text-xs text-slate-400 leading-relaxed">Official list of all registered students with active/inactive society membership status.</p>
        <div class="pt-2 flex gap-2">
            <a href="{{ route('admin.reports.membership', ['format' => 'csv']) }}" class="btn-secondary btn-sm">Export CSV</a>
            <a href="{{ route('admin.reports.membership', ['format' => 'pdf']) }}" class="btn-primary btn-sm">Export PDF</a>
        </div>
    </div>

    <div class="card space-y-3 hover:border-slate-700 transition">
        <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
        </div>
        <h2 class="text-sm font-semibold text-white">Event Attendance Logs</h2>
        <p class="text-xs text-slate-400 leading-relaxed">Granular attendance records with IN/OUT timestamps, late flags, and guest records.</p>
        <div class="pt-2 flex gap-2">
            <a href="{{ route('admin.reports.attendance', ['format' => 'csv']) }}" class="btn-secondary btn-sm">Export CSV</a>
        </div>
    </div>

    <div class="card space-y-3 hover:border-slate-700 transition">
        <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 15.546c-.523 0-1.046.151-1.5.454a2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0 2.701 2.701 0 00-1.5-.454M9 6v2m3-2v2m3-2v2M9 3h.01M12 3h.01M15 3h.01M21 21v-7a2 2 0 00-2-2H5a2 2 0 00-2 2v7h18zm-3-9V9a2 2 0 00-2-2H8a2 2 0 00-2 2v3h12z"/></svg>
        </div>
        <h2 class="text-sm font-semibold text-white">Snack Distribution Log</h2>
        <p class="text-xs text-slate-400 leading-relaxed">Inventory decrement audits, claimant student numbers, and snack session breakdown.</p>
        <div class="pt-2 flex gap-2">
            <a href="{{ route('admin.reports.snacks', ['format' => 'csv']) }}" class="btn-secondary btn-sm">Export CSV</a>
        </div>
    </div>

    <div class="card space-y-3 hover:border-slate-700 transition">
        <div class="w-10 h-10 rounded-xl bg-red-500/10 text-red-400 flex items-center justify-center">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        </div>
        <h2 class="text-sm font-semibold text-white">Incident Summary</h2>
        <p class="text-xs text-slate-400 leading-relaxed">Official incident log with severity ratings, staff notes, and resolution actions.</p>
        <div class="pt-2 flex gap-2">
            <a href="{{ route('admin.reports.incidents', ['format' => 'csv']) }}" class="btn-secondary btn-sm">Export CSV</a>
        </div>
    </div>

    <div class="card space-y-3 hover:border-slate-700 transition">
        <div class="w-10 h-10 rounded-xl bg-purple-500/10 text-purple-400 flex items-center justify-center">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
        </div>
        <h2 class="text-sm font-semibold text-white">QR & Physical Cards</h2>
        <p class="text-xs text-slate-400 leading-relaxed">Inventory and issuance metrics for printed PVC ID cards and digital tokens.</p>
        <div class="pt-2 flex gap-2">
            <a href="{{ route('admin.reports.qr-card', ['format' => 'csv']) }}" class="btn-secondary btn-sm">Export CSV</a>
        </div>
    </div>
</div>
</x-layouts.admin>
