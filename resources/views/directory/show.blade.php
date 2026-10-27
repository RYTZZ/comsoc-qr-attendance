<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('directory.index') }}" class="btn-secondary p-2 rounded-xl text-slate-400 hover:text-white">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    @php
                        $vis = $member->profile?->visibility ?? ($member->profile?->is_public ? 'public' : 'private');
                        $isPrivileged = auth()->id() === $member->id || auth()->user()?->isAdmin() || auth()->user()?->isSuperAdmin();
                        $publicIdentity = $member->display_username;
                    @endphp
                    @if($vis === 'public')
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-cyan-500/20 border border-cyan-500/40 text-cyan-300">
                            Public Profile
                        </span>
                    @elseif($vis === 'members_only')
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-[#560E0F]/40 border border-[#8F3033]/60 text-[#df747a]">
                            Members Only
                        </span>
                    @else
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-slate-800 border border-slate-700 text-slate-300">
                            Private Profile
                        </span>
                    @endif
                    <span class="text-xs text-slate-400">Computing Society Member</span>
                </div>
                <h1 class="text-2xl font-bold text-white font-brand-display tracking-tight mt-0.5">
                    {{ $publicIdentity }}
                </h1>
                @if($isPrivileged)
                    <p class="text-xs text-slate-400 mt-0.5 flex items-center gap-1.5">
                        <span class="text-slate-300 font-semibold">{{ $member->name }}</span>
                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-slate-800 border border-slate-700 text-slate-400 font-mono">Official Record</span>
                    </p>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="card bg-[#172033] border border-slate-800 rounded-2xl p-6 sm:p-8 space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6 pb-6 border-b border-slate-800">
                    <div class="flex items-center gap-4">
                        <x-user-avatar :user="$member" size="xl" class="border border-[#560E0F]/50 shadow-lg" />

                        <div>
                            <h2 class="text-xl font-bold text-white font-brand-display">{{ $publicIdentity }}</h2>
                            @if($isPrivileged)
                                <p class="text-xs text-slate-400 font-medium mt-0.5 flex items-center gap-1.5">
                                    <span>Official Record: {{ $member->name }}</span>
                                    @if($isPrivileged && $member->student)
                                        <span class="font-mono text-[11px] text-amber-400">({{ $member->student->student_number }})</span>
                                    @endif
                                </p>
                            @endif
                            <p class="text-xs text-slate-400 mt-0.5">
                                {{ $member->student ? "{$member->student->program} • {$member->student->year_level}" . ($isPrivileged ? " (Block {$member->student->block})" : "") : 'COMSOC Member' }}
                            </p>
                            @if($member->profile?->open_to_collaborate)
                                <div class="mt-2 inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                    <span>Open to Team Collaborations</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        @if($member->profile?->github_url)
                            <a href="{{ $member->profile->github_url }}" target="_blank"
                               class="btn-secondary text-xs py-2 px-3 flex items-center gap-1.5">
                                <i data-lucide="github" class="w-4 h-4"></i>
                                <span>GitHub</span>
                            </a>
                        @endif

                        @if($member->profile?->linkedin_url)
                            <a href="{{ $member->profile->linkedin_url }}" target="_blank"
                               class="btn-secondary text-xs py-2 px-3 flex items-center gap-1.5">
                                <i data-lucide="linkedin" class="w-4 h-4"></i>
                                <span>LinkedIn</span>
                            </a>
                        @endif
                    </div>
                </div>

                @if($member->profile?->bio)
                    <div>
                        <h3 class="text-xs uppercase font-bold text-slate-400 tracking-wider mb-2">About / Bio</h3>
                        <p class="text-sm text-slate-300 leading-relaxed max-w-3xl">
                            {{ $member->profile->bio }}
                        </p>
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-slate-800">
                    <div class="space-y-4">
                        <div>
                            <h3 class="text-xs uppercase font-bold text-slate-400 tracking-wider mb-2.5">Technical Skills</h3>
                            @if($member->skills->isEmpty())
                                <p class="text-xs text-slate-500">No technical skills added yet.</p>
                            @else
                                <div class="flex flex-wrap gap-2">
                                    @foreach($member->skills as $skill)
                                        <span class="px-3 py-1 rounded-xl bg-slate-900 border border-slate-700 text-xs font-semibold text-slate-200">
                                            {{ $skill->name }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        @if(!empty($member->profile?->tech_stack))
                            <div>
                                <h3 class="text-xs uppercase font-bold text-slate-400 tracking-wider mb-2.5">Languages & Technology Stack</h3>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($member->profile->tech_stack as $tech)
                                        <span class="px-2.5 py-1 rounded-lg bg-cyan-500/10 border border-cyan-500/30 text-cyan-300 text-xs font-medium">
                                            {{ $tech }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if(!empty($member->profile?->project_categories))
                            <div>
                                <h3 class="text-xs uppercase font-bold text-slate-400 tracking-wider mb-2.5">Preferred Project Domains</h3>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($member->profile->project_categories as $domain)
                                        <span class="px-2.5 py-1 rounded-lg bg-indigo-500/10 border border-indigo-500/30 text-indigo-300 text-xs font-medium">
                                            {{ $domain }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="space-y-4">
                        @if(!empty($member->profile?->interests))
                            <div>
                                <h3 class="text-xs uppercase font-bold text-slate-400 tracking-wider mb-2.5">Areas of Interest</h3>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($member->profile->interests as $interest)
                                        <span class="px-2.5 py-1 rounded-lg bg-slate-800 border border-slate-700 text-xs text-slate-300">
                                            {{ $interest }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if(!empty($member->profile?->collaboration_preferences))
                            <div>
                                <h3 class="text-xs uppercase font-bold text-slate-400 tracking-wider mb-2.5">Collaboration Styles</h3>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($member->profile->collaboration_preferences as $style)
                                        <span class="px-2.5 py-1 rounded-lg bg-amber-500/10 border border-amber-500/30 text-amber-300 text-xs font-medium">
                                            {{ $style }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div>
                            <h3 class="text-xs uppercase font-bold text-slate-400 tracking-wider mb-2.5">Unlocked Badges</h3>
                            @if($member->achievements->isEmpty())
                                <p class="text-xs text-slate-500">No achievements unlocked yet.</p>
                            @else
                                <div class="grid grid-cols-2 gap-2">
                                    @foreach($member->achievements as $ach)
                                        <div class="p-2.5 rounded-xl bg-slate-900 border border-slate-800 flex items-center gap-2.5">
                                            <div class="w-7 h-7 rounded-lg bg-amber-500/20 text-amber-400 flex items-center justify-center shrink-0">
                                                <i data-lucide="{{ $ach->icon ?: 'award' }}" class="w-4 h-4"></i>
                                            </div>
                                            <span class="text-xs font-bold text-white truncate">{{ $ach->title }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
</x-app-layout>
