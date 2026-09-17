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
        <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <h2 class="font-bold text-white text-sm">
            {{ isset($creds['is_reset']) ? 'Password Reset' : 'Account Created' }} — {{ $creds['student_name'] }}
        </h2>
    </div>
    <div class="flex items-center gap-2 text-xs text-amber-400 font-semibold">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <span>These credentials are shown only once. Share them securely.</span>
    </div>
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
                <span x-show="copied" class="flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    Copied
                </span>
            </button>
        </div>
    </div>
    <p class="text-xs text-slate-500">The student will be required to change this password on first login.</p>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-1 space-y-4">

        <div class="card">
            <div class="flex items-center justify-between mb-4">
                <h2 class="section-title mb-0">Student Info</h2>
                <button type="button"
                        onclick="document.getElementById('edit-student-modal').classList.remove('hidden')"
                        class="btn-secondary btn-sm flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit
                </button>
            </div>
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
                    <dt class="label">Program</dt>
                    <dd class="text-white text-sm">{{ $student->program ?: 'Not set' }}</dd>
                </div>
                <div>
                    <dt class="label">Year Level</dt>
                    <dd class="text-white text-sm font-semibold">
                        @if($student->year_level)
                            <span class="badge badge-active text-xs">{{ $student->year_level }}</span>
                        @else
                            <span class="text-amber-400 text-xs">Not set</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </div>

        <div id="edit-student-modal" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
            <div class="bg-slate-900 border border-slate-700 rounded-2xl p-6 max-w-md w-full space-y-4 shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h2 class="text-base font-bold text-white">Edit Student Details</h2>
                    <button type="button"
                            onclick="document.getElementById('edit-student-modal').classList.add('hidden')"
                            class="text-slate-400 hover:text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <form method="POST" action="{{ route('admin.students.update', $student) }}" class="space-y-4">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label class="label">Student Number</label>
                        <input type="text" value="{{ $student->student_number }}" class="input w-full bg-slate-800/50 cursor-not-allowed font-mono text-xs" disabled>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="label">First Name *</label>
                            <input type="text" name="first_name" value="{{ old('first_name', $student->first_name) }}" class="input w-full text-sm" required>
                        </div>
                        <div>
                            <label class="label">Last Name *</label>
                            <input type="text" name="last_name" value="{{ old('last_name', $student->last_name) }}" class="input w-full text-sm" required>
                        </div>
                    </div>
                    <div>
                        <label class="label">Middle Name</label>
                        <input type="text" name="middle_name" value="{{ old('middle_name', $student->middle_name) }}" class="input w-full text-sm">
                    </div>
                    <div>
                        <label class="label">Program</label>
                        @php
                            $progOptions = ['' => 'Select Program'];
                            foreach($programs as $p) {
                                $progOptions[$p] = $p;
                            }
                            if ($student->program && !isset($progOptions[$student->program])) {
                                $progOptions[$student->program] = $student->program;
                            }
                        @endphp
                        <x-custom-dropdown
                            name="program"
                            :options="$progOptions"
                            :value="old('program', $student->program)"
                            placeholder="Select Program" />
                    </div>
                    <div>
                        <label class="label">Year Level *</label>
                        @php
                            $ylOptions = [];
                            foreach($yearLevels as $yl) {
                                $ylOptions[$yl] = $yl;
                            }
                        @endphp
                        <x-custom-dropdown
                            name="year_level"
                            :options="$ylOptions"
                            :value="old('year_level', $student->year_level)"
                            :required="true"
                            placeholder="Select Year Level" />
                    </div>
                    <div class="flex gap-2 pt-2">
                        <button type="submit" class="btn-primary flex-1">Save Changes</button>
                        <button type="button" class="btn-secondary flex-1"
                                onclick="document.getElementById('edit-student-modal').classList.add('hidden')">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
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
                <div class="w-12 h-12 rounded-2xl bg-slate-800 flex items-center justify-center mx-auto mb-2 text-slate-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
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
                <div class="empty-state-icon flex items-center justify-center">
                    <svg class="w-8 h-8 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5a1.99 1.99 0 011.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V5a2 2 0 012-2z" />
                    </svg>
                </div>
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
