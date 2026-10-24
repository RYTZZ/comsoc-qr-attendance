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
    <header class="sticky top-0 z-40 bg-[#0B1120]/85 backdrop-blur-xl border-b border-[#334155]/40 transition-all duration-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 sm:h-20">

                <!-- Logo & Brand (No boxed container, clean logo and typography) -->
                <a href="{{ route('public.home') }}" class="flex items-center gap-3.5 group focus:outline-none focus:ring-2 focus:ring-[#df747a] rounded-lg py-1 px-1.5 transition-colors">
                    <img src="{{ asset('images/COMSOC.png') }}" alt="COMSOC Logo" class="w-9 h-9 sm:w-11 sm:h-11 object-contain drop-shadow-sm group-hover:scale-105 transition-transform">
                    <div class="flex flex-col">
                        <span class="font-extrabold text-white text-base sm:text-lg font-brand-display tracking-tight leading-none group-hover:text-[#df747a] transition-colors">
                            {{ $settings['org_short_name'] ?? 'COMSOC' }}
                        </span>
                        <span class="text-[10px] sm:text-[11px] text-slate-400 font-medium tracking-wide mt-0.5">
                            SorSU Bulan Campus
                        </span>
                    </div>
                </a>

                <!-- Desktop Navigation Links -->
                <nav class="hidden md:flex items-center gap-1 lg:gap-2">
                    <a href="{{ route('public.about') }}"
                       class="px-3.5 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('public.about') ? 'text-white bg-[#172033]/80 border border-[#334155]/60' : 'text-slate-300 hover:text-white hover:bg-[#172033]/40' }}">
                        About
                    </a>
                    <a href="{{ route('public.officers') }}"
                       class="px-3.5 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('public.officers') ? 'text-white bg-[#172033]/80 border border-[#334155]/60' : 'text-slate-300 hover:text-white hover:bg-[#172033]/40' }}">
                        Officers
                    </a>
                    <a href="{{ route('public.activities') }}"
                       class="px-3.5 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('public.activities*') ? 'text-white bg-[#172033]/80 border border-[#334155]/60' : 'text-slate-300 hover:text-white hover:bg-[#172033]/40' }}">
                        Activities
                    </a>
                    <a href="{{ route('public.achievements') }}"
                       class="px-3.5 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('public.achievements') ? 'text-white bg-[#172033]/80 border border-[#334155]/60' : 'text-slate-300 hover:text-white hover:bg-[#172033]/40' }}">
                        Achievements
                    </a>
                    <a href="{{ route('public.gallery') }}"
                       class="px-3.5 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('public.gallery') ? 'text-white bg-[#172033]/80 border border-[#334155]/60' : 'text-slate-300 hover:text-white hover:bg-[#172033]/40' }}">
                        Gallery
                    </a>
                    <a href="{{ route('public.partners') }}"
                       class="px-3.5 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('public.partners') ? 'text-white bg-[#172033]/80 border border-[#334155]/60' : 'text-slate-300 hover:text-white hover:bg-[#172033]/40' }}">
                        Partners
                    </a>
                </nav>

                <!-- Single Hub CTA (Clean, authoritative entry point) -->
                <div class="hidden sm:flex items-center">
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn-primary text-xs sm:text-sm py-2 px-4 flex items-center gap-2 shadow-lg shadow-[#560E0F]/20">
                            <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                            <span>Access COMSOC Hub</span>
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="btn-primary text-xs sm:text-sm py-2 px-4.5 flex items-center gap-2 shadow-lg shadow-[#560E0F]/25 hover:shadow-[#560E0F]/40 transition-all">
                            <span>Access COMSOC Hub</span>
                            <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </a>
                    @endauth
                </div>

                <!-- Mobile Menu Toggle Button -->
                <div class="flex items-center gap-2 md:hidden">
                    <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="btn-primary text-xs py-1.5 px-3 flex items-center gap-1">
                        <span>Hub</span>
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                    </a>

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
             class="md:hidden bg-[#0B1120]/95 backdrop-blur-xl border-b border-[#334155]/60 px-4 pt-3 pb-6 space-y-1 shadow-2xl"
             style="display: none;">
            <a href="{{ route('public.home') }}" class="block px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('public.home') ? 'text-white bg-[#172033]' : 'text-slate-300 hover:text-white hover:bg-[#172033]/60' }}">
                Home
            </a>
            <a href="{{ route('public.about') }}" class="block px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('public.about') ? 'text-white bg-[#172033]' : 'text-slate-300 hover:text-white hover:bg-[#172033]/60' }}">
                About COMSOC
            </a>
            <a href="{{ route('public.officers') }}" class="block px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('public.officers') ? 'text-white bg-[#172033]' : 'text-slate-300 hover:text-white hover:bg-[#172033]/60' }}">
                Leadership & Officers
            </a>
            <a href="{{ route('public.activities') }}" class="block px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('public.activities*') ? 'text-white bg-[#172033]' : 'text-slate-300 hover:text-white hover:bg-[#172033]/60' }}">
                Activities & Events
            </a>
            <a href="{{ route('public.achievements') }}" class="block px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('public.achievements') ? 'text-white bg-[#172033]' : 'text-slate-300 hover:text-white hover:bg-[#172033]/60' }}">
                Achievements
            </a>
            <a href="{{ route('public.gallery') }}" class="block px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('public.gallery') ? 'text-white bg-[#172033]' : 'text-slate-300 hover:text-white hover:bg-[#172033]/60' }}">
                Approved Gallery
            </a>
            <a href="{{ route('public.partners') }}" class="block px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('public.partners') ? 'text-white bg-[#172033]' : 'text-slate-300 hover:text-white hover:bg-[#172033]/60' }}">
                Partners & Collaborators
            </a>

            <div class="pt-4 border-t border-[#334155]/60">
                <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="btn-primary w-full text-center py-2.5 text-sm flex items-center justify-center gap-2">
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    <span>Access COMSOC Hub</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content Slot -->
    <main id="main-content" class="flex-1 relative z-10 focus:outline-none" tabindex="-1">
        {{ $slot }}
    </main>

    <!-- Public Website Shared Footer Component -->
    <x-public-website-footer :settings="$settings" />

</body>
</html>
