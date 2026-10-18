<x-layouts.admin :title="'Help Desk & Member Disputes'">
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-white font-brand-display tracking-tight">
                Help Desk & Member Disputes Inbox
            </h1>
            <p class="text-xs text-slate-400 mt-1">
                Investigate attendance discrepancies, EXP disputes, reward fulfillment issues, and staff escalations.
            </p>
        </div>
    </div>

    <div class="py-6 space-y-6">

        @if(session('status'))
        <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-semibold flex items-center gap-2">
            <i data-lucide="check-circle" class="w-4 h-4 shrink-0"></i>
            <span>{{ session('status') }}</span>
        </div>
        @endif

        <!-- Status Counters -->
        <div class="grid grid-cols-2 sm:grid-cols-6 gap-3">
            <div class="card bg-slate-900 border border-slate-800 p-3 text-center">
                <span class="block text-[11px] text-slate-400">Open</span>
                <span class="text-xl font-black text-rose-400 font-brand-display">{{ $counts['open'] }}</span>
            </div>
            <div class="card bg-slate-900 border border-slate-800 p-3 text-center">
                <span class="block text-[11px] text-slate-400">In Review</span>
                <span class="text-xl font-black text-blue-400 font-brand-display">{{ $counts['in_review'] }}</span>
            </div>
            <div class="card bg-slate-900 border border-slate-800 p-3 text-center">
                <span class="block text-[11px] text-slate-400">Awaiting Member</span>
                <span class="text-xl font-black text-amber-400 font-brand-display">{{ $counts['awaiting_member'] }}</span>
            </div>
            <div class="card bg-slate-900 border border-slate-800 p-3 text-center">
                <span class="block text-[11px] text-slate-400">Unassigned</span>
                <span class="text-xl font-black text-purple-400 font-brand-display">{{ $counts['unassigned'] }}</span>
            </div>
            <div class="card bg-slate-900 border border-slate-800 p-3 text-center">
                <span class="block text-[11px] text-slate-400">Resolved</span>
                <span class="text-xl font-black text-emerald-400 font-brand-display">{{ $counts['resolved'] }}</span>
            </div>
            <div class="card bg-slate-900 border border-slate-800 p-3 text-center">
                <span class="block text-[11px] text-slate-400">Closed</span>
                <span class="text-xl font-black text-slate-400 font-brand-display">{{ $counts['closed'] }}</span>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="card bg-slate-900 border border-slate-800 p-4">
            <form method="GET" action="{{ route('admin.support.index') }}" class="grid grid-cols-1 sm:grid-cols-5 gap-3">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search ticket #, member, or subject..."
                       class="bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">

                <select name="status" class="bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                    <option value="all">All Statuses</option>
                    @foreach(\App\Models\SupportTicket::STATUSES as $k => $l)
                    <option value="{{ $k }}" {{ $status === $k ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>

                <select name="category" class="bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                    <option value="all">All Categories</option>
                    @foreach(\App\Models\SupportTicket::CATEGORIES as $k => $l)
                    <option value="{{ $k }}" {{ $category === $k ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>

                <select name="assigned_to" class="bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                    <option value="all">All Assignees</option>
                    <option value="unassigned" {{ $assignedTo === 'unassigned' ? 'selected' : '' }}>Unassigned Only</option>
                    @foreach($staffUsers as $st)
                    <option value="{{ $st->id }}" {{ $assignedTo === $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
                    @endforeach
                </select>

                <div class="flex items-center gap-2">
                    <button type="submit" class="btn-primary text-xs py-2 px-4 w-full">Filter</button>
                    <a href="{{ route('admin.support.index') }}" class="btn-secondary text-xs py-2 px-3">Reset</a>
                </div>
            </form>
        </div>

        <!-- Ticket Table -->
        <div class="card bg-slate-900 border border-slate-800 overflow-hidden shadow-xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-950 text-slate-400 uppercase text-[10px] tracking-wider border-b border-slate-800">
                        <tr>
                            <th class="px-5 py-3">Ticket</th>
                            <th class="px-5 py-3">Member</th>
                            <th class="px-5 py-3">Category</th>
                            <th class="px-5 py-3">Subject</th>
                            <th class="px-5 py-3">Priority</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">Assigned Staff</th>
                            <th class="px-5 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @forelse($tickets as $tkt)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-5 py-3 font-mono font-bold text-[#df747a] whitespace-nowrap">
                                {{ $tkt->ticket_number }}
                                @if($tkt->is_escalated)
                                <span class="ml-1 px-1.5 py-0.2 rounded bg-rose-500/30 text-rose-300 text-[9px] font-bold">ESCALATED</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <p class="font-bold text-white whitespace-nowrap">{{ $tkt->user?->name }}</p>
                                <p class="text-[10px] text-slate-400">{{ $tkt->user?->student?->student_number ?? $tkt->user?->email }}</p>
                            </td>
                            <td class="px-5 py-3 text-slate-300 whitespace-nowrap">
                                {{ $tkt->category_label }}
                            </td>
                            <td class="px-5 py-3 max-w-xs truncate">
                                <a href="{{ route('admin.support.show', $tkt) }}" class="font-semibold text-white hover:text-[#df747a]">
                                    {{ $tkt->subject }}
                                </a>
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap">
                                <span class="capitalize font-bold text-[11px] {{ $tkt->priority === 'urgent' ? 'text-rose-400' : ($tkt->priority === 'high' ? 'text-amber-400' : 'text-slate-400') }}">
                                    {{ $tkt->priority }}
                                </span>
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase
                                    {{ $tkt->status === 'resolved' ? 'bg-emerald-500/20 text-emerald-300' : ($tkt->status === 'closed' ? 'bg-slate-800 text-slate-400' : ($tkt->status === 'in_review' ? 'bg-blue-500/20 text-blue-300' : 'bg-amber-500/20 text-amber-300')) }}">
                                    {{ $tkt->status_label }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-slate-400 whitespace-nowrap">
                                {{ $tkt->assignedStaff?->name ?? 'Unassigned' }}
                            </td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('admin.support.show', $tkt) }}" class="btn-secondary text-[11px] py-1 px-3">
                                    Investigate
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-slate-400">No support tickets match the selected filters.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-800">
                {{ $tickets->links() }}
            </div>
        </div>

    </div>
</div>
</x-layouts.admin>
