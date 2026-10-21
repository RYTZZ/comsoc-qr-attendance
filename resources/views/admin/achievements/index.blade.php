<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-500/20 border border-amber-500/40 text-amber-300">
                        Officer Operations
                    </span>
                    <span class="text-xs text-slate-400">Accolades & Milestones</span>
                </div>
                <h1 class="text-2xl font-bold text-white font-brand-display tracking-tight mt-1">
                    Achievements Management
                </h1>
                <p class="text-xs text-slate-400 mt-0.5">
                    Define automated milestone criteria, manage bonus XP/Points payouts, and manually award badges for organizational contributions.
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

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                <div class="lg:col-span-5 space-y-6">
                    <div class="card bg-[#172033] border border-slate-800 p-6 rounded-2xl space-y-5">
                        <div class="flex items-center gap-2 pb-3 border-b border-slate-800">
                            <i data-lucide="award" class="w-4 h-4 text-amber-400"></i>
                            <h2 class="text-base font-bold text-white font-brand-display">Create Achievement Rule</h2>
                        </div>

                        <form method="POST" action="{{ route('admin.achievements.store') }}" class="space-y-4">
                            @csrf

                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1">
                                    Achievement Title <span class="text-rose-400">*</span>
                                </label>
                                <input type="text" name="title" required placeholder="Enter achievement title"
                                       class="input-dark text-xs w-full">
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-300 mb-1">
                                        Category <span class="text-rose-400">*</span>
                                    </label>
                                    @php
                                        $catOpts = [];
                                        foreach($categories as $cat) {
                                            $catOpts[] = ['value' => $cat, 'label' => $cat];
                                        }
                                    @endphp
                                    <x-custom-dropdown
                                        name="category"
                                        :options="$catOpts"
                                        selected="Milestone"
                                        placeholder="Select category"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-300 mb-1">
                                        Lucide Icon Name
                                    </label>
                                    <input type="text" name="icon" value="award" placeholder="Enter Lucide icon name"
                                           class="input-dark text-xs w-full font-mono">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-300 mb-1">
                                        Criteria Type <span class="text-rose-400">*</span>
                                    </label>
                                    @php
                                        $critOpts = [];
                                        foreach($criteriaTypes as $val => $lbl) {
                                            $critOpts[] = ['value' => $val, 'label' => $lbl];
                                        }
                                    @endphp
                                    <x-custom-dropdown
                                        name="criteria_type"
                                        :options="$critOpts"
                                        selected="event_attendance"
                                        placeholder="Select trigger condition"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-300 mb-1">
                                        Threshold Required <span class="text-rose-400">*</span>
                                    </label>
                                    <input type="number" name="criteria_threshold" min="1" value="5" required placeholder="Enter threshold required"
                                           class="input-dark text-xs w-full font-mono">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-300 mb-1">
                                        XP Bonus Reward <span class="text-rose-400">*</span>
                                    </label>
                                    <input type="number" name="xp_bonus" min="0" value="100" required placeholder="Enter EXP bonus reward"
                                           class="input-dark text-xs w-full font-mono">
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-300 mb-1">
                                        Points Bonus Reward <span class="text-rose-400">*</span>
                                    </label>
                                    <input type="number" name="points_bonus" min="0" value="50" required placeholder="Enter points bonus reward"
                                           class="input-dark text-xs w-full font-mono">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1">
                                    Description & Unlock Guidance
                                </label>
                                <textarea name="description" rows="2"
                                          placeholder="Enter achievement description and unlock guidance"
                                          class="input-dark text-xs w-full"></textarea>
                            </div>

                            <div class="flex items-center gap-2 pt-2">
                                <input type="checkbox" name="is_active" id="ach_is_active" value="1" checked
                                       class="rounded border-slate-700 bg-slate-800 text-[#560E0F] focus:ring-[#560E0F]">
                                <label for="ach_is_active" class="text-xs text-slate-300">
                                    Active for automated progress tracking
                                </label>
                            </div>

                            <div class="pt-3">
                                <button type="submit" class="w-full btn-primary py-2 text-xs flex items-center justify-center gap-2">
                                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                    <span>Create Achievement</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="lg:col-span-7 space-y-4">
                    <div class="card bg-[#172033] border border-slate-800 p-6 rounded-2xl" x-data="{ awardModal: false, activeAch: null }">
                        <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                            <div>
                                <h2 class="text-base font-bold text-white font-brand-display">Registered Achievements</h2>
                                <p class="text-[11px] text-slate-400">Total defined milestones and unlocked members.</p>
                            </div>
                        </div>

                        @if($achievements->isEmpty())
                            <div class="p-8 text-center">
                                <p class="text-xs text-slate-400">No achievements configured yet.</p>
                            </div>
                        @else
                            <div class="divide-y divide-slate-800/80 mt-4">
                                @foreach($achievements as $ach)
                                    <div class="py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400 shrink-0">
                                                <i data-lucide="{{ $ach->icon ?: 'award' }}" class="w-5 h-5"></i>
                                            </div>

                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-sm font-bold text-white">{{ $ach->title }}</span>
                                                    <span class="px-2 py-0.2 rounded-full text-[10px] font-bold {{ $ach->is_active ? 'bg-emerald-500/20 text-emerald-300' : 'bg-slate-800 text-slate-400' }}">
                                                        {{ $ach->is_active ? 'Active' : 'Inactive' }}
                                                    </span>
                                                </div>

                                                <div class="text-[11px] text-slate-400 flex items-center gap-3 mt-0.5">
                                                    <span>{{ $ach->category }}</span>
                                                    <span>•</span>
                                                    <span>{{ $ach->criteria_type }} (≥{{ $ach->criteria_threshold }})</span>
                                                    <span>•</span>
                                                    <span class="text-amber-400">+{{ $ach->xp_bonus }} XP / +{{ $ach->points_bonus }} pts</span>
                                                    <span>•</span>
                                                    <span class="text-slate-300 font-semibold">{{ $ach->users_count }} unlocked</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2 sm:self-center">
                                            <button type="button"
                                                    @click="activeAch = {{ json_encode($ach) }}; awardModal = true;"
                                                    class="btn-primary text-xs py-1.5 px-3 flex items-center gap-1.5">
                                                <i data-lucide="gift" class="w-3.5 h-3.5"></i>
                                                <span>Award Member</span>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <template x-teleport="body">
                                <div x-show="awardModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm" style="display: none;">
                                    <div class="bg-[#172033] border border-slate-800 rounded-2xl max-w-md w-full p-6 space-y-4 shadow-xl" @click.outside="awardModal = false">
                                        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                                            <div class="flex items-center gap-2 text-amber-400 font-bold text-sm">
                                                <i data-lucide="award" class="w-4 h-4"></i>
                                                <span>Award Achievement</span>
                                            </div>
                                            <button @click="awardModal = false" class="text-slate-400 hover:text-white">
                                                <i data-lucide="x" class="w-4 h-4"></i>
                                            </button>
                                        </div>

                                        <p class="text-xs text-slate-300 leading-relaxed">
                                            Manually award <strong x-text="activeAch?.title"></strong> to a member for special organizational contributions or verified milestone completions.
                                        </p>

                                        <form :action="'/admin/achievements/' + (activeAch ? activeAch.id : '') + '/award'" method="POST" class="space-y-4">
                                            @csrf
                                            <div>
                                                <label class="block text-xs font-medium text-slate-300 mb-1">
                                                    Select Member <span class="text-rose-400">*</span>
                                                </label>
                                                @php
                                                    $studentOpts = [];
                                                    foreach($students as $st) {
                                                        $studentOpts[] = ['value' => $st->id, 'label' => "{$st->name} ({$st->email})"];
                                                    }
                                                @endphp
                                                <x-custom-dropdown
                                                    name="user_id"
                                                    :options="$studentOpts"
                                                    placeholder="Search & Select Member"
                                                />
                                            </div>

                                            <div class="flex items-center justify-end gap-2 pt-2">
                                                <button type="button" @click="awardModal = false" class="btn-secondary text-xs py-1.5 px-3">
                                                    Cancel
                                                </button>
                                                <button type="submit" class="btn-primary text-xs py-1.5 px-3.5">
                                                    Grant Award
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </template>

                            <div class="p-4 border-t border-slate-800">
                                {{ $achievements->links() }}
                            </div>
                        @endif
                    </div>
                </div>

            </div>

        </div>
    </div>
</x-app-layout>
