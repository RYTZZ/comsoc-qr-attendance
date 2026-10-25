@props([
    'partner',
])

@php
    $badgeStyle = $partner->badge_class ?? match($partner->collaboration_type) {
        'Organization under COMSOC' => 'bg-[#df747a]/15 text-[#df747a] border-[#df747a]/30',
        'Government Collaborator' => 'bg-sky-500/15 text-sky-300 border-sky-500/30',
        'Community Collaborator' => 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30',
        'Academic Collaborator' => 'bg-indigo-500/15 text-indigo-300 border-indigo-500/30',
        default => 'bg-[#172033] text-slate-300 border-[#334155]/60',
    };
@endphp

<article data-reveal-item class="group relative rounded-2xl bg-[#172033]/30 border border-[#334155]/40 hover:border-[#df747a]/60 hover:bg-[#172033]/50 transition-all duration-300 p-5 sm:p-6 flex flex-col sm:flex-row items-center gap-5 sm:gap-6 shadow-md shadow-black/10">
    
    <!-- Left Section: Clean Logo Area (No nested boxes/frames/backgrounds) -->
    <div class="w-28 sm:w-32 md:w-36 shrink-0 flex items-center justify-center">
        @if($partner->logo_url)
            <img 
                src="{{ $partner->logo_url }}" 
                alt="{{ $partner->name }} logo" 
                class="max-h-20 sm:max-h-24 max-w-full w-auto h-auto object-contain object-center filter brightness-95 group-hover:brightness-110 group-hover:scale-105 transition-all duration-300"
                loading="lazy"
            >
        @else
            <div class="w-14 h-14 rounded-xl bg-[#560E0F]/20 text-[#df747a] flex items-center justify-center border border-[#df747a]/30">
                <i data-lucide="handshake" class="w-7 h-7" aria-hidden="true"></i>
            </div>
        @endif
    </div>

    <!-- Right Section: Organization Information Area (Left-Aligned) -->
    <div class="flex-1 min-w-0 w-full flex flex-col items-start text-left justify-center space-y-2">
        
        <!-- 1. Organization Name (Left-aligned) -->
        <h3 class="text-lg sm:text-xl font-bold text-white tracking-tight font-brand-display break-words">
            {{ $partner->name }}
        </h3>

        <!-- 2. Classification Badge (Left-aligned directly beneath name) -->
        @if($partner->collaboration_type)
        <div>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-mono font-semibold border tracking-wide {{ $badgeStyle }}">
                {{ $partner->collaboration_type }}
            </span>
        </div>
        @endif

        <!-- 3. Organization Description (Left-aligned directly beneath badge) -->
        @if($partner->description)
        <p class="text-xs sm:text-sm text-slate-300 leading-relaxed font-normal break-words max-w-xl pt-0.5">
            {{ $partner->description }}
        </p>
        @endif

        <!-- 4. Official Website Link (Left-aligned beneath description) -->
        @if(!empty($partner->website_url))
        <div class="pt-1.5">
            <a 
                href="{{ $partner->website_url }}" 
                target="_blank" 
                rel="noopener noreferrer" 
                class="inline-flex items-center gap-1.5 py-1 px-1.5 text-xs font-semibold text-[#df747a] hover:text-white transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#df747a] rounded"
                aria-label="Visit official website for {{ $partner->name }}"
            >
                <span>Visit Official Website</span>
                <i data-lucide="external-link" class="w-3.5 h-3.5" aria-hidden="true"></i>
            </a>
        </div>
        @endif

    </div>

</article>
