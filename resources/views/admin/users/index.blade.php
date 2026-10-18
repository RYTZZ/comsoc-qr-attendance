<x-layouts.admin :title="'Account Management'">
<div class="page-header">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="page-title font-heading">System Account Management</h1>
            <p class="text-xs text-slate-400 mt-0.5">Manage administrative and scanning terminal accounts (Super Admin, Admin, Kiosk).</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.users.create', ['type' => 'admin']) }}" class="btn-primary btn-sm">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
                Create Admin
            </a>

            <a href="{{ route('admin.users.create', ['type' => 'kiosk']) }}" class="btn-secondary btn-sm text-[#cc7478] border-[#560E0F]/40 hover:bg-[#560E0F]/20">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                Create Kiosk
            </a>
        </div>
    </div>
</div>

<div class="card p-4 mb-6">
    <form method="GET" action="{{ route('admin.users.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 items-center">
        <div class="relative lg:col-span-2">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Search name, email, student #, position..."
                   class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 pl-9 text-xs text-white placeholder-slate-500 focus:ring-2 focus:ring-[#560E0F] focus:outline-none">
            <svg class="w-4 h-4 text-slate-500 absolute left-3 top-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </div>

        <div>
            <x-custom-dropdown name="role"
                               :options="$roles"
                               :value="request('role')"
                               placeholder="All Roles" />
        </div>

        <div>
            <x-custom-dropdown name="position"
                               :options="$positions"
                               :value="request('position')"
                               placeholder="All Positions" />
        </div>

        <div>
            <x-custom-dropdown name="status"
                               :options="$statuses"
                               :value="request('status')"
                               placeholder="All Statuses" />
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.users.index') }}" class="btn-secondary btn-sm" title="Reset Filters">Reset</a>
            <button type="submit" class="btn-primary btn-sm flex-1">Filter</button>
        </div>
    </form>
</div>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="bg-slate-950/60 text-slate-400 border-b border-slate-800">
                <tr>
                    <th class="py-3 px-4">Name & Position</th>
                    <th class="py-3 px-4">System Role</th>
                    <th class="py-3 px-4">Username / Email / ID</th>
                    <th class="py-3 px-4">Status & Security</th>
                    <th class="py-3 px-4">Assigned Event</th>
                    <th class="py-3 px-4">Last Activity</th>
                    <th class="py-3 px-4">Created Date</th>
                    <th class="py-3 px-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
                @forelse($users as $user)
                    <tr class="hover:bg-slate-800/30 transition">
                        <td class="py-3 px-4">
                            <div class="font-semibold text-white flex items-center gap-1.5">
                                @if($user->role === 'kiosk')
                                    <span class="w-2 h-2 rounded-full bg-[#560E0F]"></span>
                                @elseif($user->role === 'super_admin')
                                    <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                @elseif(in_array($user->role, ['president', 'executive_secretary']))
                                    <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                                @else
                                    <span class="w-2 h-2 rounded-full bg-indigo-400"></span>
                                @endif
                                {{ $user->name }}
                            </div>
                            @if($user->officer_position)
                                <div class="text-[11px] font-medium text-amber-300 flex items-center gap-1 mt-0.5">
                                    <svg class="w-3 h-3 text-amber-400/80 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                    <span>{{ $user->officer_position }}</span>
                                </div>
                            @endif
                            @if($user->kiosk)
                                <div class="text-[11px] font-mono text-[#cc7478]">
                                    Code: {{ $user->kiosk->identifier }}
                                </div>
                            @endif
                        </td>

                        <td class="py-3 px-4">
                            <span class="badge uppercase text-[10px] font-semibold
                                @if($user->role === 'super_admin') bg-rose-500/15 text-rose-300 border-rose-500/30
                                @elseif($user->role === 'president') bg-amber-500/15 text-amber-300 border-amber-500/30
                                @elseif($user->role === 'executive_secretary') bg-amber-500/15 text-amber-300 border-amber-500/30
                                @elseif($user->role === 'kiosk') bg-[#560E0F]/25 text-rose-200 border-[#560E0F]/40
                                @elseif($user->role === 'treasurer') bg-emerald-500/15 text-emerald-300 border-emerald-500/30
                                @elseif($user->role === 'auditor') bg-cyan-500/15 text-cyan-300 border-cyan-500/30
                                @elseif(in_array($user->role, ['event_staff', 'staff'])) bg-blue-500/15 text-blue-300 border-blue-500/30
                                @else bg-slate-500/15 text-slate-300 border-slate-500/30 @endif">
                                {{ str_replace('_', ' ', $user->role) }}
                            </span>
                        </td>

                        <td class="py-3 px-4">
                            <div class="font-mono text-slate-200">{{ $user->username ?? $user->email }}</div>
                            @if($user->username && $user->email && !str_ends_with($user->email, '@kiosk.local') && !str_ends_with($user->email, '@admin.local'))
                                <div class="text-[11px] text-slate-400">{{ $user->email }}</div>
                            @endif
                            @if($user->student_number)
                                <div class="text-[10px] font-mono text-slate-400">ID: {{ $user->student_number }}</div>
                            @endif
                        </td>

                        <td class="py-3 px-4">
                            <div class="flex flex-col gap-1">
                                <div>
                                    @if($user->is_active)
                                        <span class="badge badge-active">Active</span>
                                    @else
                                        <span class="badge badge-inactive">Inactive</span>
                                    @endif
                                </div>
                                @if($user->must_change_password)
                                    <div>
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-amber-500/20 text-amber-300 border border-amber-500/30" title="User must update password on next sign-in">
                                            Password Reset Required
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </td>

                        <td class="py-3 px-4">
                            @if($user->kiosk && $user->kiosk->assignedEvent)
                                <span class="px-2 py-0.5 rounded bg-slate-900 border border-slate-800 text-[11px] text-slate-300 font-medium">
                                    {{ $user->kiosk->assignedEvent->name }}
                                </span>
                            @elseif($user->role === 'kiosk')
                                <span class="text-slate-500 text-[11px]">Any / Today's Events</span>
                            @else
                                <span class="text-slate-600">—</span>
                            @endif
                        </td>

                        <td class="py-3 px-4 text-slate-400 whitespace-nowrap">
                            @php
                                $lastAct = $user->last_activity_at ?? $user->kiosk?->last_activity_at;
                            @endphp
                            {{ $lastAct ? $lastAct->diffForHumans() : 'Never' }}
                        </td>

                        <td class="py-3 px-4 text-slate-400 whitespace-nowrap">
                            {{ $user->created_at->format('M d, Y') }}
                        </td>

                        <td class="py-3 px-4 text-right space-x-1 whitespace-nowrap"
                            x-data="{ showResetModal: false }">

                            @if($user->id !== auth()->id())
                                <form method="POST" action="{{ route('admin.users.toggle-active', $user) }}" class="inline">
                                    @csrf
                                    <button type="submit"
                                            class="btn-secondary btn-sm {{ $user->is_active ? 'text-amber-400 border-amber-500/20 hover:bg-amber-950/20' : 'text-emerald-400 border-emerald-500/20 hover:bg-emerald-950/20' }}">
                                        {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                            @endif

                            <a href="{{ route('admin.users.edit', $user) }}" class="btn-secondary btn-sm">Edit</a>

                            <button type="button" @click="$dispatch('open-modal', 'reset-modal-{{ $user->id }}')" class="btn-secondary btn-sm text-slate-400 hover:text-white">
                                Reset PW
                            </button>

                            @if($user->role === 'kiosk' && $user->kiosk)
                                <a href="{{ route('kiosk.attendance', $user->kiosk) }}" target="_blank" class="btn-secondary btn-sm text-[#cc7478] border-[#560E0F]/30">
                                    Launch ↗
                                </a>
                            @endif

                            <x-modal name="reset-modal-{{ $user->id }}" focusable>
                                <form method="POST" action="{{ route('admin.users.reset-password', $user) }}" class="p-6 text-left">
                                    @csrf
                                    <h3 class="text-base font-semibold text-white mb-1">Reset Account Password</h3>
                                    <p class="text-xs text-slate-400 mb-4">Set a secure temporary or permanent password for <strong class="text-white">{{ $user->name }}</strong> ({{ $user->username ?? $user->email }}).</p>

                                    <div class="space-y-3 text-xs">
                                        <div>
                                            <label class="block font-medium text-slate-300 mb-1">New Password (min. 8 characters) <span class="text-red-400">*</span></label>
                                            <input type="password" name="password" required minlength="8"
                                                   class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2 text-sm text-white focus:ring-2 focus:ring-[#560E0F] focus:outline-none">
                                        </div>

                                        <div>
                                            <label class="block font-medium text-slate-300 mb-1">Confirm New Password <span class="text-red-400">*</span></label>
                                            <input type="password" name="password_confirmation" required minlength="8"
                                                   class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2 text-sm text-white focus:ring-2 focus:ring-[#560E0F] focus:outline-none">
                                        </div>

                                        <div class="pt-1">
                                            <label class="flex items-center gap-2 p-2.5 rounded-xl bg-slate-900 border border-slate-800 cursor-pointer">
                                                <input type="checkbox" name="require_change" value="1" checked
                                                       class="rounded bg-slate-950 border-slate-700 text-[#560E0F] focus:ring-[#560E0F]">
                                                <span class="text-slate-300 text-xs">Require password change on first login</span>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="mt-6 flex items-center justify-end gap-2">
                                        <button type="button" @click="$dispatch('close-modal', 'reset-modal-{{ $user->id }}')" class="btn-secondary btn-sm">
                                            Cancel
                                        </button>
                                        <button type="submit" class="btn-primary btn-sm">
                                            Update Password
                                        </button>
                                    </div>
                                </form>
                            </x-modal>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-10 text-center text-slate-500">
                            No accounts match the selected filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($users, 'links'))
        <div class="p-4 border-t border-slate-800">
            {{ $users->links() }}
        </div>
    @endif
</div>
</x-layouts.admin>
