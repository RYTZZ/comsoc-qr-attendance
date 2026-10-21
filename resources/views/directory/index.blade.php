<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-cyan-500/20 border border-cyan-500/40 text-cyan-300">
                        Networking & Skills
                    </span>
                    <span class="text-xs text-slate-400">Collaborator Discovery</span>
                </div>
                <h1 class="text-2xl font-bold text-white font-brand-display tracking-tight mt-1">
                    Member Directory & Skill Matching
                </h1>
                <p class="text-xs text-slate-400 mt-0.5">
                    Connect with fellow Computing Society members, explore tech skills, and find teammates for hackathons and projects.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('directory.profile.edit') }}" class="btn-primary text-xs flex items-center gap-1.5 py-2 px-3.5">
                    <i data-lucide="user-cog" class="w-4 h-4"></i>
                    <span>My Collaborator Profile</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="card bg-[#172033] border border-slate-800 p-4">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('directory.index', ['tab' => 'directory', 'skill_id' => $selectedSkill, 'project_category' => $selectedCategory, 'program' => $selectedProgram, 'search' => $search]) }}"
                           class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-colors flex items-center gap-2 {{ $tab === 'directory' ? 'bg-[#560E0F] text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                            <i data-lucide="users" class="w-3.5 h-3.5"></i>
                            <span>All Members</span>
                        </a>

                        <a href="{{ route('directory.index', ['tab' => 'matches']) }}"
                           class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-colors flex items-center gap-2 {{ $tab === 'matches' ? 'bg-[#560E0F] text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                            <i data-lucide="sparkles" class="w-3.5 h-3.5 text-amber-400"></i>
                            <span>Collaborator Matches</span>
                        </a>
                    </div>

                    @if($tab === 'directory')
                        <form method="GET" action="{{ route('directory.index') }}" class="flex flex-wrap items-center gap-2">
                            <input type="hidden" name="tab" value="directory">

                            <div class="relative w-full sm:w-44">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
                                    <i data-lucide="search" class="w-3.5 h-3.5"></i>
                                </span>
                                <input type="text" name="search" value="{{ $search }}"
                                       placeholder="Enter member search"
                                       class="input-dark pl-9 pr-3 py-1.5 text-xs w-full">
                            </div>

                            <div class="w-36">
                                @php
                                    $programOptions = [['value' => 'all', 'label' => 'All Programs']];
                                    foreach($programs as $p) {
                                        $val = $p->abbreviation ?: ($p->name ?? '');
                                        $programOptions[] = ['value' => $val, 'label' => $p->display_name ?: $val];
                                    }
                                @endphp
                                <x-custom-dropdown
                                    name="program"
                                    :options="$programOptions"
                                    :selected="$selectedProgram"
                                    placeholder="Select your program"
                                    onchange="this.closest('form').submit()"
                                />
                            </div>

                            <div class="w-36">
                                @php
                                    $yearOptions = [['value' => 'all', 'label' => 'All Years']];
                                    foreach($yearLevels as $yl) {
                                        $yearOptions[] = ['value' => $yl, 'label' => $yl];
                                    }
                                @endphp
                                <x-custom-dropdown
                                    name="year_level"
                                    :options="$yearOptions"
                                    :selected="$selectedYearLevel"
                                    placeholder="Select your year level"
                                    onchange="this.closest('form').submit()"
                                />
                            </div>

                            <div class="w-36">
                                @php
                                    $skillOptions = [['value' => 'all', 'label' => 'All Skills']];
                                    foreach($skills as $s) {
                                        $skillOptions[] = ['value' => $s->id, 'label' => $s->name];
                                    }
                                @endphp
                                <x-custom-dropdown
                                    name="skill_id"
                                    :options="$skillOptions"
                                    :selected="$selectedSkill"
                                    placeholder="Select a skill"
                                    onchange="this.closest('form').submit()"
                                />
                            </div>

                            <div class="w-40">
                                @php
                                    $catOptions = [['value' => 'all', 'label' => 'All Domains']];
                                    foreach($projectCategories as $pc) {
                                        $catOptions[] = ['value' => $pc, 'label' => $pc];
                                    }
                                @endphp
                                <x-custom-dropdown
                                    name="project_category"
                                    :options="$catOptions"
                                    :selected="$selectedCategory"
                                    placeholder="Project Domain"
                                    onchange="this.closest('form').submit()"
                                />
                            </div>

                            <button type="submit" class="btn-primary text-xs py-1.5 px-3">
                                Filter
                            </button>

                            @if($search || ($selectedSkill && $selectedSkill !== 'all') || ($selectedCategory && $selectedCategory !== 'all') || ($selectedProgram && $selectedProgram !== 'all') || ($selectedYearLevel && $selectedYearLevel !== 'all'))
                                <a href="{{ route('directory.index', ['tab' => 'directory']) }}" class="btn-secondary text-xs py-1.5 px-2.5">
                                    Reset
                                </a>
                            @endif
                        </form>
                    @endif
                </div>
            </div>

            @if($tab === 'directory')
                @if($members->isEmpty())
                    <div class="card bg-[#172033] border border-slate-800 p-12 text-center">
                        <div class="w-14 h-14 mx-auto rounded-full bg-slate-800/80 border border-slate-700 flex items-center justify-center text-slate-500 mb-3">
                            <i data-lucide="users" class="w-7 h-7"></i>
                        </div>
                        <h3 class="text-base font-bold text-white font-brand-display">No Members Found</h3>
                        <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                            No public member profiles matched your search or skill criteria. Try clearing your filters!
                        </p>
                    </div>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        @foreach($members as $member)
                            @php
                                $prof = $member->profile;
                                $stud = $member->student;
                            @endphp
                            <div class="card bg-[#172033] border border-slate-800 rounded-2xl p-5 hover:border-[#560E0F]/50 transition-all duration-200 flex flex-col justify-between space-y-4 group">
                                <div class="space-y-3">
                                    <div class="flex items-start justify-between gap-3">
                                        <x-user-avatar :user="$member" size="lg" class="border border-[#560E0F]/40 shadow-sm" />

                                        <div class="text-right">
                                            @if($prof && $prof->open_to_collaborate)
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                                    Open to Collab
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <div>
                                        <h3 class="text-sm font-bold text-white group-hover:text-rose-300 transition-colors line-clamp-1">
                                            {{ $member->username ? '@' . $member->username : $member->public_name }}
                                        </h3>
                                        <span class="text-[11px] text-slate-400 block font-mono">
                                            {{ $stud ? "{$stud->program} • {$stud->year_level}" : 'Computing Society' }}
                                        </span>

                                        @if($prof && $prof->bio)
                                            <p class="text-xs text-slate-400 mt-2 line-clamp-2 leading-relaxed">
                                                {{ $prof->bio }}
                                            </p>
                                        @endif
                                    </div>

                                    @if($member->skills->isNotEmpty())
                                        <div class="pt-2">
                                            <div class="flex flex-wrap gap-1.5">
                                                @foreach($member->skills->take(4) as $sk)
                                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-medium bg-slate-800 text-slate-300 border border-slate-700/60">
                                                        {{ $sk->name }}
                                                    </span>
                                                @endforeach
                                                @if($member->skills->count() > 4)
                                                    <span class="px-1.5 py-0.5 rounded-md text-[10px] text-slate-500 font-mono">
                                                        +{{ $member->skills->count() - 4 }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <div class="pt-3 border-t border-slate-800/80 flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        @if($prof && $prof->github_url)
                                            <a href="{{ $prof->github_url }}" target="_blank" class="text-slate-400 hover:text-white transition-colors" title="GitHub">
                                                <i data-lucide="github" class="w-4 h-4"></i>
                                            </a>
                                        @endif
                                        @if($prof && $prof->linkedin_url)
                                            <a href="{{ $prof->linkedin_url }}" target="_blank" class="text-slate-400 hover:text-white transition-colors" title="LinkedIn">
                                                <i data-lucide="linkedin" class="w-4 h-4"></i>
                                            </a>
                                        @endif
                                    </div>

                                    <a href="{{ route('directory.show', $member) }}" class="btn-primary text-xs py-1.5 px-3 flex items-center gap-1">
                                        <span>View Profile</span>
                                        <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="p-4 border-t border-slate-800">
                        {{ $members->appends(['tab' => 'directory', 'search' => $search, 'skill_id' => $selectedSkill, 'project_category' => $selectedCategory, 'program' => $selectedProgram, 'year_level' => $selectedYearLevel])->links() }}
                    </div>
                @endif
            @else
                <div class="space-y-6">
                    <div class="card bg-[#172033] border border-amber-500/30 p-6 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <i data-lucide="sparkles" class="w-5 h-5 text-amber-400"></i>
                                <h2 class="text-base font-bold text-white font-brand-display">Smart Collaborator Recommendations</h2>
                            </div>
                            <p class="text-xs text-slate-400 mt-1">
                                Suggested peers computed through transparent matching rules (shared tech stack, complementary skills, project domains, and collaboration style).
                            </p>
                        </div>

                        <a href="{{ route('directory.profile.edit') }}" class="btn-secondary text-xs py-2 px-3 flex items-center gap-1.5 shrink-0">
                            <i data-lucide="sliders" class="w-3.5 h-3.5"></i>
                            <span>Update Preferences</span>
                        </a>
                    </div>

                    @if($matches->isEmpty())
                        <div class="card bg-[#172033] border border-slate-800 p-12 text-center">
                            <div class="w-12 h-12 mx-auto rounded-full bg-slate-800/80 border border-slate-700 flex items-center justify-center text-slate-500 mb-3">
                                <i data-lucide="user-search" class="w-6 h-6"></i>
                            </div>
                            <h3 class="text-sm font-bold text-white">No Collaborator Matches Yet</h3>
                            <p class="text-xs text-slate-400 mt-1 max-w-md mx-auto">
                                To receive personalized teammate recommendations, make sure your profile has your technical skills, interests, and project categories filled out!
                            </p>
                            <div class="mt-4">
                                <a href="{{ route('directory.profile.edit') }}" class="btn-primary text-xs py-2 px-4">
                                    Configure My Profile
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach($matches as $item)
                                @php
                                    $cand = $item['user'];
                                    $prof = $cand->profile;
                                    $reasons = $item['match_reasons'];
                                @endphp
                                <div class="card bg-[#172033] border border-amber-500/20 p-5 rounded-2xl flex flex-col justify-between space-y-4 hover:border-amber-500/50 transition-colors">
                                    <div class="space-y-3">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="flex items-center gap-3">
                                                <x-user-avatar :user="$cand" size="md" class="border border-amber-500/40 shadow-sm" />
                                                <div>
                                                    <h3 class="text-sm font-bold text-white">{{ $cand->username ? '@' . $cand->username : $cand->public_name }}</h3>
                                                    <span class="text-[10px] text-slate-400 font-mono">
                                                        {{ $cand->student?->program ?? 'Computing Society' }}
                                                    </span>
                                                </div>
                                            </div>

                                            <span class="px-2.5 py-1 rounded-lg bg-amber-500/20 text-amber-300 font-extrabold text-xs border border-amber-500/30">
                                                {{ $item['score'] }} pts match
                                            </span>
                                        </div>

                                        <div class="p-3 rounded-xl bg-slate-900/80 border border-slate-800 space-y-1.5">
                                            <span class="text-[10px] uppercase font-bold text-amber-400/90 tracking-wider block">Why Recommended:</span>
                                            @foreach($reasons as $reason)
                                                <div class="text-xs text-slate-300 flex items-start gap-1.5">
                                                    <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-400 shrink-0 mt-0.5"></i>
                                                    <span>{{ $reason }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div class="pt-3 border-t border-slate-800/80 flex items-center justify-between">
                                        <div class="flex items-center gap-1.5">
                                            @foreach($cand->skills->take(3) as $sk)
                                                <span class="px-2 py-0.5 rounded text-[10px] bg-slate-800 text-slate-400">
                                                    {{ $sk->name }}
                                                </span>
                                            @endforeach
                                        </div>

                                        <a href="{{ route('directory.show', $cand) }}" class="btn-primary text-xs py-1.5 px-3 flex items-center gap-1">
                                            <span>Profile</span>
                                            <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
