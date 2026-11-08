<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-[#560E0F]/40 border border-[#560E0F] text-[#dfa6a9]">
                        COMSOC Hub
                    </span>
                    <span class="text-xs text-slate-400">Workshops · IDEATHON · Hackathons · Multi-Session Activities</span>
                </div>
                <h1 class="text-2xl font-bold text-white font-brand-display tracking-tight mt-1">
                    Activities & Multi-Session Events
                </h1>
                <p class="text-xs text-slate-400 mt-0.5">
                    Select sessions, register individually or as a team, attend via QR, and earn session EXP and Points.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('activities.my-registrations') }}" class="btn-primary text-xs flex items-center gap-1.5">
                    <i data-lucide="clipboard-check" class="w-4 h-4"></i>
                    <span>My Registrations & Rewards</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Pending Team Invitations Alert -->
            @if($pendingInvitations->isNotEmpty())
                <div class="card bg-[#172033] border border-amber-500/40 p-4 space-y-3">
                    <div class="flex items-center gap-2 text-amber-300 font-bold text-xs uppercase tracking-wider">
                        <i data-lucide="users" class="w-4 h-4"></i>
                        <span>Pending Team Invitations ({{ $pendingInvitations->count() }})</span>
                    </div>
                    <div class="space-y-2">
                        @foreach($pendingInvitations as $invite)
                            <div class="p-3 rounded-xl bg-[#0B1120] border border-slate-800 flex flex-wrap items-center justify-between gap-3 text-xs">
                                <div>
                                    <span class="font-bold text-white">{{ $invite->team?->name }}</span>
                                    <span class="text-slate-400">for</span>
                                    <a href="{{ route('activities.show', $invite->team?->activity) }}" class="text-[#dfa6a9] font-semibold hover:underline">
                                        {{ $invite->team?->activity?->title }}
                                    </a>
                                    <span class="text-slate-500 ml-1">(Invited by {{ $invite->inviter?->name }})</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <form method="POST" action="{{ route('activities.teams.invitations.respond', $invite) }}">
                                        @csrf
                                        <input type="hidden" name="decision" value="accepted">
                                        <button type="submit" class="btn-primary btn-sm text-xs">Accept Invitation</button>
                                    </form>
                                    <form method="POST" action="{{ route('activities.teams.invitations.respond', $invite) }}">
                                        @csrf
                                        <input type="hidden" name="decision" value="rejected">
                                        <button type="submit" class="btn-secondary btn-sm text-xs text-rose-300">Decline</button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Search & Category Filter -->
            <div class="card bg-[#172033] border border-slate-800 p-4">
                <form method="GET" action="{{ route('activities.index') }}" class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2 flex-1">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search workshops, hackathons, seminars..."
                               class="input text-xs bg-[#0B1120] border-slate-700 text-white w-full sm:w-72">
                        <select name="category" class="select text-xs bg-[#0B1120] border-slate-700 text-white w-full sm:w-56">
                            <option value="">All Activity Categories</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat }}" @selected(request('category') === $cat)>{{ $cat }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn-secondary text-xs py-2 px-4">Filter</button>
                        @if(request('search') || request('category'))
                            <a href="{{ route('activities.index') }}" class="text-xs text-slate-400 hover:text-white px-2">Clear</a>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Activities Grid -->
            @if($activities->isEmpty())
                <div class="card bg-[#172033] border border-slate-800 p-12 text-center">
                    <h3 class="text-base font-bold text-white">No activities found</h3>
                    <p class="text-xs text-slate-400 mt-1">Check back soon for upcoming COMSOC workshops, hackathons, and multi-session events.</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($activities as $act)
                        @php
                            $myReg = $userRegistrations[$act->id] ?? null;
                            $totalPossibleExp = $act->sessions->sum('exp_reward') + ($act->award_parent_rewards ? $act->parent_exp_reward : 0);
                            $totalPossiblePts = $act->sessions->sum('points_reward') + ($act->award_parent_rewards ? $act->parent_points_reward : 0);
                        @endphp
                        <div class="card bg-[#172033] border border-slate-800 flex flex-col justify-between overflow-hidden hover:border-slate-700 transition">
                            <div class="p-5 space-y-3">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="px-2.5 py-0.5 rounded-full bg-[#560E0F]/40 border border-[#560E0F] text-[#dfa6a9] text-[10px] font-bold uppercase">
                                        {{ $act->category }}
                                    </span>
                                    <span class="px-2 py-0.5 rounded bg-slate-800 border border-slate-700 text-[10px] text-slate-300 font-semibold">
                                        {{ $act->sessions_count }} Session(s)
                                    </span>
                                </div>

                                <div>
                                    <h3 class="text-base font-bold text-white leading-snug">{{ $act->title }}</h3>
                                    @if($act->description)
                                        <p class="text-xs text-slate-400 line-clamp-2 mt-1">{{ $act->description }}</p>
                                    @endif
                                </div>

                                <div class="space-y-1 text-xs text-slate-300 pt-1">
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400"></i>
                                        <span>{{ $act->starts_at ? $act->starts_at->format('M d, Y g:i A') : 'Schedule TBA' }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="map-pin" class="w-3.5 h-3.5 text-slate-400"></i>
                                        <span>{{ $act->venue ?: 'Venue TBA' }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="award" class="w-3.5 h-3.5 text-amber-400"></i>
                                        <span class="text-amber-300 font-semibold">Up to {{ $totalPossibleExp }} EXP · {{ $totalPossiblePts }} Points</span>
                                    </div>
                                </div>
                            </div>

                            <div class="px-5 py-3.5 bg-[#0B1120]/70 border-t border-slate-800 flex items-center justify-between">
                                @if($myReg && !in_array($myReg->status, ['cancelled', 'withdrawn'], true))
                                    <span class="px-2.5 py-1 rounded-full bg-emerald-500/20 border border-emerald-500/30 text-emerald-300 text-[10px] font-bold uppercase">
                                        Registered ({{ $myReg->status }})
                                    </span>
                                @else
                                    <span class="text-[11px] text-slate-400 capitalize">{{ $act->registration_mode }} mode</span>
                                @endif

                                <a href="{{ route('activities.show', $act) }}" class="btn-primary btn-sm text-xs">
                                    {{ $myReg ? 'View Details & Sessions' : 'Select Sessions & Register' }}
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $activities->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

