<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-[#560E0F]/30 border border-[#560E0F]/60 text-[#dfa6a9]">
                        Gamification
                    </span>
                    <span class="text-xs text-slate-400">Skill Challenges & Contribs</span>
                </div>
                <h1 class="text-2xl font-bold text-white font-brand-display tracking-tight mt-1">
                    Missions & Quests
                </h1>
                <p class="text-xs text-slate-400 mt-0.5">
                    Complete tasks, volunteer, develop skills, and submit evidence to earn seasonal XP and redeemable rewards.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('ledger.index') }}" class="btn-secondary text-xs flex items-center gap-1.5">
                    <i data-lucide="receipt" class="w-4 h-4"></i>
                    <span>Reward Ledger</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="card bg-[#172033] border border-slate-800 p-4">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('missions.index', ['tab' => 'available', 'category' => $category, 'search' => $search]) }}"
                           class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors {{ $tab === 'available' ? 'bg-[#560E0F] text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                            All Missions
                        </a>
                        <a href="{{ route('missions.index', ['tab' => 'in_progress', 'category' => $category, 'search' => $search]) }}"
                           class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors {{ $tab === 'in_progress' ? 'bg-[#560E0F] text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                            Under Review
                        </a>
                        <a href="{{ route('missions.index', ['tab' => 'completed', 'category' => $category, 'search' => $search]) }}"
                           class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors {{ $tab === 'completed' ? 'bg-[#560E0F] text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                            Completed
                        </a>
                        <a href="{{ route('missions.index', ['tab' => 'rejected', 'category' => $category, 'search' => $search]) }}"
                           class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors {{ $tab === 'rejected' ? 'bg-[#560E0F] text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                            Needs Revision
                        </a>
                    </div>

                    <form method="GET" action="{{ route('missions.index') }}" class="flex flex-wrap items-center gap-2">
                        <input type="hidden" name="tab" value="{{ $tab }}">

                        <div class="relative w-full sm:w-56">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
                                <i data-lucide="search" class="w-3.5 h-3.5"></i>
                            </span>
                            <input type="text" name="search" value="{{ $search }}"
                                   class="input-dark pl-9 pr-3 py-1.5 text-xs w-full">
                        </div>

                        <div class="w-full sm:w-44">
                            <x-custom-dropdown
                                name="category"
                                :options="array_merge([['value' => '', 'label' => 'All Categories']], $categories->map(fn($c) => ['value' => $c, 'label' => ucfirst($c)])->toArray())"
                                :selected="$category ?: ''"
                                placeholder="All Categories"
                            />
                        </div>

                        <button type="submit" class="btn-secondary text-xs py-1.5 px-3">
                            Filter
                        </button>
                        @if($search || $category)
                        <a href="{{ route('missions.index', ['tab' => $tab]) }}" class="text-xs text-slate-400 hover:text-white p-1.5">
                            Clear
                        </a>
                        @endif
                    </form>
                </div>
            </div>

            @if($missions->isEmpty())
                <div class="card bg-[#172033] border border-slate-800 p-12 text-center">
                    <div class="w-16 h-16 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-center mx-auto text-slate-600 mb-3">
                        <i data-lucide="target" class="w-8 h-8"></i>
                    </div>
                    <h3 class="text-base font-bold text-white font-brand-display">No missions found</h3>
                    <p class="text-xs text-slate-400 max-w-sm mx-auto mt-1">
                        There are currently no missions matching your selected criteria.
                    </p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($missions as $mission)
                        @php
                            $sub = $userSubmissions[$mission->id] ?? null;
                            $status = $sub ? $sub->status : 'open';
                        @endphp
                        <div class="card bg-[#172033] border border-slate-800 shadow-xl flex flex-col justify-between overflow-hidden group hover:border-slate-700 transition">
                            <div class="p-5 space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-slate-900 text-slate-300 border border-slate-800">
                                        {{ ucfirst($mission->category) }}
                                    </span>

                                    @if($status === 'approved')
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center gap-1">
                                            <i data-lucide="check" class="w-3 h-3"></i>
                                            <span>Completed</span>
                                        </span>
                                    @elseif($status === 'pending')
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/20 text-amber-400 border border-amber-500/30">
                                            Under Review
                                        </span>
                                    @elseif($status === 'rejected')
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/20 text-rose-400 border border-rose-500/30">
                                            Revision Needed
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-500/20 text-indigo-400 border border-indigo-500/30">
                                            Open Quest
                                        </span>
                                    @endif
                                </div>

                                <h3 class="text-base font-bold text-white font-brand-display group-hover:text-[#e69094] transition-colors line-clamp-1">
                                    {{ $mission->title }}
                                </h3>

                                <p class="text-xs text-slate-400 line-clamp-2 leading-relaxed">
                                    {{ $mission->description }}
                                </p>

                                <div class="pt-3 border-t border-slate-800/80 space-y-2 text-xs">
                                    <div class="flex items-center justify-between text-[11px]">
                                        <span class="text-slate-400">Quest Reward:</span>
                                        <span class="font-bold text-[#dfa6a9]">
                                            +{{ $mission->xp_reward }} XP • +{{ $mission->points_reward }} Points
                                        </span>
                                    </div>

                                    @if($mission->due_date)
                                    <div class="flex items-center gap-1.5 text-[11px] text-slate-400">
                                        <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-500"></i>
                                        <span>Due: {{ $mission->due_date->format('M d, Y') }}</span>
                                    </div>
                                    @endif

                                    <div class="flex items-center gap-1.5 text-[11px] text-slate-400">
                                        <i data-lucide="file-text" class="w-3.5 h-3.5 text-slate-500"></i>
                                        <span>Requires {{ ucfirst($mission->submission_type) }} Submission</span>
                                    </div>
                                </div>
                            </div>

                            <div class="p-5 pt-0">
                                <a href="{{ route('missions.show', $mission) }}" class="btn-primary w-full text-xs text-center py-2 block">
                                    {{ $status === 'open' ? 'View & Accept Quest' : 'View Mission Status' }}
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
