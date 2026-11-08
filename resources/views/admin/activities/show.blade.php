<x-layouts.admin :title="$activity->title . ' — Sessions & Registrations'">
<div class="page-header mb-6" x-data="{ showAddSession: false }">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <a href="{{ route('admin.activities.index') }}" class="text-xs text-slate-400 hover:text-white inline-flex items-center gap-1 mb-1">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                Back to Activities
            </a>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="page-title font-heading text-2xl font-bold text-white">{{ $activity->title }}</h1>
                <span class="px-2.5 py-0.5 rounded-full bg-[#560E0F]/40 border border-[#560E0F] text-[#dfa6a9] text-[10px] font-bold uppercase">
                    {{ $activity->category }}
                </span>
                <span class="px-2.5 py-0.5 rounded-full bg-slate-800 border border-slate-700 text-slate-300 text-[10px] font-bold uppercase">
                    {{ str_replace('_', ' ', $activity->status) }}
                </span>
            </div>
            <p class="text-xs text-slate-400 mt-1">
                Venue: {{ $activity->venue ?: 'TBA' }} · Mode: <strong class="text-slate-200 capitalize">{{ $activity->registration_mode }}</strong>
                · Conflict Policy: <strong class="text-slate-200 uppercase">{{ $activity->conflict_policy }}</strong>
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('organizer.activity-scanner', ['activity_id' => $activity->id]) }}" class="btn-secondary text-xs flex items-center gap-1.5 text-emerald-300 border-emerald-500/30">
                <i data-lucide="qr-code" class="w-4 h-4"></i>
                <span>Launch Session QR Scanner</span>
            </a>
            <a href="{{ route('admin.activities.export-csv', $activity) }}" class="btn-secondary text-xs flex items-center gap-1.5">
                <i data-lucide="download" class="w-4 h-4"></i>
                <span>Export CSV</span>
            </a>
            <a href="{{ route('admin.activities.edit', $activity) }}" class="btn-secondary text-xs">
                Edit Parent Settings
            </a>
            <button type="button" @click="showAddSession = !showAddSession" class="btn-primary text-xs flex items-center gap-1.5">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>Add Session</span>
            </button>
        </div>
    </div>

    <!-- Collapsible Add Session Form -->
    <div x-show="showAddSession" x-cloak class="card bg-[#172033] border border-slate-700 p-5 mt-4 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-800 pb-2">
            <h3 class="text-sm font-bold text-white uppercase tracking-wider">Add New Session / Activity Component</h3>
            <button type="button" @click="showAddSession = false" class="text-xs text-slate-400 hover:text-white">Close ✕</button>
        </div>

        <form method="POST" action="{{ route('admin.activities.sessions.store', $activity) }}" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <div class="md:col-span-2">
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Session Name *</label>
                    <input type="text" name="name" required placeholder="e.g., Session 1: Web Development"
                           class="input w-full text-xs bg-[#0B1120] border-slate-700 text-white">
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Track / Topic Category</label>
                    <input type="text" name="track_or_category" placeholder="e.g., Web Development"
                           class="input w-full text-xs bg-[#0B1120] border-slate-700 text-white">
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Venue / Laboratory</label>
                    <input type="text" name="venue" value="{{ $activity->venue }}" placeholder="e.g., Computer Lab 1"
                           class="input w-full text-xs bg-[#0B1120] border-slate-700 text-white">
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Starts At</label>
                    <input type="datetime-local" name="starts_at"
                           class="input w-full text-xs bg-[#0B1120] border-slate-700 text-white">
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Ends At</label>
                    <input type="datetime-local" name="ends_at"
                           class="input w-full text-xs bg-[#0B1120] border-slate-700 text-white">
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Session Capacity (Slots)</label>
                    <input type="number" name="capacity" min="1" placeholder="e.g., 30"
                           class="input w-full text-xs bg-[#0B1120] border-slate-700 text-white">
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">EXP Reward *</label>
                    <input type="number" name="exp_reward" value="100" min="0" required
                           class="input w-full text-xs bg-[#0B1120] border-slate-700 text-white">
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Points Reward *</label>
                    <input type="number" name="points_reward" value="10" min="0" required
                           class="input w-full text-xs bg-[#0B1120] border-slate-700 text-white">
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Min Attendance Duration (Mins)</label>
                    <input type="number" name="min_attendance_minutes" min="0" placeholder="Optional"
                           class="input w-full text-xs bg-[#0B1120] border-slate-700 text-white">
                </div>

                @if(auth()->user()->isSuperAdmin())
                    <div class="md:col-span-2">
                        <label class="block text-[11px] font-semibold text-slate-300 mb-1">Session-Specific Registration Form (Super Admin)</label>
                        <select name="registration_form_id" class="select w-full text-xs bg-[#0B1120] border-slate-700 text-white">
                            <option value="">— No Separate Session Form —</option>
                            @foreach($publishedForms as $pf)
                                <option value="{{ $pf->id }}">{{ $pf->title }} (v{{ $pf->version }})</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>

            <div class="flex flex-wrap gap-4 pt-2">
                <label class="inline-flex items-center gap-1.5 text-xs text-slate-300">
                    <input type="checkbox" name="is_registration_open" value="1" checked class="rounded bg-slate-900 border-slate-700 text-[#560E0F]">
                    <span>Registration Open</span>
                </label>
                <label class="inline-flex items-center gap-1.5 text-xs text-slate-300">
                    <input type="checkbox" name="waitlist_enabled" value="1" checked class="rounded bg-slate-900 border-slate-700 text-[#560E0F]">
                    <span>Waitlist Enabled</span>
                </label>
                <label class="inline-flex items-center gap-1.5 text-xs text-slate-300">
                    <input type="checkbox" name="requires_approval" value="1" class="rounded bg-slate-900 border-slate-700 text-[#560E0F]">
                    <span>Requires Separate Session Approval</span>
                </label>
                <label class="inline-flex items-center gap-1.5 text-xs text-slate-300">
                    <input type="checkbox" name="attendance_enabled" value="1" checked class="rounded bg-slate-900 border-slate-700 text-[#560E0F]">
                    <span>Attendance Enabled</span>
                </label>
                <label class="inline-flex items-center gap-1.5 text-xs text-slate-300">
                    <input type="checkbox" name="qr_attendance_enabled" value="1" checked class="rounded bg-slate-900 border-slate-700 text-[#560E0F]">
                    <span>QR Check-In/Out</span>
                </label>
                <label class="inline-flex items-center gap-1.5 text-xs text-slate-300">
                    <input type="checkbox" name="require_check_out" value="1" checked class="rounded bg-slate-900 border-slate-700 text-[#560E0F]">
                    <span>Require Both Check-In & Check-Out</span>
                </label>
                <label class="inline-flex items-center gap-1.5 text-xs text-slate-300">
                    <input type="checkbox" name="require_organizer_verification" value="1" class="rounded bg-slate-900 border-slate-700 text-[#560E0F]">
                    <span>Require Organizer Verification for Completion</span>
                </label>
                <label class="inline-flex items-center gap-1.5 text-xs text-slate-300">
                    <input type="checkbox" name="require_output_submission" value="1" class="rounded bg-slate-900 border-slate-700 text-[#560E0F]">
                    <span>Require Activity Output</span>
                </label>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="btn-primary text-xs py-2 px-5">Create Session</button>
            </div>
        </form>
    </div>
