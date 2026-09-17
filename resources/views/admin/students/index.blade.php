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
    @if(auth()->user()->isSuperAdmin() && $noAccountCount > 0)
    <button type="button"
            onclick="document.getElementById('bulk-create-modal').classList.remove('hidden')"
            class="btn-primary flex items-center gap-2 shrink-0">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Bulk Create Accounts
    </button>
    @endif
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
           class="input w-full sm:w-56 text-sm">
    <div class="w-full sm:w-44">
        <x-custom-dropdown
            name="account_status"
            :options="[
                '' => 'All Account Statuses',
                'no_account' => 'No Account',
                'active' => 'Active',
                'inactive' => 'Suspended / Deactivated',
            ]"
            :value="request('account_status', '')"
            placeholder="All Account Statuses"
            buttonClass="py-2 text-xs sm:text-sm" />
    </div>
    <div class="w-full sm:w-40">
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
    <div class="w-full sm:w-36">
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
    <div class="w-full sm:w-48">
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
                <th>Account</th>
                <th>Membership</th>
                <th>QR</th>
                <th>Last Login</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $student)
            @php
                $membership = $student->memberships->first();
                $activeQr = $membership?->activeQrCode;
                $user = $student->user;
            @endphp
            <tr>
                <td class="font-mono text-sm">{{ $student->student_number }}</td>
                <td class="font-medium text-white">{{ $student->full_name }}</td>
                <td>
                    @if(!$user)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-800 text-slate-400 border border-slate-700">No Account</span>
                    @elseif($user->is_active)
                        <span class="badge-active text-xs">Active</span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-red-500/10 text-red-400 border border-red-500/20">Suspended</span>
                    @endif
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
                <td class="text-slate-400 text-xs">
                    {{ $user?->last_activity_at?->diffForHumans() ?? '—' }}
                </td>
                <td>
                    <a href="{{ route('admin.students.show', $student) }}" class="btn-secondary btn-sm">Manage</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7">
                    <div class="empty-state">
                        <div class="empty-state-icon flex items-center justify-center">
                            <svg class="w-8 h-8 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
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

@if(auth()->user()->isSuperAdmin())
<div id="bulk-create-modal" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl p-6 max-w-sm w-full space-y-4 shadow-2xl">
        <h2 class="text-base font-bold text-white">Bulk Create Student Accounts</h2>
        <p class="text-sm text-slate-400">
            Create accounts for all students without one in the selected academic year.
        </p>
        <div class="bg-slate-800/60 rounded-xl p-4 text-center">
            <p class="text-3xl font-bold text-white">{{ number_format($noAccountCount) }}</p>
            <p class="text-xs text-slate-400 mt-1">Students Without Accounts</p>
        </div>
        @if($noAccountCount > 0)
        <form method="POST" action="{{ route('admin.students.bulk-create-accounts') }}">
            @csrf
            <input type="hidden" name="academic_year_id" value="{{ $yearId }}">
            <div class="flex gap-2">
                <button type="submit" class="btn-primary flex-1"
                        onclick="return confirm('Create {{ $noAccountCount }} student account(s)? This cannot be undone.')">
                    Create Accounts
                </button>
                <button type="button" class="btn-secondary flex-1"
                        onclick="document.getElementById('bulk-create-modal').classList.add('hidden')">
                    Cancel
                </button>
            </div>
        </form>
        @else
        <button type="button" class="btn-secondary w-full"
                onclick="document.getElementById('bulk-create-modal').classList.add('hidden')">
            Close
        </button>
        @endif
    </div>
</div>
@endif
</x-layouts.admin>
