<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <a href="{{ route('volunteers.index') }}" class="text-xs text-[#df747a] hover:underline flex items-center gap-1 mb-1 font-semibold">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                    Back to Volunteer Board
                </a>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-cyan-500/20 text-cyan-300 border border-cyan-500/30">
                        {{ $opportunity->category_label }}
                    </span>
                    <span class="text-xs text-[#df747a] font-bold">
                        +{{ $opportunity->exp_reward_per_hour * $opportunity->expected_hours }} EXP Potential
                    </span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-[#F8FAFC] font-brand-display tracking-tight mt-1">
                    {{ $opportunity->title }}
                </h1>
            </div>

            <div class="flex items-center gap-2">
                <span class="px-3 py-1.5 rounded-xl bg-[#172033] border border-[#334155] text-xs text-slate-300 font-semibold">
                    {{ $opportunity->acceptedApplications->count() }} / {{ $opportunity->capacity }} Slots Filled
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-[#0B1120]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if(session('status'))
            <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-semibold flex items-center gap-2">
                <i data-lucide="check-circle" class="w-4 h-4 shrink-0"></i>
                <span>{{ session('status') }}</span>
            </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left 2 Cols: Details, Responsibilities, Candidate Management -->
                <div class="lg:col-span-2 space-y-6">

                    <div class="card bg-[#172033] border border-[#334155] p-6 space-y-4">
                        <div>
                            <h3 class="text-xs font-bold text-cyan-400 uppercase tracking-wider">Opportunity Description</h3>
                            <p class="text-sm text-slate-200 mt-1 leading-relaxed">{{ $opportunity->description }}</p>
                        </div>

                        <div class="pt-4 border-t border-[#334155]">
                            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Responsibilities & Tasks</h3>
                            <p class="text-xs text-slate-300 mt-2 leading-relaxed whitespace-pre-line">{{ $opportunity->responsibilities }}</p>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 pt-4 border-t border-[#334155] text-xs text-slate-300">
                            <div>
                                <span class="text-slate-500 block text-[11px]">Expected Service</span>
                                <span class="font-bold text-white">{{ $opportunity->expected_hours }} hours</span>
                            </div>
                            <div>
                                <span class="text-slate-500 block text-[11px]">Schedule</span>
                                <span class="font-bold text-white">{{ $opportunity->starts_at->format('M d, H:i') }}</span>
                            </div>
                            <div>
                                <span class="text-slate-500 block text-[11px]">Location</span>
                                <span class="font-bold text-white capitalize">{{ $opportunity->location_type }} ({{ $opportunity->location_details ?: 'Campus' }})</span>
                            </div>
                        </div>
                    </div>

                    <!-- Coordinator / Admin Candidate Management -->
                    @if($isCoordinatorOrAdmin)
                    <div class="card bg-[#172033] border border-[#334155] p-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xs font-bold text-white uppercase tracking-wider">Volunteer Applicant Triage</h3>
                            <span class="text-xs text-slate-400">{{ $pendingApplications->count() }} Pending</span>
                        </div>

                        @if($pendingApplications->isEmpty())
                        <p class="text-xs text-slate-400 text-center py-4">No pending volunteer applications.</p>
                        @else
                        <div class="space-y-3">
                            @foreach($pendingApplications as $app)
                            <div class="p-4 bg-[#0B1120] rounded-xl border border-[#334155] space-y-3">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h4 class="text-xs font-bold text-white">{{ $app->user?->name }} ({{ $app->user?->student?->program ?? 'Student' }})</h4>
                                        <p class="text-[11px] text-slate-400">Shift request: {{ $app->assigned_shift ?: 'Flexible' }}</p>
                                    </div>
                                    <span class="text-[10px] text-slate-400">{{ $app->created_at->diffForHumans() }}</span>
                                </div>
                                <p class="text-xs text-slate-300 italic">"{{ $app->motivation_statement }}"</p>

                                <div class="flex items-center justify-end gap-2 pt-2 border-t border-[#334155]/60">
                                    <form method="POST" action="{{ route('volunteers.applications.decide', $app) }}">
                                        @csrf
                                        <input type="hidden" name="action" value="decline">
                                        <button type="submit" class="btn-secondary text-[11px] py-1 px-3">Decline</button>
                                    </form>
                                    <form method="POST" action="{{ route('volunteers.applications.decide', $app) }}">
                                        @csrf
                                        <input type="hidden" name="action" value="accept">
                                        <button type="submit" class="btn-primary text-[11px] py-1 px-4">Accept Volunteer</button>
                                    </form>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>

                    <!-- Coordinator Service Hours Approval -->
                    <div class="card bg-[#172033] border border-cyan-500/40 p-6 space-y-4">
                        <h3 class="text-xs font-bold text-cyan-400 uppercase tracking-wider">Service Hours & EXP Verification</h3>
                        <p class="text-xs text-slate-400">Verify actual service attendance and credit EXP through authoritative ExpLedgerService.</p>

                        <div class="space-y-3">
                            @forelse($opportunity->acceptedApplications as $acceptedApp)
                            <div class="p-3 bg-[#0B1120] rounded-xl border border-[#334155] flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                                <div>
                                    <p class="font-bold text-white">{{ $acceptedApp->user?->name }}</p>
                                    <p class="text-[11px] text-slate-400">Shift: {{ $acceptedApp->assigned_shift ?: 'General' }}</p>
                                    @if($acceptedApp->status === 'completed')
                                    <span class="text-emerald-400 font-bold">✓ Approved {{ $acceptedApp->approved_service_hours }} hrs (+{{ $acceptedApp->awarded_exp }} EXP)</span>
                                    @endif
                                </div>

                                @if($acceptedApp->status !== 'completed')
                                <form method="POST" action="{{ route('volunteers.applications.approve-hours', $acceptedApp) }}" class="flex items-center gap-2">
                                    @csrf
                                    <input type="number" step="0.5" name="approved_service_hours" value="{{ $opportunity->expected_hours }}"
                                           class="w-20 bg-slate-900 border border-slate-700 rounded-lg px-2 py-1 text-xs text-white">
                                    <input type="hidden" name="hours_approval_reason" value="Completed assigned volunteer shift satisfactorily.">
                                    <button type="submit" class="btn-primary text-xs py-1 px-3 whitespace-nowrap">Approve & Award EXP</button>
                                </form>
                                @endif
                            </div>
                            @empty
                            <p class="text-xs text-slate-500 text-center py-2">No accepted volunteers yet.</p>
                            @endforelse
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Right Col: Assigned Volunteers & Apply Box -->
                <div class="space-y-6">

                    <!-- Confirmed Volunteers -->
                    <div class="card bg-[#172033] border border-[#334155] p-5 space-y-4">
                        <div class="flex items-center justify-between border-b border-[#334155] pb-2">
                            <h3 class="text-xs font-bold text-white uppercase tracking-wider">Confirmed Volunteer Team</h3>
                            <span class="text-xs text-cyan-400 font-bold">{{ $opportunity->acceptedApplications->count() }} / {{ $opportunity->capacity }}</span>
                        </div>

                        <div class="space-y-3">
                            @forelse($opportunity->acceptedApplications as $cApp)
                            <div class="flex items-center gap-3 text-xs">
                                <div class="w-8 h-8 rounded-full bg-cyan-900 text-cyan-300 flex items-center justify-center font-bold shrink-0">
                                    {{ substr($cApp->user->name, 0, 1) }}
                                </div>
                                <div>
                                    <p class="font-bold text-white">{{ $cApp->user->name }}</p>
                                    <p class="text-[11px] text-slate-400">{{ $cApp->assigned_shift ?: 'General Volunteer' }}</p>
                                </div>
                            </div>
                            @empty
                            <p class="text-xs text-slate-500 text-center py-2">Be the first to join the team!</p>
                            @endforelse
                        </div>
                    </div>

                    <!-- Application Box for Members -->
                    @if(!$isCoordinatorOrAdmin)
                    <div class="card bg-[#172033] border border-cyan-500/30 p-5 space-y-4">
                        <h3 class="text-xs font-bold text-white uppercase tracking-wider">Volunteer Application</h3>

                        @if($myApplication)
                        <div class="p-3 bg-[#0B1120] rounded-xl border border-[#334155] text-xs space-y-1">
                            <p class="text-slate-300">Your application status:</p>
                            <span class="font-bold text-cyan-400 uppercase text-xs">{{ $myApplication->status_label }}</span>
                            @if($myApplication->status === 'completed')
                            <p class="text-emerald-400 font-bold pt-1">Verified Service: {{ $myApplication->approved_service_hours }} hours (+{{ $myApplication->awarded_exp }} EXP)</p>
                            @endif
                        </div>
                        @elseif($opportunity->acceptedApplications->count() >= $opportunity->capacity)
                        <p class="text-xs text-rose-400">All volunteer slots have been filled for this opportunity.</p>
                        @else
                        <form method="POST" action="{{ route('volunteers.apply', $opportunity) }}" class="space-y-3">
                            @csrf
                            <div>
                                <label class="block text-[11px] text-slate-400 mb-1">Motivation Statement *</label>
                                <textarea name="motivation_statement" rows="3" required placeholder="Why would you like to volunteer for this activity?"
                                          class="w-full bg-[#0B1120] border border-[#334155] rounded-xl p-3 text-xs text-white"></textarea>
                            </div>

                            <div>
                                <label class="block text-[11px] text-slate-400 mb-1">Relevant Skills or Prior Experience</label>
                                <input type="text" name="relevant_skills" placeholder="e.g. Photography, Customer service, AV equipment"
                                       class="w-full bg-[#0B1120] border border-[#334155] rounded-xl px-3 py-2 text-xs text-white">
                            </div>

                            <div>
                                <label class="block text-[11px] text-slate-400 mb-1">Preferred Shift / Availability</label>
                                <input type="text" name="assigned_shift" placeholder="e.g. Morning (8:00 AM - 12:00 PM)"
                                       class="w-full bg-[#0B1120] border border-[#334155] rounded-xl px-3 py-2 text-xs text-white">
                            </div>

                            <button type="submit" class="btn-primary text-xs py-2 px-4 w-full shadow-lg">Submit Volunteer Application</button>
                        </form>
                        @endif
                    </div>
                    @endif

                </div>
            </div>

        </div>
    </div>
</x-app-layout>
