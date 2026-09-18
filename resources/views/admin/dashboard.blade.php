<x-layouts.admin :title="'Dashboard'">
<div class="page-header">
    <div>
        <h1 class="page-title">Dashboard</h1>
        @if($activeYear)
        <p class="text-sm text-slate-500 mt-1">{{ $activeYear->label }}</p>
        @endif
    </div>
    <a href="{{ route('admin.events.create') }}" class="btn-primary w-full sm:w-auto">
        <i data-lucide="plus" class="w-4 h-4 mr-1"></i>
        New Event
    </a>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6 sm:mb-8">
    <div class="stat-card">
        <div class="stat-icon bg-indigo-950 text-indigo-400">
            <i data-lucide="users" class="w-6 h-6"></i>
        </div>
        <div>
            <p class="text-2xl font-bold text-white">{{ number_format($stats['total_students']) }}</p>
            <p class="text-xs text-slate-400 mt-0.5">Total Students</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon bg-emerald-950 text-emerald-400">
            <i data-lucide="award" class="w-6 h-6"></i>
        </div>
        <div>
            <p class="text-2xl font-bold text-white">{{ number_format($stats['active_members']) }}</p>
            <p class="text-xs text-slate-400 mt-0.5">Active Members</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon bg-blue-950 text-blue-400">
            <i data-lucide="check-circle-2" class="w-6 h-6"></i>
        </div>
        <div>
            <p class="text-2xl font-bold text-white">{{ number_format($stats['todays_scans']) }}</p>
            <p class="text-xs text-slate-400 mt-0.5">Scans Today</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon {{ $stats['open_incidents'] > 0 ? 'bg-red-950 text-red-400' : 'bg-slate-800 text-slate-500' }}">
            <i data-lucide="alert-triangle" class="w-6 h-6"></i>
        </div>
        <div>
            <p class="text-2xl font-bold {{ $stats['open_incidents'] > 0 ? 'text-red-400' : 'text-white' }}">{{ $stats['open_incidents'] }}</p>
            <p class="text-xs text-slate-400 mt-0.5">Open Incidents</p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="card">
        <h2 class="section-title">Upcoming Events</h2>
        @if($stats['upcoming_events']->isEmpty())
        <div class="empty-state py-8">
            <div class="empty-state-icon flex items-center justify-center text-slate-500">
                <i data-lucide="calendar" class="w-10 h-10"></i>
            </div>
            <p class="empty-state-title">No upcoming events</p>
            <p class="empty-state-body">Create an event to get started.</p>
        </div>
        @else
        <div class="space-y-3">
            @foreach($stats['upcoming_events'] as $event)
            <a href="{{ route('admin.events.show', $event) }}" class="flex items-center gap-3 p-3 rounded-lg bg-slate-800/50 hover:bg-slate-800 transition-colors">
                <div class="w-10 h-10 rounded-lg bg-[#7A1618]/20 border border-[#7A1618]/30 flex flex-col items-center justify-center flex-shrink-0">
                    <span class="text-[#dfa6a9] text-xs font-bold">{{ $event->event_date->format('d') }}</span>
                    <span class="text-[#dfa6a9] text-[10px] uppercase font-mono">{{ $event->event_date->format('M') }}</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-white truncate">{{ $event->name }}</p>
                    <p class="text-xs text-slate-400">{{ $event->location ?? 'No location set' }}</p>
                </div>
                <span class="badge {{ $event->is_published ? 'badge-active' : 'badge-inactive' }}">
                    {{ $event->is_published ? 'Published' : 'Draft' }}
                </span>
            </a>
            @endforeach
        </div>
        @endif
    </div>

    <div class="card">
        <h2 class="section-title">Quick Actions</h2>
        <div class="grid grid-cols-2 gap-2 sm:gap-3">
            @if(auth()->user()->isSuperAdmin())
            <a href="{{ route('admin.masterlist.upload') }}" class="p-3 sm:p-4 rounded-xl bg-slate-800 hover:bg-slate-700 active:scale-[0.98] transition text-center group flex flex-col items-center justify-center min-h-[90px]">
                <div class="mb-1.5 flex items-center justify-center text-slate-400 group-hover:text-white transition">
                    <i data-lucide="file-spreadsheet" class="w-6 h-6"></i>
                </div>
                <p class="text-xs font-medium text-slate-300 group-hover:text-white leading-tight">Upload Masterlist</p>
            </a>
            <a href="{{ route('admin.qr-codes.index') }}" class="p-3 sm:p-4 rounded-xl bg-slate-800 hover:bg-slate-700 active:scale-[0.98] transition text-center group flex flex-col items-center justify-center min-h-[90px]">
                <div class="mb-1.5 flex items-center justify-center text-slate-400 group-hover:text-white transition">
                    <i data-lucide="qr-code" class="w-6 h-6"></i>
                </div>
                <p class="text-xs font-medium text-slate-300 group-hover:text-white leading-tight">QR Management</p>
            </a>
            @endif
            <a href="{{ route('admin.events.create') }}" class="p-3 sm:p-4 rounded-xl bg-slate-800 hover:bg-slate-700 active:scale-[0.98] transition text-center group flex flex-col items-center justify-center min-h-[90px]">
                <div class="mb-1.5 flex items-center justify-center text-slate-400 group-hover:text-white transition">
                    <i data-lucide="calendar-plus" class="w-6 h-6"></i>
                </div>
                <p class="text-xs font-medium text-slate-300 group-hover:text-white leading-tight">New Event</p>
            </a>
            <a href="{{ route('admin.attendance.index') }}" class="p-3 sm:p-4 rounded-xl bg-slate-800 hover:bg-slate-700 active:scale-[0.98] transition text-center group flex flex-col items-center justify-center min-h-[90px]">
                <div class="mb-1.5 flex items-center justify-center text-slate-400 group-hover:text-white transition">
                    <i data-lucide="check-circle-2" class="w-6 h-6"></i>
                </div>
                <p class="text-xs font-medium text-slate-300 group-hover:text-white leading-tight">Attendance Logs</p>
            </a>
        </div>
    </div>
</div>
</x-layouts.admin>
