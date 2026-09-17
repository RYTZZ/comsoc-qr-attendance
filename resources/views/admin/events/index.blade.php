<x-layouts.admin :title="'Events Management'">
<div class="page-header">
    <div>
        <h1 class="page-title">Events Management</h1>
        <p class="text-xs text-slate-400 mt-1">Configure society events, attendance schedules, participant capacities, and lifecycle states.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        @if(auth()->user()->isSuperAdmin())
        <a href="{{ route('admin.events.create') }}" class="btn-primary">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Create Event
        </a>
        @endif
    </div>
</div>

<div class="card mb-5 p-4">
    <form method="GET" action="{{ route('admin.events.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 items-end">
        <div>
            <label class="label text-[11px] mb-1">Search Events</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by event name…" class="input py-2 text-xs">
        </div>

        @php
            $yearOpts = [['value' => '', 'label' => 'All Academic Years']];
            foreach($academicYears as $y) {
                $yearOpts[] = ['value' => (string)$y->id, 'label' => $y->label];
            }
            $statusOpts = [
                ['value' => '', 'label' => 'All Statuses'],
                ['value' => 'draft', 'label' => 'Draft'],
                ['value' => 'registration_open', 'label' => 'Registration Open'],
                ['value' => 'registration_closed', 'label' => 'Registration Closed'],
                ['value' => 'ongoing', 'label' => 'Ongoing'],
                ['value' => 'completed', 'label' => 'Completed'],
                ['value' => 'archived', 'label' => 'Archived'],
            ];
        @endphp

        <div>
            <label class="label text-[11px] mb-1">Academic Year</label>
            <x-custom-dropdown name="academic_year_id" :options="$yearOpts" :value="request('academic_year_id')" placeholder="All Academic Years" />
        </div>

        <div>
            <label class="label text-[11px] mb-1">Event Status</label>
            <x-custom-dropdown name="status" :options="$statusOpts" :value="request('status')" placeholder="All Statuses" />
        </div>

        <div class="flex gap-2">
            <button type="submit" class="btn-secondary text-xs flex-1 py-2">Filter</button>
            @if(request()->hasAny(['search', 'academic_year_id', 'status']))
                <a href="{{ route('admin.events.index') }}" class="btn-outline text-xs py-2 px-3">Reset</a>
            @endif
        </div>
    </form>
</div>

