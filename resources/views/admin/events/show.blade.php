<x-layouts.admin :title="$event->name">
<div class="page-header">
    <div class="flex items-center gap-3 min-w-0">
        <a href="{{ route('admin.events.index') }}" class="btn-secondary btn-sm shrink-0">← Events</a>
        <div class="min-w-0">
            <h1 class="page-title truncate">{{ $event->name }}</h1>
            <p class="text-xs sm:text-sm text-slate-400 truncate">{{ optional($event->event_date)->format('F d, Y') }} @if($event->location)— {{ $event->location }}@endif</p>
        </div>
    </div>
    <div class="flex flex-wrap gap-2 w-full sm:w-auto">
        <a href="{{ route('admin.event-registrations.index', $event) }}" class="btn-secondary flex-1 sm:flex-initial text-center">Registrations</a>
        <a href="{{ route('admin.events.edit', $event) }}" class="btn-primary flex-1 sm:flex-initial text-center">Edit Event</a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="card">
            <h2 class="section-title">Attendance Sessions</h2>
            <form method="POST" action="{{ route('admin.events.sessions.store', $event) }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                @csrf
                <div>
                    <label class="label">Type *</label>
                    <select name="type" class="select" required>
                        <option value="morning_in">Morning IN</option>
                        <option value="morning_out">Morning OUT</option>
                        <option value="afternoon_in">Afternoon IN</option>
                        <option value="afternoon_out">Afternoon OUT</option>
                    </select>
                </div>
                <div>
                    <label class="label">Opens At *</label>
                    <input type="time" name="opens_at" class="input" required>
                </div>
                <div>
                    <label class="label">Closes At *</label>
                    <input type="time" name="closes_at" class="input" required>
                </div>
                <div>
                    <label class="label">Late After</label>
                    <input type="time" name="late_threshold" class="input">
                </div>
                <div class="col-span-1 sm:col-span-2 md:col-span-4 flex justify-end">
                    <button type="submit" class="btn-secondary btn-sm w-full sm:w-auto">Add Session</button>
                </div>
            </form>

            @if($event->attendanceSessions->isEmpty())
            <div class="empty-state py-4">
                <p class="empty-state-title text-sm">No sessions configured</p>
            </div>
            @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr><th>Type</th><th>Opens</th><th>Closes</th><th>Late After</th></tr>
                    </thead>
                    <tbody>
                        @foreach($event->attendanceSessions as $session)
                        <tr>
                            <td class="font-medium text-white">{{ $session->label }}</td>
                            <td>{{ $session->opens_at }}</td>
                            <td>{{ $session->closes_at }}</td>
                            <td>{{ $session->late_threshold ?? '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

        <div class="card">
            <h2 class="section-title">Snack Sessions</h2>
            <form method="POST" action="{{ route('admin.events.snack-sessions.store', $event) }}" class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-4">
                @csrf
                <div>
                    <label class="label">Session Name *</label>
                    <input type="text" name="name" class="input" placeholder="e.g. Morning Snack" required>
                </div>
                <div>
                    <label class="label">Type</label>
                    <select name="type" class="select">
                        <option value="morning">Morning</option>
                        <option value="afternoon">Afternoon</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="label">Item Name</label>
                    <input type="text" name="item_name" class="input" placeholder="e.g. Sandwich">
                </div>
                <div>
                    <label class="label">Total Quantity</label>
                    <input type="number" name="total_quantity" class="input" min="0" placeholder="0">
                </div>
                <div class="col-span-2 sm:col-span-3 flex justify-end">
                    <button type="submit" class="btn-secondary btn-sm">Add Snack Session</button>
                </div>
            </form>

            @if($event->snackSessions->isEmpty())
            <div class="empty-state py-4">
                <p class="empty-state-title text-sm">No snack sessions</p>
            </div>
            @else
            <div class="space-y-3">
                @foreach($event->snackSessions as $snack)
                <div class="p-3 rounded-lg bg-slate-800/50">
                    <p class="text-sm font-medium text-white">{{ $snack->name }}</p>
                    @foreach($snack->inventories as $inv)
                    <p class="text-xs text-slate-500">{{ $inv->item_name }} — {{ $inv->remaining_quantity }}/{{ $inv->total_quantity }} remaining</p>
                    @endforeach
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    <div class="space-y-4">
        <div class="card">
            <h2 class="section-title">Event Info</h2>
            <dl class="space-y-3 text-sm">
                <div><dt class="label">Status</dt><dd><span class="badge {{ $event->is_published ? 'badge-active' : 'badge-inactive' }}">{{ $event->is_published ? 'Published' : 'Draft' }}</span></dd></div>
                <div><dt class="label">Academic Year</dt><dd class="text-white">{{ $event->academicYear->label }}</dd></div>
                <div><dt class="label">Start</dt><dd class="text-slate-300">{{ $event->starts_at?->format('M d, Y g:i A') ?? '—' }}</dd></div>
                <div><dt class="label">End</dt><dd class="text-slate-300">{{ $event->ends_at?->format('M d, Y g:i A') ?? '—' }}</dd></div>
                <div><dt class="label">Registration</dt><dd class="text-slate-300">{{ $event->requires_registration ? 'Required' : 'Not required' }}</dd></div>
                <div><dt class="label">Non-Student</dt><dd class="text-slate-300">{{ $event->allow_non_students ? 'Allowed' : 'Not allowed' }}</dd></div>
                @if($event->registration_deadline)
                <div><dt class="label">Reg. Deadline</dt><dd class="text-slate-300">{{ $event->registration_deadline->format('M d, Y g:i A') }}</dd></div>
                @endif
            </dl>
        </div>

        <div class="card">
            <h2 class="section-title">Registrations</h2>
            @php $regCounts = $event->eventRegistrations->groupBy('status'); @endphp
            <div class="space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-slate-400">Pending</span><span class="text-amber-400 font-medium">{{ $regCounts->get('pending', collect())->count() }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Approved</span><span class="text-emerald-400 font-medium">{{ $regCounts->get('approved', collect())->count() }}</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Rejected</span><span class="text-red-400 font-medium">{{ $regCounts->get('rejected', collect())->count() }}</span></div>
            </div>
            <a href="{{ route('admin.event-registrations.index', $event) }}" class="btn-secondary btn-sm mt-4">Manage Registrations</a>
        </div>

        @if(auth()->user()->isSuperAdmin())
        <div class="card border-red-900/30">
            <h2 class="section-title text-red-400">Danger Zone</h2>
            <form method="POST" action="{{ route('admin.events.destroy', $event) }}"
                  onsubmit="return confirm('Delete this event? This cannot be undone.')">
                @csrf @method('DELETE')
                <button type="submit" class="btn-danger btn-sm">Delete Event</button>
            </form>
        </div>
        @endif
    </div>
</div>
</x-layouts.admin>
