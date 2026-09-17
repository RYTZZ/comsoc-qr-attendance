<x-layouts.admin :title="'Masterlist Preview'">
<div class="page-header">
    <div>
        <h1 class="page-title">Masterlist Preview — {{ $academicYear->label }}</h1>
        <p class="text-xs text-slate-400 mt-1">Carefully review classified records before confirming permanent database import.</p>
    </div>
    <a href="{{ route('admin.masterlist.upload') }}" class="btn-secondary btn-sm">← Re-upload File</a>
</div>

<div class="mb-6">
    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-900 border border-slate-800 text-xs">
        <div class="flex items-center gap-2 text-emerald-400 font-semibold">
            <span class="w-6 h-6 rounded-full bg-emerald-600/30 border border-emerald-500/50 text-emerald-300 flex items-center justify-center">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            </span>
            <span>Upload</span>
        </div>
        <div class="h-0.5 flex-1 mx-3 bg-emerald-500/30"></div>
        <div class="flex items-center gap-2 text-indigo-400 font-semibold">
            <span class="w-6 h-6 rounded-full bg-indigo-600 text-white flex items-center justify-center text-[11px] shadow">2</span>
            <span>Review</span>
        </div>
        <div class="h-0.5 flex-1 mx-3 bg-slate-800"></div>
        <div class="flex items-center gap-2 text-slate-500 font-medium">
            <span class="w-6 h-6 rounded-full bg-slate-800 text-slate-400 flex items-center justify-center text-[11px]">3</span>
            <span>Confirm</span>
        </div>
        <div class="h-0.5 flex-1 mx-3 bg-slate-800"></div>
        <div class="flex items-center gap-2 text-slate-500 font-medium">
            <span class="w-6 h-6 rounded-full bg-slate-800 text-slate-400 flex items-center justify-center text-[11px]">4</span>
            <span>Completed</span>
        </div>
    </div>
</div>

<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    <div class="card-sm text-center">
        <p class="text-2xl font-bold text-emerald-400">{{ count($preview['new']) }}</p>
        <p class="text-xs text-slate-500 mt-1">New Students</p>
    </div>
    <div class="card-sm text-center">
        <p class="text-2xl font-bold text-blue-400">{{ count($preview['existing']) }}</p>
        <p class="text-xs text-slate-500 mt-1">Existing Students</p>
    </div>
    <div class="card-sm text-center">
        <p class="text-2xl font-bold text-amber-400">{{ count($preview['duplicates']) }}</p>
        <p class="text-xs text-slate-500 mt-1">Skipped Duplicates</p>
    </div>
    <div class="card-sm text-center">
        <p class="text-2xl font-bold text-red-400">{{ count($preview['invalid']) }}</p>
        <p class="text-xs text-slate-500 mt-1">Invalid Records</p>
    </div>
</div>

@if(count($preview['invalid']) > 0)
<div class="alert-error mb-4">
    <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
    <span>{{ count($preview['invalid']) }} records are invalid and will be skipped. Check your file format.</span>
</div>
@endif

<div class="card mb-6" x-data="{ tab: 'new' }">
    <div class="flex gap-2 mb-4 border-b border-slate-800 -mx-6 px-6 pb-0">
        @foreach(['new' => 'New (' . count($preview['new']) . ')', 'existing' => 'Existing (' . count($preview['existing']) . ')', 'duplicates' => 'Skipped (' . count($preview['duplicates']) . ')', 'invalid' => 'Invalid (' . count($preview['invalid']) . ')'] as $key => $label)
        <button @click="tab = '{{ $key }}'"
                :class="tab === '{{ $key }}' ? 'border-b-2 border-indigo-500 text-white' : 'text-slate-500 hover:text-slate-300'"
                class="px-4 py-3 text-sm font-medium transition-colors -mb-px">
            {{ $label }}
        </button>
        @endforeach
    </div>

    @foreach(['new', 'existing', 'duplicates', 'invalid'] as $section)
    <div x-show="tab === '{{ $section }}'">
        @if(count($preview[$section]) > 0)
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Student Number</th>
                        <th>Last Name</th>
                        <th>First Name</th>
                        <th>Middle Name</th>
                        @if($section === 'invalid')<th>Reason</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @foreach(array_slice($preview[$section], 0, 50) as $row)
                    <tr>
                        <td class="font-mono">{{ $row['student_number'] ?? '—' }}</td>
                        <td>{{ $row['last_name'] ?? '—' }}</td>
                        <td>{{ $row['first_name'] ?? '—' }}</td>
                        <td>{{ $row['middle_name'] ?? '—' }}</td>
                        @if($section === 'invalid')<td class="text-red-400 text-xs">{{ $row['reason'] ?? '—' }}</td>@endif
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if(count($preview[$section]) > 50)
        <p class="text-xs text-slate-500 mt-2 px-1">Showing first 50 of {{ count($preview[$section]) }} records.</p>
        @endif
        @else
        <div class="empty-state py-6">
            <p class="empty-state-title">No records in this category</p>
        </div>
        @endif
    </div>
    @endforeach
</div>

@if(count($preview['new']) + count($preview['existing']) > 0)
<div class="card border-indigo-500/30 bg-indigo-950/20">
    <h2 class="section-title">Confirm Import</h2>
    <p class="text-sm text-slate-400 mb-4">
        This will import <strong class="text-white">{{ count($preview['new']) }} new students</strong>
        and link <strong class="text-white">{{ count($preview['existing']) }} existing students</strong>
        to <strong class="text-white">{{ $academicYear->label }}</strong>.
        Duplicates and invalid records will be skipped.
    </p>
    <form method="POST" action="{{ route('admin.masterlist.import') }}">
        @csrf
        <button type="submit" class="btn-primary"
                onclick="return confirm('Confirm masterlist import for {{ $academicYear->label }}? This cannot be undone.')">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            Confirm Import
        </button>
    </form>
</div>
@endif
</x-layouts.admin>
