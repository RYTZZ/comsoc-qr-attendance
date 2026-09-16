<x-layouts.admin :title="'Schools & Universities'">
<div class="page-header">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="page-title">Schools & Universities</h1>
            <p class="text-xs text-slate-400 mt-0.5">Configure the active educational institutions available in non-student registration.</p>
        </div>

        <button type="button"
                @click="$dispatch('open-modal', 'create-org-modal')"
                class="btn-primary btn-sm">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Add School / University
        </button>
    </div>
</div>

<div class="card p-4 mb-6">
    <form method="GET" action="{{ route('admin.organizations.index') }}" class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex-1 min-w-[200px] max-w-md relative">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Search institution name..."
                   class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 pl-9 text-xs text-white placeholder-slate-500 focus:ring-2 focus:ring-brand-500 focus:outline-none">
            <svg class="w-4 h-4 text-slate-500 absolute left-3 top-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </div>

        <div class="w-48">
            <x-custom-dropdown name="status"
                               :options="['' => 'All Statuses', 'active' => 'Active Only', 'inactive' => 'Inactive Only']"
                               :value="request('status')"
                               placeholder="All Statuses" />
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.organizations.index') }}" class="btn-secondary btn-sm">Reset</a>
            <button type="submit" class="btn-primary btn-sm">Filter</button>
        </div>
    </form>
</div>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="bg-slate-950/60 text-slate-400 border-b border-slate-800">
                <tr>
                    <th class="py-3 px-4">School / University Name</th>
                    <th class="py-3 px-4">Status</th>
                    <th class="py-3 px-4">Registrations</th>
                    <th class="py-3 px-4">Created Date</th>
                    <th class="py-3 px-4">Updated Date</th>
                    <th class="py-3 px-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
                @forelse($organizations as $org)
                    <tr class="hover:bg-slate-800/30 transition"
                        x-data="{
                            isEditing: false,
                            orgName: '{{ addslashes($org->name) }}',
                            isActive: {{ $org->is_active ? 'true' : 'false' }}
                        }">
                        <td class="py-3 px-4 font-semibold text-white">
                            <span x-show="!isEditing">{{ $org->name }}</span>
                            <form x-show="isEditing" x-cloak method="POST" action="{{ route('admin.organizations.update', $org) }}" id="edit-form-{{ $org->id }}" class="flex items-center gap-2">
                                @csrf
                                @method('PUT')
                                <input type="text" name="name" x-model="orgName" required
                                       class="rounded-lg bg-slate-950 border border-slate-700 px-2.5 py-1 text-xs text-white focus:ring-2 focus:ring-brand-500">
                                <label class="flex items-center gap-1.5 text-[11px] text-slate-300">
                                    <input type="checkbox" name="is_active" value="1" x-model="isActive" class="rounded bg-slate-950 border-slate-700 text-brand-500">
                                    Active
                                </label>
                            </form>
                        </td>

                        <td class="py-3 px-4">
                            @if($org->is_active)
                                <span class="badge badge-active">Active</span>
                            @else
                                <span class="badge bg-slate-800 text-slate-400 border-slate-700">Inactive</span>
                            @endif
                        </td>

                        <td class="py-3 px-4 text-slate-300">
                            {{ $org->event_registrations_count }} participants
                        </td>

                        <td class="py-3 px-4 text-slate-400 whitespace-nowrap">
                            {{ $org->created_at->format('M d, Y h:i A') }}
                        </td>

                        <td class="py-3 px-4 text-slate-400 whitespace-nowrap">
                            {{ $org->updated_at->format('M d, Y h:i A') }}
                        </td>

                        <td class="py-3 px-4 text-right space-x-1 whitespace-nowrap">
                            <template x-if="!isEditing">
                                <div class="inline-flex items-center gap-1">
                                    <button type="button" @click="isEditing = true" class="btn-secondary btn-sm">
                                        Edit
                                    </button>

                                    <form method="POST" action="{{ route('admin.organizations.toggle-active', $org) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="btn-secondary btn-sm {{ $org->is_active ? 'text-amber-400 border-amber-500/20' : 'text-emerald-400 border-emerald-500/20' }}">
                                            {{ $org->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>

                                    @if($org->event_registrations_count === 0)
                                        <form method="POST" action="{{ route('admin.organizations.destroy', $org) }}" class="inline" onsubmit="return confirm('Delete this institution?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-secondary btn-sm text-red-400 border-red-500/20">
                                                Delete
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </template>

                            <template x-if="isEditing">
                                <div class="inline-flex items-center gap-1">
                                    <button type="submit" form="edit-form-{{ $org->id }}" class="btn-primary btn-sm">
                                        Save
                                    </button>
                                    <button type="button" @click="isEditing = false" class="btn-secondary btn-sm">
                                        Cancel
                                    </button>
                                </div>
                            </template>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-10 text-center text-slate-500">
                            No schools or universities found. Click "Add School / University" to create one.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($organizations, 'links'))
        <div class="p-4 border-t border-slate-800">
            {{ $organizations->links() }}
        </div>
    @endif
</div>

<x-modal name="create-org-modal" focusable>
    <form method="POST" action="{{ route('admin.organizations.store') }}" class="p-6">
        @csrf
        <h2 class="text-base font-semibold text-white mb-1">Add School / University</h2>
        <p class="text-xs text-slate-400 mb-4">Add a new educational institution to appear in the non-student registration form.</p>

        <div class="space-y-4 text-xs">
            <div>
                <label for="org_name" class="block font-medium text-slate-300 mb-1">Institution Name <span class="text-red-400">*</span></label>
                <input id="org_name" name="name" type="text" required autofocus
                       placeholder="e.g., SorSU - Bulan Campus"
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2 text-sm text-white placeholder-slate-500 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                @error('name')
                    <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <label class="flex items-center gap-2 p-3 rounded-xl bg-slate-950 border border-slate-800 cursor-pointer">
                <input type="checkbox" name="is_active" value="1" checked
                       class="rounded bg-slate-900 border-slate-700 text-brand-500 focus:ring-brand-400">
                <span class="text-slate-300 text-xs font-medium">Set as active immediately</span>
            </label>
        </div>

        <div class="mt-6 flex items-center justify-end gap-2">
            <button type="button" @click="$dispatch('close-modal', 'create-org-modal')" class="btn-secondary btn-sm">
                Cancel
            </button>
            <button type="submit" class="btn-primary btn-sm">
                Add Institution
            </button>
        </div>
    </form>
</x-modal>
</x-layouts.admin>
