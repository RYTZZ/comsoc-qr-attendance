<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <a href="{{ route('profile.edit') }}" title="Change Adventurer Avatar" class="group relative">
                    <x-user-avatar :user="$user" size="xl" class="border-2 border-[#8F3033]/60 shadow-xl group-hover:scale-105 transition-all group-hover:border-[#df747a]" />
                    <span class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full bg-[#560E0F] border border-[#df747a] flex items-center justify-center text-white opacity-0 group-hover:opacity-100 transition shadow">
                        <i data-lucide="edit-3" class="w-2.5 h-2.5 text-white"></i>
                    </span>
                </a>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-burgundy-deep text-[#df747a] border border-[#8F3033]/60">
                            {{ $activeSeason ? $activeSeason->name : 'Active Semester' }}
                        </span>
                        <span class="text-xs text-[#94A3B8] font-mono">
                            {{ auth()->user()?->isSuperAdmin() ? ($student ? $student->student_number : $user->username) : ($user->member_id ?: $user->username) }}
                        </span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                            Active Member
                        </span>
                        <a href="{{ route('signature.show') }}"
                           id="dashboard-signature-status-badge"
                           x-data="{
                               state: @js($signatureStatus['state'] ?? 'required'),
                               label: @js($signatureStatus['label'] ?? 'Signature Required'),
                               init() {
                                   window.addEventListener('signature-status-updated', (e) => {
                                       if (e.detail) {
                                           this.state = e.detail.state || (e.detail.registered ? 'registered' : 'required');
                                           this.label = e.detail.label || (e.detail.registered ? 'Signature Registered' : 'Signature Required');
                                       }
                                   });
                               }
                           }"
                           class="px-2 py-0.5 rounded text-[10px] font-semibold border flex items-center gap-1 transition"
                           :class="{
                               'bg-emerald-950/40 border-emerald-500/30 text-emerald-300 hover:border-emerald-400': state === 'registered',
                               'bg-rose-950/40 border-rose-500/30 text-rose-300 hover:border-rose-400': state === 'unavailable',
                               'bg-amber-950/40 border-amber-500/30 text-amber-300 hover:border-amber-400': state === 'required'
                           }">
                            <span class="w-1.5 h-1.5 rounded-full"
                                  :class="{
                                      'bg-emerald-400': state === 'registered',
                                      'bg-rose-400': state === 'unavailable',
                                      'bg-amber-400 animate-pulse': state === 'required'
                                  }"></span>
                            <span x-text="label"></span>
                        </a>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-[#F8FAFC] font-brand-display tracking-tight mt-1 flex flex-wrap items-center gap-2">
                        <span>Welcome back, {{ $student ? $student->first_name : $user->name }}!</span>
                        @if($user->username)
                            <span class="text-sm sm:text-base font-normal font-mono text-[#df747a]">({{ '@' . $user->username }})</span>
                        @endif
                    </h1>
                    <p class="text-xs text-[#94A3B8] mt-0.5">
                        {{ $student ? "{$student->program} • Year {$student->year_level} (Block {$student->block})" : 'Computing Society Member' }}
                    </p>
                </div>
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

    <div class="py-6 sm:py-8 bg-[#0B1120]"
         x-data="{
             currentTab: new URLSearchParams(window.location.search).get('tab') || 'overview',
             setTab(tab) {
                 this.currentTab = tab;
                 const url = new URL(window.location);
                 url.searchParams.set('tab', tab);
                 window.history.replaceState({}, '', url);
                 this.$nextTick(() => {
                     if (window.lucideIcons) window.createIcons({ icons: window.lucideIcons });
                 });
             }
         }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if(!$user->hasCompletedAdventurerSetup())
                <div class="bg-gradient-to-r from-[#560E0F]/40 via-[#172033] to-[#0B1120] border border-[#8F3033]/60 rounded-2xl p-4 sm:p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-lg">
                    <div class="flex items-center gap-3.5">
                        <div class="w-10 h-10 rounded-xl bg-[#560E0F] border border-[#df747a]/40 flex items-center justify-center text-white shrink-0">
                            <i data-lucide="sparkles" class="w-5 h-5 text-[#df747a]"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-white font-brand-display">Set Up Your Adventurer Identity</h3>
                            <p class="text-xs text-[#94A3B8]">Choose your character avatar and claim a unique public username for community leaderboards and profiles.</p>
                        </div>
                    </div>
                    <a href="{{ route('onboarding.adventurer') }}" class="btn-primary text-xs px-4 py-2 shrink-0 flex items-center gap-1.5 shadow">
                        <span>Choose Adventurer</span>
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                    </a>
                </div>
            @endif

            <div x-data="{
                state: @js($signatureStatus['state'] ?? 'required'),
                init() {
                    window.addEventListener('signature-status-updated', (e) => {
                        if (e.detail) {
                            this.state = e.detail.state || (e.detail.registered ? 'registered' : 'required');
                        }
                    });
                }
            }">
                <div x-show="state === 'required'" x-cloak class="bg-amber-950/40 border border-amber-500/40 rounded-2xl p-4 sm:p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-lg">
                    <div class="flex items-center gap-3.5">
                        <div class="w-10 h-10 rounded-xl bg-amber-500/20 border border-amber-500/40 flex items-center justify-center text-amber-300 shrink-0">
                            <i data-lucide="file-pen" class="w-5 h-5 text-amber-400"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-white font-brand-display flex items-center gap-2">
                                <span>Signature Registration Required</span>
                                <span class="px-2 py-0.2 rounded-full text-[10px] bg-amber-500/20 text-amber-300 border border-amber-500/30">Action Needed</span>
                            </h3>
                            <p class="text-xs text-[#94A3B8]">You have not yet registered an electronic signature. Register your handwritten signature to ensure your official attendance records and certificates can be issued seamlessly.</p>
                        </div>
                    </div>
                    <a href="{{ route('signature.show') }}" class="btn-primary text-xs px-4 py-2 shrink-0 flex items-center gap-1.5 shadow bg-amber-600 hover:bg-amber-500 border-amber-500 text-white">
                        <span>Register Signature</span>
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                    </a>
                </div>

                <div x-show="state === 'unavailable'" x-cloak class="bg-rose-950/40 border border-rose-500/40 rounded-2xl p-4 sm:p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-lg">
                    <div class="flex items-center gap-3.5">
                        <div class="w-10 h-10 rounded-xl bg-rose-500/20 border border-rose-500/40 flex items-center justify-center text-rose-300 shrink-0">
                            <i data-lucide="alert-triangle" class="w-5 h-5 text-rose-400"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-white font-brand-display">Signature Unavailable</h3>
                            <p class="text-xs text-[#94A3B8]">Your stored signature record is temporarily unretrievable. Please visit My Signature to register a replacement signature.</p>
                        </div>
                    </div>
                    <a href="{{ route('signature.show') }}" class="btn-secondary text-xs px-4 py-2 shrink-0 flex items-center gap-1.5 shadow text-rose-200 border-rose-500/40 hover:bg-rose-900/30">
                        <span>Update Signature</span>
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                    </a>
                </div>
            </div>

            <!-- Responsive Dashboard Tab Bar -->
            <div class="bg-[#172033] p-1.5 rounded-2xl border border-[#334155] shadow-xl overflow-x-auto scrollbar-none">
                <div class="flex items-center gap-1.5 min-w-max" role="tablist" aria-label="Dashboard Sections">
                    <button type="button"
                            role="tab"
                            :aria-selected="currentTab === 'overview'"
                            @click="setTab('overview')"
                            class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold transition-all duration-150"
                            :class="currentTab === 'overview'
                                ? 'bg-burgundy-gradient text-white shadow-lg shadow-[#560E0F]/30 border border-[#8F3033]/60'
                                : 'text-[#94A3B8] hover:text-white hover:bg-[#1E293B]'">
                        <i data-lucide="layout-dashboard" class="w-4 h-4 shrink-0"></i>
                        <span>Overview</span>
                    </button>

                    <button type="button"
                            role="tab"
                            :aria-selected="currentTab === 'attendance'"
                            @click="setTab('attendance')"
                            class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold transition-all duration-150"
                            :class="currentTab === 'attendance'
                                ? 'bg-burgundy-gradient text-white shadow-lg shadow-[#560E0F]/30 border border-[#8F3033]/60'
                                : 'text-[#94A3B8] hover:text-white hover:bg-[#1E293B]'">
                        <i data-lucide="calendar-check" class="w-4 h-4 shrink-0"></i>
                        <span>Attendance</span>
                        @if($verifiedAttendanceCount > 0)
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold"
                              :class="currentTab === 'attendance' ? 'bg-white/20 text-white' : 'bg-[#1E293B] text-slate-400'">
                            {{ $verifiedAttendanceCount }}
                        </span>
                        @endif
                    </button>

                    <button type="button"
                            role="tab"
                            :aria-selected="currentTab === 'exp-rewards'"
                            @click="setTab('exp-rewards')"
                            class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold transition-all duration-150"
                            :class="currentTab === 'exp-rewards'
                                ? 'bg-burgundy-gradient text-white shadow-lg shadow-[#560E0F]/30 border border-[#8F3033]/60'
                                : 'text-[#94A3B8] hover:text-white hover:bg-[#1E293B]'">
                        <i data-lucide="gift" class="w-4 h-4 shrink-0"></i>
                        <span>EXP & Rewards</span>
                    </button>

                    <button type="button"
                            role="tab"
                            :aria-selected="currentTab === 'missions'"
                            @click="setTab('missions')"
                            class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold transition-all duration-150"
                            :class="currentTab === 'missions'
                                ? 'bg-burgundy-gradient text-white shadow-lg shadow-[#560E0F]/30 border border-[#8F3033]/60'
                                : 'text-[#94A3B8] hover:text-white hover:bg-[#1E293B]'">
                        <i data-lucide="target" class="w-4 h-4 shrink-0"></i>
                        <span>Missions & Opportunities</span>
                    </button>

                    <button type="button"
                            role="tab"
                            :aria-selected="currentTab === 'profile'"
                            @click="setTab('profile')"
                            class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold transition-all duration-150"
                            :class="currentTab === 'profile'
                                ? 'bg-burgundy-gradient text-white shadow-lg shadow-[#560E0F]/30 border border-[#8F3033]/60'
                                : 'text-[#94A3B8] hover:text-white hover:bg-[#1E293B]'">
                        <i data-lucide="user" class="w-4 h-4 shrink-0"></i>
                        <span>My Profile</span>
                    </button>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- TAB 1: OVERVIEW                            -->
            <!-- ========================================== -->
            <div x-show="currentTab === 'overview'" x-transition:enter="transition ease-out duration-200" class="space-y-6">

                <!-- Announcements Bulletin (if any) -->
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
                                <span class="text-xs text-slate-400 font-medium">{{ $topAnn->published_at?->diffForHumans() }}</span>
                            </div>
                            <h2 class="text-sm sm:text-base font-bold text-white mt-1">{{ $topAnn->title }}</h2>
                            <p class="text-xs text-[#94A3B8] line-clamp-1 mt-0.5">{{ Str::limit(strip_tags($topAnn->content), 120) }}</p>
                        </div>
                    </div>
                    <a href="{{ route('announcements.show', $topAnn) }}" class="btn-secondary text-xs shrink-0 self-start sm:self-center">
                        Read Notice
                    </a>
                </div>
                @endif

                <!-- Dedicated Signature Status & Action Hub Section -->
                <x-signature-status-card :signature-status="$signatureStatus" />

                <!-- 4 Concise Summary Metric Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Spendable EXP -->
                    <div class="card bg-[#172033] border border-[#334155] p-5 shadow-lg">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-[#94A3B8] uppercase tracking-wider">Spendable EXP</span>
                            <div class="w-8 h-8 rounded-lg bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400">
                                <i data-lucide="coins" class="w-4 h-4"></i>
                            </div>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-2xl sm:text-3xl font-extrabold text-amber-400 font-mono">{{ number_format($spendableExp) }}</span>
                            <span class="text-xs text-slate-400 font-medium">pts</span>
                        </div>
                        <p class="text-[11px] text-[#94A3B8] mt-1.5 flex items-center gap-1">
                            <span>Ready to redeem in Reward Shop</span>
                        </p>
                    </div>

                    <!-- Lifetime EXP & Level -->
                    <div class="card bg-[#172033] border border-[#334155] p-5 shadow-lg">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-[#94A3B8] uppercase tracking-wider">Level & Progression</span>
                            <div class="w-8 h-8 rounded-lg bg-burgundy-deep border border-[#8F3033]/50 flex items-center justify-center text-[#df747a]">
                                <i data-lucide="sparkles" class="w-4 h-4"></i>
                            </div>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-2xl sm:text-3xl font-extrabold text-white font-brand-display">Lvl {{ $currentLevel->level }}</span>
                            <span class="text-xs text-[#df747a] font-semibold">{{ $currentLevel->title ?? $currentLevel->name }}</span>
                        </div>
                        <div class="w-full bg-[#0B1120] rounded-full h-1.5 mt-2.5 overflow-hidden">
                            <div class="bg-gradient-to-r from-[#560E0F] to-[#df747a] h-1.5 rounded-full" style="width: {{ $levelProgress }}%"></div>
                        </div>
                    </div>

                    <!-- Verified Attendance -->
                    <div class="card bg-[#172033] border border-[#334155] p-5 shadow-lg">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-[#94A3B8] uppercase tracking-wider">Attendance Scans</span>
                            <div class="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
                                <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                            </div>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-2xl sm:text-3xl font-extrabold text-emerald-400 font-mono">{{ $verifiedAttendanceCount }}</span>
                            <span class="text-xs text-slate-400 font-medium">events attended</span>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1.5">Official seminar & activity records</p>
                    </div>

                    <!-- Upcoming Events -->
                    <div class="card bg-[#172033] border border-[#334155] p-5 shadow-lg">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-[#94A3B8] uppercase tracking-wider">Upcoming Events</span>
                            <div class="w-8 h-8 rounded-lg bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400">
                                <i data-lucide="calendar" class="w-4 h-4"></i>
                            </div>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-2xl sm:text-3xl font-extrabold text-blue-400 font-mono">{{ $upcomingEvents->count() }}</span>
                            <span class="text-xs text-slate-400 font-medium">on schedule</span>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1.5">Available for attendance & EXP</p>
                    </div>
                </div>

                <!-- Quick Action Shortcuts Bar -->
                <div class="p-3.5 bg-[#172033] rounded-2xl border border-[#334155] shadow-lg flex items-center gap-2 overflow-x-auto scrollbar-none">
                    <span class="text-[10px] uppercase font-bold tracking-wider text-[#94A3B8] px-2 whitespace-nowrap">Quick Links:</span>
                    <a href="{{ route('member.dynamic_qr') }}" class="px-3 py-1.5 rounded-xl bg-[#0B1120] hover:bg-[#1E293B] border border-[#334155] text-xs font-semibold text-[#F8FAFC] flex items-center gap-1.5 whitespace-nowrap transition">
                        <i data-lucide="qr-code" class="w-3.5 h-3.5 text-[#df747a]"></i>
                        <span>Show My QR</span>
                    </a>
                    <a href="{{ route('events.index') }}" class="px-3 py-1.5 rounded-xl bg-[#0B1120] hover:bg-[#1E293B] border border-[#334155] text-xs font-semibold text-[#F8FAFC] flex items-center gap-1.5 whitespace-nowrap transition">
                        <i data-lucide="calendar" class="w-3.5 h-3.5 text-blue-400"></i>
                        <span>Browse Events</span>
                    </a>
                    <button type="button" @click="setTab('exp-rewards')" class="px-3 py-1.5 rounded-xl bg-[#0B1120] hover:bg-[#1E293B] border border-[#334155] text-xs font-semibold text-[#F8FAFC] flex items-center gap-1.5 whitespace-nowrap transition">
                        <i data-lucide="history" class="w-3.5 h-3.5 text-amber-400"></i>
                        <span>EXP History</span>
                    </button>
                    <a href="{{ route('shop.index') }}" class="px-3 py-1.5 rounded-xl bg-[#0B1120] hover:bg-[#1E293B] border border-[#334155] text-xs font-semibold text-[#F8FAFC] flex items-center gap-1.5 whitespace-nowrap transition">
                        <i data-lucide="gift" class="w-3.5 h-3.5 text-purple-400"></i>
                        <span>Browse Rewards</span>
                    </a>
                    <a href="{{ route('helpdesk.index') }}" class="px-3 py-1.5 rounded-xl bg-[#0B1120] hover:bg-[#1E293B] border border-[#334155] text-xs font-semibold text-[#F8FAFC] flex items-center gap-1.5 whitespace-nowrap transition">
                        <i data-lucide="life-buoy" class="w-3.5 h-3.5 text-rose-400"></i>
                        <span>Submit Support Request</span>
                    </a>
                    <a href="{{ route('projects.index') }}" class="px-3 py-1.5 rounded-xl bg-[#0B1120] hover:bg-[#1E293B] border border-[#334155] text-xs font-semibold text-[#F8FAFC] flex items-center gap-1.5 whitespace-nowrap transition">
                        <i data-lucide="folder-git-2" class="w-3.5 h-3.5 text-indigo-400"></i>
                        <span>Explore Projects</span>
                    </a>
                    <a href="{{ route('volunteers.index') }}" class="px-3 py-1.5 rounded-xl bg-[#0B1120] hover:bg-[#1E293B] border border-[#334155] text-xs font-semibold text-[#F8FAFC] flex items-center gap-1.5 whitespace-nowrap transition">
                        <i data-lucide="hand-heart" class="w-3.5 h-3.5 text-emerald-400"></i>
                        <span>Volunteer Board</span>
                    </a>
                    <a href="{{ route('leaderboard.index') }}" class="px-3 py-1.5 rounded-xl bg-[#0B1120] hover:bg-[#1E293B] border border-[#334155] text-xs font-semibold text-[#F8FAFC] flex items-center gap-1.5 whitespace-nowrap transition">
                        <i data-lucide="trophy" class="w-3.5 h-3.5 text-amber-400"></i>
                        <span>Leaderboard</span>
                    </a>
                </div>

                <!-- Overview Snapshots: 2 Column Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Next Scheduled Event Snapshot -->
                    <div class="card bg-[#172033] border border-[#334155] p-5 shadow-xl space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-[#334155]">
                            <div class="flex items-center gap-2">
                                <i data-lucide="calendar" class="w-4 h-4 text-blue-400"></i>
                                <h3 class="text-sm font-bold text-white font-brand-display">Upcoming Event</h3>
                            </div>
                            <button type="button" @click="setTab('attendance')" class="text-xs text-[#df747a] hover:text-white font-semibold transition">
                                See All &rarr;
                            </button>
                        </div>

                        @if($upcomingEvents->isNotEmpty())
                        @php $firstEvent = $upcomingEvents->first(); @endphp
                        <div class="p-4 rounded-xl bg-[#0B1120] border border-[#334155] space-y-3">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h4 class="text-sm font-bold text-white">{{ $firstEvent->name }}</h4>
                                    <p class="text-xs text-slate-400 mt-0.5 flex items-center gap-1.5">
                                        <i data-lucide="map-pin" class="w-3.5 h-3.5 text-slate-500"></i>
                                        <span>{{ $firstEvent->location ?: 'SorSU Bulan Campus' }}</span>
                                    </p>
                                </div>
                                <span class="px-2.5 py-1 rounded-lg text-xs font-bold font-mono bg-[#1E293B] text-blue-400 border border-blue-500/20 whitespace-nowrap">
                                    {{ $firstEvent->event_date?->format('M d, Y') }}
                                </span>
                            </div>

                            <p class="text-xs text-[#94A3B8] line-clamp-2 leading-relaxed">
                                {{ $firstEvent->description ?: 'Flagship practical engineering seminar and workshop.' }}
                            </p>

                            <div class="pt-2 border-t border-[#334155] flex items-center justify-between">
                                <span class="text-[11px] text-slate-400">
                                    Capacity: <strong class="text-white">{{ $firstEvent->event_registrations_count }} / {{ $firstEvent->max_participants ?: 'Open' }}</strong>
                                </span>
                                <a href="{{ route('events.show', $firstEvent) }}" class="btn-primary text-xs py-1.5 px-3">
                                    Event Details
                                </a>
                            </div>
                        </div>
                        @else
                        <div class="p-8 text-center bg-[#0B1120] rounded-xl border border-dashed border-[#334155]">
                            <i data-lucide="calendar-x" class="w-8 h-8 text-slate-500 mx-auto mb-2"></i>
                            <p class="text-xs text-slate-400 font-medium">No upcoming events scheduled right now.</p>
                        </div>
                        @endif
                    </div>

                    <!-- Recent Activity & XP Transactions Snapshot -->
                    <div class="card bg-[#172033] border border-[#334155] p-5 shadow-xl space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-[#334155]">
                            <div class="flex items-center gap-2">
                                <i data-lucide="history" class="w-4 h-4 text-amber-400"></i>
                                <h3 class="text-sm font-bold text-white font-brand-display">Recent Activity & EXP</h3>
                            </div>
                            <button type="button" @click="setTab('exp-rewards')" class="text-xs text-[#df747a] hover:text-white font-semibold transition">
                                View Full Ledger &rarr;
                            </button>
                        </div>

                        @if($recentXp->isNotEmpty())
                        <div class="space-y-2.5">
                            @foreach($recentXp->take(3) as $tx)
                            <div class="p-3 rounded-xl bg-[#0B1120] border border-[#334155] flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-white truncate">{{ $tx->description ?: 'Attendance credit' }}</p>
                                    <p class="text-[10px] text-slate-400 mt-0.5">{{ $tx->created_at?->diffForHumans() }}</p>
                                </div>
                                <span class="font-mono text-xs font-bold px-2 py-0.5 rounded {{ $tx->amount >= 0 ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20' }}">
                                    {{ $tx->amount >= 0 ? '+' : '' }}{{ $tx->amount }} EXP
                                </span>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <div class="p-8 text-center bg-[#0B1120] rounded-xl border border-dashed border-[#334155]">
                            <i data-lucide="sparkles" class="w-8 h-8 text-slate-500 mx-auto mb-2"></i>
                            <p class="text-xs text-slate-400 font-medium">Attend events and complete missions to earn your first EXP!</p>
                        </div>
                        @endif
                    </div>
                </div>

            </div>

            <!-- ========================================== -->
            <!-- TAB 2: ATTENDANCE                          -->
            <!-- ========================================== -->
            <div x-show="currentTab === 'attendance'" x-cloak x-transition:enter="transition ease-out duration-200" class="space-y-6">

                <!-- Dynamic QR Pass Callout -->
                <div class="card bg-burgundy-deep/60 border border-[#8F3033]/60 p-5 shadow-xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-2xl bg-burgundy-gradient border border-[#8F3033] flex items-center justify-center text-white shrink-0 shadow-lg">
                            <i data-lucide="qr-code" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-white font-brand-display">Your Dynamic Attendance Pass</h3>
                            <p class="text-xs text-[#94A3B8] mt-0.5 leading-relaxed">
                                Present this rolling dynamic QR code at the event kiosk terminal for time-in and time-out validation.
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('member.dynamic_qr') }}" class="btn-primary text-xs py-2.5 px-4 shrink-0 shadow-md flex items-center gap-1.5 self-start sm:self-center">
                        <i data-lucide="smartphone" class="w-4 h-4"></i>
                        <span>Open Attendance QR</span>
                    </a>
                </div>

                <!-- Events & Participation Schedule -->
                <div class="card bg-[#172033] border border-[#334155] p-6 shadow-xl space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-[#334155]">
                        <div>
                            <h3 class="text-base font-bold text-white font-brand-display">Event Participation & Schedule</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Seminars, workshops, and general assemblies</p>
                        </div>
                        <a href="{{ route('events.index') }}" class="btn-secondary text-xs py-1.5 px-3">
                            Browse All Events
                        </a>
                    </div>

                    @if($upcomingEvents->isNotEmpty())
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($upcomingEvents as $event)
                        @php
                            $isRegistered = isset($myEventRegistrationIds[$event->id]);
                            $hasEvaluated = isset($myEvaluations[$event->id]);
                        @endphp
                        <div class="p-4 rounded-xl bg-[#0B1120] border border-[#334155] flex flex-col justify-between space-y-3">
                            <div>
                                <div class="flex items-start justify-between gap-2">
                                    <h4 class="text-sm font-bold text-white leading-snug">{{ $event->name }}</h4>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-[#1E293B] text-blue-400 border border-blue-500/20 whitespace-nowrap">
                                        {{ $event->event_date?->format('M d, Y') }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-400 mt-1 flex items-center gap-1.5">
                                    <i data-lucide="map-pin" class="w-3.5 h-3.5 text-slate-500 shrink-0"></i>
                                    <span>{{ $event->location ?: 'SorSU Bulan Campus' }}</span>
                                </p>
                                <p class="text-xs text-slate-400 mt-1 line-clamp-2">
                                    {{ $event->description }}
                                </p>
                            </div>

                            <div class="pt-3 border-t border-[#334155] flex items-center justify-between">
                                <div>
                                    @if($isRegistered)
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                        Registered
                                    </span>
                                    @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-800 text-slate-400">
                                        Not Registered
                                    </span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2">
                                    @if($hasEvaluated)
                                    <span class="text-[10px] text-emerald-400 font-semibold flex items-center gap-1">
                                        <i data-lucide="check" class="w-3 h-3"></i> Evaluated
                                    </span>
                                    @endif
                                    <a href="{{ route('events.show', $event) }}" class="btn-secondary text-xs py-1.5 px-3">
                                        View
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="p-8 text-center bg-[#0B1120] rounded-xl border border-dashed border-[#334155]">
                        <p class="text-xs text-slate-400">No events are currently scheduled.</p>
                    </div>
                    @endif
                </div>

                <!-- Verified Attendance Records Log -->
                <div class="card bg-[#172033] border border-[#334155] p-6 shadow-xl space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-[#334155]">
                        <div>
                            <h3 class="text-base font-bold text-white font-brand-display">Attendance Records History</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Verified kiosk time-in and time-out timestamps</p>
                        </div>
                        <a href="{{ route('ledger.index') }}" class="btn-secondary text-xs py-1.5 px-3">
                            Full Attendance Ledger
                        </a>
                    </div>

                    @if($recentAttendance->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="border-b border-[#334155] text-slate-400 uppercase text-[10px] tracking-wider">
                                    <th class="py-2.5 px-3 font-semibold">Event</th>
                                    <th class="py-2.5 px-3 font-semibold">Session</th>
                                    <th class="py-2.5 px-3 font-semibold">Scan Timestamp</th>
                                    <th class="py-2.5 px-3 font-semibold">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#334155]/60 text-slate-300">
                                @foreach($recentAttendance as $record)
                                <tr>
                                    <td class="py-3 px-3 font-semibold text-white">
                                        {{ $record->event ? $record->event->name : 'Campus Activity' }}
                                    </td>
                                    <td class="py-3 px-3 text-slate-400">
                                        {{ $record->attendanceSession ? $record->attendanceSession->name : 'Main Session' }}
                                    </td>
                                    <td class="py-3 px-3 font-mono text-slate-400">
                                        {{ $record->scanned_at ? $record->scanned_at->format('M d, Y h:i A') : $record->created_at->format('M d, Y h:i A') }}
                                    </td>
                                    <td class="py-3 px-3">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                            Verified Present
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="p-8 text-center bg-[#0B1120] rounded-xl border border-dashed border-[#334155]">
                        <i data-lucide="calendar-check" class="w-8 h-8 text-slate-500 mx-auto mb-2"></i>
                        <p class="text-xs text-slate-400 font-medium">No attendance records found yet.</p>
                        <p class="text-[11px] text-slate-500 mt-1">Scan your Dynamic QR Pass at the next COMSOC event kiosk to verify presence.</p>
                    </div>
                    @endif
                </div>

            </div>

            <!-- ========================================== -->
            <!-- TAB 3: EXP & REWARDS                       -->
            <!-- ========================================== -->
            <div x-show="currentTab === 'exp-rewards'" x-cloak x-transition:enter="transition ease-out duration-200" class="space-y-6">

                <!-- EXP Balance & Level Overview Card -->
                <div class="card bg-[#172033] border border-[#334155] p-6 shadow-xl">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-burgundy-deep text-[#df747a] border border-[#8F3033]/60">
                                    Tier: {{ $currentLevel->title ?? $currentLevel->name }}
                                </span>
                                <span class="text-xs text-slate-400">Level {{ $currentLevel->level }}</span>
                            </div>
                            <h3 class="text-xl sm:text-2xl font-extrabold text-white font-brand-display mt-1">
                                Gamified EXP Economy & Rewards
                            </h3>
                            <p class="text-xs text-[#94A3B8] mt-1 max-w-lg leading-relaxed">
                                Earn EXP by attending campus activities, participating in workshops, and submitting community challenges. Spend points on official merchandise.
                            </p>
                        </div>

                        <div class="flex items-center gap-4 bg-[#0B1120] border border-[#334155] p-4 rounded-2xl shrink-0">
                            <div class="text-center px-4 border-r border-[#334155]">
                                <span class="block text-[10px] uppercase font-semibold text-[#94A3B8]">Spendable Balance</span>
                                <span class="text-2xl font-extrabold text-amber-400 font-mono">{{ number_format($spendableExp) }}</span>
                                <span class="block text-[10px] text-slate-500">Points</span>
                            </div>
                            <div class="text-center px-4">
                                <span class="block text-[10px] uppercase font-semibold text-[#94A3B8]">Lifetime EXP</span>
                                <span class="text-2xl font-extrabold text-[#df747a] font-mono">{{ number_format($lifetimeExp) }}</span>
                                <span class="block text-[10px] text-slate-500">All-time</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Featured Reward Catalog -->
                <div class="card bg-[#172033] border border-[#334155] p-6 shadow-xl space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-[#334155]">
                        <div>
                            <h3 class="text-base font-bold text-white font-brand-display">Reward Catalog & Merchandise</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Redeem official Computing Society stickers, lanyards, and merch</p>
                        </div>
                        <a href="{{ route('shop.index') }}" class="btn-primary text-xs py-1.5 px-3">
                            Open Full Reward Shop
                        </a>
                    </div>

                    @if($featuredRewards->isNotEmpty())
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($featuredRewards as $reward)
                        <div class="p-4 rounded-xl bg-[#0B1120] border border-[#334155] flex flex-col justify-between space-y-3 hover:border-[#8F3033]/80 transition">
                            <div class="space-y-2">
                                <div class="w-full h-32 rounded-lg bg-slate-900 border border-slate-800 flex items-center justify-center overflow-hidden p-2">
                                    @if($reward->image_url)
                                    <img src="{{ $reward->image_url }}" alt="{{ $reward->title ?? $reward->name }}" class="w-full h-full object-contain">
                                    @else
                                    <i data-lucide="gift" class="w-10 h-10 text-slate-600"></i>
                                    @endif
                                </div>
                                <h4 class="text-sm font-bold text-white line-clamp-1">{{ $reward->title ?? $reward->name }}</h4>
                                <p class="text-xs text-slate-400 line-clamp-2 leading-relaxed">{{ $reward->description }}</p>
                            </div>

                            <div class="pt-3 border-t border-[#334155] flex items-center justify-between">
                                <span class="font-mono text-xs font-bold text-amber-400">
                                    {{ number_format($reward->points_cost) }} EXP
                                </span>
                                <a href="{{ route('shop.show', $reward) }}" class="btn-secondary text-xs py-1 px-3">
                                    Redeem
                                </a>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="p-8 text-center bg-[#0B1120] rounded-xl border border-dashed border-[#334155]">
                        <p class="text-xs text-slate-400">No reward items published right now.</p>
                    </div>
                    @endif
                </div>

                <!-- Recent Redemptions & Claim Codes -->
                <div class="card bg-[#172033] border border-[#334155] p-6 shadow-xl space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-[#334155]">
                        <div>
                            <h3 class="text-base font-bold text-white font-brand-display">My Claim Codes & Redemptions</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Present your claim code to the COMSOC Treasurer or event booth</p>
                        </div>
                        <a href="{{ route('shop.index') }}" class="btn-secondary text-xs py-1.5 px-3">
                            Browse Rewards
                        </a>
                    </div>

                    @if(isset($recentRedemptions) && $recentRedemptions->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="border-b border-[#334155] text-slate-400 uppercase text-[10px] tracking-wider">
                                    <th class="py-2.5 px-3 font-semibold">Item</th>
                                    <th class="py-2.5 px-3 font-semibold">Claim Code</th>
                                    <th class="py-2.5 px-3 font-semibold">Cost</th>
                                    <th class="py-2.5 px-3 font-semibold">Date</th>
                                    <th class="py-2.5 px-3 font-semibold">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#334155]/60 text-slate-300">
                                @foreach($recentRedemptions as $redemption)
                                <tr>
                                    <td class="py-3 px-3 font-bold text-white">
                                        {{ $redemption->item_title_snapshot ?: ($redemption->reward ? $redemption->reward->name : 'Reward') }}
                                    </td>
                                    <td class="py-3 px-3 font-mono font-bold text-[#df747a]">
                                        {{ $redemption->claim_code }}
                                    </td>
                                    <td class="py-3 px-3 font-mono text-amber-400 font-semibold">
                                        {{ number_format($redemption->points_spent) }} EXP
                                    </td>
                                    <td class="py-3 px-3 font-mono text-slate-400">
                                        {{ $redemption->created_at->format('M d, Y') }}
                                    </td>
                                    <td class="py-3 px-3">
                                        @if($redemption->status === 'collected')
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                            Claimed
                                        </span>
                                        @else
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/10 text-amber-300 border border-amber-500/20">
                                            Ready to Claim
                                        </span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="p-8 text-center bg-[#0B1120] rounded-xl border border-dashed border-[#334155]">
                        <i data-lucide="gift" class="w-8 h-8 text-slate-500 mx-auto mb-2"></i>
                        <p class="text-xs text-slate-400 font-medium">You have not claimed any rewards yet.</p>
                        <p class="text-[11px] text-slate-500 mt-1">Visit the Reward Shop to redeem your spendable EXP.</p>
                    </div>
                    @endif
                </div>

                <!-- EXP Transaction History Ledger -->
                <div class="card bg-[#172033] border border-[#334155] p-6 shadow-xl space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-[#334155]">
                        <div>
                            <h3 class="text-base font-bold text-white font-brand-display">EXP Transaction Audit Trail</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Complete record of points earned and redeemed</p>
                        </div>
                        <a href="{{ route('ledger.index') }}" class="btn-secondary text-xs py-1.5 px-3">
                            Full Ledger View
                        </a>
                    </div>

                    @if($recentXp->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="border-b border-[#334155] text-slate-400 uppercase text-[10px] tracking-wider">
                                    <th class="py-2.5 px-3 font-semibold">Description</th>
                                    <th class="py-2.5 px-3 font-semibold">Date</th>
                                    <th class="py-2.5 px-3 font-semibold text-right">Delta</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#334155]/60 text-slate-300">
                                @foreach($recentXp as $xp)
                                <tr>
                                    <td class="py-3 px-3 font-medium text-white">
                                        {{ $xp->description ?: 'Event participation credit' }}
                                    </td>
                                    <td class="py-3 px-3 font-mono text-slate-400">
                                        {{ $xp->created_at->format('M d, Y h:i A') }}
                                    </td>
                                    <td class="py-3 px-3 text-right font-mono font-bold {{ $xp->amount >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                                        {{ $xp->amount >= 0 ? '+' : '' }}{{ $xp->amount }} EXP
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="p-8 text-center bg-[#0B1120] rounded-xl border border-dashed border-[#334155]">
                        <p class="text-xs text-slate-400">No transaction records available yet.</p>
                    </div>
                    @endif
                </div>

            </div>

            <!-- ========================================== -->
            <!-- TAB 4: MISSIONS & OPPORTUNITIES            -->
            <!-- ========================================== -->
            <div x-show="currentTab === 'missions'" x-cloak x-transition:enter="transition ease-out duration-200" class="space-y-6">

                <!-- Active Missions & Quests -->
                <div class="card bg-[#172033] border border-[#334155] p-6 shadow-xl space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-[#334155]">
                        <div>
                            <h3 class="text-base font-bold text-white font-brand-display">Missions & Challenges</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Complete technical challenges and community contributions for bonus EXP</p>
                        </div>
                        <a href="{{ route('missions.index') }}" class="btn-primary text-xs py-1.5 px-3">
                            Browse All Missions
                        </a>
                    </div>

                    @if($activeMissions->isNotEmpty())
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($activeMissions as $mission)
                        <div class="p-4 rounded-xl bg-[#0B1120] border border-[#334155] flex flex-col justify-between space-y-3">
                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-burgundy-deep text-[#df747a] border border-[#8F3033]/50">
                                        {{ $mission->category ?: 'Quest' }}
                                    </span>
                                    <span class="font-mono text-xs font-bold text-amber-400">
                                        +{{ $mission->xp_reward }} EXP
                                    </span>
                                </div>
                                <h4 class="text-sm font-bold text-white mt-1">{{ $mission->title }}</h4>
                                <p class="text-xs text-slate-400 line-clamp-2 leading-relaxed">{{ $mission->description }}</p>
                            </div>

                            <div class="pt-3 border-t border-[#334155] flex items-center justify-between">
                                <span class="text-[11px] text-slate-400">
                                    Due: {{ $mission->due_date ? $mission->due_date->format('M d') : 'Open' }}
                                </span>
                                <a href="{{ route('missions.show', $mission) }}" class="btn-secondary text-xs py-1 px-3">
                                    View Mission
                                </a>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="p-8 text-center bg-[#0B1120] rounded-xl border border-dashed border-[#334155]">
                        <p class="text-xs text-slate-400">No active missions posted right now.</p>
                    </div>
                    @endif
                </div>

                <!-- Volunteer Opportunities & Hours -->
                <div class="card bg-[#172033] border border-[#334155] p-6 shadow-xl space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-[#334155]">
                        <div>
                            <h3 class="text-base font-bold text-white font-brand-display">Volunteer Board & Community Service</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Contribute event organizing, technical, and creative support</p>
                        </div>
                        <a href="{{ route('volunteers.index') }}" class="btn-secondary text-xs py-1.5 px-3">
                            Explore Volunteer Hub
                        </a>
                    </div>

                    <div class="p-4 rounded-xl bg-burgundy-deep/40 border border-[#8F3033]/50 flex items-center justify-between">
                        <div>
                            <span class="text-xs font-semibold text-slate-300">Approved Community Service Hours</span>
                            <p class="text-xs text-slate-400 mt-0.5">Credited toward organization certification</p>
                        </div>
                        <div class="text-right">
                            <span class="text-2xl font-extrabold text-[#df747a] font-mono">{{ number_format($approvedVolunteerHours, 1) }}</span>
                            <span class="text-xs text-slate-400">hrs</span>
                        </div>
                    </div>

                    @if($activeVolunteerOpportunities->isNotEmpty())
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @foreach($activeVolunteerOpportunities as $vol)
                        <div class="p-3.5 rounded-xl bg-[#0B1120] border border-[#334155] space-y-2">
                            <h4 class="text-xs font-bold text-white truncate">{{ $vol->title }}</h4>
                            <p class="text-[11px] text-slate-400 line-clamp-2 leading-relaxed">{{ $vol->description }}</p>
                            <div class="pt-2 border-t border-[#334155] flex items-center justify-between text-[11px]">
                                <span class="text-cyan-400 font-semibold">{{ $vol->roles_needed ?: 'Volunteers' }}</span>
                                <a href="{{ route('volunteers.show', $vol) }}" class="text-[#df747a] hover:text-white font-semibold underline">Apply</a>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>

                <!-- Collaborative Projects -->
                <div class="card bg-[#172033] border border-[#334155] p-6 shadow-xl space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-[#334155]">
                        <div>
                            <h3 class="text-base font-bold text-white font-brand-display">Student Collaborative Projects</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Build open-source software and campus systems with peers</p>
                        </div>
                        <a href="{{ route('projects.index') }}" class="btn-secondary text-xs py-1.5 px-3">
                            Browse All Projects
                        </a>
                    </div>

                    @if($activeProjects->isNotEmpty())
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @foreach($activeProjects as $project)
                        <div class="p-3.5 rounded-xl bg-[#0B1120] border border-[#334155] space-y-2">
                            <h4 class="text-xs font-bold text-white truncate">{{ $project->title }}</h4>
                            <p class="text-[11px] text-slate-400 line-clamp-2 leading-relaxed">{{ $project->summary ?: $project->description }}</p>
                            <div class="pt-2 border-t border-[#334155] flex items-center justify-between text-[11px]">
                                <span class="px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-bold uppercase text-[9px]">
                                    Recruiting
                                </span>
                                <a href="{{ route('projects.show', $project) }}" class="btn-secondary text-xs py-1 px-2.5">
                                    Join Team
                                </a>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="p-8 text-center bg-[#0B1120] rounded-xl border border-dashed border-[#334155]">
                        <p class="text-xs text-slate-400">No collaborative projects recruiting right now.</p>
                    </div>
                    @endif
                </div>

            </div>

            <!-- ========================================== -->
            <!-- TAB 5: MY PROFILE                          -->
            <!-- ========================================== -->
            <div x-show="currentTab === 'profile'" x-cloak x-transition:enter="transition ease-out duration-200" class="space-y-6">

                <!-- Profile Summary & Identification Card -->
                <div class="card bg-[#172033] border border-[#334155] p-6 shadow-xl">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6 pb-6 border-b border-[#334155]">
                        <div class="flex items-center gap-4">
                            <x-user-avatar :user="$user" size="xl" class="border-2 border-[#8F3033]/60 shadow-xl" />
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-xl font-bold text-white font-brand-display">{{ $user->name }}</h3>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#560E0F] text-[#df747a] border border-[#8F3033] uppercase">
                                        {{ $user->role }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-300 mt-1">
                                    {{ $student ? "{$student->program} • Year {$student->year_level} (Block {$student->block})" : 'Computing Society Member' }}
                                </p>
                                <p class="text-[11px] font-mono text-slate-400 mt-0.5">
                                    Member ID: {{ $user->member_id ?: 'CMS-PENDING' }}
                                    @if(auth()->user()?->isSuperAdmin() && $student)
                                    <span class="ms-2 text-slate-500">• Student No: {{ $student->student_number }}</span>
                                    @endif
                                </p>
                            </div>
                        </div>

                        <a href="{{ route('profile.edit') }}" class="btn-primary text-xs py-2 px-4 flex items-center gap-1.5 self-start sm:self-center shadow-lg">
                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                            <span>Edit Profile & Avatar</span>
                        </a>
                    </div>

                    <!-- Profile Details Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 pt-6 text-xs">
                        <div class="p-3.5 rounded-xl bg-[#0B1120] border border-[#334155] space-y-1">
                            <span class="text-slate-400 uppercase text-[10px] font-semibold block">University Email</span>
                            <span class="font-mono text-white text-xs block truncate">{{ $user->email }}</span>
                        </div>

                        <div class="p-3.5 rounded-xl bg-[#0B1120] border border-[#334155] space-y-1">
                            <span class="text-slate-400 uppercase text-[10px] font-semibold block">Preferred Contact Email</span>
                            <span class="font-mono text-white text-xs block truncate">{{ $user->preferred_email ?: 'Same as university email' }}</span>
                        </div>

                        <div class="p-3.5 rounded-xl bg-[#0B1120] border border-[#334155] space-y-1">
                            <span class="text-slate-400 uppercase text-[10px] font-semibold block">Nickname / Handle</span>
                            <span class="text-white text-xs font-semibold block">{{ $user->nickname ?: 'None set' }}</span>
                        </div>

                        <div class="p-3.5 rounded-xl bg-[#0B1120] border border-[#334155] space-y-1">
                            <span class="text-slate-400 uppercase text-[10px] font-semibold block">Academic Cohort</span>
                            <span class="text-white text-xs block">{{ $student ? "{$student->program} • Block {$student->block}" : 'Computing Society' }}</span>
                        </div>

                        <div class="p-3.5 rounded-xl bg-[#0B1120] border border-[#334155] space-y-1">
                            <span class="text-slate-400 uppercase text-[10px] font-semibold block">Adventurer Seed</span>
                            <span class="font-mono text-[#df747a] text-xs block">{{ $user->avatar_seed ?: 'Felix' }} ({{ $user->avatar_style ?: 'adventurer' }})</span>
                        </div>

                        <div class="p-3.5 rounded-xl bg-[#0B1120] border border-[#334155] space-y-1">
                            <span class="text-slate-400 uppercase text-[10px] font-semibold block">Account Privacy</span>
                            <span class="text-emerald-400 text-xs font-semibold block">Verified Student Account</span>
                        </div>
                    </div>
                </div>

                <!-- Technical Skills & Technology Stack -->
                <div class="card bg-[#172033] border border-[#334155] p-6 shadow-xl space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-[#334155]">
                        <div>
                            <h3 class="text-base font-bold text-white font-brand-display">Technical Skills & Interests</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Programming languages, tools, and technical competencies</p>
                        </div>
                        <a href="{{ route('profile.edit') }}#skills-section" class="btn-secondary text-xs py-1.5 px-3">
                            Manage Skills
                        </a>
                    </div>

                    @if(isset($userSkills) && $userSkills->isNotEmpty())
                    <div class="flex flex-wrap gap-2">
                        @foreach($userSkills as $skill)
                        <span class="px-3 py-1.5 rounded-xl bg-[#0B1120] border border-[#334155] text-xs font-semibold text-slate-200 flex items-center gap-2">
                            <span>{{ $skill->name }}</span>
                            @if($skill->pivot && $skill->pivot->proficiency)
                            <span class="px-1.5 py-0.2 rounded text-[9px] font-bold uppercase bg-burgundy-deep text-[#df747a]">
                                {{ $skill->pivot->proficiency }}
                            </span>
                            @endif
                        </span>
                        @endforeach
                    </div>
                    @else
                    <div class="p-8 text-center bg-[#0B1120] rounded-xl border border-dashed border-[#334155]">
                        <p class="text-xs text-slate-400">No technical skills added to your profile yet.</p>
                        <a href="{{ route('profile.edit') }}#skills-section" class="text-xs text-[#df747a] hover:text-white underline font-semibold mt-1 inline-block">
                            Add skills to showcase your portfolio
                        </a>
                    </div>
                    @endif
                </div>

                <!-- Account Security & Quick Settings Shortcuts -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="card bg-[#172033] border border-[#334155] p-5 shadow-xl space-y-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-[#560E0F]/30 border border-[#8F3033]/50 flex items-center justify-center text-[#df747a]">
                                <i data-lucide="shield-check" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-white">Active Sessions & Devices</h4>
                                <p class="text-xs text-slate-400">Inspect device logins and active security alerts</p>
                            </div>
                        </div>
                        <div class="pt-2">
                            <a href="{{ route('profile.sessions') }}" class="btn-secondary w-full justify-center text-xs py-2">
                                Manage Active Sessions
                            </a>
                        </div>
                    </div>

                    <div class="card bg-[#172033] border border-[#334155] p-5 shadow-xl space-y-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-slate-900 border border-slate-700 flex items-center justify-center text-slate-300">
                                <i data-lucide="file-text" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-white">COMSOC Privacy Notice</h4>
                                <p class="text-xs text-slate-400">Review your rights under the Data Privacy Act of 2012</p>
                            </div>
                        </div>
                        <div class="pt-2">
                            <a href="{{ route('privacy') }}" class="btn-secondary w-full justify-center text-xs py-2">
                                Read Privacy Notice
                            </a>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
</x-app-layout>
