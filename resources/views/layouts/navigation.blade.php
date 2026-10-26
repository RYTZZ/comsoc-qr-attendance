<!-- Navigation Overlay Backdrop for both Desktop & Mobile -->
<div x-show="drawerOpen"
     x-transition:enter="transition-opacity ease-linear duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-linear duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @click="closeDrawer()"
     class="fixed inset-0 bg-black/75 backdrop-blur-sm z-40"
     style="display: none;"
     aria-hidden="true"></div>

<!-- Sliding Navigation Drawer (Fully hidden when closed, 0px residual strip) -->
<aside id="navigation-drawer"
       x-show="drawerOpen"
       x-transition:enter="transition ease-out duration-300 transform"
       x-transition:enter-start="-translate-x-full"
       x-transition:enter-end="translate-x-0"
       x-transition:leave="transition ease-in duration-200 transform"
       x-transition:leave-start="translate-x-0"
       x-transition:leave-end="-translate-x-full"
       @keydown.escape.window="closeDrawer()"
       class="fixed inset-y-0 left-0 z-50 w-80 max-w-[85vw] bg-[#172033] border-r border-[#334155] flex flex-col shadow-2xl"
       style="display: none;"
       role="dialog"
       aria-modal="true"
       aria-label="Navigation Menu">

    <!-- Drawer Header -->
    <div class="h-16 flex items-center justify-between border-b border-[#334155] px-5 shrink-0 bg-[#0B1120]/60">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 group">
            <div class="w-9 h-9 rounded-xl bg-[#0B1120] border border-[#334155] p-1.5 flex items-center justify-center shrink-0 shadow-md group-hover:border-[#8F3033] transition">
                <img src="{{ asset('images/COMSOC.png') }}" alt="Computing Society Logo" class="w-full h-full object-contain">
            </div>
            <div>
                <span class="font-extrabold text-[#F8FAFC] text-sm tracking-tight font-brand-display block leading-tight">COMSOC HUB</span>
                <span class="text-[10px] text-[#df747a] font-semibold tracking-wider uppercase block leading-none">QR Attendance & EXP</span>
            </div>
        </a>

        <!-- Clear Close Button -->
        <button type="button"
                id="drawer-close-button"
                @click="closeDrawer()"
                class="text-slate-400 hover:text-white p-2 rounded-xl hover:bg-[#1E293B] focus:outline-none focus:ring-2 focus:ring-[#8F3033] transition"
                aria-label="Close navigation menu">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>
    </div>

    <!-- Drawer Navigation Links -->
    <nav class="flex-1 overflow-y-auto px-4 py-5 space-y-6 scrollbar-none" aria-label="Main Navigation">
        <!-- Overview -->
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

                <a href="{{ route('signature.show') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('signature.*') ? 'bg-burgundy-gradient text-white shadow-md' : 'text-[#94A3B8] hover:text-white hover:bg-[#1E293B]' }}">
                    <i data-lucide="pen-tool" class="w-4 h-4 shrink-0 {{ request()->routeIs('signature.*') ? 'text-white' : 'text-[#94A3B8]' }}"></i>
                    <span>{{ __('My Signature') }}</span>
                </a>

            </div>
        </div>

        <!-- Participation -->
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

        <!-- Rewards & Progression -->
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

        <!-- Organization -->
        <div>
            <p class="px-3 mb-2 text-[10px] font-bold uppercase tracking-widest text-[#94A3B8]">Organization</p>
            <div class="space-y-1">
                <a href="{{ route('constitution.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('constitution.*') ? 'bg-burgundy-gradient text-white shadow-md' : 'text-[#94A3B8] hover:text-white hover:bg-[#1E293B]' }}">
                    <i data-lucide="scroll-text" class="w-4 h-4 shrink-0 {{ request()->routeIs('constitution.*') ? 'text-white' : 'text-[#94A3B8]' }}"></i>
                    <span>{{ __('Constitutional & By-Laws') }}</span>
                </a>
            </div>
        </div>

        <!-- Community -->
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

        <!-- Event Operations (Organizer Clearance) -->
        @if(Auth::user()->isEventOrganizer() || Auth::user()->isAdmin() || Auth::user()->isSuperAdmin())
        <div>
            <p class="px-3 mb-2 text-[10px] font-bold uppercase tracking-widest text-amber-400">Event Operations</p>
            <div class="space-y-1">
                <a href="{{ route('organizer.scanner') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('organizer.scanner') ? 'bg-amber-500/20 text-amber-300 border border-amber-500/40' : 'text-amber-300/90 hover:text-amber-200 hover:bg-[#1E293B]' }}">
                    <i data-lucide="scan-line" class="w-4 h-4 shrink-0 text-amber-400"></i>
                    <span>{{ __('Organizer Scanner') }}</span>
                </a>
            </div>
        </div>
        @endif

        <!-- Officer & Admin Console (Authorized Roles) -->
        @if(Auth::user()->isAdmin() || Auth::user()->isSuperAdmin() || Auth::user()->isStaff() || Auth::user()->isTreasurer())
        <div>
            <p class="px-3 mb-2 text-[10px] font-bold uppercase tracking-widest text-amber-400">Administration</p>
            <div class="space-y-1">
                <a href="{{ route('admin.dashboard') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition bg-amber-500/10 text-amber-300 hover:bg-amber-500/20 hover:text-white border border-amber-500/30">
                    <i data-lucide="shield" class="w-4 h-4 shrink-0 text-amber-400"></i>
                    <span>{{ __('Officer & Admin Console') }}</span>
                </a>
            </div>
        </div>
        @endif

        <!-- Account & Security -->
        <div>
            <p class="px-3 mb-2 text-[10px] font-bold uppercase tracking-widest text-[#94A3B8]">Account & Security</p>
            <div class="space-y-1">
                <a href="{{ route('notifications.index') }}"
                   class="flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('notifications.*') ? 'bg-burgundy-gradient text-white shadow-md' : 'text-[#94A3B8] hover:text-white hover:bg-[#1E293B]' }}">
                    <div class="flex items-center gap-3">
                        <i data-lucide="bell" class="w-4 h-4 shrink-0 {{ request()->routeIs('notifications.*') ? 'text-white' : 'text-[#94A3B8]' }}"></i>
                        <span>{{ __('Notifications') }}</span>
                    </div>
                    @php $unreadCount = Auth::user()->appNotifications()->whereNull('read_at')->count(); @endphp
                    @if($unreadCount > 0)
                    <span class="px-1.5 py-0.5 rounded text-[10px] bg-rose-500/20 text-rose-300 font-bold border border-rose-500/30">
                        {{ $unreadCount }}
                    </span>
                    @endif
                </a>

                <a href="{{ route('profile.edit') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('profile.edit') ? 'bg-burgundy-gradient text-white shadow-md' : 'text-[#94A3B8] hover:text-white hover:bg-[#1E293B]' }}">
                    <i data-lucide="user" class="w-4 h-4 shrink-0 {{ request()->routeIs('profile.edit') ? 'text-white' : 'text-[#94A3B8]' }}"></i>
                    <span>{{ __('Member Profile') }}</span>
                </a>

                <a href="{{ route('settings.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('settings.*') ? 'bg-burgundy-gradient text-white shadow-md' : 'text-[#94A3B8] hover:text-white hover:bg-[#1E293B]' }}">
                    <i data-lucide="settings" class="w-4 h-4 shrink-0 {{ request()->routeIs('settings.*') ? 'text-white' : 'text-[#94A3B8]' }}"></i>
                    <span>{{ __('Settings & Preferences') }}</span>
                </a>

                <a href="{{ route('profile.sessions') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('profile.sessions*') ? 'bg-burgundy-gradient text-white shadow-md' : 'text-[#94A3B8] hover:text-white hover:bg-[#1E293B]' }}">
                    <i data-lucide="shield-check" class="w-4 h-4 shrink-0 {{ request()->routeIs('profile.sessions*') ? 'text-white' : 'text-[#94A3B8]' }}"></i>
                    <span>{{ __('Active Sessions & Security') }}</span>
                </a>

                <a href="{{ route('privacy') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition {{ request()->routeIs('privacy*') ? 'bg-burgundy-gradient text-white shadow-md' : 'text-[#94A3B8] hover:text-white hover:bg-[#1E293B]' }}">
                    <i data-lucide="file-text" class="w-4 h-4 shrink-0 {{ request()->routeIs('privacy*') ? 'text-white' : 'text-[#94A3B8]' }}"></i>
                    <span>{{ __('Privacy Notice') }}</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Drawer Footer User Profile & Logout -->
    <div class="p-4 border-t border-[#334155] bg-[#0B1120]/60 shrink-0">
        <div class="flex items-center gap-3 mb-3">
            <x-user-avatar :user="Auth::user()" size="md" class="rounded-xl border border-[#334155]" />
            <div class="overflow-hidden min-w-0">
                <p class="text-xs font-bold text-white truncate">{{ Auth::user()->name }}</p>
                <p class="text-[10px] text-slate-400 truncate">{{ Auth::user()->email }}</p>
                <span class="inline-block px-1.5 py-0.2 rounded text-[9px] font-semibold bg-[#560E0F] text-[#df747a] border border-[#8F3033] uppercase mt-0.5">
                    {{ Auth::user()->role ?? 'Member' }}
                </span>
            </div>
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-xl text-xs font-semibold text-rose-400 hover:text-white hover:bg-rose-950/40 border border-rose-500/25 transition">
                <i data-lucide="log-out" class="w-4 h-4"></i>
                <span>{{ __('Log Out') }}</span>
            </button>
        </form>
    </div>
