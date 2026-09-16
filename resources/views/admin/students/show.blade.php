<x-layouts.admin :title="$student->display_name">
<div class="page-header">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.students.index') }}" class="btn-secondary btn-sm">← Back</a>
        <div>
            <h1 class="page-title">{{ $student->full_name }}</h1>
            <p class="text-xs text-slate-400 mt-0.5 font-mono">{{ $student->student_number }}</p>
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert-success mb-4">{{ session('success') }}</div>
@endif
@if(session('info'))
<div class="alert-info mb-4">{{ session('info') }}</div>
@endif
@if(session('error'))
<div class="alert-error mb-4">{{ session('error') }}</div>
@endif

@if(session('student_credentials'))
@php $creds = session('student_credentials'); @endphp
<div class="mb-4 rounded-2xl border border-emerald-500/30 bg-emerald-950/20 p-5 space-y-3" x-data="{ copied: false }">
    <div class="flex items-center gap-2">
        <span class="text-emerald-400 text-lg">✅</span>
        <h2 class="font-bold text-white text-sm">
            {{ isset($creds['is_reset']) ? 'Password Reset' : 'Account Created' }} — {{ $creds['student_name'] }}
        </h2>
    </div>
    <p class="text-xs text-amber-400 font-semibold">⚠ These credentials are shown only once. Share them securely.</p>
    <div class="bg-slate-950 rounded-xl p-4 space-y-2 font-mono text-sm">
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-xs text-slate-500 mb-0.5">Student Number (Login)</p>
                <p class="text-white font-bold">{{ $creds['username'] }}</p>
            </div>
        </div>
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-xs text-slate-500 mb-0.5">Temporary Password</p>
                <p class="text-emerald-300 font-bold text-base tracking-wider">{{ $creds['password'] }}</p>
            </div>
            <button type="button"
                    x-on:click="navigator.clipboard.writeText('{{ $creds['username'] }}\n{{ $creds['password'] }}'); copied = true; setTimeout(() => copied = false, 2000)"
                    class="btn-secondary btn-sm shrink-0">
                <span x-show="!copied">Copy</span>
                <span x-show="copied">Copied ✓</span>
            </button>
        </div>
    </div>
    <p class="text-xs text-slate-500">The student will be required to change this password on first login.</p>
</div>
@endif

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
                @if($student->program)
                <div>
                    <dt class="label">Program</dt>
                    <dd class="text-white text-sm">{{ $student->program }}</dd>
                </div>
                @endif
                @if($student->year_level)
                <div>
                    <dt class="label">Year Level</dt>
                    <dd class="text-white text-sm">Year {{ $student->year_level }}</dd>
                </div>
                @endif
            </dl>
        </div>

        <div class="card">
            <h2 class="section-title">Account</h2>
            @if($student->user)
            @php $user = $student->user; @endphp
            <dl class="space-y-3 mb-4">
                <div>
                    <dt class="label">Status</dt>
                    <dd>
                        @if($user->is_active)
                            <span class="badge-active">Active</span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-500/10 text-red-400 border border-red-500/20">Suspended</span>
                        @endif
                        @if($user->must_change_password)
                            <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-500/10 text-amber-400 border border-amber-500/20">Must Change Password</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="label">Login Username</dt>
                    <dd class="text-white font-mono text-sm">{{ $user->username }}</dd>
                </div>
                <div>
                    <dt class="label">Account Created</dt>
                    <dd class="text-slate-300 text-sm">{{ $user->created_at->format('M d, Y') }}</dd>
                </div>
                <div>
                    <dt class="label">Last Login</dt>
                    <dd class="text-slate-300 text-sm">{{ $user->last_activity_at?->diffForHumans() ?? 'Never' }}</dd>
                </div>
            </dl>

            <div class="space-y-2">
                @if(!$user->is_active)
                <form method="POST" action="{{ route('admin.students.activate-account', $student) }}">
                    @csrf
                    <button type="submit" class="btn-primary w-full text-sm">Activate Account</button>
                </form>
                @else
                <form method="POST" action="{{ route('admin.students.suspend-account', $student) }}"
                      onsubmit="return confirm('Suspend {{ $student->display_name }}\'s account? They will not be able to log in.')">
                    @csrf
                    <button type="submit" class="w-full px-4 py-2 rounded-xl text-sm font-semibold bg-amber-500/10 text-amber-400 border border-amber-500/20 hover:bg-amber-500/20 transition-colors">
                        Suspend Account
                    </button>
                </form>
                @endif

                <form method="POST" action="{{ route('admin.students.reset-password', $student) }}"
                      onsubmit="return confirm('Reset the password for {{ $student->display_name }}? A new temporary password will be generated.')">
                    @csrf
                    <button type="submit" class="w-full px-4 py-2 rounded-xl text-sm font-semibold bg-slate-800 text-slate-300 border border-slate-700 hover:bg-slate-700 transition-colors">
                        Reset Password
                    </button>
                </form>

                @if($user->is_active)
                <form method="POST" action="{{ route('admin.students.deactivate-account', $student) }}"
                      onsubmit="return confirm('Deactivate {{ $student->display_name }}\'s account? They will not be able to log in.')">
                    @csrf
                    <button type="submit" class="w-full px-4 py-2 rounded-xl text-sm font-semibold bg-red-500/10 text-red-400 border border-red-500/20 hover:bg-red-500/20 transition-colors">
                        Deactivate Account
                    </button>
                </form>
                @endif
            </div>

            @else
            <div class="text-center py-4 mb-4">
                <div class="text-3xl mb-2">👤</div>
                <p class="text-sm font-semibold text-slate-300">No Account</p>
                <p class="text-xs text-slate-500 mt-1">This student does not have a system account.</p>
            </div>
            <form method="POST" action="{{ route('admin.students.create-account', $student) }}"
                  onsubmit="return confirm('Create a student account for {{ $student->display_name }}? The login username will be their Student Number.')">
                @csrf
                <button type="submit" class="btn-primary w-full">
                    Create Account
                </button>
            </form>
            <p class="text-xs text-slate-500 text-center mt-2">Login: <span class="font-mono text-slate-400">{{ $student->student_number }}</span> + auto-generated password</p>
            @endif
        </div>
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
