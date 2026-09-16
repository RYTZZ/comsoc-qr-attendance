<x-layouts.admin :title="'Edit Account'">
<div class="page-header">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.users.index') }}" class="btn-secondary btn-sm">← Back</a>
        <h1 class="page-title font-heading">Edit Account: {{ $user->name }}</h1>
    </div>
</div>

<div class="card max-w-2xl">
    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-4">
        @csrf
        @method('PUT')

        @if($user->role === 'kiosk')
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="kiosk_name" class="label">Kiosk Name <span class="text-red-400">*</span></label>
                    <input id="kiosk_name" name="kiosk_name" type="text"
                           value="{{ old('kiosk_name', $user->kiosk?->name ?? $user->name) }}" required class="input w-full" />
                    @error('kiosk_name')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="kiosk_code" class="label">Kiosk Code / Identifier <span class="text-red-400">*</span></label>
                    <input id="kiosk_code" name="kiosk_code" type="text"
                           value="{{ old('kiosk_code', $user->kiosk?->identifier ?? 'KIOSK-001') }}" required class="input w-full uppercase" />
                    @error('kiosk_code')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label for="username" class="label">Username <span class="text-red-400">*</span></label>
                <input id="username" name="username" type="text"
                       value="{{ old('username', $user->username ?? explode('@', $user->email)[0]) }}" required class="input w-full" />
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
                                   :value="old('assigned_event_id', $user->kiosk?->assigned_event_id)"
                                   placeholder="Select Assigned Event" />
                @error('assigned_event_id')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

        @else
            <div>
                <label for="name" class="label">Full Name <span class="text-red-400">*</span></label>
                <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required class="input w-full" />
                @error('name')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="email" class="label">Username or Email Address <span class="text-red-400">*</span></label>
                <input id="email" name="email" type="text" value="{{ old('email', $user->email) }}" required class="input w-full" />
                @error('email')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="role" class="label">Role Assignment <span class="text-red-400">*</span></label>
                @if(auth()->id() === $user->id)
                    <input type="hidden" name="role" value="{{ $user->role }}">
                    <div class="px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-800 text-sm text-slate-400">
                        {{ ucfirst(str_replace('_', ' ', $user->role)) }} (Cannot modify your own role)
                    </div>
                @else
                    <x-custom-dropdown name="role"
                                       id="role"
                                       :options="[
                                           'admin' => 'Admin',
                                           'super_admin' => 'Super Admin',
                                           'staff' => 'Staff',
                                           'treasurer' => 'Treasurer',
                                           'student' => 'Student',
                                       ]"
                                       :value="old('role', $user->role)"
                                       placeholder="Select Role" />
                @endif
                @error('role')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        @endif

        <div>
            <label class="flex items-center gap-2 p-3 rounded-xl bg-slate-950 border border-slate-800 cursor-pointer">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }}
                       class="rounded bg-slate-900 border-slate-700 text-[#7A1618] focus:ring-[#7A1618]">
                <span class="text-slate-300 text-xs font-medium">Account is Active</span>
            </label>
        </div>

        <div class="border-t border-slate-800 pt-4">
            <h3 class="text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Change Password</h3>
            <p class="text-xs text-slate-500 mb-3">Leave password fields blank if you do not wish to update the current password.</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="password" class="label">New Password</label>
                    <input id="password" name="password" type="password" minlength="8" class="input w-full" placeholder="Min. 8 characters" />
                    @error('password')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password_confirmation" class="label">Confirm New Password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" minlength="8" class="input w-full" placeholder="Re-enter password" />
                </div>
            </div>
        </div>

        <div class="pt-4 flex justify-end gap-3 border-t border-slate-800">
            <a href="{{ route('admin.users.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">Update Account</button>
        </div>
    </form>
</div>
</x-layouts.admin>
