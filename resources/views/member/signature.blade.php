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
                 registeredAt: @js($status['registered_at'] ? \Carbon\Carbon::parse($status['registered_at'])->format('M d, Y h:i A') : null),
                 url: @js(route('signature.store')),
                 resetUrl: @js(route('signature.reset')),
                 previewUrl: @js(route('signature.preview')),
             })">

            <div class="card bg-[#172033] border border-[#334155] shadow-xl p-6 space-y-5">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold text-white font-brand-display">Signature Status</h2>
                        <p class="text-xs text-[#94A3B8] mt-0.5" x-show="registered">Your official electronic signature is registered and ready for attendance sheets.</p>
                        <p class="text-xs text-[#94A3B8] mt-0.5" x-show="!registered">Register your handwritten signature to enable seamless official attendance verification.</p>
                    </div>
                    <span id="signature-status-badge" role="status"
                          class="px-2.5 py-1 rounded-full text-[11px] font-semibold border shrink-0"
                          :class="{
                              'bg-emerald-950/40 border-emerald-500/30 text-emerald-300': state === 'registered',
                              'bg-amber-950/40 border-amber-500/30 text-amber-300': state === 'required',
                              'bg-rose-950/40 border-rose-500/30 text-rose-300': state === 'unavailable'
                          }"
                          x-text="state === 'registered' ? 'Registered' : label"></span>
                </div>

                <template x-if="registered && !replacing">
                    <div class="space-y-4">
                        <!-- Registration Metadata Bar -->
                        <div class="p-3.5 rounded-xl bg-[#0F172A] border border-[#334155]/60 grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                            <div>
                                <span class="text-[#94A3B8] block text-[11px]">Registration Status</span>
                                <span class="font-semibold text-emerald-400 flex items-center gap-1.5 mt-0.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                    <span>Registered</span>
                                </span>
                            </div>
                            <div x-show="registeredAt">
                                <span class="text-[#94A3B8] block text-[11px]">Registration Date</span>
                                <span class="font-medium text-slate-200 mt-0.5 block" x-text="registeredAt"></span>
                            </div>
                            <div>
                                <span class="text-[#94A3B8] block text-[11px]">Vault Version</span>
                                <span class="font-medium text-slate-200 mt-0.5 block" x-text="'Version ' + (version || 1)"></span>
                            </div>
                        </div>

                        <div>
                            <p class="text-xs text-[#94A3B8] mb-2 font-medium">Your registered signature preview (visible only to you):</p>
                            <div class="rounded-xl bg-white p-3 inline-block shadow-sm border border-slate-200">
                                <img :src="previewUrl" alt="Preview of your saved signature" class="max-h-32 object-contain">
                            </div>
                        </div>

                        <div class="mt-4 flex flex-wrap items-center gap-2.5 pt-2 border-t border-[#334155]/50">
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
                    <div class="flex items-center justify-between mb-2">
                        <label for="signature-canvas" class="block text-xs font-semibold text-slate-300">
                            <span x-text="replacing ? 'Draw your new signature' : 'Draw your signature'"></span>
                            <span class="font-normal text-[#94A3B8]">(mouse, touch or stylus)</span>
                        </label>

                        <!-- Ink Selector -->
                        <div class="flex items-center gap-1.5 bg-[#0B1120] p-1 rounded-lg border border-[#334155]">
                            <button type="button" @click="setPenColor('#000000')"
                                    class="w-5 h-5 rounded-md bg-black border transition"
                                    :class="penColor === '#000000' ? 'border-[#df747a] ring-1 ring-[#df747a]' : 'border-slate-700'"
                                    title="Black Ink"></button>
                            <button type="button" @click="setPenColor('#1e3a8a')"
                                    class="w-5 h-5 rounded-md bg-blue-900 border transition"
                                    :class="penColor === '#1e3a8a' ? 'border-[#df747a] ring-1 ring-[#df747a]' : 'border-slate-700'"
                                    title="Deep Navy Ink"></button>
                        </div>
                    </div>

                    <div class="rounded-xl bg-white border-2 border-dashed border-[#334155] overflow-hidden relative">
                        <canvas id="signature-canvas" x-ref="canvas" role="img" aria-label="Signature drawing area"
                                class="w-full block" style="height: 220px; touch-action: none;"></canvas>
                        
                        <div x-show="strokeCount === 0" class="absolute inset-0 pointer-events-none flex items-center justify-center text-slate-300 text-xs font-medium">
                            <span class="bg-slate-100/80 px-3 py-1.5 rounded-full border border-slate-200 shadow-xs">Sign within this box</span>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-2 mt-3">
                        <div class="flex items-center gap-2">
                            <button type="button" id="signature-clear-btn" class="btn-secondary text-xs" @click="clear()" :disabled="busy">Clear</button>
                            <span x-show="strokeCount > 0" class="text-[11px] text-emerald-400 font-medium flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                <span>Stroke captured</span>
                            </span>
                        </div>
                        <span class="text-[11px] text-[#94A3B8]">Touchscreens & Apple Pencil / S-Pen supported</span>
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
