<x-layouts.admin :title="'Dashboard'">
<div class="page-header">
    <div>
        <h1 class="page-title">Dashboard</h1>
        @if($activeYear)
        <p class="text-sm text-slate-500 mt-1">{{ $activeYear->label }}</p>
        @endif
    </div>
    <a href="{{ route('admin.events.create') }}" class="btn-primary w-full sm:w-auto">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        New Event
    </a>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6 sm:mb-8">
    <div class="stat-card">
        <div class="stat-icon bg-indigo-950 text-indigo-400">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        </div>
        <div>
            <p class="text-2xl font-bold text-white">{{ number_format($stats['total_students']) }}</p>
            <p class="text-xs text-slate-500 mt-0.5">Total Students</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon bg-emerald-950 text-emerald-400">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
        </div>
        <div>
            <p class="text-2xl font-bold text-white">{{ number_format($stats['active_members']) }}</p>
            <p class="text-xs text-slate-500 mt-0.5">Active Members</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon bg-blue-950 text-blue-400">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        </div>
        <div>
            <p class="text-2xl font-bold text-white">{{ number_format($stats['todays_scans']) }}</p>
            <p class="text-xs text-slate-500 mt-0.5">Scans Today</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon {{ $stats['open_incidents'] > 0 ? 'bg-red-950 text-red-400' : 'bg-slate-800 text-slate-500' }}">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        </div>
        <div>
            <p class="text-2xl font-bold {{ $stats['open_incidents'] > 0 ? 'text-red-400' : 'text-white' }}">{{ $stats['open_incidents'] }}</p>
            <p class="text-xs text-slate-500 mt-0.5">Open Incidents</p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="card">
        <h2 class="section-title">Upcoming Events</h2>
        @if($stats['upcoming_events']->isEmpty())
        <div class="empty-state py-8">
            <div class="empty-state-icon flex items-center justify-center">
                <svg class="w-8 h-8 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2" stroke-width="1.5"></rect>
                    <line x1="16" y1="2" x2="16" y2="6" stroke-width="1.5" stroke-linecap="round"></line>
                    <line x1="8" y1="2" x2="8" y2="6" stroke-width="1.5" stroke-linecap="round"></line>
                    <line x1="3" y1="10" x2="21" y2="10" stroke-width="1.5"></line>
                </svg>
            </div>
            <p class="empty-state-title">No upcoming events</p>
            <p class="empty-state-body">Create an event to get started.</p>
        </div>
        @else
        <div class="space-y-3">
            @foreach($stats['upcoming_events'] as $event)
            <a href="{{ route('admin.events.show', $event) }}" class="flex items-center gap-3 p-3 rounded-lg bg-slate-800/50 hover:bg-slate-800 transition-colors">
                <div class="w-10 h-10 rounded-lg bg-indigo-950 flex flex-col items-center justify-center flex-shrink-0">
                    <span class="text-indigo-400 text-xs font-bold">{{ $event->event_date->format('d') }}</span>
                    <span class="text-indigo-500 text-xs">{{ $event->event_date->format('M') }}</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-white truncate">{{ $event->name }}</p>
                    <p class="text-xs text-slate-500">{{ $event->location ?? 'No location set' }}</p>
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
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                </div>
                <p class="text-xs font-medium text-slate-300 group-hover:text-white leading-tight">Upload Masterlist</p>
            </a>
            <a href="{{ route('admin.qr-codes.index') }}" class="p-3 sm:p-4 rounded-xl bg-slate-800 hover:bg-slate-700 active:scale-[0.98] transition text-center group flex flex-col items-center justify-center min-h-[90px]">
                <div class="mb-1.5 flex items-center justify-center text-slate-400 group-hover:text-white transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <rect x="3" y="3" width="7" height="7" rx="1" stroke-width="1.75"></rect>
                        <rect x="14" y="3" width="7" height="7" rx="1" stroke-width="1.75"></rect>
                        <rect x="3" y="14" width="7" height="7" rx="1" stroke-width="1.75"></rect>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M14 14h3m4 0v3m-3 4h4m-4-3v3"></path>
                    </svg>
                </div>
                <p class="text-xs font-medium text-slate-300 group-hover:text-white leading-tight">QR Management</p>
            </a>
            @endif
            <a href="{{ route('admin.events.create') }}" class="p-3 sm:p-4 rounded-xl bg-slate-800 hover:bg-slate-700 active:scale-[0.98] transition text-center group flex flex-col items-center justify-center min-h-[90px]">
                <div class="mb-1.5 flex items-center justify-center text-slate-400 group-hover:text-white transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2" stroke-width="1.75"></rect>
                        <line x1="16" y1="2" x2="16" y2="6" stroke-width="1.75" stroke-linecap="round"></line>
                        <line x1="8" y1="2" x2="8" y2="6" stroke-width="1.75" stroke-linecap="round"></line>
                        <line x1="3" y1="10" x2="21" y2="10" stroke-width="1.75"></line>
                        <line x1="12" y1="14" x2="12" y2="18" stroke-width="1.75" stroke-linecap="round"></line>
                        <line x1="10" y1="16" x2="14" y2="16" stroke-width="1.75" stroke-linecap="round"></line>
                    </svg>
                </div>
                <p class="text-xs font-medium text-slate-300 group-hover:text-white leading-tight">New Event</p>
            </a>
            <a href="{{ route('admin.attendance.index') }}" class="p-3 sm:p-4 rounded-xl bg-slate-800 hover:bg-slate-700 active:scale-[0.98] transition text-center group flex flex-col items-center justify-center min-h-[90px]">
                <div class="mb-1.5 flex items-center justify-center text-slate-400 group-hover:text-white transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <line x1="18" y1="20" x2="18" y2="10" stroke-width="1.75" stroke-linecap="round"></line>
                        <line x1="12" y1="20" x2="12" y2="4" stroke-width="1.75" stroke-linecap="round"></line>
                        <line x1="6" y1="20" x2="6" y2="14" stroke-width="1.75" stroke-linecap="round"></line>
                    </svg>
                </div>
                <p class="text-xs font-medium text-slate-300 group-hover:text-white leading-tight">Attendance Logs</p>
            </a>
        </div>
    </div>
</div>
</x-layouts.admin>
