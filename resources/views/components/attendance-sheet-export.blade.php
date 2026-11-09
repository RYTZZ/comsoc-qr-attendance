@props(['event'])
@can('export-attendance-sheet', $event)
<div class="card mb-6 bg-[#172033] border border-[#334155] rounded-2xl p-4" x-data="{
        loading: false, info: null, error: '', reissue: false, reason: '', previewOpen: false, previewUrl: '',
        async check() {
            this.loading = true; this.error = '';
            try { this.info = (await window.axios.get('{{ route('attendance-sheet.check', $event) }}')).data; }
            catch (e) { this.error = 'Could not check signatures. Please try again.'; }
            finally { this.loading = false; }
        },
        get url() {
            const base = '{{ route('attendance-sheet.download', $event) }}';
            return this.reissue ? base + '?reissue=1&reason=' + encodeURIComponent(this.reason) : base;
        },
        openPreview() {
            this.previewUrl = this.url;
            this.previewOpen = true;
        },
        closePreview() {
            this.previewOpen = false;
        },
        printPreview() {
            try {
                const frame = this.$refs.pdfFrame;
                if (frame && frame.contentWindow) {
                    frame.contentWindow.focus();
                    frame.contentWindow.print();
                } else {
                    window.open(this.url, '_blank');
                }
            } catch (err) {
                window.open(this.url, '_blank');
            }
        }
    }">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-sm font-bold text-white">Official Attendance Sheet (FM-BUC-SCA-017)</h2>
            <p class="text-xs text-slate-400">Built from recorded attendance. Members without a registered signature are left blank.</p>
        </div>
        <button type="button" id="attendance-sheet-check-btn" class="btn-secondary" @click="check()" :disabled="loading">
            <span x-text="loading ? 'Checking…' : 'Prepare export'"></span>
        </button>
    </div>
    <p x-show="error" x-cloak class="text-xs text-red-300 mt-3" role="alert" x-text="error"></p>
    <div x-show="info" x-cloak class="mt-4 space-y-4">
        <!-- Signature Readiness Summary Panel -->
        <div id="attendance-sheet-readiness-panel" class="p-4 rounded-xl bg-[#0B1120] border border-[#334155] space-y-3">
            <div class="flex items-center gap-2 border-b border-[#334155] pb-2">
                <i data-lucide="clipboard-check" class="w-4 h-4 text-[#df747a]"></i>
                <h3 class="text-xs font-bold text-white uppercase tracking-wider font-brand-display">Attendance Sheet Signature Readiness</h3>
            </div>
            <ul class="text-xs text-slate-300 space-y-1.5 font-medium">
                <li class="flex items-center gap-2">
                    <span class="text-slate-400">• Eligible attendees:</span>
                    <span class="font-bold text-white font-mono" x-text="info ? info.attendee_count : 0"></span>
                </li>
                <li class="flex items-center gap-2">
                    <span class="text-slate-400">• Signatures registered:</span>
                    <span class="font-bold text-emerald-400 font-mono" x-text="info ? info.registered_count : 0"></span>
                </li>
                <li class="flex items-center gap-2">
                    <span class="text-slate-400">• Signatures missing:</span>
                    <span class="font-bold font-mono" :class="(info && info.missing_count > 0) ? 'text-amber-400' : 'text-slate-400'" x-text="info ? info.missing_count : 0"></span>
                </li>
            </ul>

            <!-- Missing Signatures Detailed Warning & List -->
            <div x-show="info && info.missing_count > 0" class="p-3.5 rounded-xl bg-amber-950/40 border border-amber-500/40 text-amber-200 text-xs space-y-2" role="alert">
                <div class="flex items-center gap-2 font-semibold text-amber-300">
                    <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-400 shrink-0"></i>
                    <span>Warning: <span x-text="info.missing_count"></span> eligible attendee(s) do not have a registered signature.</span>
                </div>
                <p class="text-[11px] text-amber-300/80">
                    Their attendance record will remain intact on the official attendance sheet, but their signature cell will be left blank.
                </p>
                <div class="pt-1">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-amber-400">Missing signatures:</p>
                    <ul class="list-disc ml-5 mt-1 space-y-0.5 text-slate-200 text-xs">
                        <template x-for="n in (info ? info.missing : [])" :key="n">
                            <li x-text="n"></li>
                        </template>
                    </ul>
                </div>
            </div>
        </div>

        <template x-if="info && info.already_issued_at">
            <div class="text-xs text-slate-400 p-3 rounded-xl bg-[#0B1120] border border-[#334155]">
                <p>An official sheet was already issued on <span class="font-semibold text-white" x-text="new Date(info.already_issued_at).toLocaleString()"></span>. Downloading returns that exact archived copy.</p>
                <label class="flex items-center gap-2 mt-2 text-slate-300 cursor-pointer">
                    <input type="checkbox" x-model="reissue" class="rounded bg-[#172033] border-[#334155] text-[#560E0F] focus:ring-[#8F3033]">
                    <span>Reissue with current data</span>
                </label>
                <input x-show="reissue" type="text" x-model="reason" placeholder="Reason for reissue (min. 10 characters)" class="mt-2 w-full rounded-lg bg-[#172033] border border-[#334155] text-xs text-white p-2 focus:border-[#8F3033] focus:ring-1 focus:ring-[#8F3033]">
            </div>
        </template>

        <div class="flex flex-wrap items-center gap-2.5 pt-1">
            <a id="attendance-sheet-download-btn" :href="url" class="btn-primary inline-flex items-center gap-2"
               :class="{ 'pointer-events-none opacity-50': reissue && reason.length < 10 }">
                <i data-lucide="file-down" class="w-4 h-4"></i>
                <span>Download Official PDF</span>
            </a>

            <button type="button" @click="openPreview()" class="btn-secondary inline-flex items-center gap-2 text-xs"
                    :disabled="reissue && reason.length < 10">
                <i data-lucide="eye" class="w-4 h-4 text-[#df747a]"></i>
                <span>Live Preview & Print</span>
            </button>
        </div>

        <!-- In-Browser PDF Preview Modal -->
        <div x-show="previewOpen" x-cloak
             class="fixed inset-0 z-50 overflow-y-auto bg-black/80 backdrop-blur-sm flex items-center justify-center p-3 sm:p-6"
             @keydown.escape.window="closePreview()">
            <div class="bg-[#172033] border border-[#334155] rounded-3xl w-full max-w-5xl shadow-2xl flex flex-col max-h-[92vh] overflow-hidden"
                 @click.away="closePreview()">
                
                <!-- Modal Header -->
                <div class="px-5 py-4 border-b border-[#334155] flex items-center justify-between gap-3 bg-[#0B1120]/50 shrink-0">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="p-2 rounded-xl bg-burgundy-deep/30 border border-[#8F3033]/40 text-[#df747a]">
                            <i data-lucide="file-text" class="w-4 h-4"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-sm font-bold text-white font-brand-display truncate">FM-BUC-SCA-017 Attendance Sheet Preview</h3>
                            <p class="text-[11px] text-slate-400">Institutional SorSU Bulan Campus Template • 8.5 × 13 in Legal Format</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <button type="button" @click="printPreview()" class="btn-secondary btn-sm flex items-center gap-1.5 text-xs">
                            <i data-lucide="printer" class="w-3.5 h-3.5 text-slate-300"></i>
                            <span class="hidden sm:inline">Print</span>
                        </button>
                        <a :href="url" class="btn-primary btn-sm flex items-center gap-1.5 text-xs">
                            <i data-lucide="download" class="w-3.5 h-3.5"></i>
                            <span class="hidden sm:inline">Download</span>
                        </a>
                        <button type="button" @click="closePreview()" class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>

                <!-- Modal Body: Embedded PDF iframe -->
                <div class="flex-1 p-3 sm:p-4 bg-[#0B1120] relative min-h-[480px]">
                    <iframe x-ref="pdfFrame"
                            :src="previewOpen ? previewUrl : ''"
                            class="w-full h-full min-h-[520px] rounded-xl border border-[#334155] bg-white shadow-inner"
                            title="Attendance Sheet Preview"></iframe>
                </div>

                <!-- Modal Footer -->
                <div class="px-5 py-3 border-t border-[#334155] bg-[#0B1120]/40 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-400 shrink-0">
                    <div class="flex items-center gap-3">
                        <span>Attendees: <strong class="text-white" x-text="info ? info.attendee_count : 0"></strong></span>
                        <span>Signatures: <strong class="text-emerald-400" x-text="info ? info.registered_count : 0"></strong></span>
                        <span x-show="info && info.missing_count > 0" class="text-amber-400">Missing: <strong x-text="info.missing_count"></strong></span>
                    </div>
                    <button type="button" @click="closePreview()" class="text-xs text-slate-400 hover:text-white underline">
                        Close Preview
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endcan
