<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                    Collaborative Development
                </span>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-[#F8FAFC] font-brand-display tracking-tight mt-1">
                    Project Collaboration & Team Finder
                </h1>
                <p class="text-xs text-[#94A3B8] mt-0.5">
                    Connect with fellow COMSOC developers, designers, and tech enthusiasts. Form teams for hackathons, capstones, and community apps.
                </p>
            </div>

            <a href="{{ route('projects.create') }}" class="btn-primary text-xs flex items-center gap-1.5 shadow-lg">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span>Post Project Idea</span>
            </a>
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

            <!-- Tab Navigation & Filters -->
            <div class="card bg-[#172033] border border-[#334155] p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-2 border-b sm:border-b-0 border-[#334155] pb-3 sm:pb-0 overflow-x-auto">
                    <a href="{{ route('projects.index', ['tab' => 'discover']) }}"
                       class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition {{ $tab === 'discover' ? 'bg-[#560E0F] text-white shadow-md' : 'text-[#94A3B8] hover:text-white hover:bg-[#1E293B]' }}">
                        Discover Projects
                    </a>
                    <a href="{{ route('projects.index', ['tab' => 'my_projects']) }}"
                       class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition {{ $tab === 'my_projects' ? 'bg-[#560E0F] text-white shadow-md' : 'text-[#94A3B8] hover:text-white hover:bg-[#1E293B]' }}">
                        My Projects & Teams
                    </a>
                    <a href="{{ route('projects.index', ['tab' => 'applied']) }}"
                       class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition {{ $tab === 'applied' ? 'bg-[#560E0F] text-white shadow-md' : 'text-[#94A3B8] hover:text-white hover:bg-[#1E293B]' }}">
                        My Applications
                    </a>
                </div>

                <form method="GET" action="{{ route('projects.index') }}" class="flex flex-wrap items-center gap-2">
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search title or skills..."
                           class="bg-[#0B1120] text-xs text-white border border-[#334155] rounded-xl px-3 py-1.5">

                    <select name="category" onchange="this.form.submit()" class="bg-[#0B1120] text-xs text-white border border-[#334155] rounded-xl px-3 py-1.5">
                        <option value="all">All Categories</option>
                        @foreach(\App\Models\CollaborationProject::CATEGORIES as $k => $label)
                        <option value="{{ $k }}" {{ $category === $k ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>

                    <button type="submit" class="btn-primary text-xs py-1.5 px-3">Search</button>
                    @if($search || $category !== 'all')
                    <a href="{{ route('projects.index', ['tab' => $tab]) }}" class="text-xs text-[#df747a] hover:underline">Clear</a>
                    @endif
                </form>
            </div>

            <!-- Project Cards Grid -->
            @if($projects->isEmpty())
            <div class="card bg-[#172033] border border-[#334155] p-12 text-center">
                <i data-lucide="code-2" class="w-12 h-12 mx-auto text-[#94A3B8] mb-3"></i>
                <h3 class="text-sm font-bold text-white font-brand-display">No project listings found</h3>
                <p class="text-xs text-[#94A3B8] mt-1">Be the first to post a technology idea or hackathon team recruitment post.</p>
                <a href="{{ route('projects.create') }}" class="mt-4 inline-block btn-primary text-xs py-1.5 px-4 shadow-md">
                    Start a Project
                </a>
            </div>
            @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($projects as $proj)
                <div class="card bg-[#172033] border border-[#334155] hover:border-emerald-500/40 p-5 rounded-2xl shadow-xl flex flex-col justify-between transition group">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                {{ $proj->category_label }}
                            </span>
                            <span class="text-[10px] text-slate-400 font-semibold capitalize">
                                {{ $proj->stage_label }}
                            </span>
                        </div>

                        <a href="{{ route('projects.show', $proj) }}" class="block">
                            <h3 class="text-base font-bold text-white group-hover:text-emerald-400 transition-colors font-brand-display line-clamp-1">
                                {{ $proj->title }}
                            </h3>
                        </a>

                        <p class="text-xs text-slate-300 mt-2 line-clamp-2 leading-relaxed">
                            {{ $proj->problem_statement }}
                        </p>

                        <!-- Needed Skills Badges -->
                        @if(!empty($proj->skills_needed))
                        <div class="flex flex-wrap gap-1.5 mt-3">
                            @foreach(array_slice($proj->skills_needed, 0, 3) as $sk)
                            <span class="px-2 py-0.5 rounded bg-[#0B1120] text-slate-300 border border-[#334155] text-[10px]">
                                {{ $sk }}
                            </span>
                            @endforeach
                            @if(count($proj->skills_needed) > 3)
                            <span class="text-[10px] text-slate-500 self-center">+{{ count($proj->skills_needed) - 3 }}</span>
                            @endif
                        </div>
                        @endif
                    </div>

                    <div class="pt-4 mt-4 border-t border-[#334155]/60 flex items-center justify-between">
                        <div class="flex items-center gap-1.5 text-xs text-slate-400">
                            <i data-lucide="users" class="w-3.5 h-3.5 text-emerald-400"></i>
                            <span>{{ $proj->active_members_count }} / {{ $proj->team_size_target }} members</span>
                        </div>

                        <div class="flex items-center gap-2">
                            @if($proj->owner_id === auth()->id() || auth()->user()?->isAdmin() || auth()->user()?->isSuperAdmin() || auth()->user()?->isComsocAdmin())
                            <form method="POST" action="{{ route('projects.destroy', $proj) }}" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        data-confirm="Are you sure you want to delete '{{ $proj->title }}'? This cannot be undone."
                                        data-confirm-title="Delete Project Post"
                                        data-confirm-type="danger"
                                        data-confirm-btn="Delete"
                                        class="p-1 text-slate-500 hover:text-rose-400 rounded transition"
                                        title="Delete Project">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </form>
                            @endif
                            <a href="{{ route('projects.show', $proj) }}" class="btn-secondary text-[11px] py-1 px-3">
                                {{ $proj->is_recruiting ? 'Join Team' : 'View Team' }}
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $projects->links() }}
            </div>
            @endif

        </div>
    </div>
</x-app-layout>
