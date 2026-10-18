<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-[#560E0F]/30 border border-[#560E0F]/60 text-[#dfa6a9]">
                        Administration
                    </span>
                    <span class="text-xs text-slate-400">Progression System</span>
                </div>
                <h1 class="text-2xl font-bold text-white font-brand-display tracking-tight mt-1">
                    Reward Rules & Economy Control
                </h1>
                <p class="text-xs text-slate-400 mt-0.5">
                    Configure activity XP/Points payout rules, level thresholds, and manage auditable member balance adjustments.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.dashboard') }}" class="btn-secondary text-xs flex items-center gap-1.5">
                    <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                    <span>Admin Dashboard</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <div class="lg:col-span-2 space-y-6">

                    <div class="card bg-[#172033] border border-slate-800 p-6 space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                            <div>
                                <h2 class="text-base font-bold text-white font-brand-display">Activity Reward Rules</h2>
                                <p class="text-xs text-slate-400">Controls automated payouts granted upon verified activities.</p>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr class="border-b border-slate-800 text-slate-400 text-[10px] uppercase font-mono">
                                        <th class="py-2.5 px-3">Activity Type</th>
                                        <th class="py-2.5 px-3">Rule Name</th>
                                        <th class="py-2.5 px-3 text-right">XP Payout</th>
                                        <th class="py-2.5 px-3 text-right">Points Payout</th>
                                        <th class="py-2.5 px-3 text-center">Status</th>
                                        <th class="py-2.5 px-3 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800 text-slate-300">
                                    @foreach($rules as $r)
                                    <tr class="hover:bg-slate-900/40">
                                        <td class="py-3 px-3 font-mono text-[#e69094]">{{ $r->activity_type }}</td>
                                        <td class="py-3 px-3 font-semibold text-white">{{ $r->name }}</td>
                                        <td class="py-3 px-3 text-right font-mono font-bold text-[#dfa6a9]">+{{ $r->xp_amount }}</td>
                                        <td class="py-3 px-3 text-right font-mono font-bold text-amber-400">+{{ $r->points_amount }}</td>
                                        <td class="py-3 px-3 text-center">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $r->is_active ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-slate-800 text-slate-400' }}">
                                                {{ $r->is_active ? 'Active' : 'Disabled' }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-3 text-right">
                                            <div x-data="{ openEdit: false }">
                                                <button @click="openEdit = true" class="text-xs text-indigo-400 hover:text-indigo-300 font-semibold">
                                                    Edit
                                                </button>

                                                <div x-show="openEdit" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
                                                    <div @click.away="openEdit = false" class="card bg-[#172033] border border-slate-700 p-6 max-w-md w-full text-left space-y-4">
                                                        <h3 class="text-sm font-bold text-white">Edit Rule: {{ $r->name }}</h3>
                                                        <form method="POST" action="{{ route('admin.economy.rules.update', $r) }}" class="space-y-3">
                                                            @csrf
                                                            @method('PUT')
                                                            <div>
                                                                <label class="block text-xs text-slate-300 mb-1">Rule Display Name</label>
                                                                <input type="text" name="name" value="{{ $r->name }}" required class="input-dark text-xs w-full">
                                                            </div>
                                                            <div class="grid grid-cols-2 gap-3">
                                                                <div>
                                                                    <label class="block text-xs text-slate-300 mb-1">XP Amount</label>
                                                                    <input type="number" name="xp_amount" value="{{ $r->xp_amount }}" min="0" required class="input-dark text-xs w-full">
                                                                </div>
                                                                <div>
                                                                    <label class="block text-xs text-slate-300 mb-1">Points Amount</label>
                                                                    <input type="number" name="points_amount" value="{{ $r->points_amount }}" min="0" required class="input-dark text-xs w-full">
                                                                </div>
                                                            </div>
                                                            <label class="flex items-center gap-2 text-xs text-slate-300 cursor-pointer pt-1">
                                                                <input type="checkbox" name="is_active" value="1" {{ $r->is_active ? 'checked' : '' }} class="rounded border-slate-700 bg-slate-900 text-[#560E0F]">
                                                                <span>Active for automated awards</span>
                                                            </label>
                                                            <div class="flex justify-end gap-2 pt-3 border-t border-slate-800">
                                                                <button type="button" @click="openEdit = false" class="btn-secondary text-xs py-1.5 px-3">Cancel</button>
                                                                <button type="submit" class="btn-primary text-xs py-1.5 px-3">Save Changes</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="pt-4 border-t border-slate-800" x-data="{ openCreate: false }">
                            <button @click="openCreate = !openCreate" class="btn-secondary text-xs flex items-center gap-1.5">
                                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                <span>Add New Reward Rule</span>
                            </button>

                            <div x-show="openCreate" class="mt-4 p-4 rounded-xl bg-slate-900/80 border border-slate-800 space-y-3">
                                <h4 class="text-xs font-bold text-white uppercase tracking-wider">New Activity Rule</h4>
                                <form method="POST" action="{{ route('admin.economy.rules.store') }}" class="space-y-3">
                                    @csrf
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-[11px] text-slate-300 mb-1">Activity Identifier (Unique)</label>
                                            <input type="text" name="activity_type" required class="input-dark text-xs w-full">
                                        </div>
                                        <div>
                                            <label class="block text-[11px] text-slate-300 mb-1">Rule Name</label>
                                            <input type="text" name="name" required class="input-dark text-xs w-full">
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-[11px] text-slate-300 mb-1">Default XP</label>
                                            <input type="number" name="xp_amount" min="0" required class="input-dark text-xs w-full">
                                        </div>
                                        <div>
                                            <label class="block text-[11px] text-slate-300 mb-1">Default Points</label>
                                            <input type="number" name="points_amount" min="0" required class="input-dark text-xs w-full">
                                        </div>
                                    </div>
                                    <button type="submit" class="btn-primary text-xs py-1.5 px-3">
                                        Create Rule
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="card bg-[#172033] border border-slate-800 p-6 space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                            <div>
                                <h2 class="text-base font-bold text-white font-brand-display">Level Threshold Progression</h2>
                                <p class="text-xs text-slate-400">Progression tiers that determine member rank and badges.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                            @foreach($levels as $lvl)
                            <div class="p-3.5 rounded-xl bg-slate-900/60 border border-slate-800 flex flex-col justify-between">
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-xs font-bold text-amber-400 font-mono">Rank {{ $lvl->level }}</span>
                                        <span class="text-[10px] text-slate-500 font-mono">{{ number_format($lvl->min_xp) }} XP</span>
                                    </div>
                                    <h4 class="text-xs font-bold text-white">{{ $lvl->title }}</h4>
                                </div>
                                <div class="mt-3 pt-2 border-t border-slate-800 flex justify-end" x-data="{ openEditLvl: false }">
                                    <button @click="openEditLvl = true" class="text-[11px] text-slate-400 hover:text-white">
                                        Edit Threshold
                                    </button>

                                    <div x-show="openEditLvl" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm text-left">
                                        <div @click.away="openEditLvl = false" class="card bg-[#172033] border border-slate-700 p-6 max-w-sm w-full space-y-3">
                                            <h3 class="text-sm font-bold text-white">Edit Level {{ $lvl->level }}</h3>
                                            <form method="POST" action="{{ route('admin.economy.levels.update', $lvl) }}" class="space-y-3">
                                                @csrf
                                                @method('PUT')
                                                <div>
                                                    <label class="block text-xs text-slate-300 mb-1">Rank Title</label>
                                                    <input type="text" name="title" value="{{ $lvl->title }}" required class="input-dark text-xs w-full">
                                                </div>
                                                <div>
                                                    <label class="block text-xs text-slate-300 mb-1">Minimum XP</label>
                                                    <input type="number" name="min_xp" value="{{ $lvl->min_xp }}" min="0" required class="input-dark text-xs w-full">
                                                </div>
                                                <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
                                                    <button type="button" @click="openEditLvl = false" class="btn-secondary text-xs py-1.5 px-3">Cancel</button>
                                                    <button type="submit" class="btn-primary text-xs py-1.5 px-3">Save</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                </div>

                <div class="space-y-6">

                    <div class="card bg-[#172033] border border-amber-500/30 p-6 space-y-4">
                        <div class="flex items-center gap-2 text-amber-400 pb-2 border-b border-slate-800">
                            <i data-lucide="shield-alert" class="w-4 h-4"></i>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-white">Manual Balance Adjustment</h3>
                        </div>

                        <p class="text-xs text-slate-400 leading-relaxed">
                            Authorized administrators can adjust member XP and spendable points with mandatory audit logging.
                        </p>

                        <form method="POST" action="{{ route('admin.economy.adjust') }}" class="space-y-3">
                            @csrf

                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1">Select Member Account</label>
                                <x-custom-dropdown
                                    name="user_id"
                                    :options="\App\Models\User::where('role', 'student')->orderBy('name')->take(50)->get()->map(fn($u) => ['value' => $u->id, 'label' => $u->name . ' (' . $u->email . ')'])->toArray()"
                                    placeholder="Select Member"
                                />
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-300 mb-1">XP Delta (+/-)</label>
                                    <input type="number" name="xp_adjustment" value="0" required class="input-dark text-xs w-full font-mono">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-300 mb-1">Points Delta (+/-)</label>
                                    <input type="number" name="points_adjustment" value="0" required class="input-dark text-xs w-full font-mono">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1">Reason / Justification</label>
                                <textarea name="reason" rows="2" required class="input-dark text-xs w-full"></textarea>
                            </div>

                            <button type="submit" class="btn-primary w-full text-xs py-2 font-bold flex items-center justify-center gap-1.5">
                                <i data-lucide="check" class="w-4 h-4"></i>
                                <span>Apply Adjustment</span>
                            </button>
                        </form>
                    </div>

                    <div class="card bg-[#172033] border border-slate-800 p-5 space-y-3">
                        <div class="flex items-center gap-2 pb-2 border-b border-slate-800">
                            <i data-lucide="history" class="w-4 h-4 text-slate-400"></i>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-white">Recent Admin Adjustments</h3>
                        </div>

                        @if($recentXpAdjustments->isEmpty())
                            <p class="text-xs text-slate-500 py-3 text-center">No manual balance adjustments recorded.</p>
                        @else
                            <div class="space-y-2.5">
                                @foreach($recentXpAdjustments as $adj)
                                <div class="p-2.5 rounded-lg bg-slate-900/60 border border-slate-800/80 text-xs">
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-white">{{ $adj->user ? $adj->user->name : 'User' }}</span>
                                        <span class="font-mono font-bold text-[#dfa6a9]">{{ $adj->amount > 0 ? '+' : '' }}{{ $adj->amount }} XP</span>
                                    </div>
                                    <p class="text-[11px] text-slate-400 mt-0.5">{{ $adj->description }}</p>
                                    <p class="text-[10px] text-slate-500 mt-1">By {{ $adj->creator ? $adj->creator->name : 'System' }} • {{ $adj->created_at->diffForHumans() }}</p>
                                </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                </div>

            </div>

        </div>
    </div>
</x-app-layout>
