<x-app-layout>
    <div class="min-h-screen bg-[#0B1120] text-[#F8FAFC] p-4 sm:p-6 lg:p-8">
        <div class="max-w-6xl mx-auto space-y-6">

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-[#334155]">
                <div>
                    <span class="text-xs font-bold tracking-wider uppercase text-[#df747a]">Inventory & Rewards Operations</span>
                    <h1 class="text-xl sm:text-2xl font-extrabold text-[#F8FAFC] tracking-tight flex items-center gap-2 font-brand-display">
                        <span>Reward Collection & Claim Desk</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-[#560E0F]/40 text-[#df747a] border border-[#8F3033]/50">
                            Authorized Staff
                        </span>
                    </h1>
                </div>

                <a href="{{ route('admin.rewards.index') }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-[#172033] hover:bg-[#1E293B] text-xs font-semibold text-[#94A3B8] hover:text-white border border-[#334155] transition">
                    <i data-lucide="package" class="w-4 h-4 text-[#df747a]"></i>
                    <span>Manage Inventory</span>
                </a>
            </div>

            <div class="bg-burgundy-deep border-2 border-[#8F3033]/60 rounded-3xl p-6 sm:p-8 shadow-2xl relative overflow-hidden">
                <div class="max-w-xl mx-auto text-center space-y-4">
                    <div>
                        <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold bg-[#560E0F] text-white border border-[#8F3033]/60 mb-2 font-brand-display">
                            <i data-lucide="scan" class="w-3.5 h-3.5 text-[#df747a]"></i>
                            Single-Use Claim Code Verification
                        </span>
                        <h2 class="text-lg sm:text-xl font-extrabold text-white font-brand-display">Verify & Mark Item as Collected</h2>
                        <p class="text-xs text-slate-300">Prompt member for their unique claim code (e.g., <code class="text-amber-400 font-mono font-bold">CMS-XXXX-XXXX</code>). Claim codes are usable only once.</p>
                    </div>

                    <form method="POST" action="{{ route('admin.rewards.collection.verify') }}" class="flex flex-col sm:flex-row gap-2 max-w-md mx-auto">
                        @csrf
                        <input type="text"
                               name="claim_code"
                               placeholder="Enter member claim code"
                               required
                               class="flex-1 bg-[#0B1120] border-2 border-[#334155] focus:border-[#8F3033] rounded-2xl px-4 py-3 text-sm text-white font-mono uppercase tracking-widest placeholder:normal-case placeholder:tracking-normal placeholder:text-[#94A3B8] focus:outline-none focus:ring-2 focus:ring-[#8F3033]/40">
                        <button type="submit" class="btn-primary shrink-0 rounded-2xl py-3 px-6 shadow-xl">
                            <i data-lucide="check-circle" class="w-4 h-4"></i>
                            <span>Confirm Collection</span>
                        </button>
                    </form>

                    @if(session('success'))
                        <div class="p-3 bg-emerald-500/10 border border-emerald-500/30 rounded-xl text-xs text-emerald-400 flex items-center justify-center gap-2">
                            <i data-lucide="check" class="w-4 h-4 shrink-0"></i>
                            <span>{{ session('success') }}</span>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="p-3 bg-rose-500/10 border border-rose-500/30 rounded-xl text-xs text-rose-400 flex items-center justify-center gap-2">
                            <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i>
                            <span>{{ session('error') }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-[#172033] border border-[#334155] rounded-3xl p-5 sm:p-6 shadow-xl space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.rewards.collection', ['status' => 'pending_collection']) }}"
                           class="px-3 py-1.5 rounded-xl text-xs font-bold transition border {{ $statusFilter === 'pending_collection' ? 'bg-burgundy-gradient text-white border-[#8F3033] shadow-md' : 'bg-[#0B1120] text-[#94A3B8] hover:text-white border-[#334155]' }}">
                            Pending Collection ({{ $counts['pending'] }})
                        </a>
                        <a href="{{ route('admin.rewards.collection', ['status' => 'claimed']) }}"
                           class="px-3 py-1.5 rounded-xl text-xs font-bold transition border {{ $statusFilter === 'claimed' ? 'bg-emerald-600 text-white border-emerald-500 shadow-md' : 'bg-[#0B1120] text-[#94A3B8] hover:text-white border-[#334155]' }}">
                            Claimed ({{ $counts['claimed'] }})
                        </a>
                        <a href="{{ route('admin.rewards.collection', ['status' => 'refunded']) }}"
                           class="px-3 py-1.5 rounded-xl text-xs font-bold transition border {{ $statusFilter === 'refunded' ? 'bg-rose-600 text-white border-rose-500 shadow-md' : 'bg-[#0B1120] text-[#94A3B8] hover:text-white border-[#334155]' }}">
                            Refunded ({{ $counts['refunded'] }})
                        </a>
                    </div>

                    <form method="GET" action="{{ route('admin.rewards.collection') }}" class="flex items-center gap-2">
                        <input type="hidden" name="status" value="{{ $statusFilter }}">
                        <input type="text"
                               name="search"
                               value="{{ $search }}"
                               placeholder="Search code, student name..."
                               class="bg-[#0B1120] border border-[#334155] text-xs rounded-xl px-3 py-1.5 text-white placeholder-[#94A3B8] focus:outline-none focus:ring-2 focus:ring-[#8F3033]">
                        <button type="submit" class="p-1.5 rounded-xl bg-[#1E293B] hover:bg-[#334155] text-white">
                            <i data-lucide="search" class="w-4 h-4"></i>
                        </button>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-[#0B1120]/80 text-[#94A3B8] uppercase tracking-wider font-bold border-b border-[#334155]">
                            <tr>
                                <th class="py-3 px-4">Claim Code</th>
                                <th class="py-3 px-4">Member</th>
                                <th class="py-3 px-4">Item</th>
                                <th class="py-3 px-4">EXP Cost</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4">Redeemed At</th>
                                <th class="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#334155]/60">
                            @forelse($redemptions as $item)
                                <tr class="hover:bg-[#1E293B]/40 transition">
                                    <td class="py-3.5 px-4 font-mono font-bold text-amber-300">
                                        {{ $item->claim_code ?? 'NO-CODE' }}
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-white font-brand-display">{{ $item->user->name }}</div>
                                        <div class="text-[10px] text-[#94A3B8] font-mono">{{ auth()->user()?->isSuperAdmin() ? ($item->user->student_number ?? $item->user->email) : ($item->user->member_id ?? $item->user->email) }}</div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="font-semibold text-slate-200">{{ $item->item_title_snapshot }}</span>
                                    </td>
                                    <td class="py-3.5 px-4 font-mono font-bold text-[#df747a] tabular-nums">
                                        {{ number_format($item->points_spent) }} EXP
                                    </td>
                                    <td class="py-3.5 px-4">
                                        @if($item->status === 'pending_collection')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/15 text-amber-400 border border-amber-500/20">
                                                Pending Collection
                                            </span>
                                        @elseif($item->status === 'claimed')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/15 text-emerald-400 border border-emerald-500/20">
                                                Claimed
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/15 text-rose-400 border border-rose-500/20">
                                                {{ ucfirst($item->status) }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4 text-[#94A3B8] text-[11px]">
                                        {{ $item->created_at->format('M d, Y g:i A') }}
                                    </td>
                                    <td class="py-3.5 px-4 text-right">
                                        @if($item->status === 'pending_collection')
                                            <div class="flex items-center justify-end gap-1.5">
                                                <form method="POST" action="{{ route('admin.rewards.collection.verify') }}" class="inline">
                                                    @csrf
                                                    <input type="hidden" name="claim_code" value="{{ $item->claim_code }}">
                                                    <button type="submit" class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[11px] transition shadow">
                                                        Collect
                                                    </button>
                                                </form>

                                                <button type="button"
                                                        @click="$dispatch('open-modal', 'cancel-redemption-{{ $item->id }}')"
                                                        class="px-2.5 py-1 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 font-bold text-[11px] border border-rose-500/20 transition">
                                                    Refund
                                                </button>
                                            </div>

                                            <x-modal name="cancel-redemption-{{ $item->id }}" focusable>
                                                <form method="POST" action="{{ route('admin.rewards.collection.cancel', $item) }}" class="p-6 bg-[#172033] text-[#F8FAFC] rounded-2xl space-y-4 border border-[#334155]">
                                                    @csrf
                                                    <h3 class="text-base font-bold text-white font-brand-display">Refund & Cancel Redemption</h3>
                                                    <p class="text-xs text-[#94A3B8]">
                                                        This will atomically restore {{ number_format($item->points_spent) }} EXP to {{ $item->user->name }} and restore 1 item to inventory.
                                                    </p>
                                                    <div>
                                                        <label class="block text-xs font-semibold text-slate-300 mb-1">Reason for Cancellation</label>
                                                        <input type="text" name="reason" placeholder="Enter reason for cancellation" required class="w-full bg-[#0B1120] border border-[#334155] rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-[#8F3033]">
                                                    </div>
                                                    <div class="flex items-center justify-end gap-2 pt-2">
                                                        <button type="button" @click="$dispatch('close')" class="btn-secondary btn-sm">Cancel</button>
                                                        <button type="submit" class="btn-danger btn-sm font-bold">Confirm Refund</button>
                                                    </div>
                                                </form>
                                            </x-modal>
                                        @else
                                            <span class="text-[10px] text-[#94A3B8]">
                                                {{ $item->collector ? 'by ' . $item->collector->name : 'Completed' }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-[#94A3B8] text-xs">
                                        No redemptions found in this category.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="pt-3">
                    {{ $redemptions->links() }}
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
