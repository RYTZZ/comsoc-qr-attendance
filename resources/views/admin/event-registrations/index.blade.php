<x-layouts.admin :title="'Guest Registrations — ' . $event->name">
<div class="page-header">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.events.show', $event) }}" class="btn-secondary btn-sm">← Back to Event</a>
            <div>
                <h1 class="page-title">Registrations: {{ $event->name }}</h1>
                <p class="text-xs text-slate-400 mt-0.5">Manage non-student participant registrations, verify details, and issue physical event QR passes.</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn-secondary btn-sm">
                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Export CSV
            </a>
            <a href="{{ route('public.register') }}" target="_blank" class="btn-secondary btn-sm text-brand-400">
                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
                Public Registration Form
            </a>
        </div>
    </div>
</div>

<div class="card p-4 mb-6">
    <form method="GET" action="{{ route('admin.event-registrations.index', $event) }}" class="space-y-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
            <div class="lg:col-span-2">
                <label class="block text-[11px] font-medium text-slate-400 uppercase tracking-wider mb-1">Search Participant</label>
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Name, email, school, program..."
                           class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 pl-9 text-xs text-white placeholder-slate-500 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    <svg class="w-4 h-4 text-slate-500 absolute left-3 top-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-medium text-slate-400 uppercase tracking-wider mb-1">Status</label>
                <x-custom-dropdown name="status"
                                   :options="['' => 'All Statuses', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected']"
                                   :value="request('status')"
                                   placeholder="All Statuses" />
            </div>

            <div>
                <label class="block text-[11px] font-medium text-slate-400 uppercase tracking-wider mb-1">T-Shirt Size</label>
                <x-custom-dropdown name="tshirt_size"
                                   :options="array_merge(['' => 'All Sizes'], array_combine($tshirtSizes, $tshirtSizes))"
                                   :value="request('tshirt_size')"
                                   placeholder="All Sizes" />
            </div>

            <div>
                <label class="block text-[11px] font-medium text-slate-400 uppercase tracking-wider mb-1">Year Level</label>
                <x-custom-dropdown name="year_level"
                                   :options="array_merge(['' => 'All Levels'], array_combine($yearLevels, $yearLevels))"
                                   :value="request('year_level')"
                                   placeholder="All Levels" />
            </div>

            <div>
                <label class="block text-[11px] font-medium text-slate-400 uppercase tracking-wider mb-1">Program</label>
                <x-custom-dropdown name="program"
                                   :options="array_merge(['' => 'All Programs'], array_combine($programs, $programs))"
                                   :value="request('program')"
                                   placeholder="All Programs" />
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-slate-800/80">
            <div class="w-full sm:w-64">
                <x-custom-dropdown name="food_restrictions"
                                   :options="array_merge(['' => 'All Food Restrictions'], array_combine($foodRestrictions, $foodRestrictions))"
                                   :value="request('food_restrictions')"
                                   placeholder="All Food Restrictions" />
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.event-registrations.index', $event) }}" class="btn-secondary btn-sm">Reset</a>
                <button type="submit" class="btn-primary btn-sm">Apply Filters</button>
            </div>
        </div>
    </form>
</div>

