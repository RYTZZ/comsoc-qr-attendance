<x-layouts.admin :title="'Membership Report'">
<div class="page-header">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.reports.index') }}" class="btn-secondary btn-sm">← Reports</a>
        <div>
            <h1 class="page-title">Membership Report</h1>
            <p class="text-xs text-slate-400 mt-0.5">Filter and export official society membership records.</p>
        </div>
    </div>
    <div class="flex gap-2">
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn-secondary text-xs">Export CSV</a>
        <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" class="btn-primary text-xs">Export PDF</a>
    </div>
</div>

<div class="card mb-6">
    <form method="GET" class="flex flex-wrap items-end gap-3">
        <div class="w-full sm:w-48">
            <label class="label text-[11px]">Academic Year</label>
            @php
                $ayOpts = ['' => 'All Years'];
                foreach($academicYears as $ay) {
                    $ayOpts[$ay->id] = $ay->label;
                }
            @endphp
            <x-custom-dropdown
                name="academic_year_id"
                :options="$ayOpts"
                :value="request('academic_year_id', '')"
                placeholder="All Years"
                buttonClass="py-1.5 text-xs" />
        </div>
        <div class="w-full sm:w-40">
            <label class="label text-[11px]">Status</label>
            <x-custom-dropdown
                name="status"
                :options="[
                    '' => 'All Statuses',
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                ]"
                :value="request('status', '')"
                placeholder="All Statuses"
                buttonClass="py-1.5 text-xs" />
        </div>
        <button type="submit" class="btn-secondary text-xs py-1.5 mt-2 sm:mt-0">Filter</button>
    </form>
</div>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="bg-slate-950/60 text-slate-400 border-b border-slate-800">
                <tr>
                    <th class="py-3 px-4">Student Name</th>
                    <th class="py-3 px-4">Student #</th>
                    <th class="py-3 px-4">Program & Year</th>
                    <th class="py-3 px-4">Academic Year</th>
                    <th class="py-3 px-4">Status</th>
                    <th class="py-3 px-4">Enrolled At</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
                @forelse($data as $mem)
                    <tr class="hover:bg-slate-800/30 transition">
                        <td class="py-3 px-4 font-semibold text-white">{{ $mem->student?->display_name ?? '—' }}</td>
                        <td class="py-3 px-4 text-slate-300 font-mono">{{ $mem->student?->student_number ?? '—' }}</td>
                        <td class="py-3 px-4 text-slate-300">
                            @if($mem->student)
                                <span class="text-white font-medium">{{ $mem->student->year_level ?? '—' }}</span>
                                <span class="text-slate-500">•</span>
                                <span class="text-slate-400 text-[11px]">{{ $mem->student->program ?: 'BSIT' }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="py-3 px-4 text-slate-300">{{ $mem->academicYear?->label ?? '—' }}</td>
                        <td class="py-3 px-4">
                            <span class="badge {{ $mem->status === 'active' ? 'badge-active' : 'badge-inactive' }}">
                                {{ ucfirst($mem->status) }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-slate-400">{{ $mem->created_at->format('M j, Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-500">No records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</x-layouts.admin>
