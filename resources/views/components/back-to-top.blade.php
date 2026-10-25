{{-- COMSOC Floating Back-to-Top Button Component --}}
<div x-data="{
        visible: false,
        checkScroll() {
            this.visible = window.scrollY > 350;
        },
        scrollToTop() {
            const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            window.scrollTo({
                top: 0,
                behavior: prefersReducedMotion ? 'auto' : 'smooth'
            });
        }
     }"
     x-init="checkScroll()"
     @scroll.window.passive="checkScroll()"
     class="fixed bottom-6 right-6 z-40 select-none print:hidden pointer-events-none"
     aria-live="polite">

    <button type="button"
            x-show="visible"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="opacity-0 translate-y-4 scale-90"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 scale-90"
            @click="scrollToTop()"
            class="pointer-events-auto group relative flex items-center justify-center w-11 h-11 sm:w-12 sm:h-12 rounded-full bg-[#172033]/90 hover:bg-[#560E0F] text-slate-200 hover:text-white border border-[#334155]/80 hover:border-[#df747a] shadow-lg shadow-black/40 hover:shadow-xl hover:shadow-[#560E0F]/40 backdrop-blur-md transition-all duration-200 ease-out focus:outline-none focus-visible:ring-2 focus-visible:ring-[#df747a] focus-visible:ring-offset-2 focus-visible:ring-offset-[#0B1120] active:scale-95 cursor-pointer touch-manipulation"
            aria-label="Back to top"
            title="Back to top"
            style="display: none;">

        {{-- Inner subtle red glow ring on hover --}}
        <span class="absolute inset-0 rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none bg-gradient-to-tr from-[#560E0F]/30 to-[#df747a]/20 blur-sm"></span>

        {{-- Arrow Up Icon with subtle upward slide on hover --}}
        <i data-lucide="arrow-up" class="w-5 h-5 relative z-10 transition-transform duration-200 ease-out group-hover:-translate-y-0.5 group-hover:scale-110 text-[#df747a] group-hover:text-white" aria-hidden="true"></i>

        <span class="sr-only">Back to top</span>
    </button>
</div>