</div>

<!-- Level 2: Sessions Overview Table -->
<div class="card bg-[#172033] border border-slate-800 overflow-hidden mb-6">
    <div class="px-5 py-3.5 border-b border-slate-800 flex items-center justify-between">
        <div>
            <h2 class="text-sm font-bold text-white uppercase tracking-wider">Level 2: Configured Activity Sessions</h2>
            <p class="text-[11px] text-slate-400">Each session has independent capacity, schedule, QR attendance, completion status, and EXP/Points rewards.</p>
        </div>
        <span class="text-xs font-mono text-slate-400">{{ $activity->sessions->count() }} session(s)</span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="bg-[#0B1120] text-slate-400 border-b border-slate-800 uppercase text-[10px]">
                <tr>
                    <th class="py-3 px-4">#</th>
                    <th class="py-3 px-4">Session / Track</th>
                    <th class="py-3 px-4">Schedule & Venue</th>
                    <th class="py-3 px-4">Capacity & Waitlist</th>
                    <th class="py-3 px-4">Attendance (In / Out)</th>
                    <th class="py-3 px-4">Completed</th>
                    <th class="py-3 px-4">Rewards</th>
                    <th class="py-3 px-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/70">
                @forelse($activity->sessions as $idx => $sess)
                    <tr class="hover:bg-[#1E293B]/50">
                        <td class="py-3 px-4 font-mono text-slate-400">{{ $idx + 1 }}</td>
                        <td class="py-3 px-4">
                            <div class="font-bold text-white">{{ $sess->name }}</div>
                            @if($sess->track_or_category)
                                <span class="text-[11px] text-slate-400">{{ $sess->track_or_category }}</span>
                            @endif
                            @if($sess->registrationForm)
                                <div class="text-[10px] text-amber-300">Form: {{ $sess->registrationForm->title }}</div>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-slate-300">
                            <div>
                                {{ $sess->starts_at ? $sess->starts_at->format('M d, g:i A') : 'TBA' }}
                                @if($sess->ends_at) — {{ $sess->ends_at->format('g:i A') }} @endif
                            </div>
                            <div class="text-[11px] text-slate-400">{{ $sess->venue ?: 'Default Venue' }}</div>
                        </td>
                        <td class="py-3 px-4">
                            <span class="font-bold text-white">{{ $sess->approved_registrations_count }}</span>
                            @if($sess->capacity)
                                <span class="text-slate-400">/ {{ $sess->capacity }}</span>
                                <span class="text-[11px] text-emerald-400 ml-1">({{ $sess->remainingSlots() }} left)</span>
                            @else
                                <span class="text-slate-500 text-[11px]">(Unlimited)</span>
                            @endif
                            @if($sess->waitlisted_registrations_count > 0)
                                <div class="text-[10px] text-amber-300">{{ $sess->waitlisted_registrations_count }} waitlisted</div>
                            @endif
                        </td>
                        <td class="py-3 px-4 font-mono text-slate-300">
                            In: {{ $sess->checked_in_count }} · Out: {{ $sess->checked_out_count }}
                        </td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300 text-[11px] font-bold">
                                {{ $sess->completed_registrations_count }}
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            <span class="font-bold text-[#dfa6a9]">{{ $sess->exp_reward }} EXP</span>
                            <span class="text-amber-300 ml-1">· {{ $sess->points_reward }} Pts</span>
                        </td>
                        <td class="py-3 px-4 text-right whitespace-nowrap">
                            <a href="{{ route('organizer.activity-scanner', ['activity_id' => $activity->id, 'session_id' => $sess->id]) }}"
                               class="btn-secondary btn-sm text-xs text-emerald-300">
                                Scan QR
                            </a>
                            <details class="inline-block text-left ml-1">
                                <summary class="btn-secondary btn-sm text-xs cursor-pointer list-none inline-block">Edit</summary>
                                <form method="POST" action="{{ route('admin.activities.sessions.update', [$activity, $sess]) }}"
                                      class="mt-2 p-3 rounded-xl bg-[#0B1120] border border-slate-700 space-y-2 w-72 shadow-xl">
                                    @csrf
                                    @method('PUT')
                                    <input type="text" name="name" value="{{ $sess->name }}" required
                                           class="input w-full text-xs py-1 px-2 bg-[#172033] border-slate-700 text-white" placeholder="Session Name">
                                    <div class="grid grid-cols-2 gap-1.5">
                                        <input type="text" name="track_or_category" value="{{ $sess->track_or_category }}"
                                               class="input w-full text-xs py-1 px-2 bg-[#172033] border-slate-700 text-white" placeholder="Track">
                                        <input type="text" name="venue" value="{{ $sess->venue }}"
                                               class="input w-full text-xs py-1 px-2 bg-[#172033] border-slate-700 text-white" placeholder="Venue">
                                        <input type="number" name="capacity" value="{{ $sess->capacity }}" min="1"
                                               class="input w-full text-xs py-1 px-2 bg-[#172033] border-slate-700 text-white" placeholder="Capacity">
                                        <select name="status" class="select w-full text-xs py-1 px-2 bg-[#172033] border-slate-700 text-white">
                                            @foreach(\App\Models\ActivitySession::STATUSES as $st)
                                                <option value="{{ $st }}" @selected($sess->status === $st)>{{ ucfirst($st) }}</option>
                                            @endforeach
                                        </select>
                                        <input type="number" name="exp_reward" value="{{ $sess->exp_reward }}" min="0"
                                               class="input w-full text-xs py-1 px-2 bg-[#172033] border-slate-700 text-white" placeholder="EXP">
                                        <input type="number" name="points_reward" value="{{ $sess->points_reward }}" min="0"
                                               class="input w-full text-xs py-1 px-2 bg-[#172033] border-slate-700 text-white" placeholder="Points">
                                    </div>
                                    <input type="hidden" name="is_registration_open" value="{{ $sess->is_registration_open ? 1 : 0 }}">
                                    <input type="hidden" name="waitlist_enabled" value="{{ $sess->waitlist_enabled ? 1 : 0 }}">
                                    <input type="hidden" name="attendance_enabled" value="{{ $sess->attendance_enabled ? 1 : 0 }}">
                                    <input type="hidden" name="qr_attendance_enabled" value="{{ $sess->qr_attendance_enabled ? 1 : 0 }}">
                                    <input type="hidden" name="require_check_out" value="{{ $sess->require_check_out ? 1 : 0 }}">
                                    <button type="submit" class="btn-primary btn-sm text-xs w-full">Save Session</button>
                                </form>
                            </details>
                            <form method="POST" action="{{ route('admin.activities.sessions.destroy', [$activity, $sess]) }}" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-secondary btn-sm text-xs text-rose-400">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-6 text-center text-slate-400">No sessions configured yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Team Applications Review (if team mode enabled) -->
