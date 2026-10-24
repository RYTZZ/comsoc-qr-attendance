<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-bold text-white font-brand-display">My Signature</h1>
            <p class="text-xs text-slate-400 mt-0.5">Register a reusable electronic signature for organizational attendance sheets</p>
        </div>
    </x-slot>

    <div class="py-8 bg-[#0B1120]">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8"
             x-data="signatureRegistration({
                 registered: @js($status['registered']),
                 state: @js($status['state']),
                 label: @js($status['label']),
                 version: @js($status['version']),
                 url: @js(route('signature.store')),
                 resetUrl: @js(route('signature.reset')),
                 previewUrl: @js(route('signature.preview')),
             })">

            <div class="card bg-[#172033] border border-[#334155] shadow-xl p-6 space-y-5">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-base font-bold text-white font-brand-display">Signature status</h2>
                    <span id="signature-status-badge" role="status"
                          class="px-2.5 py-1 rounded-full text-[11px] font-semibold border"
                          :class="{
                              'bg-emerald-950/40 border-emerald-500/30 text-emerald-300': state === 'registered',
                              'bg-amber-950/40 border-amber-500/30 text-amber-300': state === 'required',
                              'bg-rose-950/40 border-rose-500/30 text-rose-300': state === 'unavailable'
                          }"
                          x-text="state === 'registered' ? 'Signature Registered (v' + version + ')' : label"></span>
                </div>

                <template x-if="registered && !replacing">
                    <div>
                        <p class="text-xs text-[#94A3B8] mb-2">Your current signature (visible only to you):</p>
                        <div class="rounded-xl bg-white p-3 inline-block shadow-sm">
                            <img :src="previewUrl" alt="Preview of your saved signature" class="max-h-32">
                        </div>
                        <div class="mt-4 flex flex-wrap items-center gap-2.5">
                            <button type="button" id="signature-replace-btn" class="btn-primary text-xs flex items-center gap-1.5" @click="startReplace()" :disabled="busy">
                                <i data-lucide="pen-tool" class="w-3.5 h-3.5"></i>
                                <span>Replace Signature</span>
                            </button>
                            <button type="button" id="signature-reset-btn" class="btn-secondary text-xs text-rose-300 border-rose-500/30 hover:border-rose-400 hover:bg-rose-950/30 flex items-center gap-1.5" @click="reset()" :disabled="busy">
                                <i data-lucide="rotate-ccw" class="w-3.5 h-3.5 text-rose-400"></i>
                                <span>Reset Signature</span>
                            </button>
                        </div>
                    </div>
                </template>

                <div x-show="canDraw" x-cloak>
                    <label for="signature-canvas" class="block text-xs font-semibold text-slate-300 mb-2">
                        <span x-text="replacing ? 'Draw your new signature' : 'Draw your signature'"></span>
                        <span class="font-normal text-[#94A3B8]">(mouse, touch or stylus)</span>
                    </label>
                    <div class="rounded-xl bg-white border-2 border-dashed border-[#334155] overflow-hidden">
                        <canvas id="signature-canvas" x-ref="canvas" role="img" aria-label="Signature drawing area"
                                class="w-full block" style="height: 220px; touch-action: none;"></canvas>
                    </div>

                    <div class="flex flex-wrap gap-2 mt-3">
                        <button type="button" id="signature-clear-btn" class="btn-secondary text-xs" @click="clear()" :disabled="busy">Clear</button>
                    </div>

                    <div class="mt-4 space-y-3" x-show="!registered">
                        <label class="flex items-start gap-3 text-xs text-slate-300 cursor-pointer">
                            <input type="checkbox" id="signature-consent" x-model="consent" class="mt-0.5 rounded">
                            <span>
                                I understand that my signature will be stored securely (encrypted), may be reused in my applicable
                                organizational attendance sheets, and may be accessed by authorized officers when generating
                                legitimate organizational records. This is an electronic image of my signature, not a
                                cryptographically verified or guaranteed legally binding digital signature.
                            </span>
                        </label>
                    </div>

                    <div class="flex flex-wrap gap-2 mt-4">
                        <button type="button" id="signature-save-btn" class="btn-primary text-xs" @click="save()" :disabled="busy">
                            <span x-show="!busy" x-text="replacing ? 'Save replacement' : 'Save signature'"></span>
                            <span x-show="busy" x-cloak>Saving…</span>
                        </button>
                        <button type="button" class="btn-secondary text-xs" x-show="replacing" @click="cancelReplace()" :disabled="busy">Cancel</button>
                    </div>
                </div>

                <div x-show="error" x-cloak role="alert" class="p-3 rounded-xl bg-red-950/40 border border-red-500/30 text-red-300 text-xs" x-text="error"></div>
                <div x-show="success" x-cloak role="status" class="p-3 rounded-xl bg-emerald-950/40 border border-emerald-500/30 text-emerald-300 text-xs" x-text="success"></div>
            </div>
        </div>
    </div>
</x-app-layout>
