<x-layouts.admin :title="'Create New Event'">
<div class="page-header">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.events.index') }}" class="btn-secondary btn-sm">← Events</a>
        <div>
            <h1 class="page-title">Create New Event</h1>
            <p class="text-xs text-slate-400 mt-0.5">Define core event details, schedule & venue before fine-tuning attendance rules.</p>
        </div>
    </div>
</div>

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

<form method="POST" action="{{ route('admin.events.store') }}" enctype="multipart/form-data" class="space-y-6 max-w-4xl"
      x-data="eventCreateForm({
          eventDate: '{{ old('event_date') }}',
          eventStartTime: '{{ old('event_start_time', '08:00') }}',
          eventEndTime: '{{ old('event_end_time', '17:00') }}'
      })">
    @csrf

    <div class="card space-y-4">
        <div class="border-b border-slate-800 pb-3 flex items-center justify-between">
            <h2 class="section-title text-base font-brand-display">1. Event Information</h2>
            <span class="text-[11px] text-slate-500 font-mono">Core Details</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
                <label for="name" class="label">Event Name <span class="text-red-400">*</span></label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required class="input" placeholder="e.g. Annual Computing Convention 2026" />
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
                <x-custom-dropdown name="academic_year_id" :options="$yearChoices" :value="old('academic_year_id')" placeholder="Select Academic Year" :required="true" />
                @error('academic_year_id')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="label">Event Type</label>
                <x-custom-dropdown name="event_type" :options="$typeChoices" :value="old('event_type')" placeholder="Select Event Type" />
                @error('event_type')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="organizer" class="label">Organizer / Committee</label>
                <input id="organizer" name="organizer" type="text" value="{{ old('organizer', 'Computing Society') }}" class="input" placeholder="e.g. ComSoc Executive Board" />
                @error('organizer')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="contact_info" class="label">Contact Information</label>
                <input id="contact_info" name="contact_info" type="text" value="{{ old('contact_info') }}" class="input" placeholder="e.g. comsoc@university.edu" />
                @error('contact_info')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="sm:col-span-2">
                <label for="description" class="label">Description</label>
                <textarea id="description" name="description" rows="3" class="input resize-none" placeholder="Provide complete event agenda and highlights…">{{ old('description') }}</textarea>
                @error('description')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="sm:col-span-2">
                <label class="label">Event Logo / Banner</label>
                <input type="file" name="logo" accept="image/*" class="input py-2 text-xs file:mr-3 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-[#7A1618] file:text-white hover:file:bg-[#921c1f]" />
                <p class="text-[11px] text-slate-500 mt-1">PNG, JPG, or WebP up to 3MB. If empty, the official ComSoc logo will be used.</p>
                @error('logo')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    <div class="card space-y-6">
        <div class="border-b border-slate-800 pb-3 flex items-center justify-between">
            <h2 class="section-title text-base font-brand-display">2. Schedule & Venue</h2>
            <span class="text-[11px] text-slate-500 font-mono">Date, Operating Hours & Physical Site</span>
        </div>

        <div class="space-y-4">
            <div class="p-4 rounded-xl bg-slate-950/70 border border-slate-800/90 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-300 flex items-center gap-2">
                        <i data-lucide="calendar" class="w-3.5 h-3.5 text-[#e07e83]"></i>
                        Event Date
                    </span>
                    <span class="text-[11px] font-mono font-medium px-2 py-0.5 rounded bg-slate-900 border border-slate-800 text-[#e07e83]"
                          x-text="computedDay || 'Awaiting selection'">
                        —
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-center">
                    <div>
                        <input id="event_date" name="event_date" type="date" x-model="eventDate" required class="input font-mono text-sm" />
                        @error('event_date')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg bg-slate-900/90 border border-slate-800 text-xs">
                        <i data-lucide="calendar-days" class="w-4 h-4 text-[#dfa6a9] shrink-0"></i>
                        <div class="truncate">
                            <span class="text-slate-400 block text-[10px] uppercase font-semibold">Selected Event Date</span>
                            <span class="text-white font-medium truncate" x-text="formattedScheduleText || 'No date chosen yet'">
                                No date chosen yet
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
                    <span class="text-[11px] text-slate-400">Start and expected wrap-up of activities</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="event_start_time" class="label">Start Time <span class="text-red-400">*</span></label>
                        <x-time-picker name="event_start_time" id="event_start_time" :value="old('event_start_time', '08:00')" placeholder="08:00 AM" />
                        @error('starts_at')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="event_end_time" class="label">End Time</label>
                        <x-time-picker name="event_end_time" id="event_end_time" :value="old('event_end_time', '17:00')" placeholder="05:00 PM" />
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
                        Venue & Physical Site
                    </span>
                    <span class="text-[11px] text-slate-400">Campus & room instructions</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="venue_name" class="label">Venue / Facility Name <span class="text-red-400">*</span></label>
                        <div class="relative">
                            <input id="venue_name" name="venue_name" type="text" value="{{ old('venue_name') }}" required class="input pl-9" placeholder="e.g. SorSU Bulan Campus Social Hall" />
                            <i data-lucide="building" class="w-4 h-4 text-slate-500 absolute left-3 top-3 pointer-events-none"></i>
                        </div>
                        <p class="text-[10px] text-slate-500 mt-1">Primary building or hall where attendance and activities take place.</p>
                        @error('venue_name')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="venue_address" class="label">Campus / Specific Address</label>
                        <div class="relative">
                            <input id="venue_address" name="venue_address" type="text" value="{{ old('venue_address') }}" class="input pl-9" placeholder="e.g. Zone 8, Bulan, Sorsogon" />
                            <i data-lucide="map" class="w-4 h-4 text-slate-500 absolute left-3 top-3 pointer-events-none"></i>
                        </div>
                        <p class="text-[10px] text-slate-500 mt-1">Geographic campus location or barangay address.</p>
                        @error('venue_address')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="venue_details" class="label">Additional Room & Gate Directions</label>
                        <textarea id="venue_details" name="venue_details" rows="2" class="input resize-none" placeholder="e.g. 2nd Floor, Left Wing. Please wear society shirts and present student ID at the entrance.">{{ old('venue_details') }}</textarea>
                        @error('venue_details')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card space-y-4">
        <div class="border-b border-slate-800 pb-3 flex items-center justify-between">
            <h2 class="section-title text-base font-brand-display">3. Registration & Lifecycle</h2>
            <span class="text-[11px] text-slate-500 font-mono">Deadlines & Capacity</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label for="registration_opens_at" class="label">Registration Opens</label>
                <input id="registration_opens_at" name="registration_opens_at" type="datetime-local" value="{{ old('registration_opens_at') }}" class="input" />
                @error('registration_opens_at')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="registration_deadline" class="label">Registration Closes</label>
                <input id="registration_deadline" name="registration_deadline" type="datetime-local" value="{{ old('registration_deadline') }}" class="input" />
                @error('registration_deadline')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="max_participants" class="label">Participant Limit</label>
                <input id="max_participants" name="max_participants" type="number" min="1" value="{{ old('max_participants') }}" class="input" placeholder="Leave empty for unlimited" />
                @error('max_participants')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="space-y-3 pt-2">
            <label class="flex items-start gap-3 p-3 rounded-xl bg-slate-950/60 border border-slate-800 cursor-pointer hover:border-slate-700 transition">
                <input type="checkbox" name="requires_registration" value="1" {{ old('requires_registration', '1') ? 'checked' : '' }} class="mt-0.5 w-4 h-4 rounded bg-[#12141c] border-slate-700 text-[#7A1618] focus:ring-[#7A1618]" />
                <div>
                    <span class="text-sm font-semibold text-white">Registration Required</span>
                    <p class="text-xs text-slate-400 mt-0.5">Require attendees to register before participating.</p>
                </div>
            </label>

            <label class="flex items-start gap-3 p-3 rounded-xl bg-slate-950/60 border border-slate-800 cursor-pointer hover:border-slate-700 transition">
                <input type="checkbox" name="allow_non_students" value="1" {{ old('allow_non_students', '1') ? 'checked' : '' }} class="mt-0.5 w-4 h-4 rounded bg-[#12141c] border-slate-700 text-[#7A1618] focus:ring-[#7A1618]" />
                <div>
                    <span class="text-sm font-semibold text-white">Non-Student Registration Enabled</span>
                    <p class="text-xs text-slate-400 mt-0.5">Enable external guests and non-students to register.</p>
                </div>
            </label>

            <label class="flex items-start gap-3 p-3 rounded-xl bg-slate-950/60 border border-slate-800 cursor-pointer hover:border-slate-700 transition">
                <input type="checkbox" name="is_published" value="1" {{ old('is_published') ? 'checked' : '' }} class="mt-0.5 w-4 h-4 rounded bg-[#12141c] border-slate-700 text-[#7A1618] focus:ring-[#7A1618]" />
                <div>
                    <span class="text-sm font-semibold text-white">Publish Event Immediately</span>
                    <p class="text-xs text-slate-400 mt-0.5">If unchecked, event will be saved as Draft.</p>
                </div>
            </label>
        </div>
    </div>

    <div class="flex items-center justify-end gap-3 pt-2">
        <a href="{{ route('admin.events.index') }}" class="btn-secondary">Cancel</a>
        <button type="submit" :disabled="hasBlockingErrors" class="btn-primary flex items-center gap-2">
            <i data-lucide="check" class="w-4 h-4"></i>
            <span>Create & Proceed to Full Configuration</span>
        </button>
    </div>
</form>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('eventCreateForm', (config) => ({
        eventDate: config.eventDate || '',
        eventStartTime: config.eventStartTime || '08:00',
        eventEndTime: config.eventEndTime || '17:00',

        init() {
            this.$el.addEventListener('time-change', (e) => {
                const { name, value } = e.detail;
                if (name === 'event_start_time') this.eventStartTime = value;
                if (name === 'event_end_time') this.eventEndTime = value;
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

        get hasBlockingErrors() {
            return !!this.eventTimeError;
        }
    }));
});
</script>
</x-layouts.admin>
