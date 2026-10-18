<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-burgundy-deep text-[#df747a] border border-[#8F3033]/60">
                        {{ $activeSeason ? $activeSeason->name : 'Active Semester' }}
                    </span>
                    <span class="text-xs text-[#94A3B8] font-mono">
                        {{ $student ? $student->student_number : $user->username }}
                    </span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                        Active Member
                    </span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-[#F8FAFC] font-brand-display tracking-tight mt-1">
                    Welcome back, {{ $student ? $student->first_name : $user->name }}!
                </h1>
                <p class="text-xs text-[#94A3B8] mt-0.5">
                    {{ $student ? "{$student->program} • Year {$student->year_level} (Block {$student->block})" : 'Computing Society Member' }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('member.dynamic_qr') }}" class="btn-primary text-xs flex items-center gap-1.5 shadow-lg">
                    <i data-lucide="qr-code" class="w-4 h-4"></i>
                    <span>Show My QR</span>
                </a>
                <a href="{{ route('helpdesk.index') }}" class="btn-secondary text-xs flex items-center gap-1.5">
                    <i data-lucide="life-buoy" class="w-4 h-4"></i>
                    <span>Help Desk</span>
                    @if($openTicketsCount > 0)
                    <span class="px-1.5 py-0.2 bg-amber-500/30 text-amber-300 rounded-full text-[10px] font-bold">{{ $openTicketsCount }}</span>
                    @endif
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-[#0B1120]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Section G: Quick Actions Shortcut Bar -->
            <div class="p-3 bg-[#172033] rounded-2xl border border-[#334155] shadow-lg flex items-center gap-2 overflow-x-auto scrollbar-none">
                <span class="text-[10px] uppercase font-bold tracking-wider text-[#94A3B8] px-2 whitespace-nowrap">Quick Actions</span>
                <a href="{{ route('member.dynamic_qr') }}" class="px-3 py-1.5 rounded-xl bg-[#0B1120] hover:bg-[#1E293B] border border-[#334155] text-xs font-semibold text-[#F8FAFC] flex items-center gap-1.5 whitespace-nowrap transition">
                    <i data-lucide="qr-code" class="w-3.5 h-3.5 text-[#df747a]"></i>
                    <span>Show My QR</span>
                </a>
                <a href="{{ route('events.index') }}" class="px-3 py-1.5 rounded-xl bg-[#0B1120] hover:bg-[#1E293B] border border-[#334155] text-xs font-semibold text-[#F8FAFC] flex items-center gap-1.5 whitespace-nowrap transition">
                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-blue-400"></i>
                    <span>Browse Events</span>
                </a>
                <a href="{{ route('ledger.index') }}" class="px-3 py-1.5 rounded-xl bg-[#0B1120] hover:bg-[#1E293B] border border-[#334155] text-xs font-semibold text-[#F8FAFC] flex items-center gap-1.5 whitespace-nowrap transition">
                    <i data-lucide="history" class="w-3.5 h-3.5 text-amber-400"></i>
                    <span>EXP History</span>
                </a>
                <a href="{{ route('shop.index') }}" class="px-3 py-1.5 rounded-xl bg-[#0B1120] hover:bg-[#1E293B] border border-[#334155] text-xs font-semibold text-[#F8FAFC] flex items-center gap-1.5 whitespace-nowrap transition">
                    <i data-lucide="gift" class="w-3.5 h-3.5 text-purple-400"></i>
                    <span>Browse Rewards</span>
                </a>
                <a href="{{ route('helpdesk.create') }}" class="px-3 py-1.5 rounded-xl bg-[#0B1120] hover:bg-[#1E293B] border border-[#334155] text-xs font-semibold text-[#F8FAFC] flex items-center gap-1.5 whitespace-nowrap transition">
                    <i data-lucide="message-square-plus" class="w-3.5 h-3.5 text-rose-400"></i>
                    <span>Submit Support Request</span>
                </a>
                <a href="{{ route('projects.index') }}" class="px-3 py-1.5 rounded-xl bg-[#0B1120] hover:bg-[#1E293B] border border-[#334155] text-xs font-semibold text-[#F8FAFC] flex items-center gap-1.5 whitespace-nowrap transition">
                    <i data-lucide="code-2" class="w-3.5 h-3.5 text-emerald-400"></i>
                    <span>Explore Projects</span>
                </a>
                <a href="{{ route('volunteers.index') }}" class="px-3 py-1.5 rounded-xl bg-[#0B1120] hover:bg-[#1E293B] border border-[#334155] text-xs font-semibold text-[#F8FAFC] flex items-center gap-1.5 whitespace-nowrap transition">
                    <i data-lucide="heart-handshake" class="w-3.5 h-3.5 text-cyan-400"></i>
                    <span>Volunteer Board</span>
                </a>
            </div>

            <!-- Section E: Important Announcements & Bulletins -->
            @if(isset($filteredAnnouncements) && $filteredAnnouncements->isNotEmpty())
            @php $topAnn = $filteredAnnouncements->first(); @endphp
            <div class="p-4 rounded-2xl bg-burgundy-deep border border-[#8F3033]/60 shadow-xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-start sm:items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-burgundy-gradient border border-[#8F3033]/50 flex items-center justify-center text-white flex-shrink-0 shadow-md">
                        <i data-lucide="megaphone" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-rose-500/20 text-rose-300 border border-rose-500/30">
                                {{ $topAnn->priority }} Notice
                            </span>
                            @if(!isset($readReceipts[$topAnn->id]))
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                Unread
                            </span>
                            @endif
                            <span class="text-xs text-[#94A3B8] font-medium">COMSOC Official Notice</span>
                        </div>
                        <h3 class="text-sm font-bold text-white mt-0.5 font-brand-display">{{ $topAnn->title }}</h3>
                        <p class="text-xs text-slate-300 line-clamp-1 mt-0.5">{{ $topAnn->body }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-2 flex-shrink-0">
                    <a href="{{ route('announcements.show', $topAnn) }}" class="btn-primary text-xs py-1.5 px-3 flex items-center gap-1.5">
                        <span>Read Notice</span>
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                    </a>
                </div>
            </div>
            @endif

            <!-- Section A & D: Level, Lifetime EXP, Spendable EXP & Milestone Progression -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div class="card bg-burgundy-deep border border-[#8F3033]/50 shadow-2xl p-5 relative overflow-hidden flex flex-col justify-between">
                    <div class="absolute -right-6 -bottom-6 w-32 h-32 bg-[#8F3033]/20 rounded-full blur-2xl pointer-events-none"></div>
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-bold text-[#df747a] uppercase tracking-wider">Level & Progression</span>
                            <span class="px-2.5 py-1 rounded-lg bg-burgundy-gradient text-white font-extrabold text-xs shadow-md border border-[#8F3033]/50 font-brand-display">
                                Level {{ $currentLevel->level }}
                            </span>
                        </div>
                        <div class="flex items-baseline gap-2 mb-2">
                            <span class="text-3xl font-black text-white font-brand-display tracking-tight tabular-nums">{{ number_format($lifetimeExp) }}</span>
                            <span class="text-xs font-bold text-[#df747a]">Lifetime EXP</span>
                        </div>
                        <p class="text-xs text-slate-200 font-semibold mb-3">{{ $currentLevel->title }}</p>

                        <div class="w-full bg-[#0B1120] rounded-full h-2.5 overflow-hidden border border-[#334155] mb-2 p-0.5">
                            <div class="bg-burgundy-gradient h-full rounded-full transition-all duration-500" style="width: {{ $levelProgress }}%"></div>
                        </div>
                        <div class="flex justify-between text-[11px] text-[#94A3B8]">
                            <span>{{ $levelProgress }}% to next rank</span>
                            <span class="font-medium text-slate-300">{{ $nextLevel ? number_format($nextLevel->min_xp - $lifetimeExp) . ' EXP needed' : 'Max Rank Reached' }}</span>
                        </div>
                    </div>

                    <div class="pt-3 mt-3 border-t border-[#334155]/60 flex items-center justify-between text-xs">
                        <span class="text-[11px] text-[#94A3B8]">Permanent Cumulative EXP</span>
                        <a href="{{ route('leaderboard.index', ['tab' => 'lifetime']) }}" class="text-[#df747a] hover:text-white font-semibold transition flex items-center gap-1">
                            <span>Rankings</span>
                            <i data-lucide="arrow-right" class="w-3 h-3"></i>
                        </a>
                    </div>
                </div>

                <div class="card bg-[#172033] border border-amber-500/40 shadow-xl p-5 relative overflow-hidden flex flex-col justify-between">
                    <div class="absolute -right-4 -bottom-4 w-28 h-28 bg-amber-500/10 rounded-full blur-2xl pointer-events-none"></div>
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-bold text-amber-400 uppercase tracking-wider">Spendable EXP</span>
                            <div class="w-7 h-7 rounded-lg bg-amber-500/20 text-amber-400 flex items-center justify-center border border-amber-500/30">
                                <i data-lucide="coins" class="w-4 h-4"></i>
                            </div>
                        </div>
                        <div class="flex items-baseline gap-2 mb-1">
                            <span class="text-3xl font-black text-amber-300 font-brand-display tracking-tight tabular-nums">{{ number_format($spendableExp) }}</span>
                            <span class="text-xs font-bold text-amber-400/80">Available EXP</span>
                        </div>
                        <p class="text-xs text-[#94A3B8] mb-3">Authoritative active balance available for reward redemption.</p>
                    </div>

                    <div class="pt-3 border-t border-[#334155] flex items-center justify-between">
                        <a href="{{ route('shop.index') }}" class="text-xs font-bold text-amber-400 hover:text-amber-300 flex items-center gap-1 transition">
                            <span>Redeem Rewards</span>
                            <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                        </a>
                        <a href="{{ route('ledger.index') }}" class="text-xs text-[#94A3B8] hover:text-slate-200 transition">
                            Ledger History
                        </a>
                    </div>
                </div>

                <div class="card bg-[#172033] border border-[#334155] shadow-xl p-5 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-bold text-[#94A3B8] uppercase tracking-wider">Engagement Milestones</span>
                            <a href="{{ route('leaderboard.index', ['tab' => 'achievements']) }}" class="text-[11px] font-bold text-[#df747a] hover:text-white flex items-center gap-1 transition">
                                <span>Badges</span>
                                <i data-lucide="arrow-right" class="w-3 h-3"></i>
                            </a>
                        </div>
                        <div class="flex items-baseline gap-2 mb-2">
                            <span class="text-3xl font-black text-white font-brand-display tracking-tight tabular-nums">{{ $userAchievementsCount }}</span>
                            <span class="text-xs text-[#94A3B8]">/ {{ max(1, $totalAchievements) }} Badges</span>
                        </div>
                        <div class="space-y-1 text-xs text-[#94A3B8]">
                            <p>Verified Scans: <span class="font-bold text-white">{{ $verifiedAttendanceCount }}</span></p>
                            <p>Approved Volunteer Hours: <span class="font-bold text-cyan-400">{{ number_format($approvedVolunteerHours, 1) }} hrs</span></p>
                            <p>Active Projects: <span class="font-bold text-emerald-400">{{ $myActiveProjectsCount }}</span></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2 pt-4 border-t border-[#334155]">
                        <a href="{{ route('missions.index') }}" class="bg-[#0B1120] rounded-xl p-2.5 border border-[#334155] text-center hover:border-[#8F3033]/60 transition block">
                            <span class="block text-xs text-[#94A3B8]">Active Missions</span>
                            <span class="text-base font-bold text-white font-brand-display">{{ $activeMissions->count() }}</span>
                        </a>
                        <a href="{{ route('leaderboard.index') }}" class="bg-[#0B1120] rounded-xl p-2.5 border border-[#334155] text-center hover:border-amber-500/50 transition block">
                            <span class="block text-xs text-[#94A3B8]">Leaderboard</span>
                            <span class="text-base font-bold text-amber-400 font-brand-display">Top Podium</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Main Dashboard Body -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left 2 Columns -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Section B: Upcoming Events with capacity and registered status -->
                    <div class="card bg-[#172033] border border-[#334155] shadow-xl p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2">
                                <i data-lucide="calendar" class="w-4 h-4 text-[#df747a]"></i>
                                <h2 class="text-sm font-bold text-white font-brand-display uppercase tracking-wider">Upcoming Events & Registrations</h2>
                            </div>
                            <a href="{{ route('events.index') }}" class="text-xs text-[#df747a] hover:text-white font-semibold">View All</a>
                        </div>

                        @if($upcomingEvents->isEmpty())
                        <div class="p-8 text-center border border-dashed border-[#334155] rounded-2xl">
                            <i data-lucide="calendar-x" class="w-8 h-8 mx-auto text-[#94A3B8] mb-2"></i>
                            <p class="text-sm text-slate-300 font-medium">No upcoming events published right now.</p>
                            <p class="text-xs text-[#94A3B8] mt-1">Check back soon for university symposiums, general assemblies, and coding fests.</p>
                        </div>
                        @else
                        <div class="space-y-3">
                            @foreach($upcomingEvents as $evt)
                            @php 
                                $isRegistered = isset($myEventRegistrationIds[$evt->id]);
                                $regStatus = $isRegistered ? $myEventRegistrationIds[$evt->id] : null;
                                $isFull = $evt->max_participants > 0 && $evt->event_registrations_count >= $evt->max_participants;
                            @endphp
                            <div class="p-4 rounded-xl bg-[#0B1120]/70 border border-[#334155] hover:border-[#8F3033]/60 transition flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-burgundy-deep text-[#df747a] border border-[#8F3033]/40">
                                            {{ $evt->event_type ?: 'Activity' }}
                                        </span>
                                        <span class="text-xs text-[#94A3B8]">
                                            {{ $evt->event_date ? $evt->event_date->format('M d, Y') : 'TBA' }}
                                        </span>
                                        @if($isRegistered)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                            Registered ({{ ucfirst($regStatus) }})
                                        </span>
                                        @elseif($isFull)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30">
                                            Capacity Filled
                                        </span>
                                        @endif
                                    </div>
                                    <h3 class="text-sm font-bold text-white font-brand-display">{{ $evt->name }}</h3>
                                    <p class="text-xs text-[#94A3B8] mt-0.5">
                                        {{ $evt->venue_name ?: $evt->location ?: 'Bulan Campus' }}
                                        @if($evt->max_participants > 0)
                                        • Capacity: {{ $evt->event_registrations_count }}/{{ $evt->max_participants }}
                                        @endif
                                    </p>
                                </div>
                                <div class="shrink-0 flex items-center gap-2">
                                    <a href="{{ route('events.show', $evt) }}" class="btn-primary text-xs py-1.5 px-3">
                                        {{ $isRegistered ? 'View Pass / Ticket' : 'View Details' }}
                                    </a>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>

                    <!-- Active Missions & Quests -->
                    <div class="card bg-[#172033] border border-[#334155] shadow-xl p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2">
                                <i data-lucide="target" class="w-4 h-4 text-amber-400"></i>
                                <h2 class="text-sm font-bold text-white font-brand-display uppercase tracking-wider">Active Missions & Quests</h2>
                            </div>
                            <span class="text-xs text-[#94A3B8]">{{ $activeMissions->count() }} active</span>
                        </div>

                        @if($activeMissions->isEmpty())
                        <div class="p-8 text-center border border-dashed border-[#334155] rounded-2xl">
                            <i data-lucide="sparkles" class="w-8 h-8 mx-auto text-[#94A3B8] mb-2"></i>
                            <p class="text-sm text-slate-300 font-medium">No open missions at this moment.</p>
                            <p class="text-xs text-[#94A3B8] mt-1">Officers will post new student engagement quests soon.</p>
                        </div>
                        @else
                        <div class="space-y-3">
                            @foreach($activeMissions as $msn)
                            <div class="p-4 rounded-xl bg-[#0B1120]/70 border border-[#334155] hover:border-[#8F3033]/60 transition flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                <div>
                                    <h3 class="text-sm font-bold text-white font-brand-display">{{ $msn->title }}</h3>
                                    <p class="text-xs text-[#94A3B8] mt-0.5 line-clamp-1">{{ $msn->description }}</p>
                                    <div class="flex items-center gap-3 mt-2 text-[11px]">
                                        <span class="text-[#df747a] font-bold">+{{ $msn->xp_reward }} XP</span>
                                        <span class="text-amber-400 font-bold">+{{ $msn->points_reward }} Points</span>
                                        @if($msn->due_date)
                                            <span class="text-slate-500">Due {{ $msn->due_date->format('M d') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="shrink-0">
                                    <a href="{{ route('missions.show', $msn) }}" class="btn-secondary text-xs py-1.5 px-3">
                                        View Quest
                                    </a>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>

                    <!-- Module 5 & 6 Highlights: Active Projects & Volunteer Opportunities -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <!-- Projects Finder -->
                        <div class="card bg-[#172033] border border-[#334155] shadow-xl p-5">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="code-2" class="w-4 h-4 text-emerald-400"></i>
                                    <h3 class="text-xs font-bold text-white uppercase tracking-wider">Project Collaboration</h3>
                                </div>
                                <a href="{{ route('projects.index') }}" class="text-[11px] text-emerald-400 hover:text-white font-bold">Browse</a>
                            </div>

                            @if($activeProjects->isEmpty())
                            <p class="text-xs text-[#94A3B8] text-center py-4">No recruiting student projects right now.</p>
                            @else
                            <div class="space-y-2.5">
                                @foreach($activeProjects as $prj)
                                <a href="{{ route('projects.show', $prj) }}" class="p-3 rounded-xl bg-[#0B1120]/70 border border-[#334155] hover:border-emerald-500/50 transition block">
                                    <div class="flex items-center justify-between mb-1">
                                        <h4 class="text-xs font-bold text-white line-clamp-1 font-brand-display">{{ $prj->title }}</h4>
                                        <span class="text-[9px] px-1.5 py-0.5 rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-bold shrink-0">
                                            {{ $prj->category_label }}
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-[#94A3B8] line-clamp-1">{{ $prj->problem_statement }}</p>
                                </a>
                                @endforeach
                            </div>
                            @endif
                        </div>

                        <!-- Volunteer Opportunities -->
                        <div class="card bg-[#172033] border border-[#334155] shadow-xl p-5">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="heart-handshake" class="w-4 h-4 text-cyan-400"></i>
                                    <h3 class="text-xs font-bold text-white uppercase tracking-wider">Volunteer Openings</h3>
                                </div>
                                <a href="{{ route('volunteers.index') }}" class="text-[11px] text-cyan-400 hover:text-white font-bold">Browse</a>
                            </div>

                            @if($activeVolunteerOpportunities->isEmpty())
                            <p class="text-xs text-[#94A3B8] text-center py-4">No volunteer openings open right now.</p>
                            @else
                            <div class="space-y-2.5">
                                @foreach($activeVolunteerOpportunities as $vol)
                                <a href="{{ route('volunteers.show', $vol) }}" class="p-3 rounded-xl bg-[#0B1120]/70 border border-[#334155] hover:border-cyan-500/50 transition block">
                                    <div class="flex items-center justify-between mb-1">
                                        <h4 class="text-xs font-bold text-white line-clamp-1 font-brand-display">{{ $vol->title }}</h4>
                                        <span class="text-[9px] px-1.5 py-0.5 rounded bg-cyan-500/10 text-cyan-400 border border-cyan-500/20 font-bold shrink-0">
                                            {{ $vol->category_label }}
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-[#94A3B8] line-clamp-1">{{ $vol->expected_hours }} hrs • {{ $vol->location_type }}</p>
                                </a>
                                @endforeach
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Section F: Verified Attendance History -->
                    <div class="card bg-[#172033] border border-[#334155] shadow-xl p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2">
                                <i data-lucide="qr-code" class="w-4 h-4 text-emerald-400"></i>
                                <h2 class="text-sm font-bold text-white font-brand-display uppercase tracking-wider">Recent Attendance Check-Ins</h2>
                            </div>
                            <span class="text-xs text-[#94A3B8]">{{ $verifiedAttendanceCount }} Total Sessions</span>
                        </div>

                        @if($recentAttendance->isEmpty())
                        <div class="p-6 text-center border border-dashed border-[#334155] rounded-2xl">
                            <p class="text-xs text-[#94A3B8]">No attendance records scanned yet for your membership.</p>
                        </div>
                        @else
                        <div class="space-y-2">
                            @foreach($recentAttendance as $rec)
                            <div class="p-3 rounded-xl bg-[#0B1120]/60 border border-[#334155] flex items-center justify-between text-xs">
                                <div>
                                    <p class="font-bold text-white font-brand-display">{{ $rec->event ? $rec->event->name : 'General Check-In' }}</p>
                                    <p class="text-[11px] text-[#94A3B8]">{{ $rec->attendanceSession ? $rec->attendanceSession->session_name : 'Attendance Terminal' }}</p>
                                </div>
                                <div class="text-right">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                        Verified
                                    </span>
                                    <p class="text-[10px] text-slate-500 mt-0.5">{{ $rec->scanned_at ? $rec->scanned_at->format('M d, H:i') : '' }}</p>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Right Column: Support Tickets, Rewards & EXP Activity -->
                <div class="space-y-6">

                    <!-- Module 2: Member Help Desk & Support Requests Shortcut -->
                    <div class="card bg-[#172033] border border-[#334155] shadow-xl p-5">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <i data-lucide="life-buoy" class="w-4 h-4 text-rose-400"></i>
                                <span class="text-xs font-bold text-white uppercase tracking-wider">My Support Requests</span>
                            </div>
                            <a href="{{ route('helpdesk.index') }}" class="text-[11px] font-bold text-rose-400 hover:text-white">View All</a>
                        </div>

                        @if($mySupportTickets->isEmpty())
                        <div class="p-4 text-center border border-dashed border-[#334155] rounded-xl">
                            <p class="text-xs text-[#94A3B8]">No open support requests or disputes.</p>
                            <a href="{{ route('helpdesk.create') }}" class="mt-2 inline-block text-xs text-[#df747a] hover:underline font-semibold">Report an Issue</a>
                        </div>
                        @else
                        <div class="space-y-2">
                            @foreach($mySupportTickets as $tkt)
                            <a href="{{ route('helpdesk.show', $tkt) }}" class="p-2.5 rounded-xl bg-[#0B1120]/70 border border-[#334155] hover:border-rose-500/50 transition block">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-[10px] font-mono text-[#94A3B8]">{{ $tkt->ticket_number }}</span>
                                    <span class="text-[9px] px-1.5 py-0.5 rounded font-bold
                                        {{ $tkt->status === 'resolved' ? 'bg-emerald-500/20 text-emerald-400' : ($tkt->status === 'in_review' ? 'bg-blue-500/20 text-blue-400' : 'bg-amber-500/20 text-amber-300') }}">
                                        {{ $tkt->status_label }}
                                    </span>
                                </div>
                                <h4 class="text-xs font-bold text-white line-clamp-1">{{ $tkt->subject }}</h4>
                            </a>
                            @endforeach
                        </div>
                        @endif
                    </div>

                    <!-- Section D: Reward Catalog Highlights -->
                    <div class="card bg-[#172033] border border-[#334155] shadow-xl p-5">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-bold text-[#94A3B8] uppercase tracking-wider">Reward Catalog Highlights</span>
                            <a href="{{ route('shop.index') }}" class="text-[11px] font-bold text-amber-400 hover:text-amber-300 flex items-center gap-1 transition">
                                <span>Shop</span>
                                <i data-lucide="arrow-right" class="w-3 h-3"></i>
                            </a>
                        </div>

                        @if($featuredRewards->isEmpty())
                        <p class="text-xs text-[#94A3B8] text-center py-4">No rewards published currently.</p>
                        @else
                        <div class="space-y-2.5">
                            @foreach($featuredRewards as $rew)
                            <a href="{{ route('shop.show', $rew) }}" class="p-3 rounded-xl bg-[#0B1120]/70 border border-[#334155] hover:border-[#8F3033]/60 transition flex items-center justify-between block group">
                                <div>
                                    <h4 class="text-xs font-bold text-white group-hover:text-[#df747a] transition-colors font-brand-display">{{ $rew->title }}</h4>
                                    <span class="text-[10px] text-[#94A3B8]">{{ $rew->category }}</span>
                                </div>
                                <span class="px-2 py-1 rounded-lg bg-amber-500/20 text-amber-300 font-bold text-xs border border-amber-500/30">
                                    {{ number_format($rew->points_cost) }} EXP
                                </span>
                            </a>
                            @endforeach
                        </div>
                        @endif
                    </div>

                    <!-- Section C: Authoritative EXP Transaction Activity -->
                    <div class="card bg-[#172033] border border-[#334155] shadow-xl p-5">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-bold text-[#94A3B8] uppercase tracking-wider">Authoritative EXP Ledger</span>
                            <a href="{{ route('ledger.index') }}" class="text-[11px] text-amber-400 hover:underline">Full Ledger</a>
                        </div>

                        <div class="space-y-2">
                            @forelse($recentXp as $tx)
                            <div class="flex items-center justify-between text-xs py-1.5 border-b border-[#334155]/60 last:border-0">
                                <div>
                                    <p class="font-medium text-slate-200">{{ $tx->description }}</p>
                                    <p class="text-[10px] text-slate-500">{{ $tx->created_at->diffForHumans() }}</p>
                                </div>
                                <span class="font-bold {{ $tx->amount >= 0 ? 'text-[#df747a]' : 'text-slate-400' }} tabular-nums">
                                    {{ $tx->amount >= 0 ? '+' : '' }}{{ $tx->amount }} EXP
                                </span>
                            </div>
                            @empty
                            <p class="text-xs text-[#94A3B8] text-center py-3">No activity recorded yet.</p>
                            @endforelse
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</x-app-layout>
