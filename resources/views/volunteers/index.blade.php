<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-cyan-500/20 text-cyan-300 border border-cyan-500/30">
                    Community Service & Leadership
                </span>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-[#F8FAFC] font-brand-display tracking-tight mt-1">
                    Volunteer Opportunities Board
                </h1>
                <p class="text-xs text-[#94A3B8] mt-0.5">
                    Contribute your skills to COMSOC event organizing, technical desk support, logistics, and outreach to earn verified service hours and EXP.
                </p>
            </div>

            @if(auth()->user()->isAdmin() || auth()->user()->isStaff())
            <a href="{{ route('volunteers.create') }}" class="btn-primary text-xs flex items-center gap-1.5 shadow-lg">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span>Publish Volunteer Opening</span>
            </a>
            @endif
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

            <!-- Service Hours Banner -->
            <div class="card bg-[#172033] border border-cyan-500/40 p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-xl">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-cyan-500/20 text-cyan-400 border border-cyan-500/30 flex items-center justify-center shrink-0">
                        <i data-lucide="award" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-white font-brand-display">Verified Volunteer Service</h3>
                        <p class="text-xs text-[#94A3B8]">Approved hours are verified by designated coordinators and eligible for university certificates and EXP rewards.</p>
                    </div>
                </div>

                <div class="flex items-center gap-4 shrink-0">
                    <div class="text-right">
                        <span class="text-xs text-[#94A3B8] block">Your Approved Hours</span>
                        <span class="text-2xl font-black text-cyan-300 font-brand-display">{{ number_format($verifiedHoursSum, 1) }} hrs</span>
                    </div>
                </div>
            </div>

            <!-- Tab Navigation & Filters -->
            <div class="card bg-[#172033] border border-[#334155] p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-2 border-b sm:border-b-0 border-[#334155] pb-3 sm:pb-0 overflow-x-auto">
                    <a href="{{ route('volunteers.index', ['tab' => 'available']) }}"
                       class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition {{ $tab === 'available' ? 'bg-[#560E0F] text-white shadow-md' : 'text-[#94A3B8] hover:text-white hover:bg-[#1E293B]' }}">
                        Open Opportunities
                    </a>
                    <a href="{{ route('volunteers.index', ['tab' => 'my_applications']) }}"
                       class="px-3.5 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition {{ $tab === 'my_applications' ? 'bg-[#560E0F] text-white shadow-md' : 'text-[#94A3B8] hover:text-white hover:bg-[#1E293B]' }}">
                        My Applications & Shifts
                    </a>
                </div>

                <form method="GET" action="{{ route('volunteers.index') }}" class="flex flex-wrap items-center gap-2">
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search title or role..."
                           class="bg-[#0B1120] text-xs text-white border border-[#334155] rounded-xl px-3 py-1.5">

                    <select name="category" onchange="this.form.submit()" class="bg-[#0B1120] text-xs text-white border border-[#334155] rounded-xl px-3 py-1.5">
                        <option value="all">All Categories</option>
                        @foreach(\App\Models\VolunteerOpportunity::CATEGORIES as $k => $label)
                        <option value="{{ $k }}" {{ $category === $k ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>

                    <button type="submit" class="btn-primary text-xs py-1.5 px-3">Filter</button>
                    @if($search || $category !== 'all')
                    <a href="{{ route('volunteers.index', ['tab' => $tab]) }}" class="text-xs text-[#df747a] hover:underline">Clear</a>
                    @endif
                </form>
            </div>

            <!-- Opportunities Cards -->
            @if($opportunities->isEmpty())
            <div class="card bg-[#172033] border border-[#334155] p-12 text-center">
                <i data-lucide="heart-handshake" class="w-12 h-12 mx-auto text-[#94A3B8] mb-3"></i>
                <h3 class="text-sm font-bold text-white font-brand-display">No volunteer openings found</h3>
                <p class="text-xs text-[#94A3B8] mt-1">Check back before upcoming major events and symposiums.</p>
            </div>
            @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($opportunities as $opp)
                @php
                    $myApp = $myApplications[$opp->id] ?? null;
                    $isFull = $opp->accepted_applications_count >= $opp->capacity;
                @endphp
                <div class="card bg-[#172033] border border-[#334155] hover:border-cyan-500/40 p-5 rounded-2xl shadow-xl flex flex-col justify-between transition group">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-cyan-500/10 text-cyan-400 border border-cyan-500/20">
                                {{ $opp->category_label }}
                            </span>
                            <span class="text-[11px] text-[#df747a] font-bold">
                                +{{ $opp->exp_reward_per_hour * $opp->expected_hours }} EXP
                            </span>
                        </div>

                        <a href="{{ route('volunteers.show', $opp) }}" class="block">
                            <h3 class="text-base font-bold text-white group-hover:text-cyan-400 transition-colors font-brand-display line-clamp-1">
                                {{ $opp->title }}
                            </h3>
                        </a>

                        <p class="text-xs text-slate-300 mt-2 line-clamp-2 leading-relaxed">
                            {{ $opp->description }}
                        </p>

                        <div class="mt-3 space-y-1.5 text-xs text-slate-400">
                            <div class="flex items-center gap-1.5">
                                <i data-lucide="clock" class="w-3.5 h-3.5 text-cyan-400"></i>
                                <span>{{ $opp->expected_hours }} Expected Service Hours</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400"></i>
                                <span>{{ $opp->starts_at->format('M d, Y H:i') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 mt-4 border-t border-[#334155]/60 flex items-center justify-between">
                        <div class="text-xs text-slate-400">
                            <span>{{ $opp->accepted_applications_count }} / {{ $opp->capacity }} Slots</span>
                        </div>

                        @if($myApp)
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase
                            {{ $myApp->status === 'completed' ? 'bg-cyan-500/20 text-cyan-300' : ($myApp->status === 'accepted' ? 'bg-emerald-500/20 text-emerald-300' : 'bg-amber-500/20 text-amber-300') }}">
                            {{ $myApp->status_label }}
                        </span>
                        @elseif($isFull)
                        <span class="text-xs text-rose-400 font-bold">Slots Filled</span>
                        @else
                        <a href="{{ route('volunteers.show', $opp) }}" class="btn-primary text-[11px] py-1 px-3">
                            Apply
                        </a>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $opportunities->links() }}
            </div>
            @endif

        </div>
    </div>
</x-app-layout>
