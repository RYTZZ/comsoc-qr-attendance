<x-layouts.admin :title="'Create Account'">
<div class="page-header">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.users.index') }}" class="btn-secondary btn-sm">← Back</a>
        <h1 class="page-title font-heading">Create System Account</h1>
    </div>
</div>

<div class="card max-w-2xl" x-data="{ accountType: '{{ old('account_type', $initialType ?? 'admin') }}' }">
    <div class="flex items-center gap-2 p-1.5 rounded-xl bg-slate-950 border border-slate-800 mb-6">
        <button type="button"
                @click="accountType = 'admin'"
                :class="accountType === 'admin' ? 'bg-[#560E0F] text-white shadow-md font-semibold' : 'text-slate-400 hover:text-white'"
                class="flex-1 py-2 text-xs rounded-lg transition text-center flex items-center justify-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            Admin / Staff Account
        </button>

        <button type="button"
                @click="accountType = 'kiosk'"
                :class="accountType === 'kiosk' ? 'bg-[#560E0F] text-white shadow-md font-semibold' : 'text-slate-400 hover:text-white'"
                class="flex-1 py-2 text-xs rounded-lg transition text-center flex items-center justify-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            Dedicated Kiosk Account
        </button>
    </div>

    <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="account_type" :value="accountType">

        <div x-show="accountType === 'admin'" class="space-y-4">
            <div>
                <label for="admin_name" class="label">Full Name <span class="text-red-400">*</span></label>
                <input id="admin_name" name="name" type="text" value="{{ old('name') }}"
                       placeholder="Enter full name"
                       class="input w-full" :required="accountType === 'admin'" />
                @error('name')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="admin_email" class="label">Email Address <span class="text-red-400">*</span></label>
                    <input id="admin_email" name="email" type="email" value="{{ old('email') }}"
                           placeholder="Enter email address"
                           class="input w-full" :required="accountType === 'admin'" />
                    @error('email')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="admin_student_number" class="label">Student Number / Employee ID (Optional)</label>
                    <input id="admin_student_number" name="student_number" type="text" value="{{ old('student_number') }}"
                           placeholder="Enter student ID or employee ID"
                           class="input w-full" />
                    @error('student_number')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="officer_position" class="label">Organizational Position</label>
                    @php
                        $positionOpts = array_merge(['' => 'Select Position (Optional)'], $positions ?? []);
                    @endphp
                    <x-custom-dropdown name="officer_position"
                                       id="officer_position"
                                       :options="$positionOpts"
                                       :value="old('officer_position')"
                                       placeholder="Select organizational position" />
                    <p class="text-[11px] text-slate-500 mt-1">E.g., Treasurer, Auditor, Event Organizer, PIO.</p>
                    @error('officer_position')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="admin_role" class="label">System Role & Permissions <span class="text-red-400">*</span></label>
                    <x-custom-dropdown name="role"
                                       id="admin_role"
                                       :options="$systemRoles ?? [
                                           'super_admin' => 'Super Admin (Full System Privileges)',
                                           'president' => 'President',
                                           'executive_secretary' => 'Executive Secretary',
                                           'treasurer' => 'Treasurer',
                                           'auditor' => 'Auditor',
                                           'event_staff' => 'Event Staff / Organizer',
                                           'staff' => 'Staff (Operations)',
                                           'admin' => 'Admin (Legacy)',
                                       ]"
                                       :value="old('role', 'staff')"
                                       placeholder="Select system role" />
                    <p class="text-[11px] text-slate-500 mt-1">Controls application access according to least-privilege.</p>
                    @error('role')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div x-show="accountType === 'kiosk'" class="space-y-4" x-cloak>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="kiosk_name" class="label">Kiosk Name <span class="text-red-400">*</span></label>
                    <input id="kiosk_name" name="kiosk_name" type="text" value="{{ old('kiosk_name') }}"
                           class="input w-full" :required="accountType === 'kiosk'" />
                    @error('kiosk_name')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="kiosk_code" class="label">Kiosk Code / Identifier <span class="text-red-400">*</span></label>
                    <input id="kiosk_code" name="kiosk_code" type="text" value="{{ old('kiosk_code') }}"
                           class="input w-full uppercase" :required="accountType === 'kiosk'" />
                    @error('kiosk_code')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label for="kiosk_username" class="label">Username <span class="text-red-400">*</span></label>
                <input id="kiosk_username" name="username" type="text" value="{{ old('username') }}"
                       class="input w-full" :required="accountType === 'kiosk'" />
                @error('username')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="assigned_event_id" class="label">Event Assignment (Optional)</label>
                @php
                    $kioskEventOpts = ['' => 'Any Event (or Selected dynamically by staff)'];
                    foreach($events as $ev) {
                        $kioskEventOpts[$ev->id] = $ev->name . ($ev->event_date ? ' (' . $ev->event_date->format('M d, Y') . ')' : '');
                    }
                @endphp
                <x-custom-dropdown name="assigned_event_id"
                                   id="assigned_event_id"
                                   :options="$kioskEventOpts"
                                   :value="old('assigned_event_id')"
                                   placeholder="Select Assigned Event" />
                @error('assigned_event_id')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-slate-800">
            <div>
                <label for="password" class="label">Password <span class="text-red-400">*</span></label>
                <input id="password" name="password" type="password" required minlength="8"
                       class="input w-full" />
                @error('password')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="password_confirmation" class="label">Confirm Password <span class="text-red-400">*</span></label>
                <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8"
                       class="input w-full" />
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <label class="flex items-center gap-2 p-3 rounded-xl bg-slate-950 border border-slate-800 cursor-pointer">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}
                       class="rounded bg-slate-900 border-slate-700 text-[#560E0F] focus:ring-[#560E0F]">
                <span class="text-slate-300 text-xs font-medium">Account is Active immediately</span>
            </label>

            <label class="flex items-center gap-2 p-3 rounded-xl bg-slate-950 border border-slate-800 cursor-pointer">
                <input type="checkbox" name="must_change_password" value="1" {{ old('must_change_password', '1') ? 'checked' : '' }}
                       class="rounded bg-slate-900 border-slate-700 text-[#560E0F] focus:ring-[#560E0F]">
                <span class="text-slate-300 text-xs font-medium">Require password change on first login</span>
            </label>
        </div>

        <div class="pt-4 flex justify-end gap-3 border-t border-slate-800">
            <a href="{{ route('admin.users.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">
                Create Account
            </button>
        </div>
    </form>
</div>
</x-layouts.admin>
