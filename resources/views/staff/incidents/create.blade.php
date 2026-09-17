<x-layouts.admin :title="'Report Incident'">
<div class="page-header">
    <div class="flex items-center gap-3">
        <a href="{{ route('staff.incidents.my') }}" class="btn-secondary btn-sm">← My Reports</a>
        <h1 class="page-title">File Incident Report</h1>
    </div>
</div>

<div class="card max-w-2xl">
    <form method="POST" action="{{ route('staff.incidents.store') }}" class="space-y-4">
        @csrf

        <div>
            <label for="event_id" class="label">Associated Event (Optional)</label>
            @php
                $evOpts = ['' => '-- No Specific Event --'];
                foreach($events as $event) {
                    $evDate = $event->date ? $event->date->format('M d, Y') : 'TBA';
                    $evOpts[$event->id] = "{$event->name} ({$evDate})";
                }
            @endphp
            <x-custom-dropdown
                name="event_id"
                id="event_id"
                :options="$evOpts"
                :value="old('event_id', '')"
                placeholder="-- No Specific Event --" />
            @error('event_id')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="category" class="label">Incident Category <span class="text-red-400">*</span></label>
            <x-custom-dropdown
                name="category"
                id="category"
                :options="[
                    'qr_identity_issue' => 'QR / Identity Mismatch or Fake Token',
                    'attendance_issue' => 'Attendance Discrepancy',
                    'disruptive_conduct' => 'Disruptive Conduct',
                    'harassment_bullying' => 'Harassment / Bullying',
                    'property_issue' => 'Property Damage / Theft',
                    'safety_concern' => 'Safety / Health Concern',
                    'other' => 'Other',
                ]"
                :value="old('category', 'qr_identity_issue')"
                :required="true"
                placeholder="Select category…" />
            @error('category')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="description" class="label">Incident Narrative / Evidence <span class="text-red-400">*</span></label>
            <textarea id="description" name="description" rows="5" required class="input" placeholder="Provide factual details (what happened, where, student numbers involved, witnesses)...">{{ old('description') }}</textarea>
            <p class="text-[11px] text-slate-500 mt-1">Minimum 20 characters required.</p>
            @error('description')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="pt-4 flex justify-end gap-3">
            <a href="{{ route('staff.incidents.my') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">Submit Incident Report</button>
        </div>
    </form>
</div>
</x-layouts.admin>
