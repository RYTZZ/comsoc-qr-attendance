<x-public-website-layout :settings="$settings" :title="'Activities & Events'">

    <section class="py-12 sm:py-20 border-b border-[#334155]/60 bg-[#0B1120]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl mx-auto text-center space-y-4">
                <span class="text-xs font-bold text-[#df747a] tracking-widest uppercase font-brand-accent">Activities & Projects</span>
                <h1 class="text-3xl sm:text-5xl font-extrabold text-white font-brand-display tracking-tight">
                    COMSOC Activities & Events
                </h1>
                <p class="text-base text-slate-300 leading-relaxed">
                    Explore our workshops, tech conferences, hackathons, and community development initiatives.
                </p>
            </div>
        </div>
    </section>

    <section class="py-12 sm:py-20 border-b border-[#334155]/60">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            <!-- Filter Controls -->
            <div class="flex items-center justify-center gap-2">
                <a href="{{ route('public.activities', ['status' => 'all']) }}"
                   class="px-4 py-2 rounded-xl text-xs font-semibold transition-all {{ $filter === 'all' ? 'bg-[#560E0F] text-white shadow-lg' : 'bg-[#172033] text-slate-300 border border-[#334155] hover:text-white' }}">
                    All Activities
                </a>
                <a href="{{ route('public.activities', ['status' => 'upcoming']) }}"
                   class="px-4 py-2 rounded-xl text-xs font-semibold transition-all {{ $filter === 'upcoming' ? 'bg-[#560E0F] text-white shadow-lg' : 'bg-[#172033] text-slate-300 border border-[#334155] hover:text-white' }}">
                    Upcoming / Ongoing
                </a>
                <a href="{{ route('public.activities', ['status' => 'completed']) }}"
                   class="px-4 py-2 rounded-xl text-xs font-semibold transition-all {{ $filter === 'completed' ? 'bg-[#560E0F] text-white shadow-lg' : 'bg-[#172033] text-slate-300 border border-[#334155] hover:text-white' }}">
                    Completed
                </a>
            </div>

            @if($activities->isEmpty())
                <div class="card bg-[#172033] border border-[#334155] p-12 text-center rounded-2xl text-slate-400 text-sm max-w-xl mx-auto">
                    No activities found matching the selected filter.
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($activities as $event)
                    <div class="card bg-[#172033] border border-[#334155] rounded-2xl overflow-hidden hover:border-[#df747a] transition-all flex flex-col justify-between">
                        <div>
                            @if($event->logo_path)
                                <img src="{{ asset('storage/' . $event->logo_path) }}" alt="{{ $event->name }}" class="w-full h-48 object-cover">
                            @else
                                <div class="w-full h-48 bg-gradient-to-r from-[#0B1120] to-[#172033] flex items-center justify-center border-b border-[#334155]">
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

                                <p class="text-xs text-slate-300 line-clamp-3 leading-relaxed">
                                    {{ $event->description ?: 'No detailed public description provided.' }}
                                </p>
                            </div>
                        </div>

                        <div class="p-6 pt-0 border-t border-[#334155]/60 mt-4 flex items-center justify-between">
                            <span class="text-[11px] text-slate-400 flex items-center gap-1 truncate max-w-[160px]">
                                <i data-lucide="map-pin" class="w-3.5 h-3.5 text-[#df747a]"></i>
                                <span>{{ $event->effective_venue }}</span>
                            </span>
                            <a href="{{ route('public.activities.detail', $event->resolved_slug) }}" class="text-xs font-semibold text-[#df747a] hover:text-white flex items-center gap-1 transition">
                                <span>View Details</span>
                                <i data-lucide="chevron-right" class="w-4 h-4"></i>
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="pt-6">
                    {{ $activities->links() }}
                </div>
            @endif

        </div>
    </section>

</x-public-website-layout>
