<x-layouts.admin :title="'Event Raffles'">
<div class="page-header flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="page-title text-2xl font-bold text-white tracking-tight">Event Raffles</h1>
        <p class="text-xs text-slate-400 mt-1">Manage attendee raffles. Exactly 1 entry per verified attendee. Bonus points are credited via transaction ledger.</p>
    </div>
    <div>
        <button onclick="document.getElementById('createRaffleModal').classList.remove('hidden')" class="btn-primary text-xs flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Create Raffle</span>
        </button>
    </div>
</div>

@if(session('success'))
    <div class="mb-4 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 text-sm">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="mb-4 p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-300 text-sm">
        {{ session('error') }}
    </div>
@endif

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="bg-slate-900/50 text-slate-400 font-semibold border-b border-slate-800">
                <tr>
                    <th class="p-3">Raffle Title</th>
                    <th class="p-3">Event</th>
                    <th class="p-3">Prize</th>
                    <th class="p-3">Bonus Points</th>
                    <th class="p-3">Entries</th>
                    <th class="p-3">Status</th>
                    <th class="p-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
                @forelse($raffles as $raffle)
                    <tr class="hover:bg-slate-800/30 transition-colors">
                        <td class="p-3 font-semibold text-white">
                            <a href="{{ route('admin.raffles.show', $raffle) }}" class="hover:text-indigo-400 transition-colors">
                                {{ $raffle->title }}
                            </a>
                        </td>
                        <td class="p-3 text-slate-300">{{ $raffle->event->name ?? 'N/A' }}</td>
                        <td class="p-3 text-amber-300">{{ $raffle->prize_name }}</td>
                        <td class="p-3 text-emerald-400">+{{ number_format($raffle->points_bonus) }} pts</td>
                        <td class="p-3 text-slate-400">{{ $raffle->entries->count() }} attendees</td>
                        <td class="p-3">
                            @if($raffle->isDrawn())
                                <span class="badge badge-success">Drawn ({{ $raffle->winners->count() }} winners)</span>
                            @else
                                <span class="badge badge-warning">Pending Draw</span>
                            @endif
                        </td>
                        <td class="p-3 text-right space-x-2">
                            <a href="{{ route('admin.raffles.show', $raffle) }}" class="btn-ghost btn-xs">View</a>
                            @if(!$raffle->isDrawn())
                                <form action="{{ route('admin.raffles.draw', $raffle) }}" method="POST" class="inline" onsubmit="return confirm('Execute randomized draw for verified attendees?');">
                                    @csrf
                                    <button type="submit" class="btn-primary btn-xs">Draw Now</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center text-slate-500">
                            No raffles found. Create a raffle tied to an event to award attendees!
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($raffles->hasPages())
        <div class="p-3 border-t border-slate-800">
            {{ $raffles->links() }}
        </div>
    @endif
</div>

<!-- Create Modal -->
<div id="createRaffleModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-lg p-6 shadow-2xl">
        <h3 class="text-lg font-bold text-white mb-4">Create Event Raffle</h3>
        <form action="{{ route('admin.raffles.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-medium text-slate-300 mb-1">Target Event</label>
                <select name="event_id" required class="form-input w-full">
                    <option value="">Select an Event...</option>
                    @foreach($events as $event)
                        <option value="{{ $event->id }}">{{ $event->name }} ({{ $event->event_date?->format('M d, Y') }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-300 mb-1">Raffle Title</label>
                <input type="text" name="title" required placeholder="e.g. Mid-Year Tech Summit Grand Raffle" class="form-input w-full">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1">Prize Name</label>
                    <input type="text" name="prize_name" required placeholder="e.g. Raspberry Pi 4 / Swag Kit" class="form-input w-full">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1">Link to Reward Catalog (Optional)</label>
                    <select name="reward_id" class="form-input w-full">
                        <option value="">-- No specific catalog reward --</option>
                        @foreach($rewards as $rew)
                            <option value="{{ $rew->id }}">{{ $rew->title }} ({{ $rew->category }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Entry Mode & Pricing -->
            <div x-data="{ mode: 'attendance' }" class="p-3 rounded-xl bg-slate-950/60 border border-slate-800 space-y-3">
                <label class="block text-xs font-semibold text-slate-200">Participant Entry Mode & Requirements</label>
                <div class="grid grid-cols-3 gap-2">
                    <label class="flex items-center gap-2 p-2 rounded-lg border border-slate-800 text-[11px] cursor-pointer hover:bg-slate-800/40"
                           :class="mode === 'attendance' ? 'bg-indigo-600/20 border-indigo-500 text-indigo-300 font-bold' : 'text-slate-400'">
                        <input type="radio" name="entry_type" value="attendance" x-model="mode" class="sr-only">
                        <span>Verified Attendance</span>
                    </label>
                    <label class="flex items-center gap-2 p-2 rounded-lg border border-slate-800 text-[11px] cursor-pointer hover:bg-slate-800/40"
                           :class="mode === 'points_purchase' ? 'bg-indigo-600/20 border-indigo-500 text-indigo-300 font-bold' : 'text-slate-400'">
                        <input type="radio" name="entry_type" value="points_purchase" x-model="mode" class="sr-only">
                        <span>Points Purchase</span>
                    </label>
                    <label class="flex items-center gap-2 p-2 rounded-lg border border-slate-800 text-[11px] cursor-pointer hover:bg-slate-800/40"
                           :class="mode === 'tickets' ? 'bg-indigo-600/20 border-indigo-500 text-indigo-300 font-bold' : 'text-slate-400'">
                        <input type="radio" name="entry_type" value="tickets" x-model="mode" class="sr-only">
                        <span>Event Ticket</span>
                    </label>
                </div>

                <div class="grid grid-cols-2 gap-3 pt-1">
                    <div x-show="mode === 'points_purchase'">
                        <label class="block text-[11px] font-medium text-slate-300 mb-1">Points Cost Per Entry</label>
                        <input type="number" name="points_per_entry" min="0" value="25" class="form-input w-full text-xs">
                        <p class="text-[10px] text-slate-500 mt-0.5">Deducted from student's balance upon entry</p>
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-slate-300 mb-1">Max Entries Per User</label>
                        <input type="number" name="max_entries_per_user" min="1" max="50" value="1" required class="form-input w-full text-xs">
                        <p class="text-[10px] text-slate-500 mt-0.5">Maximum entries allowed per student</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1">Bonus Redeem Points</label>
                    <input type="number" name="points_bonus" min="0" value="100" class="form-input w-full">
                    <p class="text-[11px] text-slate-500 mt-1">Awarded to winner's balance</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1">Winner Count</label>
                    <input type="number" name="winner_count" min="1" max="20" value="1" class="form-input w-full">
                    <p class="text-[11px] text-slate-500 mt-1">Number of winners drawn</p>
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-300 mb-1">Description / Notes (Optional)</label>
                <textarea name="description" rows="2" class="form-input w-full"></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-3">
                <button type="button" onclick="document.getElementById('createRaffleModal').classList.add('hidden')" class="btn-ghost text-xs">Cancel</button>
                <button type="submit" class="btn-primary text-xs">Create Raffle</button>
            </div>
        </form>
    </div>
</div>
</x-layouts.admin>
