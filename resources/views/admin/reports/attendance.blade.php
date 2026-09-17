<x-layouts.admin :title="'Attendance Report'">
<div class="page-header">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.reports.index') }}" class="btn-secondary btn-sm">← Reports</a>
        <div>
            <h1 class="page-title">Attendance Report</h1>
            <p class="text-xs text-slate-400 mt-0.5">Filter attendance records and export raw scan logs.</p>
        </div>
    </div>
    <div class="flex gap-2">
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn-primary text-xs">Export CSV</a>
    </div>
</div>

<div class="card mb-6">
    <form method="GET" class="flex flex-wrap items-end gap-3">
        <div class="w-full sm:w-56">
            <label class="label text-[11px]">Event</label>
            @php
                $eventOpts = ['' => 'All Events'];
                foreach($events as $event) {
                    $eventOpts[$event->id] = $event->name;
                }
            @endphp
            <x-custom-dropdown
                name="event_id"
                :options="$eventOpts"
                :value="request('event_id', '')"
                placeholder="All Events"
                buttonClass="py-1.5 text-xs" />
        </div>
        <div>
            <label class="label text-[11px]">From Date</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="input py-1.5 text-xs" />
        </div>
        <div>
            <label class="label text-[11px]">To Date</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="input py-1.5 text-xs" />
        </div>
        <button type="submit" class="btn-secondary text-xs py-1.5">Filter</button>
    </form>
</div>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="bg-slate-950/60 text-slate-400 border-b border-slate-800">
                <tr>
                    <th class="py-3 px-4">Attendee</th>
                    <th class="py-3 px-4">Event</th>
                    <th class="py-3 px-4">Session</th>
                    <th class="py-3 px-4">Scanned At</th>
                    <th class="py-3 px-4">Punctuality</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
                @forelse($data as $rec)
                    <tr class="hover:bg-slate-800/30 transition">
                        <td class="py-3 px-4 font-medium text-white">
                            {{ $rec->membership?->student?->display_name ?? $rec->eventRegistration?->full_name ?? substr($rec->qr_token, 0, 12) }}
                        </td>
                        <td class="py-3 px-4 text-slate-300">{{ $rec->event->name ?? '—' }}</td>
                        <td class="py-3 px-4 text-slate-300">{{ $rec->attendanceSession->session_name ?? '—' }}</td>
                        <td class="py-3 px-4 text-slate-400">{{ $rec->scanned_at ? $rec->scanned_at->format('M j, Y H:i:s') : '—' }}</td>
                        <td class="py-3 px-4">
                            @if($rec->is_late)
                                <span class="badge bg-amber-500/10 text-amber-400 border-amber-500/20">LATE</span>
                            @else
                                <span class="badge bg-emerald-500/10 text-emerald-400 border-emerald-500/20">ON TIME</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-8 text-center text-slate-500">No attendance records found matching filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</x-layouts.admin>
