<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <a href="{{ route('tickets.index') }}" class="text-xs text-[#dfa6a9] hover:underline flex items-center gap-1 mb-1">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                    <span>Back to My Tickets</span>
                </a>
                <h1 class="text-2xl font-bold text-white font-brand-display tracking-tight">
                    Event Access Pass
                </h1>
                <p class="text-xs text-slate-400 mt-0.5 font-mono">
                    Ticket Ref: {{ $ticket->ticket_code }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                @if($ticket->qr_token)
                <a href="{{ route('tickets.qr', $ticket) }}" class="btn-secondary text-xs flex items-center gap-1.5">
                    <i data-lucide="download" class="w-4 h-4"></i>
                    <span>Download QR Pass</span>
                </a>
                @endif
                <a href="{{ route('events.show', $event) }}" class="btn-secondary text-xs flex items-center gap-1.5">
                    <i data-lucide="calendar" class="w-4 h-4"></i>
                    <span>Event Details</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-[#0B1120]">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="card bg-gradient-to-br from-[#172033] via-[#0B1120] to-[#172033] border border-[#8F3033]/60 shadow-2xl rounded-3xl overflow-hidden relative">
                <div class="absolute -top-12 -right-12 w-48 h-48 bg-[#560E0F]/20 rounded-full blur-3xl pointer-events-none"></div>

                <div class="p-6 sm:p-8 border-b border-[#334155] bg-[#0B1120]/40 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-[#560E0F] text-white border border-[#8F3033]">
                                {{ $event->event_type ?: 'Official Activity' }}
                            </span>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold {{ $ticket->status === 'approved' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-amber-500/20 text-amber-400 border border-amber-500/30' }}">
                                {{ ucfirst($ticket->status) }} Pass
                            </span>
                        </div>
                        <h2 class="text-xl sm:text-2xl font-extrabold text-white font-brand-display tracking-tight">
                            {{ $event->name }}
                        </h2>
                        <p class="text-xs text-[#94A3B8] mt-1 flex items-center gap-2">
                            <span>{{ $event->formatted_schedule_day }}</span>
                            <span>•</span>
                            <span>{{ $event->effective_venue }}</span>
                        </p>
                    </div>

                    <div class="text-right sm:border-l sm:border-[#334155] sm:pl-6">
                        <span class="block text-[10px] text-[#94A3B8] uppercase tracking-wider font-mono">Ticket Identifier</span>
                        <span class="text-base font-black text-amber-400 font-mono tracking-wider">{{ $ticket->ticket_code }}</span>
                    </div>
                </div>

                <div class="p-6 sm:p-8 grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
                    <div class="flex flex-col items-center text-center p-6 bg-slate-900/80 rounded-2xl border border-slate-800/80">
                        @if($ticket->qr_token)
                            <div class="p-4 bg-white rounded-2xl shadow-xl border-4 border-slate-800 mb-4 inline-block">
                                {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(200)->generate($ticket->qr_token) !!}
                            </div>
                            <span class="text-xs font-bold text-white uppercase tracking-wider">Present at Attendance Terminal</span>
                            <p class="text-[11px] text-slate-400 mt-1 max-w-xs">
                                Kiosk scanners and authorized officers will scan this code to confirm your attendance checkpoint.
                            </p>
                            <a href="{{ route('tickets.qr', $ticket) }}" class="btn-secondary text-xs mt-4 py-1.5 px-3 flex items-center gap-1.5">
                                <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                <span>Save QR Image</span>
                            </a>
                        @else
                            <div class="p-8 text-center text-slate-500">
                                <i data-lucide="clock" class="w-10 h-10 mx-auto mb-2 text-amber-500"></i>
                                <p class="text-xs font-bold text-white">QR Code Pending Approval</p>
                                <p class="text-[11px] text-slate-400 mt-1">Your ticket pass will be activated upon administrator review.</p>
                            </div>
                        @endif
                    </div>

                    <div class="space-y-4 text-xs">
                        <div class="p-4 rounded-xl bg-slate-900/50 border border-slate-800/60 space-y-3">
                            <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider">Attendee Information</h3>
                            <div class="grid grid-cols-2 gap-2 text-slate-400">
                                <div>
                                    <span class="block text-[10px] text-slate-500">Full Name</span>
                                    <span class="font-bold text-white">{{ $ticket->full_name }}</span>
                                </div>
                                <div>
                                    <span class="block text-[10px] text-slate-500">Email Address</span>
                                    <span class="font-bold text-white truncate block">{{ $ticket->email }}</span>
                                </div>
                                <div>
                                    <span class="block text-[10px] text-slate-500">Academic Program</span>
                                    <span class="font-bold text-slate-200 truncate block">{{ $ticket->program ?: 'Computing Society' }}</span>
                                </div>
                                <div>
                                    <span class="block text-[10px] text-slate-500">Year / Block</span>
                                    <span class="font-bold text-slate-200">{{ $ticket->year_level ?: 'Member' }}</span>
                                </div>
                                <div>
                                    <span class="block text-[10px] text-slate-500">T-Shirt Size</span>
                                    <span class="font-bold text-slate-200">{{ $ticket->tshirt_size ?: 'Standard' }}</span>
                                </div>
                                <div>
                                    <span class="block text-[10px] text-slate-500">Registered On</span>
                                    <span class="font-bold text-slate-200">{{ $ticket->created_at->format('M d, Y') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 rounded-xl bg-slate-900/50 border border-slate-800/60 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400 text-xs">Activity Attendance Rewards</span>
                                <span class="text-xs font-bold text-amber-300">+{{ $event->reward_xp }} XP • +{{ $event->reward_points }} Pts</span>
                            </div>
                            <p class="text-[11px] text-slate-500">
                                Your account balances and season progression update automatically upon verified check-in.
                            </p>
                        </div>
                    </div>
                </div>

                @if($attendanceRecords->isNotEmpty())
                <div class="p-6 sm:p-8 border-t border-slate-800/80 bg-slate-900/60 space-y-3">
                    <div class="flex items-center gap-2">
                        <i data-lucide="check-check" class="w-4 h-4 text-emerald-400"></i>
                        <h3 class="text-xs font-bold text-white uppercase tracking-wider">Attendance Verification Log</h3>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach($attendanceRecords as $record)
                        <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-between text-xs">
                            <div>
                                <p class="font-bold text-white">{{ $record->attendanceSession ? $record->attendanceSession->name : 'Session Check-In' }}</p>
                                <p class="text-[10px] text-slate-500">{{ $record->scanned_at ? $record->scanned_at->format('M d, Y • g:i A') : 'Recorded' }}</p>
                            </div>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                {{ ucfirst($record->status) }}
                            </span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
