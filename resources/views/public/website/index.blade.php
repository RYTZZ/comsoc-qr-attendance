<x-public-website-layout :settings="$settings" :title="'Home'">

    <!-- Hero Section -->
    @if($settings['section_hero_visible'] ?? true)
    <section class="relative overflow-hidden pt-12 pb-20 sm:pt-20 sm:pb-32 border-b border-[#334155]/60">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">

                <!-- Left Hero Copy -->
                <div class="lg:col-span-7 space-y-6 text-center lg:text-left">
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-[#560E0F]/40 border border-[#560E0F] text-[#df747a] text-xs font-semibold tracking-wide shadow-sm">
                        <span class="w-2 h-2 rounded-full bg-[#df747a] animate-pulse"></span>
                        <span>Computing Society • Sorsogon State University – Bulan Campus</span>
                    </div>

                    <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black font-brand-display text-white tracking-tight leading-tight">
                        {{ $settings['hero_headline'] ?? 'Building Skills. Creating Opportunities. Connecting Communities.' }}
                    </h1>

                    <p class="text-base sm:text-lg text-slate-300 max-w-2xl mx-auto lg:mx-0 leading-relaxed font-normal">
                        {{ $settings['hero_description'] ?? 'COMSOC brings computing students together to develop their skills, turn ideas into meaningful projects, build connections, and create experiences that strengthen our campus community.' }}
                    </p>

                    <div class="flex flex-wrap items-center justify-center lg:justify-start gap-4 pt-4">
                        <a href="{{ $settings['hero_cta_primary_url'] ?? route('public.about') }}"
                           class="btn-secondary text-sm sm:text-base py-3 px-6 rounded-xl flex items-center gap-2 border-[#334155] hover:border-[#df747a] transition-all">
                            <i data-lucide="compass" class="w-4 h-4 text-[#df747a]"></i>
                            <span>{{ $settings['hero_cta_primary_label'] ?? 'Discover COMSOC' }}</span>
                        </a>

                        <a href="{{ $settings['hero_cta_secondary_url'] ?? route('login') }}"
                           class="btn-primary text-sm sm:text-base py-3 px-6 rounded-xl flex items-center gap-2 shadow-xl shadow-[#560E0F]/30 hover:scale-105 transition-transform">
                            <i data-lucide="terminal" class="w-4 h-4"></i>
                            <span>{{ $settings['hero_cta_secondary_label'] ?? 'Access COMSOC Hub' }}</span>
                        </a>
                    </div>

                    <!-- Highlight Metrics & Quick Facts -->
                    <div class="pt-8 grid grid-cols-3 gap-4 border-t border-[#334155]/60 max-w-lg mx-auto lg:mx-0">
                        <div>
                            <span class="block text-2xl font-black font-brand-display text-white">5</span>
                            <span class="text-xs text-slate-400">Pillars of Excellence</span>
                        </div>
                        <div>
                            <span class="block text-2xl font-black font-brand-display text-white">4+</span>
                            <span class="text-xs text-slate-400">Computing Programs</span>
                        </div>
                        <div>
                            <span class="block text-2xl font-black font-brand-display text-white">100%</span>
                            <span class="text-xs text-slate-400">Student Empowered</span>
                        </div>
                    </div>
                </div>

                <!-- Right Hero Image Showcase -->
                <div class="lg:col-span-5 relative">
                    <div class="relative mx-auto max-w-md lg:max-w-none">
                        <div class="absolute -inset-1 rounded-3xl bg-gradient-to-r from-[#560E0F] to-[#1E293B] opacity-50 blur-xl"></div>
                        <div class="relative rounded-2xl bg-[#172033] border border-[#334155] p-3 shadow-2xl overflow-hidden">
                            @if(!empty($settings['hero_image']))
                                <img src="{{ asset('storage/' . $settings['hero_image']) }}" alt="COMSOC Leadership and Community" class="w-full h-80 sm:h-96 object-cover rounded-xl">
                            @else
                                <div class="w-full h-80 sm:h-96 bg-gradient-to-br from-[#0B1120] via-[#172033] to-[#560E0F]/40 rounded-xl flex flex-col items-center justify-center p-6 text-center border border-[#334155]/60">
                                    <div class="w-20 h-20 rounded-2xl bg-[#0B1120] border border-[#560E0F] p-3 flex items-center justify-center mb-4 shadow-xl">
                                        <img src="{{ asset('images/COMSOC.png') }}" alt="COMSOC Logo" class="w-full h-full object-contain">
                                    </div>
                                    <h2 class="font-bold font-brand-display text-white text-lg sm:text-xl">Computing Society Organization</h2>
                                    <p class="text-xs text-slate-400 mt-2 max-w-xs">Sorsogon State University – Bulan Campus • Innovating Today for a Better Community.</p>
                                    <div class="mt-4 flex items-center gap-2">
                                        <span class="px-2.5 py-1 rounded bg-[#0B1120] border border-[#334155] text-[11px] font-mono text-slate-300">BSIT</span>
                                        <span class="px-2.5 py-1 rounded bg-[#0B1120] border border-[#334155] text-[11px] font-mono text-slate-300">BSIS</span>
                                        <span class="px-2.5 py-1 rounded bg-[#0B1120] border border-[#334155] text-[11px] font-mono text-slate-300">BSCS</span>
                                        <span class="px-2.5 py-1 rounded bg-[#0B1120] border border-[#334155] text-[11px] font-mono text-slate-300">BTVTEd</span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>
    @endif

    <!-- Section 2: Who We Are -->
    @if($settings['section_about_visible'] ?? true)
    <section class="py-16 sm:py-24 border-b border-[#334155]/60 bg-[#0B1120]/40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl mx-auto text-center space-y-4 mb-14">
                <span class="text-xs font-bold text-[#df747a] tracking-widest uppercase font-brand-accent">Who We Are</span>
                <h2 class="text-2xl sm:text-4xl font-extrabold text-white font-brand-display tracking-tight">
                    The Pulse of Computing at SorSU Bulan
                </h2>
                <p class="text-sm sm:text-base text-slate-300 leading-relaxed">
                    {{ $settings['org_intro'] ?? 'The official student organization representing computing students at Sorsogon State University – Bulan Campus.' }}
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-stretch">
                <!-- Mission Card -->
                <div class="card bg-[#172033] border border-[#334155] p-6 sm:p-8 rounded-2xl relative overflow-hidden flex flex-col justify-between">
                    <div class="space-y-4">
                        <div class="w-12 h-12 rounded-xl bg-[#560E0F]/40 border border-[#560E0F] flex items-center justify-center text-[#df747a]">
                            <i data-lucide="target" class="w-6 h-6"></i>
                        </div>
                        <h3 class="text-xl font-bold text-white font-brand-display">Our Mission</h3>
                        <p class="text-sm text-slate-300 leading-relaxed">
                            {{ $settings['org_mission'] }}
                        </p>
                    </div>
                    <div class="pt-6 mt-6 border-t border-[#334155]/60 text-xs font-semibold text-[#df747a]">
                        Excellence • Integrity • Innovation
                    </div>
                </div>

                <!-- Vision Card -->
                <div class="card bg-[#172033] border border-[#334155] p-6 sm:p-8 rounded-2xl relative overflow-hidden flex flex-col justify-between">
                    <div class="space-y-4">
                        <div class="w-12 h-12 rounded-xl bg-blue-500/10 border border-blue-500/30 flex items-center justify-center text-blue-400">
                            <i data-lucide="eye" class="w-6 h-6"></i>
                        </div>
                        <h3 class="text-xl font-bold text-white font-brand-display">Our Vision</h3>
                        <p class="text-sm text-slate-300 leading-relaxed">
                            {{ $settings['org_vision'] }}
                        </p>
                    </div>
                    <div class="pt-6 mt-6 border-t border-[#334155]/60 text-xs font-semibold text-blue-400">
                        Leadership • Community • Impact
                    </div>
                </div>
            </div>

            <div class="text-center mt-10">
                <a href="{{ route('public.about') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-[#df747a] hover:text-white transition">
                    <span>Learn more about COMSOC's history and background</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </a>
            </div>
        </div>
    </section>
    @endif

    <!-- Section 3: Pillars of Excellence -->
    @if($settings['section_pillars_visible'] ?? true)
    <section class="py-16 sm:py-24 border-b border-[#334155]/60">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl mx-auto text-center space-y-4 mb-14">
                <span class="text-xs font-bold text-[#df747a] tracking-widest uppercase font-brand-accent">Core Foundations</span>
                <h2 class="text-2xl sm:text-4xl font-extrabold text-white font-brand-display tracking-tight">
                    Our Five Organizational Pillars
                </h2>
                <p class="text-sm sm:text-base text-slate-300 leading-relaxed">
                    Every event, program, and collaborative project of COMSOC is anchored on these core pillars.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
                @foreach($settings['pillars'] ?? [] as $pillar)
                <div class="card bg-[#172033] border border-[#334155] p-6 rounded-2xl hover:border-[#df747a] transition-all flex flex-col justify-between group">
                    <div class="space-y-4">
                        <div class="w-12 h-12 rounded-xl bg-[#0B1120] border border-[#334155] group-hover:border-[#df747a] flex items-center justify-center text-[#df747a] transition-colors">
                            <i data-lucide="{{ $pillar['icon'] ?? 'check' }}" class="w-6 h-6"></i>
                        </div>
                        <h3 class="text-lg font-bold text-white font-brand-display">{{ $pillar['title'] }}</h3>
                        <p class="text-xs text-slate-300 leading-relaxed">{{ $pillar['description'] }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <!-- Section 4: Leadership Showcase -->
    @if($settings['section_officers_visible'] ?? true)
    <section class="py-16 sm:py-24 border-b border-[#334155]/60 bg-[#0B1120]/40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-12">
                <div>
                    <span class="text-xs font-bold text-[#df747a] tracking-widest uppercase font-brand-accent">Executive & Board</span>
                    <h2 class="text-2xl sm:text-4xl font-extrabold text-white font-brand-display tracking-tight mt-1">
                        Meet the Leadership
                    </h2>
                    <p class="text-sm text-slate-300 mt-2">
                        Serving the computing student body across BSIT, BSIS, BSCS, and BTVTEd programs.
                    </p>
                </div>
                <a href="{{ route('public.officers') }}" class="btn-secondary text-xs sm:text-sm py-2 px-4 rounded-xl flex items-center gap-1.5 self-start md:self-auto">
                    <span>View All Officers</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </a>
            </div>

            @if($officers->isEmpty())
                <div class="card bg-[#172033] border border-[#334155] p-8 text-center rounded-2xl text-slate-400 text-sm">
                    Officer profiles will be published shortly by the administrator.
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach($officers as $officer)
                    <div class="card bg-[#172033] border border-[#334155] p-5 rounded-2xl flex flex-col items-center text-center group hover:border-[#df747a] transition-all">
                        <div class="w-24 h-24 rounded-2xl overflow-hidden bg-[#0B1120] border border-[#334155] mb-4 p-1">
                            @if($officer->portrait_path)
                                <img src="{{ asset('storage/' . $officer->portrait_path) }}" alt="{{ $officer->name }}" class="w-full h-full object-cover rounded-xl">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-slate-500">
                                    <i data-lucide="user" class="w-10 h-10"></i>
                                </div>
                            @endif
                        </div>
                        <h3 class="text-base font-bold text-white font-brand-display leading-tight">{{ $officer->name }}</h3>
                        <p class="text-xs font-semibold text-[#df747a] mt-1">{{ $officer->position }}</p>
                        @if($officer->program)
                            <span class="inline-block mt-2 px-2 py-0.5 rounded text-[10px] font-mono bg-[#0B1120] text-slate-300 border border-[#334155]">
                                {{ $officer->program }}
                            </span>
                        @endif
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
    @endif

    <!-- Section 5: Activities & Events Showcase -->
    @if($settings['section_activities_visible'] ?? true)
    <section class="py-16 sm:py-24 border-b border-[#334155]/60">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-12">
                <div>
                    <span class="text-xs font-bold text-[#df747a] tracking-widest uppercase font-brand-accent">Activities & Workshops</span>
                    <h2 class="text-2xl sm:text-4xl font-extrabold text-white font-brand-display tracking-tight mt-1">
                        Featured Activities & Events
                    </h2>
                    <p class="text-sm text-slate-300 mt-2">
                        Real-world workshops, hackathons, seminars, and organizational projects.
                    </p>
                </div>
                <a href="{{ route('public.activities') }}" class="btn-secondary text-xs sm:text-sm py-2 px-4 rounded-xl flex items-center gap-1.5 self-start md:self-auto">
                    <span>Explore All Activities</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </a>
            </div>

            @if($activities->isEmpty())
                <div class="card bg-[#172033] border border-[#334155] p-8 text-center rounded-2xl text-slate-400 text-sm">
                    No public activities are currently featured. Check back soon for upcoming announcements!
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($activities as $event)
                    <div class="card bg-[#172033] border border-[#334155] rounded-2xl overflow-hidden hover:border-[#df747a] transition-all flex flex-col justify-between">
                        <div>
                            @if($event->logo_path)
                                <img src="{{ asset('storage/' . $event->logo_path) }}" alt="{{ $event->name }}" class="w-full h-44 object-cover">
                            @else
                                <div class="w-full h-44 bg-gradient-to-r from-[#0B1120] to-[#172033] flex items-center justify-center border-b border-[#334155]">
                                    <i data-lucide="calendar" class="w-12 h-12 text-slate-600"></i>
                                </div>
                            @endif

                            <div class="p-6 space-y-3">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="px-2 py-0.5 rounded text-[11px] font-semibold border {{ $event->status_badge_class }}">
                                        {{ $event->status_label }}
                                    </span>
                                    <span class="text-slate-400 font-mono text-[11px]">
                                        {{ $event->event_date ? $event->event_date->format('M d, Y') : 'Date TBA' }}
                                    </span>
                                </div>

                                <h3 class="text-lg font-bold text-white font-brand-display leading-snug">
                                    {{ $event->name }}
                                </h3>

                                <p class="text-xs text-slate-300 line-clamp-2 leading-relaxed">
                                    {{ $event->description ?: 'No detailed description provided.' }}
                                </p>
                            </div>
                        </div>

                        <div class="p-6 pt-0 border-t border-[#334155]/60 mt-4 flex items-center justify-between">
                            <span class="text-[11px] text-slate-400 flex items-center gap-1 truncate max-w-[160px]">
                                <i data-lucide="map-pin" class="w-3 h-3 text-[#df747a]"></i>
                                <span>{{ $event->effective_venue }}</span>
                            </span>
                            <a href="{{ route('public.activities.detail', $event->resolved_slug) }}" class="text-xs font-semibold text-[#df747a] hover:text-white flex items-center gap-1 transition">
                                <span>View Details</span>
                                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
    @endif

    <!-- Section 6: Achievements & Milestones -->
    @if($settings['section_achievements_visible'] ?? true)
    <section class="py-16 sm:py-24 border-b border-[#334155]/60 bg-[#0B1120]/40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-12">
                <div>
                    <span class="text-xs font-bold text-[#df747a] tracking-widest uppercase font-brand-accent">Verified Accomplishments</span>
                    <h2 class="text-2xl sm:text-4xl font-extrabold text-white font-brand-display tracking-tight mt-1">
                        Achievements & Milestones
                    </h2>
                    <p class="text-sm text-slate-300 mt-2">
                        Celebrating student innovation, competition awards, and organizational progress.
                    </p>
                </div>
                <a href="{{ route('public.achievements') }}" class="btn-secondary text-xs sm:text-sm py-2 px-4 rounded-xl flex items-center gap-1.5 self-start md:self-auto">
                    <span>View All Milestones</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </a>
            </div>

            @if($achievements->isEmpty())
                <div class="card bg-[#172033] border border-[#334155] p-8 text-center rounded-2xl text-slate-400 text-sm">
                    Verified achievements and milestones will be updated by organization administrators.
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($achievements as $ach)
                    <div class="card bg-[#172033] border border-[#334155] p-6 rounded-2xl hover:border-[#df747a] transition-all flex flex-col justify-between">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between text-xs text-[#df747a]">
                                <span class="font-mono">{{ $ach->date_or_year ?? 'Milestone' }}</span>
                                <i data-lucide="award" class="w-5 h-5"></i>
                            </div>
                            <h3 class="text-base font-bold text-white font-brand-display">{{ $ach->title }}</h3>
                            <p class="text-xs text-slate-300 leading-relaxed">{{ $ach->description }}</p>
                        </div>
                        @if($ach->award_details)
                            <div class="mt-4 pt-3 border-t border-[#334155]/60 text-[11px] text-amber-300 flex items-center gap-1.5">
                                <i data-lucide="trophy" class="w-3.5 h-3.5 text-amber-400"></i>
                                <span>{{ $ach->award_details }}</span>
                            </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
    @endif

    <!-- Section 7: Gallery Preview -->
    @if($settings['section_gallery_visible'] ?? true)
    <section class="py-16 sm:py-24 border-b border-[#334155]/60">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-12">
                <div>
                    <span class="text-xs font-bold text-[#df747a] tracking-widest uppercase font-brand-accent">Photo Archive</span>
                    <h2 class="text-2xl sm:text-4xl font-extrabold text-white font-brand-display tracking-tight mt-1">
                        Approved Organization Gallery
                    </h2>
                    <p class="text-sm text-slate-300 mt-2">
                        Moments captured across seminars, hackathons, and community assemblies.
                    </p>
                </div>
                <a href="{{ route('public.gallery') }}" class="btn-secondary text-xs sm:text-sm py-2 px-4 rounded-xl flex items-center gap-1.5 self-start md:self-auto">
                    <span>View Full Gallery</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </a>
            </div>

            @if($gallery->isEmpty())
                <div class="card bg-[#172033] border border-[#334155] p-8 text-center rounded-2xl text-slate-400 text-sm">
                    Approved gallery photos will be showcased here.
                </div>
            @else
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    @foreach($gallery as $item)
                    <div class="card bg-[#172033] border border-[#334155] rounded-xl overflow-hidden group aspect-video relative">
                        <img src="{{ asset('storage/' . $item->image_path) }}" alt="{{ $item->alt_text }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy">
                        @if($item->caption)
                            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity p-3 flex items-end">
                                <span class="text-[11px] text-white leading-tight">{{ $item->caption }}</span>
                            </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
    @endif

    <!-- Section 8: Partners & Collaborations -->
    @if($settings['section_partners_visible'] ?? true)
    <section class="py-16 sm:py-24 border-b border-[#334155]/60 bg-[#0B1120]/40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl mx-auto text-center space-y-4 mb-12">
                <span class="text-xs font-bold text-[#df747a] tracking-widest uppercase font-brand-accent">Ecosystem & Alliances</span>
                <h2 class="text-2xl sm:text-4xl font-extrabold text-white font-brand-display tracking-tight">
                    Partners & Collaborators
                </h2>
                <p class="text-sm text-slate-300">
                    Working together with industry leaders, educational bodies, and technology communities.
                </p>
            </div>

            @if($partners->isEmpty())
                <div class="card bg-[#172033] border border-[#334155] p-8 text-center rounded-2xl text-slate-400 text-sm max-w-xl mx-auto">
                    Confirmed partner collaborations will be published upon approval.
                </div>
            @else
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-6 items-center">
                    @foreach($partners as $partner)
                    <div class="card bg-[#172033] border border-[#334155] p-4 rounded-xl flex flex-col items-center justify-center text-center hover:border-[#df747a] transition-all min-h-[120px]">
                        @if($partner->logo_path)
                            <img src="{{ asset('storage/' . $partner->logo_path) }}" alt="{{ $partner->name }}" class="h-10 object-contain max-w-[100px] mb-2 filter brightness-90 hover:brightness-100 transition">
                        @else
                            <i data-lucide="handshake" class="w-8 h-8 text-[#df747a] mb-2"></i>
                        @endif
                        <span class="text-xs font-semibold text-white truncate max-w-[120px]">{{ $partner->name }}</span>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
    @endif

    <!-- Section 9: Discover COMSOC Hub -->
    @if($settings['section_hub_cta_visible'] ?? true)
    <section class="py-16 sm:py-24 border-b border-[#334155]/60 relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="card bg-gradient-to-br from-[#172033] via-[#0B1120] to-[#560E0F]/40 border border-[#334155] p-8 sm:p-14 rounded-3xl shadow-2xl">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                    <div class="lg:col-span-8 space-y-4">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#560E0F]/50 text-[#df747a] border border-[#560E0F] text-xs font-semibold">
                            <i data-lucide="layers" class="w-3.5 h-3.5"></i>
                            <span>Student Platform</span>
                        </div>
                        <h2 class="text-2xl sm:text-4xl font-extrabold text-white font-brand-display tracking-tight">
                            {{ $settings['hub_headline'] ?? 'Your COMSOC Experience Starts Here.' }}
                        </h2>
                        <p class="text-sm sm:text-base text-slate-300 leading-relaxed max-w-2xl">
                            {{ $settings['hub_description'] ?? 'Access your student profile, participate in activities, track eligible attendance, view your EXP, and discover available rewards through COMSOC Hub.' }}
                        </p>
                        <div class="pt-4 flex flex-wrap items-center gap-3">
                            <a href="{{ route('login') }}" class="btn-primary text-sm py-2.5 px-5 rounded-xl flex items-center gap-2 shadow-lg shadow-[#560E0F]/30">
                                <i data-lucide="log-in" class="w-4 h-4"></i>
                                <span>Sign In to Hub</span>
                            </a>
                            <a href="{{ route('signup') }}" class="btn-secondary text-sm py-2.5 px-5 rounded-xl flex items-center gap-2">
                                <i data-lucide="user-plus" class="w-4 h-4"></i>
                                <span>Register Account</span>
                            </a>
                        </div>
                    </div>

                    <div class="lg:col-span-4 bg-[#0B1120]/80 border border-[#334155] p-5 rounded-2xl space-y-3 text-xs text-slate-300">
                        <h4 class="font-bold text-white text-sm flex items-center gap-2">
                            <i data-lucide="shield-check" class="w-4 h-4 text-emerald-400"></i>
                            <span>Verification & Access</span>
                        </h4>
                        <p>Registration requires an active student number verified against the official university masterlist.</p>
                        <p>Registered students complete handwritten digital signature registration for attendance sheet generation.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endif

    <!-- Section 10: Frequently Asked Questions -->
    @if($settings['section_faq_visible'] ?? true)
    <section class="py-16 sm:py-24 border-b border-[#334155]/60 bg-[#0B1120]/40">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center space-y-4 mb-12">
                <span class="text-xs font-bold text-[#df747a] tracking-widest uppercase font-brand-accent">Inquiries</span>
                <h2 class="text-2xl sm:text-4xl font-extrabold text-white font-brand-display tracking-tight">
                    Frequently Asked Questions
                </h2>
                <p class="text-sm text-slate-300">
                    Answers to common inquiries from students and prospective members.
                </p>
            </div>

            @if($faqs->isEmpty())
                <div class="space-y-4">
                    <div class="card bg-[#172033] border border-[#334155] p-6 rounded-2xl">
                        <h3 class="text-base font-bold text-white font-brand-display mb-2">What is COMSOC?</h3>
                        <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                            The Computing Society (COMSOC) is the official recognized student organization representing all computing and technology discipline students at Sorsogon State University – Bulan Campus.
                        </p>
                    </div>
                    <div class="card bg-[#172033] border border-[#334155] p-6 rounded-2xl">
                        <h3 class="text-base font-bold text-white font-brand-display mb-2">Who can register for COMSOC Hub?</h3>
                        <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                            All currently enrolled computing students at SorSU Bulan Campus whose student numbers exist in the masterlist are eligible to complete registration and create an account.
                        </p>
                    </div>
                    <div class="card bg-[#172033] border border-[#334155] p-6 rounded-2xl">
                        <h3 class="text-base font-bold text-white font-brand-display mb-2">How does QR attendance work?</h3>
                        <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                            Members receive a personal QR code within their dashboard or membership card. During events, scanning terminals record time-in and time-out sessions, which populate verified attendance sheets.
                        </p>
                    </div>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($faqs as $faq)
                    <div class="card bg-[#172033] border border-[#334155] p-6 rounded-2xl">
                        <h3 class="text-base font-bold text-white font-brand-display mb-2">{{ $faq->question }}</h3>
                        <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">{{ $faq->answer }}</p>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
    @endif

    <!-- Section 11: Final Call to Action -->
    <section class="py-16 sm:py-24 text-center relative overflow-hidden">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6 relative z-10">
            <h2 class="text-3xl sm:text-5xl font-black font-brand-display text-white tracking-tight">
                {{ $settings['final_cta_headline'] ?? 'Be Part of the Community. Build What Comes Next.' }}
            </h2>
            <p class="text-base text-slate-300 max-w-xl mx-auto leading-relaxed">
                {{ $settings['final_cta_description'] ?? 'Whether you are writing your first line of code or an aspiring developer preparing for industry, COMSOC welcomes you.' }}
            </p>
            <div class="pt-4 flex flex-wrap items-center justify-center gap-4">
                <a href="{{ route('public.about') }}" class="btn-secondary text-sm sm:text-base py-3 px-6 rounded-xl flex items-center gap-2">
                    <i data-lucide="info" class="w-4 h-4"></i>
                    <span>Discover COMSOC</span>
                </a>
                <a href="{{ route('login') }}" class="btn-primary text-sm sm:text-base py-3 px-6 rounded-xl flex items-center gap-2 shadow-xl shadow-[#560E0F]/30">
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    <span>Access COMSOC Hub</span>
                </a>
            </div>
        </div>
    </section>

</x-public-website-layout>
