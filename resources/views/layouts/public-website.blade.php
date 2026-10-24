<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $metaTitle ?? ($title ? $title . ' — ' : '') . ($settings['meta_title'] ?? 'Computing Society (COMSOC)') }}</title>
    <meta name="description" content="{{ $metaDescription ?? $settings['meta_description'] ?? 'Computing Society of Sorsogon State University – Bulan Campus' }}">

    <!-- Open Graph Metadata -->
    <meta property="og:title" content="{{ $metaTitle ?? ($title ? $title . ' — ' : '') . ($settings['meta_title'] ?? 'Computing Society (COMSOC)') }}">
    <meta property="og:description" content="{{ $metaDescription ?? $settings['meta_description'] ?? 'Official website of the Computing Society Organization (COMSOC) of Sorsogon State University – Bulan Campus.' }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ !empty($settings['hero_image']) ? asset('storage/' . $settings['hero_image']) : asset('images/COMSOC.png') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@600;700;800&family=Space+Grotesk:wght@600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-[#0B1120] text-[#F8FAFC] font-sans antialiased selection:bg-[#560E0F] selection:text-white flex flex-col min-h-screen relative overflow-x-hidden" x-data="{ mobileMenuOpen: false }">

    <!-- Accessibility Skip to Main Content Link -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:px-4 focus:py-2 focus:bg-[#560E0F] focus:text-white focus:rounded-xl focus:shadow-xl focus:outline-none focus:ring-2 focus:ring-[#df747a] text-xs font-semibold">
        Skip to main content
    </a>

    <!-- Ambient Grid & Glow Background -->
    <x-public-auth-bg />

    <!-- Top Navigation Bar -->
    <header class="sticky top-0 z-40 bg-[#0B1120]/90 backdrop-blur-md border-b border-[#334155]/80 transition-all duration-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 sm:h-20">

                <!-- Logo & Brand -->
                <a href="{{ route('public.home') }}" class="flex items-center gap-3 group focus:outline-none focus:ring-2 focus:ring-[#df747a] rounded-xl p-1">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-[#172033] border border-[#334155] p-1.5 flex items-center justify-center shrink-0 shadow-lg group-hover:border-[#df747a] transition-colors">
                        <img src="{{ asset('images/COMSOC.png') }}" alt="Computing Society Logo" class="w-full h-full object-contain">
                    </div>
                    <div class="flex flex-col">
                        <span class="font-extrabold text-white text-base sm:text-lg font-brand-display tracking-tight leading-tight group-hover:text-[#df747a] transition-colors">
                            {{ $settings['org_short_name'] ?? 'COMSOC' }}
                        </span>
                        <span class="text-[10px] sm:text-[11px] text-[#94A3B8] font-medium tracking-wide">
                            SorSU – Bulan Campus
                        </span>
                    </div>
                </a>

                <!-- Desktop Navigation Links -->
                <nav class="hidden md:flex items-center gap-1 lg:gap-2">
                    <a href="{{ route('public.about') }}"
                       class="px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('public.about') ? 'text-white bg-[#172033] border border-[#334155]' : 'text-slate-300 hover:text-white hover:bg-[#172033]/60' }}">
                        About
                    </a>
                    <a href="{{ route('public.officers') }}"
                       class="px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('public.officers') ? 'text-white bg-[#172033] border border-[#334155]' : 'text-slate-300 hover:text-white hover:bg-[#172033]/60' }}">
                        Officers
                    </a>
                    <a href="{{ route('public.activities') }}"
                       class="px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('public.activities*') ? 'text-white bg-[#172033] border border-[#334155]' : 'text-slate-300 hover:text-white hover:bg-[#172033]/60' }}">
                        Activities
                    </a>
                    <a href="{{ route('public.achievements') }}"
                       class="px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('public.achievements') ? 'text-white bg-[#172033] border border-[#334155]' : 'text-slate-300 hover:text-white hover:bg-[#172033]/60' }}">
                        Achievements
                    </a>
                    <a href="{{ route('public.gallery') }}"
                       class="px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('public.gallery') ? 'text-white bg-[#172033] border border-[#334155]' : 'text-slate-300 hover:text-white hover:bg-[#172033]/60' }}">
                        Gallery
                    </a>
                    <a href="{{ route('public.partners') }}"
                       class="px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('public.partners') ? 'text-white bg-[#172033] border border-[#334155]' : 'text-slate-300 hover:text-white hover:bg-[#172033]/60' }}">
                        Partners
                    </a>
                </nav>

                <!-- Auth & Portal Action CTAs -->
                <div class="hidden sm:flex items-center gap-2.5">
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn-primary text-xs sm:text-sm py-2 px-4 flex items-center gap-2 shadow-lg shadow-[#560E0F]/20">
                            <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                            <span>Access COMSOC Hub</span>
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="btn-secondary text-xs sm:text-sm py-2 px-3.5 flex items-center gap-1.5 text-slate-300 hover:text-white">
                            <i data-lucide="log-in" class="w-4 h-4"></i>
                            <span>Log In</span>
                        </a>
                        <a href="{{ route('login') }}" class="btn-primary text-xs sm:text-sm py-2 px-4 flex items-center gap-2 shadow-lg shadow-[#560E0F]/20">
                            <i data-lucide="arrow-right" class="w-4 h-4"></i>
                            <span>Access COMSOC Hub</span>
                        </a>
                    @endauth
                </div>

                <!-- Mobile Menu Button -->
                <div class="flex items-center gap-2 md:hidden">
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn-primary text-xs py-1.5 px-2.5 flex items-center gap-1">
                            <i data-lucide="layout-dashboard" class="w-3.5 h-3.5"></i>
                            <span>Hub</span>
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="btn-primary text-xs py-1.5 px-2.5 flex items-center gap-1">
                            <i data-lucide="log-in" class="w-3.5 h-3.5"></i>
                            <span>Log In</span>
                        </a>
                    @endauth

                    <button @click="mobileMenuOpen = !mobileMenuOpen; $nextTick(() => { if (window.createIcons && window.lucideIcons) window.createIcons({ icons: window.lucideIcons }); })"
                            type="button"
                            class="p-2 rounded-xl text-slate-300 hover:text-white hover:bg-[#172033] focus:outline-none focus:ring-2 focus:ring-[#df747a]"
                            aria-label="Toggle Navigation Menu"
                            :aria-expanded="mobileMenuOpen.toString()"
                            aria-controls="mobile-nav-menu">
                        <i x-show="!mobileMenuOpen" data-lucide="menu" class="w-6 h-6"></i>
                        <i x-show="mobileMenuOpen" data-lucide="x" class="w-6 h-6" style="display: none;"></i>
                    </button>
                </div>

            </div>
        </div>

        <!-- Mobile Dropdown Navigation Menu -->
        <div id="mobile-nav-menu"
             x-show="mobileMenuOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="md:hidden bg-[#0B1120]/95 border-b border-[#334155] px-4 pt-2 pb-6 space-y-1.5 shadow-2xl"
             style="display: none;">
            <a href="{{ route('public.home') }}" class="block px-3 py-2.5 rounded-lg text-sm font-medium text-slate-200 hover:bg-[#172033]">
                Home
            </a>
            <a href="{{ route('public.about') }}" class="block px-3 py-2.5 rounded-lg text-sm font-medium text-slate-200 hover:bg-[#172033]">
                About COMSOC
            </a>
            <a href="{{ route('public.officers') }}" class="block px-3 py-2.5 rounded-lg text-sm font-medium text-slate-200 hover:bg-[#172033]">
                Leadership & Officers
            </a>
            <a href="{{ route('public.activities') }}" class="block px-3 py-2.5 rounded-lg text-sm font-medium text-slate-200 hover:bg-[#172033]">
                Activities & Events
            </a>
            <a href="{{ route('public.achievements') }}" class="block px-3 py-2.5 rounded-lg text-sm font-medium text-slate-200 hover:bg-[#172033]">
                Achievements
            </a>
            <a href="{{ route('public.gallery') }}" class="block px-3 py-2.5 rounded-lg text-sm font-medium text-slate-200 hover:bg-[#172033]">
                Approved Gallery
            </a>
            <a href="{{ route('public.partners') }}" class="block px-3 py-2.5 rounded-lg text-sm font-medium text-slate-200 hover:bg-[#172033]">
                Partners & Collaborators
            </a>

            <div class="pt-4 border-t border-[#334155] flex flex-col gap-2">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn-primary w-full text-center py-2.5 text-sm flex items-center justify-center gap-2">
                        <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                        <span>Return to Student Dashboard</span>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn-secondary w-full text-center py-2.5 text-sm flex items-center justify-center gap-2">
                        <i data-lucide="log-in" class="w-4 h-4"></i>
                        <span>Member Log In</span>
                    </a>
                    <a href="{{ route('signup') }}" class="btn-primary w-full text-center py-2.5 text-sm flex items-center justify-center gap-2">
                        <i data-lucide="user-plus" class="w-4 h-4"></i>
                        <span>Register Account</span>
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <!-- Main Content Slot -->
    <main id="main-content" class="flex-1 relative z-10 focus:outline-none" tabindex="-1">
        {{ $slot }}
    </main>

    <!-- Public Website Footer -->
    <footer class="relative z-10 bg-[#0B1120] border-t border-[#334155]/80 pt-12 pb-8 text-slate-400 text-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 pb-10 border-b border-[#334155]/60">

                <!-- Col 1: Identity -->
                <div class="md:col-span-1 space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-[#172033] border border-[#334155] p-1.5 flex items-center justify-center shrink-0">
                            <img src="{{ asset('images/COMSOC.png') }}" alt="Computing Society Logo" class="w-full h-full object-contain">
                        </div>
                        <div>
                            <span class="font-extrabold text-white text-base font-brand-display block">COMSOC</span>
                            <span class="text-[10px] text-[#df747a] font-semibold tracking-wider uppercase block">SorSU Bulan Campus</span>
                        </div>
                    </div>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        {{ $settings['org_tagline'] ?? 'Innovating Today for a Better Community.' }}
                    </p>
                    <p class="text-[11px] text-slate-500">
                        {{ $settings['org_institution'] ?? 'Sorsogon State University – Bulan Campus' }}
                    </p>
                </div>

                <!-- Col 2: Navigation -->
                <div>
                    <h3 class="text-xs font-bold text-white uppercase tracking-wider mb-3">Explore</h3>
                    <ul class="space-y-2 text-xs">
                        <li><a href="{{ route('public.about') }}" class="hover:text-white transition">About & Mission</a></li>
                        <li><a href="{{ route('public.officers') }}" class="hover:text-white transition">Leadership</a></li>
                        <li><a href="{{ route('public.activities') }}" class="hover:text-white transition">Activities & Events</a></li>
                        <li><a href="{{ route('public.achievements') }}" class="hover:text-white transition">Verified Achievements</a></li>
                        <li><a href="{{ route('public.gallery') }}" class="hover:text-white transition">Approved Gallery</a></li>
                        <li><a href="{{ route('public.partners') }}" class="hover:text-white transition">Partners</a></li>
                    </ul>
                </div>

                <!-- Col 3: Student Platform -->
                <div>
                    <h3 class="text-xs font-bold text-white uppercase tracking-wider mb-3">COMSOC Hub</h3>
                    <ul class="space-y-2 text-xs">
                        <li><a href="{{ route('login') }}" class="hover:text-white transition">Student Log In</a></li>
                        <li><a href="{{ route('signup') }}" class="hover:text-white transition">Student Sign Up</a></li>
                        <li><a href="{{ route('account.assistance') }}" class="hover:text-white transition">Account Assistance</a></li>
                        <li><a href="{{ route('privacy') }}" class="hover:text-white transition">Data Privacy Notice</a></li>
                    </ul>
                </div>

                <!-- Col 4: Verified Contact -->
                <div>
                    <h3 class="text-xs font-bold text-white uppercase tracking-wider mb-3">Contact & Channels</h3>
                    <div class="space-y-2 text-xs text-slate-400">
                        @if(!empty($settings['contact_email']))
                            <p class="flex items-center gap-2">
                                <i data-lucide="mail" class="w-3.5 h-3.5 text-[#df747a]"></i>
                                <a href="mailto:{{ $settings['contact_email'] }}" class="hover:text-white underline">{{ $settings['contact_email'] }}</a>
                            </p>
                        @endif
                        @if(!empty($settings['contact_address']))
                            <p class="flex items-start gap-2">
                                <i data-lucide="map-pin" class="w-3.5 h-3.5 text-[#df747a] shrink-0 mt-0.5"></i>
                                <span>{{ $settings['contact_address'] }}</span>
                            </p>
                        @endif
                        @if(!empty($settings['contact_facebook']))
                            <p class="flex items-center gap-2 pt-1">
                                <i data-lucide="facebook" class="w-3.5 h-3.5 text-blue-400"></i>
                                <a href="{{ $settings['contact_facebook'] }}" target="_blank" rel="noopener noreferrer" class="hover:text-white underline">Official Facebook Page</a>
                            </p>
                        @endif
                    </div>
                </div>

            </div>

            <div class="pt-6 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
                <p>© {{ date('Y') }} Computing Society (COMSOC) — SorSU Bulan Campus. All rights reserved.</p>
                <div class="flex items-center gap-4">
                    <a href="{{ route('privacy') }}" class="hover:text-slate-300 underline">Privacy Policy</a>
                    <span>•</span>
                    <a href="{{ route('privacy') }}" class="hover:text-slate-300 underline">Terms of Use</a>
                    <span>•</span>
                    <span class="font-mono text-slate-400">COMSOC Hub v2.5</span>
                </div>
            </div>
        </div>
    </footer>

</body>
</html>
