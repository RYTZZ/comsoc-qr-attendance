<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'ComSoc QR Attendance') }}</title>

        <x-favicon />

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600;700&family=Manrope:wght@600;700;800&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-full bg-[#0B1120] text-[#F8FAFC] font-sans antialiased"
          x-data="{
              drawerOpen: false,
              readerFullscreen: false,
              openDrawer() {
                  this.drawerOpen = true;
                  this.$nextTick(() => {
                      if (window.lucideIcons) window.createIcons({ icons: window.lucideIcons });
                      const closeBtn = document.getElementById('drawer-close-button');
                      if (closeBtn) closeBtn.focus();
                  });
              },
              closeDrawer() {
                  this.drawerOpen = false;
              },
              toggleDrawer() {
                  if (this.drawerOpen) {
                      this.closeDrawer();
                  } else {
                      this.openDrawer();
                  }
              }
          }"
          x-effect="document.body.classList.toggle('overflow-hidden', drawerOpen || readerFullscreen)"
          @reader-fullscreen.window="readerFullscreen = $event.detail"
          @keydown.escape.window="closeDrawer()">
        <div class="min-h-screen bg-[#0B1120] obsidian-canvas flex flex-col">
            @include('layouts.navigation')

            <div class="flex-1 flex flex-col min-w-0 w-full transition-all duration-300 ease-in-out">

                <header class="sticky top-0 z-20 bg-[#111827]/90 backdrop-blur-xl border-b border-white/[0.08] px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between shadow-lg">
                    <div class="flex items-center gap-3">
                        <!-- Recognizable Hamburger Toggle Button for both desktop and mobile -->
                        <button type="button"
                                @click="toggleDrawer()"
                                class="inline-flex items-center justify-center p-2 rounded-xl text-slate-300 hover:text-white hover:bg-[#1E293B] focus:outline-none focus:ring-2 focus:ring-[#8F3033] transition"
                                :aria-expanded="drawerOpen.toString()"
                                aria-controls="navigation-drawer"
                                aria-label="Toggle navigation menu">
                            <i data-lucide="menu" class="w-5 h-5"></i>
                        </button>

                        <div class="flex items-center gap-2.5">
                            <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 group">
                                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-[#0B1120] border border-[#334155] p-1 flex items-center justify-center shrink-0 shadow-sm group-hover:border-[#8F3033] transition">
                                    <img src="{{ asset('images/COMSOC.png') }}" alt="Computing Society Logo" class="w-full h-full object-contain">
                                </div>
                                <div class="flex flex-col">
                                    <span class="font-extrabold text-[#F8FAFC] text-sm font-brand-display tracking-tight leading-tight">COMSOC HUB</span>
                                    <span class="text-[9.5px] text-[#df747a] font-semibold tracking-wider uppercase leading-none hidden sm:inline">QR Attendance & EXP</span>
                                </div>
                            </a>
                        </div>

                        <!-- Desktop Top Quick-Nav Pills -->
                        <nav class="hidden lg:flex items-center gap-1 ms-4 ps-4 border-l border-white/10" aria-label="Primary Quick Navigation">
                            <a href="{{ route('dashboard') }}"
                               class="px-3 py-1.5 rounded-xl text-xs font-semibold flex items-center gap-1.5 transition {{ request()->routeIs('dashboard') ? 'bg-[#560E0F]/50 text-white border border-[#8F3033]/60 shadow' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
                                <i data-lucide="layout-dashboard" class="w-3.5 h-3.5 text-[#df747a]"></i>
                                <span>Dashboard</span>
                            </a>
                            <a href="{{ route('activities.index') }}"
                               class="px-3 py-1.5 rounded-xl text-xs font-semibold flex items-center gap-1.5 transition {{ request()->routeIs('activities.*') ? 'bg-[#560E0F]/50 text-white border border-[#8F3033]/60 shadow' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
                                <i data-lucide="layers" class="w-3.5 h-3.5 text-[#df747a]"></i>
                                <span>Activities</span>
                            </a>
                            <a href="{{ route('events.index') }}"
                               class="px-3 py-1.5 rounded-xl text-xs font-semibold flex items-center gap-1.5 transition {{ request()->routeIs('events.*') ? 'bg-[#560E0F]/50 text-white border border-[#8F3033]/60 shadow' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
                                <i data-lucide="calendar" class="w-3.5 h-3.5 text-sky-400"></i>
                                <span>Events</span>
                            </a>
                            <a href="{{ route('missions.index') }}"
                               class="px-3 py-1.5 rounded-xl text-xs font-semibold flex items-center gap-1.5 transition {{ request()->routeIs('missions.*') || request()->routeIs('shop.*') ? 'bg-[#560E0F]/50 text-white border border-[#8F3033]/60 shadow' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
                                <i data-lucide="target" class="w-3.5 h-3.5 text-amber-400"></i>
                                <span>Quests & Shop</span>
                            </a>
                            <a href="{{ route('directory.index') }}"
                               class="px-3 py-1.5 rounded-xl text-xs font-semibold flex items-center gap-1.5 transition {{ request()->routeIs('directory.*') || request()->routeIs('projects.*') ? 'bg-[#560E0F]/50 text-white border border-[#8F3033]/60 shadow' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
                                <i data-lucide="users" class="w-3.5 h-3.5 text-emerald-400"></i>
                                <span>Community</span>
                            </a>
                        </nav>
                    </div>

                    <div class="flex items-center gap-2 sm:gap-3">
                        <a href="{{ route('notifications.index') }}"
                           class="relative p-2 rounded-xl text-slate-300 hover:text-white hover:bg-[#1E293B] transition focus:outline-none focus:ring-2 focus:ring-[#8F3033]"
                           title="Notifications"
                           aria-label="View Notifications">
                            <i data-lucide="bell" class="w-4 h-4"></i>
                            @php $unreadCount = Auth::user()->appNotifications()->whereNull('read_at')->count(); @endphp
                            @if($unreadCount > 0)
                            <span class="absolute top-1 right-1 flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-[#8F3033] ring-2 ring-[#172033]"></span>
                            </span>
                            @endif
                        </a>

                        <x-dropdown align="right" width="56" contentClasses="py-1 bg-[#172033] border border-[#334155] shadow-2xl rounded-xl">
                            <x-slot name="trigger">
                                <button type="button" class="inline-flex items-center gap-2 p-1.5 sm:px-3 sm:py-1.5 border border-[#334155] rounded-xl text-slate-200 bg-[#0B1120] hover:text-white hover:bg-[#1E293B] focus:outline-none focus:ring-2 focus:ring-[#8F3033] transition">
                                    <x-user-avatar :user="Auth::user()" size="sm" class="rounded-lg" />
                                    <div class="hidden sm:block text-left max-w-[120px] truncate">
                                        <div class="text-xs font-semibold text-white truncate leading-tight">{{ Auth::user()->name }}</div>
                                        <div class="text-[10px] text-slate-400 truncate capitalize">{{ Auth::user()->role ?? 'Member' }}</div>
                                    </div>
                                    <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 ms-0.5"></i>
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                <div class="px-4 py-3 border-b border-[#334155] text-xs">
                                    <p class="font-bold text-white truncate">{{ Auth::user()->name }}</p>
                                    <p class="text-[11px] text-slate-400 truncate mt-0.5">{{ Auth::user()->email }}</p>
                                </div>

                                <x-dropdown-link :href="route('profile.edit')" class="text-slate-300 hover:text-white hover:bg-[#1E293B] flex items-center gap-2 text-xs py-2.5">
                                    <i data-lucide="user" class="w-4 h-4 text-slate-400"></i>
                                    {{ __('Member Profile') }}
                                </x-dropdown-link>

                                <x-dropdown-link :href="route('settings.index')" class="text-slate-300 hover:text-white hover:bg-[#1E293B] flex items-center gap-2 text-xs py-2.5">
                                    <i data-lucide="settings" class="w-4 h-4 text-[#df747a]"></i>
                                    {{ __('Settings & Preferences') }}
                                </x-dropdown-link>

                                <x-dropdown-link :href="route('profile.sessions')" class="text-slate-300 hover:text-white hover:bg-[#1E293B] flex items-center gap-2 text-xs py-2.5">
                                    <i data-lucide="shield-check" class="w-4 h-4 text-[#df747a]"></i>
                                    {{ __('Active Sessions & Security') }}
                                </x-dropdown-link>

                                @if(Auth::user()->isAdmin() || Auth::user()->isSuperAdmin() || Auth::user()->isTreasurer() || Auth::user()->isAuditor())
                                <x-dropdown-link :href="route('admin.dashboard')" class="text-amber-300 hover:text-amber-200 hover:bg-[#1E293B] flex items-center gap-2 text-xs py-2.5 font-semibold">
                                    <i data-lucide="shield" class="w-4 h-4 text-amber-400"></i>
                                    {{ __('Officer Dashboard') }}
                                </x-dropdown-link>
                                @endif

                                <x-dropdown-link :href="route('privacy')" class="text-slate-300 hover:text-white hover:bg-[#1E293B] flex items-center gap-2 text-xs py-2.5">
                                    <i data-lucide="file-text" class="w-4 h-4 text-slate-400"></i>
                                    {{ __('Privacy Notice') }}
                                </x-dropdown-link>

                                <div class="border-t border-[#334155] my-1"></div>

                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <x-dropdown-link :href="route('logout')"
                                            class="text-rose-400 hover:text-rose-300 hover:bg-[#1E293B] flex items-center gap-2 text-xs py-2.5"
                                            onclick="event.preventDefault(); this.closest('form').submit();">
                                        <i data-lucide="log-out" class="w-4 h-4 text-rose-400"></i>
                                        {{ __('Log Out') }}
                                    </x-dropdown-link>
                                </form>
                            </x-slot>
                        </x-dropdown>
                    </div>
                </header>

                @isset($header)
                    <div class="bg-[#172033] border-b border-[#334155] shadow-sm">
                        <div class="max-w-7xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </div>
                @endisset

                <main class="flex-1 pb-32 lg:pb-10">
                    {{ $slot }}
                </main>
            </div>
        </div>

        <!-- Mobile Floating 5-Icon Bottom Action Dock (Thumb-Zone Navigation) -->
        @auth
        <nav x-show="!readerFullscreen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 translate-y-4"
             class="lg:hidden fixed inset-x-3 sm:inset-x-6 bottom-[calc(0.75rem+env(safe-area-inset-bottom,0px))] z-30 mx-auto max-w-md floating-glass-dock px-2.5 sm:px-3.5 py-1.5"
             aria-label="Mobile Bottom Dock">
            <div class="grid grid-cols-5 items-center gap-0.5 sm:gap-1">
                <a href="{{ route('dashboard') }}"
                   aria-current="{{ request()->routeIs('dashboard') ? 'page' : 'false' }}"
                   class="flex flex-col items-center justify-center min-h-[44px] py-1 px-1 rounded-full text-[10px] font-semibold transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#df747a]/60 {{ request()->routeIs('dashboard') ? 'text-[#df747a] bg-[#560E0F]/25' : 'text-slate-400 hover:text-white' }}">
                    <i data-lucide="home" class="w-4 h-4 mb-0.5 shrink-0"></i>
                    <span class="truncate max-w-full leading-tight">Home</span>
                </a>

                <a href="{{ route('activities.index') }}"
                   aria-current="{{ request()->routeIs('activities.*') ? 'page' : 'false' }}"
                   class="flex flex-col items-center justify-center min-h-[44px] py-1 px-1 rounded-full text-[10px] font-semibold transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#df747a]/60 {{ request()->routeIs('activities.*') ? 'text-[#df747a] bg-[#560E0F]/25' : 'text-slate-400 hover:text-white' }}">
                    <i data-lucide="layers" class="w-4 h-4 mb-0.5 shrink-0"></i>
                    <span class="truncate max-w-full leading-tight">Activities</span>
                </a>

                <div class="flex flex-col items-center justify-center">
                    <a href="{{ route('member.dynamic_qr') }}"
                       aria-current="{{ request()->routeIs('member.dynamic_qr') ? 'page' : 'false' }}"
                       class="-mt-5 w-12 h-12 rounded-2xl bg-gradient-to-br from-[#560E0F] to-[#E11D48] text-white shadow-lg shadow-[#8F3033]/50 border border-[#fda4af]/40 flex flex-col items-center justify-center transition duration-200 transform active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#fda4af]"
                       aria-label="Open My QR Pass">
                        <i data-lucide="qr-code" class="w-5 h-5 shrink-0"></i>
                        <span class="text-[8px] font-extrabold uppercase tracking-tighter leading-none mt-0.5">MY QR</span>
                    </a>
                </div>

                <a href="{{ route('missions.index') }}"
                   aria-current="{{ request()->routeIs('missions.*') || request()->routeIs('shop.*') ? 'page' : 'false' }}"
                   class="flex flex-col items-center justify-center min-h-[44px] py-1 px-1 rounded-full text-[10px] font-semibold transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#df747a]/60 {{ request()->routeIs('missions.*') || request()->routeIs('shop.*') ? 'text-[#df747a] bg-[#560E0F]/25' : 'text-slate-400 hover:text-white' }}">
                    <i data-lucide="target" class="w-4 h-4 mb-0.5 shrink-0"></i>
                    <span class="truncate max-w-full leading-tight">Quests</span>
                </a>

                <a href="{{ route('directory.index') }}"
                   aria-current="{{ request()->routeIs('directory.*') || request()->routeIs('projects.*') ? 'page' : 'false' }}"
                   class="flex flex-col items-center justify-center min-h-[44px] py-1 px-1 rounded-full text-[10px] font-semibold transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#df747a]/60 {{ request()->routeIs('directory.*') || request()->routeIs('projects.*') ? 'text-[#df747a] bg-[#560E0F]/25' : 'text-slate-400 hover:text-white' }}">
                    <i data-lucide="users" class="w-4 h-4 mb-0.5 shrink-0"></i>
                    <span class="truncate max-w-full leading-tight">Community</span>
                </a>
            </div>
        </nav>
        @endauth

        <x-session-security-monitor />
        <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
        @stack('scripts')
    </body>
</html>

