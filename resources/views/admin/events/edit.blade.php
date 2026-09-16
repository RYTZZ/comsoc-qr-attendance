<x-layouts.admin :title="'Edit Event'">
<div class="page-header">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.events.show', $event) }}" class="btn-secondary btn-sm">← Back</a>
        <h1 class="page-title">Edit Event: {{ $event->name }}</h1>
    </div>
</div>

<div class="card max-w-2xl">
    <form method="POST" action="{{ route('admin.events.update', $event) }}" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label for="name" class="label">Event Name <span class="text-red-400">*</span></label>
            <input id="name" name="name" type="text" value="{{ old('name', $event->name) }}" required class="input" />
            @error('name')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="description" class="label">Description</label>
            <textarea id="description" name="description" rows="3" class="input">{{ old('description', $event->description) }}</textarea>
            @error('description')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="date" class="label">Date <span class="text-red-400">*</span></label>
                <input id="date" name="date" type="date" value="{{ old('date', $event->date?->format('Y-m-d')) }}" required class="input" />
                @error('date')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="location" class="label">Location / Venue</label>
                <input id="location" name="location" type="text" value="{{ old('location', $event->location) }}" class="input" />
                @error('location')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="border-t border-slate-800 pt-4 space-y-3">
            <div class="flex items-center gap-2">
                <input type="checkbox" id="is_published" name="is_published" value="1" {{ old('is_published', $event->is_published) ? 'checked' : '' }} class="rounded border-slate-700 bg-slate-900 text-brand-600 focus:ring-brand-500" />
                <label for="is_published" class="text-xs text-slate-300">Published (visible on kiosks and public listings)</label>
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" id="requires_registration" name="requires_registration" value="1" {{ old('requires_registration', $event->requires_registration) ? 'checked' : '' }} class="rounded border-slate-700 bg-slate-900 text-brand-600 focus:ring-brand-500" />
                <label for="requires_registration" class="text-xs text-slate-300">Requires prior registration</label>
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" id="allow_non_students" name="allow_non_students" value="1" {{ old('allow_non_students', $event->allow_non_students) ? 'checked' : '' }} class="rounded border-slate-700 bg-slate-900 text-brand-600 focus:ring-brand-500" />
                <label for="allow_non_students" class="text-xs text-slate-300">Allow non-student guest registration</label>
            </div>
        </div>

        <div class="pt-4 flex justify-end gap-3">
            <a href="{{ route('admin.events.show', $event) }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">Update Event</button>
        </div>
    </form>
</div>
</x-layouts.admin>
