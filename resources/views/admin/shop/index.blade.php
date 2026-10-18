<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-500/20 border border-amber-500/40 text-amber-300">
                        Officer Operations
                    </span>
                    <span class="text-xs text-slate-400">Merch & Rewards Management</span>
                </div>
                <h1 class="text-2xl font-bold text-white font-brand-display tracking-tight mt-1">
                    Reward Shop & Redemptions
                </h1>
                <p class="text-xs text-slate-400 mt-0.5">
                    Manage available merchandise, configure points cost and inventory stock, and fulfill or reject member redemption orders.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.economy.index') }}" class="btn-secondary text-xs flex items-center gap-1.5">
                    <i data-lucide="coins" class="w-4 h-4"></i>
                    <span>Reward Economy</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
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
                                                                  placeholder="e.g. Size out of stock, duplicate order request, or pickup unavailable"
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
                                            Category <span class="text-rose-400">*</span>
                                        </label>
                                        @php
                                            $adminCatOptions = [];
                                            foreach($categories as $c) {
                                                $adminCatOptions[] = ['value' => $c, 'label' => $c];
                                            }
                                        @endphp
                                        <x-custom-dropdown
                                            name="category"
                                            :options="$adminCatOptions"
                                            selected="Merchandise"
                                            placeholder="Select Category"
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-slate-300 mb-1">
                                            Points Cost <span class="text-rose-400">*</span>
                                        </label>
                                        <input type="number" name="points_cost" min="0" value="50" required
                                               class="input-dark text-xs w-full font-mono">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-300 mb-1">
                                            Min Level Req <span class="text-rose-400">*</span>
                                        </label>
                                        <input type="number" name="min_level" min="1" max="100" value="1" required
                                               class="input-dark text-xs w-full font-mono">
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-slate-300 mb-1">
                                            Stock (Blank for Unlimited)
                                        </label>
                                        <input type="number" name="stock" min="0" placeholder="e.g. 25"
                                               class="input-dark text-xs w-full font-mono">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-300 mb-1">
                                        Description
                                    </label>
                                    <textarea name="description" rows="3"
                                              class="input-dark text-xs w-full"></textarea>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-300 mb-1">
                                        Eligibility Instructions / Restrictions
                                    </label>
                                    <input type="text" name="eligibility_notes" placeholder="e.g. For official computing society members only"
                                           class="input-dark text-xs w-full">
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-300 mb-1">
                                        Fulfillment / Pickup Instructions
                                    </label>
                                    <textarea name="fulfillment_instructions" rows="2"
                                              placeholder="e.g. Claim at COMSOC Officer desk in the ICT building during office hours."
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

                                <div class="flex items-center gap-2 pt-2">
                                    <input type="checkbox" name="is_active" id="create_is_active" value="1" checked
                                           class="rounded border-slate-700 bg-slate-800 text-[#560E0F] focus:ring-[#560E0F]">
                                    <label for="create_is_active" class="text-xs text-slate-300">
                                        Publish immediately to member reward catalog
                                    </label>
                                </div>

                                <div class="pt-3">
                                    <button type="submit" class="w-full btn-primary py-2 text-xs flex items-center justify-center gap-2">
                                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                        <span>Create & Publish Reward</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="lg:col-span-7 space-y-4">
                        <div class="card bg-[#172033] border border-slate-800 p-6 rounded-2xl">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-800">
                                <div>
                                    <h2 class="text-base font-bold text-white font-brand-display">Published Catalog Items</h2>
                                    <p class="text-[11px] text-slate-400">Edit existing items, adjust inventory, view reservation breakdown, and configure event links.</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <form method="GET" action="{{ route('admin.shop.index') }}" class="flex items-center gap-2">
                                        <input type="hidden" name="tab" value="catalog">
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
                                    <p class="text-xs text-slate-400">No reward items have been added to the catalog yet.</p>
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
                                                        <span class="px-2 py-0.2 rounded-full text-[10px] font-bold {{ $reward->is_active ? 'bg-emerald-500/20 text-emerald-300' : 'bg-slate-800 text-slate-400' }}">
                                                            {{ $reward->is_active ? 'Active' : 'Unpublished' }}
                                                        </span>
                                                        @if($reward->is_archived)
                                                            <span class="px-2 py-0.2 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300">
                                                                Archived
                                                            </span>
                                                        @endif
                                                        @if($reward->event)
                                                            <span class="px-2 py-0.2 rounded-full text-[10px] font-semibold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                                                                {{ $reward->event->name }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <div class="text-[11px] text-slate-400 flex flex-wrap items-center gap-3 mt-1">
                                                        <span>{{ $reward->category }}</span>
                                                        <span>•</span>
                                                        <span class="text-amber-400 font-bold">{{ number_format($reward->points_cost) }} pts</span>
                                                        <span>•</span>
                                                        <span class="text-emerald-400 font-medium">Available: {{ $reward->stock === null ? 'Unlimited' : $reward->stock }}</span>
                                                        <span>•</span>
                                                        <span class="text-indigo-300">Reserved: {{ $reward->reserved_quantity }}</span>
                                                        <span>•</span>
                                                        <span class="text-slate-300">Claimed: {{ $reward->claimed_quantity }}</span>
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

                                                <form action="{{ route('admin.shop.toggle-active', $reward) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" class="btn-secondary text-xs py-1.5 px-3">
                                                        {{ $reward->is_active ? 'Unpublish' : 'Publish' }}
                                                    </button>
                                                </form>

                                                @if(!$reward->is_archived)
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
                                                            <input type="text" name="category" :value="activeReward?.category" required class="input-dark text-xs w-full">
                                                        </div>

                                                        <div>
                                                            <label class="block text-xs font-semibold text-slate-300 mb-1">Points Cost</label>
                                                            <input type="number" name="points_cost" :value="activeReward?.points_cost" required class="input-dark text-xs w-full font-mono">
                                                        </div>
                                                    </div>

                                                    <div class="grid grid-cols-2 gap-3">
                                                        <div>
                                                            <label class="block text-xs font-semibold text-slate-300 mb-1">Min Level</label>
                                                            <input type="number" name="min_level" :value="activeReward?.min_level" required class="input-dark text-xs w-full font-mono">
                                                        </div>

                                                        <div>
                                                            <label class="block text-xs font-semibold text-slate-300 mb-1">Stock</label>
                                                            <input type="number" name="stock" :value="activeReward?.stock" class="input-dark text-xs w-full font-mono">
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <label class="block text-xs font-semibold text-slate-300 mb-1">Description</label>
                                                        <textarea name="description" rows="2" :value="activeReward?.description" class="input-dark text-xs w-full"></textarea>
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
</x-app-layout>
