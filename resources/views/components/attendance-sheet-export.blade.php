@props(['event'])
@can('export-attendance-sheet', $event)
<div class="card mb-6 bg-[#172033] border border-[#334155] rounded-2xl p-4" x-data="{
        loading: false, info: null, error: '', reissue: false, reason: '',
        async check() {
            this.loading = true; this.error = '';
            try { this.info = (await window.axios.get('{{ route('attendance-sheet.check', $event) }}')).data; }
            catch (e) { this.error = 'Could not check signatures. Please try again.'; }
            finally { this.loading = false; }
        },
        get url() {
            const base = '{{ route('attendance-sheet.download', $event) }}';
            return this.reissue ? base + '?reissue=1&reason=' + encodeURIComponent(this.reason) : base;
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

        <a id="attendance-sheet-download-btn" :href="url" class="btn-primary inline-flex items-center gap-2"
           :class="{ 'pointer-events-none opacity-50': reissue && reason.length < 10 }">
            <i data-lucide="file-down" class="w-4 h-4"></i>
            <span>Download Official PDF</span>
        </a>
    </div>
</div>
@endcan
