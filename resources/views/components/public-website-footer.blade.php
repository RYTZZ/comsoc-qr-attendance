@props([
    'settings' => [],
])

@php
    $settings = !empty($settings) ? $settings : \App\Services\WebsiteSettingsService::all();
    $contactEmail = $settings['contact_email'] ?? 'computingsocietysorsubc@gmail.com';
    $contactAddress = $settings['contact_address'] ?? 'Zone 8, Bulan, Sorsogon, Philippines 4706';
    $contactFacebook = $settings['contact_facebook'] ?? 'https://www.facebook.com/comsocsorsubc';
@endphp

<!-- Public Website Shared Footer -->
<footer class="relative z-10 bg-[#0B1120] border-t border-[#334155]/60 pt-12 pb-8 text-slate-400 text-sm mt-auto shrink-0" role="contentinfo" aria-label="Global Site Footer">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Main 4-Column Responsive Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-10 pb-10 border-b border-[#334155]/40 items-start">

            <!-- Col 1: COMSOC Branding -->
            <div class="min-w-0 space-y-3.5">
                <a href="{{ route('public.home') }}" class="inline-flex items-center gap-3 group focus:outline-none focus-visible:ring-2 focus-visible:ring-[#df747a] rounded-lg">
                    <img src="{{ asset('images/COMSOC.png') }}" alt="COMSOC Emblem" class="w-12 h-12 object-contain drop-shadow-sm group-hover:scale-105 transition-transform duration-200">
                    <div>
                        <span class="font-extrabold text-white text-lg font-brand-display block tracking-tight group-hover:text-[#df747a] transition-colors">COMSOC</span>
                        <span class="text-[11px] text-[#df747a] font-semibold tracking-wider uppercase block">SorSU Bulan Campus</span>
                    </div>
                </a>
                <p class="text-xs font-semibold text-white tracking-wide leading-relaxed">
                    {{ $settings['org_tagline'] ?? 'Innovating Today for a Better Community.' }}
                </p>
                <p class="text-xs text-slate-400 leading-relaxed font-normal">
                    The official student organization representing computing and technology scholars at Sorsogon State University – Bulan Campus.
                </p>
            </div>

            <!-- Col 2: Organization Navigation -->
            <nav class="min-w-0 space-y-3" aria-label="Organization Navigation">
                <h3 class="text-xs font-bold text-white uppercase tracking-wider font-brand-accent">Organization</h3>
                <ul class="space-y-2 text-xs text-slate-300">
                    <li>
                        <a href="{{ route('public.about') }}" class="hover:text-white transition-colors duration-150 inline-block focus:outline-none focus-visible:underline focus-visible:text-white whitespace-nowrap">
                            About & Mission
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('public.officers') }}" class="hover:text-white transition-colors duration-150 inline-block focus:outline-none focus-visible:underline focus-visible:text-white whitespace-nowrap">
                            Leadership
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('public.activities') }}" class="hover:text-white transition-colors duration-150 inline-block focus:outline-none focus-visible:underline focus-visible:text-white whitespace-nowrap">
                            Activities & Events
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('public.achievements') }}" class="hover:text-white transition-colors duration-150 inline-block focus:outline-none focus-visible:underline focus-visible:text-white whitespace-nowrap">
                            Achievements
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('public.gallery') }}" class="hover:text-white transition-colors duration-150 inline-block focus:outline-none focus-visible:underline focus-visible:text-white whitespace-nowrap">
                            Gallery
                        </a>
                    </li>
                </ul>
            </nav>

            <!-- Col 3: COMSOC Hub -->
            <nav class="min-w-0 space-y-3" aria-label="COMSOC Hub Navigation">
                <h3 class="text-xs font-bold text-white uppercase tracking-wider font-brand-accent">COMSOC Hub</h3>
                <ul class="space-y-2 text-xs text-slate-300">
                    <li>
                        <a href="{{ route('login') }}" class="hover:text-white transition-colors duration-150 inline-block focus:outline-none focus-visible:underline focus-visible:text-white whitespace-nowrap">
                            Member Sign In
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('signup') }}" class="hover:text-white transition-colors duration-150 inline-block focus:outline-none focus-visible:underline focus-visible:text-white whitespace-nowrap">
                            Student Registration
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('account.assistance') }}" class="hover:text-white transition-colors duration-150 inline-block focus:outline-none focus-visible:underline focus-visible:text-white whitespace-nowrap">
                            Account Assistance
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('privacy') }}" class="hover:text-white transition-colors duration-150 inline-block focus:outline-none focus-visible:underline focus-visible:text-white whitespace-nowrap">
                            Data Privacy Notice
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('public.partners') }}" class="text-[#df747a] hover:text-white transition-colors duration-150 inline-flex items-center gap-1.5 focus:outline-none focus-visible:underline whitespace-nowrap">
                            <i data-lucide="handshake" class="w-3.5 h-3.5 shrink-0" aria-hidden="true"></i>
                            <span>Partners & Collaborations</span>
                        </a>
                    </li>
                </ul>
            </nav>

            <!-- Col 4: Contact & Channels -->
            <div class="min-w-0 space-y-3">
                <h3 class="text-xs font-bold text-white uppercase tracking-wider font-brand-accent">Contact & Channels</h3>
                <ul class="space-y-2.5 text-xs text-slate-300">
                    @if(!empty($contactEmail))
                    <li class="flex items-start gap-2.5">
                        <i data-lucide="mail" class="w-4 h-4 text-[#df747a] shrink-0 mt-0.5" aria-hidden="true"></i>
                        <div class="min-w-0 flex-1">
                            <span class="sr-only">Official Email:</span>
                            <a href="mailto:{{ $contactEmail }}" class="hover:text-white transition-colors duration-150 break-all focus:outline-none focus-visible:underline focus-visible:text-white">
                                {{ $contactEmail }}
                            </a>
                        </div>
                    </li>
                    @endif

                    @if(!empty($contactAddress))
                    <li class="flex items-start gap-2.5">
                        <i data-lucide="map-pin" class="w-4 h-4 text-[#df747a] shrink-0 mt-0.5" aria-hidden="true"></i>
                        <div class="min-w-0 flex-1 leading-relaxed text-slate-400">
                            <span class="sr-only">Campus Address:</span>
                            <span>{{ $contactAddress }}</span>
                        </div>
                    </li>
                    @endif

                    @if(!empty($contactFacebook))
                    <li class="flex items-start gap-2.5 pt-0.5">
                        <i data-lucide="facebook" class="w-4 h-4 text-sky-400 shrink-0 mt-0.5" aria-hidden="true"></i>
                        <div class="min-w-0 flex-1">
                            <span class="sr-only">Facebook:</span>
                            <a href="{{ $contactFacebook }}" target="_blank" rel="noopener noreferrer" class="hover:text-white transition-colors duration-150 inline-flex items-center gap-1 focus:outline-none focus-visible:underline focus-visible:text-white">
                                <span>Official Facebook Page</span>
                                <i data-lucide="external-link" class="w-3 h-3 text-slate-500" aria-hidden="true"></i>
                            </a>
                        </div>
                    </li>
                    @endif
                </ul>
            </div>

        </div>

        <!-- Bottom Copyright & Legal Bar -->
        <div class="pt-6 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-400">
            <p class="text-center sm:text-left leading-relaxed">
                © {{ date('Y') }} Computing Society (COMSOC) — SorSU Bulan Campus. All rights reserved.
            </p>
            <nav class="flex items-center gap-4 shrink-0" aria-label="Legal & Policies">
                <a href="{{ route('privacy') }}" class="hover:text-white transition-colors duration-150 focus:outline-none focus-visible:underline focus-visible:text-white">
                    Privacy Notice
                </a>
                <span class="text-slate-600 select-none" aria-hidden="true">•</span>
                <a href="{{ route('public.terms') }}" class="hover:text-white transition-colors duration-150 focus:outline-none focus-visible:underline focus-visible:text-white">
                    Terms of Use
                </a>
            </nav>
        </div>

    </div>
</footer>

