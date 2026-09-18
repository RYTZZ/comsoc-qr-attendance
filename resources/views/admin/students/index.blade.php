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

<div x-data="{
    searchVal: '{{ request('search') }}',
    submitDebounced() {
        $refs.filterForm.submit();
    }
}">
    <form x-ref="filterForm" method="GET" class="mb-3 flex flex-wrap gap-2 items-end">
        <div class="relative w-full sm:w-56">
            <input type="search" name="search" x-model="searchVal"
                   @input.debounce.400ms="submitDebounced()"
                   placeholder="Search name or student #…"
                   class="input w-full text-sm pl-8">
            <svg class="w-4 h-4 text-slate-500 absolute left-2.5 top-3 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
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

    @if(request()->hasAny(['search', 'year_level', 'membership_status', 'qr_status', 'academic_year_id']))
    <div class="flex flex-wrap items-center gap-1.5 mb-4">
        <span class="text-[11px] text-slate-400 font-medium mr-1">Active Filters:</span>
        @if(request('search'))
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-slate-800 text-slate-200 border border-slate-700">
                <span>Query: "{{ request('search') }}"</span>
                <a href="{{ request()->fullUrlWithQuery(['search' => null]) }}" class="hover:text-red-400">×</a>
            </span>
        @endif
        @if(request('year_level'))
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-slate-800 text-slate-200 border border-slate-700">
                <span>Year: {{ request('year_level') }}</span>
                <a href="{{ request()->fullUrlWithQuery(['year_level' => null]) }}" class="hover:text-red-400">×</a>
            </span>
        @endif
        @if(request('membership_status'))
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-slate-800 text-slate-200 border border-slate-700">
                <span>Membership: {{ ucfirst(request('membership_status')) }}</span>
                <a href="{{ request()->fullUrlWithQuery(['membership_status' => null]) }}" class="hover:text-red-400">×</a>
            </span>
        @endif
        @if(request('qr_status'))
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-slate-800 text-slate-200 border border-slate-700">
                <span>QR: {{ ucfirst(request('qr_status')) }}</span>
                <a href="{{ request()->fullUrlWithQuery(['qr_status' => null]) }}" class="hover:text-red-400">×</a>
            </span>
        @endif
        @if(request('academic_year_id'))
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-slate-800 text-slate-200 border border-slate-700">
                <span>AY Filtered</span>
                <a href="{{ request()->fullUrlWithQuery(['academic_year_id' => null]) }}" class="hover:text-red-400">×</a>
            </span>
        @endif
        <a href="{{ route('admin.students.index') }}" class="text-[11px] text-brand-400 hover:underline ml-1">Clear all</a>
    </div>
    @endif
</div>

<div class="table-wrap">
    <table class="table table-sticky-header">
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
