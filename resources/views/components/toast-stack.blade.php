<div x-data="{
    toasts: [],
    add(message, type = 'success', duration = 4000) {
        const id = Date.now() + Math.random().toString(36).substr(2, 4);
        this.toasts.push({ id, message, type, duration, progress: 100 });
        
        const startTime = Date.now();
        const interval = setInterval(() => {
            const elapsed = Date.now() - startTime;
            const toast = this.toasts.find(t => t.id === id);
            if (!toast) {
                clearInterval(interval);
                return;
            }
            toast.progress = Math.max(0, 100 - (elapsed / duration) * 100);
            if (elapsed >= duration) {
                this.remove(id);
                clearInterval(interval);
            }
        }, 50);
    },
    remove(id) {
        this.toasts = this.toasts.filter(t => t.id !== id);
    },
    init() {
        window.toast = (msg, type = 'success', duration = 4000) => {
            this.add(msg, type, duration);
        };

        @if(session('success'))
            this.add(@js(session('success')), 'success');
        @endif

        @if(session('error'))
            this.add(@js(session('error')), 'error');
        @endif

        @if(session('status'))
            this.add(@js(session('status')), 'info');
        @endif
    }
}"
class="fixed top-5 right-5 z-[9999] flex flex-col gap-2.5 max-w-sm w-full pointer-events-none select-none">
    <template x-for="toast in toasts" :key="toast.id">
        <div x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-[-12px] scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-90 translate-x-4"
             class="pointer-events-auto relative overflow-hidden rounded-xl border bg-[#171a23]/95 backdrop-blur-md shadow-2xl p-3.5 flex items-start gap-3"
             :class="{
                'border-emerald-500/30 text-white': toast.type === 'success',
                'border-red-500/30 text-white': toast.type === 'error',
                'border-amber-500/30 text-white': toast.type === 'warning',
                'border-indigo-500/30 text-white': toast.type === 'info'
             }">
            <div class="shrink-0 mt-0.5">
                <template x-if="toast.type === 'success'">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </template>
                <template x-if="toast.type === 'error'">
                    <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </template>
                <template x-if="toast.type === 'warning'">
                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </template>
                <template x-if="toast.type === 'info'">
                    <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </template>
            </div>

            <div class="flex-1 min-w-0 pr-2">
                <p class="text-xs font-medium leading-relaxed" x-text="toast.message"></p>
            </div>

            <button type="button"
                    @click="remove(toast.id)"
                    class="text-slate-500 hover:text-white transition shrink-0 p-0.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <div class="absolute bottom-0 left-0 h-0.5 bg-current opacity-30 transition-all duration-75 ease-linear"
                 :style="`width: ${toast.progress}%;`"></div>
        </div>
    </template>
</div>
