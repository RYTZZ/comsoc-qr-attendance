<x-layouts.admin :title="'Create Event'">
<div class="page-header">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.events.index') }}" class="btn-secondary btn-sm">← Events</a>
        <div>
            <h1 class="page-title">Create New Event</h1>
            <p class="text-xs text-slate-400 mt-0.5">Super Admin Event Creation & Initial Setup</p>
        </div>
    </div>
</div>

@if($errors->any())
    <div class="mb-5 p-4 rounded-xl bg-red-500/10 border border-red-500/30 text-red-300 text-xs space-y-1">
        <p class="font-bold text-red-400">Please correct the following:</p>
        <ul class="list-disc list-inside space-y-0.5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('admin.events.store') }}" enctype="multipart/form-data" class="space-y-6 max-w-4xl"
      x-data="eventCreateForm({ eventDate: '{{ old('event_date') }}' })">
    @csrf

    <div class="card space-y-4">
        <div class="border-b border-slate-800 pb-3 flex items-center justify-between">
            <h2 class="section-title text-base font-brand-display">Event Information</h2>
            <span class="text-[11px] text-slate-500 font-mono">Basic Details</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
                <label for="name" class="label">Event Name <span class="text-red-400">*</span></label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required class="input" placeholder="e.g. General Assembly 2026" />
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

    <div class="card space-y-4">
        <div class="border-b border-slate-800 pb-3 flex items-center justify-between">
            <h2 class="section-title text-base font-brand-display">Schedule & Venue</h2>
            <span class="text-[11px] text-slate-500 font-mono">Timing & Location</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
            <div>
                <label for="event_date" class="label">Event Date <span class="text-red-400">*</span></label>
                <input id="event_date" name="event_date" type="date" x-model="eventDate" required class="input" />
                @error('event_date')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="label">Event Day</label>
                <div class="input bg-slate-950/80 border-slate-800 text-slate-300 font-medium select-none flex items-center" x-text="computedDay || 'Auto-calculated from date'">
                    Auto-calculated from date
                </div>
            </div>

            <div class="sm:col-span-2 md:col-span-1">
                <label class="label">Schedule Preview</label>
                <div class="p-2.5 rounded-xl bg-slate-950/60 border border-slate-800 text-xs text-slate-300 font-mono" x-text="formattedScheduleText || 'Date TBA'">
                    Date TBA
                </div>
            </div>

            <div>
                <label for="starts_at" class="label">Start Date & Time</label>
                <input id="starts_at" name="starts_at" type="datetime-local" value="{{ old('starts_at') }}" class="input" />
                @error('starts_at')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="ends_at" class="label">End Date & Time</label>
                <input id="ends_at" name="ends_at" type="datetime-local" value="{{ old('ends_at') }}" class="input" />
                @error('ends_at')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="max_participants" class="label">Participant Limit</label>
                <input id="max_participants" name="max_participants" type="number" min="1" value="{{ old('max_participants') }}" class="input" placeholder="Leave empty for unlimited" />
                @error('max_participants')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="venue_name" class="label">Venue / Facility Name</label>
                <input id="venue_name" name="venue_name" type="text" value="{{ old('venue_name') }}" class="input" placeholder="e.g. University Gymnasium" />
                @error('venue_name')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="venue_address" class="label">Venue Address / Campus</label>
                <input id="venue_address" name="venue_address" type="text" value="{{ old('venue_address') }}" class="input" placeholder="e.g. Main Campus, Sorsogon City" />
                @error('venue_address')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="sm:col-span-2 md:col-span-3">
                <label for="venue_details" class="label">Specific Venue Details</label>
                <textarea id="venue_details" name="venue_details" rows="2" class="input resize-none" placeholder="e.g. 2nd Floor, Left Wing. Please wear society shirts.">{{ old('venue_details') }}</textarea>
                @error('venue_details')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    <div class="card space-y-4">
        <div class="border-b border-slate-800 pb-3 flex items-center justify-between">
            <h2 class="section-title text-base font-brand-display">Registration & Lifecycle</h2>
            <span class="text-[11px] text-slate-500 font-mono">Initial Status</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="registration_opens_at" class="label">Registration Opens Date & Time</label>
                <input id="registration_opens_at" name="registration_opens_at" type="datetime-local" value="{{ old('registration_opens_at') }}" class="input" />
                @error('registration_opens_at')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="registration_deadline" class="label">Registration Closes Date & Time</label>
                <input id="registration_deadline" name="registration_deadline" type="datetime-local" value="{{ old('registration_deadline') }}" class="input" />
                @error('registration_deadline')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
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

    <div class="flex justify-end gap-3 pt-2">
        <a href="{{ route('admin.events.index') }}" class="btn-secondary">Cancel</a>
        <button type="submit" class="btn-primary">Create & Proceed to Full Configuration</button>
    </div>
</form>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('eventCreateForm', (config) => ({
        eventDate: config.eventDate || '',

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

