<x-layouts.admin :title="'Manage Student EXP & Points'">
<div class="space-y-6">
    <div class="page-header flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-rose-500/20 border border-rose-500/40 text-rose-300">
                    Super Admin Exclusive
                </span>
                <span class="text-xs text-slate-400">Governance & Economy Operations</span>
            </div>
            <h1 class="page-title text-2xl font-bold text-white font-brand-display tracking-tight mt-1">
                Manage Student EXP & Points
            </h1>
            <p class="text-xs text-slate-400 mt-0.5">
                Manual distribution and adjustments for student Lifetime EXP and Spendable Raffle Points with full audit ledger tracking.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.economy.index') }}" class="btn-secondary text-xs flex items-center gap-1.5">
                <i data-lucide="coins" class="w-4 h-4"></i>
                <span>Reward Economy</span>
            </a>
            <a href="{{ route('admin.shop.index') }}" class="btn-secondary text-xs flex items-center gap-1.5">
                <i data-lucide="gift" class="w-4 h-4"></i>
                <span>Reward Shop</span>
            </a>
            <a href="{{ route('admin.dashboard') }}" class="btn-secondary text-xs flex items-center gap-1.5">
                <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                <span>Admin Dashboard</span>
            </a>
        </div>
    </div>

    <div class="space-y-6">

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

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                <!-- Left Column: Student Search & Selection -->
                <div class="lg:col-span-5 space-y-4">
                    <div class="card bg-[#172033] border border-slate-800 p-5 rounded-2xl space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                            <h2 class="text-sm font-bold text-white flex items-center gap-2">
                                <i data-lucide="users" class="w-4 h-4 text-amber-400"></i>
                                <span>Find Student</span>
                            </h2>
                            <span class="text-[11px] text-slate-400">{{ $students->total() }} student(s)</span>
                        </div>

                        <!-- Search Form -->
                        <form method="GET" action="{{ route('admin.students.exp-points.index') }}" class="relative">
                            <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-2.5"></i>
                            <input type="text" name="search" value="{{ $search }}"
                                   placeholder="Search name, student #, email, or member ID..."
                                   class="input-dark text-xs w-full pl-9 pr-8">
                            @if(!empty($search))
                                <a href="{{ route('admin.students.exp-points.index') }}" class="absolute right-2.5 top-2.5 text-slate-400 hover:text-white">
                                    <i data-lucide="x" class="w-4 h-4"></i>
                                </a>
                            @endif
                        </form>

                        <!-- Student List -->
                        <div class="divide-y divide-slate-800/80 max-h-[500px] overflow-y-auto">
                            @forelse($students as $st)
                                @php
                                    $isSelected = $selectedStudent && $selectedStudent->id === $st->id;
                                    $stNumber = $st->student?->student_id ?? $st->student_number ?? $st->member_id;
                                @endphp
                                <a href="{{ route('admin.students.exp-points.index', ['search' => $search, 'user_id' => $st->id]) }}"
                                   class="p-3 rounded-xl flex items-center justify-between transition-colors block {{ $isSelected ? 'bg-[#560E0F]/40 border border-[#560E0F]' : 'hover:bg-slate-800/50' }}">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-lg bg-slate-800 flex items-center justify-center font-bold text-xs text-white">
                                            {{ strtoupper(substr($st->name, 0, 2)) }}
                                        </div>
                                        <div>
                                            <div class="text-xs font-bold text-white">{{ $st->name }}</div>
                                            <div class="text-[10px] text-slate-400 flex items-center gap-2">
                                                <span>{{ $stNumber }}</span>
                                                <span>•</span>
                                                <span class="text-indigo-400">{{ $st->member_id }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-[11px] font-bold text-amber-400 font-mono">{{ number_format($st->getSpendableExp()) }} pts</div>
                                        <div class="text-[10px] text-emerald-400 font-mono">{{ number_format($st->getLifetimeExp()) }} EXP</div>
                                    </div>
                                </a>
                            @empty
                                <div class="py-8 text-center text-xs text-slate-400">
                                    No students matched "{{ $search }}".
                                </div>
                            @endforelse
                        </div>

                        @if($students->hasPages())
                            <div class="pt-2 border-t border-slate-800">
                                {{ $students->links() }}
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Right Column: Student Profile, Balances & Adjustment Form -->
                <div class="lg:col-span-7 space-y-6">
                    @if($selectedStudent)
                        @php
                            $studentLifetimeExp = $selectedStudent->getLifetimeExp();
                            $studentPoints = $selectedStudent->getSpendableExp();
                            $studentLevel = \App\Models\Level::forXp($studentLifetimeExp);
                            $studentIdStr = $selectedStudent->student?->student_id ?? $selectedStudent->student_number ?? $selectedStudent->member_id;
                        @endphp

                        <!-- Selected Student Overview Card -->
                        <div class="card bg-[#172033] border border-slate-800 p-6 rounded-2xl">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-800">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.2 rounded-full text-[10px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                                            Level {{ $studentLevel->level ?? 1 }} • {{ $studentLevel->title ?? 'Novice' }}
                                        </span>
                                        <span class="text-xs text-slate-400 font-mono">{{ $selectedStudent->member_id }}</span>
                                    </div>
                                    <h2 class="text-xl font-bold text-white font-brand-display mt-1">{{ $selectedStudent->name }}</h2>
                                    <p class="text-xs text-slate-400">{{ $selectedStudent->email }} • Student #: {{ $studentIdStr }}</p>
                                </div>

                                <div class="flex items-center gap-4">
                                    <div class="px-4 py-2.5 rounded-xl bg-slate-900/80 border border-slate-800 text-right">
                                        <div class="text-[10px] uppercase font-bold tracking-wider text-slate-400">Lifetime EXP</div>
                                        <div class="text-base font-bold text-emerald-400 font-mono">{{ number_format($studentLifetimeExp) }}</div>
                                    </div>

                                    <div class="px-4 py-2.5 rounded-xl bg-slate-900/80 border border-slate-800 text-right">
                                        <div class="text-[10px] uppercase font-bold tracking-wider text-slate-400">Raffle Points</div>
                                        <div class="text-base font-bold text-amber-400 font-mono">{{ number_format($studentPoints) }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Adjustment Form with Alpine.js Confirmation Modal -->
                            <div x-data="{
                                confirmModal: false,
                                awardType: 'award',
                                expAmount: 50,
                                pointsAmount: 50,
                                reasonCategory: 'event_attendance',
                                customReason: '',
                                eventId: '',
                                idempotencyKey: 'adj_' + Date.now() + '_' + Math.floor(Math.random() * 100000),
                                submitForm() {
                                    $refs.adjustmentForm.submit();
                                }
                            }" class="mt-5 space-y-4">
                                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                                    <i data-lucide="sliders" class="w-4 h-4 text-indigo-400"></i>
                                    <span>Distribute or Adjust Balances</span>
                                </h3>

                                <form x-ref="adjustmentForm" method="POST" action="{{ route('admin.students.exp-points.adjust') }}" class="space-y-4">
                                    @csrf
                                    <input type="hidden" name="user_id" value="{{ $selectedStudent->id }}">
                                    <input type="hidden" name="idempotency_key" :value="idempotencyKey">

                                    <!-- Action Type Toggle -->
                                    <div class="grid grid-cols-2 gap-3 p-1 rounded-xl bg-slate-900 border border-slate-800">
                                        <button type="button" @click="awardType = 'award'"
                                                :class="awardType === 'award' ? 'bg-emerald-600 text-white shadow-sm font-bold' : 'text-slate-400 hover:text-white'"
                                                class="py-2 text-xs rounded-lg transition-all flex items-center justify-center gap-1.5">
                                            <i data-lucide="arrow-up-circle" class="w-3.5 h-3.5"></i>
                                            <span>Award EXP / Points</span>
                                        </button>
                                        <button type="button" @click="awardType = 'deduct'"
                                                :class="awardType === 'deduct' ? 'bg-rose-600 text-white shadow-sm font-bold' : 'text-slate-400 hover:text-white'"
                                                class="py-2 text-xs rounded-lg transition-all flex items-center justify-center gap-1.5">
                                            <i data-lucide="arrow-down-circle" class="w-3.5 h-3.5"></i>
                                            <span>Deduct EXP / Points</span>
                                        </button>
                                    </div>
                                    <input type="hidden" name="award_type" :value="awardType">

                                    <!-- Numeric Inputs for Separate EXP and Points -->
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-semibold text-slate-300 mb-1">
                                                EXP Amount <span class="text-slate-500 font-normal">(Affects Level/Rank)</span>
                                            </label>
                                            <div class="relative">
                                                <input type="number" name="exp_amount" min="0" max="50000" x-model.number="expAmount" required
                                                       class="input-dark text-xs w-full font-mono">
                                                <span class="absolute right-3 top-2 text-xs text-slate-500">EXP</span>
                                            </div>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-semibold text-slate-300 mb-1">
                                                Raffle / Redeem Points <span class="text-slate-500 font-normal">(Spendable Balance)</span>
                                            </label>
                                            <div class="relative">
                                                <input type="number" name="points_amount" min="0" max="50000" x-model.number="pointsAmount" required
                                                       class="input-dark text-xs w-full font-mono">
                                                <span class="absolute right-3 top-2 text-xs text-slate-500">Pts</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Event Association Dropdown -->
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-300 mb-1">
                                            Associated Event (Optional)
                                        </label>
                                        <select name="event_id" x-model="eventId" class="input-dark text-xs w-full">
                                            <option value="">-- No specific event (General Adjustment) --</option>
                                            @foreach($events as $event)
                                                <option value="{{ $event->id }}">{{ $event->name }} ({{ $event->event_date?->format('M d, Y') }})</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Reason Category & Custom Note -->
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-semibold text-slate-300 mb-1">
                                                Reason Category <span class="text-rose-400">*</span>
                                            </label>
                                            <select name="reason_category" x-model="reasonCategory" required class="input-dark text-xs w-full">
                                                @foreach($reasons as $val => $label)
                                                    <option value="{{ $val }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-semibold text-slate-300 mb-1">
                                                Specific Note / Context
                                            </label>
                                            <input type="text" name="custom_reason" x-model="customReason" placeholder="e.g. Winner 1st place in Mobile Dev Category"
                                                   class="input-dark text-xs w-full">
                                        </div>
                                    </div>

                                    <div class="pt-2">
                                        <button type="button" @click="confirmModal = true"
                                                class="w-full btn-primary py-2.5 text-xs flex items-center justify-center gap-2">
                                            <i data-lucide="check" class="w-4 h-4"></i>
                                            <span x-text="awardType === 'award' ? 'Review & Award to ' + '{{ addslashes($selectedStudent->name) }}' : 'Review & Deduct from ' + '{{ addslashes($selectedStudent->name) }}'"></span>
                                        </button>
                                    </div>
                                </form>

                                <!-- Confirmation Dialog Modal -->
                                <template x-teleport="body">
                                    <div x-show="confirmModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm" style="display: none;">
                                        <div class="bg-[#172033] border border-slate-800 rounded-2xl max-w-md w-full p-6 space-y-4 shadow-2xl" @click.outside="confirmModal = false">
                                            <div class="flex items-center gap-3 text-amber-400 pb-3 border-b border-slate-800">
                                                <div class="w-9 h-9 rounded-full bg-amber-500/20 border border-amber-500/30 flex items-center justify-center">
                                                    <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                                                </div>
                                                <div>
                                                    <h3 class="text-sm font-bold text-white">Confirm Balance Adjustment</h3>
                                                    <p class="text-[11px] text-slate-400">Please verify details before committing to the ledger.</p>
                                                </div>
                                            </div>

                                            <div class="p-4 rounded-xl bg-slate-900 border border-slate-800 space-y-2.5 text-xs">
                                                <div class="flex justify-between">
                                                    <span class="text-slate-400">Student:</span>
                                                    <span class="font-bold text-white">{{ $selectedStudent->name }}</span>
                                                </div>
                                                <div class="flex justify-between">
                                                    <span class="text-slate-400">Action:</span>
                                                    <span class="font-bold" :class="awardType === 'award' ? 'text-emerald-400' : 'text-rose-400'"
                                                          x-text="awardType === 'award' ? 'AWARD (Increase)' : 'DEDUCT (Decrease)'"></span>
                                                </div>
                                                <div class="flex justify-between">
                                                    <span class="text-slate-400">EXP Delta:</span>
                                                    <span class="font-bold font-mono" :class="awardType === 'award' ? 'text-emerald-400' : 'text-rose-400'"
                                                          x-text="(awardType === 'award' ? '+' : '-') + expAmount + ' EXP'"></span>
                                                </div>
                                                <div class="flex justify-between">
                                                    <span class="text-slate-400">Raffle Points Delta:</span>
                                                    <span class="font-bold font-mono" :class="awardType === 'award' ? 'text-amber-400' : 'text-rose-400'"
                                                          x-text="(awardType === 'award' ? '+' : '-') + pointsAmount + ' Points'"></span>
                                                </div>
                                                <div class="flex justify-between border-t border-slate-800/80 pt-2">
                                                    <span class="text-slate-400">Acting Admin:</span>
                                                    <span class="text-slate-300 font-semibold">{{ auth()->user()->name }}</span>
                                                </div>
                                            </div>

                                            <p class="text-[11px] text-slate-400 leading-relaxed">
                                                This action will generate immutable ledger transactions in both the EXP credit vault and points journal.
                                            </p>

                                            <div class="flex items-center justify-end gap-2 pt-2">
                                                <button type="button" @click="confirmModal = false" class="btn-secondary text-xs py-1.5 px-3">
                                                    Cancel
                                                </button>
                                                <button type="button" @click="confirmModal = false; submitForm();"
                                                        class="btn-primary text-xs py-1.5 px-4 font-bold"
                                                        :class="awardType === 'award' ? 'bg-emerald-600 hover:bg-emerald-500' : 'bg-rose-600 hover:bg-rose-500'">
                                                    Confirm & Apply Adjustment
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Transaction Ledger History for Selected Student -->
                        <div class="card bg-[#172033] border border-slate-800 p-6 rounded-2xl">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-800 mb-4">
                                <div>
                                    <h3 class="text-sm font-bold text-white flex items-center gap-2">
                                        <i data-lucide="history" class="w-4 h-4 text-emerald-400"></i>
                                        <span>Recent Transaction History</span>
                                    </h3>
                                    <p class="text-[11px] text-slate-400">Unified audit trail of EXP and Points adjustments for this student.</p>
                                </div>
                            </div>

                            @if($recentTransactions->isEmpty())
                                <div class="py-8 text-center text-xs text-slate-400">
                                    No ledger transactions recorded yet for this student.
                                </div>
                            @else
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left text-xs border-collapse">
                                        <thead>
                                            <tr class="text-[10px] uppercase tracking-wider text-slate-400 border-b border-slate-800 bg-slate-900/40">
                                                <th class="py-2.5 px-3">Date</th>
                                                <th class="py-2.5 px-3">EXP Delta</th>
                                                <th class="py-2.5 px-3">Points Delta</th>
                                                <th class="py-2.5 px-3">Reason / Details</th>
                                                <th class="py-2.5 px-3">Admin</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-800/60">
                                            @foreach($recentTransactions as $tx)
                                                <tr class="hover:bg-slate-900/30">
                                                    <td class="py-2.5 px-3 text-slate-400 whitespace-nowrap">
                                                        {{ $tx->created_at->format('M d, Y h:i A') }}
                                                    </td>
                                                    <td class="py-2.5 px-3 font-mono font-bold {{ $tx->lifetime_delta >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                                                        {{ ($tx->lifetime_delta >= 0 ? '+' : '') . $tx->lifetime_delta }} EXP
                                                    </td>
                                                    <td class="py-2.5 px-3 font-mono font-bold {{ $tx->spendable_delta >= 0 ? 'text-amber-400' : 'text-rose-400' }}">
                                                        {{ ($tx->spendable_delta >= 0 ? '+' : '') . $tx->spendable_delta }} pts
                                                    </td>
                                                    <td class="py-2.5 px-3 text-slate-300 max-w-xs truncate" title="{{ $tx->description }}">
                                                        {{ $tx->description }}
                                                    </td>
                                                    <td class="py-2.5 px-3 text-slate-400 whitespace-nowrap">
                                                        {{ $tx->creator?->name ?? 'System' }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>

                    @else
                        <div class="card bg-[#172033] border border-slate-800 p-12 text-center rounded-2xl">
                            <i data-lucide="user-search" class="w-12 h-12 text-slate-500 mx-auto mb-3"></i>
                            <h3 class="text-base font-bold text-white">No Student Selected</h3>
                            <p class="text-xs text-slate-400 mt-1">Search for a student on the left panel to review their balances, audit ledger, and distribute EXP or points.</p>
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </div>
</div>
</x-layouts.admin>
