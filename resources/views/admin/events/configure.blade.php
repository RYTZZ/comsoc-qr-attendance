<x-layouts.admin :title="'Configure Event: ' . $event->name">
<div class="page-header">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.events.index') }}" class="btn-secondary btn-sm">← Events</a>
        <div>
            <h1 class="page-title">Event Configuration</h1>
            <p class="text-xs text-slate-400 mt-0.5">{{ $event->name }} • Super Admin Control</p>
        </div>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider border {{ $event->status_badge_class }}">
            {{ $event->status_label }}
        </span>
        <a href="{{ route('admin.events.show', $event) }}" class="btn-secondary btn-sm">Preview Info</a>
    </div>
</div>

@if(session('success'))
    <div class="alert-success mb-5 animate-fade-in text-xs sm:text-sm">{{ session('success') }}</div>
@endif

@if($errors->any())
    <div class="mb-5 p-4 rounded-xl bg-red-500/10 border border-red-500/30 text-red-300 text-xs space-y-1">
        <p class="font-bold text-red-400">Please correct the following errors:</p>
        <ul class="list-disc list-inside space-y-0.5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('admin.events.configure.save', $event) }}" enctype="multipart/form-data" class="space-y-6 max-w-4xl"
      x-data="eventConfig({
          eventDate: '{{ old('event_date', $event->event_date?->format('Y-m-d')) }}',
          requiresReg: {{ old('requires_registration', $event->requires_registration) ? 'true' : 'false' }},
          attendanceEnabled: {{ old('attendance_enabled', $event->attendance_enabled) ? 'true' : 'false' }}
      })">
    @csrf

    <div class="card space-y-4">
        <div class="border-b border-slate-800 pb-3 flex items-center justify-between">
            <h2 class="section-title text-base font-brand-display">1. Event Information</h2>
            <span class="text-[11px] text-slate-500 font-mono">Core Identity</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
                <label for="name" class="label">Event Name <span class="text-red-400">*</span></label>
                <input id="name" name="name" type="text" value="{{ old('name', $event->name) }}" required class="input" placeholder="e.g. Annual Computing Convention 2026" />
                @error('name')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            @php
                $yearChoices = [];
                foreach($academicYears as $y) {
                    $yearChoices[] = ['value' => (string)$y->id, 'label' => $y->label];
                }
                $typeChoices = [
                    ['value' => 'General Assembly', 'label' => 'General Assembly'],
                    ['value' => 'Seminar / Tech Talk', 'label' => 'Seminar / Tech Talk'],
                    ['value' => 'Workshop / Bootcamp', 'label' => 'Workshop / Bootcamp'],
                    ['value' => 'Hackathon / Competition', 'label' => 'Hackathon / Competition'],
                    ['value' => 'Social Gathering', 'label' => 'Social Gathering'],
                    ['value' => 'Conference', 'label' => 'Conference'],
                    ['value' => 'Other', 'label' => 'Other Activity'],
                ];
            @endphp

            <div>
                <label class="label">Academic Year <span class="text-red-400">*</span></label>
                <x-custom-dropdown name="academic_year_id" :options="$yearChoices" :value="old('academic_year_id', $event->academic_year_id)" placeholder="Select Academic Year" :required="true" />
                @error('academic_year_id')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="label">Event Type</label>
                <x-custom-dropdown name="event_type" :options="$typeChoices" :value="old('event_type', $event->event_type)" placeholder="Select Event Type" />
                @error('event_type')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="organizer" class="label">Organizer / Host Committee</label>
                <input id="organizer" name="organizer" type="text" value="{{ old('organizer', $event->organizer) }}" class="input" placeholder="e.g. ComSoc Executive Board" />
                @error('organizer')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="contact_info" class="label">Contact Information / Inquiries</label>
                <input id="contact_info" name="contact_info" type="text" value="{{ old('contact_info', $event->contact_info) }}" class="input" placeholder="e.g. comsoc@university.edu / 09123456789" />
                @error('contact_info')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="sm:col-span-2">
                <label for="description" class="label">Event Description</label>
                <textarea id="description" name="description" rows="3" class="input resize-none" placeholder="Provide complete event agenda, requirements, and highlights…">{{ old('description', $event->description) }}</textarea>
                @error('description')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="sm:col-span-2">
                <label class="label">Event Logo / Banner</label>
                <div class="flex items-center gap-4">
                    @if($event->logo_path)
                        <img src="{{ asset('storage/' . $event->logo_path) }}" alt="{{ $event->name }}" class="w-16 h-16 rounded-xl object-contain bg-slate-900 border border-slate-700 p-1">
                    @else
                        <div class="w-16 h-16 rounded-xl bg-slate-900 border border-slate-700 flex items-center justify-center p-2">
                            <img src="{{ asset('images/COMSOC.png') }}" alt="ComSoc Default Logo" class="w-full h-full object-contain opacity-50">
                        </div>
                    @endif
                    <div class="flex-1">
                        <input type="file" name="logo" accept="image/*" class="input py-2 text-xs file:mr-3 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-[#7A1618] file:text-white hover:file:bg-[#921c1f]" />
                        <p class="text-[11px] text-slate-500 mt-1">PNG, JPG, or WebP up to 3MB.</p>
                    </div>
                </div>
                @error('logo')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    <div class="card space-y-6" id="schedule-venue-section">
        <div class="border-b border-slate-800 pb-3 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-[#7A1618]/20 border border-[#7A1618]/40 flex items-center justify-center text-[#e07e83]">
                    <i data-lucide="calendar-clock" class="w-4 h-4"></i>
                </div>
                <div>
                    <h2 class="section-title text-base font-brand-display">2. Schedule & Venue</h2>
                    <p class="text-[11px] text-slate-400">Date, event timing, and location details</p>
                </div>
            </div>
            <span class="text-[11px] font-mono text-slate-500 uppercase tracking-wider bg-slate-900 px-2 py-1 rounded border border-slate-800">Operational Area</span>
        </div>

        <div class="space-y-5">
            <div class="p-4 rounded-xl bg-slate-950/70 border border-slate-800/90 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-300 flex items-center gap-2">
                        <i data-lucide="calendar" class="w-3.5 h-3.5 text-[#e07e83]"></i>
                        Event Date
                    </span>
                    <span class="text-[11px] font-mono font-medium px-2 py-0.5 rounded bg-slate-900 border border-slate-800 text-[#e07e83]"
                          x-text="computedDay || 'Awaiting selection'">
                        {{ $event->event_day ?? '—' }}
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-center">
                    <div>
                        <input id="event_date" name="event_date" type="date" x-model="eventDate" required class="input font-mono text-sm" />
                        @error('event_date')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg bg-slate-900/90 border border-slate-800 text-xs">
                        <i data-lucide="info" class="w-4 h-4 text-slate-400 shrink-0"></i>
                        <div class="truncate">
                            <span class="text-slate-400 block text-[10px] uppercase font-semibold">Selected Event Date</span>
                            <span class="text-white font-medium truncate" x-text="formattedScheduleText || 'No date chosen yet'">
                                {{ $event->formatted_schedule_day }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-4 rounded-xl bg-slate-950/70 border border-slate-800/90 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-300 flex items-center gap-2">
                        <i data-lucide="clock" class="w-3.5 h-3.5 text-[#e07e83]"></i>
                        Event Operating Hours
                    </span>
                    <span class="text-[11px] text-slate-400">Start and expected end of activities</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="event_start_time" class="label">Event Start Time <span class="text-red-400">*</span></label>
                        <x-time-picker name="event_start_time" id="event_start_time" :value="old('event_start_time', $event->starts_at?->format('H:i'))" placeholder="e.g. 08:00 AM" />
                        @error('starts_at')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="event_end_time" class="label">Event End Time</label>
                        <x-time-picker name="event_end_time" id="event_end_time" :value="old('event_end_time', $event->ends_at?->format('H:i'))" placeholder="e.g. 05:00 PM" />
                        @error('ends_at')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <input type="hidden" name="starts_at" :value="computedStartsAt" />
                <input type="hidden" name="ends_at" :value="computedEndsAt" />

                <div x-show="eventTimeError" x-cloak class="p-2.5 rounded-lg bg-red-500/10 border border-red-500/30 text-red-300 text-xs flex items-center gap-2">
                    <i data-lucide="alert-circle" class="w-4 h-4 shrink-0 text-red-400"></i>
                    <span x-text="eventTimeError"></span>
                </div>
            </div>

            <div class="p-4 rounded-xl bg-slate-950/70 border border-slate-800/90 space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-300 flex items-center gap-2">
                        <i data-lucide="map-pin" class="w-3.5 h-3.5 text-[#e07e83]"></i>
                        Venue & Physical Location
                    </span>
                    <span class="text-[11px] text-slate-400">Campus & room instructions</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="venue_name" class="label">Venue / Facility Name <span class="text-red-400">*</span></label>
                        <div class="relative">
                            <input id="venue_name" name="venue_name" type="text" x-model="venueName" required class="input pl-9" placeholder="e.g. SorSU Bulan Campus Social Hall" />
                            <i data-lucide="building" class="w-4 h-4 text-slate-500 absolute left-3 top-3 pointer-events-none"></i>
                        </div>
                        <p class="text-[10px] text-slate-500 mt-1">Primary building or hall where attendance and activities take place.</p>
                        @error('venue_name')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="venue_address" class="label">Campus / Specific Address</label>
                        <div class="relative">
                            <input id="venue_address" name="venue_address" type="text" x-model="venueAddress" class="input pl-9" placeholder="e.g. Zone 8, Bulan, Sorsogon" />
                            <i data-lucide="map" class="w-4 h-4 text-slate-500 absolute left-3 top-3 pointer-events-none"></i>
                        </div>
                        <p class="text-[10px] text-slate-500 mt-1">Geographic campus location or barangay address.</p>
                        @error('venue_address')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="venue_details" class="label">Additional Room & Gate Directions</label>
                        <textarea id="venue_details" name="venue_details" rows="2" class="input resize-none" placeholder="e.g. 2nd Floor, West Wing. All students must present their student ID at Gate 1 checkpoint.">{{ old('venue_details', $event->venue_details) }}</textarea>
                        @error('venue_details')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card space-y-4">
        <div class="border-b border-slate-800 pb-3 flex items-center justify-between">
            <h2 class="section-title text-base font-brand-display">3. Registration</h2>
            <span class="text-[11px] text-slate-500 font-mono">Policies & Deadlines</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="registration_opens_at" class="label">Registration Opens Date & Time</label>
                <input id="registration_opens_at" name="registration_opens_at" type="datetime-local" value="{{ old('registration_opens_at', $event->registration_opens_at?->format('Y-m-d\TH:i')) }}" class="input" />
                @error('registration_opens_at')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="registration_deadline" class="label">Registration Closes Date & Time</label>
                <input id="registration_deadline" name="registration_deadline" type="datetime-local" value="{{ old('registration_deadline', $event->registration_deadline?->format('Y-m-d\TH:i')) }}" class="input" />
                @error('registration_deadline')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="space-y-3 pt-2">
            <label class="flex items-start gap-3 p-3 rounded-xl bg-slate-950/60 border border-slate-800 cursor-pointer hover:border-slate-700 transition">
                <input type="checkbox" name="requires_registration" value="1" x-model="requiresReg" class="mt-0.5 w-4 h-4 rounded bg-[#12141c] border-slate-700 text-[#7A1618] focus:ring-[#7A1618]" />
                <div>
                    <span class="text-sm font-semibold text-white">Registration Required</span>
                    <p class="text-xs text-slate-400 mt-0.5">Participants must register before attending or scanning their QR credentials.</p>
                </div>
            </label>

            <label class="flex items-start gap-3 p-3 rounded-xl bg-slate-950/60 border border-slate-800 cursor-pointer hover:border-slate-700 transition">
                <input type="checkbox" name="allow_non_students" value="1" {{ old('allow_non_students', $event->allow_non_students) ? 'checked' : '' }} class="mt-0.5 w-4 h-4 rounded bg-[#12141c] border-slate-700 text-[#7A1618] focus:ring-[#7A1618]" />
                <div>
                    <span class="text-sm font-semibold text-white">Non-Student Registration Enabled</span>
                    <p class="text-xs text-slate-400 mt-0.5">Allow external guest attendees, senior high students, and faculty from other institutions to register.</p>
                </div>
            </label>
        </div>
    </div>

    @php
        $existingMorningIn = $event->attendanceSessions->firstWhere('type', 'morning_in');
        $existingMorningOut = $event->attendanceSessions->firstWhere('type', 'morning_out');
        $existingAfternoonIn = $event->attendanceSessions->firstWhere('type', 'afternoon_in');
        $existingAfternoonOut = $event->attendanceSessions->firstWhere('type', 'afternoon_out');
    @endphp

    <div class="card space-y-6" id="attendance-config-section">
        <div class="border-b border-slate-800 pb-3 flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-[#7A1618]/20 border border-[#7A1618]/40 flex items-center justify-center text-[#e07e83]">
                    <i data-lucide="qr-code" class="w-4 h-4"></i>
                </div>
                <div>
                    <h2 class="section-title text-base font-brand-display">4. Attendance Configuration</h2>
                    <p class="text-[11px] text-slate-400">Configure the attendance checkpoints and time windows used by the kiosk.</p>
                </div>
            </div>
            <span class="text-[11px] font-mono text-slate-500 uppercase tracking-wider bg-slate-900 px-2.5 py-1 rounded-md border border-slate-800">Sessions & Punctuality</span>
        </div>

        <div class="p-4 rounded-xl bg-slate-950/80 border border-slate-800/90 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="space-y-0.5">
                <span class="text-sm font-bold text-white flex items-center gap-2">
                    <i data-lucide="scan-line" class="w-4 h-4 text-[#e07e83]"></i>
                    Attendance Enabled
                </span>
                <p class="text-xs text-slate-400">Enable kiosk checkpoint scanning and attendance tracking for this event.</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer shrink-0">
                <input type="checkbox" name="attendance_enabled" value="1" x-model="attendanceEnabled" class="sr-only peer">
                <div class="w-12 h-6.5 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#7A1618]"></div>
                <span class="ml-2.5 text-xs font-mono font-bold" :class="attendanceEnabled ? 'text-[#e07e83]' : 'text-slate-500'" x-text="attendanceEnabled ? 'ACTIVE' : 'MUTED'"></span>
            </label>
        </div>

        <div x-show="attendanceEnabled" x-cloak class="space-y-6 pt-1">
            <div class="space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-200">Morning Session</h3>
                    </div>
                    <span class="text-[11px] text-slate-500 font-mono">Arrival & Lunch Dismissal</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-4 rounded-xl border transition-all space-y-4"
                         :class="morningInEnabled ? 'bg-slate-950/80 border-slate-800' : 'bg-slate-950/40 border-slate-900 opacity-60'">
                        <div class="flex items-start justify-between gap-2 border-b border-slate-800/80 pb-3">
                            <div>
                                <span class="text-xs font-bold text-white uppercase tracking-wider block">Morning IN</span>
                                <p class="text-[11px] text-slate-400 mt-0.5">Student arrival and morning check-in</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer shrink-0">
                                <input type="checkbox" name="sessions[morning_in][enabled]" value="1" x-model="morningInEnabled" class="sr-only peer">
                                <div class="w-9 h-5 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#7A1618]"></div>
                                <span class="ml-2 text-[11px] font-mono font-bold" :class="morningInEnabled ? 'text-emerald-400' : 'text-slate-500'" x-text="morningInEnabled ? 'ON' : 'OFF'"></span>
                            </label>
                        </div>

                        <div x-show="morningInEnabled" class="space-y-3">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="label text-xs">Opens At <span class="text-red-400">*</span></label>
                                    <x-time-picker name="sessions[morning_in][opens_at]" id="morning_in_opens" :value="old('sessions.morning_in.opens_at', $existingMorningIn?->opens_at)" placeholder="08:00 AM" />
                                    @error('sessions.morning_in.opens_at')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="label text-xs">Closes At <span class="text-red-400">*</span></label>
                                    <x-time-picker name="sessions[morning_in][closes_at]" id="morning_in_closes" :value="old('sessions.morning_in.closes_at', $existingMorningIn?->closes_at)" placeholder="11:30 AM" />
                                    @error('sessions.morning_in.closes_at')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <div class="pt-2 border-t border-slate-800/80">
                                <label class="label text-xs">Late Threshold</label>
                                <x-time-picker name="sessions[morning_in][late_threshold]" id="morning_in_late" :value="old('sessions.morning_in.late_threshold', $existingMorningIn?->late_threshold)" placeholder="e.g. 08:15 AM" />
                                <p class="text-[11px] text-amber-300/90 mt-1.5 flex items-start gap-1.5">
                                    <i data-lucide="info" class="w-3.5 h-3.5 text-amber-400 shrink-0 mt-0.5"></i>
                                    <span>Students checking in after this time will be marked as <strong class="text-amber-200">Late</strong> for Morning IN. Scans prior to this are recorded as Present.</span>
                                </p>
                                @error('sessions.morning_in.late_threshold')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <div x-show="!morningInEnabled" class="text-[11px] text-slate-500 italic py-2">
                            This checkpoint is disabled and will not accept attendance scans.
                        </div>
                    </div>

                    <div class="p-4 rounded-xl border transition-all space-y-4"
                         :class="morningOutEnabled ? 'bg-slate-950/80 border-slate-800' : 'bg-slate-950/40 border-slate-900 opacity-60'">
                        <div class="flex items-start justify-between gap-2 border-b border-slate-800/80 pb-3">
                            <div>
                                <span class="text-xs font-bold text-white uppercase tracking-wider block">Morning OUT</span>
                                <p class="text-[11px] text-slate-400 mt-0.5">Student departure for the lunch break</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer shrink-0">
                                <input type="checkbox" name="sessions[morning_out][enabled]" value="1" x-model="morningOutEnabled" class="sr-only peer">
                                <div class="w-9 h-5 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#7A1618]"></div>
                                <span class="ml-2 text-[11px] font-mono font-bold" :class="morningOutEnabled ? 'text-emerald-400' : 'text-slate-500'" x-text="morningOutEnabled ? 'ON' : 'OFF'"></span>
                            </label>
                        </div>

                        <div x-show="morningOutEnabled" class="space-y-3">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="label text-xs">Opens At <span class="text-red-400">*</span></label>
                                    <x-time-picker name="sessions[morning_out][opens_at]" id="morning_out_opens" :value="old('sessions.morning_out.opens_at', $existingMorningOut?->opens_at)" placeholder="11:30 AM" />
                                    @error('sessions.morning_out.opens_at')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="label text-xs">Closes At <span class="text-red-400">*</span></label>
                                    <x-time-picker name="sessions[morning_out][closes_at]" id="morning_out_closes" :value="old('sessions.morning_out.closes_at', $existingMorningOut?->closes_at)" placeholder="01:00 PM" />
                                    @error('sessions.morning_out.closes_at')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                                </div>
                            </div>
                            <div class="pt-2 text-[11px] text-slate-500 flex items-center gap-1.5">
                                <i data-lucide="check-circle-2" class="w-3.5 h-3.5 text-slate-400 shrink-0"></i>
                                <span>Requires a prior Morning IN scan to validate attendance departure.</span>
                            </div>
                        </div>
                        <div x-show="!morningOutEnabled" class="text-[11px] text-slate-500 italic py-2">
                            This checkpoint is disabled and will not accept attendance scans.
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-center my-1 text-slate-600">
                <i data-lucide="arrow-down" class="w-4 h-4"></i>
            </div>

            <div class="space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-orange-400"></span>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-200">Afternoon Session</h3>
                    </div>
                    <span class="text-[11px] text-slate-500 font-mono">Return & Final Dismissal</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-4 rounded-xl border transition-all space-y-4"
                         :class="afternoonInEnabled ? 'bg-slate-950/80 border-slate-800' : 'bg-slate-950/40 border-slate-900 opacity-60'">
                        <div class="flex items-start justify-between gap-2 border-b border-slate-800/80 pb-3">
                            <div>
                                <span class="text-xs font-bold text-white uppercase tracking-wider block">Afternoon IN</span>
                                <p class="text-[11px] text-slate-400 mt-0.5">Student return and afternoon check-in</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer shrink-0">
                                <input type="checkbox" name="sessions[afternoon_in][enabled]" value="1" x-model="afternoonInEnabled" class="sr-only peer">
                                <div class="w-9 h-5 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#7A1618]"></div>
                                <span class="ml-2 text-[11px] font-mono font-bold" :class="afternoonInEnabled ? 'text-emerald-400' : 'text-slate-500'" x-text="afternoonInEnabled ? 'ON' : 'OFF'"></span>
                            </label>
                        </div>

                        <div x-show="afternoonInEnabled" class="space-y-3">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="label text-xs">Opens At <span class="text-red-400">*</span></label>
                                    <x-time-picker name="sessions[afternoon_in][opens_at]" id="afternoon_in_opens" :value="old('sessions.afternoon_in.opens_at', $existingAfternoonIn?->opens_at)" placeholder="01:00 PM" />
                                    @error('sessions.afternoon_in.opens_at')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="label text-xs">Closes At <span class="text-red-400">*</span></label>
                                    <x-time-picker name="sessions[afternoon_in][closes_at]" id="afternoon_in_closes" :value="old('sessions.afternoon_in.closes_at', $existingAfternoonIn?->closes_at)" placeholder="03:00 PM" />
                                    @error('sessions.afternoon_in.closes_at')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <div class="pt-2 border-t border-slate-800/80">
                                <label class="label text-xs">Late Threshold</label>
                                <x-time-picker name="sessions[afternoon_in][late_threshold]" id="afternoon_in_late" :value="old('sessions.afternoon_in.late_threshold', $existingAfternoonIn?->late_threshold)" placeholder="Optional afternoon late cutoff" />
                                <p class="text-[11px] text-amber-300/90 mt-1.5 flex items-start gap-1.5">
                                    <i data-lucide="info" class="w-3.5 h-3.5 text-amber-400 shrink-0 mt-0.5"></i>
                                    <span>Students checking in after this cutoff are marked Late for Afternoon IN. Leave blank if afternoon punctuality is not tracked.</span>
                                </p>
                                @error('sessions.afternoon_in.late_threshold')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <div x-show="!afternoonInEnabled" class="text-[11px] text-slate-500 italic py-2">
                            This checkpoint is disabled and will not accept attendance scans.
                        </div>
                    </div>

                    <div class="p-4 rounded-xl border transition-all space-y-4"
                         :class="afternoonOutEnabled ? 'bg-slate-950/80 border-slate-800' : 'bg-slate-950/40 border-slate-900 opacity-60'">
                        <div class="flex items-start justify-between gap-2 border-b border-slate-800/80 pb-3">
                            <div>
                                <span class="text-xs font-bold text-white uppercase tracking-wider block">Afternoon OUT</span>
                                <p class="text-[11px] text-slate-400 mt-0.5">Student final departure</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer shrink-0">
                                <input type="checkbox" name="sessions[afternoon_out][enabled]" value="1" x-model="afternoonOutEnabled" class="sr-only peer">
                                <div class="w-9 h-5 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#7A1618]"></div>
                                <span class="ml-2 text-[11px] font-mono font-bold" :class="afternoonOutEnabled ? 'text-emerald-400' : 'text-slate-500'" x-text="afternoonOutEnabled ? 'ON' : 'OFF'"></span>
                            </label>
                        </div>

                        <div x-show="afternoonOutEnabled" class="space-y-3">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="label text-xs">Opens At <span class="text-red-400">*</span></label>
                                    <x-time-picker name="sessions[afternoon_out][opens_at]" id="afternoon_out_opens" :value="old('sessions.afternoon_out.opens_at', $existingAfternoonOut?->opens_at)" placeholder="04:30 PM" />
                                    @error('sessions.afternoon_out.opens_at')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="label text-xs">Closes At <span class="text-red-400">*</span></label>
                                    <x-time-picker name="sessions[afternoon_out][closes_at]" id="afternoon_out_closes" :value="old('sessions.afternoon_out.closes_at', $existingAfternoonOut?->closes_at)" placeholder="06:00 PM" />
                                    @error('sessions.afternoon_out.closes_at')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                                </div>
                            </div>
                            <div class="pt-2 text-[11px] text-slate-500 flex items-center gap-1.5">
                                <i data-lucide="check-circle-2" class="w-3.5 h-3.5 text-slate-400 shrink-0"></i>
                                <span>Records completion of attendance for participants before event concludes.</span>
                            </div>
                        </div>
                        <div x-show="!afternoonOutEnabled" class="text-[11px] text-slate-500 italic py-2">
                            This checkpoint is disabled and will not accept attendance scans.
                        </div>
                    </div>
                </div>
            </div>

            <div x-show="attendanceValidationErrors.length > 0" x-cloak class="p-3.5 rounded-xl bg-red-500/10 border border-red-500/30 text-red-300 text-xs space-y-1.5">
                <div class="font-bold flex items-center gap-2 text-red-400">
                    <i data-lucide="alert-triangle" class="w-4 h-4 shrink-0"></i>
                    Schedule Sequence Conflict Detected:
                </div>
                <ul class="list-disc list-inside space-y-1 text-[11px]">
                    <template x-for="err in attendanceValidationErrors" :key="err">
                        <li x-text="err"></li>
                    </template>
                </ul>
            </div>

            <div class="p-4 rounded-xl bg-slate-950/80 border border-slate-800/90 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-300 flex items-center gap-2">
                        <i data-lucide="route" class="w-3.5 h-3.5 text-[#e07e83]"></i>
                        Attendance Timeline Preview
                    </span>
                    <span class="text-[10px] text-emerald-400 font-mono">Dynamic Flow Preview</span>
                </div>

                <div class="hidden sm:block overflow-x-auto py-2">
                    <div class="flex items-center justify-between min-w-[500px] relative px-4">
                        <div class="absolute left-6 right-6 top-1/2 -translate-y-1/2 h-0.5 bg-slate-800 z-0"></div>

                        <div class="relative z-10 flex flex-col items-center text-center">
                            <span class="text-[11px] font-mono font-bold" :class="morningInEnabled ? 'text-white' : 'text-slate-600'" x-text="morningInOpens ? formatTimeDisplay(morningInOpens) : 'TBD'"></span>
                            <div class="w-3.5 h-3.5 rounded-full my-1.5 border-2" :class="morningInEnabled ? 'bg-[#7A1618] border-red-400 shadow-sm' : 'bg-slate-800 border-slate-700'"></div>
                            <span class="text-[10px] font-semibold" :class="morningInEnabled ? 'text-[#dfa6a9]' : 'text-slate-600'">Morning IN</span>
                        </div>

                        <template x-if="morningInEnabled && morningInLate">
                            <div class="relative z-10 flex flex-col items-center text-center">
                                <span class="text-[11px] font-mono font-bold text-amber-400" x-text="formatTimeDisplay(morningInLate)"></span>
                                <div class="w-3 h-3 rounded-full my-1.5 border-2 bg-amber-500 border-amber-300"></div>
                                <span class="text-[10px] font-semibold text-amber-400/90">Late Cutoff</span>
                            </div>
                        </template>

                        <div class="relative z-10 flex flex-col items-center text-center">
                            <span class="text-[11px] font-mono font-bold" :class="morningOutEnabled ? 'text-white' : 'text-slate-600'" x-text="morningOutOpens ? formatTimeDisplay(morningOutOpens) : 'TBD'"></span>
                            <div class="w-3.5 h-3.5 rounded-full my-1.5 border-2" :class="morningOutEnabled ? 'bg-[#7A1618] border-red-400 shadow-sm' : 'bg-slate-800 border-slate-700'"></div>
                            <span class="text-[10px] font-semibold" :class="morningOutEnabled ? 'text-[#dfa6a9]' : 'text-slate-600'">Morning OUT</span>
                        </div>

                        <div class="relative z-10 flex flex-col items-center text-center">
                            <span class="text-[11px] font-mono font-bold" :class="afternoonInEnabled ? 'text-white' : 'text-slate-600'" x-text="afternoonInOpens ? formatTimeDisplay(afternoonInOpens) : 'TBD'"></span>
                            <div class="w-3.5 h-3.5 rounded-full my-1.5 border-2" :class="afternoonInEnabled ? 'bg-[#7A1618] border-red-400 shadow-sm' : 'bg-slate-800 border-slate-700'"></div>
                            <span class="text-[10px] font-semibold" :class="afternoonInEnabled ? 'text-[#dfa6a9]' : 'text-slate-600'">Afternoon IN</span>
                        </div>

                        <template x-if="afternoonInEnabled && afternoonInLate">
                            <div class="relative z-10 flex flex-col items-center text-center">
                                <span class="text-[11px] font-mono font-bold text-amber-400" x-text="formatTimeDisplay(afternoonInLate)"></span>
                                <div class="w-3 h-3 rounded-full my-1.5 border-2 bg-amber-500 border-amber-300"></div>
                                <span class="text-[10px] font-semibold text-amber-400/90">Late Cutoff</span>
                            </div>
                        </template>

                        <div class="relative z-10 flex flex-col items-center text-center">
                            <span class="text-[11px] font-mono font-bold" :class="afternoonOutEnabled ? 'text-white' : 'text-slate-600'" x-text="afternoonOutOpens ? formatTimeDisplay(afternoonOutOpens) : 'TBD'"></span>
                            <div class="w-3.5 h-3.5 rounded-full my-1.5 border-2" :class="afternoonOutEnabled ? 'bg-[#7A1618] border-red-400 shadow-sm' : 'bg-slate-800 border-slate-700'"></div>
                            <span class="text-[10px] font-semibold" :class="afternoonOutEnabled ? 'text-[#dfa6a9]' : 'text-slate-600'">Afternoon OUT</span>
                        </div>
                    </div>
                </div>

                <div class="block sm:hidden space-y-2 text-xs border-l-2 border-slate-800 pl-3 py-1 font-mono">
                    <div :class="morningInEnabled ? 'text-white' : 'text-slate-600'">
                        <span class="text-slate-400 font-bold">Morning IN:</span>
                        <span x-text="morningInOpens ? formatTimeDisplay(morningInOpens) : 'TBD'"></span>
                        <template x-if="morningInEnabled && morningInLate">
                            <span class="text-amber-400 text-[11px] block">(Late after <span x-text="formatTimeDisplay(morningInLate)"></span>)</span>
                        </template>
                    </div>
                    <div :class="morningOutEnabled ? 'text-white' : 'text-slate-600'">
                        <span class="text-slate-400 font-bold">Morning OUT:</span>
                        <span x-text="morningOutOpens ? formatTimeDisplay(morningOutOpens) : 'TBD'"></span>
                    </div>
                    <div :class="afternoonInEnabled ? 'text-white' : 'text-slate-600'">
                        <span class="text-slate-400 font-bold">Afternoon IN:</span>
                        <span x-text="afternoonInOpens ? formatTimeDisplay(afternoonInOpens) : 'TBD'"></span>
                        <template x-if="afternoonInEnabled && afternoonInLate">
                            <span class="text-amber-400 text-[11px] block">(Late after <span x-text="formatTimeDisplay(afternoonInLate)"></span>)</span>
                        </template>
                    </div>
                    <div :class="afternoonOutEnabled ? 'text-white' : 'text-slate-600'">
                        <span class="text-slate-400 font-bold">Afternoon OUT:</span>
                        <span x-text="afternoonOutOpens ? formatTimeDisplay(afternoonOutOpens) : 'TBD'"></span>
                    </div>
                </div>
            </div>

            <div class="p-4 rounded-xl bg-slate-900/90 border border-slate-800 space-y-3">
                <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-300 flex items-center gap-2">
                        <i data-lucide="clipboard-check" class="w-3.5 h-3.5 text-[#e07e83]"></i>
                        Current Attendance Schedule Summary
                    </span>
                    <span class="text-[11px] text-emerald-400 font-mono">Pre-Save Verification</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                    <div class="p-3 rounded-lg bg-slate-950/70 border border-slate-800/80 space-y-1.5">
                        <span class="text-[#dfa6a9] font-bold block text-xs uppercase tracking-wider">Morning</span>
                        <div class="space-y-1 font-mono text-slate-300">
                            <div class="flex justify-between">
                                <span>Morning IN:</span>
                                <span class="font-bold text-white" x-text="morningInEnabled ? (formatTimeDisplay(morningInOpens) + ' — ' + formatTimeDisplay(morningInCloses)) : 'Disabled'"></span>
                            </div>
                            <template x-if="morningInEnabled && morningInLate">
                                <div class="flex justify-between text-amber-400">
                                    <span>Late Threshold:</span>
                                    <span class="font-bold" x-text="formatTimeDisplay(morningInLate)"></span>
                                </div>
                            </template>
                            <div class="flex justify-between">
                                <span>Morning OUT:</span>
                                <span class="font-bold text-white" x-text="morningOutEnabled ? (formatTimeDisplay(morningOutOpens) + ' — ' + formatTimeDisplay(morningOutCloses)) : 'Disabled'"></span>
                            </div>
                        </div>
                    </div>

                    <div class="p-3 rounded-lg bg-slate-950/70 border border-slate-800/80 space-y-1.5">
                        <span class="text-[#dfa6a9] font-bold block text-xs uppercase tracking-wider">Afternoon</span>
                        <div class="space-y-1 font-mono text-slate-300">
                            <div class="flex justify-between">
                                <span>Afternoon IN:</span>
                                <span class="font-bold text-white" x-text="afternoonInEnabled ? (formatTimeDisplay(afternoonInOpens) + ' — ' + formatTimeDisplay(afternoonInCloses)) : 'Disabled'"></span>
                            </div>
                            <template x-if="afternoonInEnabled && afternoonInLate">
                                <div class="flex justify-between text-amber-400">
                                    <span>Late Threshold:</span>
                                    <span class="font-bold" x-text="formatTimeDisplay(afternoonInLate)"></span>
                                </div>
                            </template>
                            <div class="flex justify-between">
                                <span>Afternoon OUT:</span>
                                <span class="font-bold text-white" x-text="afternoonOutEnabled ? (formatTimeDisplay(afternoonOutOpens) + ' — ' + formatTimeDisplay(afternoonOutCloses)) : 'Disabled'"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card space-y-4">
        <div class="border-b border-slate-800 pb-3 flex items-center justify-between">
            <h2 class="section-title text-base font-brand-display">5. Participant Limit</h2>
            <span class="text-[11px] text-slate-500 font-mono">Capacities & Availability</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-center">
            <div>
                <label for="max_participants" class="label">Maximum Participant Limit</label>
                <input id="max_participants" name="max_participants" type="number" min="1" value="{{ old('max_participants', $event->max_participants) }}" class="input" placeholder="Leave blank for unlimited capacity" />
                <p class="text-[11px] text-slate-500 mt-1">If blank or 0, registrations will be unlimited.</p>
                @error('max_participants')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="p-3.5 rounded-xl bg-slate-950/60 border border-slate-800 text-xs space-y-1.5">
                <span class="text-slate-400 font-semibold block">Current Registration Status:</span>
                <p class="font-mono text-white text-sm">
                    Registered: <span class="text-emerald-400 font-bold">{{ $event->participant_count }}</span>
                    / {{ $event->max_participants ?: 'Unlimited' }}
                </p>
                @if($event->isFull())
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-red-500/10 text-red-400 border border-red-500/20 uppercase">
                        Registration Full — Additional Entries Blocked
                    </span>
                @else
                    <span class="text-[11px] text-slate-400">Accepting registrations while open.</span>
                @endif
            </div>
        </div>
    </div>

    <div class="card space-y-4">
        <div class="border-b border-slate-800 pb-3 flex items-center justify-between">
            <h2 class="section-title text-base font-brand-display">6. Additional Settings</h2>
            <span class="text-[11px] text-slate-500 font-mono">Snacks & Lifecycle State</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @php
                $statusList = [
                    ['value' => 'draft', 'label' => 'Draft'],
                    ['value' => 'registration_open', 'label' => 'Registration Open'],
                    ['value' => 'registration_closed', 'label' => 'Registration Closed'],
                    ['value' => 'ongoing', 'label' => 'Ongoing'],
                    ['value' => 'completed', 'label' => 'Completed'],
                    ['value' => 'archived', 'label' => 'Archived'],
                ];
            @endphp

            <div>
                <label class="label">Event Lifecycle Status</label>
                <x-custom-dropdown name="status" :options="$statusList" :value="old('status', $event->status)" placeholder="Select Status" />
                <p class="text-[11px] text-slate-500 mt-1">Super Admin can override status manually at any time.</p>
                @error('status')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="space-y-3">
                <label class="flex items-start gap-3 p-3 rounded-xl bg-slate-950/60 border border-slate-800 cursor-pointer hover:border-slate-700 transition">
                    <input type="checkbox" name="is_published" value="1" {{ old('is_published', $event->is_published) ? 'checked' : '' }} class="mt-0.5 w-4 h-4 rounded bg-[#12141c] border-slate-700 text-[#7A1618] focus:ring-[#7A1618]" />
                    <div>
                        <span class="text-sm font-semibold text-white">Publish Event</span>
                        <p class="text-xs text-slate-400 mt-0.5">Show this event on kiosks, student dashboard, and public portal.</p>
                    </div>
                </label>

                <label class="flex items-start gap-3 p-3 rounded-xl bg-slate-950/60 border border-slate-800 cursor-pointer hover:border-slate-700 transition">
                    <input type="checkbox" name="snack_distribution_enabled" value="1" {{ old('snack_distribution_enabled', $event->snack_distribution_enabled) ? 'checked' : '' }} class="mt-0.5 w-4 h-4 rounded bg-[#12141c] border-slate-700 text-[#7A1618] focus:ring-[#7A1618]" />
                    <div>
                        <span class="text-sm font-semibold text-white">Snack Distribution Enabled</span>
                        <p class="text-xs text-slate-400 mt-0.5">Allow snack claim kiosk sessions for this event.</p>
                    </div>
                </label>
            </div>
        </div>
    </div>

    <div class="sticky bottom-4 z-20 p-4 rounded-xl bg-[#171a23]/95 backdrop-blur-md border border-slate-700 shadow-2xl flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full" :class="isDirty ? 'bg-amber-400 animate-pulse' : 'bg-emerald-400'"></span>
            <span class="text-xs text-slate-300 font-medium" x-text="isDirty ? 'Unsaved configuration changes' : 'All saved and up to date'"></span>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.events.index') }}" class="btn-secondary btn-sm">Cancel</a>
            <button type="submit"
                    :disabled="isSubmitting || hasBlockingErrors"
                    class="btn-primary flex items-center gap-2">
                <template x-if="isSubmitting">
                    <svg class="animate-spin -ml-1 mr-1 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                    </svg>
                </template>
                <template x-if="!isSubmitting">
                    <i data-lucide="save" class="w-4 h-4"></i>
                </template>
                <span x-text="isSubmitting ? 'Saving Configuration…' : 'Save Event Configuration'"></span>
            </button>
        </div>
    </div>
</form>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('eventConfig', (config) => ({
        eventDate: config.eventDate || '',
        requiresReg: config.requiresReg,
        attendanceEnabled: config.attendanceEnabled,
        eventStartTime: '{{ old('event_start_time', $event->starts_at?->format('H:i')) }}',
        eventEndTime: '{{ old('event_end_time', $event->ends_at?->format('H:i')) }}',
        venueName: '{{ addslashes(old('venue_name', $event->venue_name ?: $event->location)) }}',
        venueAddress: '{{ addslashes(old('venue_address', $event->venue_address)) }}',

        morningInEnabled: {{ $existingMorningIn ? 'true' : 'false' }},
        morningInOpens: '{{ old('sessions.morning_in.opens_at', $existingMorningIn?->opens_at) }}',
        morningInCloses: '{{ old('sessions.morning_in.closes_at', $existingMorningIn?->closes_at) }}',
        morningInLate: '{{ old('sessions.morning_in.late_threshold', $existingMorningIn?->late_threshold) }}',

        morningOutEnabled: {{ $existingMorningOut ? 'true' : 'false' }},
        morningOutOpens: '{{ old('sessions.morning_out.opens_at', $existingMorningOut?->opens_at) }}',
        morningOutCloses: '{{ old('sessions.morning_out.closes_at', $existingMorningOut?->closes_at) }}',

        afternoonInEnabled: {{ $existingAfternoonIn ? 'true' : 'false' }},
        afternoonInOpens: '{{ old('sessions.afternoon_in.opens_at', $existingAfternoonIn?->opens_at) }}',
        afternoonInCloses: '{{ old('sessions.afternoon_in.closes_at', $existingAfternoonIn?->closes_at) }}',
        afternoonInLate: '{{ old('sessions.afternoon_in.late_threshold', $existingAfternoonIn?->late_threshold) }}',

        afternoonOutEnabled: {{ $existingAfternoonOut ? 'true' : 'false' }},
        afternoonOutOpens: '{{ old('sessions.afternoon_out.opens_at', $existingAfternoonOut?->opens_at) }}',
        afternoonOutCloses: '{{ old('sessions.afternoon_out.closes_at', $existingAfternoonOut?->closes_at) }}',

        isSubmitting: false,
        isDirty: false,

        init() {
            this.$watch('eventDate', () => this.isDirty = true);
            this.$watch('venueName', () => this.isDirty = true);
            this.$watch('venueAddress', () => this.isDirty = true);
            this.$watch('morningInEnabled', () => this.isDirty = true);
            this.$watch('morningOutEnabled', () => this.isDirty = true);
            this.$watch('afternoonInEnabled', () => this.isDirty = true);
            this.$watch('afternoonOutEnabled', () => this.isDirty = true);
            this.$watch('attendanceEnabled', () => this.isDirty = true);

            this.$el.addEventListener('time-change', (e) => {
                this.isDirty = true;
                const { name, value } = e.detail;
                if (name === 'event_start_time') this.eventStartTime = value;
                if (name === 'event_end_time') this.eventEndTime = value;
                if (name === 'sessions[morning_in][opens_at]') this.morningInOpens = value;
                if (name === 'sessions[morning_in][closes_at]') this.morningInCloses = value;
                if (name === 'sessions[morning_in][late_threshold]') this.morningInLate = value;
                if (name === 'sessions[morning_out][opens_at]') this.morningOutOpens = value;
                if (name === 'sessions[morning_out][closes_at]') this.morningOutCloses = value;
                if (name === 'sessions[afternoon_in][opens_at]') this.afternoonInOpens = value;
                if (name === 'sessions[afternoon_in][closes_at]') this.afternoonInCloses = value;
                if (name === 'sessions[afternoon_in][late_threshold]') this.afternoonInLate = value;
                if (name === 'sessions[afternoon_out][opens_at]') this.afternoonOutOpens = value;
                if (name === 'sessions[afternoon_out][closes_at]') this.afternoonOutCloses = value;
            });

            this.$el.addEventListener('submit', () => {
                this.isSubmitting = true;
            });

            if (window.lucide) {
                this.$nextTick(() => window.lucide.createIcons());
            }
        },

        get computedDay() {
            if (!this.eventDate) return '';
            const d = new Date(this.eventDate + 'T00:00:00');
            if (isNaN(d)) return '';
            return d.toLocaleDateString('en-US', { weekday: 'long' });
        },

        get formattedScheduleText() {
            if (!this.eventDate) return '';
            const d = new Date(this.eventDate + 'T00:00:00');
            if (isNaN(d)) return '';
            const dateStr = d.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
            const dayStr = d.toLocaleDateString('en-US', { weekday: 'long' });
            return `${dateStr} — ${dayStr}`;
        },

        get computedStartsAt() {
            if (!this.eventDate || !this.eventStartTime) return '';
            return `${this.eventDate} ${this.eventStartTime}:00`;
        },

        get computedEndsAt() {
            if (!this.eventDate || !this.eventEndTime) return '';
            return `${this.eventDate} ${this.eventEndTime}:00`;
        },

        get eventTimeError() {
            if (this.eventStartTime && this.eventEndTime) {
                if (this.eventEndTime <= this.eventStartTime) {
                    return 'Event End Time must be later than Event Start Time.';
                }
            }
            return '';
        },

        get attendanceValidationErrors() {
            const errors = [];
            if (!this.attendanceEnabled) return errors;

            if (this.morningInEnabled) {
                if (this.morningInOpens && this.morningInCloses && this.morningInOpens >= this.morningInCloses) {
                    errors.push('Morning IN opening time must be earlier than Morning IN closing time.');
                }
                if (this.morningInLate && this.morningInOpens && this.morningInCloses) {
                    if (this.morningInLate < this.morningInOpens || this.morningInLate > this.morningInCloses) {
                        errors.push('Morning Late Threshold (' + this.formatTimeDisplay(this.morningInLate) + ') must be between Morning IN opening (' + this.formatTimeDisplay(this.morningInOpens) + ') and closing (' + this.formatTimeDisplay(this.morningInCloses) + ').');
                    }
                }
            }

            if (this.morningOutEnabled) {
                if (this.morningOutOpens && this.morningOutCloses && this.morningOutOpens >= this.morningOutCloses) {
                    errors.push('Morning OUT opening time must be earlier than Morning OUT closing time.');
                }
            }

            if (this.morningInEnabled && this.morningOutEnabled && this.morningInOpens && this.morningOutOpens) {
                if (this.morningInOpens >= this.morningOutOpens) {
                    errors.push('Morning OUT must be later than Morning IN.');
                }
            }

            if (this.afternoonInEnabled) {
                if (this.afternoonInOpens && this.afternoonInCloses && this.afternoonInOpens >= this.afternoonInCloses) {
                    errors.push('Afternoon IN opening time must be earlier than Afternoon IN closing time.');
                }
                if (this.afternoonInLate && this.afternoonInOpens && this.afternoonInCloses) {
                    if (this.afternoonInLate < this.afternoonInOpens || this.afternoonInLate > this.afternoonInCloses) {
                        errors.push('Afternoon Late Threshold (' + this.formatTimeDisplay(this.afternoonInLate) + ') must be between Afternoon IN opening (' + this.formatTimeDisplay(this.afternoonInOpens) + ') and closing (' + this.formatTimeDisplay(this.afternoonInCloses) + ').');
                    }
                }
            }

            if (this.afternoonOutEnabled) {
                if (this.afternoonOutOpens && this.afternoonOutCloses && this.afternoonOutOpens >= this.afternoonOutCloses) {
                    errors.push('Afternoon OUT opening time must be earlier than Afternoon OUT closing time.');
                }
            }

            if (this.afternoonInEnabled && this.afternoonOutEnabled && this.afternoonInOpens && this.afternoonOutOpens) {
                if (this.afternoonInOpens >= this.afternoonOutOpens) {
                    errors.push('Afternoon OUT must be later than Afternoon IN.');
                }
            }

            if (this.morningOutEnabled && this.afternoonInEnabled && this.morningOutOpens && this.afternoonInOpens) {
                if (this.morningOutOpens > this.afternoonInOpens) {
                    errors.push('Morning OUT (' + this.formatTimeDisplay(this.morningOutOpens) + ') cannot be later than Afternoon IN (' + this.formatTimeDisplay(this.afternoonInOpens) + ').');
                }
            }

            return errors;
        },

        get hasBlockingErrors() {
            return !!this.eventTimeError || this.attendanceValidationErrors.length > 0;
        },

        formatTimeDisplay(timeVal) {
            if (!timeVal) return '';
            const parts = timeVal.split(':');
            if (parts.length < 2) return timeVal;
            let h = parseInt(parts[0], 10);
            const m = parts[1];
            const ampm = h >= 12 ? 'PM' : 'AM';
            let hour12 = h % 12;
            if (hour12 === 0) hour12 = 12;
            return `${String(hour12).padStart(2, '0')}:${m} ${ampm}`;
        }
    }));
});
</script>
</x-layouts.admin>
