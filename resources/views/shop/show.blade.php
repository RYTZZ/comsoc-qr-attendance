<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('shop.index') }}" class="btn-secondary p-2 rounded-xl text-slate-400 hover:text-white">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-500/20 border border-amber-500/40 text-amber-300">
                        {{ $reward->category }}
                    </span>
                    <span class="text-xs text-slate-400">Reward Catalog</span>
                </div>
                <h1 class="text-2xl font-bold text-white font-brand-display tracking-tight mt-0.5">
                    {{ $reward->title }}
                </h1>
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-[#0B1120]">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

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

            <div class="grid grid-cols-1 md:grid-cols-12 gap-6">

                <div class="md:col-span-5 space-y-6">
                    <div class="card bg-[#172033] border border-[#334155] rounded-2xl overflow-hidden p-3 shadow-xl">
                        <div class="h-64 sm:h-72 bg-[#0B1120] rounded-xl overflow-hidden flex items-center justify-center relative">
                            @if($reward->effective_image_url)
                                <img src="{{ $reward->effective_image_url }}" alt="{{ $reward->title }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-20 h-20 rounded-2xl bg-[#560E0F]/20 border border-[#8F3033]/40 flex items-center justify-center text-[#df747a]">
                                    <i data-lucide="gift" class="w-10 h-10"></i>
                                </div>
                            @endif

                            <div class="absolute top-3 right-3">
                                @if($reward->stock !== null)
                                    @if($reward->stock > 0)
                                        <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-500/20 border border-emerald-500/40 text-emerald-300 backdrop-blur-sm">
                                            {{ $reward->stock }} items left
                                        </span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-rose-500/20 border border-rose-500/40 text-rose-300 backdrop-blur-sm">
                                            Out of Stock
                                        </span>
                                    @endif
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-cyan-500/20 border border-cyan-500/40 text-cyan-300 backdrop-blur-sm">
                                        In Stock
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="card bg-[#172033] border border-[#334155] p-5 rounded-2xl space-y-3 shadow-xl">
                        <div class="text-xs uppercase font-bold text-[#94A3B8] tracking-wider">Your Balance Status</div>
                        <div class="flex items-center justify-between p-3 rounded-xl bg-[#0B1120] border border-[#334155]">
                            <span class="text-xs text-[#94A3B8]">Spendable EXP</span>
                            <span class="text-sm font-extrabold text-amber-400 flex items-center gap-1 font-mono">
                                <i data-lucide="coins" class="w-4 h-4"></i>
                                {{ number_format($currentPoints) }} EXP
                            </span>
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-xl bg-[#0B1120] border border-[#334155]">
                            <span class="text-xs text-[#94A3B8]">Your Current Level</span>
                            <span class="text-sm font-extrabold text-white font-brand-display">Level {{ $currentLevel }}</span>
                        </div>
                    </div>
                </div>

                <div class="md:col-span-7 space-y-6">
                    <div class="card bg-[#172033] border border-[#334155] p-6 rounded-2xl space-y-5 shadow-xl">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h2 class="text-xl font-bold text-white font-brand-display">{{ $reward->title }}</h2>
                                <span class="text-xs text-[#94A3B8] mt-0.5 block">Category: {{ $reward->category }}</span>
                            </div>
                            <div class="text-right">
                                <div class="text-[10px] uppercase font-bold text-[#94A3B8] tracking-wider">Cost</div>
                                <div class="text-xl font-black text-amber-400 flex items-center gap-1.5 justify-end font-mono">
                                    <i data-lucide="coins" class="w-5 h-5"></i>
                                    <span>{{ number_format($reward->points_cost) }} EXP</span>
                                    <span class="text-xs font-normal text-slate-400">({{ number_format($reward->points_cost) }} pts)</span>
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-slate-800 pt-4">
                            <h3 class="text-xs uppercase font-bold text-slate-400 tracking-wider mb-2">Item Description</h3>
                            <p class="text-sm text-slate-300 leading-relaxed">
                                {{ $reward->description ?? 'No specific description provided for this reward.' }}
                            </p>
                        </div>

                        @if($reward->eligibility_notes || $reward->min_level > 1)
                            <div class="border-t border-slate-800 pt-4">
                                <h3 class="text-xs uppercase font-bold text-slate-400 tracking-wider mb-2">Eligibility Criteria</h3>
                                <div class="space-y-2">
                                    <div class="flex items-center gap-2 text-xs">
                                        <i data-lucide="{{ $isEligibleLevel ? 'check-circle' : 'x-circle' }}"
                                           class="w-4 h-4 {{ $isEligibleLevel ? 'text-emerald-400' : 'text-rose-400' }}"></i>
                                        <span class="{{ $isEligibleLevel ? 'text-slate-300' : 'text-rose-300 font-semibold' }}">
                                            Minimum Level {{ $reward->min_level }} required
                                        </span>
                                    </div>
                                    @if($reward->eligibility_notes)
                                        <div class="p-3 rounded-xl bg-slate-900/50 border border-slate-800 text-xs text-slate-300">
                                            {{ $reward->eligibility_notes }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        @if($reward->fulfillment_instructions)
                            <div class="border-t border-slate-800 pt-4">
                                <h3 class="text-xs uppercase font-bold text-slate-400 tracking-wider mb-2">Fulfillment Instructions</h3>
                                <div class="p-3 rounded-xl bg-amber-500/10 border border-amber-500/20 text-xs text-amber-300/90 leading-relaxed flex items-start gap-2.5">
                                    <i data-lucide="info" class="w-4 h-4 flex-shrink-0 mt-0.5 text-amber-400"></i>
                                    <span>{{ $reward->fulfillment_instructions }}</span>
                                </div>
                            </div>
                        @endif

                        <div class="border-t border-slate-800 pt-6">
                            @if(!$hasStock)
                                <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs text-center font-medium">
                                    This reward is currently out of stock. Please check back later.
                                </div>
                            @elseif(!$isEligibleLevel)
                                <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs text-center font-medium">
                                    You must reach Level {{ $reward->min_level }} to redeem this reward. (Current: Level {{ $currentLevel }})
                                </div>
                            @elseif(!$hasEnoughPoints)
                                <div class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-300 text-xs text-center font-medium">
                                    You need {{ number_format($reward->points_cost - ($spendableExp ?? $currentPoints)) }} more Spendable EXP to redeem this item. Attend events to earn more!
                                </div>
                            @else
                                <div x-data="{ confirming: false }">
                                    <div x-show="!confirming">
                                        <button @click="confirming = true" class="w-full btn-primary py-3 text-sm flex items-center justify-center gap-2">
                                            <i data-lucide="gift" class="w-4 h-4"></i>
                                            <span>Redeem for {{ number_format($reward->points_cost) }} EXP</span>
                                        </button>
                                    </div>

                                    <div x-show="confirming" class="p-4 rounded-xl bg-slate-900 border border-slate-800 space-y-4" style="display: none;">
                                        <div>
                                            <h4 class="text-sm font-bold text-white">Confirm Reward Redemption</h4>
                                            <p class="text-xs text-slate-400 mt-1">
                                                Are you sure you want to spend {{ number_format($reward->points_cost) }} Spendable EXP on <strong>{{ $reward->title }}</strong>? Credits are consumed using FEFO (First Expiring, First Out). A unique single-use Claim Code will be issued for collection.
                                            </p>
                                        </div>

                                        <form method="POST" action="{{ route('shop.redeem', $reward) }}" class="space-y-3">
                                            @csrf
                                            <div>
                                                <label class="block text-xs font-medium text-slate-300 mb-1">
                                                    Special Notes or Size (Optional)
                                                </label>
                                                <input type="text" name="notes" placeholder="e.g. Size M, Preferred pickup time, or color"
                                                       class="input-dark text-xs w-full">
                                            </div>

                                            <div class="flex items-center gap-2 pt-1">
                                                <button type="submit" class="flex-1 btn-primary text-xs py-2">
                                                    Confirm & Deduct Points
                                                </button>
                                                <button type="button" @click="confirming = false" class="btn-secondary text-xs py-2 px-3">
                                                    Cancel
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
</x-app-layout>
