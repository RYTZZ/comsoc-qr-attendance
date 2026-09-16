<x-layouts.admin :title="'Edit Academic Year'">
<div class="page-header">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.academic-years.index') }}" class="btn-secondary btn-sm">← Back</a>
        <h1 class="page-title">Edit Academic Year</h1>
    </div>
</div>

<div class="card max-w-xl">
    <form method="POST" action="{{ route('admin.academic-years.update', $academicYear) }}" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label for="label" class="label">Label / Academic Year Name <span class="text-red-400">*</span></label>
            <input id="label" name="label" type="text" value="{{ old('label', $academicYear->label) }}" required class="input" />
            @error('label')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="start_date" class="label">Start Date</label>
                <input id="start_date" name="start_date" type="date" value="{{ old('start_date', $academicYear->start_date?->format('Y-m-d')) }}" class="input" />
                @error('start_date')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="end_date" class="label">End Date</label>
                <input id="end_date" name="end_date" type="date" value="{{ old('end_date', $academicYear->end_date?->format('Y-m-d')) }}" class="input" />
                @error('end_date')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="pt-4 flex justify-end gap-3">
            <a href="{{ route('admin.academic-years.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">Update Academic Year</button>
        </div>
    </form>
</div>
</x-layouts.admin>
