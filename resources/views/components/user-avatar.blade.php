@props([
    'user' => null,
    'seed' => null,
    'style' => 'adventurer',
    'size' => 'md',
    'alt' => 'Avatar',
    'class' => '',
])

@php
    $finalSeed = $seed ?? ($user?->avatar_seed ?: ($user ? \App\Services\AvatarService::DEFAULT_SEED : 'Felix'));
    $finalStyle = $style ?? ($user?->avatar_style ?: 'adventurer');
    $avatarUrl = \App\Services\AvatarService::getUrl($finalSeed, $finalStyle);
    $initials = $user ? strtoupper(substr($user->nickname ?: $user->name, 0, 1)) : '?';

    $sizeClasses = [
        'xs' => 'w-6 h-6 text-[10px]',
        'sm' => 'w-7 h-7 text-xs',
        'md' => 'w-9 h-9 text-xs',
        'lg' => 'w-12 h-12 text-sm',
        'xl' => 'w-16 h-16 text-xl',
        '2xl' => 'w-20 h-20 text-2xl',
    ][$size] ?? 'w-9 h-9 text-xs';
@endphp

<div {{ $attributes->merge(['class' => "relative shrink-0 rounded-2xl overflow-hidden bg-[#560E0F]/40 border border-[#8F3033]/50 flex items-center justify-center text-white font-bold select-none shadow-sm {$sizeClasses} {$class}"]) }}>
    <img
        src="{{ $avatarUrl }}"
        alt="{{ $alt }}"
        class="w-full h-full object-cover rounded-2xl transition-transform duration-200 group-hover:scale-105"
        loading="lazy"
        onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='flex';"
    />
    <span class="hidden w-full h-full items-center justify-center bg-[#560E0F] text-white font-brand-display">
        {{ $initials }}
    </span>
</div>
