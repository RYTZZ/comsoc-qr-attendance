<x-public-website-layout :settings="$settings" :title="'Activities & Events'">

    <!-- Editorial Header -->
    <section class="py-16 sm:py-24 relative overflow-hidden bg-gradient-to-b from-[#0B1120] to-[#172033]/30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl space-y-4">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#560E0F]/20 border border-[#df747a]/30 text-[#df747a] text-xs font-mono font-semibold uppercase tracking-wider">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#df747a] animate-pulse"></span>
                    <span>Initiatives & Engagements</span>
                </div>
                <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black text-white font-brand-display tracking-tight leading-tight">
                    COMSOC Activities & Technical Programs
                </h1>
                <p class="text-base sm:text-lg text-slate-300 leading-relaxed max-w-2xl font-normal">
                    Discover workshops, algorithmic bootcamps, community development projects, and public forums hosted by the Computing Society at Sorsogon State University – Bulan Campus.
                </p>
            </div>
        </div>
    </section>

    <!-- Main Activities Section -->
    <section class="py-12 sm:py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">

            <!-- Filter Controls -->
            <div class="flex flex-wrap items-center justify-between gap-4 pb-6 border-b border-[#334155]/30">
                <div class="flex items-center gap-2">
                    <a href="{{ route('public.activities', ['status' => 'all']) }}"
                       class="px-4 py-2 rounded-xl text-xs font-semibold transition-all {{ $filter === 'all' ? 'bg-[#560E0F] text-white shadow-lg shadow-[#560E0F]/30' : 'bg-[#172033]/40 text-slate-300 hover:text-white hover:bg-[#172033] border border-[#334155]/40' }}">
                        All Activities
                    </a>
                    <a href="{{ route('public.activities', ['status' => 'upcoming']) }}"
                       class="px-4 py-2 rounded-xl text-xs font-semibold transition-all {{ $filter === 'upcoming' ? 'bg-[#560E0F] text-white shadow-lg shadow-[#560E0F]/30' : 'bg-[#172033]/40 text-slate-300 hover:text-white hover:bg-[#172033] border border-[#334155]/40' }}">
                        Upcoming & Active
                    </a>
                    <a href="{{ route('public.activities', ['status' => 'completed']) }}"
                       class="px-4 py-2 rounded-xl text-xs font-semibold transition-all {{ $filter === 'completed' ? 'bg-[#560E0F] text-white shadow-lg shadow-[#560E0F]/30' : 'bg-[#172033]/40 text-slate-300 hover:text-white hover:bg-[#172033] border border-[#334155]/40' }}">
                        Concluded
                    </a>
                </div>

                <div class="text-xs text-slate-400 font-mono">
                    Showing {{ $activities->total() }} recorded {{ Str::plural('activity', $activities->total()) }}
                </div>
            </div>

            @if($activities->isEmpty())
                <div class="py-16 text-center max-w-md mx-auto space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-[#172033]/40 border border-[#334155]/40 flex items-center justify-center mx-auto text-slate-400">
                        <i data-lucide="calendar-x" class="w-6 h-6"></i>
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-base font-bold text-white font-brand-display">No activities found</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            No activities match the current filter selection. Check back soon as our executive board publishes new semester schedules.
                        </p>
                    </div>
                    <div>
                        <a href="{{ route('public.activities', ['status' => 'all']) }}" class="inline-flex items-center gap-1.5 text-xs text-[#df747a] hover:text-white transition font-semibold">
                            <span>View All Activities</span>
                            <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                        </a>
                    </div>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    @foreach($activities as $event)
                    <article class="group flex flex-col justify-between rounded-2xl bg-[#172033]/30 border border-[#334155]/30 p-5 hover:border-[#df747a]/50 transition-colors">
                        <div class="space-y-4">
                            <!-- Visual Banner -->
                            <a href="{{ route('public.activities.detail', $event->resolved_slug) }}" class="block aspect-video rounded-xl overflow-hidden bg-[#0B1120] relative border border-[#334155]/40 group-hover:border-[#df747a]/50 transition-all">
                                @if($event->logo_path)
                                    <img src="{{ asset('storage/' . $event->logo_path) }}" alt="{{ $event->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                @else
                                    <div class="w-full h-full flex flex-col items-center justify-center text-slate-600 bg-gradient-to-br from-[#0B1120] to-[#172033]">
                                        <i data-lucide="terminal" class="w-10 h-10 mb-2 opacity-50"></i>
                                        <span class="text-[11px] font-mono uppercase tracking-wider text-slate-500">COMSOC Activity</span>
                                    </div>
                                @endif
                                <div class="absolute top-3 left-3 flex items-center gap-2">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold uppercase tracking-wider backdrop-blur-md {{ $event->status_badge_class }}">
                                        {{ $event->status_label }}
                                    </span>
                                </div>
                            </a>

                            <!-- Meta info -->
                            <div class="flex items-center gap-4 text-xs text-slate-400 font-mono">
                                <span class="flex items-center gap-1.5">
                                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-[#df747a]"></i>
                                    {{ $event->event_date ? $event->event_date->format('M d, Y') : 'Date TBA' }}
                                </span>
                                @if($event->starts_at)
                                <span class="flex items-center gap-1.5">
                                    <i data-lucide="clock" class="w-3.5 h-3.5 text-slate-500"></i>
                                    {{ $event->starts_at->format('h:i A') }}
                                </span>
                                @endif
                            </div>

                            <!-- Title -->
                            <h2 class="text-xl font-bold text-white font-brand-display group-hover:text-[#df747a] transition-colors leading-snug">
                                <a href="{{ route('public.activities.detail', $event->resolved_slug) }}">
                                    {{ $event->name }}
                                </a>
                            </h2>

                            <!-- Description -->
                            <p class="text-xs text-slate-300 line-clamp-3 leading-relaxed">
                                {{ $event->description ?: 'No detailed public description provided.' }}
                            </p>
                        </div>

                        <!-- Footer Info -->
                        <div class="pt-5 mt-5 border-t border-[#334155]/20 flex items-center justify-between text-xs">
                            <span class="text-slate-400 flex items-center gap-1.5 truncate max-w-[200px]">
                                <i data-lucide="map-pin" class="w-3.5 h-3.5 text-[#df747a] shrink-0"></i>
                                <span class="truncate">{{ $event->effective_venue }}</span>
                            </span>
                            <a href="{{ route('public.activities.detail', $event->resolved_slug) }}" class="font-semibold text-[#df747a] hover:text-white flex items-center gap-1 transition shrink-0">
                                <span>Overview</span>
                                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                            </a>
                        </div>
                    </article>
                    @endforeach
                </div>

                <div class="pt-8">
                    {{ $activities->links() }}
                </div>
            @endif

        </div>
    </section>

</x-public-website-layout>
