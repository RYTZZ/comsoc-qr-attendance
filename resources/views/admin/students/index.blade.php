<x-layouts.admin :title="'Students'">
<div class="page-header">
    <h1 class="page-title">Students</h1>
    <form method="GET" class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
        <input type="search" name="search" value="{{ request('search') }}" placeholder="Search name or student #…" class="input w-full sm:w-64">
        <button type="submit" class="btn-secondary w-full sm:w-auto">Search</button>
    </form>
</div>

@if($activeYear)
<p class="text-xs text-slate-400 mb-4">Showing membership status for: <span class="text-white font-semibold">{{ $activeYear->label }}</span></p>
@endif

<div class="table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th>Student Number</th>
                <th>Name</th>
                <th>Current Membership</th>
                <th>QR</th>
                <th>Account</th>
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
                    @if($student->user)
                    <span class="badge-active text-xs">Active</span>
                    @else
                    <span class="text-slate-600 text-xs">No Account</span>
                    @endif
                </td>
                <td>
                    <a href="{{ route('admin.students.show', $student) }}" class="btn-secondary btn-sm">View</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6">
                    <div class="empty-state">
                        <div class="empty-state-icon">👥</div>
                        <p class="empty-state-title">No students found</p>
                        <p class="empty-state-body">Upload a masterlist to import students.</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $students->links() }}</div>
</x-layouts.admin>
