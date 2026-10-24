<x-public-website-layout :settings="$settings" :title="'Home'">

    <!-- Hero Section: Editorial & Asymmetric, Clean Logo & Typography -->
    @if($settings['section_hero_visible'] ?? true)
    <section id="hero" class="relative overflow-hidden pt-12 pb-20 sm:pt-20 sm:pb-32 border-b border-[#334155]/40 scroll-mt-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8 items-center">

                <!-- Left Column: Bold Typography & Narrative (7 cols) -->
                <div class="lg:col-span-7 space-y-6 text-center lg:text-left">
                    <div class="inline-flex items-center gap-2.5 px-3 py-1.5 rounded-full bg-[#560E0F]/25 border border-[#560E0F]/60 text-[#df747a] text-xs font-semibold tracking-wide">
                        <span class="w-2 h-2 rounded-full bg-[#df747a] animate-pulse"></span>
                        <span>Computing Society • Sorsogon State University – Bulan Campus</span>
                    </div>

                    <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black font-brand-display text-white tracking-tight leading-[1.08]">
                        {{ $settings['hero_headline'] ?? 'Where Computing Minds Build What\'s Next.' }}
                    </h1>

                    <p class="text-base sm:text-lg text-slate-300 max-w-2xl mx-auto lg:mx-0 leading-relaxed font-normal">
                        {{ $settings['hero_description'] ?? 'COMSOC brings computing students together to develop technical skills, create meaningful projects, build lifelong connections, and strengthen our campus community.' }}
                    </p>

                    <div class="flex flex-wrap items-center justify-center lg:justify-start gap-4 pt-2">
                        <a href="{{ route('public.about') }}"
                           class="btn-secondary text-sm sm:text-base py-3 px-6 rounded-xl flex items-center gap-2 border-[#334155] hover:border-[#df747a] transition-all">
                            <span>Discover COMSOC</span>
                            <i data-lucide="arrow-down" class="w-4 h-4 text-[#df747a]"></i>
                        </a>

                        <a href="{{ route('public.activities') }}"
                           class="btn-primary text-sm sm:text-base py-3 px-6 rounded-xl flex items-center gap-2 shadow-xl shadow-[#560E0F]/25 hover:shadow-[#560E0F]/40 transition-all">
                            <span>Explore Activities</span>
                            <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </a>
                    </div>

                    <!-- Highlight Metrics & Impact Indicators -->
                    <div class="pt-8 grid grid-cols-3 gap-6 border-t border-[#334155]/40 max-w-lg mx-auto lg:mx-0 text-left">
                        <div>
                            <span class="block text-2xl sm:text-3xl font-black font-brand-display text-white">5</span>
                            <span class="text-xs text-slate-400">Foundational Pillars</span>
                        </div>
                        <div>
                            <span class="block text-2xl sm:text-3xl font-black font-brand-display text-white">4</span>
                            <span class="text-xs text-slate-400">Computing Programs</span>
                        </div>
                        <div>
                            <span class="block text-2xl sm:text-3xl font-black font-brand-display text-white">100%</span>
                            <span class="text-xs text-slate-400">Student Empowered</span>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Authentic Visual Presentation (5 cols) -->
                <div class="lg:col-span-5 relative flex items-center justify-center">
                    @if(!empty($settings['hero_image']))
                        <div class="relative w-full max-w-lg rounded-2xl overflow-hidden shadow-2xl border border-[#334155]/60">
                            <img src="{{ asset('storage/' . $settings['hero_image']) }}" alt="COMSOC Leadership and Community" class="w-full h-80 sm:h-[420px] object-cover">
                            <div class="absolute inset-0 bg-gradient-to-t from-[#0B1120] via-transparent to-transparent opacity-60"></div>
                        </div>
                    @else
                        <!-- Direct, Non-Boxed Authentic Emblem Showcase -->
                        <div class="relative w-full max-w-md flex flex-col items-center text-center p-6 sm:p-8">
                            <div class="relative mb-6">
                                <img src="{{ asset('images/COMSOC.png') }}" alt="Computing Society Emblem" class="w-44 h-44 sm:w-56 sm:h-56 object-contain drop-shadow-2xl">
                            </div>
                            <div class="space-y-2">
                                <h2 class="text-xl sm:text-2xl font-bold font-brand-display text-white">Computing Society</h2>
                                <p class="text-xs sm:text-sm text-slate-400 max-w-xs mx-auto">
                                    Official Student Organization • College of Information and Communications Technology
                                </p>
                                <div class="pt-3 flex flex-wrap items-center justify-center gap-2">
                                    <span class="px-2.5 py-1 rounded-md text-xs font-mono text-slate-300 bg-[#172033] border border-[#334155]/60">BSIT</span>
                                    <span class="px-2.5 py-1 rounded-md text-xs font-mono text-slate-300 bg-[#172033] border border-[#334155]/60">BSIS</span>
                                    <span class="px-2.5 py-1 rounded-md text-xs font-mono text-slate-300 bg-[#172033] border border-[#334155]/60">BSCS</span>
                                    <span class="px-2.5 py-1 rounded-md text-xs font-mono text-slate-300 bg-[#172033] border border-[#334155]/60">BTVTEd</span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </section>
    @endif

    <!-- Section 2: Who We Are / Editorial Introduction -->
    @if($settings['section_about_visible'] ?? true)
    <section id="about" class="py-16 sm:py-24 border-b border-[#334155]/40 scroll-mt-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-start">
                <div class="lg:col-span-5 space-y-4">
                    <span class="text-xs font-bold text-[#df747a] tracking-widest uppercase font-brand-accent">Who We Are</span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-white font-brand-display tracking-tight leading-tight">
                        The Pulse of Computing at SorSU Bulan
                    </h2>
                    <p class="text-sm sm:text-base text-slate-300 leading-relaxed">
                        {{ $settings['org_intro'] ?? 'The official student organization representing computing and information technology students at Sorsogon State University – Bulan Campus.' }}
                    </p>
                    <div class="pt-2">
                        <a href="{{ route('public.about') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-[#df747a] hover:text-white transition-colors">
                            <span>Read our full organizational story & mission</span>
                            <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </a>
                    </div>
                </div>

                <div class="lg:col-span-7 grid grid-cols-1 sm:grid-cols-2 gap-8">
                    <!-- Mission Statement -->
                    <div class="space-y-3 p-6 rounded-2xl bg-[#172033]/60 border border-[#334155]/40">
                        <div class="flex items-center gap-3">
                            <i data-lucide="target" class="w-5 h-5 text-[#df747a]"></i>
                            <h3 class="text-lg font-bold text-white font-brand-display">Our Mission</h3>
                        </div>
                        <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                            {{ $settings['org_mission'] }}
                        </p>
                    </div>

                    <!-- Vision Statement -->
                    <div class="space-y-3 p-6 rounded-2xl bg-[#172033]/60 border border-[#334155]/40">
                        <div class="flex items-center gap-3">
                            <i data-lucide="eye" class="w-5 h-5 text-blue-400"></i>
                            <h3 class="text-lg font-bold text-white font-brand-display">Our Vision</h3>
                        </div>
                        <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                            {{ $settings['org_vision'] }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endif

    <!-- Section 3: The Five Organizational Pillars -->
    @if($settings['section_pillars_visible'] ?? true)
    <section id="pillars" class="py-16 sm:py-24 border-b border-[#334155]/40 scroll-mt-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-12">
                <div>
                    <span class="text-xs font-bold text-[#df747a] tracking-widest uppercase font-brand-accent">Core Foundations</span>
                    <h2 class="text-2xl sm:text-4xl font-extrabold text-white font-brand-display tracking-tight mt-1">
                        Our Five Organizational Pillars
                    </h2>
                    <p class="text-sm text-slate-300 mt-2 max-w-xl">
                        Every event, workshop, and collaborative project of COMSOC is anchored on these core organizational pillars.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
                @foreach($settings['pillars'] ?? [] as $pillar)
                <div class="p-6 rounded-2xl bg-[#172033]/60 border border-[#334155]/40 hover:border-[#df747a]/60 transition-all flex flex-col justify-between group">
                    <div class="space-y-4">
                        <div class="w-10 h-10 rounded-xl bg-[#0B1120] border border-[#334155]/60 group-hover:border-[#df747a] flex items-center justify-center text-[#df747a] transition-colors">
                            <i data-lucide="{{ $pillar['icon'] ?? 'check' }}" class="w-5 h-5"></i>
                        </div>
                        <h3 class="text-base font-bold text-white font-brand-display">{{ $pillar['title'] }}</h3>
                        <p class="text-xs text-slate-300 leading-relaxed">{{ $pillar['description'] }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <!-- Section 4: Activities & Events Showcase -->
    @if($settings['section_activities_visible'] ?? true)
    <section id="activities" class="py-16 sm:py-24 border-b border-[#334155]/40 scroll-mt-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-12">
                <div>
                    <span class="text-xs font-bold text-[#df747a] tracking-widest uppercase font-brand-accent">Events & Workshops</span>
                    <h2 class="text-2xl sm:text-4xl font-extrabold text-white font-brand-display tracking-tight mt-1">
                        Featured Activities & Events
                    </h2>
                    <p class="text-sm text-slate-300 mt-2">
                        Workshops, tech assemblies, hackathons, and organizational seminars.
                    </p>
                </div>
                <a href="{{ route('public.activities') }}" class="btn-secondary text-xs sm:text-sm py-2 px-4 rounded-xl flex items-center gap-1.5 self-start md:self-auto">
                    <span>Explore All Activities</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </a>
            </div>

            @if($activities->isEmpty())
                <div class="p-8 text-center rounded-2xl bg-[#172033]/40 border border-[#334155]/40 text-slate-400 text-sm">
                    No public activities are currently featured. Check back soon for upcoming announcements!
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($activities as $event)
                    <div class="rounded-2xl bg-[#172033]/70 border border-[#334155]/50 overflow-hidden hover:border-[#df747a]/60 transition-all flex flex-col justify-between group">
                        <div>
                            @if($event->logo_path)
                                <img src="{{ asset('storage/' . $event->logo_path) }}" alt="{{ $event->name }}" class="w-full h-44 object-cover">
                            @else
                                <div class="w-full h-44 bg-gradient-to-r from-[#0B1120] to-[#172033] flex items-center justify-center border-b border-[#334155]/40">
                                    <i data-lucide="calendar" class="w-10 h-10 text-slate-600"></i>
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

                                <h3 class="text-lg font-bold text-white font-brand-display leading-snug group-hover:text-[#df747a] transition-colors">
                                    {{ $event->name }}
                                </h3>

                                <p class="text-xs text-slate-300 line-clamp-2 leading-relaxed">
                                    {{ $event->description ?: 'No detailed description provided.' }}
                                </p>
                            </div>
                        </div>

                        <div class="p-6 pt-0 border-t border-[#334155]/40 mt-4 flex items-center justify-between">
                            <span class="text-[11px] text-slate-400 flex items-center gap-1 truncate max-w-[160px]">
                                <i data-lucide="map-pin" class="w-3 h-3 text-[#df747a]"></i>
                                <span>{{ $event->effective_venue }}</span>
                            </span>
                            <a href="{{ route('public.activities.detail', $event->resolved_slug) }}" class="text-xs font-semibold text-[#df747a] hover:text-white flex items-center gap-1 transition-colors">
                                <span>Details</span>
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

    <!-- Section 5: Achievements & Milestones -->
    @if($settings['section_achievements_visible'] ?? true)
    <section id="achievements" class="py-16 sm:py-24 border-b border-[#334155]/40 scroll-mt-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-12">
                <div>
                    <span class="text-xs font-bold text-[#df747a] tracking-widest uppercase font-brand-accent">Organizational Honors</span>
                    <h2 class="text-2xl sm:text-4xl font-extrabold text-white font-brand-display tracking-tight mt-1">
                        Achievements & Milestones
                    </h2>
                    <p class="text-sm text-slate-300 mt-2">
                        Competitive awards, completed development milestones, and student honors.
                    </p>
                </div>
                <a href="{{ route('public.achievements') }}" class="btn-secondary text-xs sm:text-sm py-2 px-4 rounded-xl flex items-center gap-1.5 self-start md:self-auto">
                    <span>View All Milestones</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </a>
            </div>

            @if($achievements->isEmpty())
                <div class="p-8 text-center rounded-2xl bg-[#172033]/40 border border-[#334155]/40 text-slate-400 text-sm">
                    Verified achievements and milestones will be updated by organization administrators.
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($achievements as $ach)
                    <div class="p-6 rounded-2xl bg-[#172033]/60 border border-[#334155]/40 hover:border-[#df747a]/60 transition-all flex flex-col justify-between">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between text-xs text-[#df747a]">
                                <span class="font-mono font-bold">{{ $ach->date_or_year ?? 'Milestone' }}</span>
                                <i data-lucide="award" class="w-5 h-5"></i>
                            </div>
                            <h3 class="text-base font-bold text-white font-brand-display">{{ $ach->title }}</h3>
                            <p class="text-xs text-slate-300 leading-relaxed">{{ $ach->description }}</p>
                        </div>
                        @if($ach->award_details)
                            <div class="mt-4 pt-3 border-t border-[#334155]/40 text-[11px] text-amber-300 flex items-center gap-1.5">
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

    <!-- Section 6: Leadership Preview -->
    @if($settings['section_officers_visible'] ?? true)
    <section id="officers" class="py-16 sm:py-24 border-b border-[#334155]/40 scroll-mt-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-12">
                <div>
                    <span class="text-xs font-bold text-[#df747a] tracking-widest uppercase font-brand-accent">Leadership</span>
                    <h2 class="text-2xl sm:text-4xl font-extrabold text-white font-brand-display tracking-tight mt-1">
                        Meet the Leadership Team
                    </h2>
                    <p class="text-sm text-slate-300 mt-2">
                        Serving the student body across BSIT, BSIS, BSCS, and BTVTEd programs.
                    </p>
                </div>
                <a href="{{ route('public.officers') }}" class="btn-secondary text-xs sm:text-sm py-2 px-4 rounded-xl flex items-center gap-1.5 self-start md:self-auto">
                    <span>View All Officers</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </a>
            </div>

            @if($officers->isEmpty())
                <div class="p-8 text-center rounded-2xl bg-[#172033]/40 border border-[#334155]/40 text-slate-400 text-sm">
                    Officer profiles will be published shortly by the administrator.
                </div>
            @else
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-6">
                    @foreach($officers as $officer)
                    <div class="p-5 rounded-2xl bg-[#172033]/60 border border-[#334155]/40 flex flex-col items-center text-center group hover:border-[#df747a]/60 transition-all">
                        <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-full overflow-hidden bg-[#0B1120] border border-[#334155] mb-3">
                            @if($officer->portrait_path)
                                <img src="{{ asset('storage/' . $officer->portrait_path) }}" alt="{{ $officer->name }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-slate-500">
                                    <i data-lucide="user" class="w-8 h-8"></i>
                                </div>
                            @endif
                        </div>
                        <h3 class="text-sm sm:text-base font-bold text-white font-brand-display leading-tight">{{ $officer->name }}</h3>
                        <p class="text-xs font-semibold text-[#df747a] mt-1">{{ $officer->position }}</p>
                        @if($officer->program)
                            <span class="inline-block mt-2 px-2 py-0.5 rounded text-[10px] font-mono bg-[#0B1120] text-slate-300 border border-[#334155]/60">
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

    <!-- Section 7: Gallery Preview -->
    @if($settings['section_gallery_visible'] ?? true)
    <section id="gallery" class="py-16 sm:py-24 border-b border-[#334155]/40 scroll-mt-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-12">
                <div>
                    <span class="text-xs font-bold text-[#df747a] tracking-widest uppercase font-brand-accent">Visual Archive</span>
                    <h2 class="text-2xl sm:text-4xl font-extrabold text-white font-brand-display tracking-tight mt-1">
                        Moments & Memories
                    </h2>
                    <p class="text-sm text-slate-300 mt-2">
                        Highlights captured across bootcamps, community days, and student assemblies.
                    </p>
                </div>
                <a href="{{ route('public.gallery') }}" class="btn-secondary text-xs sm:text-sm py-2 px-4 rounded-xl flex items-center gap-1.5 self-start md:self-auto">
                    <span>View Full Gallery</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </a>
            </div>

            @if($gallery->isEmpty())
                <div class="p-8 text-center rounded-2xl bg-[#172033]/40 border border-[#334155]/40 text-slate-400 text-sm">
                    Approved gallery photos will be showcased here.
                </div>
            @else
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4" x-data="{ hpLightboxOpen: false, hpImage: '', hpCaption: '' }">
                    @foreach($gallery as $item)
                    <div class="rounded-xl overflow-hidden group aspect-video relative border border-[#334155]/50 hover:border-[#df747a] transition-colors">
                        <button type="button"
                                @click="hpLightboxOpen = true; hpImage = '{{ asset('storage/' . $item->image_path) }}'; hpCaption = '{{ addslashes($item->caption ?? '') }}'"
                                class="w-full h-full text-left relative block focus:outline-none focus-visible:ring-2 focus-visible:ring-[#df747a]"
                                aria-label="View photo preview">
                            <img src="{{ asset('storage/' . $item->image_path) }}" alt="{{ $item->alt_text }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity p-3 flex items-end">
                                @if($item->caption)
                                    <span class="text-[11px] text-white leading-tight line-clamp-2">{{ $item->caption }}</span>
                                @else
                                    <span class="text-[11px] text-slate-300">View photo</span>
                                @endif
                            </div>
                        </button>
                    </div>
                    @endforeach

                    <!-- Home Lightbox Modal -->
                    <div x-show="hpLightboxOpen"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         @keydown.escape.window="hpLightboxOpen = false"
                         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/90 backdrop-blur-md"
                         style="display: none;"
                         role="dialog"
                         aria-modal="true"
                         aria-label="Gallery Preview">
                        <div class="relative max-w-4xl w-full bg-[#172033] border border-[#334155] rounded-2xl overflow-hidden shadow-2xl"
                             @click.away="hpLightboxOpen = false">
                            <div class="flex items-center justify-between p-4 border-b border-[#334155]/80 bg-[#0B1120]">
                                <span class="text-sm font-bold text-white">COMSOC Gallery Moment</span>
                                <button type="button"
                                        @click="hpLightboxOpen = false"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-[#172033] focus:outline-none focus:ring-2 focus:ring-[#df747a]"
                                        aria-label="Close dialog">
                                    <i data-lucide="x" class="w-5 h-5"></i>
                                </button>
                            </div>
                            <div class="max-h-[75vh] flex items-center justify-center bg-black">
                                <img :src="hpImage" alt="Gallery preview" class="max-h-[70vh] w-auto max-w-full object-contain">
                            </div>
                            <div x-show="hpCaption" class="p-4 bg-[#0B1120] border-t border-[#334155]/80">
                                <p class="text-xs text-slate-300" x-text="hpCaption"></p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>
    @endif

    <!-- Section 8: Partners & Collaborations -->
    @if($settings['section_partners_visible'] ?? true)
    <section id="partners" class="py-16 sm:py-24 border-b border-[#334155]/40 scroll-mt-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl mx-auto text-center space-y-3 mb-12">
                <span class="text-xs font-bold text-[#df747a] tracking-widest uppercase font-brand-accent">Ecosystem & Alliances</span>
                <h2 class="text-2xl sm:text-4xl font-extrabold text-white font-brand-display tracking-tight">
                    Partners & Collaborators
                </h2>
                <p class="text-sm text-slate-300">
                    Connecting students with campus organizations, academic communities, and technology partners.
                </p>
            </div>

            @if($partners->isEmpty())
                <div class="p-8 text-center rounded-2xl bg-[#172033]/40 border border-[#334155]/40 text-slate-400 text-sm max-w-xl mx-auto">
                    Confirmed partner collaborations will be published upon approval.
                </div>
            @else
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-6 items-center">
                    @foreach($partners as $partner)
                    <div class="p-4 rounded-xl bg-[#172033]/50 border border-[#334155]/40 flex flex-col items-center justify-center text-center hover:border-[#df747a]/60 transition-all min-h-[110px]">
                        @if($partner->logo_path)
                            <img src="{{ asset('storage/' . $partner->logo_path) }}" alt="{{ $partner->name }}" class="h-10 object-contain max-w-[100px] mb-2 filter brightness-90 hover:brightness-100 transition">
                        @else
                            <i data-lucide="handshake" class="w-7 h-7 text-[#df747a] mb-2"></i>
                        @endif
                        <span class="text-xs font-semibold text-white truncate max-w-[120px]">{{ $partner->name }}</span>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
    @endif

    <!-- Section 9: Discover COMSOC Hub (Student Portal Entry) -->
    @if($settings['section_hub_cta_visible'] ?? true)
    <section id="hub-portal" class="py-16 sm:py-24 border-b border-[#334155]/40 scroll-mt-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center p-8 sm:p-12 rounded-3xl bg-gradient-to-r from-[#172033] to-[#0B1120] border border-[#334155]/60">
                <div class="lg:col-span-8 space-y-4">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#560E0F]/40 text-[#df747a] border border-[#560E0F]/60 text-xs font-semibold">
                        <i data-lucide="layers" class="w-3.5 h-3.5"></i>
                        <span>Student Platform</span>
                    </div>
                    <h2 class="text-2xl sm:text-4xl font-extrabold text-white font-brand-display tracking-tight">
                        {{ $settings['hub_headline'] ?? 'Your COMSOC Experience Starts Here.' }}
                    </h2>
                    <p class="text-sm sm:text-base text-slate-300 leading-relaxed max-w-2xl">
                        {{ $settings['hub_description'] ?? 'Access your student profile, participate in activities, track verified QR attendance, earn EXP, and unlock rewards through COMSOC Hub.' }}
                    </p>
                    <div class="pt-2 flex flex-wrap items-center gap-3">
                        <a href="{{ route('login') }}" class="btn-primary text-sm py-2.5 px-5 rounded-xl flex items-center gap-2 shadow-lg shadow-[#560E0F]/30">
                            <span>Access COMSOC Hub</span>
                            <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </a>
                        <a href="{{ route('signup') }}" class="btn-secondary text-sm py-2.5 px-5 rounded-xl flex items-center gap-2">
                            <span>Register Student Account</span>
                        </a>
                    </div>
                </div>

                <div class="lg:col-span-4 p-5 rounded-2xl bg-[#0B1120]/70 border border-[#334155]/50 space-y-3 text-xs text-slate-300">
                    <h4 class="font-bold text-white text-sm flex items-center gap-2">
                        <i data-lucide="shield-check" class="w-4 h-4 text-emerald-400"></i>
                        <span>Official Verification</span>
                    </h4>
                    <p>Registration requires an active student number verified against the university masterlist.</p>
                    <p>Members complete digital handwritten signatures for official event attendance validation.</p>
                </div>
            </div>
        </div>
    </section>
    @endif

    <!-- Section 10: Frequently Asked Questions -->
    @if($settings['section_faq_visible'] ?? true)
    <section id="faqs" class="py-16 sm:py-24 border-b border-[#334155]/40 scroll-mt-20">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center space-y-3 mb-12">
                <span class="text-xs font-bold text-[#df747a] tracking-widest uppercase font-brand-accent">Inquiries</span>
                <h2 class="text-2xl sm:text-4xl font-extrabold text-white font-brand-display tracking-tight">
                    Frequently Asked Questions
                </h2>
                <p class="text-sm text-slate-300">
                    Answers to common questions from students and prospective members.
                </p>
            </div>

            @if($faqs->isEmpty())
                <div class="space-y-4" x-data="{ activeFaq: null }">
                    <div class="p-6 rounded-2xl bg-[#172033]/60 border border-[#334155]/40 transition-colors">
                        <button type="button"
                                @click="activeFaq = (activeFaq === 1 ? null : 1)"
                                class="w-full text-left flex items-center justify-between gap-4 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#df747a]"
                                :aria-expanded="activeFaq === 1">
                            <span class="text-base font-bold text-white font-brand-display">What is COMSOC?</span>
                            <i data-lucide="chevron-down" class="w-4 h-4 text-[#df747a] shrink-0 transition-transform duration-200" :class="{ 'rotate-180': activeFaq === 1 }"></i>
                        </button>
                        <div x-show="activeFaq === 1" x-collapse class="text-xs sm:text-sm text-slate-300 leading-relaxed border-t border-[#334155]/40 pt-4 mt-4">
                            The Computing Society (COMSOC) is the official recognized student organization representing all computing and technology discipline students at Sorsogon State University – Bulan Campus.
                        </div>
                    </div>

                    <div class="p-6 rounded-2xl bg-[#172033]/60 border border-[#334155]/40 transition-colors">
                        <button type="button"
                                @click="activeFaq = (activeFaq === 2 ? null : 2)"
                                class="w-full text-left flex items-center justify-between gap-4 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#df747a]"
                                :aria-expanded="activeFaq === 2">
                            <span class="text-base font-bold text-white font-brand-display">Who can register for COMSOC Hub?</span>
                            <i data-lucide="chevron-down" class="w-4 h-4 text-[#df747a] shrink-0 transition-transform duration-200" :class="{ 'rotate-180': activeFaq === 2 }"></i>
                        </button>
                        <div x-show="activeFaq === 2" x-collapse class="text-xs sm:text-sm text-slate-300 leading-relaxed border-t border-[#334155]/40 pt-4 mt-4">
                            All currently enrolled computing students at SorSU Bulan Campus whose student numbers exist in the masterlist are eligible to complete registration and create an account.
                        </div>
                    </div>

                    <div class="p-6 rounded-2xl bg-[#172033]/60 border border-[#334155]/40 transition-colors">
                        <button type="button"
                                @click="activeFaq = (activeFaq === 3 ? null : 3)"
                                class="w-full text-left flex items-center justify-between gap-4 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#df747a]"
                                :aria-expanded="activeFaq === 3">
                            <span class="text-base font-bold text-white font-brand-display">How does QR attendance work?</span>
                            <i data-lucide="chevron-down" class="w-4 h-4 text-[#df747a] shrink-0 transition-transform duration-200" :class="{ 'rotate-180': activeFaq === 3 }"></i>
                        </button>
                        <div x-show="activeFaq === 3" x-collapse class="text-xs sm:text-sm text-slate-300 leading-relaxed border-t border-[#334155]/40 pt-4 mt-4">
                            Members receive a personal QR code within their dashboard. During events, scanning terminals record time-in and time-out sessions, which populate verified attendance sheets.
                        </div>
                    </div>
                </div>
            @else
                <div class="space-y-4" x-data="{ activeFaq: null }">
                    @foreach($faqs as $idx => $faq)
                    <div class="p-6 rounded-2xl bg-[#172033]/60 border border-[#334155]/40 transition-colors">
                        <button type="button"
                                @click="activeFaq = (activeFaq === {{ $idx }} ? null : {{ $idx }})"
                                class="w-full text-left flex items-center justify-between gap-4 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#df747a]"
                                :aria-expanded="activeFaq === {{ $idx }}">
                            <span class="text-base font-bold text-white font-brand-display">{{ $faq->question }}</span>
                            <i data-lucide="chevron-down" class="w-4 h-4 text-[#df747a] shrink-0 transition-transform duration-200" :class="{ 'rotate-180': activeFaq === {{ $idx }} }"></i>
                        </button>
                        <div x-show="activeFaq === {{ $idx }}" x-collapse class="text-xs sm:text-sm text-slate-300 leading-relaxed border-t border-[#334155]/40 pt-4 mt-4">
                            {{ $faq->answer }}
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
    @endif

    <!-- Section 11: Final Call to Action -->
    <section class="py-20 sm:py-28 text-center relative overflow-hidden">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6 relative z-10">
            <h2 class="text-3xl sm:text-5xl font-black font-brand-display text-white tracking-tight leading-tight">
                {{ $settings['final_cta_headline'] ?? 'Be Part of the Community. Build What Comes Next.' }}
            </h2>
            <p class="text-base sm:text-lg text-slate-300 max-w-xl mx-auto leading-relaxed">
                {{ $settings['final_cta_description'] ?? 'Whether you are writing your first line of code or an aspiring developer preparing for industry, COMSOC welcomes you.' }}
            </p>
            <div class="pt-4 flex flex-wrap items-center justify-center gap-4">
                <a href="{{ route('public.about') }}" class="btn-secondary text-sm sm:text-base py-3 px-6 rounded-xl flex items-center gap-2">
                    <i data-lucide="info" class="w-4 h-4"></i>
                    <span>Discover COMSOC</span>
                </a>
                <a href="{{ route('login') }}" class="btn-primary text-sm sm:text-base py-3 px-6 rounded-xl flex items-center gap-2 shadow-xl shadow-[#560E0F]/30">
                    <span>Access COMSOC Hub</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </a>
            </div>
        </div>
    </section>

</x-public-website-layout>
