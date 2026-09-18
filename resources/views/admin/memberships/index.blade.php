<x-layouts.admin :title="'Memberships'">
<div class="page-header">
    <h1 class="page-title">Memberships</h1>
    <div class="flex flex-col sm:flex-row flex-wrap gap-2 w-full sm:w-auto">
        <form method="GET" class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
            @php
                $yearOpts = ['' => 'All Years'];
                foreach($academicYears as $year) {
                    $yearOpts[$year->id] = $year->label;
                }
            @endphp
            <div class="w-full sm:w-44">
                <x-custom-dropdown
                    name="academic_year_id"
                    :options="$yearOpts"
                    :value="request('academic_year_id', '')"
                    placeholder="All Years"
                    :autoSubmit="true"
                    buttonClass="py-2 text-xs" />
            </div>
            <div class="w-full sm:w-36">
                <x-custom-dropdown
                    name="status"
                    :options="[
                        '' => 'All Status',
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ]"
                    :value="request('status', '')"
                    placeholder="All Status"
                    :autoSubmit="true"
                    buttonClass="py-2 text-xs" />
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
                    :autoSubmit="true"
                    buttonClass="py-2 text-xs" />
            </div>
            <div class="flex gap-2">
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Search…" class="input flex-1 sm:w-40">
                <button type="submit" class="btn-secondary shrink-0">Filter</button>
            </div>
        </form>
        <form method="POST" action="{{ route('admin.memberships.bulk-activate') }}" enctype="multipart/form-data" class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto pt-2 sm:pt-0 sm:border-l sm:border-slate-800 sm:pl-2">
            @csrf
            <input type="hidden" name="academic_year_id" value="{{ request('academic_year_id') ?? $activeYear?->id ?? '' }}">
            <input type="file" name="file" accept=".xlsx,.xls,.csv" class="input w-full sm:w-48 py-1 text-xs" required>
            <button type="button"
                    data-confirm="Bulk activate memberships from this uploaded file?"
                    data-confirm-title="Bulk Activate Memberships"
                    data-confirm-type="success"
                    data-confirm-btn="Activate File"
                    class="btn-success w-full sm:w-auto">Bulk Activate</button>
        </form>
    </div>
</div>

@if($missingQrCount > 0)
<div class="mb-4 flex items-center justify-between gap-4 rounded-xl border border-amber-500/30 bg-amber-950/30 px-5 py-3">
    <div class="flex items-center gap-3">
        <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-400 shrink-0"></i>
        <div>
            <p class="text-sm font-semibold text-amber-300">QR Codes Missing: {{ $missingQrCount }}</p>
            <p class="text-xs text-slate-400">Active memberships without a QR code in the current filter.</p>
        </div>
    </div>
    <form method="POST" action="{{ route('admin.memberships.generate-missing-qrs') }}">
        @csrf
        <input type="hidden" name="academic_year_id" value="{{ $filterYearId }}">
        <button type="button"
                data-confirm="Generate QR codes for all {{ $missingQrCount }} active members missing one?"
                data-confirm-title="Generate Missing QR Codes"
                data-confirm-type="primary"
                data-confirm-btn="Generate Codes"
                class="btn-primary btn-sm">
            Generate Missing QR Codes
        </button>
    </form>
</div>
@endif

<div class="table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th>Student</th>
                <th>Student #</th>
                <th>Program & Year</th>
                <th>Membership #</th>
                <th>Academic Year</th>
                <th>Status</th>
                <th>QR</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($memberships as $membership)
            @php
                $student = $membership->student;
            @endphp
            <tr>
                <td class="font-medium text-white">{{ $student?->display_name ?? 'Unknown Student' }}</td>
                <td class="font-mono text-sm">{{ $student?->student_number ?? '—' }}</td>
                <td>
                    <div class="flex flex-col">
                        <span class="text-white text-xs font-semibold">{{ $student?->year_level ?? 'Not assigned' }}</span>
                        <span class="text-slate-400 text-[11px]">{{ $student?->program ?? 'None' }}</span>
                    </div>
                </td>
                <td class="font-mono text-xs text-slate-400">{{ $membership->membership_number ?? '—' }}</td>
                <td class="text-slate-400 text-xs">{{ $membership->academicYear?->label ?? '—' }}</td>
                <td>
                    <span class="badge {{ $membership->status === 'active' ? 'badge-active' : 'badge-inactive' }}">
                        {{ ucfirst($membership->status) }}
                    </span>
                </td>
                <td>
                    @if($membership->activeQrCode)
                        <div class="flex flex-col gap-0.5">
                            <span class="badge-active text-xs">QR Available</span>
                            @if($membership->activeQrCode->card?->status)
                                <span class="text-[10px] text-slate-400">
                                    {{ ucfirst(str_replace('_', ' ', $membership->activeQrCode->card->status)) }}
                                </span>
                            @endif
                        </div>
                    @elseif($membership->latestQrCode && $membership->latestQrCode->status === 'revoked')
                        <span class="badge-rejected text-xs">QR Revoked</span>
                    @elseif($membership->status === 'active')
                        <div class="flex flex-col gap-1">
                            <span class="text-amber-400 text-xs font-semibold">QR Missing</span>
                            <form method="POST" action="{{ route('admin.memberships.generate-qr', $membership) }}">
                                @csrf
                                <button type="submit" class="btn-secondary btn-sm text-xs py-0.5 px-2">Generate QR</button>
                            </form>
                        </div>
                    @else
                        <span class="text-slate-600 text-xs">None</span>
                    @endif
                </td>
                <td class="space-x-1">
                    @if($membership->status === 'inactive')
                    <form method="POST" action="{{ route('admin.memberships.activate', $membership) }}" class="inline">
                        @csrf
                        <button type="submit" class="btn-success btn-sm">Activate</button>
                    </form>
                    @else
                    <form method="POST" action="{{ route('admin.memberships.deactivate', $membership) }}" class="inline">
                        @csrf
                        <button type="button"
                                data-confirm="Deactivate this membership? The student's active QR attendance access will be suspended."
                                data-confirm-title="Deactivate Membership"
                                data-confirm-type="warning"
                                data-confirm-btn="Deactivate"
                                class="btn-secondary btn-sm">Deactivate</button>
                    </form>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="8">
                <div class="empty-state">
                    <div class="empty-state-icon flex items-center justify-center">
                        <i data-lucide="award" class="w-8 h-8 text-slate-500"></i>
                    </div>
                    <p class="empty-state-title">No memberships found</p>
                    <p class="empty-state-body">Upload a masterlist first to create membership records.</p>
                </div>
            </td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $memberships->links() }}</div>
</x-layouts.admin>
