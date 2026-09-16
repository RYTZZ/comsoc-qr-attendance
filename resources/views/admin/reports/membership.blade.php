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
        <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" class="btn-primary text-xs">Export PDF</a>
    </div>
</div>

<div class="card mb-6">
    <form method="GET" class="flex flex-wrap items-end gap-3">
        <div>
            <label class="label text-[11px]">Academic Year</label>
            <select name="academic_year_id" class="input py-1.5 text-xs">
                <option value="">-- All Years --</option>
                @foreach($academicYears as $ay)
                    <option value="{{ $ay->id }}" {{ request('academic_year_id') == $ay->id ? 'selected' : '' }}>{{ $ay->label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label text-[11px]">Status</label>
            <select name="status" class="input py-1.5 text-xs">
                <option value="">-- All Statuses --</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>
        <button type="submit" class="btn-secondary text-xs py-1.5">Filter</button>
    </form>
</div>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="bg-slate-950/60 text-slate-400 border-b border-slate-800">
                <tr>
                    <th class="py-3 px-4">Student Name</th>
                    <th class="py-3 px-4">Student #</th>
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
                        <td colspan="5" class="py-8 text-center text-slate-500">No records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</x-layouts.admin>
