@php
    $user = Auth::user();
    $isMember = $user && ($user->isMember() || $user->isStudent());
    $isOrganizer = $user && ($user->isEventOrganizer() || $user->isAdmin());
@endphp

@if($isMember)
<div class="lg:hidden fixed bottom-0 inset-x-0 z-40 bg-[#172033]/95 backdrop-blur-md border-t border-[#334155] shadow-2xl safe-area-bottom">
    <div class="grid grid-cols-5 h-16 items-center px-1">
        <a href="{{ route('dashboard') }}"
           class="flex flex-col items-center justify-center py-1 transition-all duration-150 {{ request()->routeIs('dashboard') ? 'text-[#df747a]' : 'text-[#94A3B8] hover:text-[#F8FAFC]' }}">
            <i data-lucide="home" class="w-5 h-5 mb-1 {{ request()->routeIs('dashboard') ? 'stroke-[2.5]' : 'stroke-[1.75]' }}"></i>
            <span class="text-[10px] font-semibold tracking-tight">Home</span>
        </a>

        <a href="{{ route('member.dynamic_qr') }}"
           class="flex flex-col items-center justify-center py-1 transition-all duration-150 {{ request()->routeIs('member.dynamic_qr') ? 'text-[#df747a]' : 'text-[#94A3B8] hover:text-[#F8FAFC]' }}">
            <div class="relative">
                <i data-lucide="qr-code" class="w-5 h-5 mb-1 {{ request()->routeIs('member.dynamic_qr') ? 'stroke-[2.5]' : 'stroke-[1.75]' }}"></i>
                <span class="absolute -top-1 -right-1 flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#df747a] opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-[#8F3033]"></span>
                </span>
            </div>
            <span class="text-[10px] font-semibold tracking-tight">Attendance</span>
        </a>

        <a href="{{ route('shop.index') }}"
           class="flex flex-col items-center justify-center py-1 transition-all duration-150 {{ request()->routeIs('shop.*') ? 'text-[#df747a]' : 'text-[#94A3B8] hover:text-[#F8FAFC]' }}">
            <i data-lucide="gift" class="w-5 h-5 mb-1 {{ request()->routeIs('shop.*') ? 'stroke-[2.5]' : 'stroke-[1.75]' }}"></i>
            <span class="text-[10px] font-semibold tracking-tight">Rewards</span>
        </a>

        <a href="{{ route('leaderboard.index') }}"
           class="flex flex-col items-center justify-center py-1 transition-all duration-150 {{ request()->routeIs('leaderboard.*') ? 'text-amber-400' : 'text-[#94A3B8] hover:text-[#F8FAFC]' }}">
            <i data-lucide="trophy" class="w-5 h-5 mb-1 {{ request()->routeIs('leaderboard.*') ? 'stroke-[2.5] text-amber-400' : 'stroke-[1.75]' }}"></i>
            <span class="text-[10px] font-semibold tracking-tight">Rankings</span>
        </a>

        <a href="{{ route('profile.edit') }}"
           class="flex flex-col items-center justify-center py-1 transition-all duration-150 {{ request()->routeIs('profile.*') ? 'text-[#df747a]' : 'text-[#94A3B8] hover:text-[#F8FAFC]' }}">
            <i data-lucide="user" class="w-5 h-5 mb-1 {{ request()->routeIs('profile.*') ? 'stroke-[2.5]' : 'stroke-[1.75]' }}"></i>
            <span class="text-[10px] font-semibold tracking-tight">Profile</span>
        </a>
    </div>
</div>
@endif
