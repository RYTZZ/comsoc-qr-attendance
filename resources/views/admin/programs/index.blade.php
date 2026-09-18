<x-layouts.admin :title="'Programs & Courses'">
<div class="page-header">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="page-title font-heading">Programs / Courses</h1>
            <p class="text-xs text-slate-400 mt-0.5">Manage centralized academic programs and courses used across registration, records, and filters.</p>
        </div>

        <button type="button"
                @click="$dispatch('open-modal', 'create-program-modal')"
                class="btn-primary btn-sm">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Add Program
        </button>
    </div>
</div>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="bg-slate-950/60 text-slate-400 border-b border-slate-800">
                <tr>
                    <th class="py-3 px-4">Order</th>
                    <th class="py-3 px-4">Program Name & Abbreviation</th>
                    <th class="py-3 px-4">Abbreviation</th>
                    <th class="py-3 px-4">Status</th>
                    <th class="py-3 px-4">Created Date</th>
                    <th class="py-3 px-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
                @forelse($programs as $prog)
                    <tr class="hover:bg-slate-800/30 transition"
                        x-data="{
                            isEditing: false,
                            progName: '{{ addslashes($prog->name) }}',
                            progAbbr: '{{ addslashes($prog->abbreviation) }}',
                            isActive: {{ $prog->is_active ? 'true' : 'false' }}
                        }">
                        <td class="py-3 px-4 text-slate-400 font-mono">
                            #{{ $prog->sort_order }}
                        </td>

                        <td class="py-3 px-4 font-semibold text-white">
                            <span x-show="!isEditing">{{ $prog->display_name }}</span>
                            <form x-show="isEditing" x-cloak method="POST" action="{{ route('admin.programs.update', $prog) }}" id="edit-form-{{ $prog->id }}" class="flex flex-wrap items-center gap-2">
                                @csrf
                                @method('PUT')
                                <input type="text" name="name" x-model="progName" required placeholder="Full Program Name"
                                       class="rounded-lg bg-slate-950 border border-slate-700 px-2.5 py-1 text-xs text-white focus:ring-2 focus:ring-[#7A1618] focus:outline-none min-w-[220px]">
                                <input type="text" name="abbreviation" x-model="progAbbr" required placeholder="Abbr"
                                       class="w-20 rounded-lg bg-slate-950 border border-slate-700 px-2.5 py-1 text-xs text-white focus:ring-2 focus:ring-[#7A1618] focus:outline-none">
                                <label class="flex items-center gap-1.5 text-[11px] text-slate-300">
                                    <input type="checkbox" name="is_active" value="1" x-model="isActive" class="rounded bg-slate-950 border-slate-700 text-[#7A1618]">
                                    Active
                                </label>
                            </form>
                        </td>

                        <td class="py-3 px-4 font-mono text-slate-300">
                            <span class="px-2 py-0.5 rounded bg-slate-800 border border-slate-700 font-semibold text-[11px] text-[#cc7478]">
                                {{ $prog->abbreviation }}
                            </span>
                        </td>

                        <td class="py-3 px-4">
                            @if($prog->is_active)
                                <span class="badge badge-active">Active</span>
                            @else
                                <span class="badge bg-slate-800 text-slate-400 border-slate-700">Inactive</span>
                            @endif
                        </td>

                        <td class="py-3 px-4 text-slate-400 whitespace-nowrap">
                            {{ $prog->created_at ? $prog->created_at->format('M d, Y h:i A') : 'Default' }}
                        </td>

                        <td class="py-3 px-4 text-right space-x-1 whitespace-nowrap">
                            <template x-if="!isEditing">
                                <div class="inline-flex items-center gap-1">
                                    <button type="button" @click="isEditing = true" class="btn-secondary btn-sm">
                                        Edit
                                    </button>

                                    <form method="POST" action="{{ route('admin.programs.toggle-active', $prog) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="btn-secondary btn-sm {{ $prog->is_active ? 'text-amber-400 border-amber-500/20' : 'text-emerald-400 border-emerald-500/20' }}">
                                            {{ $prog->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('admin.programs.destroy', $prog) }}" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button"
                                                data-confirm="Delete this academic program? This action cannot be undone."
                                                data-confirm-title="Delete Program"
                                                data-confirm-type="danger"
                                                data-confirm-btn="Delete Program"
                                                class="btn-secondary btn-sm text-red-400 border-red-500/20">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </template>

                            <template x-if="isEditing">
                                <div class="inline-flex items-center gap-1">
                                    <button type="submit" form="edit-form-{{ $prog->id }}" class="btn-primary btn-sm">
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
                            No programs found. Click "Add Program" to create one.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<x-modal name="create-program-modal" focusable>
    <form method="POST" action="{{ route('admin.programs.store') }}" class="p-6">
        @csrf
        <h2 class="text-base font-semibold text-white mb-1">Add Program / Course</h2>
        <p class="text-xs text-slate-400 mb-4">Add a new academic program option. The full title and abbreviation will be formatted automatically.</p>

        <div class="space-y-4 text-xs">
            <div>
                <label for="prog_name" class="block font-medium text-slate-300 mb-1">Program Full Name <span class="text-red-400">*</span></label>
                <input id="prog_name" name="name" type="text" required autofocus
                       placeholder="e.g., Bachelor of Science in Information Technology"
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2 text-sm text-white placeholder-slate-500 focus:ring-2 focus:ring-[#7A1618] focus:outline-none">
                @error('name')
                    <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="prog_abbr" class="block font-medium text-slate-300 mb-1">Abbreviation <span class="text-red-400">*</span></label>
                <input id="prog_abbr" name="abbreviation" type="text" required
                       placeholder="e.g., BSIT"
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2 text-sm text-white placeholder-slate-500 focus:ring-2 focus:ring-[#7A1618] focus:outline-none">
                @error('abbreviation')
                    <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mt-6 flex items-center justify-end gap-2">
            <button type="button" @click="$dispatch('close-modal', 'create-program-modal')" class="btn-secondary btn-sm">
                Cancel
            </button>
            <button type="submit" class="btn-primary btn-sm">
                Add Program
            </button>
        </div>
    </form>
</x-modal>
</x-layouts.admin>
