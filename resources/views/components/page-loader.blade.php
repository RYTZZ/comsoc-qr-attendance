<div id="page-loading-scene"
     x-data="{
        ready: false,
        init() {
            const minDuration = 3000;
            const maxDuration = 5000;
            const startTime = performance.now();
            let pageLoaded = (document.readyState === 'complete');

            const checkAndDismiss = () => {
                const elapsed = performance.now() - startTime;
                if (elapsed >= minDuration && pageLoaded) {
                    this.ready = true;
                } else if (elapsed < minDuration) {
                    setTimeout(checkAndDismiss, minDuration - elapsed);
                }
            };

            if (!pageLoaded) {
                window.addEventListener('load', () => {
                    pageLoaded = true;
                    checkAndDismiss();
                }, { once: true });
            } else {
                checkAndDismiss();
            }

            setTimeout(() => {
                this.ready = true;
            }, maxDuration);
        }
     }"
     x-show="!ready"
     x-transition:leave="transition ease-out duration-500"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0 pointer-events-none"
     class="fixed inset-0 z-[9999] flex flex-col items-center justify-center bg-[#0f1117] text-slate-100 select-none">

    <div class="relative flex flex-col items-center">
        <div class="absolute -inset-6 rounded-full bg-[#7A1618]/25 blur-2xl pointer-events-none"></div>

        <div class="relative w-20 h-20 mb-5 flex items-center justify-center p-2 rounded-2xl bg-[#171a23] border border-slate-800 shadow-2xl">
            <img src="{{ asset('images/COMSOC.png') }}" alt="Computing Society Logo" class="w-full h-full object-contain">
        </div>

        <p class="text-xs font-semibold text-slate-300 tracking-wider mb-4 font-brand-display">Loading...</p>

        <div class="w-36 h-1 rounded-full bg-slate-800/80 overflow-hidden relative border border-slate-800">
            <div class="absolute inset-y-0 w-full loading-bar-indeterminate rounded-full"></div>
        </div>
    </div>
</div>
