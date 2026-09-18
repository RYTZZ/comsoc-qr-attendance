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

<!-- Real-Time Membership Headcount & Live Metric Gauge -->
<div class="card p-5 mb-6 bg-gradient-to-r from-[#171a23] via-[#1a1e2b] to-[#171a23] border-slate-800">
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-3">
        <div>
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                <h2 class="text-sm font-bold text-white uppercase tracking-wider font-brand-display">Society Activation & Headcount Ratio</h2>
            </div>
            <p class="text-xs text-slate-400 mt-0.5">Ratio of officially enrolled IT students with activated academic year membership passes.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-xl font-bold font-mono text-white">{{ $stats['membership_rate'] }}%</span>
            <span class="text-xs text-slate-400">Activated</span>
        </div>
    </div>
    <div class="w-full bg-slate-900 rounded-full h-3.5 p-0.5 border border-slate-800 overflow-hidden">
        <div class="bg-gradient-to-r from-[#7A1618] via-[#a82528] to-emerald-500 h-full rounded-full transition-all duration-700"
             style="width: {{ min(100, max(2, $stats['membership_rate'])) }}%"></div>
    </div>
    <div class="flex items-center justify-between text-[11px] text-slate-400 mt-2">
        <span>{{ number_format($stats['active_members']) }} Active Members</span>
        <span>{{ number_format(max(0, $stats['total_students'] - $stats['active_members'])) }} Pending / Non-Activated</span>
        <span>{{ number_format($stats['total_students']) }} Total Students</span>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- Upcoming Events with Progress Metric -->
    <div class="card lg:col-span-2">
        <div class="flex items-center justify-between mb-4">
            <h2 class="section-title mb-0">Upcoming Events</h2>
            <a href="{{ route('admin.events.index') }}" class="text-xs text-brand-400 hover:text-brand-300">View all</a>
        </div>
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
            <a href="{{ route('admin.events.show', $event) }}" class="flex items-center gap-3 p-3.5 rounded-xl bg-slate-900/60 border border-slate-800/80 hover:border-slate-700 transition-colors">
                <div class="w-11 h-11 rounded-xl bg-[#7A1618]/20 border border-[#7A1618]/30 flex flex-col items-center justify-center flex-shrink-0">
                    <span class="text-[#dfa6a9] text-xs font-bold">{{ $event->event_date ? $event->event_date->format('d') : '--' }}</span>
                    <span class="text-[#dfa6a9] text-[9px] uppercase font-mono">{{ $event->event_date ? $event->event_date->format('M') : 'TBA' }}</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-white truncate">{{ $event->name }}</p>
                    <div class="flex items-center gap-2 text-xs text-slate-400 mt-0.5">
                        <span>{{ $event->location ?? 'Bulan Campus' }}</span>
                        <span>•</span>
                        <span class="font-mono text-emerald-400">{{ $event->attendance_records_count }} checks</span>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="badge {{ $event->is_published ? 'badge-active' : 'badge-inactive' }}">
                        {{ $event->is_published ? 'Published' : 'Draft' }}
                    </span>
                </div>
            </a>
            @endforeach
        </div>
        @endif
    </div>

    <!-- Academic Program Breakdown -->
    <div class="card space-y-4">
        <h2 class="section-title mb-1">Student Enrollment</h2>
        <p class="text-xs text-slate-400">Distribution across curriculum departments.</p>

        <div class="space-y-3 pt-1">
            @forelse($stats['program_breakdown'] as $prog)
            <div>
                <div class="flex items-center justify-between text-xs mb-1">
                    <span class="font-semibold text-white">{{ $prog->prog }}</span>
                    <span class="text-slate-400 font-mono">{{ $prog->count }} ({{ $stats['total_students'] > 0 ? round(($prog->count / $stats['total_students']) * 100) : 0 }}%)</span>
                </div>
                <div class="w-full bg-slate-900 rounded-full h-2 overflow-hidden border border-slate-800">
                    <div class="bg-indigo-500 h-full rounded-full"
                         style="width: {{ $stats['total_students'] > 0 ? min(100, round(($prog->count / $stats['total_students']) * 100)) : 0 }}%"></div>
                </div>
            </div>
            @empty
            <p class="text-xs text-slate-500 py-4 text-center">No program data available.</p>
            @endforelse
        </div>

        <div class="pt-3 border-t border-slate-800 flex flex-wrap gap-2">
            @foreach($stats['year_level_breakdown'] as $yl)
            <div class="px-2.5 py-1 rounded-lg bg-slate-900 border border-slate-800 text-[11px] flex items-center gap-1.5">
                <span class="text-slate-400">{{ $yl->year_level }}:</span>
                <span class="font-bold text-white font-mono">{{ $yl->count }}</span>
            </div>
            @endforeach
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Live Recent Scan Feed -->
    <div class="card lg:col-span-2">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800 mb-3">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <h2 class="section-title mb-0">Recent Attendance Activity</h2>
            </div>
            <a href="{{ route('admin.attendance.index') }}" class="text-xs text-brand-400 hover:text-brand-300">View full logs</a>
        </div>

        @if($stats['recent_scans']->isEmpty())
        <div class="empty-state py-8">
            <p class="empty-state-title">No attendance recorded yet</p>
            <p class="empty-state-body">Scans from terminals and kiosks will appear here live.</p>
        </div>
        @else
        <div class="space-y-2.5">
            @foreach($stats['recent_scans'] as $scan)
            <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800 flex items-center justify-between gap-3 text-xs">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-8 h-8 rounded-lg bg-slate-800 flex items-center justify-center text-slate-400 shrink-0">
                        <i data-lucide="scan-line" class="w-4 h-4 text-indigo-400"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="font-semibold text-white truncate">{{ $scan->student?->full_name ?? ($scan->participant_type === 'student' ? 'Student' : 'Event Guest') }}</p>
                        <p class="text-[11px] text-slate-400 truncate">{{ $scan->event?->name }} • {{ $scan->attendanceSession?->label ?? 'Session' }}</p>
                    </div>
                </div>
                <div class="text-right shrink-0">
                    <span class="badge {{ $scan->status === 'late' ? 'badge-late' : 'badge-present' }} text-[10px] px-2 py-0.5">
                        {{ strtoupper($scan->status ?? 'PRESENT') }}
                    </span>
                    <p class="text-[10px] font-mono text-slate-500 mt-0.5">{{ $scan->scanned_at ? $scan->scanned_at->diffForHumans() : 'Just now' }}</p>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    <!-- Quick Actions -->
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
