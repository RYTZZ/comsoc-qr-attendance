<x-layouts.admin :title="$student->display_name">
<div class="page-header">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.students.index') }}" class="btn-secondary btn-sm">← Back</a>
        <h1 class="page-title">{{ $student->full_name }}</h1>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-1 space-y-4">
        <div class="card">
            <h2 class="section-title">Student Info</h2>
            <dl class="space-y-3">
                <div>
                    <dt class="label">Student Number</dt>
                    <dd class="text-white font-mono">{{ $student->student_number }}</dd>
                </div>
                <div>
                    <dt class="label">Last Name</dt>
                    <dd class="text-white">{{ $student->last_name }}</dd>
                </div>
                <div>
                    <dt class="label">First Name</dt>
                    <dd class="text-white">{{ $student->first_name }}</dd>
                </div>
                @if($student->middle_name)
                <div>
                    <dt class="label">Middle Name</dt>
                    <dd class="text-white">{{ $student->middle_name }}</dd>
                </div>
                @endif
                <div>
                    <dt class="label">System Account</dt>
                    <dd>
                        @if($student->user)
                        <span class="badge-active">{{ $student->user->email }}</span>
                        <p class="text-xs text-slate-500 mt-1">Role: {{ ucfirst($student->user->role) }}</p>
                        @else
                        <span class="text-slate-500 text-sm">No account linked</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </div>

        @if(!$student->user)
        <div class="card border border-brand-500/20">
            <h2 class="section-title">Create Student Account</h2>
            <form method="POST" action="{{ route('admin.students.create-account', $student) }}" class="space-y-3">
                @csrf
                <div>
                    <label class="label" for="email">Email / Username</label>
                    <input type="email" name="email" id="email" class="input w-full" required
                           placeholder="{{ strtolower(str_replace(' ', '', $student->first_name)) }}.{{ strtolower($student->student_number) }}@student.local"
                           value="{{ old('email') }}">
                    @error('email')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="password">Password</label>
                    <input type="password" name="password" id="password" class="input w-full" required minlength="8">
                    @error('password')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label" for="password_confirmation">Confirm Password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" class="input w-full" required minlength="8">
                </div>
                <button type="submit" class="btn-primary w-full"
                        onclick="return confirm('Create a student account for {{ $student->display_name }}?')">
                    Create Account
                </button>
            </form>
        </div>
        @endif
    </div>

    <div class="lg:col-span-2 space-y-4">
        <div class="card">
            <h2 class="section-title">Membership History</h2>
            @if($student->memberships->isEmpty())
            <div class="empty-state py-6">
                <div class="empty-state-icon">🏷️</div>
                <p class="empty-state-title">No memberships</p>
            </div>
            @else
            <div class="space-y-3">
                @foreach($student->memberships->sortByDesc(fn($m) => $m->academicYear?->year_start) as $membership)
                <div class="p-4 rounded-xl bg-slate-800/50 border border-slate-700/50">
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-medium text-white">{{ $membership->academicYear->label }}</span>
                        <span class="badge {{ $membership->status === 'active' ? 'badge-active' : 'badge-inactive' }}">{{ ucfirst($membership->status) }}</span>
                    </div>
                    <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500">
                        <span>MEM# {{ $membership->membership_number ?? 'N/A' }}</span>
                        @if($membership->fee_paid)
                        <span>Fee: ₱{{ number_format($membership->fee_paid, 2) }}</span>
                        @endif
                        @if($membership->paid_at)
                        <span>Paid: {{ $membership->paid_at->format('M d, Y') }}</span>
                        @endif
                    </div>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @if($membership->activeQrCode)
                        <span class="badge-active text-xs">QR Active — Batch #{{ $membership->activeQrCode->batch_number }}</span>
                        @elseif($membership->status === 'active')
                        <span class="text-amber-400 text-xs font-semibold">QR Missing</span>
                        @else
                        <span class="text-slate-600 text-xs">No active QR</span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
</div>
</x-layouts.admin>
