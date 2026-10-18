<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-500/20 border border-amber-500/40 text-amber-300">
                        Season Rewards
                    </span>
                    <span class="text-xs text-slate-400">Claim Member Merch & Perks</span>
                </div>
                <h1 class="text-2xl font-bold text-white font-brand-display tracking-tight mt-1">
                    Reward Shop & Redemptions
                </h1>
                <p class="text-xs text-slate-400 mt-0.5">
                    Exchange your earned activity points for exclusive Computing Society kits, merchandise, and perks.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-3 px-3.5 py-2 rounded-xl bg-[#172033] border border-[#334155] shadow-sm">
                    <div class="w-8 h-8 rounded-lg bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400">
                        <i data-lucide="coins" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <div class="text-[10px] uppercase font-bold text-[#94A3B8] tracking-wider">Spendable EXP</div>
                        <div class="text-base font-extrabold text-amber-400 font-mono">{{ number_format($spendableExp ?? $currentPoints) }} EXP</div>
                    </div>
                </div>

                <div class="flex items-center gap-3 px-3.5 py-2 rounded-xl bg-[#172033] border border-[#334155] shadow-sm">
                    <div class="w-8 h-8 rounded-lg bg-[#560E0F]/20 border border-[#8F3033]/40 flex items-center justify-center text-[#df747a]">
                        <i data-lucide="shield" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <div class="text-[10px] uppercase font-bold text-[#94A3B8] tracking-wider">Member Level</div>
                        <div class="text-base font-extrabold text-white font-brand-display">Level {{ $currentLevel }}</div>
                    </div>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-[#0B1120]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm flex items-center gap-3">
                    <i data-lucide="check-circle" class="w-5 h-5 flex-shrink-0"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-sm flex items-center gap-3">
                    <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <div class="card bg-[#172033] border border-[#334155] p-4">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('shop.index', ['tab' => 'shop', 'category' => $selectedCategory, 'search' => $search]) }}"
                           class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-colors {{ $statusTab === 'shop' ? 'bg-[#560E0F] text-white border border-[#8F3033] shadow-sm' : 'text-[#94A3B8] hover:text-white hover:bg-[#1E293B]' }}">
                            Catalog Shop
                        </a>
                        <a href="{{ route('shop.index', ['tab' => 'history']) }}"
                           class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-colors {{ $statusTab === 'history' ? 'bg-[#560E0F] text-white border border-[#8F3033] shadow-sm' : 'text-[#94A3B8] hover:text-white hover:bg-[#1E293B]' }}">
                            My Redemptions ({{ $myRedemptions->total() }})
                        </a>
                    </div>

                    @if($statusTab === 'shop')
                        <form method="GET" action="{{ route('shop.index') }}" class="flex flex-wrap items-center gap-2">
                            <input type="hidden" name="tab" value="shop">

                            <div class="relative w-full sm:w-56">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
                                    <i data-lucide="search" class="w-3.5 h-3.5"></i>
                                </span>
                                <input type="text" name="search" value="{{ $search }}"
                                       class="input-dark pl-9 pr-3 py-1.5 text-xs w-full">
                            </div>

                            <div class="w-44">
                                @php
                                    $catOptions = [['value' => 'all', 'label' => 'All Categories']];
                                    foreach($categories as $cat) {
                                        $catOptions[] = ['value' => $cat, 'label' => $cat];
                                    }
                                @endphp
                                <x-custom-dropdown
                                    name="category"
                                    :options="$catOptions"
                                    :selected="$selectedCategory"
                                    placeholder="Filter Category"
                                    onchange="this.closest('form').submit()"
                                />
                            </div>

                            <button type="submit" class="btn-primary text-xs py-1.5 px-3">
                                Filter
                            </button>

                            @if($selectedCategory !== 'all' || $search)
                                <a href="{{ route('shop.index', ['tab' => 'shop']) }}" class="btn-secondary text-xs py-1.5 px-2.5">
                                    Reset
                                </a>
                            @endif
                        </form>
                    @endif
                </div>
            </div>

            @if($statusTab === 'shop')
                @if($rewards->isEmpty())
                    <div class="card bg-[#172033] border border-[#334155] p-12 text-center">
                        <div class="w-14 h-14 mx-auto rounded-full bg-[#0B1120] border border-[#334155] flex items-center justify-center text-[#94A3B8] mb-3">
                            <i data-lucide="package-search" class="w-7 h-7"></i>
                        </div>
                        <h3 class="text-base font-bold text-white font-brand-display">No Available Rewards Found</h3>
                        <p class="text-xs text-[#94A3B8] mt-1 max-w-sm mx-auto">
                            No reward items match your current category or search criteria. Please check back later as officers add more merchandise!
                        </p>
                    </div>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        @foreach($rewards as $reward)
                            @php
                                $canAfford = $currentPoints >= $reward->points_cost;
                                $canLevel = $currentLevel >= $reward->min_level;
                                $inStock = $reward->stock === null || $reward->stock > 0;
                            @endphp
                            <div class="card bg-[#172033] border border-[#334155] rounded-2xl overflow-hidden hover:border-[#8F3033]/60 transition-all duration-200 flex flex-col group shadow-xl">
                                <div class="relative h-44 bg-[#0B1120] overflow-hidden flex items-center justify-center">
                                    @if($reward->effective_image_url)
                                        <img src="{{ $reward->effective_image_url }}" alt="{{ $reward->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                    @else
                                        <div class="w-16 h-16 rounded-2xl bg-[#560E0F]/20 border border-[#8F3033]/40 flex items-center justify-center text-[#df747a]">
                                            <i data-lucide="gift" class="w-8 h-8"></i>
                                        </div>
                                    @endif

                                    <div class="absolute top-3 left-3 flex flex-col gap-1.5">
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#172033]/90 border border-[#334155] text-slate-200 backdrop-blur-sm">
                                            {{ $reward->category }}
                                        </span>
                                    </div>

                                    <div class="absolute top-3 right-3">
                                        @if($reward->stock !== null)
                                            @if($reward->stock > 0)
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 border border-emerald-500/40 text-emerald-300 backdrop-blur-sm">
                                                    {{ $reward->stock }} left
                                                </span>
                                            @else
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/20 border border-rose-500/40 text-rose-300 backdrop-blur-sm">
                                                    Out of Stock
                                                </span>
                                            @endif
                                        @else
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-cyan-500/20 border border-cyan-500/40 text-cyan-300 backdrop-blur-sm">
                                                Available
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
                                    <div>
                                        <div class="flex items-center justify-between gap-2 mb-1.5">
                                            <h3 class="text-base font-bold text-white group-hover:text-[#df747a] transition-colors line-clamp-1 font-brand-display">
                                                {{ $reward->title }}
                                            </h3>
                                        </div>

                                        <p class="text-xs text-[#94A3B8] line-clamp-2 leading-relaxed">
                                            {{ $reward->description ?? 'Official COMSOC reward item available for members.' }}
                                        </p>

                                        <div class="mt-3 flex items-center gap-2">
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold {{ $canLevel ? 'bg-[#0B1120] text-slate-300 border border-[#334155]' : 'bg-rose-500/10 text-rose-300 border border-rose-500/20' }}">
                                                Level {{ $reward->min_level }}+ Required
                                            </span>
                                        </div>
                                    </div>

                                    <div class="pt-3 border-t border-[#334155] flex items-center justify-between gap-3">
                                        <div>
                                            <span class="text-[10px] uppercase font-bold text-[#94A3B8] tracking-wider block">Cost</span>
                                            <div class="flex items-center gap-1 font-extrabold text-sm text-amber-400 font-mono">
                                                <i data-lucide="coins" class="w-3.5 h-3.5"></i>
                                                <span>{{ number_format($reward->points_cost) }} EXP</span>
                                                <span class="sr-only">{{ number_format($reward->points_cost) }} pts</span>
                                            </div>
                                        </div>

                                        <a href="{{ route('shop.show', $reward) }}" class="btn-primary text-xs py-1.5 px-3.5 flex items-center gap-1.5">
                                            <span>View Details</span>
                                            <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            @else
                <div class="card bg-[#172033] border border-[#334155] overflow-hidden shadow-xl">
                    <div class="p-4 border-b border-[#334155] flex items-center justify-between">
                        <div>
                            <h2 class="text-sm font-bold text-white font-brand-display">Redemption Orders & History</h2>
                            <p class="text-[11px] text-[#94A3B8]">Track the status of your reward claims and fulfillment notes.</p>
                        </div>
                    </div>

                    @if($myRedemptions->isEmpty())
                        <div class="p-12 text-center">
                            <div class="w-12 h-12 mx-auto rounded-full bg-[#0B1120] border border-[#334155] flex items-center justify-center text-[#94A3B8] mb-3">
                                <i data-lucide="clock" class="w-6 h-6"></i>
                            </div>
                            <h3 class="text-sm font-bold text-white font-brand-display">No Redemption History Yet</h3>
                            <p class="text-xs text-[#94A3B8] mt-1 max-w-sm mx-auto">
                                You haven't redeemed any rewards yet. Browse the catalog to claim items with your points!
                            </p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="border-b border-[#334155] text-[10px] uppercase tracking-wider text-[#94A3B8] bg-[#0B1120]/80">
                                        <th class="py-3 px-4">Claim Code</th>
                                        <th class="py-3 px-4">Item Snapshot</th>
                                        <th class="py-3 px-4">EXP Spent</th>
                                        <th class="py-3 px-4">Status</th>
                                        <th class="py-3 px-4">Requested At</th>
                                        <th class="py-3 px-4">Notes / Feedback</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#334155]/60">
                                    @foreach($myRedemptions as $redemption)
                                        <tr class="hover:bg-[#1E293B]/40 transition-colors">
                                            <td class="py-3.5 px-4 font-mono font-bold text-amber-300">
                                                {{ $redemption->claim_code ?? 'CMS-PENDING' }}
                                            </td>
                                            <td class="py-3.5 px-4 font-semibold text-white">
                                                <div class="flex items-center gap-2">
                                                    <i data-lucide="gift" class="w-4 h-4 text-amber-400"></i>
                                                    <span class="font-brand-display">{{ $redemption->item_title_snapshot }}</span>
                                                </div>
                                            </td>
                                            <td class="py-3.5 px-4 font-bold text-[#df747a] font-mono tabular-nums">
                                                {{ number_format($redemption->points_spent) }} EXP
                                            </td>
                                            <td class="py-3.5 px-4">
                                                @if($redemption->status === 'fulfilled')
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                                        Fulfilled
                                                    </span>
                                                @elseif($redemption->status === 'rejected')
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30">
                                                        Refunded / Rejected
                                                    </span>
                                                @else
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                                        Pending Review
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="py-3.5 px-4 text-[#94A3B8] font-mono">
                                                {{ $redemption->created_at->format('M d, Y h:i A') }}
                                            </td>
                                            <td class="py-3.5 px-4 text-slate-300 max-w-xs truncate">
                                                @if($redemption->status === 'rejected' && $redemption->rejection_reason)
                                                    <span class="text-rose-400 font-medium">{{ $redemption->rejection_reason }}</span>
                                                @elseif($redemption->notes)
                                                    <span>{{ $redemption->notes }}</span>
                                                @else
                                                    <span class="text-slate-500">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="p-4 border-t border-[#334155]">
                            {{ $myRedemptions->links() }}
                        </div>
                    @endif
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
