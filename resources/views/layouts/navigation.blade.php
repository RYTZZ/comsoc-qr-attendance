<aside class="hidden lg:flex flex-col fixed inset-y-0 left-0 z-30 bg-[#172033] border-r border-[#334155] transition-all duration-300 ease-in-out"
       :class="sidebarCollapsed ? 'w-20' : 'w-64'">

    <div class="h-16 flex items-center border-b border-[#334155] px-4 shrink-0 bg-[#0B1120]/40"
         :class="sidebarCollapsed ? 'justify-center' : 'justify-between'">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 overflow-hidden">
            <div class="w-9 h-9 rounded-xl bg-[#0B1120] border border-[#334155] p-1 flex items-center justify-center shrink-0 shadow-md">
                <img src="{{ asset('images/COMSOC.png') }}" alt="Computing Society Logo" class="w-full h-full object-contain">
            </div>
            <div class="flex flex-col overflow-hidden transition-all duration-300"
                 x-show="!sidebarCollapsed"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-x-2"
                 x-transition:enter-end="opacity-100 translate-x-0">
                <span class="font-extrabold text-[#F8FAFC] text-sm tracking-tight font-brand-display truncate">COMSOC</span>
                <span class="text-[10px] text-[#df747a] font-semibold tracking-wider uppercase truncate">QR Attendance</span>
            </div>
        </a>

        <button type="button"
                @click="toggleSidebar()"
                class="text-slate-400 hover:text-white p-1.5 rounded-lg hover:bg-[#1E293B] transition focus:outline-none focus:ring-2 focus:ring-[#8F3033]"
                :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                :aria-label="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'">
            <i data-lucide="chevron-left" class="w-4 h-4 transition-transform duration-300" :class="sidebarCollapsed ? 'rotate-180' : ''"></i>
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-6 scrollbar-none">
        <div>
            <div class="px-3 mb-2 transition-all duration-200" x-show="!sidebarCollapsed">
                <span class="text-[10px] font-bold uppercase tracking-widest text-[#94A3B8]">Overview</span>
            </div>
            <div class="space-y-1">
                <a href="{{ route('dashboard') }}"
                   class="group relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-150 {{ request()->routeIs('dashboard') ? 'bg-burgundy-gradient text-white shadow-lg shadow-[#560E0F]/30 border border-[#8F3033]/50' : 'text-[#94A3B8] hover:text-white hover:bg-[#1E293B]' }}"
                   :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                    <i data-lucide="layout-dashboard" class="w-4 h-4 shrink-0 {{ request()->routeIs('dashboard') ? 'text-white' : 'text-[#94A3B8] group-hover:text-white' }}"></i>
                    <span class="truncate" x-show="!sidebarCollapsed">{{ __('Dashboard') }}</span>
                    <span x-show="sidebarCollapsed" class="sr-only">{{ __('Dashboard') }}</span>
                    <div x-show="sidebarCollapsed" class="absolute left-full ms-2 px-2.5 py-1 bg-[#172033] text-white text-xs rounded-md shadow-xl border border-[#334155] whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity z-50">
                        {{ __('Dashboard') }}
                    </div>
                </a>

                <a href="{{ route('member.dynamic_qr') }}"
                   class="group relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-150 {{ request()->routeIs('member.dynamic_qr') ? 'bg-burgundy-gradient text-white shadow-lg shadow-[#560E0F]/30 border border-[#8F3033]/50' : 'text-[#94A3B8] hover:text-white hover:bg-[#1E293B]' }}"
                   :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                    <i data-lucide="qr-code" class="w-4 h-4 shrink-0 {{ request()->routeIs('member.dynamic_qr') ? 'text-white' : 'text-[#94A3B8] group-hover:text-white' }}"></i>
                    <span class="truncate" x-show="!sidebarCollapsed">{{ __('Dynamic Attendance QR') }}</span>
                    <span x-show="sidebarCollapsed" class="sr-only">{{ __('Dynamic Attendance QR') }}</span>
                    <div x-show="sidebarCollapsed" class="absolute left-full ms-2 px-2.5 py-1 bg-[#172033] text-white text-xs rounded-md shadow-xl border border-[#334155] whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity z-50">
                        {{ __('Dynamic Attendance QR') }}
                    </div>
                </a>
            </div>
        </div>

        <div>
            <div class="px-3 mb-2 transition-all duration-200" x-show="!sidebarCollapsed">
                <span class="text-[10px] font-bold uppercase tracking-widest text-[#94A3B8]">Participation</span>
            </div>
            <div class="space-y-1">
                <a href="{{ route('events.index') }}"
                   class="group relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-150 {{ request()->routeIs('events.*') ? 'bg-burgundy-gradient text-white shadow-lg shadow-[#560E0F]/30 border border-[#8F3033]/50' : 'text-[#94A3B8] hover:text-white hover:bg-[#1E293B]' }}"
                   :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                    <i data-lucide="calendar" class="w-4 h-4 shrink-0 {{ request()->routeIs('events.*') ? 'text-white' : 'text-[#94A3B8] group-hover:text-white' }}"></i>
                    <span class="truncate" x-show="!sidebarCollapsed">{{ __('Events & Activities') }}</span>
                    <span x-show="sidebarCollapsed" class="sr-only">{{ __('Events & Activities') }}</span>
                    <div x-show="sidebarCollapsed" class="absolute left-full ms-2 px-2.5 py-1 bg-[#172033] text-white text-xs rounded-md shadow-xl border border-[#334155] whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity z-50">
                        {{ __('Events & Activities') }}
                    </div>
                </a>

                <a href="{{ route('tickets.index') }}"
                   class="group relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-150 {{ request()->routeIs('tickets.*') ? 'bg-burgundy-gradient text-white shadow-lg shadow-[#560E0F]/30 border border-[#8F3033]/50' : 'text-[#94A3B8] hover:text-white hover:bg-[#1E293B]' }}"
                   :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                    <i data-lucide="ticket" class="w-4 h-4 shrink-0 {{ request()->routeIs('tickets.*') ? 'text-white' : 'text-[#94A3B8] group-hover:text-white' }}"></i>
                    <span class="truncate" x-show="!sidebarCollapsed">{{ __('My Tickets') }}</span>
                    <span x-show="sidebarCollapsed" class="sr-only">{{ __('My Tickets') }}</span>
                    <div x-show="sidebarCollapsed" class="absolute left-full ms-2 px-2.5 py-1 bg-[#172033] text-white text-xs rounded-md shadow-xl border border-[#334155] whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity z-50">
                        {{ __('My Tickets') }}
                    </div>
                </a>

                <a href="{{ route('ledger.index') }}"
                   class="group relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-150 {{ request()->routeIs('ledger.*') ? 'bg-burgundy-gradient text-white shadow-lg shadow-[#560E0F]/30 border border-[#8F3033]/50' : 'text-[#94A3B8] hover:text-white hover:bg-[#1E293B]' }}"
                   :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                    <i data-lucide="history" class="w-4 h-4 shrink-0 {{ request()->routeIs('ledger.*') ? 'text-white' : 'text-[#94A3B8] group-hover:text-white' }}"></i>
                    <span class="truncate" x-show="!sidebarCollapsed">{{ __('Attendance & EXP Ledger') }}</span>
                    <span x-show="sidebarCollapsed" class="sr-only">{{ __('Attendance & EXP Ledger') }}</span>
                    <div x-show="sidebarCollapsed" class="absolute left-full ms-2 px-2.5 py-1 bg-[#172033] text-white text-xs rounded-md shadow-xl border border-[#334155] whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity z-50">
                        {{ __('Attendance & EXP Ledger') }}
                    </div>
                </a>
            </div>
        </div>

        <div>
            <div class="px-3 mb-2 transition-all duration-200" x-show="!sidebarCollapsed">
                <span class="text-[10px] font-bold uppercase tracking-widest text-[#94A3B8]">Rewards & Progression</span>
            </div>
            <div class="space-y-1">
                <a href="{{ route('missions.index') }}"
                   class="group relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-150 {{ request()->routeIs('missions.*') ? 'bg-burgundy-gradient text-white shadow-lg shadow-[#560E0F]/30 border border-[#8F3033]/50' : 'text-[#94A3B8] hover:text-white hover:bg-[#1E293B]' }}"
                   :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                    <i data-lucide="target" class="w-4 h-4 shrink-0 {{ request()->routeIs('missions.*') ? 'text-white' : 'text-[#94A3B8] group-hover:text-white' }}"></i>
                    <span class="truncate" x-show="!sidebarCollapsed">{{ __('Missions & Quests') }}</span>
                    <span x-show="sidebarCollapsed" class="sr-only">{{ __('Missions & Quests') }}</span>
                    <div x-show="sidebarCollapsed" class="absolute left-full ms-2 px-2.5 py-1 bg-[#172033] text-white text-xs rounded-md shadow-xl border border-[#334155] whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity z-50">
                        {{ __('Missions & Quests') }}
                    </div>
                </a>

                <a href="{{ route('shop.index') }}"
                   class="group relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-150 {{ request()->routeIs('shop.*') ? 'bg-burgundy-gradient text-white shadow-lg shadow-[#560E0F]/30 border border-[#8F3033]/50' : 'text-[#94A3B8] hover:text-white hover:bg-[#1E293B]' }}"
                   :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                    <i data-lucide="gift" class="w-4 h-4 shrink-0 {{ request()->routeIs('shop.*') ? 'text-white' : 'text-[#94A3B8] group-hover:text-white' }}"></i>
                    <span class="truncate" x-show="!sidebarCollapsed">{{ __('Rewards Marketplace') }}</span>
                    <span x-show="sidebarCollapsed" class="sr-only">{{ __('Rewards Marketplace') }}</span>
                    <div x-show="sidebarCollapsed" class="absolute left-full ms-2 px-2.5 py-1 bg-[#172033] text-white text-xs rounded-md shadow-xl border border-[#334155] whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity z-50">
                        {{ __('Rewards Marketplace') }}
                    </div>
                </a>

                <a href="{{ route('leaderboard.index') }}"
                   class="group relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-150 {{ request()->routeIs('leaderboard.*') ? 'bg-burgundy-gradient text-white shadow-lg shadow-[#560E0F]/30 border border-[#8F3033]/50' : 'text-[#94A3B8] hover:text-white hover:bg-[#1E293B]' }}"
                   :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                    <i data-lucide="trophy" class="w-4 h-4 shrink-0 {{ request()->routeIs('leaderboard.*') ? 'text-white' : 'text-[#94A3B8] group-hover:text-white' }}"></i>
                    <span class="truncate" x-show="!sidebarCollapsed">{{ __('Leaderboard & Badges') }}</span>
                    <span x-show="sidebarCollapsed" class="sr-only">{{ __('Leaderboard & Badges') }}</span>
                    <div x-show="sidebarCollapsed" class="absolute left-full ms-2 px-2.5 py-1 bg-[#172033] text-white text-xs rounded-md shadow-xl border border-[#334155] whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity z-50">
                        {{ __('Leaderboard & Badges') }}
                    </div>
                </a>
            </div>
        </div>

        @if(Auth::user()->isEventOrganizer() || Auth::user()->isAdmin() || Auth::user()->isSuperAdmin())
        <div>
            <div class="px-3 mb-2 transition-all duration-200" x-show="!sidebarCollapsed">
                <span class="text-[10px] font-bold uppercase tracking-widest text-amber-400">Event Operations</span>
            </div>
            <div class="space-y-1">
                <a href="{{ route('organizer.scanner') }}"
                   class="group relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-150 {{ request()->routeIs('organizer.scanner') ? 'bg-amber-500/20 text-amber-300 border border-amber-500/40' : 'text-amber-300/80 hover:text-amber-200 hover:bg-[#1E293B]' }}"
                   :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                    <i data-lucide="scan-line" class="w-4 h-4 shrink-0 text-amber-400"></i>
                    <span class="truncate" x-show="!sidebarCollapsed">{{ __('Organizer Scanner') }}</span>
                    <span x-show="sidebarCollapsed" class="sr-only">{{ __('Organizer Scanner') }}</span>
                    <div x-show="sidebarCollapsed" class="absolute left-full ms-2 px-2.5 py-1 bg-[#172033] text-amber-300 text-xs rounded-md shadow-xl border border-[#334155] whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity z-50">
                        {{ __('Organizer Scanner') }}
                    </div>
                </a>
            </div>
        </div>
        @endif

        <div>
            <div class="px-3 mb-2 transition-all duration-200" x-show="!sidebarCollapsed">
                <span class="text-[10px] font-bold uppercase tracking-widest text-[#94A3B8]">Community</span>
            </div>
            <div class="space-y-1">
                <a href="{{ route('directory.index') }}"
                   class="group relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-150 {{ request()->routeIs('directory.*') ? 'bg-burgundy-gradient text-white shadow-lg shadow-[#560E0F]/30 border border-[#8F3033]/50' : 'text-[#94A3B8] hover:text-white hover:bg-[#1E293B]' }}"
                   :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                    <i data-lucide="users" class="w-4 h-4 shrink-0 {{ request()->routeIs('directory.*') ? 'text-white' : 'text-[#94A3B8] group-hover:text-white' }}"></i>
                    <span class="truncate" x-show="!sidebarCollapsed">{{ __('Member Directory') }}</span>
                    <span x-show="sidebarCollapsed" class="sr-only">{{ __('Member Directory') }}</span>
                    <div x-show="sidebarCollapsed" class="absolute left-full ms-2 px-2.5 py-1 bg-[#172033] text-white text-xs rounded-md shadow-xl border border-[#334155] whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity z-50">
                        {{ __('Member Directory') }}
                    </div>
                </a>

                <a href="{{ route('announcements.index') }}"
                   class="group relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-150 {{ request()->routeIs('announcements.*') ? 'bg-burgundy-gradient text-white shadow-lg shadow-[#560E0F]/30 border border-[#8F3033]/50' : 'text-[#94A3B8] hover:text-white hover:bg-[#1E293B]' }}"
                   :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                    <i data-lucide="megaphone" class="w-4 h-4 shrink-0 {{ request()->routeIs('announcements.*') ? 'text-white' : 'text-[#94A3B8] group-hover:text-white' }}"></i>
                    <span class="truncate" x-show="!sidebarCollapsed">{{ __('Announcements') }}</span>
                    <span x-show="sidebarCollapsed" class="sr-only">{{ __('Announcements') }}</span>
                    <div x-show="sidebarCollapsed" class="absolute left-full ms-2 px-2.5 py-1 bg-[#172033] text-white text-xs rounded-md shadow-xl border border-[#334155] whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity z-50">
                        {{ __('Announcements') }}
                    </div>
                </a>
                <a href="{{ route('projects.index') }}"
                   class="group relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-150 {{ request()->routeIs('projects.*') ? 'bg-burgundy-gradient text-white shadow-lg shadow-[#560E0F]/30 border border-[#8F3033]/50' : 'text-[#94A3B8] hover:text-white hover:bg-[#1E293B]' }}"
                   :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                    <i data-lucide="code-2" class="w-4 h-4 shrink-0 {{ request()->routeIs('projects.*') ? 'text-white' : 'text-[#94A3B8] group-hover:text-white' }}"></i>
                    <span class="truncate" x-show="!sidebarCollapsed">{{ __('Project Collaboration') }}</span>
                    <span x-show="sidebarCollapsed" class="sr-only">{{ __('Project Collaboration') }}</span>
                    <div x-show="sidebarCollapsed" class="absolute left-full ms-2 px-2.5 py-1 bg-[#172033] text-white text-xs rounded-md shadow-xl border border-[#334155] whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity z-50">
                        {{ __('Project Collaboration') }}
                    </div>
                </a>

                <a href="{{ route('volunteers.index') }}"
                   class="group relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-150 {{ request()->routeIs('volunteers.*') ? 'bg-burgundy-gradient text-white shadow-lg shadow-[#560E0F]/30 border border-[#8F3033]/50' : 'text-[#94A3B8] hover:text-white hover:bg-[#1E293B]' }}"
                   :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                    <i data-lucide="heart-handshake" class="w-4 h-4 shrink-0 {{ request()->routeIs('volunteers.*') ? 'text-white' : 'text-[#94A3B8] group-hover:text-white' }}"></i>
                    <span class="truncate" x-show="!sidebarCollapsed">{{ __('Volunteer Board') }}</span>
                    <span x-show="sidebarCollapsed" class="sr-only">{{ __('Volunteer Board') }}</span>
                    <div x-show="sidebarCollapsed" class="absolute left-full ms-2 px-2.5 py-1 bg-[#172033] text-white text-xs rounded-md shadow-xl border border-[#334155] whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity z-50">
                        {{ __('Volunteer Board') }}
                    </div>
                </a>

                <a href="{{ route('helpdesk.index') }}"
                   class="group relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-150 {{ request()->routeIs('helpdesk.*') ? 'bg-burgundy-gradient text-white shadow-lg shadow-[#560E0F]/30 border border-[#8F3033]/50' : 'text-[#94A3B8] hover:text-white hover:bg-[#1E293B]' }}"
                   :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                    <i data-lucide="life-buoy" class="w-4 h-4 shrink-0 {{ request()->routeIs('helpdesk.*') ? 'text-white' : 'text-[#94A3B8] group-hover:text-white' }}"></i>
                    <span class="truncate" x-show="!sidebarCollapsed">{{ __('Help Desk & Disputes') }}</span>
                    <span x-show="sidebarCollapsed" class="sr-only">{{ __('Help Desk & Disputes') }}</span>
                    <div x-show="sidebarCollapsed" class="absolute left-full ms-2 px-2.5 py-1 bg-[#172033] text-white text-xs rounded-md shadow-xl border border-[#334155] whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity z-50">
                        {{ __('Help Desk & Disputes') }}
                    </div>
                </a>
            </div>
        </div>

        <div>
            <div class="px-3 mb-2 transition-all duration-200" x-show="!sidebarCollapsed">
                <span class="text-[10px] font-bold uppercase tracking-widest text-[#94A3B8]">Account</span>
            </div>
            <div class="space-y-1">
                <a href="{{ route('profile.edit') }}"
                   class="group relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-150 {{ request()->routeIs('profile.*') ? 'bg-burgundy-gradient text-white shadow-lg shadow-[#560E0F]/30 border border-[#8F3033]/50' : 'text-[#94A3B8] hover:text-white hover:bg-[#1E293B]' }}"
                   :class="sidebarCollapsed ? 'justify-center px-0' : ''">
                    <i data-lucide="user" class="w-4 h-4 shrink-0 {{ request()->routeIs('profile.*') ? 'text-white' : 'text-[#94A3B8] group-hover:text-white' }}"></i>
                    <span class="truncate" x-show="!sidebarCollapsed">{{ __('Profile & Security') }}</span>
                    <span x-show="sidebarCollapsed" class="sr-only">{{ __('Profile & Security') }}</span>
                    <div x-show="sidebarCollapsed" class="absolute left-full ms-2 px-2.5 py-1 bg-[#172033] text-white text-xs rounded-md shadow-xl border border-[#334155] whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity z-50">
                        {{ __('Profile & Security') }}
                    </div>
                </a>

                @if(Auth::user()->isAdmin() || Auth::user()->isSuperAdmin() || Auth::user()->isStaff() || Auth::user()->isTreasurer())
                <a href="{{ route('admin.dashboard') }}"
                   class="group relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-150 bg-amber-500/10 text-amber-300 hover:bg-amber-500/20 hover:text-white border border-amber-500/30"
                   :class="sidebarCollapsed ? 'justify-center px-0' : ''"
                   title="Switch to Executive & Officer Administration Console">
                    <i data-lucide="shield" class="w-4 h-4 shrink-0 text-amber-400"></i>
                    <span class="truncate" x-show="!sidebarCollapsed">{{ __('Officer Admin Console') }}</span>
                    <span x-show="sidebarCollapsed" class="sr-only">{{ __('Officer Admin Console') }}</span>
                    <div x-show="sidebarCollapsed" class="absolute left-full ms-2 px-2.5 py-1 bg-[#172033] text-amber-300 text-xs rounded-md shadow-xl border border-amber-500/30 whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity z-50">
                        {{ __('Officer Admin Console') }}
                    </div>
                </a>
                @endif
            </div>
        </div>
    </nav>
