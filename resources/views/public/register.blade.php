<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $event ? 'Register for ' . $event->name : 'Non-Student Registration' }} — Computing Society</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Michroma&family=Sora:wght@600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#0f1117] text-slate-100 min-h-screen flex flex-col font-sans antialiased selection:bg-[#7A1618] selection:text-white relative overflow-x-hidden">
    <x-public-auth-bg />

    <div class="relative overflow-hidden flex-1 flex flex-col items-center justify-center p-4 py-10 z-10">
        <div class="w-full max-w-xl relative z-10">
            <div class="text-center mb-6">
                <div class="w-20 h-20 mx-auto mb-3 flex items-center justify-center p-2 rounded-2xl bg-[#171a23]/90 border border-slate-800 shadow-xl enter-logo backdrop-blur-sm">
                    <img src="{{ asset('images/COMSOC.png') }}" alt="Computing Society Logo" class="w-full h-full object-contain">
                </div>
                <div class="enter-header">
                    <p class="font-brand-accent text-[11px] text-[#dfa6a9] tracking-widest uppercase mb-1">COMPUTING SOCIETY</p>
                    <h1 class="font-brand-display text-2xl sm:text-3xl font-bold tracking-tight text-white">Event Registration</h1>
                    <p class="text-slate-400 text-xs sm:text-sm mt-0.5">Official Non-Student Participant Gateway</p>
                </div>
            </div>

            @if(!$event)
                <div class="bg-[#171a23]/95 border border-slate-800/80 rounded-2xl shadow-xl p-8 text-center enter-card backdrop-blur-md">
                    <div class="w-12 h-12 mx-auto rounded-full bg-slate-800/80 flex items-center justify-center text-slate-400 mb-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold text-white">No Events Available</h3>
                    <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                        There are currently no events open for non-student participant registration.
                    </p>
                </div>
            @else
                <div class="bg-[#171a23]/95 border border-slate-800/80 rounded-2xl shadow-2xl p-5 sm:p-8 enter-card backdrop-blur-md">
                    @if(isset($events) && $events->count() > 1)
                        <div class="mb-6 pb-5 border-b border-slate-800">
                            <label class="block text-xs font-medium text-slate-300 mb-1.5 font-brand-display">
                                Select Event <span class="text-red-400">*</span>
                            </label>
                            @php
                                $eventDropdownOptions = [];
                                foreach ($events as $ev) {
                                    $eventDropdownOptions[] = [
                                        'value' => (string) $ev->id,
                                        'label' => $ev->name . ' (' . ($ev->event_date ? $ev->event_date->format('M d, Y') : 'Date TBA') . ')'
                                    ];
                                }
                            @endphp
                            <div @dropdown-selected.window="if ($event.detail.name === 'event_selection') { window.location.href = '{{ route('public.register') }}?event_id=' + $event.detail.value; }">
                                <x-custom-dropdown name="event_selection"
                                                   :options="$eventDropdownOptions"
                                                   :value="$event->id"
                                                   placeholder="Select an Event" />
                            </div>
                        </div>
                    @endif

                    <div class="border-b border-slate-800 pb-5 mb-6">
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                            <div class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-[#7A1618]/15 text-[#dfa6a9] border border-[#7A1618]/30">
                                Participant Registration
                            </div>

                            @if($registrationStatus === 'Registration Open')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                    Registration Open
                                </span>
                            @elseif($registrationStatus === 'Closing Soon')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                                    Closing Soon
                                </span>
                            @elseif($registrationStatus === 'Registration Full')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-500/10 text-red-400 border border-red-500/20">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span>
                                    Registration Full
                                </span>
                            @elseif($registrationStatus === 'Registration Closed')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-500/10 text-red-400 border border-red-500/20">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span>
                                    Registration Closed
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-500/10 text-slate-400 border border-slate-500/20">
                                    Registration Not Open
                                </span>
                            @endif
                        </div>

                        <h2 class="text-xl sm:text-2xl font-bold text-white font-brand-display">{{ $event->name }}</h2>

                        <div class="mt-2.5 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs text-slate-300">
                            <span class="flex items-center gap-1.5 font-medium text-white">
                                <svg class="w-4 h-4 text-[#dfa6a9] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                {{ $event->formatted_schedule_day }}
                            </span>
                            @if($event->starts_at)
                                <span class="flex items-center gap-1.5 text-slate-400">
                                     <svg class="w-4 h-4 text-slate-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                     </svg>
                                     {{ $event->starts_at->format('g:i A') }}@if($event->ends_at && $event->ends_at->format('g:i A') !== $event->starts_at->format('g:i A')) — {{ $event->ends_at->format('g:i A') }}@endif
                                 </span>
                            @endif
                            @if($event->effective_venue)
                                <span class="flex items-center gap-1.5 text-slate-300">
                                     <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                     </svg>
                                     {{ $event->effective_venue }}
                                 </span>
                            @endif
                        </div>

                        @if($event->venue_address || $event->venue_details)
                            <div class="mt-2.5 p-2.5 rounded-xl bg-slate-950/60 border border-slate-800/80 text-xs text-slate-400 space-y-1">
                                @if($event->venue_address)
                                     <p><span class="text-slate-500 font-medium">Address:</span> {{ $event->venue_address }}</p>
                                @endif
                                @if($event->venue_details)
                                     <p><span class="text-slate-500 font-medium">Venue Details:</span> {{ $event->venue_details }}</p>
                                @endif
                            </div>
                        @endif

                        @if($event->max_participants)
                            <div class="mt-3 flex items-center justify-between p-2.5 rounded-xl bg-slate-950/60 border border-slate-800 text-xs">
                                <span class="text-slate-400 font-medium">Participant Limit</span>
                                <span class="font-mono {{ $event->isFull() ? 'text-red-400 font-bold' : 'text-slate-200' }}">
                                    Registered: {{ $event->participant_count }} / {{ $event->max_participants }}
                                </span>
                            </div>
                        @endif

                        @if($isOpen && $deadline && now()->lt($deadline))
                            <div class="mt-4 rounded-xl p-3 sm:p-4 bg-slate-950/70 border border-slate-800"
                                 x-data="registrationCountdown('{{ $deadline->toIso8601String() }}')">
                                <div class="flex items-center justify-between gap-2 mb-1.5">
                                    <span class="text-[11px] font-medium uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Time Left to Register
                                    </span>
                                    <span class="text-[11px] text-slate-500" x-text="'Deadline: ' + formattedDeadline"></span>
                                </div>

                                <template x-if="!isExpired">
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 bg-slate-900 border border-slate-800 rounded-lg p-2 text-center">
                                            <span class="block text-lg sm:text-xl font-bold font-mono text-white" x-text="days"></span>
                                            <span class="block text-[10px] uppercase tracking-wider text-slate-500">Days</span>
                                        </div>
                                        <div class="flex-1 bg-slate-900 border border-slate-800 rounded-lg p-2 text-center">
                                            <span class="block text-lg sm:text-xl font-bold font-mono text-white" x-text="hours"></span>
                                            <span class="block text-[10px] uppercase tracking-wider text-slate-500">Hours</span>
                                        </div>
                                        <div class="flex-1 bg-slate-900 border border-slate-800 rounded-lg p-2 text-center">
                                            <span class="block text-lg sm:text-xl font-bold font-mono text-white" x-text="minutes"></span>
                                            <span class="block text-[10px] uppercase tracking-wider text-slate-500">Minutes</span>
                                        </div>
                                        <div class="flex-1 bg-slate-900 border border-slate-800 rounded-lg p-2 text-center">
                                            <span class="block text-lg sm:text-xl font-bold font-mono text-brand-400" x-text="seconds"></span>
                                            <span class="block text-[10px] uppercase tracking-wider text-slate-500">Seconds</span>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="isExpired">
                                    <div class="p-2.5 rounded-lg bg-red-500/10 border border-red-500/20 text-center">
                                        <span class="text-xs font-semibold text-red-400">Registration Expired</span>
                                    </div>
                                </template>
                            </div>
                        @endif

                        @if($event->description)
                            <p class="text-xs text-slate-300 mt-3 leading-relaxed">{{ $event->description }}</p>
                        @endif
                    </div>

                    @if(session('error'))
                        <div class="mb-5 p-3.5 rounded-xl bg-red-500/10 border border-red-500/30 text-red-300 text-xs flex items-center gap-2.5">
                            <svg class="w-5 h-5 shrink-0 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>{{ session('error') }}</span>
                        </div>
                    @endif

                    @if(!$isOpen)
                        <div class="py-8 text-center bg-slate-950/50 rounded-xl border border-slate-800">
                            <div class="w-12 h-12 mx-auto rounded-full bg-slate-800/80 flex items-center justify-center text-slate-400 mb-3">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <h3 class="text-base font-semibold text-white">
                                {{ $registrationStatus === 'Registration Full' ? 'Registration Full' : 'Registration Closed' }}
                            </h3>
                            <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto px-4">
                                @if($registrationStatus === 'Registration Full')
                                    Registration capacity has been reached for this event.
                                @elseif($registrationStatus === 'Registration Not Open')
                                    Registration for this event has not opened yet.
                                @else
                                    Registration for this event is currently closed or the deadline has passed.
                                @endif
                            </p>
                        </div>
                    @else
                        <form method="POST"
                              action="{{ route('public.register.store', $event) }}"
                              x-data="{
                                  organization: '{{ old('organization', '') }}',
                                  foodRestriction: '{{ old('food_restrictions', 'None') }}',
                                  confirmed: {{ old('confirmed') ? 'true' : 'false' }},
                                  isSubmitting: false
                              }"
                              @dropdown-selected.window="
                                  if ($event.detail.name === 'organization') { organization = $event.detail.value; }
                                  if ($event.detail.name === 'food_restrictions') { foodRestriction = $event.detail.value; }
                              "
                              @submit="if(!confirmed) { $event.preventDefault(); return; } isSubmitting = true;"
                              class="space-y-6">
                            @csrf
                            <input type="hidden" name="event_id" value="{{ $event->id }}">

                            <div class="space-y-3">
                                <div class="flex items-center gap-2 pb-1 border-b border-slate-800">
                                    <span class="w-5 h-5 rounded-full bg-brand-500/10 text-brand-400 text-xs font-bold flex items-center justify-center">1</span>
                                    <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-300">Personal Information</h3>
                                </div>

                                <div>
                                    <label for="full_name" class="block text-xs font-medium text-slate-300 mb-1">Full Name <span class="text-red-400">*</span></label>
                                    <input id="full_name" name="full_name" type="text" value="{{ old('full_name') }}" required autofocus
                                           class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent transition"
                                           placeholder="e.g., Juan Dela Cruz" />
                                    @error('full_name')
                                        <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="email" class="block text-xs font-medium text-slate-300 mb-1">Email Address <span class="text-red-400">*</span></label>
                                    <input id="email" name="email" type="email" value="{{ old('email') }}" required
                                           class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent transition"
                                           placeholder="e.g., juan@example.com" />
                                    @error('email')
                                        <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="organization" class="block text-xs font-medium text-slate-300 mb-1">School / University <span class="text-red-400">*</span></label>
                                    @php
                                        $orgOptions = $organizations->pluck('name')->toArray();
                                        $orgOptions[] = 'Other';
                                    @endphp
                                    <x-custom-dropdown name="organization"
                                                       :options="$orgOptions"
                                                       :value="old('organization')"
                                                       placeholder="Select your school or university"
                                                       :required="true" />
                                    @error('organization')
                                        <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div x-show="organization === 'Other'" x-cloak class="transition-all">
                                    <label for="custom_organization" class="block text-xs font-medium text-slate-300 mb-1">
                                        School / University Name <span class="text-red-400">*</span>
                                    </label>
                                    <input id="custom_organization" name="custom_organization" type="text"
                                           value="{{ old('custom_organization') }}"
                                           :required="organization === 'Other'"
                                           class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent transition"
                                           placeholder="Enter your official school / university name" />
                                    @error('custom_organization')
                                        <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="space-y-3">
                                <div class="flex items-center gap-2 pb-1 border-b border-slate-800">
                                    <span class="w-5 h-5 rounded-full bg-brand-500/10 text-brand-400 text-xs font-bold flex items-center justify-center">2</span>
                                    <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-300">Participant Information</h3>
                                </div>

                                <div>
                                    <label for="program" class="block text-xs font-medium text-slate-300 mb-1">Program / Course <span class="text-red-400">*</span></label>
                                    <x-custom-dropdown name="program"
                                                       :options="$programs"
                                                       :value="old('program')"
                                                       placeholder="Select Program"
                                                       :required="true" />
                                    @error('program')
                                        <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label for="year_level" class="block text-xs font-medium text-slate-300 mb-1">Year Level <span class="text-red-400">*</span></label>
                                        <x-custom-dropdown name="year_level"
                                                           :options="$yearLevels"
                                                           :value="old('year_level')"
                                                           placeholder="Select Year Level"
                                                           :required="true" />
                                        @error('year_level')
                                            <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="tshirt_size" class="block text-xs font-medium text-slate-300 mb-1">T-Shirt Size <span class="text-red-400">*</span></label>
                                        <x-custom-dropdown name="tshirt_size"
                                                           :options="$tshirtSizes"
                                                           :value="old('tshirt_size')"
                                                           placeholder="Select Size"
                                                           :required="true" />
                                        @error('tshirt_size')
                                            <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <div class="flex items-center gap-2 pb-1 border-b border-slate-800">
                                    <span class="w-5 h-5 rounded-full bg-brand-500/10 text-brand-400 text-xs font-bold flex items-center justify-center">3</span>
                                    <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-300">Food Information</h3>
                                </div>

                                <div>
                                    <label for="food_restrictions" class="block text-xs font-medium text-slate-300 mb-1">Food Restrictions <span class="text-red-400">*</span></label>
                                    <x-custom-dropdown name="food_restrictions"
                                                       :options="$foodRestrictions"
                                                       :value="old('food_restrictions', 'None')"
                                                       placeholder="Select Food Restrictions"
                                                       :required="true" />
                                    @error('food_restrictions')
                                        <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div x-show="foodRestriction === 'Allergies' || foodRestriction === 'Other'" x-cloak class="transition-all">
                                    <label for="food_restriction_details" class="block text-xs font-medium text-slate-300 mb-1">
                                        Food Restriction Details <span class="text-red-400">*</span>
                                    </label>
                                    <textarea id="food_restriction_details" name="food_restriction_details" rows="2"
                                              :required="foodRestriction === 'Allergies' || foodRestriction === 'Other'"
                                              class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent transition"
                                              placeholder="Please specify your dietary restrictions or known food allergies...">{{ old('food_restriction_details') }}</textarea>
                                    @error('food_restriction_details')
                                        <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="space-y-3 pt-2">
                                <div class="flex items-center gap-2 pb-1 border-b border-slate-800">
                                    <span class="w-5 h-5 rounded-full bg-brand-500/10 text-brand-400 text-xs font-bold flex items-center justify-center">4</span>
                                    <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-300">Confirmation</h3>
                                </div>

                                <label class="flex items-start gap-3 p-3 rounded-xl bg-slate-950 border border-slate-800 hover:border-slate-700 cursor-pointer transition">
                                    <input type="checkbox" name="confirmed" value="1"
                                           x-model="confirmed"
                                           required
                                           class="mt-1 h-4 w-4 rounded border-slate-700 text-brand-500 focus:ring-brand-400 focus:ring-offset-slate-950 bg-slate-900" />
                                    <span class="text-xs text-slate-300 leading-relaxed">
                                        I confirm that the information I provided is accurate and that I agree to participate in this event. <span class="text-red-400">*</span>
                                    </span>
                                </label>
                                @error('confirmed')
                                    <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="pt-2">
                                <button type="submit"
                                        :disabled="!confirmed || isSubmitting"
                                        class="w-full py-3.5 px-4 rounded-xl font-semibold text-sm text-white bg-[#7A1618] hover:bg-[#8e1b1d] disabled:opacity-50 disabled:cursor-not-allowed active:scale-[0.99] transition shadow-md focus:outline-none focus:ring-2 focus:ring-[#7A1618] focus:ring-offset-2 focus:ring-offset-[#171a23]">
                                    <span x-show="!isSubmitting">Submit Registration</span>
                                    <span x-show="isSubmitting" x-cloak>Submitting Registration...</span>
                                </button>
                            </div>
                        </form>
                    @endif

                    <p class="text-[11px] text-slate-500 text-center mt-5">
                        Upon approval by an administrator, your physical event QR pass will be prepared by the ComSoc team.
                    </p>
                </div>
            @endif
        </div>
    </div>

    <script>
        function registrationCountdown(deadlineIso) {
            return {
                deadline: new Date(deadlineIso),
                days: '00',
                hours: '00',
                minutes: '00',
                seconds: '00',
                isExpired: false,
                formattedDeadline: '',
                timer: null,
                init() {
                    try {
                        this.formattedDeadline = new Intl.DateTimeFormat(undefined, {
                            month: 'short',
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        }).format(this.deadline);
                    } catch (e) {
                        this.formattedDeadline = deadlineIso;
                    }

                    this.update();
                    this.timer = setInterval(() => this.update(), 1000);
                },
                update() {
                    const now = new Date();
                    const diff = this.deadline - now;

                    if (diff <= 0) {
                        this.isExpired = true;
                        this.days = '00';
                        this.hours = '00';
                        this.minutes = '00';
                        this.seconds = '00';
                        if (this.timer) clearInterval(this.timer);
                        return;
                    }

                    const d = Math.floor(diff / (1000 * 60 * 60 * 24));
                    const h = Math.floor((diff / (1000 * 60 * 60)) % 24);
                    const m = Math.floor((diff / (1000 * 60)) % 60);
                    const s = Math.floor((diff / 1000) % 60);

                    this.days = String(d).padStart(2, '0');
                    this.hours = String(h).padStart(2, '0');
                    this.minutes = String(m).padStart(2, '0');
                    this.seconds = String(s).padStart(2, '0');
                }
            };
        }
    </script>
</body>
</html>
