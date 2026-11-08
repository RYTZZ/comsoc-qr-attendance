@props([
    'account',
    'student' => null,
    'showViewLink' => false,
])

@php
    $isSuperAdmin = auth()->user()?->isSuperAdmin();
    $studentModel = $student ?? $account?->student;
@endphp

@if($account && $account->role === 'student' && $isSuperAdmin)
<div x-data="{ showSuspendModal: false, showDeleteModal: false, showRestoreModal: false }" class="inline-flex flex-wrap items-center gap-1.5">
    @if($showViewLink && $studentModel)
        <a href="{{ route('admin.students.show', $studentModel) }}" class="btn-secondary btn-sm">
            View Account
        </a>
    @endif

    @if($account->trashed())
        <form method="POST" action="{{ route('admin.users.restore', $account->id) }}" class="inline">
            @csrf
            <button type="submit" class="btn-secondary btn-sm text-emerald-400 border-emerald-500/30 hover:bg-emerald-950/30">
                Restore Account
            </button>
        </form>
    @elseif($account->isSuspended() || ! $account->is_active)
        <form method="POST" action="{{ route('admin.users.reactivate', $account) }}" class="inline">
            @csrf
            <button type="submit" class="btn-secondary btn-sm text-emerald-400 border-emerald-500/30 hover:bg-emerald-950/30">
                Reactivate Account
            </button>
        </form>

        <button type="button"
                @click="showDeleteModal = true"
                class="btn-secondary btn-sm text-rose-400 border-rose-500/30 hover:bg-rose-950/30">
            Delete Account
        </button>
    @else
        <button type="button"
                @click="showSuspendModal = true"
                class="btn-secondary btn-sm text-amber-400 border-amber-500/30 hover:bg-amber-950/30">
            Suspend Account
        </button>

        <button type="button"
                @click="showDeleteModal = true"
                class="btn-secondary btn-sm text-rose-400 border-rose-500/30 hover:bg-rose-950/30">
            Delete Account
        </button>
    @endif

    {{-- Suspend Account Confirmation Modal --}}
    <div x-show="showSuspendModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
         role="dialog" aria-modal="true" aria-label="Suspend Account Confirmation">
        <div @click.away="showSuspendModal = false"
             class="card bg-[#172033] border border-amber-500/40 rounded-2xl p-6 max-w-md w-full text-left space-y-4 shadow-2xl whitespace-normal">
            <div class="flex items-start justify-between gap-3 border-b border-slate-800 pb-3">
                <div>
                    <h3 class="text-base font-bold text-amber-300">Suspend Account</h3>
                    <p class="text-xs text-slate-400 mt-0.5">{{ $account->name }} ({{ $account->email }})</p>
                </div>
                <button type="button" @click="showSuspendModal = false" class="text-slate-400 hover:text-white">&times;</button>
            </div>

            <div class="rounded-xl bg-amber-950/40 border border-amber-500/30 p-3.5 text-xs text-amber-100 leading-relaxed space-y-1">
                <p class="font-semibold text-amber-300">Are you sure you want to suspend this student's account?</p>
                <p>The student will immediately lose access to COMSOC Hub. Their historical records will remain preserved.</p>
            </div>

            <form method="POST" action="{{ route('admin.users.suspend', $account) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">
                        Suspension Reason <span class="text-rose-400">*</span>
                    </label>
                    <textarea name="reason" rows="3" required minlength="3" maxlength="1000"
                              placeholder="Provide the official reason for suspending this student account..."
                              class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-xs text-white focus:ring-2 focus:ring-amber-500 focus:outline-none"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-800">
                    <button type="button" @click="showSuspendModal = false" class="btn-secondary btn-sm">Cancel</button>
                    <button type="submit" class="btn-primary btn-sm bg-amber-600 hover:bg-amber-500 border-amber-500 text-white">
                        Confirm Suspend Account
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Delete Account Confirmation Modal --}}
    <div x-show="showDeleteModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
         role="dialog" aria-modal="true" aria-label="Delete Account Confirmation">
        <div @click.away="showDeleteModal = false"
             class="card bg-[#172033] border border-rose-500/40 rounded-2xl p-6 max-w-md w-full text-left space-y-4 shadow-2xl whitespace-normal">
            <div class="flex items-start justify-between gap-3 border-b border-slate-800 pb-3">
                <div>
                    <h3 class="text-base font-bold text-rose-300">Delete Account</h3>
                    <p class="text-xs text-slate-400 mt-0.5">{{ $account->name }} ({{ $account->email }})</p>
                </div>
                <button type="button" @click="showDeleteModal = false" class="text-slate-400 hover:text-white">&times;</button>
            </div>

            <div class="rounded-xl bg-rose-950/40 border border-rose-500/30 p-3.5 text-xs text-rose-100 leading-relaxed space-y-1">
                <p class="font-semibold text-rose-300">Are you sure you want to delete this student account?</p>
                <p>The account will be removed from active member listings and prevented from logging in. Historical attendance, activity participation, EXP, and Points records will be preserved.</p>
            </div>

            <form method="POST" action="{{ route('admin.users.destroy', $account) }}" class="space-y-4">
                @csrf
                @method('DELETE')
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">
                        Deletion Reason <span class="text-rose-400">*</span>
                    </label>
                    <textarea name="reason" rows="3" required minlength="3" maxlength="1000"
                              placeholder="Provide the official reason for deleting this student account..."
                              class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-xs text-white focus:ring-2 focus:ring-rose-500 focus:outline-none"></textarea>
                </div>

                <label class="flex items-start gap-2.5 p-2.5 rounded-xl bg-slate-900 border border-slate-800 cursor-pointer text-xs text-slate-300">
                    <input type="checkbox" name="confirm_delete" value="1" required
                           class="mt-0.5 rounded bg-slate-950 border-slate-700 text-rose-600 focus:ring-rose-500">
                    <span>I confirm that I want to delete this student account and understand that login access will be revoked while historical records remain preserved.</span>
                </label>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-800">
                    <button type="button" @click="showDeleteModal = false" class="btn-secondary btn-sm">Cancel</button>
                    <button type="submit" class="btn-primary btn-sm bg-rose-600 hover:bg-rose-500 border-rose-500 text-white">
                        Confirm Delete Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