</aside>

<div class="lg:hidden">
    <div x-show="mobileDrawerOpen"
         x-transition:enter="transition-opacity ease-linear duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="mobileDrawerOpen = false"
         class="fixed inset-0 bg-black/75 backdrop-blur-sm z-50"
         style="display: none;"></div>

    <aside x-show="mobileDrawerOpen"
           x-transition:enter="transition ease-out duration-300 transform"
           x-transition:enter-start="-translate-x-full"
           x-transition:enter-end="translate-x-0"
           x-transition:leave="transition ease-in duration-200 transform"
           x-transition:leave-start="translate-x-0"
           x-transition:leave-end="-translate-x-full"
           @keydown.escape.window="mobileDrawerOpen = false"
           class="fixed inset-y-0 left-0 z-50 w-80 max-w-[85vw] bg-[#172033] border-r border-[#334155] flex flex-col shadow-2xl"
           style="display: none;">

        <div class="h-16 flex items-center justify-between border-b border-[#334155] px-5 shrink-0 bg-[#0B1120]/40">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-[#0B1120] border border-[#334155] p-1 flex items-center justify-center shrink-0 shadow-md">
                    <img src="{{ asset('images/COMSOC.png') }}" alt="Computing Society Logo" class="w-full h-full object-contain">
                </div>
                <div>
                    <span class="font-extrabold text-[#F8FAFC] text-sm tracking-tight font-brand-display block">COMSOC</span>
                    <span class="text-[10px] text-[#df747a] font-semibold tracking-wider uppercase block">QR Attendance</span>
                </div>
            </a>

            <button type="button"
                    @click="mobileDrawerOpen = false"
                    class="text-slate-400 hover:text-white p-2 rounded-xl hover:bg-[#1E293B] transition focus:outline-none focus:ring-2 focus:ring-[#8F3033]"
                    aria-label="Close navigation drawer">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto px-4 py-5 space-y-6 scrollbar-none">
            <div>
                <p class="px-3 mb-2 text-[10px] font-bold uppercase tracking-widest text-[#94A3B8]">Overview</p>
                <div class="space-y-1">
                    <a href="{{ route('dashboard') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('dashboard') ? 'bg-burgundy-gradient text-white shadow-md' : 'text-[#94A3B8] hover:text-white hover:bg-[#1E293B]' }}">
                        <i data-lucide="layout-dashboard" class="w-4 h-4 shrink-0 {{ request()->routeIs('dashboard') ? 'text-white' : 'text-[#94A3B8]' }}"></i>
                        <span>{{ __('Dashboard') }}</span>
                    </a>

                    <a href="{{ route('member.dynamic_qr') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('member.dynamic_qr') ? 'bg-burgundy-gradient text-white shadow-md' : 'text-[#94A3B8] hover:text-white hover:bg-[#1E293B]' }}">
                        <i data-lucide="qr-code" class="w-4 h-4 shrink-0 {{ request()->routeIs('member.dynamic_qr') ? 'text-white' : 'text-[#94A3B8]' }}"></i>
                        <span>{{ __('Dynamic Attendance QR') }}</span>
                    </a>
                </div>
            </div>

            <div>
                <p class="px-3 mb-2 text-[10px] font-bold uppercase tracking-widest text-[#94A3B8]">Participation</p>
                <div class="space-y-1">
                    <a href="{{ route('events.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('events.*') ? 'bg-burgundy-gradient text-white shadow-md' : 'text-[#94A3B8] hover:text-white hover:bg-[#1E293B]' }}">
                        <i data-lucide="calendar" class="w-4 h-4 shrink-0 {{ request()->routeIs('events.*') ? 'text-white' : 'text-[#94A3B8]' }}"></i>
                        <span>{{ __('Events & Activities') }}</span>
                    </a>
                    <a href="{{ route('tickets.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('tickets.*') ? 'bg-burgundy-gradient text-white shadow-md' : 'text-[#94A3B8] hover:text-white hover:bg-[#1E293B]' }}">
                        <i data-lucide="ticket" class="w-4 h-4 shrink-0 {{ request()->routeIs('tickets.*') ? 'text-white' : 'text-[#94A3B8]' }}"></i>
                        <span>{{ __('My Tickets') }}</span>
                    </a>
                    <a href="{{ route('ledger.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('ledger.*') ? 'bg-burgundy-gradient text-white shadow-md' : 'text-[#94A3B8] hover:text-white hover:bg-[#1E293B]' }}">
                        <i data-lucide="history" class="w-4 h-4 shrink-0 {{ request()->routeIs('ledger.*') ? 'text-white' : 'text-[#94A3B8]' }}"></i>
                        <span>{{ __('Attendance & EXP Ledger') }}</span>
                    </a>
                </div>
            </div>

            <div>
                <p class="px-3 mb-2 text-[10px] font-bold uppercase tracking-widest text-[#94A3B8]">Rewards & Progression</p>
                <div class="space-y-1">
                    <a href="{{ route('missions.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('missions.*') ? 'bg-burgundy-gradient text-white shadow-md' : 'text-[#94A3B8] hover:text-white hover:bg-[#1E293B]' }}">
                        <i data-lucide="target" class="w-4 h-4 shrink-0 {{ request()->routeIs('missions.*') ? 'text-white' : 'text-[#94A3B8]' }}"></i>
                        <span>{{ __('Missions & Quests') }}</span>
                    </a>
                    <a href="{{ route('shop.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('shop.*') ? 'bg-burgundy-gradient text-white shadow-md' : 'text-[#94A3B8] hover:text-white hover:bg-[#1E293B]' }}">
                        <i data-lucide="gift" class="w-4 h-4 shrink-0 {{ request()->routeIs('shop.*') ? 'text-white' : 'text-[#94A3B8]' }}"></i>
                        <span>{{ __('Rewards Marketplace') }}</span>
                    </a>
                    <a href="{{ route('leaderboard.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('leaderboard.*') ? 'bg-burgundy-gradient text-white shadow-md' : 'text-[#94A3B8] hover:text-white hover:bg-[#1E293B]' }}">
                        <i data-lucide="trophy" class="w-4 h-4 shrink-0 {{ request()->routeIs('leaderboard.*') ? 'text-white' : 'text-[#94A3B8]' }}"></i>
                        <span>{{ __('Leaderboard & Badges') }}</span>
                    </a>
                </div>
            </div>

            <div>
                <p class="px-3 mb-2 text-[10px] font-bold uppercase tracking-widest text-[#94A3B8]">Community</p>
                <div class="space-y-1">
                    <a href="{{ route('directory.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('directory.*') ? 'bg-burgundy-gradient text-white shadow-md' : 'text-[#94A3B8] hover:text-white hover:bg-[#1E293B]' }}">
                        <i data-lucide="users" class="w-4 h-4 shrink-0 {{ request()->routeIs('directory.*') ? 'text-white' : 'text-[#94A3B8]' }}"></i>
                        <span>{{ __('Member Directory') }}</span>
                    </a>

                    <a href="{{ route('announcements.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('announcements.*') ? 'bg-burgundy-gradient text-white shadow-md' : 'text-[#94A3B8] hover:text-white hover:bg-[#1E293B]' }}">
                        <i data-lucide="megaphone" class="w-4 h-4 shrink-0 {{ request()->routeIs('announcements.*') ? 'text-white' : 'text-[#94A3B8]' }}"></i>
                        <span>{{ __('Announcements') }}</span>
                    </a>

                    <a href="{{ route('projects.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('projects.*') ? 'bg-burgundy-gradient text-white shadow-md' : 'text-[#94A3B8] hover:text-white hover:bg-[#1E293B]' }}">
                        <i data-lucide="code-2" class="w-4 h-4 shrink-0 {{ request()->routeIs('projects.*') ? 'text-white' : 'text-[#94A3B8]' }}"></i>
                        <span>{{ __('Project Collaboration') }}</span>
                    </a>

                    <a href="{{ route('volunteers.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('volunteers.*') ? 'bg-burgundy-gradient text-white shadow-md' : 'text-[#94A3B8] hover:text-white hover:bg-[#1E293B]' }}">
                        <i data-lucide="heart-handshake" class="w-4 h-4 shrink-0 {{ request()->routeIs('volunteers.*') ? 'text-white' : 'text-[#94A3B8]' }}"></i>
                        <span>{{ __('Volunteer Board') }}</span>
                    </a>

                    <a href="{{ route('helpdesk.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('helpdesk.*') ? 'bg-burgundy-gradient text-white shadow-md' : 'text-[#94A3B8] hover:text-white hover:bg-[#1E293B]' }}">
                        <i data-lucide="life-buoy" class="w-4 h-4 shrink-0 {{ request()->routeIs('helpdesk.*') ? 'text-white' : 'text-[#94A3B8]' }}"></i>
                        <span>{{ __('Help Desk & Disputes') }}</span>
                    </a>
                </div>
            </div>

            @if(Auth::user()->isEventOrganizer() || Auth::user()->isAdmin() || Auth::user()->isSuperAdmin())
            <div>
                <p class="px-3 mb-2 text-[10px] font-bold uppercase tracking-widest text-amber-400">Event Operations</p>
                <div class="space-y-1">
                    <a href="{{ route('organizer.scanner') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('organizer.scanner') ? 'bg-amber-500/20 text-amber-300 border border-amber-500/40' : 'text-amber-300/80 hover:text-amber-200 hover:bg-[#1E293B]' }}">
                        <i data-lucide="scan-line" class="w-4 h-4 shrink-0 text-amber-400"></i>
                        <span>{{ __('Organizer Scanner') }}</span>
                    </a>
                </div>
            </div>
            @endif

            <div>
                <p class="px-3 mb-2 text-[10px] font-bold uppercase tracking-widest text-[#94A3B8]">Account & Security</p>
                <div class="space-y-1">
                    <a href="{{ route('notifications.index') }}"
                       class="flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('notifications.*') ? 'bg-burgundy-gradient text-white shadow-md' : 'text-[#94A3B8] hover:text-white hover:bg-[#1E293B]' }}">
                        <div class="flex items-center gap-3">
                            <i data-lucide="bell" class="w-4 h-4 shrink-0 {{ request()->routeIs('notifications.*') ? 'text-white' : 'text-[#94A3B8]' }}"></i>
                            <span>{{ __('Notifications') }}</span>
                        </div>
                        @php $unreadCount = Auth::user()->notifications()->whereNull('read_at')->count(); @endphp
                        @if($unreadCount > 0)
                        <span class="px-1.5 py-0.5 rounded text-[10px] bg-rose-500/20 text-rose-300 font-bold border border-rose-500/30">
                            {{ $unreadCount }}
                        </span>
                        @endif
                    </a>
                    <a href="{{ route('profile.edit') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('profile.*') ? 'bg-burgundy-gradient text-white shadow-md' : 'text-[#94A3B8] hover:text-white hover:bg-[#1E293B]' }}">
                        <i data-lucide="user" class="w-4 h-4 shrink-0 {{ request()->routeIs('profile.*') ? 'text-white' : 'text-[#94A3B8]' }}"></i>
                        <span>{{ __('Profile & Security') }}</span>
                    </a>

                    @if(Auth::user()->isAdmin() || Auth::user()->isSuperAdmin() || Auth::user()->isStaff() || Auth::user()->isTreasurer())
                    <a href="{{ route('admin.dashboard') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition bg-[#1E293B] text-amber-300 hover:bg-[#334155] hover:text-white border border-amber-500/30">
                        <i data-lucide="shield" class="w-4 h-4 shrink-0 text-amber-400"></i>
                        <span>{{ __('Officer & Admin Panel') }}</span>
                    </a>
                    @endif
                </div>
            </div>
        </nav>

        <div class="p-4 border-t border-[#334155] bg-[#0B1120]/40 shrink-0">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-9 h-9 rounded-xl bg-[#560E0F] flex items-center justify-center text-white shrink-0 font-bold text-xs shadow-inner">
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                </div>
                <div class="overflow-hidden">
                    <p class="text-xs font-bold text-white truncate">{{ Auth::user()->name }}</p>
                    <p class="text-[10px] text-slate-400 truncate">{{ Auth::user()->email }}</p>
                </div>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-xl text-xs font-semibold text-rose-400 hover:text-rose-300 hover:bg-rose-950/40 border border-rose-500/20 transition">
                    <i data-lucide="log-out" class="w-4 h-4"></i>
                    <span>{{ __('Log Out') }}</span>
                </button>
            </form>
        </div>
    </aside>
</div>

<nav aria-label="Mobile Navigation"
     class="lg:hidden fixed bottom-3 inset-x-3 sm:inset-x-8 max-w-lg mx-auto z-40 bg-[#172033]/95 backdrop-blur-lg border border-[#334155] rounded-2xl shadow-2xl p-1.5 transition-all duration-300 safe-area-bottom">
    <div class="grid grid-cols-5 items-center gap-1">
        <a href="{{ route('dashboard') }}"
           class="flex flex-col items-center justify-center py-2 px-1 rounded-xl transition-all duration-150 min-h-[48px] {{ request()->routeIs('dashboard') ? 'bg-burgundy-gradient text-white shadow-md' : 'text-[#94A3B8] hover:text-[#F8FAFC] hover:bg-[#1E293B]' }}"
           aria-label="Home">
            <i data-lucide="home" class="w-5 h-5 shrink-0 {{ request()->routeIs('dashboard') ? 'text-white' : 'text-[#94A3B8]' }}"></i>
            <span class="text-[10px] font-semibold mt-1 truncate">Home</span>
        </a>

        <a href="{{ route('member.dynamic_qr') }}"
           class="flex flex-col items-center justify-center py-2 px-1 rounded-xl transition-all duration-150 min-h-[48px] {{ request()->routeIs('member.dynamic_qr') ? 'bg-burgundy-gradient text-white shadow-md' : 'text-[#94A3B8] hover:text-[#F8FAFC] hover:bg-[#1E293B]' }}"
           aria-label="QR Pass">
            <i data-lucide="qr-code" class="w-5 h-5 shrink-0 {{ request()->routeIs('member.dynamic_qr') ? 'text-white' : 'text-[#94A3B8]' }}"></i>
            <span class="text-[10px] font-semibold mt-1 truncate">QR Pass</span>
        </a>

        <a href="{{ route('shop.index') }}"
           class="flex flex-col items-center justify-center py-2 px-1 rounded-xl transition-all duration-150 min-h-[48px] {{ request()->routeIs('shop.*') ? 'bg-burgundy-gradient text-white shadow-md' : 'text-[#94A3B8] hover:text-[#F8FAFC] hover:bg-[#1E293B]' }}"
           aria-label="Rewards">
            <i data-lucide="gift" class="w-5 h-5 shrink-0 {{ request()->routeIs('shop.*') ? 'text-white' : 'text-[#94A3B8]' }}"></i>
            <span class="text-[10px] font-semibold mt-1 truncate">Rewards</span>
        </a>

        <a href="{{ route('leaderboard.index') }}"
           class="flex flex-col items-center justify-center py-2 px-1 rounded-xl transition-all duration-150 min-h-[48px] {{ request()->routeIs('leaderboard.*') ? 'bg-burgundy-gradient text-white shadow-md' : 'text-[#94A3B8] hover:text-[#F8FAFC] hover:bg-[#1E293B]' }}"
           aria-label="Rankings">
            <i data-lucide="trophy" class="w-5 h-5 shrink-0 {{ request()->routeIs('leaderboard.*') ? 'text-white' : 'text-[#94A3B8]' }}"></i>
            <span class="text-[10px] font-semibold mt-1 truncate">Rankings</span>
        </a>

        <button type="button"
                @click="mobileDrawerOpen = true"
                class="flex flex-col items-center justify-center py-2 px-1 rounded-xl transition-all duration-150 min-h-[48px] {{ request()->routeIs('profile.*', 'notifications.*', 'ledger.*', 'missions.*', 'directory.*', 'opportunities.*', 'announcements.*', 'projects.*', 'volunteers.*', 'helpdesk.*', 'admin.*', 'organizer.*') ? 'bg-burgundy-gradient text-white shadow-md' : 'text-[#94A3B8] hover:text-[#F8FAFC] hover:bg-[#1E293B]' }}"
                aria-label="Profile and more modules">
            <i data-lucide="user" class="w-5 h-5 shrink-0 {{ request()->routeIs('profile.*', 'notifications.*', 'ledger.*', 'missions.*', 'directory.*', 'opportunities.*', 'announcements.*', 'projects.*', 'volunteers.*', 'helpdesk.*', 'admin.*', 'organizer.*') ? 'text-white' : 'text-[#94A3B8]' }}"></i>
            <span class="text-[10px] font-semibold mt-1 truncate">Menu</span>
        </button>
    </div>
</nav>