</aside>

<!-- Mobile Floating Bottom Navigation Bar -->
<nav aria-label="Mobile Bottom Navigation"
     x-show="!readerFullscreen"
     x-transition:enter="transition-all ease-out duration-200"
     x-transition:enter-start="opacity-0 translate-y-6"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition-all ease-in duration-150"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 translate-y-6"
     class="lg:hidden fixed bottom-3 inset-x-3 sm:inset-x-8 max-w-lg mx-auto z-30 bg-[#172033]/95 backdrop-blur-lg border border-[#334155] rounded-2xl shadow-2xl p-1.5 transition-all duration-300 safe-area-bottom">
    <div class="grid grid-cols-5 items-center gap-1">
        <a href="{{ route('dashboard') }}"
           class="flex flex-col items-center justify-center py-2 px-1 rounded-xl transition-all duration-150 min-h-[48px] {{ request()->routeIs('dashboard') ? 'bg-burgundy-gradient text-white shadow-md' : 'text-[#94A3B8] hover:text-[#F8FAFC] hover:bg-[#1E293B]' }}"
           aria-label="Home Dashboard">
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
                @click="openDrawer()"
                class="flex flex-col items-center justify-center py-2 px-1 rounded-xl transition-all duration-150 min-h-[48px] {{ request()->routeIs('settings.*', 'profile.*', 'notifications.*', 'ledger.*', 'missions.*', 'directory.*', 'opportunities.*', 'announcements.*', 'projects.*', 'volunteers.*', 'helpdesk.*', 'admin.*', 'organizer.*') ? 'bg-burgundy-gradient text-white shadow-md' : 'text-[#94A3B8] hover:text-[#F8FAFC] hover:bg-[#1E293B]' }}"
                aria-label="Open full navigation menu">
            <i data-lucide="menu" class="w-5 h-5 shrink-0 {{ request()->routeIs('settings.*', 'profile.*', 'notifications.*', 'ledger.*', 'missions.*', 'directory.*', 'opportunities.*', 'announcements.*', 'projects.*', 'volunteers.*', 'helpdesk.*', 'admin.*', 'organizer.*') ? 'text-white' : 'text-[#94A3B8]' }}"></i>
            <span class="text-[10px] font-semibold mt-1 truncate">Menu</span>
        </button>
    </div>
</nav>
