<x-layouts.admin :title="'Reward Management & Publishing Catalog'">
<div class="space-y-6">
    <div class="page-header flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-[#560E0F]/30 border border-[#560E0F]/60 text-[#dfa6a9]">
                    Reward Operations & Publishing
                </span>
                <span class="text-xs text-slate-400">Merchandise, Bundles & Vouchers</span>
            </div>
            <h1 class="page-title text-2xl font-bold text-white font-brand-display tracking-tight mt-1">
                Reward Management & Publishing Catalog
            </h1>
            <p class="text-xs text-slate-400 mt-0.5">
                Create, manage, and publish society merchandise, tote bags, caps, pins, mouse pads, kits, and discount vouchers for members.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.economy.index') }}" class="btn-secondary text-xs flex items-center gap-1.5">
                <i data-lucide="coins" class="w-4 h-4 text-indigo-400"></i>
                <span>Reward Economy</span>
            </a>
            @if(auth()->user()->isSuperAdmin())
            <a href="{{ route('admin.students.exp-points.index') }}" class="btn-secondary text-xs flex items-center gap-1.5">
                <i data-lucide="award" class="w-4 h-4 text-amber-400"></i>
                <span>Manage EXP & Points</span>
            </a>
            @endif
            <a href="{{ route('admin.rewards.collection') }}" class="btn-secondary text-xs flex items-center gap-1.5">
                <i data-lucide="qr-code" class="w-4 h-4 text-emerald-400"></i>
                <span>Collection Desk</span>
            </a>
        </div>
    </div>

    <!-- Reward Economy Dashboard Overview Tray -->
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-3">
        <div class="card p-4 bg-[#172033] border border-slate-800">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Total Rewards</span>
            <p class="text-xl font-bold font-mono text-white">{{ number_format($summary['total_rewards']) }}</p>
        </div>
        <div class="card p-4 bg-[#172033] border border-slate-800">
            <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-400 block mb-1">Published</span>
            <p class="text-xl font-bold font-mono text-emerald-400">{{ number_format($summary['published_rewards']) }}</p>
        </div>
        <div class="card p-4 bg-[#172033] border border-slate-800">
            <span class="text-[10px] font-bold uppercase tracking-wider text-amber-400 block mb-1">Drafts</span>
            <p class="text-xl font-bold font-mono text-amber-400">{{ number_format($summary['draft_rewards']) }}</p>
        </div>
        <div class="card p-4 bg-[#172033] border border-slate-800">
            <span class="text-[10px] font-bold uppercase tracking-wider text-rose-400 block mb-1">Out of Stock</span>
            <p class="text-xl font-bold font-mono text-rose-400">{{ number_format($summary['out_of_stock']) }}</p>
        </div>
        <div class="card p-4 bg-[#172033] border border-slate-800">
            <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-400 block mb-1">Total Redemptions</span>
            <p class="text-xl font-bold font-mono text-indigo-300">{{ number_format($summary['total_redemptions']) }}</p>
        </div>
        <div class="card p-4 bg-[#172033] border border-slate-800">
            <span class="text-[10px] font-bold uppercase tracking-wider text-cyan-400 block mb-1">Pending Claims</span>
            <p class="text-xl font-bold font-mono text-cyan-300">{{ number_format($summary['pending_claims']) }}</p>
        </div>
    </div>

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

            <div class="card bg-[#172033] border border-slate-800 p-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.shop.index', ['tab' => 'requests', 'status' => $statusFilter]) }}"
                           class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-colors flex items-center gap-2 {{ $tab === 'requests' ? 'bg-[#560E0F] text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                            <i data-lucide="inbox" class="w-3.5 h-3.5"></i>
                            <span>Redemption Requests</span>
                            @if($pendingCount > 0)
                                <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold bg-amber-400 text-slate-900">
                                    {{ $pendingCount }}
                                </span>
                            @endif
                        </a>

                        <a href="{{ route('admin.shop.index', ['tab' => 'catalog']) }}"
                           class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-colors flex items-center gap-2 {{ $tab === 'catalog' ? 'bg-[#560E0F] text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                            <i data-lucide="gift" class="w-3.5 h-3.5"></i>
                            <span>Reward Catalog ({{ $rewards->total() }})</span>
                        </a>
                    </div>

                    @if($tab === 'requests')
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-slate-400">Status:</span>
                            <div class="w-36">
                                <x-custom-dropdown
                                    name="status_filter"
                                    :options="[
                                        ['value' => 'all', 'label' => 'All Statuses'],
                                        ['value' => 'pending', 'label' => 'Pending'],
                                        ['value' => 'fulfilled', 'label' => 'Fulfilled'],
                                        ['value' => 'rejected', 'label' => 'Rejected'],
                                    ]"
                                    :selected="$statusFilter"
                                    placeholder="Filter Status"
                                    onchange="window.location.href='{{ route('admin.shop.index', ['tab' => 'requests']) }}&status=' + this.value"
                                />
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            @if($tab === 'requests')
                <div class="card bg-[#172033] border border-slate-800 overflow-hidden">
                    <div class="p-4 border-b border-slate-800 flex items-center justify-between">
                        <div>
                            <h2 class="text-sm font-bold text-white font-brand-display">Member Redemption Queue</h2>
                            <p class="text-[11px] text-slate-400">Review pending orders, confirm fulfillment, or reject and refund points.</p>
                        </div>
                    </div>

                    @if($redemptions->isEmpty())
                        <div class="p-12 text-center">
                            <div class="w-12 h-12 mx-auto rounded-full bg-slate-800/80 border border-slate-700 flex items-center justify-center text-slate-500 mb-3">
                                <i data-lucide="check-check" class="w-6 h-6"></i>
                            </div>
                            <h3 class="text-sm font-bold text-white">No Redemptions Found</h3>
                            <p class="text-xs text-slate-400 mt-1">There are no redemptions matching this criteria.</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="border-b border-slate-800 text-[10px] uppercase tracking-wider text-slate-400 bg-slate-900/40">
                                        <th class="py-3 px-4">Member</th>
                                        <th class="py-3 px-4">Reward Item</th>
                                        <th class="py-3 px-4">Points</th>
                                        <th class="py-3 px-4">Order Notes</th>
                                        <th class="py-3 px-4">Date</th>
                                        <th class="py-3 px-4">Status</th>
                                        <th class="py-3 px-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800/60" x-data="{ rejectModal: false, activeId: null, activeTitle: '' }">
                                    @foreach($redemptions as $item)
                                        <tr class="hover:bg-slate-900/30 transition-colors">
                                            <td class="py-3.5 px-4 font-semibold text-white">
                                                <div>{{ $item->user->name }}</div>
                                                <div class="text-[10px] text-slate-400 font-mono">
                                                    {{ $item->user->student?->student_id ?? $item->user->email }}
                                                </div>
                                            </td>
                                            <td class="py-3.5 px-4 text-white font-medium">
                                                <div class="flex items-center gap-1.5">
                                                    <i data-lucide="gift" class="w-3.5 h-3.5 text-amber-400"></i>
                                                    <span>{{ $item->item_title_snapshot }}</span>
                                                </div>
                                            </td>
                                            <td class="py-3.5 px-4 font-bold text-amber-400">
                                                {{ number_format($item->points_spent) }} pts
                                            </td>
                                            <td class="py-3.5 px-4 text-slate-300 max-w-xs truncate">
                                                {{ $item->notes ?? '—' }}
                                            </td>
                                            <td class="py-3.5 px-4 text-slate-400 whitespace-nowrap">
                                                {{ $item->created_at->format('M d, Y h:i A') }}
                                            </td>
                                            <td class="py-3.5 px-4">
                                                @if($item->status === 'fulfilled')
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                                        Fulfilled
                                                    </span>
                                                @elseif($item->status === 'rejected')
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30">
                                                        Rejected & Refunded
                                                    </span>
                                                @else
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                                        Pending
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="py-3.5 px-4 text-right">
                                                @if($item->status === 'pending')
                                                    <div class="flex items-center justify-end gap-2">
                                                        <form method="POST" action="{{ route('admin.shop.fulfill', $item) }}" class="inline">
                                                            @csrf
                                                            <button type="submit" class="btn-primary text-[11px] py-1 px-2.5 flex items-center gap-1">
                                                                <i data-lucide="check" class="w-3 h-3"></i>
                                                                <span>Fulfill</span>
                                                            </button>
                                                        </form>

                                                        <button type="button"
                                                                @click="rejectModal = true; activeId = '{{ $item->id }}'; activeTitle = '{{ addslashes($item->item_title_snapshot) }}'"
                                                                class="btn-secondary text-rose-400 hover:text-rose-300 text-[11px] py-1 px-2.5 flex items-center gap-1 border-rose-500/30">
                                                            <i data-lucide="x" class="w-3 h-3"></i>
                                                            <span>Reject</span>
                                                        </button>
                                                    </div>
                                                @else
                                                    <span class="text-[11px] text-slate-500">Processed</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach

                                    <template x-teleport="body">
                                        <div x-show="rejectModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm" style="display: none;">
                                            <div class="bg-[#172033] border border-slate-800 rounded-2xl max-w-md w-full p-6 space-y-4 shadow-xl" @click.outside="rejectModal = false">
                                                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                                                    <div class="flex items-center gap-2 text-rose-400 font-bold text-sm">
                                                        <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                                                        <span>Reject & Refund Redemption</span>
                                                    </div>
                                                    <button @click="rejectModal = false" class="text-slate-400 hover:text-white">
                                                        <i data-lucide="x" class="w-4 h-4"></i>
                                                    </button>
                                                </div>

                                                <p class="text-xs text-slate-300 leading-relaxed">
                                                    Rejecting this redemption for <strong x-text="activeTitle"></strong> will automatically refund the deducted points back to the member's season balance.
                                                </p>

                                                <form :action="'/admin/shop/redemptions/' + activeId + '/reject'" method="POST" class="space-y-4">
                                                    @csrf
                                                    <div>
                                                        <label class="block text-xs font-medium text-slate-300 mb-1">
                                                            Rejection Reason (Visible to Member) <span class="text-rose-400">*</span>
                                                        </label>
                                                        <textarea name="rejection_reason" rows="3" required
                                                                  placeholder="Enter rejection reason for member"
                                                                  class="input-dark text-xs w-full"></textarea>
                                                    </div>

                                                    <div class="flex items-center justify-end gap-2 pt-2">
                                                        <button type="button" @click="rejectModal = false" class="btn-secondary text-xs py-1.5 px-3">
                                                            Cancel
                                                        </button>
                                                        <button type="submit" class="btn-primary bg-rose-600 hover:bg-rose-500 text-xs py-1.5 px-3.5">
                                                            Confirm Reject & Refund
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <div class="p-4 border-t border-slate-800">
                            {{ $redemptions->links() }}
                        </div>
                    @endif
                </div>

            @else

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                    <div class="lg:col-span-5 space-y-6">
                        <div class="card bg-[#172033] border border-slate-800 p-6 rounded-2xl space-y-5">
                            <div class="flex items-center gap-2 pb-3 border-b border-slate-800">
                                <i data-lucide="plus-circle" class="w-4 h-4 text-amber-400"></i>
                                <h2 class="text-base font-bold text-white font-brand-display">Create Reward Item</h2>
                            </div>

                            <form method="POST" action="{{ route('admin.shop.store') }}" enctype="multipart/form-data" class="space-y-4">
                                @csrf

                                <div>
                                    <label class="block text-xs font-semibold text-slate-300 mb-1">
                                        Item Title <span class="text-rose-400">*</span>
                                    </label>
                                    <input type="text" name="title" required
                                           class="input-dark text-xs w-full">
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-300 mb-1">
                                        Associated Event (Optional)
                                    </label>
                                    <select name="event_id" class="input-dark text-xs w-full">
                                        <option value="">-- General Reward (No specific event) --</option>
                                        @foreach($events as $ev)
                                            <option value="{{ $ev->id }}">{{ $ev->name }}</option>
                                        @endforeach
                                    </select>
                                    <p class="text-[11px] text-slate-500 mt-1">Configure exclusive rewards for events like <em>From Idea to App: Building a Real-World Product</em></p>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-300 mb-1">
                                            Reward Type <span class="text-rose-400">*</span>
                                        </label>
                                        <select name="reward_type" required class="input-dark text-xs w-full">
                                            @foreach($types as $typeKey => $typeLabel)
                                                <option value="{{ $typeKey }}">{{ $typeLabel }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-slate-300 mb-1">
                                            Category <span class="text-rose-400">*</span>
                                        </label>
                                        <select name="category" required class="input-dark text-xs w-full">
                                            @foreach($categories as $cat)
                                                <option value="{{ $cat }}">{{ $cat }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-300 mb-1">
                                            Points Cost (Spendable EXP) <span class="text-rose-400">*</span>
                                        </label>
                                        <input type="number" name="points_cost" min="0" value="50" required
                                               class="input-dark text-xs w-full font-mono">
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-slate-300 mb-1">
                                            Min Level Req <span class="text-rose-400">*</span>
                                        </label>
                                        <input type="number" name="min_level" min="1" max="100" value="1" required
                                               class="input-dark text-xs w-full font-mono">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-300 mb-1">
                                            Inventory Stock (Physical / Merch / Kit)
                                        </label>
                                        <input type="number" name="stock" min="0" placeholder="Enter stock quantity (leave blank for unlimited)"
                                               class="input-dark text-xs w-full font-mono">
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-slate-300 mb-1">
                                            Per-Member Limit (Optional)
                                        </label>
                                        <input type="number" name="redemption_limit_per_member" min="1" placeholder="Enter redemption limit per student"
                                               class="input-dark text-xs w-full font-mono">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-300 mb-1">
                                            Publication Start (Optional)
                                        </label>
                                        <input type="datetime-local" name="starts_at"
                                               class="input-dark text-xs w-full">
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-slate-300 mb-1">
                                            Publication End / Expiry (Optional)
                                        </label>
                                        <input type="datetime-local" name="ends_at"
                                               class="input-dark text-xs w-full">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-300 mb-1">
                                        Description
                                    </label>
                                    <textarea name="description" rows="3" placeholder="Enter item description and inclusions"
                                              class="input-dark text-xs w-full"></textarea>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-300 mb-1">
                                        Eligibility Instructions / Restrictions
                                    </label>
                                    <input type="text" name="eligibility_notes" placeholder="Enter eligibility instructions or restrictions"
                                           class="input-dark text-xs w-full">
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-300 mb-1">
                                        Fulfillment / Pickup Instructions
                                    </label>
                                    <textarea name="fulfillment_instructions" rows="2"
                                              placeholder="Enter fulfillment and pickup instructions"
                                              class="input-dark text-xs w-full"></textarea>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-300 mb-1">
                                            Image Upload (File)
                                        </label>
                                        <input type="file" name="image_file" accept="image/*"
                                               class="input-dark text-xs w-full file:mr-2 file:py-1 file:px-2 file:rounded-md file:border-0 file:text-[10px] file:bg-slate-800 file:text-white">
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-slate-300 mb-1">
                                            Or External Image URL
                                        </label>
                                        <input type="url" name="image_url" placeholder="https://..."
                                               class="input-dark text-xs w-full">
                                    </div>
                                </div>

                                <div class="pt-3 flex items-center gap-2">
                                    <button type="submit" name="action" value="draft" class="btn-secondary text-xs py-2 px-3 w-1/2 flex items-center justify-center gap-1.5">
                                        <i data-lucide="file-edit" class="w-3.5 h-3.5 text-amber-400"></i>
                                        <span>Save as Draft</span>
                                    </button>
                                    <button type="submit" name="action" value="publish" class="btn-primary text-xs py-2 px-3 w-1/2 flex items-center justify-center gap-1.5">
                                        <i data-lucide="upload-cloud" class="w-3.5 h-3.5"></i>
                                        <span>Publish Reward</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="lg:col-span-7 space-y-4">
                        <div class="card bg-[#172033] border border-slate-800 p-6 rounded-2xl">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-800">
                                <div>
                                    <h2 class="text-base font-bold text-white font-brand-display">Rewards Catalog Items</h2>
                                    <p class="text-[11px] text-slate-400">Manage drafts, publish merchandise/kits, adjust inventory, view reservation breakdown, and configure event links.</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <form method="GET" action="{{ route('admin.shop.index') }}" class="flex items-center gap-2">
                                        <input type="hidden" name="tab" value="catalog">
                                        <select name="catalog_status" onchange="this.form.submit()" class="input-dark text-xs py-1 px-2.5">
                                            <option value="">All Statuses</option>
                                            <option value="draft" {{ request('catalog_status') === 'draft' ? 'selected' : '' }}>Drafts</option>
                                            <option value="published" {{ request('catalog_status') === 'published' ? 'selected' : '' }}>Published</option>
                                            <option value="archived" {{ request('catalog_status') === 'archived' ? 'selected' : '' }}>Archived</option>
                                        </select>
                                        <select name="event_id" onchange="this.form.submit()" class="input-dark text-xs py-1 px-2.5">
                                            <option value="">All Events</option>
                                            @foreach($events as $ev)
                                                <option value="{{ $ev->id }}" {{ $eventFilter == $ev->id ? 'selected' : '' }}>
                                                    {{ $ev->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </form>
                                </div>
                            </div>

                            @if($rewards->isEmpty())
                                <div class="p-8 text-center">
                                    <p class="text-xs text-slate-400">No reward items found matching your filters.</p>
                                </div>
                            @else
                                <div class="divide-y divide-slate-800/80 mt-4" x-data="{ editModal: false, activeReward: null }">
                                    @foreach($rewards as $reward)
                                        <div class="py-4 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-14 h-14 rounded-xl bg-slate-900 overflow-hidden flex-shrink-0 flex items-center justify-center border border-slate-800">
                                                    @if($reward->effective_image_url)
                                                        <img src="{{ $reward->effective_image_url }}" alt="{{ $reward->title }}" class="w-full h-full object-cover">
                                                    @else
                                                        <i data-lucide="gift" class="w-6 h-6 text-[#dfa6a9]"></i>
                                                    @endif
                                                </div>

                                                <div>
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <span class="text-sm font-bold text-white">{{ $reward->title }}</span>
                                                        
                                                        @if($reward->status === 'draft')
                                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                                                Draft
                                                            </span>
                                                        @elseif($reward->status === 'archived' || $reward->is_archived)
                                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-400 border border-slate-700">
                                                                Archived
                                                            </span>
                                                        @else
                                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                                                Published
                                                            </span>
                                                        @endif

                                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-purple-500/20 text-purple-300 border border-purple-500/30">
                                                            {{ ucfirst($reward->reward_type ?? 'physical') }}
                                                        </span>

                                                        @if($reward->event)
                                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                                                                {{ $reward->event->name }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <div class="text-[11px] text-slate-400 flex flex-wrap items-center gap-3 mt-1.5">
                                                        <span>{{ $reward->category }}</span>
                                                        <span>•</span>
                                                        <span class="text-amber-400 font-bold">{{ number_format($reward->points_cost) }} pts / EXP</span>
                                                        <span>•</span>
                                                        <span class="text-emerald-400 font-medium">Stock: {{ $reward->stock === null ? 'Unlimited' : $reward->stock }}</span>
                                                        <span>•</span>
                                                        <span class="text-indigo-300">Reserved: {{ $reward->reserved_quantity }}</span>
                                                        <span>•</span>
                                                        <span class="text-slate-300">Claimed: {{ $reward->claimed_quantity }}</span>
                                                        @if($reward->redemption_limit_per_member)
                                                            <span>•</span>
                                                            <span class="text-sky-400">Limit: {{ $reward->redemption_limit_per_member }}/user</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="flex items-center gap-2 flex-wrap">
                                                <button type="button"
                                                        @click="activeReward = {{ json_encode($reward) }}; editModal = true;"
                                                        class="btn-secondary text-xs py-1.5 px-3 flex items-center gap-1.5">
                                                    <i data-lucide="edit-2" class="w-3.5 h-3.5"></i>
                                                    <span>Edit</span>
                                                </button>

                                                @if($reward->status === 'draft')
                                                    <form action="{{ route('admin.shop.publish', $reward) }}" method="POST" class="inline">
                                                        @csrf
                                                        <button type="submit" class="btn-primary text-xs py-1.5 px-3 flex items-center gap-1 bg-emerald-600 hover:bg-emerald-500">
                                                            <i data-lucide="upload-cloud" class="w-3.5 h-3.5"></i>
                                                            <span>Publish</span>
                                                        </button>
                                                    </form>
                                                @else
                                                    <form action="{{ route('admin.shop.toggle-active', $reward) }}" method="POST" class="inline">
                                                        @csrf
                                                        <button type="submit" class="btn-secondary text-xs py-1.5 px-3">
                                                            {{ $reward->is_active ? 'Disable' : 'Enable' }}
                                                        </button>
                                                    </form>
                                                @endif

                                                @if($reward->status !== 'archived' && !$reward->is_archived)
                                                    <form action="{{ route('admin.shop.archive', $reward) }}" method="POST" class="inline">
                                                        @csrf
                                                        <button type="submit" class="btn-secondary text-xs py-1.5 px-3 text-amber-400">
                                                            Archive
                                                        </button>
                                                    </form>
                                                @endif

                                                @if(auth()->user()->isSuperAdmin() && $reward->redemptions_count === 0)
                                                    <form action="{{ route('admin.shop.destroy', $reward) }}" method="POST" class="inline" onsubmit="return confirm('Permanently delete reward item {{ addslashes($reward->title) }}?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn-secondary text-xs py-1.5 px-2.5 text-rose-400 border-rose-500/30">
                                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach

                                    <template x-teleport="body">
                                        <div x-show="editModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm" style="display: none;">
                                            <div class="bg-[#172033] border border-slate-800 rounded-2xl max-w-lg w-full p-6 space-y-4 shadow-xl max-h-[90vh] overflow-y-auto" @click.outside="editModal = false">
                                                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                                                    <h3 class="text-sm font-bold text-white">Edit Reward Item</h3>
                                                    <button @click="editModal = false" class="text-slate-400 hover:text-white">
                                                        <i data-lucide="x" class="w-4 h-4"></i>
                                                    </button>
                                                </div>

                                                <form :action="'/admin/shop/' + (activeReward ? activeReward.id : '')" method="POST" enctype="multipart/form-data" class="space-y-4">
                                                    @csrf
                                                    @method('PUT')

                                                    <div>
                                                        <label class="block text-xs font-semibold text-slate-300 mb-1">Title</label>
                                                        <input type="text" name="title" :value="activeReward?.title" required class="input-dark text-xs w-full">
                                                    </div>

                                                    <div class="grid grid-cols-2 gap-3">
                                                        <div>
                                                            <label class="block text-xs font-semibold text-slate-300 mb-1">Status</label>
                                                            <select name="status" :value="activeReward?.status" class="input-dark text-xs w-full">
                                                                <option value="draft">Draft</option>
                                                                <option value="published">Published</option>
                                                                <option value="archived">Archived</option>
                                                            </select>
                                                        </div>

                                                        <div>
                                                            <label class="block text-xs font-semibold text-slate-300 mb-1">Reward Type</label>
                                                            <select name="reward_type" :value="activeReward?.reward_type" class="input-dark text-xs w-full">
                                                                @foreach($types as $typeKey => $typeLabel)
                                                                    <option value="{{ $typeKey }}">{{ $typeLabel }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <label class="block text-xs font-semibold text-slate-300 mb-1">Associated Event</label>
                                                        <select name="event_id" :value="activeReward?.event_id" class="input-dark text-xs w-full">
                                                            <option value="">-- General Reward (No specific event) --</option>
                                                            @foreach($events as $ev)
                                                                <option value="{{ $ev->id }}">{{ $ev->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>

                                                    <div class="grid grid-cols-2 gap-3">
                                                        <div>
                                                            <label class="block text-xs font-semibold text-slate-300 mb-1">Category</label>
                                                            <select name="category" :value="activeReward?.category" required class="input-dark text-xs w-full">
                                                                @foreach($categories as $cat)
                                                                    <option value="{{ $cat }}">{{ $cat }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>

                                                        <div>
                                                            <label class="block text-xs font-semibold text-slate-300 mb-1">Points Cost (Spendable EXP)</label>
                                                            <input type="number" name="points_cost" :value="activeReward?.points_cost" required class="input-dark text-xs w-full font-mono">
                                                        </div>
                                                    </div>

                                                    <div class="grid grid-cols-3 gap-3">
                                                        <div>
                                                            <label class="block text-xs font-semibold text-slate-300 mb-1">Min Level</label>
                                                            <input type="number" name="min_level" :value="activeReward?.min_level" required class="input-dark text-xs w-full font-mono">
                                                        </div>

                                                        <div>
                                                            <label class="block text-xs font-semibold text-slate-300 mb-1">Stock</label>
                                                            <input type="number" name="stock" :value="activeReward?.stock" class="input-dark text-xs w-full font-mono">
                                                        </div>

                                                        <div>
                                                            <label class="block text-xs font-semibold text-slate-300 mb-1">User Limit</label>
                                                            <input type="number" name="redemption_limit_per_member" :value="activeReward?.redemption_limit_per_member" class="input-dark text-xs w-full font-mono">
                                                        </div>
                                                    </div>

                                                    <div class="grid grid-cols-2 gap-3">
                                                        <div>
                                                            <label class="block text-xs font-semibold text-slate-300 mb-1">Starts At</label>
                                                            <input type="datetime-local" name="starts_at" :value="activeReward?.starts_at ? activeReward.starts_at.substring(0, 16) : ''" class="input-dark text-xs w-full">
                                                        </div>

                                                        <div>
                                                            <label class="block text-xs font-semibold text-slate-300 mb-1">Ends At</label>
                                                            <input type="datetime-local" name="ends_at" :value="activeReward?.ends_at ? activeReward.ends_at.substring(0, 16) : ''" class="input-dark text-xs w-full">
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <label class="block text-xs font-semibold text-slate-300 mb-1">Description</label>
                                                        <textarea name="description" rows="2" :value="activeReward?.description" class="input-dark text-xs w-full"></textarea>
                                                    </div>

                                                    <div>
                                                        <label class="block text-xs font-semibold text-slate-300 mb-1">Eligibility Notes</label>
                                                        <input type="text" name="eligibility_notes" :value="activeReward?.eligibility_notes" class="input-dark text-xs w-full">
                                                    </div>

                                                    <div>
                                                        <label class="block text-xs font-semibold text-slate-300 mb-1">Fulfillment Instructions</label>
                                                        <textarea name="fulfillment_instructions" rows="2" :value="activeReward?.fulfillment_instructions" class="input-dark text-xs w-full"></textarea>
                                                    </div>

                                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                        <div>
                                                            <label class="block text-xs font-semibold text-slate-300 mb-1">Replace Image (File)</label>
                                                            <input type="file" name="image_file" accept="image/*" class="input-dark text-xs w-full file:mr-2 file:py-1 file:px-2 file:rounded-md file:border-0 file:text-[10px] file:bg-slate-800 file:text-white">
                                                        </div>
                                                        <div>
                                                            <label class="block text-xs font-semibold text-slate-300 mb-1">Or Image URL</label>
                                                            <input type="url" name="image_url" :value="activeReward?.image_url" placeholder="https://..." class="input-dark text-xs w-full">
                                                        </div>
                                                    </div>

                                                    <div class="flex items-center gap-2 pt-2">
                                                        <input type="checkbox" name="is_active" id="edit_is_active" value="1" :checked="activeReward?.is_active"
                                                               class="rounded border-slate-700 bg-slate-800 text-[#560E0F] focus:ring-[#560E0F]">
                                                        <label for="edit_is_active" class="text-xs text-slate-300">
                                                            Active & Available in Shop
                                                        </label>
                                                    </div>

                                                    <div class="flex items-center justify-end gap-2 pt-3">
                                                        <button type="button" @click="editModal = false" class="btn-secondary text-xs py-1.5 px-3">
                                                            Cancel
                                                        </button>
                                                        <button type="submit" class="btn-primary text-xs py-1.5 px-4">
                                                            Save Changes
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                <div class="p-4 border-t border-slate-800">
                                    {{ $rewards->links() }}
                                </div>
                            @endif
                        </div>
                    </div>

                </div>

            @endif

        </div>
    </div>
</x-layouts.admin>

