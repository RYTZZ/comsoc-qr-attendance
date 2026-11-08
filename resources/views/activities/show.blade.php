<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <a href="{{ route('activities.index') }}" class="text-xs text-slate-400 hover:text-white inline-flex items-center gap-1 mb-1">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                    Back to COMSOC Activities
                </a>
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-2xl font-bold text-white font-brand-display tracking-tight">{{ $activity->title }}</h1>
                    <span class="px-2.5 py-0.5 rounded-full bg-[#560E0F]/40 border border-[#560E0F] text-[#dfa6a9] text-[10px] font-bold uppercase">
                        {{ $activity->category }}
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('member.dynamic_qr') }}" class="btn-secondary text-xs flex items-center gap-1.5">
                    <i data-lucide="qr-code" class="w-4 h-4 text-[#dfa6a9]"></i>
                    <span>My Attendance QR</span>
                </a>
                <a href="{{ route('activities.my-registrations') }}" class="btn-secondary text-xs">
                    My Registrations
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $user = auth()->user();
        $parentSchema = $activity->getEffectiveFormSnapshot();
        $existingSessionIds = $registration
            ? $registration->sessionRegistrations->whereIn('status', ['pending', 'approved', 'waitlisted'])->pluck('activity_session_id')->all()
            : [];
        $sessionSchedulesJs = $activity->sessions->mapWithKeys(fn($s) => [
            $s->id => [
                'id' => $s->id,
                'name' => $s->name,
                'starts_at' => $s->starts_at?->timestamp,
                'ends_at' => $s->ends_at?->timestamp,
                'time_label' => $s->starts_at ? $s->starts_at->format('M d, g:i A') : 'Schedule TBA',
                'venue' => $s->venue ?: ($activity->venue ?: 'TBA'),
                'exp_reward' => (int) $s->exp_reward,
                'points_reward' => (int) $s->points_reward,
                'has_form' => !empty($s->getEffectiveFormSnapshot()['fields']),
            ]
        ]);
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6"
             x-data="{
                 selectedSessions: {{ Js::from(old('session_ids', $existingSessionIds)) }},
                 schedules: {{ Js::from($sessionSchedulesJs) }},
                 conflictPolicy: '{{ $activity->conflict_policy }}',
                 maxSessions: {{ $activity->max_sessions_per_student ?? 'null' }},
                 allowMultiple: {{ $activity->allow_multiple_sessions ? 'true' : 'false' }},
                 parentBonusExp: {{ $activity->award_parent_rewards ? (int) $activity->parent_exp_reward : 0 }},
                 parentBonusPoints: {{ $activity->award_parent_rewards ? (int) $activity->parent_points_reward : 0 }},
                 toggleSession(id) {
                     if (!this.allowMultiple) {
                         this.selectedSessions = [id];
                         return;
                     }
                     const idx = this.selectedSessions.indexOf(id);
                     if (idx > -1) {
                         this.selectedSessions.splice(idx, 1);
                     } else {
                         if (this.maxSessions && this.selectedSessions.length >= this.maxSessions) {
                             alert('You can select at most ' + this.maxSessions + ' session(s).');
                             return;
                         }
                         this.selectedSessions.push(id);
                     }
                 },
                 isSelected(id) {
                     return this.selectedSessions.includes(id);
                 },
                 sessionConflictsWithSelected(id) {
                     const target = this.schedules[id];
                     if (!target || !target.starts_at || !target.ends_at) return null;
                     for (let i = 0; i < this.selectedSessions.length; i++) {
                         const selId = this.selectedSessions[i];
                         if (selId === id) continue;
                         const s = this.schedules[selId];
                         if (s && s.starts_at && s.ends_at && target.starts_at < s.ends_at && s.starts_at < target.ends_at) {
                             return s.name;
                         }
                     }
                     return null;
                 },
                 totalSelectedExp() {
                     let sum = this.selectedSessions.reduce((acc, id) => acc + (this.schedules[id]?.exp_reward || 0), 0);
                     return this.selectedSessions.length > 0 ? sum + this.parentBonusExp : 0;
                 },
                 totalSelectedPoints() {
                     let sum = this.selectedSessions.reduce((acc, id) => acc + (this.schedules[id]?.points_reward || 0), 0);
                     return this.selectedSessions.length > 0 ? sum + this.parentBonusPoints : 0;
                 },
                 activeConflicts() {
                     const conflicts = [];
                     for (let i = 0; i < this.selectedSessions.length; i++) {
                         for (let j = i + 1; j < this.selectedSessions.length; j++) {
                             const s1 = this.schedules[this.selectedSessions[i]];
                             const s2 = this.schedules[this.selectedSessions[j]];
                             if (s1 && s2 && s1.starts_at && s1.ends_at && s2.starts_at && s2.ends_at) {
                                 if (s1.starts_at < s2.ends_at && s2.starts_at < s1.ends_at) {
                                     conflicts.push(`${s1.name} overlaps with ${s2.name}`);
                                 }
                             }
                         }
                     }
                     return conflicts;
                 }
             }">

            <!-- Validation Errors -->
            @if($errors->any())
                <div class="p-4 rounded-xl bg-rose-500/15 border border-rose-500/40 text-xs text-rose-200 space-y-1">
                    <div class="font-bold text-rose-300">Please resolve the following before submitting:</div>
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Parent Event Details Banner Card -->
            <div class="card bg-[#172033] border border-slate-800 p-6 space-y-4">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 space-y-3">
                        <h2 class="text-lg font-bold text-white">About This Activity</h2>
                        <p class="text-xs text-slate-300 whitespace-pre-line leading-relaxed">
                            {{ $activity->description ?: 'Join this official Computing Society activity.' }}
                        </p>

                        @if($activity->general_requirements)
                            <div class="p-3.5 rounded-xl bg-[#0B1120] border border-slate-800">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-amber-400 block mb-1">General Requirements</span>
                                <p class="text-xs text-slate-300">{{ $activity->general_requirements }}</p>
                            </div>
                        @endif
                    </div>

                    <div class="p-4 rounded-xl bg-[#0B1120] border border-slate-800 space-y-2.5 text-xs">
                        <div class="font-bold text-white uppercase tracking-wider text-[11px] border-b border-slate-800 pb-2">
                            Event Specifications
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Organizer:</span>
                            <span class="font-semibold text-white">{{ $activity->organizer_name ?: 'COMSOC' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Venue:</span>
                            <span class="font-semibold text-white">{{ $activity->venue ?: 'TBA' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Schedule:</span>
                            <span class="font-semibold text-white">{{ $activity->starts_at ? $activity->starts_at->format('M d, Y g:i A') : 'TBA' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Registration Mode:</span>
                            <span class="font-semibold text-[#dfa6a9] capitalize">{{ $activity->registration_mode }}</span>
                        </div>
                        @if($activity->max_sessions_per_student)
                            <div class="flex justify-between">
                                <span class="text-slate-400">Session Limit:</span>
                                <span class="font-semibold text-amber-300">Max {{ $activity->max_sessions_per_student }} session(s)</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Authenticated Student Profile Integration Card (Read-Only, Auto-Attached) -->
            <div class="card bg-[#172033] border border-slate-800 p-4">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-[#560E0F]/30 border border-[#560E0F] flex items-center justify-center text-[#dfa6a9] font-bold">
                            <i data-lucide="user-check" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <div class="text-xs font-bold text-white">Authenticated Participant Profile (Auto-Linked)</div>
                            <div class="text-[11px] text-slate-400">
                                Your identity is verified automatically from your COMSOC Hub account. You do not need to re-enter personal details.
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs bg-[#0B1120] px-4 py-2.5 rounded-xl border border-slate-800">
                        <div>
                            <span class="text-[10px] text-slate-500 block">Full Name</span>
                            <span class="font-semibold text-white">{{ $user->name }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-500 block">Member ID</span>
                            <span class="font-mono text-slate-300">{{ $user->member_id }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-500 block">Program</span>
                            <span class="text-slate-300">{{ $user->getProfileProgram() ?? 'Linked' }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-500 block">Year Level</span>
                            <span class="text-slate-300">{{ $user->getProfileYearLevel() ?? 'Linked' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Team Formation Section (for IDEATHON, Hackathons, Team Competitions) -->
            @if(in_array($activity->registration_mode, ['team', 'both'], true))
                <div class="card bg-[#172033] border border-slate-800 p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                        <div>
                            <h2 class="text-sm font-bold text-white uppercase tracking-wider">Team Formation & Invitations</h2>
                            <p class="text-[11px] text-slate-400">
                                Team Size: {{ $activity->min_team_size }} to {{ $activity->max_team_size }} members.
                                @if($activity->require_all_team_invites_accepted)
                                    All invited members must accept their invitations before the team captain submits registration.
                                @endif
                            </p>
                        </div>
                    </div>

                    @if($userTeamMembership)
                        @php $team = $userTeamMembership->team; @endphp
                        <div class="p-4 rounded-xl bg-[#0B1120] border border-slate-800 space-y-3">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <span class="text-xs text-slate-400">Your Team:</span>
                                    <span class="text-base font-bold text-white ml-1">{{ $team->name }}</span>
                                    <span class="ml-2 px-2 py-0.5 rounded bg-indigo-500/20 text-indigo-300 text-[10px] font-bold uppercase">
                                        {{ $team->status }}
                                    </span>
                                </div>
                                @if($userTeamMembership->invitation_status === 'pending')
                                    <div class="flex items-center gap-2">
                                        <form method="POST" action="{{ route('activities.teams.invitations.respond', $userTeamMembership) }}">
                                            @csrf
                                            <input type="hidden" name="decision" value="accepted">
                                            <button type="submit" class="btn-primary btn-sm text-xs">Accept Team Invite</button>
                                        </form>
                                        <form method="POST" action="{{ route('activities.teams.invitations.respond', $userTeamMembership) }}">
                                            @csrf
                                            <input type="hidden" name="decision" value="rejected">
                                            <button type="submit" class="btn-secondary btn-sm text-xs text-rose-300">Decline</button>
                                        </form>
                                    </div>
                                @endif
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                                @foreach($team->members as $tm)
                                    <div class="p-2.5 rounded-lg bg-[#172033] border border-slate-800 flex items-center justify-between text-xs">
                                        <div>
                                            <div class="font-semibold text-white">{{ $tm->user?->name }}</div>
                                            <div class="text-[10px] text-slate-400 capitalize">{{ $tm->role }}</div>
                                        </div>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $tm->invitation_status === 'accepted' ? 'bg-emerald-500/20 text-emerald-300' : 'bg-amber-500/20 text-amber-300' }}">
                                            {{ $tm->invitation_status }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>

                            @if($team->captain_user_id === $user->id && $team->members->count() < $activity->max_team_size)
                                <form method="POST" action="{{ route('activities.teams.invite', $team) }}" class="flex items-center gap-2 pt-2">
                                    @csrf
                                    <select name="user_id" required class="select text-xs bg-[#172033] border-slate-700 text-white flex-1">
                                        <option value="">— Invite another COMSOC Hub member —</option>
                                        @foreach($eligibleMembers as $em)
                                            <option value="{{ $em->id }}">{{ $em->name }} ({{ $em->member_id }})</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn-secondary text-xs py-2 px-4">Send Invite</button>
                                </form>
                            @endif
                        </div>
                    @else
                        <!-- Create Team Form -->
                        <form method="POST" action="{{ route('activities.teams.store', $activity) }}" class="space-y-3 p-4 rounded-xl bg-[#0B1120] border border-slate-800">
                            @csrf
                            <div class="text-xs font-bold text-white">Create a Team as Team Captain</div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[11px] text-slate-400 mb-1">Team Name *</label>
                                    <input type="text" name="name" required placeholder="e.g., Team NeuralForge"
                                           class="input w-full text-xs bg-[#172033] border-slate-700 text-white">
                                </div>
                                <div>
                                    <label class="block text-[11px] text-slate-400 mb-1">Project / Innovation Title (Optional)</label>
                                    <input type="text" name="project_title" placeholder="e.g., Smart Campus Queue Optimizer"
                                           class="input w-full text-xs bg-[#172033] border-slate-700 text-white">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-[11px] text-slate-400 mb-1">Invite COMSOC Hub Members</label>
                                    <select name="invited_user_ids[]" multiple class="select w-full text-xs bg-[#172033] border-slate-700 text-white h-28">
                                        @foreach($eligibleMembers as $em)
                                            <option value="{{ $em->id }}">{{ $em->name }} ({{ $em->member_id }} · {{ $em->getProfileProgram() ?? 'Student' }})</option>
                                        @endforeach
                                    </select>
                                    <p class="text-[10px] text-slate-500 mt-1">Hold Ctrl / Cmd to select multiple members.</p>
                                </div>
                            </div>
                            <div class="flex justify-end">
                                <button type="submit" class="btn-secondary text-xs py-2 px-4">Create Team & Send Invitations</button>
                            </div>
                        </form>
                    @endif
                </div>
            @endif

            <!-- Main Registration & Multi-Session Conference Builder (2-Column Sticky Layout) -->
            <form method="POST"
                  action="{{ route('activities.register', $activity) }}"
                  enctype="multipart/form-data"
                  class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                @csrf

                @if($userTeamMembership && $userTeamMembership->invitation_status === 'accepted')
                    <input type="hidden" name="team_id" value="{{ $userTeamMembership->activity_team_id }}">
                @endif

                <!-- Left Column (2/3 width): Interactive Session Track Selector + Dynamic Questions -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Step 1: Select Sessions -->
                    <div class="bento-card space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-800 pb-3">
                            <div>
                                <h2 class="text-sm font-bold text-white uppercase tracking-wider">
                                    Select Activity Session(s)
                                </h2>
                                <p class="text-[11px] text-slate-400">
                                    @if($activity->allow_multiple_sessions)
                                        You may select multiple sessions{{ $activity->max_sessions_per_student ? " (up to {$activity->max_sessions_per_student})" : '' }}.
                                    @else
                                        Select 1 session to attend.
                                    @endif
                                    Each session tracks capacity, QR attendance, and EXP/Points independently.
                                </p>
                            </div>

                            @if($activity->allow_independent_session_registration)
                                <label class="inline-flex items-center gap-2 text-xs text-amber-300 cursor-pointer">
                                    <input type="checkbox" name="independent_session_mode" value="1"
                                           class="rounded bg-slate-900 border-slate-700 text-[#560E0F]">
                                    <span>Independent Session Registration Only</span>
                                </label>
                            @endif
                        </div>

                        <!-- Live Schedule Conflict Banner -->
                        <template x-if="activeConflicts().length > 0">
                            <div class="p-3 rounded-xl border text-xs"
                                 :class="conflictPolicy === 'block' ? 'bg-rose-500/15 border-rose-500/40 text-rose-300' : 'bg-amber-500/15 border-amber-500/40 text-amber-300'">
                                <strong x-text="conflictPolicy === 'block' ? 'Schedule Conflict (Blocked): ' : 'Schedule Overlap Warning: '"></strong>
                                <span x-text="activeConflicts().join(' · ')"></span>
                            </div>
                        </template>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($activity->sessions as $sess)
                                @php
                                    $remaining = $sess->remainingSlots();
                                    $isFull = $sess->isFull();
                                    $sessionSchema = $sess->getEffectiveFormSnapshot();
                                    $fillPercent = $sess->capacity ? min(100, (int) round((($sess->capacity - ($remaining ?? 0)) / max(1, $sess->capacity)) * 100)) : 25;
                                @endphp
                                <div class="p-4 rounded-xl border transition cursor-pointer relative overflow-hidden"
                                     :class="isSelected('{{ $sess->id }}') ? 'bg-[#1E293B] border-[#8F3033] ring-1 ring-[#8F3033] shadow-lg shadow-[#560E0F]/20' : 'bg-[#0B1120] border-slate-800 hover:border-slate-700'"
                                     @click="toggleSession('{{ $sess->id }}')">

                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex items-start gap-3">
                                            <input type="checkbox"
                                                   name="session_ids[]"
                                                   value="{{ $sess->id }}"
                                                   :checked="isSelected('{{ $sess->id }}')"
                                                   @click.stop="toggleSession('{{ $sess->id }}')"
                                                   class="mt-1 rounded bg-slate-900 border-slate-700 text-[#560E0F] focus:ring-[#560E0F]">
                                            <div>
                                                <div class="font-bold text-white text-sm">{{ $sess->name }}</div>
                                                @if($sess->track_or_category)
                                                    <span class="text-[11px] text-[#dfa6a9] font-medium">{{ $sess->track_or_category }}</span>
                                                @endif
                                                @if($sess->description)
                                                    <p class="text-xs text-slate-400 mt-1">{{ $sess->description }}</p>
                                                @endif

                                                <!-- Live Overlap Indicator Badge -->
                                                <template x-if="!isSelected('{{ $sess->id }}') && sessionConflictsWithSelected('{{ $sess->id }}')">
                                                    <div class="mt-1.5 inline-flex items-center gap-1 px-2 py-0.5 rounded bg-amber-500/15 border border-amber-500/30 text-[10px] font-semibold text-amber-300">
                                                        <span>⏰ Overlaps with</span>
                                                        <span x-text="sessionConflictsWithSelected('{{ $sess->id }}')"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>

                                        <div class="text-right shrink-0 font-mono-tabular">
                                            <span class="px-2 py-0.5 rounded bg-[#560E0F]/40 border border-[#560E0F] text-[#dfa6a9] text-[10px] font-bold block">
                                                +{{ $sess->exp_reward }} EXP
                                            </span>
                                            <span class="text-[10px] text-amber-300 font-semibold block mt-0.5">
                                                +{{ $sess->points_reward }} Points
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Visual Slot Capacity Bar -->
                                    @if($sess->capacity !== null)
                                        <div class="w-full bg-slate-900 rounded-full h-1.5 mt-3 overflow-hidden">
                                            <div class="h-1.5 rounded-full {{ $isFull ? 'bg-amber-500' : 'bg-emerald-500' }}" style="width: {{ $fillPercent }}%"></div>
                                        </div>
                                    @endif

                                    <div class="mt-2.5 pt-2 border-t border-slate-800/80 flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-400">
                                        <div>
                                            <i data-lucide="clock" class="w-3 h-3 inline mr-1"></i>
                                            {{ $sess->starts_at ? $sess->starts_at->format('M d, g:i A') : 'Schedule TBA' }}
                                            @if($sess->ends_at) — {{ $sess->ends_at->format('g:i A') }} @endif
                                        </div>
                                        <div>
                                            <i data-lucide="map-pin" class="w-3 h-3 inline mr-1"></i>
                                            {{ $sess->venue ?: ($activity->venue ?: 'TBA') }}
                                        </div>
                                        <div class="font-mono-tabular">
                                            @if($sess->capacity !== null)
                                                @if($isFull)
                                                    <span class="text-amber-400 font-semibold">
                                                        Full ({{ $sess->waitlist_enabled ? 'Waitlist Open' : '0 slots' }})
                                                    </span>
                                                @else
                                                    <span class="text-emerald-400 font-semibold">{{ $remaining }} / {{ $sess->capacity }} slots left</span>
                                                @endif
                                            @else
                                                <span class="text-emerald-400">Open Capacity</span>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Session-Specific Dynamic Questions (Shown when Session is Selected) -->
                                    @if($sessionSchema && !empty($sessionSchema['fields']))
                                        <div x-show="isSelected('{{ $sess->id }}')" @click.stop class="mt-4 pt-3 border-t border-slate-700 space-y-3">
                                            <div class="text-xs font-bold text-amber-300">
                                                Questions for {{ $sess->name }}:
                                            </div>
                                            <x-dynamic-form-fields :schema="$sessionSchema" :namePrefix="'session_answers[' . $sess->id . ']'" />
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Step 2: Parent Activity Registration Form Questions (if assigned by Super Admin) -->
                    @if($parentSchema && !empty($parentSchema['fields']))
                        <div class="bento-card space-y-4">
                            <div class="border-b border-slate-800 pb-2">
                                <h2 class="text-sm font-bold text-white uppercase tracking-wider">
                                    {{ $parentSchema['title'] ?? 'Activity Registration Questions' }}
                                </h2>
                                @if(!empty($parentSchema['description']))
                                    <p class="text-xs text-slate-400 mt-0.5">{{ $parentSchema['description'] }}</p>
                                @endif
                            </div>

                            <x-dynamic-form-fields :schema="$parentSchema" namePrefix="answers" />
                        </div>
                    @endif
                </div>

                <!-- Right Column (1/3 width Sticky Summary Rail): Live Registration Summary & Rewards Preview -->
                <div class="lg:col-span-1 lg:sticky lg:top-20 space-y-4">
                    <div class="bento-hero space-y-4">
                        <div class="flex items-center justify-between border-b border-white/10 pb-3">
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-widest text-[#df747a]">Conference Builder</span>
                                <h3 class="text-sm font-extrabold text-white font-brand-display">Your Registration Summary</h3>
                            </div>
                            <span class="px-2.5 py-0.5 rounded-full bg-[#0B1120] border border-white/10 text-xs font-bold font-mono-tabular text-white"
                                  x-text="selectedSessions.length + ' Selected'"></span>
                        </div>

                        <!-- Selected Session Items -->
                        <div class="space-y-2 max-h-60 overflow-y-auto">
                            <template x-for="sid in selectedSessions" :key="sid">
                                <div class="p-2.5 rounded-xl bg-[#0B1120]/90 border border-white/[0.08] flex items-center justify-between gap-2 text-xs">
                                    <div class="min-w-0">
                                        <div class="font-bold text-white truncate" x-text="schedules[sid]?.name || 'Session'"></div>
                                        <div class="text-[10px] text-slate-400 truncate" x-text="(schedules[sid]?.time_label || '') + ' · ' + (schedules[sid]?.venue || '')"></div>
                                    </div>
                                    <span class="font-mono-tabular text-[11px] font-bold text-[#dfa6a9] shrink-0"
                                          x-text="'+' + (schedules[sid]?.exp_reward || 0) + ' XP'"></span>
                                </div>
                            </template>
                            <div x-show="selectedSessions.length === 0" class="p-4 rounded-xl bg-[#0B1120]/60 border border-dashed border-slate-700 text-center text-xs text-slate-400">
                                Select one or more session tracks on the left to build your activity schedule.
                            </div>
                        </div>

                        <!-- Potential Completion Rewards Summary -->
                        <div class="p-3.5 rounded-xl bg-[#0B1120]/90 border border-white/[0.08] space-y-1.5 text-xs">
                            <div class="flex items-center justify-between text-slate-400">
                                <span>Schedule Check:</span>
                                <span class="font-semibold"
                                      :class="activeConflicts().length === 0 ? 'text-emerald-400' : 'text-amber-300'"
                                      x-text="activeConflicts().length === 0 ? '✓ No Time Conflicts' : '⚠ Overlap Detected'"></span>
                            </div>
                            <div class="flex items-center justify-between pt-1.5 border-t border-slate-800">
                                <span class="text-slate-300 font-semibold">Potential Completion Rewards:</span>
                                <span class="font-mono-tabular font-bold text-amber-300">
                                    +<span x-text="totalSelectedExp()"></span> EXP · +<span x-text="totalSelectedPoints()"></span> Pts
                                </span>
                            </div>
                        </div>

                        <!-- Submit / Active Status -->
                        @if(!$registration || in_array($registration->status, ['cancelled', 'withdrawn', 'rejected'], true) || $activity->allow_independent_session_registration)
                            <button type="submit" class="btn-primary w-full text-xs py-3 px-6 font-bold shadow-xl">
                                Submit Activity Registration
                            </button>
                        @else
                            <div class="p-3.5 rounded-xl bg-emerald-950/40 border border-emerald-500/30 space-y-2.5">
                                <div class="text-xs text-emerald-300">
                                    Active Registration (<strong class="font-mono-tabular">{{ $registration->ticket_code }}</strong>) · Status: <strong class="uppercase">{{ $registration->status }}</strong>
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <a href="{{ route('activities.my-registrations') }}" class="btn-secondary btn-sm text-xs flex-1 text-center">View My Registrations</a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </form>

            @if($registration && !in_array($registration->status, ['cancelled', 'withdrawn', 'rejected'], true) && $activity->allow_cancellation)
                <div class="flex justify-end">
                    <form method="POST" action="{{ route('activities.registrations.cancel', $registration) }}">
                        @csrf
                        <button type="submit" class="btn-secondary btn-sm text-xs text-rose-300">Cancel Registration</button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

