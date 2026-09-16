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
            <select id="event_id" name="event_id" class="input">
                <option value="">-- No Specific Event --</option>
                @foreach($events as $event)
                    <option value="{{ $event->id }}" {{ old('event_id') == $event->id ? 'selected' : '' }}>
                        {{ $event->name }} ({{ $event->date ? $event->date->format('M d, Y') : 'TBA' }})
                    </option>
                @endforeach
            </select>
            @error('event_id')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="category" class="label">Incident Category <span class="text-red-400">*</span></label>
            <select id="category" name="category" required class="input">
                <option value="qr_identity_issue" {{ old('category') === 'qr_identity_issue' ? 'selected' : '' }}>QR / Identity Mismatch or Fake Token</option>
                <option value="attendance_issue" {{ old('category') === 'attendance_issue' ? 'selected' : '' }}>Attendance Discrepancy</option>
                <option value="disruptive_conduct" {{ old('category') === 'disruptive_conduct' ? 'selected' : '' }}>Disruptive Conduct</option>
                <option value="harassment_bullying" {{ old('category') === 'harassment_bullying' ? 'selected' : '' }}>Harassment / Bullying</option>
                <option value="property_issue" {{ old('category') === 'property_issue' ? 'selected' : '' }}>Property Damage / Theft</option>
                <option value="safety_concern" {{ old('category') === 'safety_concern' ? 'selected' : '' }}>Safety / Health Concern</option>
                <option value="other" {{ old('category') === 'other' ? 'selected' : '' }}>Other</option>
            </select>
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