<div x-data="{
    selected: [],
    allPendingIds: {{ json_encode($registrations->where('status', 'pending')->pluck('id')->values()) }},
    toggleAll() {
        if (this.selected.length === this.allPendingIds.length) {
            this.selected = [];
        } else {
            this.selected = [...this.allPendingIds];
        }
    }
}" class="relative">
    <div class="card overflow-hidden p-0 border border-slate-800">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs table-sticky-header">
                <thead class="bg-slate-950/60 text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="py-3 px-4 w-10">
                            <input type="checkbox"
                                   @click="toggleAll()"
                                   :checked="allPendingIds.length > 0 && selected.length === allPendingIds.length"
                                   :disabled="allPendingIds.length === 0"
                                   class="rounded bg-slate-900 border-slate-700 text-[#7A1618] focus:ring-[#7A1618]">
                        </th>
                        <th class="py-3 px-4">Participant Name</th>
                        <th class="py-3 px-4">Email</th>
                        <th class="py-3 px-4">School / Univ</th>
                        <th class="py-3 px-4">Program</th>
                        <th class="py-3 px-4">Year Level</th>
                        <th class="py-3 px-4">T-Shirt</th>
                        <th class="py-3 px-4">Food</th>
                        <th class="py-3 px-4">Confirmed</th>
                        <th class="py-3 px-4">Reg Date</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($registrations as $reg)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="py-3 px-4">
                                @if($reg->status === 'pending')
                                    <input type="checkbox"
                                           value="{{ $reg->id }}"
                                           x-model="selected"
                                           class="rounded bg-slate-900 border-slate-700 text-[#7A1618] focus:ring-[#7A1618]">
                                @endif
                            </td>
                            <td class="py-3 px-4 font-semibold text-white">
                                <a href="{{ route('admin.event-registrations.show', $reg) }}" class="hover:text-brand-400 transition">
                                    {{ $reg->full_name }}
                                </a>
                            </td>
                            <td class="py-3 px-4 text-slate-300 font-mono text-[11px]">{{ $reg->email }}</td>
                            <td class="py-3 px-4 text-slate-400 max-w-[140px] truncate" title="{{ $reg->organization }}">{{ $reg->organization ?? '—' }}</td>
                            <td class="py-3 px-4 text-slate-300">{{ $reg->program ?? '—' }}</td>
                            <td class="py-3 px-4 text-slate-300">{{ $reg->year_level ?? '—' }}</td>
                            <td class="py-3 px-4 font-semibold text-brand-400">{{ $reg->tshirt_size ?? '—' }}</td>
                            <td class="py-3 px-4 text-slate-400">
                                <span title="{{ $reg->food_restriction_details }}">{{ $reg->food_restrictions ?? 'None' }}</span>
                            </td>
                            <td class="py-3 px-4">
                                @if($reg->confirmed)
                                    <span class="text-emerald-400 inline-flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Yes
                                    </span>
                                @else
                                    <span class="text-slate-500">No</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-slate-400 whitespace-nowrap">{{ $reg->created_at->format('M d, Y') }}</td>
                            <td class="py-3 px-4">
                                @if($reg->status === 'approved')
                                    <span class="badge badge-active">Approved</span>
                                @elseif($reg->status === 'rejected')
                                    <span class="badge bg-red-500/10 text-red-400 border-red-500/20">Rejected</span>
                                @else
                                    <span class="badge bg-yellow-500/10 text-yellow-400 border-yellow-500/20">Pending</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right space-x-1 whitespace-nowrap">
                                <a href="{{ route('admin.event-registrations.show', $reg) }}" class="btn-secondary btn-sm" title="View Full Details">
                                    Details
                                </a>

                                @if($reg->status === 'pending')
                                    <form method="POST" action="{{ route('admin.event-registrations.approve', $reg) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="btn-primary btn-sm">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.event-registrations.reject', $reg) }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="reason" value="Administrative decision">
                                        <button type="button"
                                                data-confirm="Reject this participant registration?"
                                                data-confirm-title="Reject Registration"
                                                data-confirm-type="danger"
                                                data-confirm-btn="Reject Registration"
                                                class="btn-secondary btn-sm text-red-400 border-red-500/20">Reject</button>
                                    </form>
                                @elseif($reg->status === 'approved')
                                    <a href="{{ route('admin.event-registrations.qr', $reg) }}" class="btn-secondary btn-sm text-brand-400" title="Download Print-Ready QR">
                                        QR Pass
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="py-10 text-center text-slate-500">
                                No participant registrations matching your search or filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($registrations, 'links'))
            <div class="p-4 border-t border-slate-800">
                {{ $registrations->links() }}
            </div>
        @endif
    </div>

    <!-- Floating Batch Action Bar -->
    <div x-show="selected.length > 0"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-4"
         class="fixed bottom-6 left-1/2 -translate-x-1/2 z-40 bg-[#171a23]/95 border border-slate-700 shadow-2xl rounded-2xl px-5 py-3 flex items-center gap-4 backdrop-blur-md"
         x-cloak>
        <span class="text-xs font-semibold text-white">
            <span x-text="selected.length" class="text-brand-400 font-mono"></span> pending selected
        </span>
        <div class="h-4 w-px bg-slate-700"></div>
        <form method="POST" action="{{ route('admin.event-registrations.batch-approve') }}" class="inline">
            @csrf
            <template x-for="id in selected" :key="id">
                <input type="hidden" name="registration_ids[]" :value="id">
            </template>
            <button type="submit" class="btn-primary btn-sm text-xs py-1.5 px-3">
                Approve Selected
            </button>
        </form>
        <form method="POST" action="{{ route('admin.event-registrations.batch-reject') }}" class="inline">
            @csrf
            <template x-for="id in selected" :key="id">
                <input type="hidden" name="registration_ids[]" :value="id">
            </template>
            <input type="hidden" name="reason" value="Bulk rejection by administrator">
            <button type="submit" class="btn-secondary btn-sm text-xs py-1.5 px-3 text-red-400 border-red-500/20">
                Reject Selected
            </button>
        </form>
        <button type="button" @click="selected = []" class="text-slate-400 hover:text-white text-xs">
            Cancel
        </button>
    </div>
</div>
</x-layouts.admin>
