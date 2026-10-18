<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-indigo-500/20 border border-indigo-500/40 text-indigo-300">
                        Career & Growth
                    </span>
                    <span class="text-xs text-slate-400">External Tech Opportunities</span>
                </div>
                <h1 class="text-2xl font-bold text-white font-brand-display tracking-tight mt-1">
                    Tech Opportunities & Jobs
                </h1>
                <p class="text-xs text-slate-400 mt-0.5">
                    Explore curated internships, student fellowships, junior engineering roles, and freelance gigs from trusted industry partners.
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm flex items-center gap-3">
                    <i data-lucide="check-circle" class="w-5 h-5 flex-shrink-0"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <div class="card bg-[#172033] border border-slate-800 p-4">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('opportunities.index', ['tab' => 'all', 'job_type' => $selectedJobType, 'workplace' => $selectedWorkplace, 'search' => $search]) }}"
                           class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-colors flex items-center gap-2 {{ $tab === 'all' ? 'bg-[#560E0F] text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                            <i data-lucide="briefcase" class="w-3.5 h-3.5"></i>
                            <span>All Listings</span>
                        </a>

                        <a href="{{ route('opportunities.index', ['tab' => 'bookmarked']) }}"
                           class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-colors flex items-center gap-2 {{ $tab === 'bookmarked' ? 'bg-[#560E0F] text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                            <i data-lucide="bookmark" class="w-3.5 h-3.5 text-amber-400"></i>
                            <span>Saved Bookmarks ({{ count($myBookmarkIds) }})</span>
                        </a>
                    </div>

                    <form method="GET" action="{{ route('opportunities.index') }}" class="flex flex-wrap items-center gap-2">
                        <input type="hidden" name="tab" value="{{ $tab }}">

                        <div class="relative w-full sm:w-48">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
                                <i data-lucide="search" class="w-3.5 h-3.5"></i>
                            </span>
                            <input type="text" name="search" value="{{ $search }}"
                                   class="input-dark pl-9 pr-3 py-1.5 text-xs w-full">
                        </div>

                        <div class="w-40">
                            @php
                                $typeOpts = [['value' => 'all', 'label' => 'All Role Types']];
                                foreach($employmentTypes as $et) {
                                    $typeOpts[] = ['value' => $et, 'label' => $et];
                                }
                            @endphp
                            <x-custom-dropdown
                                name="job_type"
                                :options="$typeOpts"
                                :selected="$selectedJobType"
                                placeholder="Role Type"
                                onchange="this.closest('form').submit()"
                            />
                        </div>

                        <div class="w-36">
                            <x-custom-dropdown
                                name="workplace"
                                :options="[
                                    ['value' => 'all', 'label' => 'All Locations'],
                                    ['value' => 'remote', 'label' => 'Remote Only'],
                                    ['value' => 'onsite', 'label' => 'Onsite / Hybrid'],
                                ]"
                                :selected="$selectedWorkplace"
                                placeholder="Workplace"
                                onchange="this.closest('form').submit()"
                            />
                        </div>

                        <button type="submit" class="btn-primary text-xs py-1.5 px-3">
                            Filter
                        </button>

                        @if($search || ($selectedJobType && $selectedJobType !== 'all') || ($selectedWorkplace && $selectedWorkplace !== 'all'))
                            <a href="{{ route('opportunities.index', ['tab' => $tab]) }}" class="btn-secondary text-xs py-1.5 px-2.5">
                                Reset
                            </a>
                        @endif
                    </form>
                </div>
            </div>

            @if($jobs->isEmpty())
                <div class="card bg-[#172033] border border-slate-800 p-12 text-center">
                    <div class="w-14 h-14 mx-auto rounded-full bg-slate-800/80 border border-slate-700 flex items-center justify-center text-slate-500 mb-3">
                        <i data-lucide="briefcase" class="w-7 h-7"></i>
                    </div>
                    <h3 class="text-base font-bold text-white font-brand-display">No Opportunities Found</h3>
                    <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                        No tech listings match your current filters. Check back soon as officers post verified industry opportunities!
                    </p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($jobs as $job)
                        @php
                            $isSaved = in_array($job->id, $myBookmarkIds);
                            $isExpired = $job->isExpired();
                        @endphp
                        <div class="card bg-[#172033] border {{ $job->is_verified ? 'border-slate-800 hover:border-indigo-500/50' : 'border-slate-800' }} rounded-2xl p-5 flex flex-col justify-between space-y-4 transition-all duration-200 group">
                            <div class="space-y-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-11 h-11 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400 shrink-0 font-extrabold text-sm">
                                            <i data-lucide="building" class="w-5 h-5"></i>
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-bold text-white group-hover:text-indigo-300 transition-colors line-clamp-1">
                                                {{ $job->title }}
                                            </h3>
                                            <span class="text-xs text-slate-400 font-medium">
                                                {{ $job->company }}
                                            </span>
                                        </div>
                                    </div>

                                    <form method="POST" action="{{ route('opportunities.bookmark', $job) }}">
                                        @csrf
                                        <button type="submit" class="p-2 rounded-xl {{ $isSaved ? 'text-amber-400 bg-amber-500/10 border border-amber-500/30' : 'text-slate-500 hover:text-white bg-slate-900 border border-slate-800' }} transition-colors" title="{{ $isSaved ? 'Remove bookmark' : 'Save bookmark' }}">
                                            <i data-lucide="bookmark" class="w-4 h-4 {{ $isSaved ? 'fill-amber-400' : '' }}"></i>
                                        </button>
                                    </form>
                                </div>

                                <div class="flex flex-wrap items-center gap-1.5 pt-1">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-300 border border-slate-700">
                                        {{ $job->job_type }}
                                    </span>

                                    @if($job->is_remote)
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                            Remote
                                        </span>
                                    @elseif($job->location)
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-slate-800 text-slate-400">
                                            {{ $job->location }}
                                        </span>
                                    @endif

                                    @if($job->is_verified)
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-cyan-500/20 text-cyan-300 border border-cyan-500/30 flex items-center gap-1">
                                            <i data-lucide="badge-check" class="w-3 h-3"></i>
                                            <span>Verified</span>
                                        </span>
                                    @endif
                                </div>

                                <p class="text-xs text-slate-400 line-clamp-3 leading-relaxed">
                                    {{ $job->description }}
                                </p>

                                @if(!empty($job->required_skills))
                                    <div class="flex flex-wrap gap-1.5 pt-1">
                                        @foreach(array_slice($job->required_skills, 0, 4) as $skill)
                                            <span class="px-2 py-0.5 rounded-md text-[10px] bg-slate-900 border border-slate-800 text-slate-400">
                                                {{ $skill }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <div class="pt-3 border-t border-slate-800/80 flex items-center justify-between gap-2">
                                <div>
                                    @if($job->deadline_at)
                                        <span class="text-[10px] {{ $isExpired ? 'text-rose-400 font-bold' : 'text-slate-400' }} block">
                                            {{ $isExpired ? 'Expired' : 'Deadline: ' . $job->deadline_at->format('M d, Y') }}
                                        </span>
                                    @else
                                        <span class="text-[10px] text-slate-500 block">Open Deadline</span>
                                    @endif
                                </div>

                                <a href="{{ route('opportunities.show', $job) }}" class="btn-primary text-xs py-1.5 px-3 flex items-center gap-1">
                                    <span>Details</span>
                                    <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="p-4 border-t border-slate-800">
                    {{ $jobs->appends(['tab' => $tab, 'search' => $search, 'job_type' => $selectedJobType, 'workplace' => $selectedWorkplace])->links() }}
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
