@props([
    'badgeText' => 'COMING SOON',
    'badgeType' => 'discovery', // discovery (rose/burgundy), success (emerald), amber, sky
    'headline' => '',
    'description' => '',
    'progressLabel' => 'COMPILING SOMETHING AWESOME...',
    'buttonText' => 'Back to Base',
    'buttonRoute' => 'dashboard',
    'smallCaption' => '404? Nah. Just building something awesome.',
    'accentColor' => 'rose', // rose, emerald, sky, amber
])

@php
    $badgeClasses = match($badgeType) {
        'success' => 'bg-emerald-500/10 text-emerald-300 border-emerald-500/25',
        'amber' => 'bg-amber-500/10 text-amber-300 border-amber-500/25',
        'sky' => 'bg-sky-500/10 text-sky-300 border-sky-500/25',
        default => 'bg-rose-500/10 text-rose-300 border-rose-500/25',
    };

    $glowColor = match($accentColor) {
        'emerald' => 'bg-emerald-500/15',
        'sky' => 'bg-sky-500/15',
        'amber' => 'bg-amber-500/15',
        default => 'bg-[#8F3033]/20',
    };
@endphp

<x-app-layout>
<div class="min-h-[85vh] bg-[#0B1120] relative flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 overflow-hidden select-none"
     x-data="{
         messages: [
             'Our developers are probably fixing one more bug...',
             'Turning coffee into code. Please wait.',
             'Something awesome is compiling.',
             'while(!ready) { keep_building(); }',
             '99% complete... just kidding, we\'re developers.',
             'Commit early, commit often. Polishing the experience!',
             'Running unit tests and drinking water.'
         ],
         currentMsgIndex: 0,
         typedMessage: '',
         charIndex: 0,
         isDeleting: false,
         typeWriter() {
             const fullText = this.messages[this.currentMsgIndex];
             if (this.isDeleting) {
                 this.typedMessage = fullText.substring(0, this.charIndex - 1);
                 this.charIndex--;
             } else {
                 this.typedMessage = fullText.substring(0, this.charIndex + 1);
                 this.charIndex++;
             }

             let typingSpeed = this.isDeleting ? 30 : 60;

             if (!this.isDeleting && this.charIndex === fullText.length) {
                 typingSpeed = 2500;
                 this.isDeleting = true;
             } else if (this.isDeleting && this.charIndex === 0) {
                 this.isDeleting = false;
                 this.currentMsgIndex = (this.currentMsgIndex + 1) % this.messages.length;
                 typingSpeed = 500;
             }

             setTimeout(() => this.typeWriter(), typingSpeed);
         }
     }"
     x-init="typeWriter()">

    <!-- Subtle Background Ambient Glows & Geometric Floating Grid -->
    <div class="absolute -top-32 -left-32 w-96 h-96 {{ $glowColor }} rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-[#560E0F]/20 rounded-full blur-3xl pointer-events-none"></div>
    
    <!-- Floating Minimal Particles -->
    <div class="absolute inset-0 pointer-events-none opacity-20 overflow-hidden">
        <div class="absolute top-1/4 left-1/5 w-2 h-2 rounded-full bg-rose-400 animate-ping"></div>
        <div class="absolute top-3/4 right-1/4 w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></div>
        <div class="absolute top-1/3 right-1/3 w-1.5 h-1.5 rounded-full bg-amber-400 animate-ping" style="animation-delay: 1.5s;"></div>
        <div class="absolute bottom-1/5 left-1/3 w-1.5 h-1.5 rounded-full bg-sky-400 animate-pulse" style="animation-delay: 2s;"></div>
    </div>

    <!-- Centered Card Container -->
    <div class="relative w-full max-w-2xl bg-[#172033] border border-slate-800 rounded-3xl p-6 sm:p-10 shadow-2xl shadow-black/80 flex flex-col items-center text-center space-y-6">

        <!-- Top Status Pill -->
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full border text-[11px] font-extrabold tracking-wider uppercase {{ $badgeClasses }}">
            <span class="w-2 h-2 rounded-full bg-current animate-pulse"></span>
            <span>{{ $badgeText }}</span>
        </div>

        <!-- Custom Slot for Interactive Visual/Animation -->
        <div class="w-full max-w-lg my-2">
            {{ $slot }}
        </div>

        <!-- Main Copy -->
        <div class="space-y-3 max-w-lg">
            <h1 class="text-2xl sm:text-3xl font-extrabold font-brand-display text-white tracking-tight">
                {{ $headline }}
            </h1>
            <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                {{ $description }}
            </p>
        </div>

        <!-- Progress Tracker Bar (Playful compiling vibe) -->
        <div class="w-full max-w-md space-y-2 pt-2">
            <div class="flex items-center justify-between text-[10px] font-mono font-bold tracking-wider text-slate-400">
                <span class="flex items-center gap-1.5">
                    <span class="inline-block w-1.5 h-1.5 rounded-full bg-rose-400 animate-ping"></span>
                    <span>{{ $progressLabel }}</span>
                </span>
                <span class="text-rose-400">BUILDING</span>
            </div>
            <div class="w-full h-2 rounded-full bg-slate-900 border border-slate-800 overflow-hidden relative">
                <div class="h-full bg-gradient-to-r from-[#560E0F] via-[#8F3033] to-[#E11D48] rounded-full w-3/4 relative animate-pulse">
                    <div class="absolute inset-0 bg-white/20 animate-[shimmer_2s_infinite]"></div>
                </div>
            </div>
        </div>

        <!-- Rotating Terminal Dev Quote Box -->
        <div class="w-full max-w-md p-3 rounded-xl bg-[#0B1120] border border-slate-800/80 font-mono text-xs text-left text-slate-300 flex items-center gap-2.5 shadow-inner">
            <span class="text-rose-400 font-bold select-none">&gt;</span>
            <span class="truncate flex-1 text-slate-200" x-text="typedMessage"></span>
            <span class="inline-block w-2 h-4 bg-rose-400 animate-pulse shrink-0"></span>
        </div>

        <!-- Action Button & Back Link -->
        <div class="pt-2 flex flex-col items-center gap-3">
            <a href="{{ route($buttonRoute) }}"
               class="btn-primary text-xs sm:text-sm py-2.5 px-6 rounded-xl font-bold flex items-center gap-2 shadow-lg shadow-[#560E0F]/40 hover:scale-[1.02] active:scale-[0.98] transition">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>{{ $buttonText }}</span>
            </a>
            
            <p class="text-[11px] text-slate-500 font-mono">
                {{ $smallCaption }}
            </p>
        </div>

    </div>
</div>
</x-app-layout>
