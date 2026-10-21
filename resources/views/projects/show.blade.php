<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <a href="{{ route('projects.index') }}" class="text-xs text-[#df747a] hover:underline flex items-center gap-1 mb-1 font-semibold">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                    Back to Projects
                </a>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                        {{ $project->category_label }}
                    </span>
                    <span class="text-xs text-slate-400 capitalize">Stage: {{ $project->stage_label }}</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-[#F8FAFC] font-brand-display tracking-tight mt-1">
                    {{ $project->title }}
                </h1>
            </div>

            <div class="flex items-center gap-2">
                @if($project->repository_url)
                <a href="{{ $project->repository_url }}" target="_blank" rel="noopener noreferrer" class="btn-secondary text-xs flex items-center gap-1.5">
                    <i data-lucide="github" class="w-4 h-4"></i>
                    <span>Repository</span>
                </a>
                @endif
                @if($project->demo_url)
                <a href="{{ $project->demo_url }}" target="_blank" rel="noopener noreferrer" class="btn-primary text-xs flex items-center gap-1.5 shadow-md">
                    <i data-lucide="external-link" class="w-4 h-4"></i>
                    <span>Live Demo</span>
                </a>
                @endif
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
                <!-- Left 2 Cols: Details, Workspace & Applications -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Problem Statement & Description -->
                    <div class="card bg-[#172033] border border-[#334155] p-6 space-y-4">
                        <div>
                            <h3 class="text-xs font-bold text-emerald-400 uppercase tracking-wider">Problem Statement</h3>
                            <p class="text-sm text-slate-200 mt-1 leading-relaxed">{{ $project->problem_statement }}</p>
                        </div>

                        <div class="pt-4 border-t border-[#334155]">
                            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Objectives & Scope</h3>
                            <p class="text-xs text-slate-300 mt-2 leading-relaxed whitespace-pre-line">{{ $project->description }}</p>
                        </div>

                        <!-- Needed Skills -->
                        @if(!empty($project->skills_needed))
                        <div class="pt-4 border-t border-[#334155]">
                            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Technologies & Skills Needed</h4>
                            <div class="flex flex-wrap gap-2">
                                @foreach($project->skills_needed as $sk)
                                <span class="px-2.5 py-1 rounded-lg bg-[#0B1120] text-slate-200 border border-[#334155] text-xs font-medium">
                                    {{ $sk }}
                                </span>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Collaboration Workspace: Milestones & Tasks -->
                    <div class="card bg-[#172033] border border-[#334155] p-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i data-lucide="milestone" class="w-4 h-4 text-emerald-400"></i>
                                <h3 class="text-xs font-bold text-white uppercase tracking-wider">Project Milestones & Deliverables</h3>
                            </div>
                        </div>

                        @if($project->milestones->isEmpty())
                        <p class="text-xs text-slate-400 text-center py-4">No project milestones recorded yet.</p>
                        @else
                        <div class="space-y-2">
                            @foreach($project->milestones as $ms)
                            <div class="p-3 bg-[#0B1120] border border-[#334155] rounded-xl flex items-center justify-between text-xs">
                                <div>
                                    <span class="font-bold text-white">{{ $ms->title }}</span>
                                    @if($ms->due_date)
                                    <span class="text-slate-400 text-[11px] ml-2">Target: {{ $ms->due_date->format('M d, Y') }}</span>
                                    @endif
                                </div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase
                                    {{ $ms->status === 'completed' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-amber-500/20 text-amber-300 border border-amber-500/30' }}">
                                    {{ $ms->status }}
                                </span>
                            </div>
                            @endforeach
                        </div>
                        @endif

                        @if($isOwner)
                        <form method="POST" action="{{ route('projects.milestones.store', $project) }}" class="pt-3 border-t border-[#334155] flex flex-col sm:flex-row gap-2">
                            @csrf
                            <input type="text" name="title" required placeholder="Enter milestone title"
                                   class="bg-[#0B1120] border border-[#334155] rounded-xl px-3 py-1.5 text-xs text-white flex-1">
                            <input type="date" name="due_date" class="bg-[#0B1120] border border-[#334155] rounded-xl px-3 py-1.5 text-xs text-white">
                            <button type="submit" class="btn-primary text-xs py-1.5 px-4 whitespace-nowrap">Add</button>
                        </form>
                        @endif
                    </div>

                    <!-- Owner Management: Candidate Applications Review -->
                    @if($isOwner || auth()->user()->isAdmin())
                    <div class="card bg-[#172033] border border-[#334155] p-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xs font-bold text-white uppercase tracking-wider">Recruitment Candidate Applications</h3>
                            <span class="text-xs text-slate-400">{{ $pendingApplications->count() }} Pending</span>
                        </div>

                        @if($pendingApplications->isEmpty())
                        <p class="text-xs text-slate-400 text-center py-4">No pending join applications.</p>
                        @else
                        <div class="space-y-3">
                            @foreach($pendingApplications as $app)
                            <div class="p-4 bg-[#0B1120] rounded-xl border border-[#334155] space-y-3">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h4 class="text-xs font-bold text-white">{{ $app->user?->name }} ({{ $app->user?->student?->program ?? 'Student' }})</h4>
                                        <p class="text-[11px] text-emerald-400">Applying as: {{ $app->preferred_role }}</p>
                                    </div>
                                    <span class="text-[10px] text-slate-400">{{ $app->created_at->diffForHumans() }}</span>
                                </div>
                                <p class="text-xs text-slate-300 italic">"{{ $app->motivation }}"</p>
                                @if($app->skills_summary)
                                <p class="text-[11px] text-slate-400">Skills: {{ $app->skills_summary }}</p>
                                @endif

                                <div class="flex items-center justify-end gap-2 pt-2 border-t border-[#334155]/60">
                                    <form method="POST" action="{{ route('projects.applications.decide', $app) }}">
                                        @csrf
                                        <input type="hidden" name="action" value="decline">
                                        <button type="submit" class="btn-secondary text-[11px] py-1 px-3">Decline</button>
                                    </form>
                                    <form method="POST" action="{{ route('projects.applications.decide', $app) }}">
                                        @csrf
                                        <input type="hidden" name="action" value="accept">
                                        <button type="submit" class="btn-primary text-[11px] py-1 px-4">Accept Collaborator</button>
                                    </form>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @endif
                </div>

                <!-- Right Col: Team Members & Application Box -->
                <div class="space-y-6">

                    <!-- Team Roster -->
                    <div class="card bg-[#172033] border border-[#334155] p-5 space-y-4">
                        <div class="flex items-center justify-between border-b border-[#334155] pb-2">
                            <h3 class="text-xs font-bold text-white uppercase tracking-wider">Active Team Roster</h3>
                            <span class="text-xs text-emerald-400 font-bold">{{ $project->activeMembers->count() }} / {{ $project->team_size_target }}</span>
                        </div>

                        <div class="space-y-3">
                            @foreach($project->activeMembers as $mem)
                            <div class="flex items-center gap-3 text-xs">
                                <x-user-avatar :user="$mem->user" size="sm" class="rounded-xl border border-[#8F3033]/60" />
                                <div>
                                    <p class="font-bold text-white">{{ $mem->user->name }}</p>
                                    <p class="text-[11px] text-slate-400">{{ $mem->role }}</p>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Application Form Box for non-members -->
                    @if(!$isOwner && !$isMember)
                    <div class="card bg-[#172033] border border-emerald-500/30 p-5 space-y-4">
                        <h3 class="text-xs font-bold text-white uppercase tracking-wider">Join This Project</h3>

                        @if($myApplication)
                        <div class="p-3 bg-[#0B1120] rounded-xl border border-[#334155] text-xs">
                            <p class="text-slate-300">Your application status:</p>
                            <span class="font-bold text-amber-400 uppercase text-xs">{{ $myApplication->status }}</span>
                        </div>
                        @elseif($project->activeMembers->count() >= $project->team_size_target)
                        <p class="text-xs text-rose-400">Team target capacity is currently full.</p>
                        @else
                        <form method="POST" action="{{ route('projects.apply', $project) }}" class="space-y-3">
                            @csrf
                            <div>
                                <label class="block text-[11px] text-slate-400 mb-1">Role You Wish to Take *</label>
                                <input type="text" name="preferred_role" required placeholder="Enter preferred role"
                                       class="w-full bg-[#0B1120] border border-[#334155] rounded-xl px-3 py-2 text-xs text-white">
                            </div>

                            <div>
                                <label class="block text-[11px] text-slate-400 mb-1">Motivation & Contribution *</label>
                                <textarea name="motivation" rows="3" required placeholder="Enter motivation and contribution statement"
                                          class="w-full bg-[#0B1120] border border-[#334155] rounded-xl p-3 text-xs text-white"></textarea>
                            </div>

                            <div>
                                <label class="block text-[11px] text-slate-400 mb-1">Relevant Skills</label>
                                <input type="text" name="skills_summary" placeholder="Enter relevant technical skills"
                                       class="w-full bg-[#0B1120] border border-[#334155] rounded-xl px-3 py-2 text-xs text-white">
                            </div>

                            <button type="submit" class="btn-primary text-xs py-2 px-4 w-full shadow-lg">Submit Application</button>
                        </form>
                        @endif
                    </div>
                    @endif

                </div>
            </div>

        </div>
    </div>
</x-app-layout>
