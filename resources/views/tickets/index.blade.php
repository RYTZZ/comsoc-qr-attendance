<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-[#560E0F]/30 border border-[#8F3033]/60 text-[#df747a]">
                        Membership Pass
                    </span>
                    <span class="text-xs text-[#94A3B8]">Digital Credentials</span>
                </div>
                <h1 class="text-2xl font-bold text-white font-brand-display tracking-tight mt-1">
                    My Event Tickets
                </h1>
                <p class="text-xs text-[#94A3B8] mt-0.5">
                    View active event access passes, attendance verification tokens, and completed activities.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('events.index') }}" class="btn-secondary text-xs flex items-center gap-1.5">
                    <i data-lucide="compass" class="w-4 h-4"></i>
                    <span>Browse More Events</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-[#0B1120]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if($tickets->isEmpty())
                <div class="card bg-[#172033] border border-[#334155] p-12 text-center">
                    <div class="w-16 h-16 rounded-2xl bg-[#0B1120] border border-[#334155] flex items-center justify-center mx-auto text-[#94A3B8] mb-4">
                        <i data-lucide="ticket" class="w-8 h-8"></i>
                    </div>
                    <h3 class="text-base font-bold text-white font-brand-display">No registered event tickets yet</h3>
                    <p class="text-xs text-[#94A3B8] max-w-sm mx-auto mt-1 mb-6">
                        You have not registered for any Computing Society events or activities. Discover upcoming assemblies, bootcamps, and hackathons.
                    </p>
                    <a href="{{ route('events.index') }}" class="btn-primary text-xs py-2 px-4 inline-flex items-center gap-1.5">
                        <i data-lucide="calendar" class="w-4 h-4"></i>
                        <span>Explore Events</span>
                    </a>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($tickets as $ticket)
                        @php
                            $event = $ticket->event;
                        @endphp
                        <div class="card bg-[#172033] border border-[#334155] shadow-xl flex flex-col justify-between overflow-hidden relative group hover:border-[#8F3033]/60 transition">
                            <div class="h-1.5 bg-gradient-to-r from-[#560E0F] to-amber-500"></div>

                            <div class="p-5 space-y-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-mono font-bold text-amber-400 uppercase tracking-wider">
                                        {{ $ticket->ticket_code }}
                                    </span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $ticket->status === 'approved' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : ($ticket->status === 'pending' ? 'bg-amber-500/20 text-amber-400 border border-amber-500/30' : 'bg-rose-500/20 text-rose-400 border border-rose-500/30') }}">
                                        {{ ucfirst($ticket->status) }}
                                    </span>
                                </div>

                                <div>
                                    <h3 class="text-base font-bold text-white font-brand-display group-hover:text-[#e69094] transition-colors line-clamp-1">
                                        {{ $event ? $event->name : 'Special Event' }}
                                    </h3>
                                    <p class="text-xs text-slate-400 mt-1 flex items-center gap-1.5">
                                        <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-500"></i>
                                        <span>{{ $event ? $event->formatted_schedule_day : 'Schedule TBA' }}</span>
                                    </p>
                                    <p class="text-xs text-slate-400 mt-1 flex items-center gap-1.5">
                                        <i data-lucide="map-pin" class="w-3.5 h-3.5 text-slate-500"></i>
                                        <span class="truncate">{{ $event ? $event->effective_venue : 'Bulan Campus' }}</span>
                                    </p>
                                </div>

                                <div class="p-3 rounded-xl bg-[#0B1120] border border-[#334155] flex items-center justify-between text-xs">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-lg bg-[#560E0F]/20 flex items-center justify-center text-[#df747a]">
                                            <i data-lucide="qr-code" class="w-4 h-4"></i>
                                        </div>
                                        <div>
                                            <span class="block text-[#94A3B8] text-[10px]">Attendee</span>
                                            <span class="font-bold text-white text-xs truncate max-w-[120px] block font-brand-display">{{ $ticket->full_name }}</span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span class="block text-[#94A3B8] text-[10px]">T-Shirt / Size</span>
                                        <span class="font-bold text-slate-200 text-xs font-mono">{{ $ticket->tshirt_size ?: 'N/A' }}</span>
                                    </div>
                                </div>

                                @if($event)
                                <div class="flex items-center justify-between text-[11px] text-[#94A3B8] pt-1">
                                    <span>Reward:</span>
                                    <span class="text-[#df747a] font-semibold font-mono">+{{ $event->reward_xp }} XP • +{{ $event->reward_points }} Pts</span>
                                </div>
                                @endif
                            </div>

                            <div class="p-5 pt-0">
                                <a href="{{ route('tickets.show', $ticket) }}" class="btn-primary w-full text-xs text-center py-2 flex items-center justify-center gap-1.5">
                                    <i data-lucide="ticket" class="w-3.5 h-3.5"></i>
                                    <span>Open Ticket Pass</span>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $tickets->links() }}
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
