<x-layouts.admin :title="'Student Accounts'">
<div class="page-header">
    <div>
        <h1 class="page-title">Student Accounts</h1>
        <p class="text-xs text-slate-400 mt-0.5">
            @if($activeYear)
                Showing data for <span class="text-white font-semibold">{{ $activeYear->label }}</span>
            @else
                No active academic year set
            @endif
        </p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn-secondary text-xs flex items-center gap-1.5 shrink-0">
            <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5"></i>
            Export CSV
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert-success mb-4">{{ session('success') }}</div>
@endif
@if(session('info'))
<div class="alert-info mb-4">{{ session('info') }}</div>
@endif

<form method="GET" class="mb-4 flex flex-wrap gap-2 items-end">
    <input type="search" name="search" value="{{ request('search') }}"
           placeholder="Search name or student #…"
           class="input w-full sm:w-52 text-sm">
    <div class="w-full sm:w-36">
        @php
            $yearLevelOptions = ['' => 'All Year Levels'];
            foreach($yearLevels as $yl) {
                $yearLevelOptions[$yl] = $yl;
            }
        @endphp
        <x-custom-dropdown
            name="year_level"
            :options="$yearLevelOptions"
            :value="request('year_level', '')"
            placeholder="All Year Levels"
            buttonClass="py-2 text-xs sm:text-sm" />
    </div>
    <div class="w-full sm:w-36">
        <x-custom-dropdown
            name="membership_status"
            :options="[
                '' => 'All Membership',
                'active' => 'Active',
                'pending' => 'Pending',
                'none' => 'None',
            ]"
            :value="request('membership_status', '')"
            placeholder="All Membership"
            buttonClass="py-2 text-xs sm:text-sm" />
    </div>
    <div class="w-full sm:w-32">
        <x-custom-dropdown
            name="qr_status"
            :options="[
                '' => 'All QR',
                'active' => 'QR Active',
                'missing' => 'QR Missing',
                'none' => 'No QR',
            ]"
            :value="request('qr_status', '')"
            placeholder="All QR"
            buttonClass="py-2 text-xs sm:text-sm" />
    </div>
    <div class="w-full sm:w-44">
        @php
            $yearOpts = ['' => 'All Academic Years'];
            foreach($academicYears as $year) {
                $yearOpts[$year->id] = $year->label . ($year->is_active ? ' (Active)' : '');
            }
            $selectedYear = request('academic_year_id') ?: ($yearId ?? '');
        @endphp
        <x-custom-dropdown
            name="academic_year_id"
            :options="$yearOpts"
            :value="$selectedYear"
            placeholder="All Academic Years"
            buttonClass="py-2 text-xs sm:text-sm" />
    </div>
    <button type="submit" class="btn-secondary text-sm">Filter</button>
    <a href="{{ route('admin.students.index') }}" class="btn-secondary text-sm">Reset</a>
</form>

<div class="table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th>Student Number</th>
                <th>Name</th>
                <th>Program & Year</th>
                <th>Membership</th>
                <th>QR</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $student)
            @php
                $membership = $student->memberships->first();
                $activeQr = $membership?->activeQrCode;
            @endphp
            <tr>
                <td class="font-mono text-sm">{{ $student->student_number }}</td>
                <td class="font-medium text-white">{{ $student->full_name }}</td>
                <td>
                    <div class="flex flex-col">
                        <span class="text-white text-xs font-medium">{{ $student->year_level ?? '—' }}</span>
                        <span class="text-slate-400 text-[11px]">{{ $student->program ?? 'BSIT' }}</span>
                    </div>
                </td>
                <td>
                    @if($membership)
                    <span class="badge {{ $membership->status === 'active' ? 'badge-active' : 'badge-inactive' }}">
                        {{ ucfirst($membership->status) }}
                    </span>
                    @else
                    <span class="text-slate-600 text-xs">—</span>
                    @endif
                </td>
                <td>
                    @if($membership && $membership->status === 'active')
                        @if($activeQr)
                            <span class="badge-active text-xs">Active</span>
                        @else
                            <span class="text-amber-400 text-xs font-semibold">Missing</span>
                        @endif
                    @else
                        <span class="text-slate-600 text-xs">None</span>
                    @endif
                </td>
                <td>
                    <a href="{{ route('admin.students.show', $student) }}" class="btn-secondary btn-sm">Manage</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6">
                    <div class="empty-state">
                        <div class="empty-state-icon flex items-center justify-center">
                            <i data-lucide="users" class="w-8 h-8 text-slate-500"></i>
                        </div>
                        <p class="empty-state-title">No students found</p>
                        <p class="empty-state-body">Try adjusting your filters or upload a masterlist.</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $students->links() }}</div>
</x-layouts.admin>
