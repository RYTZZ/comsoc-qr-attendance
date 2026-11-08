<x-layouts.admin :title="'Activity & Multi-Session Management'">
<div class="page-header mb-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="px-2 py-0.5 rounded bg-[#560E0F]/40 border border-[#560E0F] text-[#dfa6a9] text-[10px] font-bold uppercase tracking-wider">
                    Activity Management
                </span>
                <span class="text-xs text-slate-400">Multi-Session Events, Workshops, IDEATHON & Hackathons</span>
            </div>
            <h1 class="page-title font-heading text-2xl font-bold text-white">Dynamic Activity & Multi-Session Dashboard</h1>
        </div>

        <div class="flex items-center gap-2">
            @if(auth()->user()->isSuperAdmin())
                <a href="{{ route('admin.registration-forms.index') }}" class="btn-secondary text-xs flex items-center gap-1.5">
                    <i data-lucide="file-sliders" class="w-4 h-4 text-amber-400"></i>
                    <span>Form Builder</span>
                </a>
            @endif
            <a href="{{ route('admin.activities.create') }}" class="btn-primary text-xs flex items-center gap-1.5">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>Create Parent Activity</span>
            </a>
        </div>
    </div>
</div>

<!-- Super Admin Real-Time Analytics Overview (12 KPIs) -->
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
    <div class="card bg-[#172033] border border-slate-800 p-3.5">
        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Parent Events</span>
        <span class="text-xl font-extrabold text-white mt-1 block">{{ number_format($stats['total_parent_events']) }}</span>
        <span class="text-[11px] text-emerald-400">{{ $stats['active_events'] }} active</span>
    </div>

    <div class="card bg-[#172033] border border-slate-800 p-3.5">
        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Total Sessions</span>
        <span class="text-xl font-extrabold text-white mt-1 block">{{ number_format($stats['total_sessions']) }}</span>
        <span class="text-[11px] text-slate-400">{{ $stats['published_forms'] }} published forms</span>
    </div>

    <div class="card bg-[#172033] border border-slate-800 p-3.5">
        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Registrations</span>
        <span class="text-xl font-extrabold text-white mt-1 block">{{ number_format($stats['total_registrations']) }}</span>
        <span class="text-[11px] text-emerald-400">{{ $stats['approved_registrations'] }} approved</span>
    </div>

    <div class="card bg-[#172033] border border-slate-800 p-3.5">
        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Pending & Waitlist</span>
        <span class="text-xl font-extrabold text-amber-300 mt-1 block">{{ number_format($stats['pending_applications']) }}</span>
        <span class="text-[11px] text-slate-400">{{ $stats['waitlisted_participants'] }} waitlisted</span>
    </div>

    <div class="card bg-[#172033] border border-slate-800 p-3.5">
        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Attendance & Completion</span>
        <span class="text-xl font-extrabold text-sky-300 mt-1 block">{{ number_format($stats['total_attendances']) }}</span>
        <span class="text-[11px] text-emerald-400">{{ $stats['completed_sessions'] }} completed</span>
    </div>

    <div class="card bg-[#172033] border border-slate-800 p-3.5">
        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Rewards Issued</span>
        <span class="text-xl font-extrabold text-[#dfa6a9] mt-1 block">{{ number_format($stats['total_exp_distributed']) }} EXP</span>
        <span class="text-[11px] text-amber-300">{{ number_format($stats['total_points_distributed']) }} Points</span>
    </div>
</div>

<!-- Filters -->
<div class="card bg-[#172033] border border-slate-800 p-4 mb-6">
    <form method="GET" action="{{ route('admin.activities.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 items-end">
        <div class="lg:col-span-2">
            <label class="block text-[11px] font-semibold text-slate-400 mb-1">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Title, venue, organizer..."
                   class="input w-full text-xs bg-[#0B1120] border-slate-700 text-white">
        </div>

        <div>
            <label class="block text-[11px] font-semibold text-slate-400 mb-1">Category</label>
            <select name="category" class="select w-full text-xs bg-[#0B1120] border-slate-700 text-white">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat }}" @selected(request('category') === $cat)>{{ $cat }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-[11px] font-semibold text-slate-400 mb-1">Status</label>
            <select name="status" class="select w-full text-xs bg-[#0B1120] border-slate-700 text-white">
                <option value="">All Statuses</option>
                @foreach($statuses as $st)
                    <option value="{{ $st }}" @selected(request('status') === $st)>{{ ucfirst(str_replace('_', ' ', $st)) }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-[11px] font-semibold text-slate-400 mb-1">Date From</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}"
                   class="input w-full text-xs bg-[#0B1120] border-slate-700 text-white">
        </div>

        <div class="flex items-center gap-2">
            <button type="submit" class="btn-primary text-xs py-2 px-4">Filter</button>
            <a href="{{ route('admin.activities.index') }}" class="btn-secondary text-xs py-2 px-3">Reset</a>
        </div>
    </form>
</div>

<!-- Parent Activities Table -->
<div class="card bg-[#172033] border border-slate-800 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="bg-[#0B1120] text-slate-400 border-b border-slate-800 uppercase text-[10px] tracking-wider">
                <tr>
                    <th class="py-3.5 px-4">Parent Activity</th>
                    <th class="py-3.5 px-4">Category & Mode</th>
                    <th class="py-3.5 px-4">Schedule & Venue</th>
                    <th class="py-3.5 px-4">Sessions</th>
                    <th class="py-3.5 px-4">Registrations / Capacity</th>
                    <th class="py-3.5 px-4">Status</th>
                    <th class="py-3.5 px-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/70">
                @forelse($activities as $act)
                    <tr class="hover:bg-[#1E293B]/60 transition">
                        <td class="py-3.5 px-4">
                            <a href="{{ route('admin.activities.show', $act) }}" class="font-bold text-white hover:text-[#dfa6a9] text-sm">
                                {{ $act->title }}
                            </a>
                            @if($act->registrationForm)
                                <div class="text-[11px] text-amber-300 mt-0.5">
                                    Form: {{ $act->registrationForm->title }} (v{{ $act->form_version ?? $act->registrationForm->version }})
                                </div>
                            @endif
                        </td>

                        <td class="py-3.5 px-4">
                            <span class="px-2 py-0.5 rounded bg-slate-800 border border-slate-700 text-[11px] text-slate-200">
                                {{ $act->category }}
                            </span>
                            <div class="text-[11px] text-slate-400 mt-1 capitalize">
                                {{ $act->registration_mode }} Registration · {{ $act->is_multi_session ? 'Multi-Session' : 'Single-Session' }}
                            </div>
                        </td>

                        <td class="py-3.5 px-4 text-slate-300">
                            <div>{{ $act->starts_at ? $act->starts_at->format('M d, Y g:i A') : 'TBA' }}</div>
                            <div class="text-[11px] text-slate-400">{{ $act->venue ?: 'TBA' }}</div>
                        </td>

                        <td class="py-3.5 px-4 font-semibold text-white">
                            {{ $act->sessions_count }} session(s)
                        </td>

                        <td class="py-3.5 px-4">
                            <span class="font-bold text-white">{{ $act->registrations_count }}</span>
                            @if($act->overall_capacity)
                                <span class="text-slate-400">/ {{ $act->overall_capacity }}</span>
                            @else
                                <span class="text-slate-500 text-[11px]">(Unlimited)</span>
                            @endif
                        </td>

                        <td class="py-3.5 px-4">
                            <span class="px-2.5 py-1 rounded-full bg-slate-800 border border-slate-700 text-slate-200 text-[10px] font-bold uppercase">
                                {{ str_replace('_', ' ', $act->status) }}
                            </span>
                        </td>

                        <td class="py-3.5 px-4 text-right whitespace-nowrap space-x-1">
                            <a href="{{ route('admin.activities.show', $act) }}" class="btn-primary btn-sm text-xs">
                                Manage Sessions & Participants
                            </a>
                            <a href="{{ route('admin.activities.edit', $act) }}" class="btn-secondary btn-sm text-xs">
                                Edit
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-10 text-center text-slate-400">
                            No activities found. Click <strong>Create Parent Activity</strong> to configure a single-session or multi-session activity.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4 border-t border-slate-800">
        {{ $activities->links() }}
    </div>
</div>
</x-layouts.admin>

