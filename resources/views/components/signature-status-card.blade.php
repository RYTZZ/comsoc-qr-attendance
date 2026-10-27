@props([
    'signatureStatus' => [],
])

@php
    $state = $signatureStatus['state'] ?? 'required';
    $label = $signatureStatus['label'] ?? 'Signature Required';
    $version = $signatureStatus['version'] ?? null;
    $registered = (bool) ($signatureStatus['registered'] ?? false);
@endphp

<div id="dashboard-signature-status-section"
     x-data="{
         registered: @js($registered),
         state: @js($state),
         label: @js($label),
         version: @js($version),
         modalOpen: false,
         modalMode: 'view', // 'view', 'draw'
         pad: null,
         busy: false,
         error: '',
         success: '',
         consent: false,
         previewUrl: '{{ route('signature.preview') }}?t=' + Date.now(),
         storeUrl: '{{ route('signature.store') }}',
         resetUrl: '{{ route('signature.reset') }}',
         ratio: 1,

         init() {
             window.addEventListener('signature-status-updated', (e) => {
                 if (e.detail) {
                     this.registered = !!e.detail.registered;
                     this.state = e.detail.state || (this.registered ? 'registered' : 'required');
                     this.label = e.detail.label || (this.registered ? 'Signature Registered' : 'Signature Required');
                     this.version = e.detail.version || null;
                     this.refreshPreview();
                 }
             });
         },

         refreshPreview() {
             this.previewUrl = '{{ route('signature.preview') }}?t=' + Date.now();
         },

         openViewModal() {
             this.error = '';
             this.success = '';
             this.modalMode = 'view';
             this.refreshPreview();
             this.modalOpen = true;
         },

         openRegisterModal(isReplacing = false) {
             this.error = '';
             this.success = '';
             this.modalMode = 'draw';
             this.consent = false;
             this.modalOpen = true;
             this.$nextTick(() => {
                 this.setupCanvas();
             });
         },

         closeModal() {
             this.modalOpen = false;
             if (this.pad) {
                 this.pad.clear();
             }
             this.error = '';
             this.success = '';
         },

         setupCanvas() {
             const canvas = this.$refs.dashboardCanvas;
             if (!canvas) return;

             if (!this.pad) {
                 this.pad = new SignaturePad(canvas, {
                     penColor: '#000000',
                     backgroundColor: 'rgba(0,0,0,0)',
                     minWidth: 0.8,
                     maxWidth: 2.4,
                 });
             }

             this.resizeCanvas();
         },

         resizeCanvas() {
             const canvas = this.$refs.dashboardCanvas;
             if (!canvas || !canvas.offsetWidth || !this.pad) return;

             const saved = this.pad.toData();
             this.ratio = Math.max(window.devicePixelRatio || 1, 1);
             canvas.width = canvas.offsetWidth * this.ratio;
             canvas.height = canvas.offsetHeight * this.ratio;
             canvas.getContext('2d').scale(this.ratio, this.ratio);
             this.pad.clear();
             if (saved.length) {
                 this.pad.fromData(saved);
             }
         },

         clearCanvas() {
             if (this.pad) {
                 this.pad.clear();
             }
             this.error = '';
         },

         exportDataUrl() {
             const src = this.$refs.dashboardCanvas;
             const maxW = 1000;
             const scale = Math.min(1, maxW / src.width);
             const out = document.createElement('canvas');
             out.width = Math.max(1, Math.round(src.width * scale));
             out.height = Math.max(1, Math.round(src.height * scale));
             out.getContext('2d').drawImage(src, 0, 0, out.width, out.height);
             return out.toDataURL('image/png');
         },

         async resetSignature() {
             this.error = '';
             this.success = '';

             const confirmed = window.appConfirm
                 ? await window.appConfirm({
                     title: 'Reset Active Signature?',
                     message: 'Resetting will deactivate your currently registered signature. A new handwritten signature must be registered before your signature can be used for future official attendance sheet exports. Your previous signature history remains securely archived.',
                     confirmText: 'Reset Signature',
                     cancelText: 'Keep Current Signature',
                     type: 'danger',
                 })
                 : false;

             if (!confirmed) {
                 return;
             }

             this.busy = true;
             try {
                 const res = await window.axios.delete(this.resetUrl);
                 this.registered = false;
                 this.state = res.data.state || 'required';
                 this.label = res.data.label || 'Signature Required';
                 this.version = res.data.version || null;
                 this.success = res.data.message || 'Signature reset successfully. You may now register a new signature.';

                 if (this.modalOpen && this.modalMode === 'view') {
                     this.modalOpen = false;
                 }

                 window.dispatchEvent(new CustomEvent('signature-status-updated', {
                     detail: {
                         registered: false,
                         state: this.state,
                         label: this.label,
                         version: this.version,
                     }
                 }));
             } catch (e) {
                 const d = e.response && e.response.data;
                 this.error = (d && (d.message || (d.errors && Object.values(d.errors)[0][0])))
                     || 'The signature could not be reset. Please try again.';
             } finally {
                 this.busy = false;
             }
         },

         async saveSignature() {
             this.error = '';
             this.success = '';

             if (!this.pad || this.pad.isEmpty()) {
                 this.error = 'Please draw your handwritten signature on the canvas first.';
                 return;
             }

             if (!this.registered && !this.consent) {
                 this.error = 'Please acknowledge the consent statement before registering.';
                 return;
             }

             this.busy = true;
             try {
                 const payload = { signature: this.exportDataUrl() };
                 if (!this.registered) payload.consent = true;

                 const res = this.registered
                     ? await window.axios.put(this.storeUrl, payload)
                     : await window.axios.post(this.storeUrl, payload);

                 this.registered = res.data.registered;
                 this.state = res.data.state || (res.data.registered ? 'registered' : 'required');
                 this.label = res.data.label || (res.data.registered ? 'Signature Registered' : 'Signature Required');
                 this.version = res.data.version;
                 this.success = res.data.message;

                 if (this.pad) {
                     this.pad.clear();
                 }
                 this.refreshPreview();

                 window.dispatchEvent(new CustomEvent('signature-status-updated', {
                     detail: {
                         registered: this.registered,
                         state: this.state,
                         label: this.label,
                         version: this.version,
                     }
                 }));

                 // Switch to view preview modal after saving
                 setTimeout(() => {
                     this.modalMode = 'view';
                 }, 400);
             } catch (e) {
                 const d = e.response && e.response.data;
                 this.error = (d && (d.message || (d.errors && Object.values(d.errors)[0][0])))
                     || 'The signature could not be saved. Please try again.';
             } finally {
                 this.busy = false;
             }
         }
     }"
     class="card bg-[#172033] border border-[#334155] p-5 sm:p-6 shadow-xl space-y-4">

    <!-- Card Header with Real-Time Reactive Status Badge -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-[#334155]">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 shadow-md transition-colors"
                 :class="registered ? 'bg-emerald-500/10 border border-emerald-500/20 text-emerald-400' : 'bg-amber-500/10 border border-amber-500/20 text-amber-400'">
                <template x-if="registered">
                    <i data-lucide="shield-check" class="w-5 h-5 text-emerald-400"></i>
                </template>
                <template x-if="!registered">
                    <i data-lucide="file-pen" class="w-5 h-5 text-amber-400"></i>
                </template>
            </div>
            <div>
                <h3 class="text-sm sm:text-base font-bold text-white font-brand-display flex items-center gap-2">
                    <span>Signature Status</span>
                </h3>
                <p class="text-xs text-[#94A3B8] mt-0.5">
                    Official handwritten signature used for event attendance sheets and credentials
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <span id="dashboard-signature-status-pill"
                  class="px-3 py-1 rounded-full text-xs font-semibold border flex items-center gap-1.5 transition-all shadow-sm"
                  :class="registered ? 'bg-emerald-950/40 border-emerald-500/30 text-emerald-300' : (state === 'unavailable' ? 'bg-rose-950/40 border-rose-500/30 text-rose-300' : 'bg-amber-950/40 border-amber-500/30 text-amber-300')">
                <span class="w-2 h-2 rounded-full"
                      :class="registered ? 'bg-emerald-400' : (state === 'unavailable' ? 'bg-rose-400' : 'bg-amber-400 animate-pulse')"></span>
                <span x-text="registered ? 'Signature Registered' + (version ? ' (v' + version + ')' : '') : (state === 'unavailable' ? 'Signature Unavailable' : 'Signature Required')"></span>
            </span>
        </div>
    </div>

    <!-- Feedback messages -->
    <div x-show="error" x-cloak role="alert" class="p-3 rounded-xl bg-red-950/40 border border-red-500/30 text-red-300 text-xs flex items-center justify-between gap-2">
        <span x-text="error"></span>
        <button type="button" @click="error = ''" class="text-red-400 hover:text-white text-xs">&times;</button>
    </div>
    <div x-show="success" x-cloak role="status" class="p-3 rounded-xl bg-emerald-950/40 border border-emerald-500/30 text-emerald-300 text-xs flex items-center justify-between gap-2">
        <span x-text="success"></span>
        <button type="button" @click="success = ''" class="text-emerald-400 hover:text-white text-xs">&times;</button>
    </div>

    <!-- Conditional Body & Actions -->
    <div class="bg-[#0B1120] rounded-xl p-4 sm:p-5 border border-[#334155]/60 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <!-- Status Explanation Text -->
        <div class="space-y-1">
            <template x-if="registered">
                <div>
                    <h4 class="text-xs sm:text-sm font-semibold text-white flex items-center gap-2">
                        <span class="text-emerald-400">●</span>
                        <span>Active Signature is Registered and Ready</span>
                    </h4>
                    <p class="text-xs text-[#94A3B8] mt-0.5 leading-relaxed">
                        Your handwritten signature is encrypted and securely stored in your vault. It will be stamped on official attendance sheets for sessions you attend.
                    </p>
                </div>
            </template>
            <template x-if="!registered">
                <div>
                    <h4 class="text-xs sm:text-sm font-semibold text-white flex items-center gap-2">
                        <span class="text-amber-400">●</span>
                        <span>Action Required: Handwritten Signature Missing</span>
                    </h4>
                    <p class="text-xs text-[#94A3B8] mt-0.5 leading-relaxed">
                        No active signature is on file. Register your handwritten signature now so your attendance records appear with an official signature on published sheets.
                    </p>
                </div>
            </template>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-wrap items-center gap-2 shrink-0">
            <!-- WHEN SIGNATURE IS REGISTERED -->
            <template x-if="registered">
                <div class="flex flex-wrap items-center gap-2">
                    <!-- 1. View Signature -->
                    <button type="button"
                            id="dashboard-view-signature-btn"
                            @click="openViewModal()"
                            class="btn-secondary text-xs py-2 px-3.5 flex items-center gap-1.5 shadow"
                            :disabled="busy">
                        <i data-lucide="eye" class="w-3.5 h-3.5 text-blue-400"></i>
                        <span>View Signature</span>
                    </button>

                    <!-- 2. Reset Signature -->
                    <button type="button"
                            id="dashboard-reset-signature-btn"
                            @click="resetSignature()"
                            class="btn-secondary text-xs py-2 px-3.5 text-rose-300 border-rose-500/30 hover:border-rose-400 hover:bg-rose-950/30 flex items-center gap-1.5 shadow"
                            :disabled="busy">
                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5 text-rose-400"></i>
                        <span>Reset Signature</span>
                    </button>

                    <!-- 3. Register New Signature -->
                    <button type="button"
                            id="dashboard-register-new-signature-btn"
                            @click="openRegisterModal(true)"
                            class="btn-primary text-xs py-2 px-3.5 flex items-center gap-1.5 shadow-lg"
                            :disabled="busy">
                        <i data-lucide="pen-tool" class="w-3.5 h-3.5"></i>
                        <span>Register New Signature</span>
                    </button>
                </div>
            </template>

            <!-- WHEN SIGNATURE IS REQUIRED -->
            <template x-if="!registered">
                <div class="flex items-center gap-2">
                    <!-- Register Signature -->
                    <button type="button"
                            id="dashboard-register-signature-btn"
                            @click="openRegisterModal(false)"
                            class="btn-primary text-xs py-2 px-4 flex items-center gap-1.5 shadow-lg bg-amber-600 hover:bg-amber-500 border-amber-500 text-white"
                            :disabled="busy">
                        <i data-lucide="pen-tool" class="w-3.5 h-3.5"></i>
                        <span>Register Signature</span>
                    </button>
                </div>
            </template>
        </div>
    </div>

    <!-- Modal for Viewing & Drawing Signatures -->
    <div x-show="modalOpen"
         x-cloak
         @keydown.escape.window="if (modalOpen && !busy) closeModal()"
         class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0 flex items-center justify-center"
         style="display: none;">

        <!-- Backdrop -->
        <div x-show="modalOpen"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="if (!busy) closeModal()"
             class="fixed inset-0 bg-black/75 backdrop-blur-sm transition-opacity"></div>

        <!-- Modal Content Container -->
        <div x-show="modalOpen"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-3 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-3 sm:scale-95"
             class="relative w-full max-w-lg bg-[#172033] border border-[#334155] rounded-2xl shadow-2xl p-6 text-left overflow-hidden z-10 space-y-4">

            <!-- Modal Header -->
            <div class="flex items-center justify-between pb-3 border-b border-[#334155]">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-burgundy-deep border border-[#8F3033]/50 flex items-center justify-center text-[#df747a]">
                        <i data-lucide="signature" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h4 class="font-brand-display text-base font-bold text-white tracking-tight"
                            x-text="modalMode === 'view' ? 'Your Registered Signature' : (registered ? 'Register Replacement Signature' : 'Register Handwritten Signature')">
                        </h4>
                        <p class="text-[11px] text-slate-400">
                            <span x-text="modalMode === 'view' ? 'Encrypted personal signature from vault' : 'Draw with mouse, touch or stylus'"></span>
                        </p>
                    </div>
                </div>
                <button type="button" @click="closeModal()" :disabled="busy" class="text-slate-400 hover:text-white transition p-1 rounded-lg">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- In-Modal Alerts -->
            <div x-show="error" x-cloak role="alert" class="p-3 rounded-xl bg-red-950/40 border border-red-500/30 text-red-300 text-xs" x-text="error"></div>
            <div x-show="success" x-cloak role="status" class="p-3 rounded-xl bg-emerald-950/40 border border-emerald-500/30 text-emerald-300 text-xs" x-text="success"></div>

            <!-- VIEW MODE -->
            <div x-show="modalMode === 'view'" class="space-y-4">
                <p class="text-xs text-[#94A3B8]">
                    This is your currently active electronic signature on file. It is stamped onto official attendance sheets when exported by authorized officers.
                </p>
                <div class="rounded-xl bg-white p-4 flex items-center justify-center min-h-[140px] shadow-inner border border-slate-700">
                    <img :src="previewUrl" alt="Your active electronic signature" class="max-h-28 object-contain">
                </div>
                <div class="flex items-center justify-between pt-2">
                    <button type="button"
                            @click="resetSignature()"
                            :disabled="busy"
                            class="btn-secondary text-xs text-rose-300 border-rose-500/30 hover:bg-rose-950/30 flex items-center gap-1.5">
                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5 text-rose-400"></i>
                        <span>Reset Signature</span>
                    </button>
                    <div class="flex items-center gap-2">
                        <button type="button"
                                @click="openRegisterModal(true)"
                                :disabled="busy"
                                class="btn-primary text-xs flex items-center gap-1.5">
                            <i data-lucide="pen-tool" class="w-3.5 h-3.5"></i>
                            <span>Register New</span>
                        </button>
                        <button type="button" @click="closeModal()" class="btn-secondary text-xs">
                            Close
                        </button>
                    </div>
                </div>
            </div>

            <!-- DRAW MODE -->
            <div x-show="modalMode === 'draw'" class="space-y-4">
                <div>
                    <label for="dashboard-signature-canvas" class="block text-xs font-semibold text-slate-300 mb-1.5">
                        Draw your signature inside the box below:
                    </label>
                    <div class="rounded-xl bg-white border-2 border-dashed border-slate-600 overflow-hidden shadow-inner">
                        <canvas id="dashboard-signature-canvas"
                                x-ref="dashboardCanvas"
                                role="img"
                                aria-label="Dashboard signature canvas"
                                class="w-full block"
                                style="height: 190px; touch-action: none;"></canvas>
                    </div>
                    <div class="flex items-center justify-between mt-2">
                        <span class="text-[11px] text-slate-400">Ensure the signature is clear and centered.</span>
                        <button type="button"
                                id="dashboard-signature-clear-btn"
                                @click="clearCanvas()"
                                :disabled="busy"
                                class="text-xs text-slate-400 hover:text-white underline">
                            Clear Canvas
                        </button>
                    </div>
                </div>

                <div x-show="!registered" class="p-3 rounded-xl bg-[#0B1120] border border-[#334155]">
                    <label class="flex items-start gap-2.5 text-[11px] text-slate-300 cursor-pointer">
                        <input type="checkbox" id="dashboard-signature-consent" x-model="consent" class="mt-0.5 rounded border-slate-600 text-burgundy-deep focus:ring-[#8F3033]">
                        <span class="leading-relaxed">
                            I consent to encrypting and storing this signature for official COMSOC attendance sheets and activity records.
                        </span>
                    </label>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-[#334155]">
                    <button type="button" @click="closeModal()" :disabled="busy" class="btn-secondary text-xs">
                        Cancel
                    </button>
                    <button type="button"
                            id="dashboard-signature-save-btn"
                            @click="saveSignature()"
                            :disabled="busy"
                            class="btn-primary text-xs flex items-center gap-1.5">
                        <span x-show="!busy" x-text="registered ? 'Save Replacement' : 'Save Signature'"></span>
                        <span x-show="busy" x-cloak>Saving…</span>
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>
