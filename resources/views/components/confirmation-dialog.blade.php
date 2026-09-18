<div x-data="{
    open: false,
    title: 'Are you sure?',
    message: 'This action cannot be undone.',
    confirmText: 'Confirm',
    cancelText: 'Cancel',
    type: 'danger',
    requiresInput: false,
    inputLabel: '',
    inputPlaceholder: '',
    inputValue: '',
    inputRequired: false,
    inputError: '',
    pendingCallback: null,

    init() {
        window.appConfirm = (options) => {
            return new Promise((resolve) => {
                this.title = options.title || 'Are you sure?';
                this.message = options.message || 'Please confirm to continue.';
                this.confirmText = options.confirmText || 'Confirm';
                this.cancelText = options.cancelText || 'Cancel';
                this.type = options.type || 'danger';
                this.requiresInput = !!options.requiresInput;
                this.inputLabel = options.inputLabel || 'Reason';
                this.inputPlaceholder = options.inputPlaceholder || '';
                this.inputValue = options.defaultValue || '';
                this.inputRequired = options.inputRequired !== false;
                this.inputError = '';
                this.pendingCallback = resolve;
                this.open = true;

                this.$nextTick(() => {
                    if (this.requiresInput && this.$refs.promptInput) {
                        this.$refs.promptInput.focus();
                    } else if (this.$refs.confirmBtn) {
                        this.$refs.confirmBtn.focus();
                    }
                });
            });
        };

        window.appPrompt = (options) => {
            return window.appConfirm({
                ...options,
                requiresInput: true
            });
        };

        document.addEventListener('click', (e) => {
            const trigger = e.target.closest('[data-confirm]');
            if (!trigger) return;

            e.preventDefault();
            e.stopPropagation();

            const message = trigger.getAttribute('data-confirm');
            const title = trigger.getAttribute('data-confirm-title') || 'Confirm Action';
            const type = trigger.getAttribute('data-confirm-type') || 'danger';
            const confirmText = trigger.getAttribute('data-confirm-btn') || 'Confirm';

            window.appConfirm({
                title,
                message,
                type,
                confirmText
            }).then((confirmed) => {
                if (confirmed) {
                    const form = trigger.closest('form');
                    if (trigger.tagName === 'BUTTON' && trigger.type === 'submit' && form) {
                        form.submit();
                    } else if (trigger.tagName === 'A' && trigger.href) {
                        window.location.href = trigger.href;
                    } else if (form) {
                        form.submit();
                    }
                }
            });
        }, true);
    },

    confirm() {
        if (this.requiresInput && this.inputRequired && !this.inputValue.trim()) {
            this.inputError = 'Please provide a reason before proceeding.';
            if (this.$refs.promptInput) this.$refs.promptInput.focus();
            return;
        }

        const result = this.requiresInput ? this.inputValue.trim() : true;
        this.open = false;
        if (this.pendingCallback) {
            this.pendingCallback(result);
            this.pendingCallback = null;
        }
    },

    cancel() {
        this.open = false;
        if (this.pendingCallback) {
            this.pendingCallback(false);
            this.pendingCallback = null;
        }
    }
}"
x-show="open"
x-cloak
@keydown.escape.window="if (open) cancel()"
class="fixed inset-0 z-[100] overflow-y-auto px-4 py-6 sm:px-0 flex items-center justify-center"
style="display: none;">

    <div x-show="open"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="cancel()"
         class="fixed inset-0 bg-black/75 backdrop-blur-sm transition-opacity"></div>

    <div x-show="open"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-3 sm:scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave-end="opacity-0 translate-y-3 sm:scale-95"
         class="relative w-full max-w-md bg-[#171a23] border border-slate-800 rounded-2xl shadow-2xl p-6 text-left overflow-hidden z-10">

        <div class="flex items-start gap-3.5 mb-4">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                 :class="{
                     'bg-red-500/15 text-red-400 border border-red-500/30': type === 'danger',
                     'bg-amber-500/15 text-amber-400 border border-amber-500/30': type === 'warning',
                     'bg-indigo-500/15 text-indigo-400 border border-indigo-500/30': type === 'primary' || type === 'info',
                     'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30': type === 'success'
                 }">
                <template x-if="type === 'danger'">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </template>
                <template x-if="type === 'warning'">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </template>
                <template x-if="type === 'primary' || type === 'info'">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </template>
                <template x-if="type === 'success'">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </template>
            </div>

            <div class="flex-1 min-w-0">
                <h3 class="font-brand-display text-base font-bold text-white tracking-tight" x-text="title"></h3>
                <p class="text-xs text-slate-400 mt-1 leading-relaxed" x-text="message"></p>
            </div>
        </div>

        <template x-if="requiresInput">
            <div class="mb-5 space-y-1.5">
                <label class="block text-xs font-medium text-slate-300" x-text="inputLabel"></label>
                <textarea x-ref="promptInput"
                          x-model="inputValue"
                          @keydown.enter.prevent="if(!$event.shiftKey) confirm()"
                          rows="3"
                          :placeholder="inputPlaceholder"
                          class="input text-xs w-full resize-none"></textarea>
                <p x-show="inputError" x-text="inputError" class="text-[11px] text-red-400 font-medium"></p>
            </div>
        </template>

        <div class="flex items-center justify-end gap-2.5 pt-2">
            <button type="button"
                    @click="cancel()"
                    class="btn-secondary btn-sm py-2 px-3.5 text-xs text-slate-300 hover:text-white">
                <span x-text="cancelText">Cancel</span>
            </button>
            <button type="button"
                    x-ref="confirmBtn"
                    @click="confirm()"
                    :class="{
                        'btn-primary bg-[#7A1618] hover:bg-[#8e1b1d] focus:ring-[#7A1618]': type === 'danger' || type === 'primary',
                        'btn-warning bg-amber-600 hover:bg-amber-500 focus:ring-amber-500': type === 'warning',
                        'btn-success bg-emerald-600 hover:bg-emerald-500 focus:ring-emerald-500': type === 'success'
                    }"
                    class="btn-sm py-2 px-4 text-xs font-semibold text-white shadow-md">
                <span x-text="confirmText">Confirm</span>
            </button>
        </div>
    </div>
</div>
