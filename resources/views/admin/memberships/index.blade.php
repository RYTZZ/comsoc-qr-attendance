<x-layouts.admin :title="'Memberships'">
<div class="page-header">
    <h1 class="page-title">Memberships</h1>
    <div class="flex flex-col sm:flex-row flex-wrap gap-2 w-full sm:w-auto">
        <form method="GET" class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
            <select name="academic_year_id" class="select w-full sm:w-40" onchange="this.form.submit()">
                <option value="">All Years</option>
                @foreach($academicYears as $year)
                <option value="{{ $year->id }}" {{ request('academic_year_id') == $year->id ? 'selected' : '' }}>{{ $year->label }}</option>
                @endforeach
            </select>
            <select name="status" class="select w-full sm:w-32" onchange="this.form.submit()">
                <option value="">All Status</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
            <div class="flex gap-2">
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Search…" class="input flex-1 sm:w-48">
                <button type="submit" class="btn-secondary shrink-0">Filter</button>
            </div>
        </form>
        <form method="POST" action="{{ route('admin.memberships.bulk-activate') }}" enctype="multipart/form-data" class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto pt-2 sm:pt-0 sm:border-l sm:border-slate-800 sm:pl-2">
            @csrf
            <input type="hidden" name="academic_year_id" value="{{ request('academic_year_id') ?? $activeYear?->id }}">
            <input type="file" name="file" accept=".xlsx,.xls,.csv" class="input w-full sm:w-48 py-1 text-xs" required>
            <button type="submit" class="btn-success w-full sm:w-auto" onclick="return confirm('Bulk activate from this file?')">Bulk Activate</button>
        </form>
    </div>
</div>

@if($missingQrCount > 0)
<div class="mb-4 flex items-center justify-between gap-4 rounded-xl border border-amber-500/30 bg-amber-950/30 px-5 py-3">
    <div class="flex items-center gap-3">
        <span class="text-amber-400 text-lg">⚠</span>
        <div>
            <p class="text-sm font-semibold text-amber-300">QR Codes Missing: {{ $missingQrCount }}</p>
            <p class="text-xs text-slate-400">Active memberships without a QR code in the current filter.</p>
        </div>
    </div>
    <form method="POST" action="{{ route('admin.memberships.generate-missing-qrs') }}">
        @csrf
        <input type="hidden" name="academic_year_id" value="{{ $filterYearId }}">
        <button type="submit" class="btn-primary btn-sm"
                onclick="return confirm('Generate QR codes for all {{ $missingQrCount }} active members missing one?')">
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
                <th>Membership #</th>
                <th>Academic Year</th>
                <th>Status</th>
                <th>QR</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($memberships as $membership)
            <tr>
                <td class="font-medium text-white">{{ $membership->student->display_name }}</td>
                <td class="font-mono text-sm">{{ $membership->student->student_number }}</td>
                <td class="font-mono text-xs text-slate-400">{{ $membership->membership_number ?? '—' }}</td>
                <td class="text-slate-400 text-xs">{{ $membership->academicYear->label }}</td>
                <td>
                    <span class="badge {{ $membership->status === 'active' ? 'badge-active' : 'badge-inactive' }}">
                        {{ ucfirst($membership->status) }}
                    </span>
                </td>
                <td>
                    @if($membership->activeQrCode)
                        <div class="flex flex-col gap-0.5">
                            <span class="badge-active text-xs">QR Available</span>
                            @if($membership->activeQrCode->card)
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
                        <button type="submit" class="btn-secondary btn-sm"
                                onclick="return confirm('Deactivate this membership?')">Deactivate</button>
                    </form>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="7">
                <div class="empty-state">
                    <div class="empty-state-icon">🏷️</div>
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
