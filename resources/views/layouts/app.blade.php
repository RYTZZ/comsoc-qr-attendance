<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'ComSoc QR Attendance') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@600;700;800&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-full bg-[#0B1120] text-[#F8FAFC] font-sans antialiased"
          x-data="{
              drawerOpen: false,
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
          x-effect="document.body.classList.toggle('overflow-hidden', drawerOpen)"
          @keydown.escape.window="closeDrawer()">
        <div class="min-h-screen bg-[#0B1120] flex flex-col">
            @include('layouts.navigation')

            <div class="flex-1 flex flex-col min-w-0 w-full transition-all duration-300 ease-in-out">

                <header class="sticky top-0 z-20 bg-[#172033]/95 backdrop-blur-md border-b border-[#334155] px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between shadow-md">
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
                                <div class="w-8 h-8 rounded-xl bg-[#0B1120] border border-[#334155] p-1 flex items-center justify-center shrink-0 shadow-sm group-hover:border-[#8F3033] transition">
                                    <img src="{{ asset('images/COMSOC.png') }}" alt="Computing Society Logo" class="w-full h-full object-contain">
                                </div>
                                <div class="flex flex-col">
                                    <span class="font-extrabold text-[#F8FAFC] text-sm font-brand-display tracking-tight leading-tight">COMSOC HUB</span>
                                    <span class="text-[9.5px] text-[#df747a] font-semibold tracking-wider uppercase leading-none hidden sm:inline">QR Attendance & EXP</span>
                                </div>
                            </a>
                        </div>

                        <div class="hidden xl:flex items-center gap-2 text-xs text-slate-400 ms-4 ps-4 border-l border-[#334155]">
                            <span class="inline-block w-2 h-2 rounded-full bg-emerald-400"></span>
                            <span class="font-medium text-[#94A3B8]">Computing Society of Sorsogon State University – Bulan Campus</span>
                        </div>
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
                                    {{ __('Profile & Settings') }}
                                </x-dropdown-link>

                                <x-dropdown-link :href="route('profile.sessions')" class="text-slate-300 hover:text-white hover:bg-[#1E293B] flex items-center gap-2 text-xs py-2.5">
                                    <i data-lucide="shield-check" class="w-4 h-4 text-[#df747a]"></i>
                                    {{ __('Active Sessions & Security') }}
                                </x-dropdown-link>

                                @if(Auth::user()->isAdmin() || Auth::user()->isSuperAdmin() || Auth::user()->isStaff() || Auth::user()->isTreasurer())
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

                <main class="flex-1 pb-28 lg:pb-10">
                    {{ $slot }}
                </main>
            </div>
        </div>


        <x-session-security-monitor />
        <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
        @stack('scripts')
    </body>
</html>

