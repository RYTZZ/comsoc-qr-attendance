@props([
    'level' => 1,
    'size' => 'md', // 'xs', 'sm', 'md', 'lg', 'xl'
    'showTitle' => false,
    'showLevelNumber' => true,
    'showBadgeName' => false,
    'locked' => false,
    'interactive' => false,
])

@php
    $levelNum = is_object($level) ? (int) $level->level : (int) $level;
    $levelNum = max(1, min(20, $levelNum));

    $canonical = \App\Models\Level::CANONICAL_LEVELS[$levelNum] ?? \App\Models\Level::CANONICAL_LEVELS[1];
    $title = is_object($level) && !empty($level->title) ? $level->title : $canonical['title'];
    $badgeName = is_object($level) && !empty($level->badge_name) ? $level->badge_name : $canonical['badge_name'];
    $iconName = is_object($level) && !empty($level->badge_icon) ? $level->badge_icon : $canonical['badge_icon'];
    $tier = \App\Models\Level::tierForLevel($levelNum);
    $styling = \App\Models\Level::tierStyling($tier);

    // Dimension configurations
    $dimensions = match ($size) {
        'xs' => [
            'box' => 'w-5 h-5',
            'icon' => 'w-2.5 h-2.5',
            'num_text' => 'text-[8px]',
            'badge_shape' => 'rounded-md',
            'border_width' => 'border',
        ],
        'sm' => [
            'box' => 'w-7 h-7',
            'icon' => 'w-3.5 h-3.5',
            'num_text' => 'text-[9px]',
            'badge_shape' => 'rounded-lg',
            'border_width' => 'border',
        ],
        'lg' => [
            'box' => 'w-14 h-14',
            'icon' => 'w-7 h-7',
            'num_text' => 'text-xs',
            'badge_shape' => 'rounded-2xl',
            'border_width' => 'border-2',
        ],
        'xl' => [
            'box' => 'w-20 h-20',
            'icon' => 'w-10 h-10',
            'num_text' => 'text-sm',
            'badge_shape' => 'rounded-3xl',
            'border_width' => 'border-2',
        ],
        default => [ // md
            'box' => 'w-10 h-10',
            'icon' => 'w-5 h-5',
            'num_text' => 'text-[10px]',
            'badge_shape' => 'rounded-xl',
            'border_width' => 'border-[1.5px]',
        ],
    };

    $containerClasses = $locked
        ? 'opacity-40 grayscale filter bg-slate-900 border-slate-700 text-slate-500'
        : 'bg-gradient-to-br ' . $styling['bg_gradient'] . ' ' . $styling['border'] . ' ' . $styling['text'] . ' ' . $styling['glow'];
@endphp

<div {{ $attributes->merge(['class' => 'inline-flex items-center gap-2' . ($interactive ? ' group cursor-pointer' : '')]) }}
     title="{{ $locked ? 'Locked: Lvl ' . $levelNum . ' ' . $title : 'Lvl ' . $levelNum . ' ' . $title . ' (' . $badgeName . ' - ' . $tier . ')' }}">
    
    <!-- Shield / Badge Icon Plate -->
    <div class="relative shrink-0 flex items-center justify-center {{ $dimensions['box'] }} {{ $dimensions['badge_shape'] }} {{ $dimensions['border_width'] }} {{ $containerClasses }} shadow-md transition-transform duration-200 {{ $interactive ? 'group-hover:scale-105' : '' }}">
        @if($locked)
            <i data-lucide="lock" class="{{ $dimensions['icon'] }} text-slate-400"></i>
        @else
            <i data-lucide="{{ $iconName }}" class="{{ $dimensions['icon'] }} {{ $styling['accent'] }}"></i>
        @endif

        @if($showLevelNumber && in_array($size, ['md', 'lg', 'xl']))
            <span class="absolute -bottom-1 -right-1 px-1 py-0.2 {{ $dimensions['num_text'] }} font-black font-brand-display rounded {{ $locked ? 'bg-slate-800 text-slate-400 border border-slate-600' : 'bg-[#0B1120] ' . $styling['text'] . ' border ' . $styling['border'] }} shadow">
                {{ $levelNum }}
            </span>
        @endif
    </div>

    <!-- Optional Rank Title / Badge Name Text -->
    @if($showTitle || $showBadgeName)
        <div class="flex flex-col text-left leading-tight min-w-0">
            @if($showBadgeName)
                <span class="text-xs font-bold text-white truncate font-brand-display">
                    {{ $badgeName }}
                </span>
            @endif
            @if($showTitle)
                <span class="text-[11px] font-semibold {{ $locked ? 'text-slate-500' : $styling['accent'] }} truncate">
                    Lvl {{ $levelNum }} &bull; {{ $title }}
                </span>
            @endif
        </div>
    @endif
</div>

