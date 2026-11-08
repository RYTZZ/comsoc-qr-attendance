@props([
    'lifetimeExp' => 0,
    'currentLevel' => null,
])

@php
    $exp = (int) $lifetimeExp;
    $currentLvlNum = $currentLevel ? (int) $currentLevel->level : \App\Models\Level::forXp($exp)->level;
    $allBadges = \App\Models\Level::getCollection();
    $unlockedCount = collect($allBadges)->where('level', '<=', $currentLvlNum)->count();
@endphp

<div x-data="{
        selectedBadge: null,
        openModal(badge) {
            this.selectedBadge = badge;
            this.$nextTick(() => {
                if (window.lucideIcons) window.createIcons({ icons: window.lucideIcons });
                else if (window.lucide) window.lucide.createIcons();
            });
        },
        closeModal() {
            this.selectedBadge = null;
        }
    }"
    class="card bg-[#172033] border border-[#334155] shadow-xl p-5 sm:p-6">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#334155] pb-4 mb-6">
        <div>
            <div class="flex items-center gap-2">
                <i data-lucide="award" class="w-5 h-5 text-amber-400"></i>
                <h3 class="text-base font-bold text-white font-brand-display uppercase tracking-wider">
                    Level Badges & Rank Progression
                </h3>
            </div>
            <p class="text-xs text-[#94A3B8] mt-1">
                Visual progression tiers based on cumulative lifetime EXP. Click any badge to inspect requirements.
            </p>
        </div>

        <div class="flex items-center gap-2 self-start sm:self-auto">
            <span class="px-3 py-1 rounded-xl text-xs font-bold bg-[#0B1120] border border-[#334155] text-white">
                Unlocked: <strong class="text-amber-400 font-mono">{{ $unlockedCount }}</strong> / 20
            </span>
        </div>
    </div>

    <!-- 20-Badge Grid Collection -->
    <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-5 gap-3 sm:gap-4">
        @foreach($allBadges as $lvlNum => $b)
            @php
                $isUnlocked = $lvlNum <= $currentLvlNum;
                $tier = \App\Models\Level::tierForLevel($lvlNum);
                $styling = \App\Models\Level::tierStyling($tier);
                $needed = max(0, $b['min_xp'] - $exp);
            @endphp

            <div @click="openModal({{ json_encode(array_merge($b, ['is_unlocked' => $isUnlocked, 'needed_xp' => $needed, 'tier_styling' => $styling])) }})"
                 class="group relative flex flex-col items-center text-center p-3.5 rounded-2xl border transition-all duration-200 cursor-pointer {{ $isUnlocked ? 'bg-[#0B1120]/70 hover:bg-[#1E293B] ' . $styling['border'] . '/50 hover:' . $styling['border'] . ' shadow-md' : 'bg-[#0B1120]/30 border-slate-800/80 opacity-60 hover:opacity-90 hover:border-slate-600' }}">

                <!-- Badge Plate Container -->
                <div class="relative w-12 h-12 sm:w-14 sm:h-14 rounded-2xl flex items-center justify-center border-2 mb-2 transition-transform duration-200 group-hover:scale-105 {{ $isUnlocked ? 'bg-gradient-to-br ' . $styling['bg_gradient'] . ' ' . $styling['border'] . ' ' . $styling['glow'] . ' text-white shadow-lg' : 'bg-slate-900/90 border-slate-700 text-slate-500' }}">
                    @if($isUnlocked)
                        <i data-lucide="{{ $b['badge_icon'] }}" class="w-6 h-6 {{ $styling['accent'] }}"></i>
                    @else
                        <i data-lucide="lock" class="w-5 h-5 text-slate-500"></i>
                    @endif

                    <!-- Level Badge Tag -->
                    <span class="absolute -bottom-1 -right-1 px-1.5 py-0.2 rounded text-[9px] font-black font-brand-display border shadow {{ $isUnlocked ? 'bg-[#0B1120] ' . $styling['text'] . ' ' . $styling['border'] : 'bg-slate-800 text-slate-400 border-slate-600' }}">
                        {{ $lvlNum }}
                    </span>
                </div>

                <!-- Badge Name & Level Rank -->
                <h4 class="text-xs font-bold truncate max-w-full font-brand-display {{ $isUnlocked ? 'text-white' : 'text-slate-400' }}">
                    {{ $b['badge_name'] }}
                </h4>
                <p class="text-[10px] truncate max-w-full font-medium {{ $isUnlocked ? $styling['accent'] : 'text-slate-500' }}">
                    {{ $b['title'] }}
                </p>

                <!-- Status Pill -->
                <div class="mt-2 text-[10px] font-mono">
                    @if($isUnlocked)
                        <span class="px-2 py-0.5 rounded-full font-bold text-[9px] {{ $styling['pill_bg'] }}">
                            {{ $lvlNum === $currentLvlNum ? 'Current' : 'Unlocked' }}
                        </span>
                    @else
                        <span class="text-slate-400 text-[10px]">
                            {{ number_format($needed) }} EXP left
                        </span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <!-- Inspection Modal / Detail Drawer -->
    <div x-show="selectedBadge"
         x-cloak
         @keydown.escape.window="closeModal()"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
        
        <div @click.away="closeModal()"
             class="card bg-[#172033] border border-[#334155] rounded-3xl max-w-md w-full p-6 text-center shadow-2xl relative">
            
            <button @click="closeModal()" class="absolute top-4 right-4 text-slate-400 hover:text-white transition">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>

            <!-- Large Badge Display -->
            <div class="w-20 h-20 mx-auto rounded-3xl flex items-center justify-center border-2 mb-4 shadow-xl"
                 :class="selectedBadge && selectedBadge.is_unlocked ? 'bg-gradient-to-br ' + selectedBadge.tier_styling.bg_gradient + ' ' + selectedBadge.tier_styling.border + ' ' + selectedBadge.tier_styling.glow + ' text-white' : 'bg-slate-900 border-slate-700 text-slate-500'">
                <template x-if="selectedBadge && selectedBadge.is_unlocked">
                    <i :data-lucide="selectedBadge.badge_icon" class="w-10 h-10" :class="selectedBadge.tier_styling.accent"></i>
                </template>
                <template x-if="selectedBadge && !selectedBadge.is_unlocked">
                    <i data-lucide="lock" class="w-8 h-8 text-slate-500"></i>
                </template>
            </div>

            <!-- Titles -->
            <h3 class="text-xl font-extrabold text-white font-brand-display" x-text="selectedBadge ? selectedBadge.badge_name : ''"></h3>
            <p class="text-sm font-semibold mt-0.5"
               :class="selectedBadge && selectedBadge.is_unlocked ? selectedBadge.tier_styling.accent : 'text-slate-400'"
               x-text="selectedBadge ? ('Level ' + selectedBadge.level + ' • ' + selectedBadge.title) : ''"></p>

            <div class="my-4 p-3 rounded-2xl bg-[#0B1120] border border-[#334155] text-xs space-y-1.5 text-left">
                <div class="flex justify-between">
                    <span class="text-[#94A3B8]">Visual Tier:</span>
                    <span class="font-bold text-white" x-text="selectedBadge ? selectedBadge.tier : ''"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-[#94A3B8]">Required Total EXP:</span>
                    <span class="font-mono font-bold text-amber-400" x-text="selectedBadge ? Number(selectedBadge.min_xp).toLocaleString() + ' EXP' : ''"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-[#94A3B8]">Unlock Status:</span>
                    <span class="font-bold"
                          :class="selectedBadge && selectedBadge.is_unlocked ? 'text-emerald-400' : 'text-amber-400'"
                          x-text="selectedBadge && selectedBadge.is_unlocked ? 'Unlocked & Active' : 'Locked'"></span>
                </div>
                <template x-if="selectedBadge && !selectedBadge.is_unlocked">
                    <div class="flex justify-between pt-1 border-t border-[#334155]/60">
                        <span class="text-[#94A3B8]">EXP Remaining:</span>
                        <span class="font-mono font-bold text-rose-400" x-text="Number(selectedBadge.needed_xp).toLocaleString() + ' EXP needed'"></span>
                    </div>
                </template>
            </div>

            <p class="text-xs text-slate-400 leading-relaxed"
               x-text="selectedBadge && selectedBadge.is_unlocked ? 'Achievement verified and recognized across your profile, dashboard, and leaderboard.' : 'Accumulate EXP by attending campus activities, completing missions, and joining workshops to unlock this badge.'">
            </p>

            <button type="button" @click="closeModal()" class="btn-secondary w-full text-xs mt-5 py-2.5">
                Close
            </button>
        </div>
    </div>
</div>

