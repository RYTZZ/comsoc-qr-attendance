<x-app-layout>
    <div class="min-h-screen bg-[#0B1120] text-[#F8FAFC] p-4 sm:p-6 lg:p-8"
         x-data="organizerScanner({
             scanUrl: '{{ route('organizer.scanner.scan') }}',
             csrfToken: '{{ csrf_token() }}',
             eventId: '{{ $selectedEvent?->id }}',
             sessionId: '{{ $activeSession?->id }}'
         })"
         x-init="initScanner()">

        <div class="max-w-4xl mx-auto space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-[#334155]">
                <div>
                    <span class="text-xs font-bold tracking-wider uppercase text-[#df747a]">Organizer Operations Workspace</span>
                    <h1 class="text-xl sm:text-2xl font-extrabold text-[#F8FAFC] tracking-tight flex items-center gap-2 font-brand-display">
                        <span>Event QR Scanner</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30">
                            Live Camera
                        </span>
                    </h1>
                </div>

                @if($events->isNotEmpty())
                <div class="flex items-center gap-2">
                    <form method="GET" action="{{ route('organizer.scanner') }}" class="flex items-center gap-2">
                        <select name="event_id" onchange="this.form.submit()" class="bg-[#172033] border border-[#334155] text-xs rounded-xl px-3 py-2 text-white focus:ring-2 focus:ring-[#8F3033] focus:outline-none">
                            @foreach($events as $ev)
                                <option value="{{ $ev->id }}" {{ $selectedEvent?->id === $ev->id ? 'selected' : '' }}>
                                    {{ Str::limit($ev->name, 28) }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>
                @endif
            </div>

            @if(!$selectedEvent)
                <div class="bg-[#172033] border border-[#334155] rounded-3xl p-8 text-center max-w-md mx-auto my-12">
                    <i data-lucide="calendar-x" class="w-12 h-12 text-[#94A3B8] mx-auto mb-3"></i>
                    <h3 class="text-base font-bold text-white mb-1 font-brand-display">No Assigned Events</h3>
                    <p class="text-xs text-[#94A3B8]">You are not currently assigned to any active events. Contact a COMSOC Admin for assignment.</p>
                </div>
            @else
                <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
                    @foreach($selectedEvent->attendanceSessions as $sess)
                        <a href="{{ route('organizer.scanner', ['event_id' => $selectedEvent->id, 'session_id' => $sess->id]) }}"
                           class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition border {{ $activeSession?->id === $sess->id ? 'bg-burgundy-gradient text-white border-[#8F3033] shadow-lg shadow-[#560E0F]/30' : 'bg-[#172033] text-[#94A3B8] hover:text-white border-[#334155]' }}">
                            <span class="w-2 h-2 rounded-full {{ $sess->isInSession() ? 'bg-emerald-400' : 'bg-amber-400' }}"></span>
                            <span>{{ $sess->label }}</span>
                        </a>
                    @endforeach
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="bg-[#172033] border border-[#334155] rounded-2xl p-3.5 text-center">
                        <span class="text-[11px] font-semibold text-[#94A3B8]">Total Scanned IN</span>
                        <p class="text-xl font-black text-emerald-400 font-mono mt-0.5 tabular-nums">{{ $stats['total_in'] }}</p>
                    </div>
                    <div class="bg-[#172033] border border-[#334155] rounded-2xl p-3.5 text-center">
                        <span class="text-[11px] font-semibold text-[#94A3B8]">Total Scanned OUT</span>
                        <p class="text-xl font-black text-[#df747a] font-mono mt-0.5 tabular-nums">{{ $stats['total_out'] }}</p>
                    </div>
                    <div class="bg-[#172033] border border-[#334155] rounded-2xl p-3.5 text-center">
                        <span class="text-[11px] font-semibold text-[#94A3B8]">On Time</span>
                        <p class="text-xl font-black text-white font-mono mt-0.5 tabular-nums">{{ $stats['present'] }}</p>
                    </div>
                    <div class="bg-[#172033] border border-[#334155] rounded-2xl p-3.5 text-center">
                        <span class="text-[11px] font-semibold text-[#94A3B8]">Late Attendees</span>
                        <p class="text-xl font-black text-amber-400 font-mono mt-0.5 tabular-nums">{{ $stats['late'] }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                    <div class="lg:col-span-7 bg-[#172033] border border-[#334155] rounded-3xl p-4 sm:p-6 shadow-2xl relative overflow-hidden">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2">
                                <i data-lucide="camera" class="w-4 h-4 text-[#df747a]"></i>
                                <span class="text-xs font-bold text-white tracking-tight font-brand-display">Live Scanner Feed</span>
                            </div>
                            <button type="button" @click="toggleCamera()" class="text-xs text-[#df747a] hover:text-white transition flex items-center gap-1 font-semibold">
                                <i data-lucide="switch-camera" class="w-3.5 h-3.5"></i>
                                <span x-text="cameraActive ? 'Stop Camera' : 'Start Camera'">Start Camera</span>
                            </button>
                        </div>

                        <div class="relative w-full aspect-square sm:aspect-4/3 bg-black rounded-2xl overflow-hidden border-2 border-[#334155] flex items-center justify-center">
                            <div id="scanner-reader" class="w-full h-full"></div>

                            <div class="absolute inset-0 pointer-events-none flex items-center justify-center">
                                <div class="w-56 h-56 border-2 border-[#8F3033]/60 rounded-3xl relative">
                                    <div class="absolute top-0 left-0 w-6 h-6 border-t-4 border-l-4 border-[#df747a] rounded-tl-xl -mt-1 -ml-1"></div>
                                    <div class="absolute top-0 right-0 w-6 h-6 border-t-4 border-r-4 border-[#df747a] rounded-tr-xl -mt-1 -mr-1"></div>
                                    <div class="absolute bottom-0 left-0 w-6 h-6 border-b-4 border-l-4 border-[#df747a] rounded-bl-xl -mb-1 -ml-1"></div>
                                    <div class="absolute bottom-0 right-0 w-6 h-6 border-b-4 border-r-4 border-[#df747a] rounded-br-xl -mb-1 -mr-1"></div>
                                </div>
                            </div>

                            <div x-show="processing" class="absolute inset-0 bg-[#0B1120]/85 backdrop-blur-xs flex flex-col items-center justify-center z-10" style="display: none;">
                                <i data-lucide="loader-2" class="w-8 h-8 text-[#df747a] animate-spin mb-2"></i>
                                <span class="text-xs font-bold text-white font-brand-display">Verifying Token Authenticity...</span>
                            </div>
                        </div>

                        <p class="text-center text-[11px] text-[#94A3B8] mt-3">
                            Aim camera at member's dynamic 30-second rolling QR pass. Immediate server-side validation.
                        </p>

                        <!-- Manual Token Lookup Section -->
                        <div class="mt-4 pt-4 border-t border-[#334155]/80">
                            <label class="block text-xs font-bold text-white mb-1.5 font-brand-display flex items-center justify-between">
                                <span class="flex items-center gap-1.5">
                                    <i data-lucide="key" class="w-3.5 h-3.5 text-[#df747a]"></i>
                                    <span>Manual Attendance Lookup</span>
                                </span>
                                <span class="text-[10px] text-[#94A3B8] font-normal">Can't scan QR?</span>
                            </label>
                            <form @submit.prevent="submitManualToken()" class="flex items-center gap-2">
                                <div class="relative flex-1">
                                    <input type="text"
                                           x-model="manualTokenInput"
                                           placeholder="e.g. 7K9-MP4"
                                           maxlength="10"
                                           class="w-full bg-[#0B1120] border border-[#334155] focus:border-[#df747a] focus:ring-1 focus:ring-[#df747a] rounded-xl px-3 py-2 text-xs font-mono font-bold tracking-wider text-white placeholder-[#64748B] uppercase uppercase-input outline-none transition"
                                           :disabled="processing">
                                </div>
                                <button type="submit"
                                        :disabled="processing || !manualTokenInput || manualTokenInput.trim().length < 3"
                                        class="px-4 py-2 rounded-xl bg-burgundy-deep hover:bg-[#8F3033] text-white text-xs font-bold transition disabled:opacity-50 disabled:cursor-not-allowed shadow-sm shrink-0 flex items-center gap-1">
                                    <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                                    <span>Verify</span>
                                </button>
                            </form>
                            <p class="text-[10px] text-[#64748B] mt-1.5">
                                Enter the 6-character code shown on the student's dynamic attendance pass.
                            </p>
                        </div>
                    </div>

                    <div class="lg:col-span-5 space-y-4">
                        <div class="bg-[#172033] border rounded-3xl p-5 sm:p-6 transition-all duration-300"
                             :class="{
                                 'border-emerald-500/50 bg-emerald-950/25 shadow-emerald-900/20 shadow-xl': lastResult && lastResult.result_type === 'Accepted',
                                 'border-amber-500/50 bg-amber-950/25 shadow-amber-900/20 shadow-xl': lastResult && (lastResult.result_type === 'Duplicate' || lastResult.result_type === 'Expired'),
                                 'border-rose-500/50 bg-rose-950/25 shadow-rose-900/20 shadow-xl': lastResult && (lastResult.result_type === 'Ineligible' || lastResult.result_type === 'Wrong event' || lastResult.result_type === 'Invalid token'),
                                 'border-[#334155]': !lastResult
                             }">
                            <div class="flex items-center justify-between pb-3 border-b border-[#334155]/80 mb-4">
                                <span class="text-xs font-bold text-[#94A3B8] uppercase tracking-wider font-brand-display">Verification Status</span>
                                <template x-if="lastResult">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold"
                                          :class="{
                                              'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30': lastResult.result_type === 'Accepted',
                                              'bg-amber-500/20 text-amber-400 border border-amber-500/30': lastResult.result_type === 'Duplicate' || lastResult.result_type === 'Expired',
                                              'bg-rose-500/20 text-rose-400 border border-rose-500/30': lastResult.result_type === 'Ineligible' || lastResult.result_type === 'Wrong event' || lastResult.result_type === 'Invalid token'
                                          }"
                                          x-text="lastResult.result_type">
                                    </span>
                                </template>
                            </div>

                            <template x-if="!lastResult">
                                <div class="py-8 text-center text-[#94A3B8]">
                                    <i data-lucide="scan-line" class="w-10 h-10 mx-auto mb-2 opacity-50"></i>
                                    <p class="text-xs">Awaiting member scan...</p>
                                </div>
                            </template>

                            <template x-if="lastResult">
                                <div class="space-y-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center font-bold text-lg"
                                             :class="{
                                                 'bg-emerald-500/20 text-emerald-400': lastResult.result_type === 'Accepted',
                                                 'bg-amber-500/20 text-amber-400': lastResult.result_type === 'Duplicate' || lastResult.result_type === 'Expired',
                                                 'bg-rose-500/20 text-rose-400': lastResult.result_type === 'Ineligible' || lastResult.result_type === 'Wrong event' || lastResult.result_type === 'Invalid token'
                                             }">
                                            <i data-lucide="check" class="w-6 h-6" x-show="lastResult.result_type === 'Accepted'"></i>
                                            <i data-lucide="alert-triangle" class="w-6 h-6" x-show="lastResult.result_type === 'Duplicate' || lastResult.result_type === 'Expired'"></i>
                                            <i data-lucide="x" class="w-6 h-6" x-show="lastResult.result_type === 'Ineligible' || lastResult.result_type === 'Wrong event' || lastResult.result_type === 'Invalid token'"></i>
                                        </div>
                                        <div class="overflow-hidden">
                                            <h4 class="text-sm font-bold text-white truncate font-brand-display" x-text="lastResult.member ? lastResult.member.name : 'Unknown Member'"></h4>
                                            <p class="text-xs text-[#94A3B8] font-mono" x-text="lastResult.member && lastResult.member.student_number ? 'ID: ' + lastResult.member.student_number : ''"></p>
                                        </div>
                                    </div>

                                    <div class="p-3 bg-[#0B1120] rounded-xl text-xs space-y-1 border border-[#334155]">
                                        <p class="text-slate-200" x-text="lastResult.message"></p>
                                        <template x-if="lastResult.exp_awarded > 0">
                                            <p class="text-amber-400 font-bold flex items-center gap-1 mt-1">
                                                <i data-lucide="award" class="w-3.5 h-3.5"></i>
                                                <span x-text="'+' + lastResult.exp_awarded + ' EXP awarded to member!'"></span>
                                            </p>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="bg-[#172033] border border-[#334155] rounded-3xl p-5 shadow-xl">
                            <h4 class="text-xs font-bold text-[#94A3B8] uppercase tracking-wider mb-3 flex items-center justify-between font-brand-display">
                                <span>Recent Scans in This Session</span>
                                <span class="text-[10px] text-slate-500 tabular-nums" x-text="scanLog.length + ' scans'"></span>
                            </h4>

                            <div class="space-y-2 max-h-56 overflow-y-auto scrollbar-none">
                                <template x-for="(entry, index) in scanLog" :key="index">
                                    <div class="flex items-center justify-between p-2 rounded-xl bg-[#0B1120] text-xs border border-[#334155]/60">
                                        <div class="flex items-center gap-2 overflow-hidden">
                                            <span class="w-2 h-2 rounded-full"
                                                  :class="entry.type === 'Accepted' ? 'bg-emerald-400' : 'bg-rose-400'"></span>
                                            <span class="font-medium text-white truncate font-brand-display" x-text="entry.name"></span>
                                        </div>
                                        <span class="text-[10px] text-[#94A3B8] font-mono" x-text="entry.time"></span>
                                    </div>
                                </template>

                                <template x-if="scanLog.length === 0">
                                    <p class="text-[11px] text-[#94A3B8] text-center py-4">No scans logged yet in this session.</p>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

    <script>
        function organizerScanner(config) {
            return {
                scanUrl: config.scanUrl,
                csrfToken: config.csrfToken,
                eventId: config.eventId,
                sessionId: config.sessionId,
                cameraActive: false,
                html5QrScanner: null,
                processing: false,
                lastResult: null,
                scanLog: [],
                manualTokenInput: '',

                initScanner() {
                    if (window.lucideIcons) window.createIcons({ icons: window.lucideIcons });
                    this.startCamera();
                },

                async submitManualToken() {
                    if (!this.manualTokenInput || this.manualTokenInput.trim().length === 0) return;
                    const token = this.manualTokenInput.trim();
                    await this.onScanSuccess(token);
                    this.manualTokenInput = '';
                },

                async startCamera() {
                    if (!this.eventId || !this.sessionId) return;
                    try {
                        this.html5QrScanner = new Html5Qrcode("scanner-reader");
                        const config = {
                            fps: 10,
                            qrbox: { width: 250, height: 250 },
                            aspectRatio: 1.0
                        };

                        await this.html5QrScanner.start(
                            { facingMode: "environment" },
                            config,
                            (decodedText) => this.onScanSuccess(decodedText),
                            (errorMessage) => {}
                        );

                        this.cameraActive = true;
                    } catch (err) {
                        console.warn("Unable to start primary camera, falling back:", err);
                        try {
                            const cameras = await Html5Qrcode.getCameras();
                            if (cameras && cameras.length) {
                                await this.html5QrScanner.start(
                                    cameras[0].id,
                                    { fps: 10, qrbox: { width: 250, height: 250 } },
                                    (decodedText) => this.onScanSuccess(decodedText)
                                );
                                this.cameraActive = true;
                            }
                        } catch (subErr) {
                            console.error("Camera access failed:", subErr);
                        }
                    }
                },

                async stopCamera() {
                    if (this.html5QrScanner && this.cameraActive) {
                        await this.html5QrScanner.stop();
                        this.cameraActive = false;
                    }
                },

                async toggleCamera() {
                    if (this.cameraActive) {
                        await this.stopCamera();
                    } else {
                        await this.startCamera();
                    }
                },

                async onScanSuccess(token) {
                    if (this.processing) return;
                    this.processing = true;

                    try {
                        const response = await fetch(this.scanUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken
                            },
                            body: JSON.stringify({
                                token: token,
                                event_id: this.eventId,
                                session_id: this.sessionId
                            })
                        });

                        const data = await response.json();
                        this.lastResult = data;

                        this.scanLog.unshift({
                            name: data.member ? data.member.name : 'Unknown Scan',
                            type: data.result_type,
                            time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' })
                        });

                        if (this.scanLog.length > 20) this.scanLog.pop();

                        this.triggerHaptic(data.result_type === 'Accepted');
                    } catch (error) {
                        console.error('Scan submission error:', error);
                        this.lastResult = {
                            result_type: 'Network Error',
                            message: 'Network transmission failure. Check internet connection.'
                        };
                    } finally {
                        if (window.lucideIcons) window.createIcons({ icons: window.lucideIcons });
                        setTimeout(() => {
                            this.processing = false;
                        }, 1200);
                    }
                },

                triggerHaptic(success) {
                    if (navigator.vibrate) {
                        if (success) {
                            navigator.vibrate(100);
                        } else {
                            navigator.vibrate([100, 50, 100]);
                        }
                    }
                }
            };
        }
    </script>
</x-app-layout>
