<x-public-website-layout :settings="$settings" :title="$event->name">

    <section class="py-12 sm:py-20 border-b border-[#334155]/60 bg-[#0B1120]">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="space-y-4">
                <a href="{{ route('public.activities') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#df747a] hover:text-white transition">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    <span>Back to Activities</span>
                </a>

                <div class="flex flex-wrap items-center gap-2 pt-2">
                    <span class="px-2.5 py-1 rounded text-xs font-semibold border {{ $event->status_badge_class }}">
                        {{ $event->status_label }}
                    </span>
                    @if($event->event_type)
                        <span class="px-2.5 py-1 rounded text-xs font-mono bg-[#172033] text-slate-300 border border-[#334155]">
                            {{ $event->event_type }}
                        </span>
                    @endif
                </div>

                <h1 class="text-2xl sm:text-4xl lg:text-5xl font-black text-white font-brand-display tracking-tight leading-tight">
                    {{ $event->name }}
                </h1>

                <div class="flex flex-wrap items-center gap-6 text-xs text-slate-300 pt-2 font-mono">
                    <div class="flex items-center gap-2">
                        <i data-lucide="calendar" class="w-4 h-4 text-[#df747a]"></i>
                        <span>{{ $event->event_date ? $event->event_date->format('F d, Y') : 'Date TBA' }}</span>
                    </div>
                    @if($event->starts_at)
                    <div class="flex items-center gap-2">
                        <i data-lucide="clock" class="w-4 h-4 text-[#df747a]"></i>
                        <span>{{ $event->starts_at->format('h:i A') }} @if($event->ends_at) — {{ $event->ends_at->format('h:i A') }} @endif</span>
                    </div>
                    @endif
                    <div class="flex items-center gap-2">
                        <i data-lucide="map-pin" class="w-4 h-4 text-[#df747a]"></i>
                        <span>{{ $event->effective_venue }}</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-12 sm:py-20 border-b border-[#334155]/60">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">

                <!-- Main Details -->
                <div class="lg:col-span-8 space-y-8">
                    @if($event->logo_path)
                    <div class="rounded-2xl overflow-hidden border border-[#334155] shadow-2xl">
                        <img src="{{ asset('storage/' . $event->logo_path) }}" alt="{{ $event->name }}" class="w-full max-h-[420px] object-cover">
                    </div>
                    @endif

                    <div class="card bg-[#172033] border border-[#334155] p-6 sm:p-8 rounded-2xl space-y-4">
                        <h2 class="text-xl font-bold text-white font-brand-display">About This Activity</h2>
                        <div class="text-sm text-slate-300 leading-relaxed whitespace-pre-line">
                            {{ $event->description ?: 'No detailed public description has been posted yet.' }}
                        </div>
                    </div>

                    @if(!empty($event->participation_requirements))
                    <div class="card bg-[#172033] border border-[#334155] p-6 rounded-2xl space-y-2">
                        <h3 class="text-sm font-bold text-white flex items-center gap-2">
                            <i data-lucide="alert-circle" class="w-4 h-4 text-[#df747a]"></i>
                            <span>Participation Guidelines</span>
                        </h3>
                        <p class="text-xs text-slate-300 leading-relaxed">{{ $event->participation_requirements }}</p>
                    </div>
                    @endif

                    <!-- Associated Photos -->
                    @if($gallery->isNotEmpty())
                    <div class="space-y-4 pt-4">
                        <h3 class="text-lg font-bold text-white font-brand-display">Activity Highlights</h3>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            @foreach($gallery as $photo)
                            <div class="rounded-xl overflow-hidden border border-[#334155] aspect-video">
                                <img src="{{ asset('storage/' . $photo->image_path) }}" alt="{{ $photo->alt_text }}" class="w-full h-full object-cover">
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Sidebar Metadata -->
                <div class="lg:col-span-4 space-y-6">
                    <div class="card bg-[#172033] border border-[#334155] p-6 rounded-2xl space-y-4">
                        <h3 class="text-sm font-bold text-white uppercase tracking-wider font-brand-accent">Activity Summary</h3>
                        <div class="space-y-3 text-xs text-slate-300">
                            <div>
                                <span class="block text-slate-500 font-mono text-[10px] uppercase">Organizer</span>
                                <span class="font-semibold text-white">{{ $event->organizer ?: 'Computing Society (COMSOC)' }}</span>
                            </div>
                            <div>
                                <span class="block text-slate-500 font-mono text-[10px] uppercase">Location</span>
                                <span class="font-semibold text-white">{{ $event->effective_venue }}</span>
                            </div>
                            <div>
                                <span class="block text-slate-500 font-mono text-[10px] uppercase">Attendance Tracking</span>
                                <span class="font-semibold text-emerald-400">QR Code Verified</span>
                            </div>
                        </div>

                        <!-- Hub Login Action -->
                        <div class="pt-4 border-t border-[#334155]">
                            <p class="text-[11px] text-slate-400 mb-3">Enrolled computing students can view this event and record eligible attendance on COMSOC Hub.</p>
                            <a href="{{ route('login') }}" class="btn-primary w-full text-center py-2.5 text-xs flex items-center justify-center gap-2">
                                <i data-lucide="log-in" class="w-4 h-4"></i>
                                <span>Access COMSOC Hub</span>
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

</x-public-website-layout>
