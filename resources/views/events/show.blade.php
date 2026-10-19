<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <a href="{{ route('events.index') }}" class="text-xs text-[#dfa6a9] hover:underline flex items-center gap-1 mb-1">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                    <span>Back to Events & Activities</span>
                </a>
                <h1 class="text-2xl font-bold text-white font-brand-display tracking-tight">
                    {{ $event->name }}
                </h1>
                <p class="text-xs text-slate-400 mt-0.5">
                    {{ $event->event_type ?: 'Activity' }} • Organized by {{ $event->organizer ?: 'Computing Society' }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                @if($registration)
                    <a href="{{ route('tickets.show', $registration) }}" class="btn-primary text-xs flex items-center gap-1.5">
                        <i data-lucide="ticket" class="w-4 h-4"></i>
                        <span>View My Ticket</span>
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <div class="lg:col-span-2 space-y-6">

                    <div class="card bg-[#172033] border border-slate-800 shadow-xl overflow-hidden">
                        <div class="h-64 sm:h-80 bg-gradient-to-br from-[#12141c] via-[#1a141b] to-[#25151c] relative flex items-center justify-center border-b border-slate-800">
                            @if($event->cover_image)
                                <img src="{{ $event->cover_image }}" alt="{{ $event->name }}" class="w-full h-full object-cover">
                            @else
                                <div class="text-center p-6">
                                    <div class="w-20 h-20 rounded-2xl bg-[#560E0F]/20 border border-[#560E0F]/40 flex items-center justify-center text-[#e69094] mx-auto mb-3">
                                        <i data-lucide="calendar" class="w-10 h-10"></i>
                                    </div>
                                    <p class="text-xs text-slate-400 font-mono uppercase tracking-wider">Computing Society Official Event</p>
                                </div>
                            @endif

                            <div class="absolute top-4 left-4 flex items-center gap-2">
                                <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-slate-900/90 text-white border border-slate-700">
                                    {{ $event->event_type ?: 'Activity' }}
                                </span>
                            </div>

                            <div class="absolute top-4 right-4">
                                <span class="px-3 py-1 rounded-full text-xs font-bold border {{ $event->status_badge_class }}">
                                    {{ $event->status_label }}
                                </span>
                            </div>
                        </div>

                        <div class="p-6 space-y-6">
                            <div>
                                <h2 class="text-lg font-bold text-white font-brand-display mb-2">Event Overview</h2>
                                <div class="text-sm text-slate-300 leading-relaxed space-y-3 whitespace-pre-line">
                                    {{ $event->description ?: 'No detailed narrative provided for this event.' }}
                                </div>
                            </div>

                            @if($event->participation_requirements)
                            <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800">
                                <div class="flex items-center gap-2 mb-2 text-[#e69094]">
                                    <i data-lucide="info" class="w-4 h-4"></i>
                                    <h3 class="text-xs font-bold uppercase tracking-wider">Participation Requirements</h3>
                                </div>
                                <p class="text-xs text-slate-300 whitespace-pre-line">{{ $event->participation_requirements }}</p>
                            </div>
                            @endif

                            <div>
                                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Attendance Checkpoint Sessions</h3>
                                @if($activeSessions->isEmpty())
                                    <p class="text-xs text-slate-500">No scheduled checkpoint sessions configured for this event.</p>
                                @else
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        @foreach($activeSessions as $ses)
                                        <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800/80">
                                            <div class="flex items-center justify-between mb-1">
                                                <h4 class="text-xs font-bold text-white">{{ $ses->name }}</h4>
                                                <span class="text-[10px] uppercase font-bold text-slate-400 font-mono">{{ $ses->type }}</span>
                                            </div>
                                            <p class="text-[11px] text-slate-400">
                                                Opens: {{ $ses->opens_at ? \Carbon\Carbon::parse($ses->opens_at)->format('g:i A') : 'TBA' }}
                                                • Closes: {{ $ses->closes_at ? \Carbon\Carbon::parse($ses->closes_at)->format('g:i A') : 'TBA' }}
                                            </p>
                                        </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            @if($attendanceRecords->isNotEmpty())
                            <div>
                                <h3 class="text-xs font-bold text-emerald-400 uppercase tracking-wider mb-3">Your Verified Attendance Records</h3>
                                <div class="space-y-2">
                                    @foreach($attendanceRecords as $att)
                                    <div class="p-3 rounded-xl bg-slate-900/40 border border-slate-800 flex items-center justify-between text-xs">
                                        <div>
                                            <p class="font-bold text-white">{{ $att->attendanceSession ? $att->attendanceSession->name : 'Session Check-In' }}</p>
                                            <p class="text-[11px] text-slate-400">Verified by {{ $att->scannedByUser ? $att->scannedByUser->name : 'Terminal' }}</p>
                                        </div>
                                        <div class="text-right">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                                {{ ucfirst($att->status) }}
                                            </span>
                                            <p class="text-[10px] text-slate-500 mt-0.5">{{ $att->scanned_at ? $att->scanned_at->format('M d, g:i A') : '' }}</p>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif

                        </div>
                    </div>

                </div>

                <div class="space-y-6">

                    <div class="card bg-[#172033] border border-slate-800 shadow-xl p-6 space-y-5">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Registration Status</span>
                            <span class="px-2 py-0.5 rounded text-xs font-bold {{ $event->isRegistrationOpen() ? 'bg-emerald-500/20 text-emerald-400' : 'bg-rose-500/20 text-rose-400' }}">
                                {{ $event->registration_status }}
                            </span>
                        </div>

                        <div class="space-y-3 text-xs">
                            <div class="flex items-start gap-3">
                                <i data-lucide="calendar" class="w-4 h-4 text-[#e69094] shrink-0 mt-0.5"></i>
                                <div>
                                    <span class="block text-slate-400">Date & Schedule</span>
                                    <span class="font-bold text-white">{{ $event->formatted_schedule_day }}</span>
                                    @if($event->starts_at)
                                        <span class="block text-slate-400 text-[11px]">
                                            {{ $event->starts_at->format('g:i A') }} @if($event->ends_at) - {{ $event->ends_at->format('g:i A') }} @endif
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <i data-lucide="map-pin" class="w-4 h-4 text-[#e69094] shrink-0 mt-0.5"></i>
                                <div>
                                    <span class="block text-slate-400">Venue / Location</span>
                                    <span class="font-bold text-white">{{ $event->effective_venue }}</span>
                                    @if($event->venue_details)
                                        <span class="block text-slate-400 text-[11px]">{{ $event->venue_details }}</span>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <i data-lucide="award" class="w-4 h-4 text-amber-400 shrink-0 mt-0.5"></i>
                                <div>
                                    <span class="block text-slate-400">Member Rewards</span>
                                    <span class="font-bold text-amber-300">
                                        +{{ $event->reward_xp }} XP • +{{ $event->reward_points }} Points
                                    </span>
                                    <span class="block text-slate-500 text-[11px]">Granted upon verified attendance</span>
                                </div>
                            </div>

                            @if($event->max_participants)
                            <div class="flex items-start gap-3">
                                <i data-lucide="users" class="w-4 h-4 text-indigo-400 shrink-0 mt-0.5"></i>
                                <div>
                                    <span class="block text-slate-400">Event Capacity</span>
                                    <span class="font-bold text-white">{{ $event->participant_count }} / {{ $event->max_participants }} registered</span>
                                </div>
                            </div>
                            @endif

                            @if($event->registration_deadline)
                            <div class="flex items-start gap-3">
                                <i data-lucide="clock" class="w-4 h-4 text-slate-400 shrink-0 mt-0.5"></i>
                                <div>
                                    <span class="block text-slate-400">Registration Deadline</span>
                                    <span class="font-bold text-white">{{ $event->registration_deadline->format('M d, Y • g:i A') }}</span>
                                </div>
                            </div>
                            @endif
                        </div>

                        <div class="pt-4 border-t border-slate-800">
                            @if($registration)
                                @if($registration->status === 'approved')
                                    <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-xl p-4 text-center space-y-2">
                                        <div class="flex items-center justify-center gap-1.5 text-emerald-400 font-bold text-xs">
                                            <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                                            <span>You are Registered!</span>
                                        </div>
                                        <p class="text-[11px] text-slate-400">Your Dynamic Attendance QR Pass is active and authorized for this event.</p>
                                        <div class="space-y-2 pt-1">
                                            @if($event->evaluation_template_id || !empty($event->evaluation_snapshot))
                                                <a href="{{ route('events.evaluation.show', $event) }}" class="px-3 py-2 rounded-xl bg-amber-500/20 hover:bg-amber-500/30 border border-amber-500/40 text-amber-300 text-xs font-bold transition flex items-center justify-center gap-1.5 w-full">
                                                    <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                                                    <span>Complete Mandatory Evaluation</span>
                                                </a>
                                            @endif
                                            <a href="{{ route('tickets.show', $registration) }}" class="btn-primary w-full text-xs text-center py-2 block">
                                                Open Event Dynamic QR Pass
                                            </a>
                                        </div>
                                    </div>
                                @elseif($registration->status === 'pending')
                                    <div class="bg-amber-500/10 border border-amber-500/30 rounded-xl p-4 text-center space-y-2">
                                        <div class="flex items-center justify-center gap-1.5 text-amber-400 font-bold text-xs">
                                            <i data-lucide="clock" class="w-4 h-4"></i>
                                            <span>Registration Pending Review</span>
                                        </div>
                                        <p class="text-[11px] text-slate-400">Your registration is awaiting approval. Your Dynamic Attendance QR code will activate upon approval.</p>
                                        <a href="{{ route('tickets.show', $registration) }}" class="btn-secondary w-full text-xs text-center py-2 block">
                                            View Registration Details
                                        </a>
                                    </div>
                                @else
                                    <div class="bg-rose-500/10 border border-rose-500/30 rounded-xl p-4 text-center space-y-2">
                                        <div class="flex items-center justify-center gap-1.5 text-rose-400 font-bold text-xs">
                                            <i data-lucide="alert-circle" class="w-4 h-4"></i>
                                            <span>Registration Cancelled or Ineligible</span>
                                        </div>
                                        <p class="text-[11px] text-slate-400">Attendance QR Pass is not active. Your registration was cancelled or revoked.</p>
                                        <a href="{{ route('tickets.show', $registration) }}" class="btn-secondary w-full text-xs text-center py-2 block">
                                            View Status
                                        </a>
                                    </div>
                                @endif
                            @elseif($event->isRegistrationOpen())
                                <div class="mb-4 p-3 rounded-xl bg-[#560E0F]/15 border border-[#8F3033]/40 text-left">
                                    <div class="flex items-start gap-2.5">
                                        <i data-lucide="qr-code" class="w-4 h-4 text-[#df747a] shrink-0 mt-0.5"></i>
                                        <div>
                                            <p class="text-xs font-bold text-white">Dynamic Attendance QR Pass Required</p>
                                            <p class="text-[11px] text-[#94A3B8] mt-0.5">
                                                Join this event to access your Dynamic Attendance QR Code for check-in and rewards.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <form method="POST" action="{{ route('events.join', $event) }}" class="space-y-4">
                                    @csrf

                                    @if($event->tshirt_enabled)
                                        <div>
                                            <label class="block text-xs font-semibold text-slate-300 mb-1 flex items-center justify-between">
                                                <span>T-Shirt Size Preference</span>
                                                @if($event->tshirt_required)
                                                    <span class="text-[10px] text-rose-400 font-bold uppercase tracking-wider">Required</span>
                                                @else
                                                    <span class="text-[10px] text-slate-400 font-normal">Optional</span>
                                                @endif
                                            </label>
                                            <x-custom-dropdown
                                                name="tshirt_size"
                                                :options="array_merge(
                                                    $event->tshirt_required ? [] : [['value' => '', 'label' => 'None / Not Applicable']],
                                                    array_map(fn($s) => ['value' => $s, 'label' => 'Size ' . $s], \App\Models\EventRegistration::TSHIRT_SIZES)
                                                )"
                                                :selected="old('tshirt_size', $event->tshirt_required ? 'L' : '')"
                                                placeholder="Select T-Shirt Size"
                                            />
                                            @error('tshirt_size')
                                                <p class="text-[11px] text-rose-400 mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    @endif

                                    @if($event->dietary_enabled)
                                        <div>
                                            <label class="block text-xs font-semibold text-slate-300 mb-1 flex items-center justify-between">
                                                <span>Dietary Restrictions</span>
                                                @if($event->dietary_required)
                                                    <span class="text-[10px] text-rose-400 font-bold uppercase tracking-wider">Required</span>
                                                @else
                                                    <span class="text-[10px] text-slate-400 font-normal">Optional</span>
                                                @endif
                                            </label>
                                            <x-custom-dropdown
                                                name="food_restrictions"
                                                :options="array_merge(
                                                    $event->dietary_required ? [] : [['value' => '', 'label' => 'None / Not Specified']],
                                                    array_map(fn($f) => ['value' => $f, 'label' => $f], \App\Models\EventRegistration::FOOD_RESTRICTIONS)
                                                )"
                                                :selected="old('food_restrictions', $event->dietary_required ? 'None' : '')"
                                                placeholder="Select Restriction"
                                            />
                                            @error('food_restrictions')
                                                <p class="text-[11px] text-rose-400 mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    @endif

                                    <button type="submit" class="btn-primary w-full text-xs py-2.5 font-bold shadow-lg shadow-[#560E0F]/20 flex items-center justify-center gap-2">
                                        <i data-lucide="ticket" class="w-4 h-4"></i>
                                        <span>Register for Event</span>
                                    </button>
                                </form>
                            @else
                                <div class="bg-slate-900/80 border border-slate-800 rounded-xl p-4 text-center">
                                    <p class="text-xs font-bold text-slate-400">Registration Unavailable</p>
                                    <p class="text-[11px] text-slate-500 mt-1">This event is either full, past its deadline, or not currently open for registrations.</p>
                                </div>
                            @endif
                        </div>

                    </div>

                </div>

            </div>

        </div>
    </div>
</x-app-layout>
