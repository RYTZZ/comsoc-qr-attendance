<x-app-layout>
    <div class="min-h-[calc(100vh-4rem)] bg-[#0B1120] text-[#F8FAFC] flex flex-col justify-between p-4 sm:p-6 lg:p-8"
         x-data="dynamicQrPass({
             initialPayload: '{{ $initialQrPayload }}',
             initialManualToken: '{{ $initialManualToken ?? '' }}',
             initialFormattedManualToken: '{{ $initialFormattedManualToken ?? '' }}',
             initialExpiresAt: '{{ $initialExpiresAt }}',
             ttl: {{ $ttl }},
             refreshUrl: '{{ route('member.dynamic_qr.refresh') }}',
             eventId: '{{ $event?->id ?? '' }}',
             passState: '{{ $passState ?? 'active' }}'
         })"
         x-init="initPass()">

        <div class="max-w-md mx-auto w-full flex items-center justify-between pb-4 border-b border-[#334155]">
            <div class="flex items-center gap-3">
                <x-user-avatar :user="$user" size="lg" class="shadow-lg shadow-[#560E0F]/30 border border-[#8F3033]/60" />
                <div>
                    <h1 class="text-base font-extrabold text-white leading-tight flex items-center gap-2 font-brand-display">
                        <span>{{ $user->display_name }}</span>
                        @if($user->is_eligible)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30">
                                Verified Member
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/15 text-rose-400 border border-rose-500/30">
                                Ineligible
                            </span>
                        @endif
                    </h1>
                    <p class="text-xs text-[#94A3B8] font-mono">
                        {{ $user->member_id ? "Member ID: {$user->member_id}" : (auth()->user()?->isSuperAdmin() ? "Student ID: " . ($user->student_number ?? 'N/A') : "Account: {$user->email}") }}
                    </p>
                </div>
            </div>

            <a href="{{ route('dashboard') }}" class="p-2.5 rounded-xl bg-[#172033] hover:bg-[#1E293B] text-[#94A3B8] hover:text-white transition border border-[#334155]" title="Close QR View">
                <i data-lucide="x" class="w-5 h-5"></i>
            </a>
        </div>

        <div class="max-w-md mx-auto w-full my-auto py-6">
            <div class="relative bg-[#172033] border border-[#334155] rounded-3xl p-6 sm:p-8 shadow-2xl flex flex-col items-center text-center overflow-hidden">
                <div class="absolute -top-24 -left-24 w-48 h-48 bg-[#560E0F]/30 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute -bottom-24 -right-24 w-48 h-48 bg-[#8F3033]/25 rounded-full blur-3xl pointer-events-none"></div>

                @if(isset($approvedRegistrations) && $approvedRegistrations->count() > 1)
                    <!-- Multi-Event Selector Dropdown -->
                    <div class="mb-4 w-full text-left" x-data="{ open: false }">
                        <label class="block text-[10px] uppercase font-bold text-slate-400 tracking-wider mb-1 font-mono">
                            Select Joined Event Pass ({{ $approvedRegistrations->count() }} Available)
                        </label>
                        <div class="relative">
                            <select onchange="window.location.href = '{{ route('member.dynamic_qr') }}?event_id=' + this.value"
                                    class="input-dark text-xs w-full py-2 px-3 bg-[#0B1120] border border-[#334155] rounded-xl text-white font-semibold focus:border-[#8F3033]">
                                @foreach($approvedRegistrations as $appReg)
                                    <option value="{{ $appReg->event->id }}" {{ $event && $event->id === $appReg->event->id ? 'selected' : '' }}>
                                        {{ $appReg->event->name }} ({{ $appReg->event->formatted_schedule_day ?? 'Scheduled' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @elseif(isset($event))
                    <div class="mb-3 px-3 py-1.5 rounded-xl bg-slate-900/80 border border-slate-800 text-center w-full">
                        <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider block font-mono">Event Attendance Pass</span>
                        <h2 class="text-sm font-bold text-white font-brand-display truncate">{{ $event->name }}</h2>
                    </div>
                @endif

                <div class="mb-4">
                    <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-bold text-white shadow-sm border border-[#8F3033]/50 bg-burgundy-deep">
                        <span class="w-2 h-2 rounded-full bg-[#df747a] animate-ping"></span>
                        {{ isset($event) ? 'Event Dynamic Attendance Pass' : 'Dynamic Attendance Pass' }}
                    </span>
                </div>

                @if(($passState ?? 'no_events') === 'no_events' || ($passState ?? 'no_events') === 'not_joined')
                    <!-- Case A: Student has not joined an event -->
                    <div class="w-full p-6 sm:p-8 bg-slate-900/90 rounded-2xl border border-slate-800 space-y-4 my-2">
                        <div class="w-16 h-16 mx-auto rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400 shadow-inner">
                            <i data-lucide="ticket" class="w-8 h-8"></i>
                        </div>
                        <div class="space-y-2">
                            <h3 class="text-base font-bold text-white font-brand-display">No Event Attendance QR Available</h3>
                            <p class="text-xs text-slate-300 leading-relaxed font-medium">
                                You haven't joined any eligible events or activities yet. Join an event through Events & Activities to access its Dynamic Attendance QR Code.
                            </p>
                            <p class="text-[11px] text-[#94A3B8] leading-relaxed">
                                Each event requires its own authorized pass. Once your registration is confirmed, your 30-second rolling QR pass will be displayed here.
                            </p>
                        </div>
                        <div class="pt-2">
                            <a href="{{ route('events.index') }}" class="btn-primary w-full text-xs py-2.5 flex items-center justify-center gap-2 shadow-lg shadow-[#560E0F]/30 font-bold">
                                <i data-lucide="calendar" class="w-4 h-4"></i>
                                <span>Browse Events & Activities</span>
                            </a>
                        </div>
                    </div>
                @elseif(($passState ?? 'no_events') === 'pending')
                    <!-- Case A pending approval: Registration pending review -->
                    <div class="w-full p-6 sm:p-8 bg-slate-900/90 rounded-2xl border border-slate-800 space-y-4 my-2">
                        <div class="w-16 h-16 mx-auto rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400 shadow-inner">
                            <i data-lucide="clock" class="w-8 h-8"></i>
                        </div>
                        <div class="space-y-2">
                            <h3 class="text-base font-bold text-white font-brand-display">Registration Pending Approval</h3>
                            <p class="text-xs text-slate-300 leading-relaxed font-medium">
                                Your registration for <strong>{{ $event->name ?? 'the event' }}</strong> is currently awaiting coordinator review.
                            </p>
                            <p class="text-[11px] text-[#94A3B8] leading-relaxed">
                                Your Dynamic Attendance QR code will automatically activate once your registration has been approved.
                            </p>
                        </div>
                        <div class="pt-2 flex flex-col gap-2">
                            @if(isset($eventRegistration))
                                <a href="{{ route('tickets.show', $eventRegistration) }}" class="btn-primary w-full text-xs py-2.5 flex items-center justify-center gap-1.5 font-bold">
                                    <i data-lucide="ticket" class="w-4 h-4"></i>
                                    <span>View Registration Pass</span>
                                </a>
                            @endif
                            <a href="{{ route('events.index') }}" class="btn-secondary w-full text-xs py-2 flex items-center justify-center gap-1.5">
                                <span>Browse Other Events</span>
                            </a>
                        </div>
                    </div>
                @elseif(($passState ?? 'no_events') === 'revoked')
                    <!-- Case C: Cancelled / Revoked / Ineligible -->
                    <div class="w-full p-6 sm:p-8 bg-slate-900/90 rounded-2xl border border-slate-800 space-y-4 my-2">
                        <div class="w-16 h-16 mx-auto rounded-2xl bg-rose-500/10 border border-rose-500/30 flex items-center justify-center text-rose-400 shadow-inner">
                            <i data-lucide="shield-alert" class="w-8 h-8"></i>
                        </div>
                        <div class="space-y-2">
                            <h3 class="text-base font-bold text-rose-400 font-brand-display">Event Pass Inactive</h3>
                            <p class="text-xs text-slate-300 leading-relaxed font-medium">
                                Your registration for this event has been cancelled or rejected, or your member account is not in good standing.
                            </p>
                            <p class="text-[11px] text-[#94A3B8] leading-relaxed">
                                An active QR pass cannot be issued. Please contact the event organizers or ComSoc officers if you require assistance.
                            </p>
                        </div>
                        <div class="pt-2">
                            <a href="{{ route('events.index') }}" class="btn-secondary w-full text-xs py-2.5 flex items-center justify-center gap-1.5">
                                <span>Return to Events & Activities</span>
                            </a>
                @elseif(($passState ?? 'no_events') === 'ended')
                    <!-- Case D: Event Has Ended -->
                    <div class="w-full p-6 sm:p-8 bg-slate-900/90 rounded-2xl border border-slate-800 space-y-4 my-2">
                        <div class="w-16 h-16 mx-auto rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400 shadow-inner">
                            <i data-lucide="calendar-x" class="w-8 h-8"></i>
                        </div>
                        <div class="space-y-2">
                            <h3 class="text-base font-bold text-amber-400 font-brand-display">Event Ended</h3>
                            <p class="text-xs text-slate-300 leading-relaxed font-medium">
                                This event has ended. Your attendance QR Code is no longer available.
                            </p>
                            <p class="text-[11px] text-[#94A3B8] leading-relaxed">
                                Attendance check-in for this event is closed. You can view your attendance records or check other upcoming events.
                            </p>
                        </div>
                        <div class="pt-2 flex flex-col gap-2">
                            @if(isset($eventRegistration))
                                <a href="{{ route('tickets.show', $eventRegistration) }}" class="btn-secondary w-full text-xs py-2.5 flex items-center justify-center gap-1.5 font-semibold">
                                    <i data-lucide="ticket" class="w-4 h-4"></i>
                                    <span>View Event Pass</span>
                                </a>
                            @endif
                            <a href="{{ route('events.index') }}" class="btn-primary w-full text-xs py-2 flex items-center justify-center gap-1.5">
                                <span>Browse Events & Activities</span>
                            </a>
                        </div>
                    </div>
                @else
                    <!-- Case B: Joined & Eligible - Active 30s Dynamic Rolling QR -->
                    <!-- Outer QR frame with perimeter animated countdown timer border -->
                    <div class="relative p-2 sm:p-2.5 rounded-3xl flex items-center justify-center transition-all duration-300">
                        <!-- SVG Countdown Border Overlay -->
                        <svg class="absolute inset-0 w-full h-full pointer-events-none -rotate-90"
                             viewBox="0 0 280 280"
                             preserveAspectRatio="none">
                            <!-- Track Background -->
                            <rect x="5" y="5" width="270" height="270" rx="22" ry="22"
                                  fill="none"
                                  stroke="#1E293B"
                                  stroke-width="5" />
                            <!-- Animated Perimeter Countdown Indicator -->
                            <rect x="5" y="5" width="270" height="270" rx="22" ry="22"
                                  fill="none"
                                  :stroke="timeLeft <= 5 ? '#ef4444' : (timeLeft <= 10 ? '#f59e0b' : '#8F3033')"
                                  stroke-width="5"
                                  stroke-linecap="round"
                                  pathLength="1000"
                                  stroke-dasharray="1000"
                                  :stroke-dashoffset="1000 - (progressPercent * 10)"
                                  class="transition-all duration-300 ease-linear drop-shadow-[0_0_8px_rgba(143,48,51,0.5)]" />
                        </svg>

                        <!-- QR Inner White Box with Quiet Zone -->
                        <div class="relative p-4 sm:p-5 bg-white rounded-2xl shadow-2xl flex items-center justify-center border border-slate-200">
                            <div id="qrcode-canvas" class="w-[210px] h-[210px] sm:w-[230px] sm:h-[230px] flex items-center justify-center overflow-hidden"></div>

                            <!-- Refreshing Loading State -->
                            <div x-show="refreshing" class="absolute inset-0 bg-white/95 backdrop-blur-xs rounded-2xl flex flex-col items-center justify-center transition-opacity z-10" style="display: none;">
                                <i data-lucide="refresh-cw" class="w-8 h-8 text-[#560E0F] animate-spin mb-2"></i>
                                <span class="text-xs font-bold text-slate-900 font-brand-display">Refreshing Pass...</span>
                            </div>

                            <!-- Render / Connection Error State -->
                            <div x-show="errorMessage && !refreshing" class="absolute inset-0 bg-white rounded-2xl flex flex-col items-center justify-center p-4 text-center z-10" style="display: none;">
                                <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center mb-2 border border-rose-200">
                                    <i data-lucide="alert-circle" class="w-5 h-5"></i>
                                </div>
                                <span class="text-xs font-bold text-slate-900 font-brand-display">Failed to load QR</span>
                                <p class="text-[10px] text-slate-500 mt-1 max-w-[170px]" x-text="errorMessage"></p>
                                <button type="button" @click="fetchNewToken()" class="mt-3 px-3 py-1 rounded-lg bg-[#560E0F] text-white text-[11px] font-bold hover:bg-[#8F3033] transition shadow">
                                    Retry
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="w-full mt-6 space-y-3">
                        <div class="flex items-center justify-between text-xs px-1">
                            <span class="text-[#94A3B8] flex items-center gap-1.5 font-medium">
                                <i data-lucide="timer" class="w-4 h-4 text-[#df747a]"></i>
                                <span>QR refreshes in</span>
                            </span>
                            <span class="font-mono font-black text-sm tabular-nums"
                                  :class="timeLeft <= 5 ? 'text-rose-400 animate-pulse' : (timeLeft <= 10 ? 'text-amber-400' : 'text-[#df747a]')"
                                  x-text="formattedTimeLeft">00:30</span>
                        </div>

                        <!-- Secondary Horizontal Bar -->
                        <div class="w-full h-2 bg-[#0B1120] rounded-full overflow-hidden p-0.5 border border-[#334155]">
                            <div class="h-full rounded-full transition-all duration-300 ease-linear"
                                 :class="timeLeft <= 5 ? 'bg-rose-500' : (timeLeft <= 10 ? 'bg-amber-500' : 'bg-burgundy-gradient')"
                                 :style="`width: ${progressPercent}%`"></div>
                        </div>

                        <div class="pt-2 flex items-center justify-between text-[11px] text-[#94A3B8]">
                            <span class="flex items-center gap-1">
                                <i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-400"></i>
                                <span>Anti-Screenshot Verified</span>
                            </span>
                            <button type="button" @click="fetchNewToken()" :disabled="refreshing" class="text-[#df747a] hover:text-white underline underline-offset-2 transition disabled:opacity-50 flex items-center gap-1 font-semibold">
                                <i data-lucide="refresh-cw" class="w-3 h-3" :class="refreshing ? 'animate-spin' : ''"></i>
                                <span>Force Refresh</span>
                            </button>
                        </div>

                        <!-- Manual Attendance Token Section -->
                        <div class="mt-4 pt-4 border-t border-[#334155]/80 w-full text-left">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-[#94A3B8] font-brand-display flex items-center gap-1.5">
                                    <i data-lucide="key" class="w-3.5 h-3.5 text-[#df747a]"></i>
                                    <span>Manual Attendance Token</span>
                                </span>
                                <span class="text-[10px] text-slate-500 font-mono" x-text="formattedTimeLeft + ' left'"></span>
                            </div>

                            <div class="flex items-center gap-2 bg-[#0B1120] border-2 border-[#334155] hover:border-[#8F3033]/70 rounded-2xl p-2 sm:p-2.5 transition">
                                <div class="flex-1 font-mono font-black text-center text-lg sm:text-xl tracking-widest text-[#F8FAFC] select-all tabular-nums"
                                     x-text="displayManualToken">
                                    {{ $initialFormattedManualToken ?? '------' }}
                                </div>

                                <button type="button"
                                        @click="copyToken()"
                                        class="shrink-0 px-3 py-1.5 rounded-xl text-xs font-semibold bg-[#172033] hover:bg-[#1E293B] text-slate-200 hover:text-white border border-[#334155] transition flex items-center gap-1 shadow-sm active:scale-95"
                                        :title="copied ? 'Copied to clipboard' : 'Copy token'">
                                    <template x-if="!copied">
                                        <span class="flex items-center gap-1">
                                            <i data-lucide="copy" class="w-3.5 h-3.5 text-[#df747a]"></i>
                                            <span class="hidden sm:inline">Copy</span>
                                        </span>
                                    </template>
                                    <template x-if="copied">
                                        <span class="flex items-center gap-1 text-emerald-400 font-bold">
                                            <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                            <span>Copied!</span>
                                        </span>
                                    </template>
                                </button>
                            </div>

                            <p class="text-[11px] text-[#94A3B8] mt-2 text-center leading-relaxed">
                                Can't scan the QR code? Enter this token in the manual attendance lookup.
                            </p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="max-w-md mx-auto w-full text-center text-xs text-[#94A3B8] space-y-2 pb-6">
            <p class="leading-relaxed">
                Present this screen to the designated <span class="text-[#F8FAFC] font-bold">COMSOC Event Organizer</span> scanner. QR payload refreshes securely every 30 seconds.
            </p>
            <p class="text-[11px] text-slate-500 font-medium">
                Computing Society Organization • Sorsogon State University – Bulan Campus
            </p>
        </div>
    </div>

    @push('scripts')
    <script>
        function dynamicQrPass(config) {
            return {
                payload: config.initialPayload || '',
                manualToken: config.initialManualToken || '',
                formattedManualToken: config.initialFormattedManualToken || '',
                copied: false,
                expiresAt: config.initialExpiresAt ? new Date(config.initialExpiresAt).getTime() : Date.now() + 30000,
                ttl: config.ttl || 30,
                timeLeft: 30,
                progressPercent: 100,
                refreshUrl: config.refreshUrl,
                refreshing: false,
                errorMessage: '',
                timerInterval: null,
                qrInstance: null,

                get displayManualToken() {
                    return this.formattedManualToken || this.manualToken || '------';
                },

                get formattedTimeLeft() {
                    const s = Math.max(0, Math.floor(this.timeLeft));
                    const mins = Math.floor(s / 60);
                    const secs = s % 60;
                    return String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
                },

                async copyToken() {
                    const tokenToCopy = this.formattedManualToken || this.manualToken;
                    if (!tokenToCopy || tokenToCopy === '------') return;

                    try {
                        if (navigator.clipboard && window.isSecureContext) {
                            await navigator.clipboard.writeText(tokenToCopy);
                        } else {
                            const textArea = document.createElement('textarea');
                            textArea.value = tokenToCopy;
                            textArea.style.position = 'fixed';
                            textArea.style.left = '-999999px';
                            document.body.appendChild(textArea);
                            textArea.focus();
                            textArea.select();
                            document.execCommand('copy');
                            textArea.remove();
                        }
                        this.copied = true;
                        setTimeout(() => {
                            this.copied = false;
                        }, 2000);
                    } catch (e) {
                        console.error('Copy token failed:', e);
                    }
                },

                eventId: config.eventId || '',
                passState: config.passState || 'active',

                initPass() {
                    if (this.passState !== 'active') {
                        return;
                    }
                    this.waitForQrLibAndRender();
                    this.syncTiming();
                    this.startCountdown();

                    document.addEventListener('visibilitychange', () => {
                        if (document.visibilityState === 'visible') {
                            this.syncTiming();
                            if (this.timeLeft <= 0) {
                                this.fetchNewToken();
                            }
                        }
                    });
                },

                waitForQrLibAndRender(retries = 20) {
                    if (typeof QRCode !== 'undefined') {
                        this.renderQr(this.payload);
                    } else if (retries > 0) {
                        setTimeout(() => this.waitForQrLibAndRender(retries - 1), 100);
                    } else {
                        this.errorMessage = 'QR generator library failed to load.';
                    }
                },

                renderQr(text) {
                    if (!text) {
                        this.errorMessage = 'No valid token payload received.';
                        return;
                    }
                    const container = document.getElementById('qrcode-canvas');
                    if (!container) return;
                    container.innerHTML = '';
                    this.errorMessage = '';

                    try {
                        this.qrInstance = new QRCode(container, {
                            text: text,
                            width: 220,
                            height: 220,
                            colorDark: "#0B1120",
                            colorLight: "#FFFFFF",
                            correctLevel: QRCode.CorrectLevel.M
                        });
                    } catch (err) {
                        console.error('QR Render Exception:', err);
                        this.errorMessage = 'Failed to generate QR code canvas.';
                    }
                },

                syncTiming() {
                    const now = Date.now();
                    const diffMs = this.expiresAt - now;
                    const diffSeconds = Math.max(0, diffMs / 1000);
                    this.timeLeft = Math.ceil(diffSeconds);
                    this.progressPercent = Math.max(0, Math.min(100, (diffSeconds / this.ttl) * 100));
                },

                startCountdown() {
                    if (this.timerInterval) clearInterval(this.timerInterval);
                    this.timerInterval = setInterval(() => {
                        this.syncTiming();
                        if (this.timeLeft <= 0) {
                            this.fetchNewToken();
                        }
                    }, 250);
                },

                async fetchNewToken() {
                    if (this.refreshing) return;
                    this.refreshing = true;
                    this.errorMessage = '';

                    try {
                        const tokenMeta = document.querySelector('meta[name="csrf-token"]');
                        const response = await fetch(this.refreshUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': tokenMeta ? tokenMeta.getAttribute('content') : ''
                            },
                            body: JSON.stringify({
                                event_id: this.eventId || null
                            })
                        });

                        if (!response.ok) {
                            throw new Error('Server responded with ' + response.status);
                        }

                        const data = await response.json();
                        if (!data.payload) {
                            throw new Error(data.message || 'Invalid refresh response');
                        }

                        this.payload = data.payload;
                        if (data.manual_token) {
                            this.manualToken = data.manual_token;
                        }
                        if (data.formatted_manual_token) {
                            this.formattedManualToken = data.formatted_manual_token;
                        }
                        this.expiresAt = new Date(data.expires_at).getTime();
                        this.ttl = data.ttl || 30;
                        this.renderQr(this.payload);
                        this.syncTiming();
                    } catch (error) {
                        console.error('QR Refresh Error:', error);
                        this.errorMessage = 'Connection interrupted. Tap retry.';
                    } finally {
                        this.refreshing = false;
                        if (window.lucideIcons && window.createIcons) {
                            window.createIcons({ icons: window.lucideIcons });
                        }
                    }
                }
            };
        }
    </script>
    @endpush
</x-app-layout>
