@props([
    'officer',
])

@php
    $isVacant = in_array(strtolower(trim($officer->name ?? '')), ['vacant', 'awaiting confirmation', 'name not provided', 'to be confirmed']);
    $initials = $isVacant ? '?' : ($officer->initials ?? '?');
    $badgeStyle = 'bg-[#df747a]/15 text-[#df747a] border-[#df747a]/30';
    if ($officer->program) {
        $badgeStyle = match(strtoupper(trim($officer->program))) {
            'BSIT' => 'bg-amber-500/15 text-amber-300 border-amber-500/30',
            'BSCS' => 'bg-cyan-500/15 text-cyan-300 border-cyan-500/30',
            'BSIS' => 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30',
            'BTVTED' => 'bg-violet-500/15 text-violet-300 border-violet-500/30',
            default => 'bg-[#df747a]/15 text-[#df747a] border-[#df747a]/30',
        };
    }
@endphp

<article data-reveal-item class="group relative rounded-2xl bg-[#172033]/30 border border-[#334155]/40 hover:border-[#df747a]/60 hover:bg-[#172033]/50 transition-all duration-300 p-5 sm:p-5 flex flex-col sm:flex-row items-center sm:items-start gap-4 sm:gap-5 shadow-md shadow-black/10 h-full">
    
    <!-- Left Section: Clean Officer Portrait / Initials Avatar -->
    <div class="w-20 sm:w-20 md:w-24 shrink-0 flex items-center justify-center">
        @if($officer->portrait_url)
            <img 
                src="{{ $officer->portrait_url }}" 
                alt="{{ $officer->name }} portrait" 
                class="w-18 h-18 sm:w-20 sm:h-20 md:w-22 md:h-22 rounded-2xl object-cover object-center border-2 border-[#334155]/60 group-hover:border-[#df747a] transition-all duration-300 shadow-sm"
                loading="lazy"
            >
        @else
            <div class="w-18 h-18 sm:w-20 sm:h-20 md:w-22 md:h-22 rounded-2xl bg-gradient-to-br from-[#172033] to-[#0B1120] border-2 border-[#334155]/60 group-hover:border-[#df747a] flex flex-col items-center justify-center text-white font-brand-display font-extrabold text-xl sm:text-2xl transition-all duration-300 shadow-sm select-none">
                <span class="{{ $isVacant ? 'text-slate-500' : 'text-[#df747a]' }}">{{ $initials }}</span>
            </div>
        @endif
    </div>

    <!-- Right Section: Officer Information Area -->
    <div class="flex-1 min-w-0 w-full flex flex-col items-center sm:items-start text-center sm:text-left justify-center space-y-1.5">
        
        <!-- 1. Full Name -->
        <h3 class="text-base sm:text-lg font-bold tracking-tight font-brand-display break-words {{ $isVacant ? 'text-slate-400 italic font-normal' : 'text-white' }}">
            {{ $officer->name }}
        </h3>

        <!-- 2. Official Position -->
        <div class="text-xs sm:text-sm font-semibold text-[#df747a]">
            {{ $officer->position }}
        </div>

        <!-- 3. Program Affiliation Badge (if applicable) -->
        @if(!empty($officer->program))
        <div class="pt-0.5">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-mono font-semibold border tracking-wide {{ $badgeStyle }}">
                {{ $officer->program }}
            </span>
        </div>
        @endif

        <!-- Optional Bio -->
        @if(!empty($officer->bio))
        <p class="text-xs text-slate-300 leading-relaxed font-normal break-words max-w-md pt-0.5">
            {{ $officer->bio }}
        </p>
        @endif

        <!-- Optional Social/Profile Link with min 44px touch target on mobile -->
        @if(!empty($officer->social_link))
        <div class="pt-1">
            <a 
                href="{{ $officer->social_link }}" 
                target="_blank" 
                rel="noopener noreferrer" 
                class="inline-flex items-center gap-1.5 py-1 px-1.5 text-xs font-semibold text-[#df747a] hover:text-white transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#df747a] rounded"
                aria-label="View profile of {{ $officer->name }}"
            >
                <span>View Profile</span>
                <i data-lucide="external-link" class="w-3.5 h-3.5" aria-hidden="true"></i>
            </a>
        </div>
        @endif

    </div>

</article>
