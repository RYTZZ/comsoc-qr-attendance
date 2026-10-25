<x-public-website-layout :settings="$settings" :title="$event->name">

    <!-- Editorial Event Header -->
    <section class="py-12 sm:py-16 relative overflow-hidden bg-gradient-to-b from-[#0B1120] to-[#172033]/30">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="space-y-6" data-reveal>
                <div>
                    <a href="{{ route('public.activities') }}" class="inline-flex items-center gap-2 text-xs font-mono font-semibold text-[#df747a] hover:text-white transition">
                        <i data-lucide="arrow-left" class="w-4 h-4"></i>
                        <span>Return to Activities Directory</span>
                    </a>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <span class="px-3 py-1 rounded-full text-xs font-mono font-bold uppercase tracking-wider backdrop-blur-md {{ $event->status_badge_class }}">
                        {{ $event->status_label }}
                    </span>
                    @if($event->event_type)
                        <span class="px-3 py-1 rounded-full text-xs font-mono bg-[#172033]/80 text-slate-300 border border-[#334155]/40">
                            {{ $event->event_type }}
                        </span>
                    @endif
                </div>

                <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black text-white font-brand-display tracking-tight leading-tight max-w-4xl">
                    {{ $event->name }}
                </h1>

                <!-- Key metadata strip -->
                <div class="flex flex-wrap items-center gap-6 text-xs text-slate-300 pt-2 font-mono">
                    <div class="flex items-center gap-2">
                        <i data-lucide="calendar" class="w-4 h-4 text-[#df747a]"></i>
                        <span class="text-white">{{ $event->event_date ? $event->event_date->format('F d, Y') : 'Date TBA' }}</span>
                    </div>
                    @if($event->starts_at)
                    <div class="flex items-center gap-2">
                        <i data-lucide="clock" class="w-4 h-4 text-[#df747a]"></i>
                        <span class="text-white">{{ $event->starts_at->format('h:i A') }} @if($event->ends_at) — {{ $event->ends_at->format('h:i A') }} @endif</span>
                    </div>
                    @endif
                    <div class="flex items-center gap-2">
                        <i data-lucide="map-pin" class="w-4 h-4 text-[#df747a]"></i>
                        <span class="text-white">{{ $event->effective_venue }}</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Detail & Sidebar -->
    <section class="pt-8 sm:pt-10 pb-16 sm:pb-24">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">

                <!-- Left Column: Core Narrative & Media -->
                <div class="lg:col-span-8 space-y-12">

                    <!-- Cover Image if present -->
                    @if($event->logo_path)
                    <div data-reveal class="rounded-2xl overflow-hidden border border-[#334155]/40 shadow-2xl bg-[#0B1120] img-reveal-container">
                        <img src="{{ asset('storage/' . $event->logo_path) }}" alt="{{ $event->name }}" class="w-full max-h-[480px] object-cover">
                    </div>
                    @endif

                    <!-- Description / Narrative -->
                    <div class="space-y-4" data-reveal>
                        <h2 class="text-2xl font-bold text-white font-brand-display">Program Overview</h2>
                        <div class="text-base text-slate-300 leading-relaxed space-y-4 whitespace-pre-line font-normal">
                            {{ $event->description ?: 'No detailed public program overview has been released yet. Registered students may monitor session announcements via COMSOC Hub.' }}
                        </div>
                    </div>

                    <!-- Guidelines -->
                    @if(!empty($event->participation_requirements))
                    <div data-reveal class="p-6 rounded-2xl bg-[#172033]/30 border-l-4 border-l-[#df747a] border-y border-r border-[#334155]/30 space-y-2">
                        <h3 class="text-sm font-bold text-white flex items-center gap-2 uppercase tracking-wider font-mono">
                            <i data-lucide="info" class="w-4 h-4 text-[#df747a]"></i>
                            <span>Participation Guidelines</span>
                        </h3>
                        <p class="text-xs text-slate-300 leading-relaxed">{{ $event->participation_requirements }}</p>
                    </div>
                    @endif

                    <!-- Associated Gallery Highlights -->
                    @if($gallery->isNotEmpty())
                    <div class="space-y-6 pt-6">
                        <div class="flex items-center justify-between pb-3 border-b border-[#334155]/30" data-reveal>
                            <h3 class="text-xl font-bold text-white font-brand-display">Documented Moments</h3>
                            <a href="{{ route('public.gallery') }}" class="text-xs text-[#df747a] hover:text-white transition font-mono flex items-center gap-1">
                                <span>Browse Visual Archive</span>
                                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                            </a>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4" data-reveal-group>
                            @foreach($gallery as $photo)
                            <div data-reveal-item class="rounded-xl overflow-hidden border border-[#334155]/40 aspect-video bg-[#0B1120] group img-reveal-container">
                                <img src="{{ asset('storage/' . $photo->image_path) }}" alt="{{ $photo->alt_text }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                </div>

                <!-- Right Column: Verification & Operations Sidebar -->
                <div class="lg:col-span-4 space-y-8" data-reveal>

                    <!-- Metadata Overview -->
                    <div class="bg-[#172033]/40 p-6 rounded-2xl border border-[#334155]/30 space-y-6">
                        <h3 class="text-xs font-mono font-bold text-slate-400 uppercase tracking-widest">
                            Event Logistics
                        </h3>

                        <div class="space-y-4 text-xs">
                            <div class="space-y-1">
                                <span class="text-slate-500 font-mono text-[10px] uppercase">Host Organization</span>
                                <div class="font-semibold text-white">{{ $event->organizer ?: 'Computing Society (COMSOC)' }}</div>
                            </div>

                            <div class="space-y-1">
                                <span class="text-slate-500 font-mono text-[10px] uppercase">Venue & Campus</span>
                                <div class="font-semibold text-white">{{ $event->effective_venue }}</div>
                                <div class="text-[11px] text-slate-400">Sorsogon State University – Bulan Campus</div>
                            </div>

                            <div class="space-y-1">
                                <span class="text-slate-500 font-mono text-[10px] uppercase">Attendance Validation</span>
                                <div class="font-semibold text-emerald-400 flex items-center gap-1.5">
                                    <i data-lucide="qr-code" class="w-4 h-4"></i>
                                    <span>QR Attendance Check-in Active</span>
                                </div>
                            </div>
                        </div>

                        <!-- Hub Action Box -->
                        <div class="pt-6 border-t border-[#334155]/30 space-y-3">
                            <p class="text-xs text-slate-400 leading-relaxed font-normal">
                                Students enrolled in computing programs can log into COMSOC Hub to access their unique QR code and view attendance history.
                            </p>
                            <a href="{{ route('login') }}" class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-[#560E0F] hover:bg-[#6c1214] text-white text-xs font-semibold shadow-lg shadow-[#560E0F]/30 transition-all">
                                <span>Access COMSOC Hub</span>
                                <i data-lucide="arrow-right" class="w-4 h-4"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Helpful Notice -->
                    <div class="p-6 rounded-2xl bg-[#0B1120] border border-[#334155]/30 space-y-2">
                        <span class="text-[10px] font-mono uppercase tracking-wider text-slate-500">Public Inquiries</span>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            Questions regarding collaboration or event participation may be directed to our official organizational channels.
                        </p>
                    </div>

                </div>

            </div>
        </div>
    </section>

</x-public-website-layout>
