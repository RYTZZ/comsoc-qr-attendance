@props([
    'settings' => [],
])

@php
    $settings = !empty($settings) ? $settings : \App\Services\WebsiteSettingsService::all();
@endphp

<!-- Public Website Shared Footer -->
<footer class="relative z-10 bg-[#0B1120] border-t border-[#334155]/60 pt-16 pb-12 text-slate-400 text-sm mt-auto shrink-0">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-8 lg:gap-10 pb-12 border-b border-[#334155]/40">

            <!-- Col 1: COMSOC Identity (lg:col-span-4) -->
            <div class="min-w-0 sm:col-span-2 lg:col-span-4 space-y-4">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/COMSOC.png') }}" alt="COMSOC Logo" class="w-10 h-10 object-contain drop-shadow-sm">
                    <div>
                        <span class="font-extrabold text-white text-lg font-brand-display block tracking-tight">COMSOC</span>
                        <span class="text-[11px] text-[#df747a] font-semibold tracking-wider uppercase block">SorSU Bulan Campus</span>
                    </div>
                </div>
                <p class="text-sm text-slate-300 font-medium leading-relaxed max-w-sm">
                    {{ $settings['org_tagline'] ?? 'Innovating Today for a Better Community.' }}
                </p>
                <p class="text-xs text-slate-400 leading-relaxed max-w-sm">
                    The official student organization representing computing and technology scholars at Sorsogon State University – Bulan Campus.
                </p>
            </div>

            <!-- Col 2: Organization (lg:col-span-2) -->
            <div class="min-w-0 lg:col-span-2 space-y-3">
                <h3 class="text-xs font-bold text-white uppercase tracking-wider font-brand-accent">Organization</h3>
                <ul class="space-y-2 text-xs text-slate-300">
                    <li><a href="{{ route('public.about') }}" class="hover:text-white transition-colors">About & Mission</a></li>
                    <li><a href="{{ route('public.officers') }}" class="hover:text-white transition-colors">Leadership</a></li>
                    <li><a href="{{ route('public.activities') }}" class="hover:text-white transition-colors">Activities & Events</a></li>
                    <li><a href="{{ route('public.achievements') }}" class="hover:text-white transition-colors">Achievements</a></li>
                    <li><a href="{{ route('public.gallery') }}" class="hover:text-white transition-colors">Gallery</a></li>
                    <li><a href="{{ route('public.partners') }}" class="hover:text-white transition-colors">Partners</a></li>
                </ul>
            </div>

            <!-- Col 3: COMSOC Hub (lg:col-span-2) -->
            <div class="min-w-0 lg:col-span-2 space-y-3">
                <h3 class="text-xs font-bold text-white uppercase tracking-wider font-brand-accent">COMSOC Hub</h3>
                <ul class="space-y-2 text-xs text-slate-300">
                    <li><a href="{{ route('login') }}" class="hover:text-white transition-colors">Member Sign In</a></li>
                    <li><a href="{{ route('signup') }}" class="hover:text-white transition-colors">Student Registration</a></li>
                    <li><a href="{{ route('account.assistance') }}" class="hover:text-white transition-colors">Account Assistance</a></li>
                    <li><a href="{{ route('privacy') }}" class="hover:text-white transition-colors">Data Privacy Notice</a></li>
                </ul>
            </div>

            <!-- Col 4: Contact & Channels (lg:col-span-4) -->
            <div class="min-w-0 sm:col-span-2 lg:col-span-4 space-y-3">
                <h3 class="text-xs font-bold text-white uppercase tracking-wider font-brand-accent">Contact & Channels</h3>
                <div class="space-y-3 text-xs text-slate-300">
                    @if(!empty($settings['contact_email']))
                        <div class="flex items-start gap-2.5">
                            <i data-lucide="mail" class="w-4 h-4 text-[#df747a] shrink-0 mt-0.5" aria-hidden="true"></i>
                            <div class="min-w-0 flex-1">
                                <span class="sr-only">Email address:</span>
                                <a href="mailto:{{ $settings['contact_email'] }}" class="hover:text-white transition-colors break-words">
                                    {{ $settings['contact_email'] }}
                                </a>
                            </div>
                        </div>
                    @endif
                    @if(!empty($settings['contact_address']))
                        <div class="flex items-start gap-2.5">
                            <i data-lucide="map-pin" class="w-4 h-4 text-[#df747a] shrink-0 mt-0.5" aria-hidden="true"></i>
                            <div class="min-w-0 flex-1 leading-relaxed">
                                <span class="sr-only">Physical address:</span>
                                <span>{{ $settings['contact_address'] }}</span>
                            </div>
                        </div>
                    @endif
                    @if(!empty($settings['contact_facebook']))
                        <div class="flex items-start gap-2.5 pt-0.5">
                            <i data-lucide="facebook" class="w-4 h-4 text-blue-400 shrink-0 mt-0.5" aria-hidden="true"></i>
                            <div class="min-w-0 flex-1">
                                <span class="sr-only">Facebook:</span>
                                <a href="{{ $settings['contact_facebook'] }}" target="_blank" rel="noopener noreferrer" class="hover:text-white transition-colors inline-block">
                                    Official Facebook Page
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

        </div>

        <!-- Bottom Footer Bar -->
        <div class="pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-400">
            <p class="text-center sm:text-left">© {{ date('Y') }} Computing Society (COMSOC) — SorSU Bulan Campus. All rights reserved.</p>
            <div class="flex items-center gap-4 shrink-0">
                <a href="{{ route('privacy') }}" class="hover:text-white transition-colors">Privacy Notice</a>
                <span class="text-slate-600" aria-hidden="true">•</span>
                <a href="{{ route('privacy') }}" class="hover:text-white transition-colors">Terms of Use</a>
            </div>
        </div>
    </div>
</footer>
