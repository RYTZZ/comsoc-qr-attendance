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

    <div class="card space-y-4">
        <div class="border-b border-slate-800 pb-3 flex items-center justify-between">
            <h2 class="section-title text-base font-brand-display">2. Schedule</h2>
            <span class="text-[11px] text-slate-500 font-mono">Date, Day & Times</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
            <div>
                <label for="event_date" class="label">Event Date <span class="text-red-400">*</span></label>
                <input id="event_date" name="event_date" type="date" x-model="eventDate" required class="input" />
                @error('event_date')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="label">Event Day</label>
                <div class="input bg-slate-950/80 border-slate-800 text-slate-300 font-medium select-none flex items-center" x-text="computedDay || 'Select a date above'">
                    {{ $event->event_day ?? '—' }}
                </div>
            </div>

            <div class="sm:col-span-2 md:col-span-1">
                <label class="label">Schedule Preview</label>
                <div class="p-2.5 rounded-xl bg-slate-950/60 border border-slate-800 text-xs text-slate-300 font-mono" x-text="formattedScheduleText || 'Date TBA'">
                    {{ $event->formatted_schedule_day }}
                </div>
            </div>

            <div>
                <label for="starts_at" class="label">Start Date & Time</label>
                <input id="starts_at" name="starts_at" type="datetime-local" value="{{ old('starts_at', $event->starts_at?->format('Y-m-d\TH:i')) }}" class="input" />
                @error('starts_at')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="ends_at" class="label">End Date & Time</label>
                <input id="ends_at" name="ends_at" type="datetime-local" value="{{ old('ends_at', $event->ends_at?->format('Y-m-d\TH:i')) }}" class="input" />
                @error('ends_at')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

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

            <div>
                <label for="attendance_starts_at" class="label">Attendance Window Opens</label>
                <input id="attendance_starts_at" name="attendance_starts_at" type="time" value="{{ old('attendance_starts_at', $event->attendance_starts_at) }}" class="input" />
                @error('attendance_starts_at')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="attendance_ends_at" class="label">Attendance Window Closes</label>
                <input id="attendance_ends_at" name="attendance_ends_at" type="time" value="{{ old('attendance_ends_at', $event->attendance_ends_at) }}" class="input" />
                @error('attendance_ends_at')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    <div class="card space-y-4">
        <div class="border-b border-slate-800 pb-3 flex items-center justify-between">
            <h2 class="section-title text-base font-brand-display">3. Venue</h2>
            <span class="text-[11px] text-slate-500 font-mono">Location & Room Details</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="venue_name" class="label">Venue / Facility Name</label>
                <input id="venue_name" name="venue_name" type="text" value="{{ old('venue_name', $event->venue_name ?: $event->location) }}" class="input" placeholder="e.g. University Gymnasium / Audio-Visual Hall" />
                @error('venue_name')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="venue_address" class="label">Venue Address / Campus</label>
                <input id="venue_address" name="venue_address" type="text" value="{{ old('venue_address', $event->venue_address) }}" class="input" placeholder="e.g. Main Campus, Sorsogon City" />
                @error('venue_address')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="sm:col-span-2">
                <label for="venue_details" class="label">Specific Venue Details / Room Instructions</label>
                <textarea id="venue_details" name="venue_details" rows="2" class="input resize-none" placeholder="e.g. 2nd Floor, Left Wing. Please wear university ID at gate checkpoint.">{{ old('venue_details', $event->venue_details) }}</textarea>
                @error('venue_details')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    <div class="card space-y-4">
        <div class="border-b border-slate-800 pb-3 flex items-center justify-between">
            <h2 class="section-title text-base font-brand-display">4. Registration</h2>
            <span class="text-[11px] text-slate-500 font-mono">Policies & Deadlines</span>
        </div>

        <div class="space-y-3">
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

    <div class="card space-y-4">
        <div class="border-b border-slate-800 pb-3 flex items-center justify-between">
            <h2 class="section-title text-base font-brand-display">5. Attendance Configuration</h2>
            <span class="text-[11px] text-slate-500 font-mono">Sessions & Punctuality</span>
        </div>

        <label class="flex items-start gap-3 p-3 rounded-xl bg-slate-950/60 border border-slate-800 cursor-pointer hover:border-slate-700 transition">
            <input type="checkbox" name="attendance_enabled" value="1" x-model="attendanceEnabled" class="mt-0.5 w-4 h-4 rounded bg-[#12141c] border-slate-700 text-[#7A1618] focus:ring-[#7A1618]" />
            <div>
                <span class="text-sm font-semibold text-white">Attendance Enabled</span>
                <p class="text-xs text-slate-400 mt-0.5">Enable kiosk checkpoint scanning and presence verification for this event.</p>
            </div>
        </label>

        <div x-show="attendanceEnabled" class="space-y-3 pt-2">
            <p class="text-xs text-slate-400">Configure opening, closing, and late thresholds for checkpoint intervals:</p>

            @php
                $sessionTypes = [
                    'morning_in' => ['title' => 'Morning IN', 'desc' => 'Morning arrival checkpoint'],
                    'morning_out' => ['title' => 'Morning OUT', 'desc' => 'Lunch dismissal checkpoint'],
                    'afternoon_in' => ['title' => 'Afternoon IN', 'desc' => 'Afternoon session entry checkpoint'],
                    'afternoon_out' => ['title' => 'Afternoon OUT', 'desc' => 'Event closing departure checkpoint'],
                ];
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @foreach($sessionTypes as $key => $info)
                    @php
                        $existingSession = $event->attendanceSessions->firstWhere('type', $key);
                    @endphp
                    <div class="p-3.5 rounded-xl bg-slate-950/80 border border-slate-800 space-y-3" x-data="{ enabled: {{ $existingSession ? 'true' : 'false' }} }">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-800/80">
                            <div>
                                <span class="text-xs font-bold text-white uppercase tracking-wider">{{ $info['title'] }}</span>
                                <p class="text-[10px] text-slate-500">{{ $info['desc'] }}</p>
                            </div>
                            <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-[#dfa6a9]">
                                <input type="checkbox" name="sessions[{{ $key }}][enabled]" value="1" x-model="enabled" class="rounded bg-[#12141c] border-slate-700 text-[#7A1618] focus:ring-[#7A1618]" />
                                Active
                            </label>
                        </div>

                        <div x-show="enabled" class="grid grid-cols-3 gap-2 text-xs">
                            <div>
                                <label class="label text-[10px]">Opens At *</label>
                                <input type="time" name="sessions[{{ $key }}][opens_at]" value="{{ old("sessions.$key.opens_at", $existingSession?->opens_at) }}" class="input py-1 text-xs" :required="enabled" />
                            </div>
                            <div>
                                <label class="label text-[10px]">Closes At *</label>
                                <input type="time" name="sessions[{{ $key }}][closes_at]" value="{{ old("sessions.$key.closes_at", $existingSession?->closes_at) }}" class="input py-1 text-xs" :required="enabled" />
                            </div>
                            <div>
                                <label class="label text-[10px]">Late Threshold</label>
                                <input type="time" name="sessions[{{ $key }}][late_threshold]" value="{{ old("sessions.$key.late_threshold", $existingSession?->late_threshold) }}" class="input py-1 text-xs" />
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="card space-y-4">
        <div class="border-b border-slate-800 pb-3 flex items-center justify-between">
            <h2 class="section-title text-base font-brand-display">6. Participant Limit</h2>
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
            <h2 class="section-title text-base font-brand-display">7. Additional Settings</h2>
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

    <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
        <a href="{{ route('admin.events.index') }}" class="btn-secondary">Cancel</a>
        <div class="flex items-center gap-2">
            <button type="submit" class="btn-primary">Save Event Configuration</button>
        </div>
    </div>
</form>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('eventConfig', (config) => ({
        eventDate: config.eventDate || '',
        requiresReg: config.requiresReg,
        attendanceEnabled: config.attendanceEnabled,

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
        }
    }));
});
</script>
</x-layouts.admin>
