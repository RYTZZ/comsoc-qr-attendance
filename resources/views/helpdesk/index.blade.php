<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-rose-500/20 text-rose-300 border border-rose-500/30">
                    Dispute & Resolution Support
                </span>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-[#F8FAFC] font-brand-display tracking-tight mt-1">
                    Member Help Desk & Dispute Center
                </h1>
                <p class="text-xs text-[#94A3B8] mt-0.5">
                    Official channel for attendance discrepancies, EXP balance adjustments, QR scanning problems, and organizational concerns.
                </p>
            </div>

            <a href="{{ route('helpdesk.create') }}" class="btn-primary text-xs flex items-center gap-1.5 shadow-lg">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span>Submit New Ticket</span>
            </a>
        </div>
    </x-slot>

    <div class="py-8 bg-[#0B1120]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if(session('status'))
            <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-semibold flex items-center gap-2">
                <i data-lucide="check-circle" class="w-4 h-4 shrink-0"></i>
                <span>{{ session('status') }}</span>
            </div>
            @endif

            <!-- Quick Metrics -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="card bg-[#172033] border border-[#334155] p-4 text-center">
                    <span class="block text-xs text-[#94A3B8]">Total Filed</span>
                    <span class="text-2xl font-black text-white font-brand-display">{{ $stats['total'] }}</span>
                </div>
                <div class="card bg-[#172033] border border-amber-500/30 p-4 text-center">
                    <span class="block text-xs text-amber-400">Open & Active</span>
                    <span class="text-2xl font-black text-amber-300 font-brand-display">{{ $stats['open'] }}</span>
                </div>
                <div class="card bg-[#172033] border border-emerald-500/30 p-4 text-center">
                    <span class="block text-xs text-emerald-400">Resolved</span>
                    <span class="text-2xl font-black text-emerald-300 font-brand-display">{{ $stats['resolved'] }}</span>
                </div>
                <div class="card bg-[#172033] border border-[#334155] p-4 text-center">
                    <span class="block text-xs text-slate-400">Closed</span>
                    <span class="text-2xl font-black text-slate-300 font-brand-display">{{ $stats['closed'] }}</span>
                </div>
            </div>

            <!-- Filters -->
            <div class="card bg-[#172033] border border-[#334155] p-4 flex flex-wrap items-center justify-between gap-3">
                <form method="GET" action="{{ route('helpdesk.index') }}" class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
                    <div>
                        <select name="status" onchange="this.form.submit()" class="bg-[#0B1120] text-xs text-white border border-[#334155] rounded-xl px-3 py-2">
                            <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All Statuses</option>
                            <option value="open" {{ $status === 'open' ? 'selected' : '' }}>Open</option>
                            <option value="in_review" {{ $status === 'in_review' ? 'selected' : '' }}>In Review</option>
                            <option value="awaiting_member" {{ $status === 'awaiting_member' ? 'selected' : '' }}>Awaiting Member</option>
                            <option value="resolved" {{ $status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                            <option value="closed" {{ $status === 'closed' ? 'selected' : '' }}>Closed</option>
                        </select>
                    </div>

                    <div>
                        <select name="category" onchange="this.form.submit()" class="bg-[#0B1120] text-xs text-white border border-[#334155] rounded-xl px-3 py-2">
                            <option value="all" {{ $category === 'all' ? 'selected' : '' }}>All Categories</option>
                            @foreach(\App\Models\SupportTicket::CATEGORIES as $k => $label)
                            <option value="{{ $k }}" {{ $category === $k ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if($status !== 'all' || $category !== 'all')
                    <a href="{{ route('helpdesk.index') }}" class="text-xs text-[#df747a] hover:underline font-semibold">Reset Filter</a>
                    @endif
                </form>
            </div>

            <!-- Tickets List -->
            <div class="card bg-[#172033] border border-[#334155] shadow-xl overflow-hidden">
                @if($tickets->isEmpty())
                <div class="p-12 text-center">
                    <i data-lucide="inbox" class="w-12 h-12 mx-auto text-[#94A3B8] mb-3"></i>
                    <h3 class="text-sm font-bold text-white font-brand-display">No support requests found</h3>
                    <p class="text-xs text-[#94A3B8] mt-1">If you encountered an issue with your attendance scan, EXP points, or rewards, log a ticket anytime.</p>
                    <a href="{{ route('helpdesk.create') }}" class="mt-4 inline-block btn-primary text-xs py-1.5 px-4">
                        Submit a Request
                    </a>
                </div>
                @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-[#0B1120]/80 text-[#94A3B8] uppercase text-[10px] tracking-wider border-b border-[#334155]">
                            <tr>
                                <th class="px-5 py-3">Ticket</th>
                                <th class="px-5 py-3">Category</th>
                                <th class="px-5 py-3">Subject</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3">Assigned Staff</th>
                                <th class="px-5 py-3">Updated</th>
                                <th class="px-5 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#334155]/60">
                            @foreach($tickets as $tkt)
                            <tr class="hover:bg-[#1E293B]/60 transition">
                                <td class="px-5 py-3.5 font-mono text-[#df747a] font-bold whitespace-nowrap">
                                    {{ $tkt->ticket_number }}
                                </td>
                                <td class="px-5 py-3.5 text-slate-300 whitespace-nowrap">
                                    {{ $tkt->category_label }}
                                </td>
                                <td class="px-5 py-3.5">
                                    <a href="{{ route('helpdesk.show', $tkt) }}" class="font-bold text-white hover:text-[#df747a] transition-colors font-brand-display line-clamp-1">
                                        {{ $tkt->subject }}
                                    </a>
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase
                                        {{ $tkt->status === 'resolved' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : ($tkt->status === 'closed' ? 'bg-slate-700/50 text-slate-400' : ($tkt->status === 'in_review' ? 'bg-blue-500/20 text-blue-300 border border-blue-500/30' : 'bg-amber-500/20 text-amber-300 border border-amber-500/30')) }}">
                                        {{ $tkt->status_label }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-[#94A3B8] whitespace-nowrap">
                                    {{ $tkt->assignedStaff ? $tkt->assignedStaff->name : 'Queue Unassigned' }}
                                </td>
                                <td class="px-5 py-3.5 text-slate-400 whitespace-nowrap">
                                    {{ $tkt->updated_at->diffForHumans() }}
                                </td>
                                <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                    <a href="{{ route('helpdesk.show', $tkt) }}" class="btn-secondary text-[11px] py-1 px-2.5">
                                        View Thread
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-[#334155]">
                    {{ $tickets->links() }}
                </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
