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

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="bg-slate-950/60 text-slate-400 border-b border-slate-800">
                <tr>
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
                                    <button type="submit" class="btn-secondary btn-sm text-red-400 border-red-500/20" onclick="return confirm('Reject this registration?')">Reject</button>
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
                        <td colspan="11" class="py-10 text-center text-slate-500">
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
</x-layouts.admin>