@if($activity->teams->isNotEmpty())
<div class="card bg-[#172033] border border-slate-800 overflow-hidden mb-6">
    <div class="px-5 py-3.5 border-b border-slate-800">
        <h2 class="text-sm font-bold text-white uppercase tracking-wider">Registered Teams ({{ $activity->teams->count() }})</h2>
    </div>
    <div class="divide-y divide-slate-800">
        @foreach($activity->teams as $team)
            <div class="p-4 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-white text-sm">{{ $team->name }}</span>
                        <span class="px-2 py-0.5 rounded bg-slate-800 border border-slate-700 text-[10px] font-bold uppercase text-slate-300">
                            {{ $team->status }}
                        </span>
                        @if($team->project_title)
                            <span class="text-xs text-[#dfa6a9]">Project: {{ $team->project_title }}</span>
                        @endif
                    </div>
                    <div class="text-[11px] text-slate-400 mt-1">
                        Captain: <strong class="text-slate-200">{{ $team->captain?->name }}</strong> · Members:
                        @foreach($team->members as $tm)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-[#0B1120] border border-slate-800 ml-1">
                                {{ $tm->user?->name }}
                                <span class="text-[10px] {{ $tm->invitation_status === 'accepted' ? 'text-emerald-400' : 'text-amber-400' }}">({{ $tm->invitation_status }})</span>
                            </span>
                        @endforeach
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <form method="POST" action="{{ route('admin.activities.teams.review', $team) }}" class="inline">
                        @csrf
                        <input type="hidden" name="decision" value="approved">
                        <button type="submit" class="btn-primary btn-sm text-xs">Approve Team</button>
                    </form>
                    <form method="POST" action="{{ route('admin.activities.teams.review', $team) }}" class="inline-flex items-center gap-1">
                        @csrf
                        <input type="hidden" name="decision" value="rejected">
                        <input type="text" name="rejection_reason" placeholder="Reason..." required
                               class="input text-xs py-1 px-2 bg-[#0B1120] border-slate-700 w-36">
                        <button type="submit" class="btn-secondary btn-sm text-xs text-rose-300">Reject</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif

<!-- Registrations & Session Completion Management (with Slide-Over Inspector Drawer) -->
<div class="card bg-[#172033] border border-slate-800 overflow-hidden" x-data="{ inspectorRegId: null }">
    <div class="p-4 border-b border-slate-800 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-sm font-bold text-white uppercase tracking-wider">Participant Registrations & Session Tracking</h2>

        <form method="GET" action="{{ route('admin.activities.show', $activity) }}" class="flex flex-wrap items-center gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search student, ticket, team..."
                   class="input text-xs py-1.5 px-3 bg-[#0B1120] border-slate-700 text-white">
            <select name="session_id" class="select text-xs py-1.5 bg-[#0B1120] border-slate-700 text-white">
                <option value="">All Sessions</option>
                @foreach($activity->sessions as $s)
                    <option value="{{ $s->id }}" @selected(request('session_id') === $s->id)>{{ $s->name }}</option>
                @endforeach
            </select>
            <select name="reg_status" class="select text-xs py-1.5 bg-[#0B1120] border-slate-700 text-white">
                <option value="">All Statuses</option>
                @foreach(\App\Models\ActivityRegistration::STATUSES as $st)
                    <option value="{{ $st }}" @selected(request('reg_status') === $st)>{{ ucfirst($st) }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn-secondary btn-sm text-xs">Filter</button>
        </form>
    </div>

    <div class="divide-y divide-slate-800">
        @forelse($registrations as $reg)
            <div class="p-4 space-y-3 hover:bg-[#1E293B]/30">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-xs font-bold text-[#dfa6a9]">{{ $reg->ticket_code }}</span>
                            <span class="font-bold text-white text-sm">{{ $reg->user?->name }}</span>
                            <span class="text-xs text-slate-400">({{ $reg->user?->getProfileProgram() ?? 'N/A' }} · {{ $reg->user?->getProfileYearLevel() ?? 'N/A' }})</span>
                            @if($reg->team)
                                <span class="px-2 py-0.5 rounded bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 text-[10px] font-bold">
                                    Team: {{ $reg->team->name }}
                                </span>
                            @endif
                        </div>
                        <div class="text-[11px] text-slate-400 mt-0.5">
                            Email: {{ $reg->user?->email }}
                            @if(\App\Models\User::canViewStudentId(auth()->user()))
                                · Student ID: <span class="font-mono text-slate-300">{{ $reg->user?->getMaskedStudentNumber(auth()->user()) ?? 'N/A' }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button"
                                @click="inspectorRegId = inspectorRegId === '{{ $reg->id }}' ? null : '{{ $reg->id }}'"
                                class="px-2.5 py-1 rounded-lg bg-[#0B1120] border border-slate-700 hover:border-[#8F3033] text-[11px] font-semibold text-[#dfa6a9]">
                            Inspect Submission
                        </button>
                        <span class="px-2.5 py-1 rounded-full bg-slate-800 border border-slate-700 text-[10px] font-bold uppercase text-white">
                            Parent: {{ $reg->status }}
                        </span>
                        <span class="px-2.5 py-1 rounded-full bg-slate-800 border border-slate-700 text-[10px] font-bold uppercase text-sky-300">
                            Completion: {{ $reg->completion_status }}
                        </span>

                        @if($reg->status !== 'approved')
                            <form method="POST" action="{{ route('admin.activities.registrations.approve', $reg) }}" class="inline">
                                @csrf
                                <button type="submit" class="btn-primary btn-sm text-xs">Approve Parent</button>
                            </form>
                        @endif

                        @if($reg->status !== 'rejected')
                            <form method="POST" action="{{ route('admin.activities.registrations.reject', $reg) }}" class="inline-flex items-center gap-1">
                                @csrf
                                <input type="text" name="rejection_reason" placeholder="Reject reason..." required
                                       class="input text-xs py-1 px-2 bg-[#0B1120] border-slate-700 w-32">
                                <button type="submit" class="btn-secondary btn-sm text-xs text-rose-300">Reject</button>
                            </form>
                        @endif
                    </div>
                </div>

                <!-- Slide-Over Participant Submission Inspector Drawer -->
                <div x-show="inspectorRegId === '{{ $reg->id }}'"
                     style="display: none;"
                     class="fixed inset-0 z-50 flex justify-end bg-black/70 backdrop-blur-sm"
                     @click.self="inspectorRegId = null">
                    <div class="w-full max-w-md bg-[#111827] border-l border-[#8F3033]/60 h-full overflow-y-auto p-6 space-y-5 shadow-2xl">
                        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-widest text-[#df747a]">Participant Inspector</span>
                                <h3 class="text-base font-extrabold text-white font-brand-display">{{ $reg->user?->name }}</h3>
                                <p class="text-xs font-mono-tabular text-slate-400">{{ $reg->ticket_code }} · {{ $reg->user?->email }}</p>
                            </div>
                            <button type="button" @click="inspectorRegId = null" class="px-2.5 py-1 rounded-lg bg-slate-800 text-xs text-slate-300 hover:text-white">Close</button>
                        </div>

                        <div class="grid grid-cols-2 gap-2.5 text-xs">
                            <div class="p-3 rounded-xl bg-[#0B1120] border border-slate-800">
                                <span class="text-[10px] text-slate-500 block">Program & Year</span>
                                <span class="font-semibold text-white">{{ $reg->user?->getProfileProgram() ?? 'N/A' }} · {{ $reg->user?->getProfileYearLevel() ?? 'N/A' }}</span>
                            </div>
                            <div class="p-3 rounded-xl bg-[#0B1120] border border-slate-800">
                                <span class="text-[10px] text-slate-500 block">Registration Status</span>
                                <span class="font-bold uppercase text-emerald-400">{{ $reg->status }} ({{ $reg->completion_status }})</span>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-300">Submitted Form Responses</h4>
                            @forelse($reg->answers as $ans)
                                <div class="p-3 rounded-xl bg-[#0B1120] border border-slate-800 text-xs">
                                    <span class="text-[10px] text-slate-500 block">{{ $ans->field_label }}</span>
                                    @if($ans->question_type === 'file_upload' && $ans->file_path)
                                        <a href="{{ route('activity-answers.download', $ans) }}" class="text-[#dfa6a9] hover:underline font-medium">
                                            Download: {{ $ans->displayValue() }}
                                        </a>
                                    @else
                                        <span class="text-slate-200 font-medium">{{ $ans->displayValue() ?: '—' }}</span>
                                    @endif
                                </div>
                            @empty
                                <p class="text-xs text-slate-500">No custom form answers submitted.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Submitted Dynamic Form Answers -->
                @if($reg->answers->isNotEmpty())
                    <div class="p-3 rounded-lg bg-[#0B1120] border border-slate-800 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 text-xs">
                        @foreach($reg->answers as $ans)
                            <div>
                                <span class="text-[10px] text-slate-500 block">{{ $ans->field_label }}</span>
                                @if($ans->question_type === 'file_upload' && $ans->file_path)
                                    <a href="{{ route('activity-answers.download', $ans) }}" class="text-[#dfa6a9] hover:underline font-medium">
                                        Download: {{ $ans->displayValue() }}
                                    </a>
                                @else
                                    <span class="text-slate-200 font-medium">{{ $ans->displayValue() ?: '—' }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                <!-- Enrolled Sessions & Per-Session Approval / Completion / Rewards -->
                <div class="space-y-2">
                    @foreach($reg->sessionRegistrations as $sr)
                        <div class="p-3 rounded-lg bg-[#0B1120]/80 border border-slate-800/90 flex flex-wrap items-center justify-between gap-3 text-xs">
                            <div>
                                <span class="font-bold text-white">{{ $sr->session?->name }}</span>
                                <span class="ml-2 px-2 py-0.5 rounded bg-slate-800 text-[10px] uppercase font-semibold text-slate-300">
                                    Reg: {{ $sr->status }}
                                </span>
                                <span class="ml-1 px-2 py-0.5 rounded bg-slate-800 text-[10px] uppercase font-semibold text-sky-300">
                                    Attendance: {{ $sr->attendance_status }}
                                </span>
                                <span class="ml-1 px-2 py-0.5 rounded bg-slate-800 text-[10px] uppercase font-semibold {{ $sr->completion_status === 'completed' ? 'text-emerald-400' : 'text-amber-300' }}">
                                    Completion: {{ $sr->completion_status }}
                                </span>
                                @if($sr->rewards_awarded)
                                    <span class="ml-2 text-emerald-400 font-bold">
                                        +{{ $sr->exp_awarded }} EXP / +{{ $sr->points_awarded }} Pts
                                    </span>
                                @endif
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                @if($sr->status !== 'approved')
                                    <form method="POST" action="{{ route('admin.activities.session-registrations.approve', $sr) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 rounded bg-emerald-600 text-white text-[11px] font-semibold">
                                            Approve Session
                                        </button>
                                    </form>
                                @endif

                                @if($sr->status !== 'rejected')
                                    <form method="POST" action="{{ route('admin.activities.session-registrations.reject', $sr) }}" class="inline-flex items-center gap-1">
                                        @csrf
                                        <input type="text" name="rejection_reason" placeholder="Reason..." required
                                               class="input text-[11px] py-1 px-2 bg-[#172033] border-slate-700 text-white w-28">
                                        <button type="submit" class="px-2 py-1 rounded bg-slate-800 border border-rose-500/40 text-rose-300 text-[11px] font-semibold hover:bg-rose-500/20">
                                            Reject Session
                                        </button>
                                    </form>
                                @endif

                                <form method="POST" action="{{ route('admin.activities.session-registrations.verify', $sr) }}" class="inline-flex items-center gap-1.5">
                                    @csrf
                                    <input type="hidden" name="organizer_verified" value="1">
                                    <input type="hidden" name="output_submitted" value="1">
                                    <select name="completion_status" class="select text-[11px] py-1 px-2 bg-[#172033] border-slate-700 text-white">
                                        @foreach(\App\Models\ActivitySessionRegistration::COMPLETION_STATUSES as $cs)
                                            <option value="{{ $cs }}" @selected($sr->completion_status === $cs)>{{ ucfirst(str_replace('_', ' ', $cs)) }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="px-2.5 py-1 rounded bg-[#560E0F] text-white text-[11px] font-semibold hover:bg-[#731416]">
                                        Verify & Evaluate Rewards
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="p-8 text-center text-xs text-slate-400">
                No participant registrations match the current filters.
            </div>
        @endforelse
    </div>

    <div class="p-4 border-t border-slate-800">
        {{ $registrations->links() }}
    </div>
</div>
</x-layouts.admin>