<div class="card overflow-hidden p-0 border border-slate-800">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="bg-[#12141c] text-slate-400 border-b border-slate-800 uppercase tracking-wider font-semibold">
                <tr>
                    <th class="py-3 px-4">Event Name</th>
                    <th class="py-3 px-4">Date & Day</th>
                    <th class="py-3 px-4">Time</th>
                    <th class="py-3 px-4">Venue</th>
                    <th class="py-3 px-4">Registration</th>
                    <th class="py-3 px-4">Participants</th>
                    <th class="py-3 px-4">Attendance</th>
                    <th class="py-3 px-4">Event Status</th>
                    <th class="py-3 px-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60 bg-[#171a23]">
                @forelse($events as $event)
                <tr class="hover:bg-slate-800/40 transition">
                    <td class="py-3.5 px-4 font-semibold text-white">
                        <div class="flex items-center gap-2.5">
                            @if($event->logo_path)
                                <img src="{{ asset('storage/' . $event->logo_path) }}" alt="{{ $event->name }}" class="w-8 h-8 rounded-lg object-contain bg-slate-900 border border-slate-800 p-0.5 shrink-0">
                            @else
                                <div class="w-8 h-8 rounded-lg bg-[#12141c] border border-slate-800 flex items-center justify-center text-xs font-bold text-slate-400 shrink-0">
                                    {{ substr($event->name, 0, 2) }}
                                </div>
                            @endif
                            <div>
                                <a href="{{ route('admin.events.show', $event) }}" class="hover:text-red-300 transition">
                                    {{ $event->name }}
                                </a>
                                @if($event->event_type)
                                    <span class="block text-[10px] text-slate-400 font-normal uppercase tracking-wider">{{ $event->event_type }}</span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="py-3.5 px-4 text-slate-200">
                        <div class="font-medium">{{ $event->event_date ? $event->event_date->format('M d, Y') : 'TBA' }}</div>
                        <div class="text-[11px] text-slate-400">{{ $event->event_day ?? '—' }}</div>
                    </td>
                    <td class="py-3.5 px-4 text-slate-300 font-mono text-[11px]">
                        @if($event->starts_at)
                            {{ $event->starts_at->format('g:i A') }}
                            @if($event->ends_at)
                                <span class="text-slate-500">—</span> {{ $event->ends_at->format('g:i A') }}
                            @endif
                        @else
                            <span class="text-slate-500">—</span>
                        @endif
                    </td>
                    <td class="py-3.5 px-4 text-slate-300 max-w-[150px] truncate">
                        <span title="{{ $event->effective_venue }}">{{ $event->effective_venue }}</span>
                    </td>
                    <td class="py-3.5 px-4">
                        @if(!$event->requires_registration)
                            <span class="text-slate-500 text-[11px]">Not Required</span>
                        @elseif($event->registration_status === 'Registration Open')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Open</span>
                        @elseif($event->registration_status === 'Closing Soon')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-500/10 text-amber-400 border border-amber-500/20">Closing Soon</span>
                        @elseif($event->registration_status === 'Registration Full')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-red-500/10 text-red-400 border border-red-500/20">Full</span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-500/10 text-slate-400 border border-slate-500/20">Closed</span>
                        @endif
                    </td>
                    <td class="py-3.5 px-4 font-mono text-xs">
                        <span class="text-white font-semibold">{{ $event->event_registrations_count }}</span>
                        @if($event->max_participants)
                            <span class="text-slate-500">/ {{ $event->max_participants }}</span>
                        @else
                            <span class="text-slate-500 text-[11px]">/ ∞</span>
                        @endif
                    </td>
                    <td class="py-3.5 px-4">
                        @if($event->attendance_enabled)
                            <span class="inline-flex items-center gap-1 text-[11px] text-emerald-400 font-medium">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                Enabled
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 text-[11px] text-slate-500">
                                Disabled
                            </span>
                        @endif
                    </td>
                    <td class="py-3.5 px-4">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border {{ $event->status_badge_class }}">
                            {{ $event->status_label }}
                        </span>
                    </td>
                    <td class="py-3.5 px-4 text-right">
                        <div class="inline-flex items-center gap-1">
                            <a href="{{ route('admin.events.show', $event) }}" class="btn-secondary btn-sm py-1 px-2 text-[11px]">View</a>
                            @if(auth()->user()->isSuperAdmin())
                                <a href="{{ route('admin.events.configure', $event) }}" class="btn-secondary btn-sm py-1 px-2 text-[11px] hover:border-[#7A1618]">Configure</a>

                                <div x-data="{ open: false }" class="relative inline-block text-left" @click.away="open = false">
                                    <button type="button" @click="open = !open" class="btn-secondary btn-sm py-1 px-1.5 text-xs text-slate-400 hover:text-white">
                                        •••
                                    </button>
                                    <div x-show="open" x-cloak class="absolute right-0 z-50 mt-1 w-44 rounded-xl bg-[#1e222d] border border-slate-700 shadow-2xl py-1 text-xs text-left">
                                        @if(!$event->is_published || $event->status === 'draft')
                                            <form method="POST" action="{{ route('admin.events.publish', $event) }}" onsubmit="return confirm('Publish this event and open registration?');">
                                                @csrf
                                                <button type="submit" class="w-full text-left px-3 py-2 text-emerald-400 hover:bg-emerald-500/10">Publish Event</button>
                                            </form>
                                        @endif

                                        @if($event->status === 'registration_open')
                                            <form method="POST" action="{{ route('admin.events.close-registration', $event) }}" onsubmit="return confirm('Close registration for this event immediately?');">
                                                @csrf
                                                <button type="submit" class="w-full text-left px-3 py-2 text-amber-400 hover:bg-amber-500/10">Close Registration</button>
                                            </form>
                                        @endif

                                        @if($event->status !== 'completed' && $event->status !== 'archived')
                                            <form method="POST" action="{{ route('admin.events.complete', $event) }}" onsubmit="return confirm('Mark this event as completed?');">
                                                @csrf
                                                <button type="submit" class="w-full text-left px-3 py-2 text-indigo-400 hover:bg-indigo-500/10">Complete Event</button>
                                            </form>
                                        @endif

                                        @if($event->status !== 'archived')
                                            <form method="POST" action="{{ route('admin.events.archive', $event) }}" onsubmit="return confirm('Archive this event? It will be removed from public view.');">
                                                @csrf
                                                <button type="submit" class="w-full text-left px-3 py-2 text-slate-400 hover:bg-slate-700/30">Archive Event</button>
                                            </form>
                                        @endif

                                        <div class="border-t border-slate-700/60 my-1"></div>
                                        <form method="POST" action="{{ route('admin.events.destroy', $event) }}" onsubmit="return confirm('Permanently delete this event? This action cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="w-full text-left px-3 py-2 text-red-400 hover:bg-red-500/10">Delete Event</button>
                                        </form>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="py-12 text-center text-slate-500">
                        <div class="empty-state">
                            <div class="empty-state-icon flex items-center justify-center">
                                <svg class="w-8 h-8 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2" stroke-width="1.5"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6" stroke-width="1.5" stroke-linecap="round"></line>
                                    <line x1="8" y1="2" x2="8" y2="6" stroke-width="1.5" stroke-linecap="round"></line>
                                    <line x1="3" y1="10" x2="21" y2="10" stroke-width="1.5"></line>
                                </svg>
                            </div>
                            <p class="empty-state-title">No events found</p>
                            <p class="empty-state-body">Create a new event or adjust your filter criteria.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">{{ $events->links() }}</div>
</x-layouts.admin>

