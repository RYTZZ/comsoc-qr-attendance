<x-layouts.admin :title="'Attendance Logs'">
<div class="page-header">
    <div>
        <h1 class="page-title">Attendance Records</h1>
        <p class="text-xs text-slate-400 mt-1">Real-time and historic kiosk scan records across all sessions.</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('admin.reports.attendance') }}" class="btn-secondary text-xs">Export Attendance</a>
    </div>
</div>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="bg-slate-950/60 text-slate-400 border-b border-slate-800">
                <tr>
                    <th class="py-3 px-4">Attendee / Student</th>
                    <th class="py-3 px-4">Type</th>
                    <th class="py-3 px-4">Event</th>
                    <th class="py-3 px-4">Session</th>
                    <th class="py-3 px-4">Scanned At</th>
                    <th class="py-3 px-4">Status</th>
                    <th class="py-3 px-4">Kiosk</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
                @forelse($records as $rec)
                    <tr class="hover:bg-slate-800/30 transition">
                        <td class="py-3 px-4">
                            @if($rec->membership?->student)
                                <div class="font-medium text-white">{{ $rec->membership->student->display_name }}</div>
                                <div class="text-[11px] text-slate-500">{{ $rec->membership->student->student_number }}</div>
                            @elseif($rec->eventRegistration)
                                <div class="font-medium text-white">{{ $rec->eventRegistration->full_name }}</div>
                                <div class="text-[11px] text-slate-500">{{ $rec->eventRegistration->email }}</div>
                            @else
                                <span class="text-slate-400 font-mono">{{ substr($rec->qr_token, 0, 12) }}...</span>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            @if($rec->attendee_type === 'student')
                                <span class="badge bg-indigo-500/10 text-indigo-400 border-indigo-500/20">Student</span>
                            @else
                                <span class="badge bg-purple-500/10 text-purple-400 border-purple-500/20">Guest</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-slate-300">{{ $rec->event->name ?? '—' }}</td>
                        <td class="py-3 px-4 text-slate-300">
                            <span class="font-medium text-white">{{ $rec->attendanceSession->session_name ?? 'Session' }}</span>
                            <span class="badge {{ $rec->action === 'in' ? 'bg-indigo-950 text-indigo-300 ring-1 ring-indigo-500/30' : 'bg-blue-950 text-blue-300 ring-1 ring-blue-500/30' }} text-[10px] ml-1 uppercase">
                                {{ strtoupper($rec->action ?? $rec->attendanceSession->session_type ?? 'IN') }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-slate-400 font-mono text-xs">{{ $rec->scanned_at ? $rec->scanned_at->format('M j, Y h:i A') : '—' }}</td>
                        <td class="py-3 px-4">
                            @if($rec->is_late)
                                <span class="badge badge-late inline-flex items-center gap-1">
                                    <svg class="w-3 h-3 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2"></circle><polyline points="12 6 12 12 16 14" stroke-width="2"></polyline></svg>
                                    LATE
                                </span>
                            @else
                                <span class="badge badge-present inline-flex items-center gap-1">
                                    <svg class="w-3 h-3 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    ON TIME
                                </span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-slate-400">{{ $rec->kiosk->name ?? 'Scanner' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-500">No attendance scans recorded yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($records, 'links'))
        <div class="p-4 border-t border-slate-800">
            {{ $records->links() }}
        </div>
    @endif
</div>
</x-layouts.admin>
