<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-[#560E0F]/30 border border-[#560E0F]/60 text-[#dfa6a9]">
                        Computing Society
                    </span>
                    <span class="text-xs text-slate-400">Activities & Assemblies</span>
                </div>
                <h1 class="text-2xl font-bold text-white font-brand-display tracking-tight mt-1">
                    Events & Activities
                </h1>
                <p class="text-xs text-slate-400 mt-0.5">
                    Explore upcoming seminars, competitions, workshops, and verify your participation tickets.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('tickets.index') }}" class="btn-secondary text-xs flex items-center gap-1.5">
                    <i data-lucide="ticket" class="w-4 h-4"></i>
                    <span>My Registered Tickets</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="card bg-[#172033] border border-slate-800 p-4">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-center gap-2">
                        <a href="{{ route('events.index', ['tab' => 'upcoming', 'type' => request('type'), 'search' => request('search')]) }}"
                           class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors {{ $tab !== 'past' ? 'bg-[#560E0F] text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                            Upcoming Events
                        </a>
                        <a href="{{ route('events.index', ['tab' => 'past', 'type' => request('type'), 'search' => request('search')]) }}"
                           class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors {{ $tab === 'past' ? 'bg-[#560E0F] text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                            Event History
                        </a>
                    </div>

                    <form method="GET" action="{{ route('events.index') }}" class="flex flex-wrap items-center gap-2">
                        <input type="hidden" name="tab" value="{{ $tab }}">
                        
                        <div class="relative w-full sm:w-64">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
                                <i data-lucide="search" class="w-3.5 h-3.5"></i>
                            </span>
                            <input type="text" name="search" value="{{ request('search') }}"
                                   class="input-dark pl-9 pr-3 py-1.5 text-xs w-full">
                        </div>

                        <div class="w-full sm:w-48">
                            <x-custom-dropdown
                                name="type"
                                :options="array_merge([['value' => '', 'label' => 'All Categories']], $eventTypes->map(fn($t) => ['value' => $t, 'label' => $t])->toArray())"
                                :selected="request('type', '')"
                                placeholder="All Categories"
                            />
                        </div>

                        <button type="submit" class="btn-secondary text-xs py-1.5 px-3">
                            Filter
                        </button>
                        @if(request('search') || request('type'))
                        <a href="{{ route('events.index', ['tab' => $tab]) }}" class="text-xs text-slate-400 hover:text-white p-1.5">
                            Clear
                        </a>
                        @endif
                    </form>
                </div>
            </div>

            @if($events->isEmpty())
                <div class="card bg-[#172033] border border-slate-800 p-12 text-center">
                    <div class="w-12 h-12 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-center mx-auto text-slate-600 mb-3">
                        <i data-lucide="calendar-x" class="w-6 h-6"></i>
                    </div>
                    <h3 class="text-base font-bold text-white font-brand-display">No events found</h3>
                    <p class="text-xs text-slate-400 max-w-sm mx-auto mt-1">
                        @if($tab === 'past')
                            No concluded event history matches your active filters.
                        @else
                            No upcoming activities scheduled at this time. Check back soon for announcements.
                        @endif
                    </p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($events as $event)
                        @php
                            $isRegistered = isset($userRegistrations[$event->id]);
                            $registration = $isRegistered ? $userRegistrations[$event->id] : null;
                        @endphp
                        <div class="card bg-[#172033] border border-slate-800 shadow-xl flex flex-col justify-between overflow-hidden group hover:border-slate-700 transition">
                            <div>
                                <div class="h-36 bg-gradient-to-br from-[#12141c] via-[#1a141b] to-[#25151c] relative flex items-center justify-center border-b border-slate-800 overflow-hidden">
                                    @if($event->cover_image)
                                        <img src="{{ $event->cover_image }}" alt="{{ $event->name }}" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-16 h-16 rounded-2xl bg-[#560E0F]/20 border border-[#560E0F]/40 flex items-center justify-center text-[#e69094]">
                                            <i data-lucide="calendar" class="w-8 h-8"></i>
                                        </div>
                                    @endif

                                    <div class="absolute top-3 left-3 flex items-center gap-1.5">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-slate-900/90 text-slate-200 border border-slate-700">
                                            {{ $event->event_type ?: 'Activity' }}
                                        </span>
                                    </div>

                                    <div class="absolute top-3 right-3">
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ $event->status_badge_class }}">
                                            {{ $event->status_label }}
                                        </span>
                                    </div>
                                </div>

                                <div class="p-5 space-y-3">
                                    <div class="flex items-center gap-2 text-xs text-slate-400">
                                        <i data-lucide="calendar" class="w-3.5 h-3.5 text-[#dfa6a9]"></i>
                                        <span>{{ $event->formatted_schedule_day }}</span>
                                    </div>

                                    <h3 class="text-base font-bold text-white font-brand-display group-hover:text-[#e69094] transition-colors line-clamp-1">
                                        {{ $event->name }}
                                    </h3>

                                    <p class="text-xs text-slate-400 line-clamp-2 leading-relaxed">
                                        {{ $event->description ?: 'No detailed description provided for this Computing Society activity.' }}
                                    </p>

                                    <div class="pt-3 border-t border-slate-800/80 space-y-2 text-xs">
                                        <div class="flex items-center gap-2 text-slate-400">
                                            <i data-lucide="map-pin" class="w-3.5 h-3.5 text-slate-500 shrink-0"></i>
                                            <span class="truncate">{{ $event->effective_venue }}</span>
                                        </div>

                                        <div class="flex items-center justify-between text-[11px]">
                                            <span class="text-slate-400">Activity Rewards:</span>
                                            <span class="font-bold text-[#dfa6a9]">
                                                +{{ $event->reward_xp }} XP • +{{ $event->reward_points }} Pts
                                            </span>
                                        </div>

                                        @if($event->max_participants)
                                        <div class="flex items-center justify-between text-[11px] text-slate-400">
                                            <span>Capacity:</span>
                                            <span>{{ $event->participant_count }} / {{ $event->max_participants }}</span>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="p-5 pt-0">
                                <div class="flex items-center justify-between gap-3 pt-3 border-t border-slate-800">
                                    @if($isRegistered)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30">
                                            <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i>
                                            <span>Registered</span>
                                        </span>
                                        <a href="{{ route('tickets.show', $registration) }}" class="btn-secondary text-xs py-1.5 px-3">
                                            View Ticket
                                        </a>
                                    @else
                                        <a href="{{ route('events.show', $event) }}" class="btn-primary w-full text-xs text-center py-2">
                                            Event Details & Join
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $events->links() }}
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
