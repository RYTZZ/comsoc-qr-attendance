<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <a href="{{ route('tickets.index') }}" class="text-xs text-[#dfa6a9] hover:underline flex items-center gap-1 mb-1">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                    <span>Back to My Tickets</span>
                </a>
                <h1 class="text-2xl font-bold text-white font-brand-display tracking-tight">
                    Event Access Pass
                </h1>
                <p class="text-xs text-slate-400 mt-0.5 font-mono">
                    Ticket Ref: {{ $ticket->ticket_code }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('events.show', $event) }}" class="btn-secondary text-xs flex items-center gap-1.5">
                    <i data-lucide="calendar" class="w-4 h-4"></i>
                    <span>Event Details</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-[#0B1120]">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="card bg-gradient-to-br from-[#172033] via-[#0B1120] to-[#172033] border border-[#8F3033]/60 shadow-2xl rounded-3xl overflow-hidden relative">
                <div class="absolute -top-12 -right-12 w-48 h-48 bg-[#560E0F]/20 rounded-full blur-3xl pointer-events-none"></div>

                <div class="p-6 sm:p-8 border-b border-[#334155] bg-[#0B1120]/40 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-[#560E0F] text-white border border-[#8F3033]">
                                {{ $event->event_type ?: 'Official Activity' }}
                            </span>
                            @if($ticket->status === 'approved')
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                    <span>Active Event Pass</span>
                                </span>
                            @elseif($ticket->status === 'pending')
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-400 border border-amber-500/30">
                                    Pending Approval
                                </span>
                            @elseif($ticket->status === 'rejected' || $ticket->status === 'cancelled')
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-400 border border-rose-500/30">
                                    Pass Revoked / Cancelled
                                </span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-500/20 text-slate-400 border border-slate-500/30">
                                    {{ ucfirst($ticket->status) }}
                                </span>
                            @endif
                        </div>
                        <h2 class="text-xl sm:text-2xl font-extrabold text-white font-brand-display tracking-tight">
                            {{ $event->name }}
                        </h2>
                        <p class="text-xs text-[#94A3B8] mt-1 flex items-center gap-2">
                            <span>{{ $event->formatted_schedule_day }}</span>
                            <span>•</span>
                            <span>{{ $event->effective_venue }}</span>
                        </p>
                    </div>

                    <div class="text-right sm:border-l sm:border-[#334155] sm:pl-6">
                        <span class="block text-[10px] text-[#94A3B8] uppercase tracking-wider font-mono">Ticket Identifier</span>
                        <span class="text-base font-black text-amber-400 font-mono tracking-wider">{{ $ticket->ticket_code }}</span>
                    </div>
                </div>

                <div class="p-6 sm:p-8 grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
                    @if($dynamicPass && $ticket->status === 'approved')
                        <!-- Event-Specific Dynamic QR Pass -->
                        <div class="flex flex-col items-center text-center p-6 bg-slate-900/90 rounded-2xl border border-slate-800 select-none"
                             x-data="eventDynamicQrPass({
                                 initialPayload: '{{ $dynamicPass['payload'] }}',
                                 initialManualToken: '{{ $dynamicPass['manual_token'] }}',
                                 initialFormattedManualToken: '{{ $dynamicPass['formatted_manual_token'] }}',
                                 initialExpiresAt: '{{ $dynamicPass['expires_at'] }}',
                                 ttl: {{ $dynamicPass['ttl'] }},
                                 refreshUrl: '{{ route('tickets.refresh_qr', $ticket) }}'
                             })"
                             x-init="initPass()"
                             oncontextmenu="return false;">
                            
                            <div class="mb-3">
                                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[11px] font-bold text-white shadow-sm border border-[#8F3033]/50 bg-burgundy-deep">
                                    <span class="w-2 h-2 rounded-full bg-[#df747a] animate-ping"></span>
                                    <span>Event Dynamic Attendance Pass</span>
                                </span>
                            </div>

                            <!-- Outer QR container with perimeter animated countdown ring -->
                            <div class="relative p-2 rounded-2xl flex items-center justify-center transition-all duration-300">
                                <svg class="absolute inset-0 w-full h-full pointer-events-none -rotate-90"
                                     viewBox="0 0 240 240"
                                     preserveAspectRatio="none">
                                    <rect x="4" y="4" width="232" height="232" rx="18" ry="18"
                                          fill="none"
                                          stroke="#1E293B"
                                          stroke-width="4" />
                                    <rect x="4" y="4" width="232" height="232" rx="18" ry="18"
                                          fill="none"
                                          :stroke="timeLeft <= 5 ? '#ef4444' : (timeLeft <= 10 ? '#f59e0b' : '#8F3033')"
                                          stroke-width="4"
                                          stroke-linecap="round"
                                          pathLength="1000"
                                          stroke-dasharray="1000"
                                          :stroke-dashoffset="1000 - (progressPercent * 10)"
                                          class="transition-all duration-300 ease-linear drop-shadow-[0_0_8px_rgba(143,48,51,0.5)]" />
                                </svg>

                                <!-- QR Inner Canvas Container -->
                                <div class="relative p-3.5 bg-white rounded-xl shadow-2xl flex items-center justify-center border border-slate-200 select-none"
                                     style="-webkit-touch-callout: none;">
                                    <div id="event-qrcode-canvas" class="w-[190px] h-[190px] flex items-center justify-center overflow-hidden pointer-events-none"></div>

                                    <!-- Refreshing Overlay -->
                                    <div x-show="refreshing" class="absolute inset-0 bg-white/95 backdrop-blur-xs rounded-xl flex flex-col items-center justify-center z-10" style="display: none;">
                                        <i data-lucide="refresh-cw" class="w-7 h-7 text-[#560E0F] animate-spin mb-1.5"></i>
                                        <span class="text-[11px] font-bold text-slate-900 font-brand-display">Refreshing Pass...</span>
                                    </div>

                                    <!-- Error Overlay -->
                                    <div x-show="errorMessage && !refreshing" class="absolute inset-0 bg-white rounded-xl flex flex-col items-center justify-center p-3 text-center z-10" style="display: none;">
                                        <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center mb-1.5 border border-rose-200">
                                            <i data-lucide="alert-circle" class="w-4 h-4"></i>
                                        </div>
                                        <span class="text-[11px] font-bold text-slate-900 font-brand-display">Failed to load QR</span>
                                        <p class="text-[10px] text-slate-500 mt-0.5 max-w-[150px]" x-text="errorMessage"></p>
                                        <button type="button" @click="fetchNewToken()" class="mt-2 px-2.5 py-1 rounded bg-[#560E0F] text-white text-[10px] font-bold hover:bg-[#8F3033] transition shadow">
                                            Retry
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Countdown & Refresh Controls -->
                            <div class="w-full mt-4 space-y-2">
                                <div class="flex items-center justify-between text-xs px-1">
                                    <span class="text-[#94A3B8] flex items-center gap-1.5 font-medium text-[11px]">
                                        <i data-lucide="timer" class="w-3.5 h-3.5 text-[#df747a]"></i>
                                        <span>Pass refreshes in</span>
                                    </span>
                                    <span class="font-mono font-black text-xs tabular-nums"
                                          :class="timeLeft <= 5 ? 'text-rose-400 animate-pulse' : (timeLeft <= 10 ? 'text-amber-400' : 'text-[#df747a]')"
                                          x-text="formattedTimeLeft">00:30</span>
                                </div>

                                <div class="w-full h-1.5 bg-[#0B1120] rounded-full overflow-hidden p-0.5 border border-[#334155]">
                                    <div class="h-full rounded-full transition-all duration-300 ease-linear"
                                         :class="timeLeft <= 5 ? 'bg-rose-500' : (timeLeft <= 10 ? 'bg-amber-500' : 'bg-burgundy-gradient')"
                                         :style="`width: ${progressPercent}%`"></div>
                                </div>

                                <div class="pt-1 flex items-center justify-between text-[10px] text-[#94A3B8]">
                                    <span class="flex items-center gap-1">
                                        <i data-lucide="shield-check" class="w-3 h-3 text-emerald-400"></i>
                                        <span>Anti-Replay Protected</span>
                                    </span>
                                    <button type="button" @click="fetchNewToken()" :disabled="refreshing" class="text-[#df747a] hover:text-white underline underline-offset-2 transition disabled:opacity-50 flex items-center gap-1 font-semibold">
                                        <i data-lucide="refresh-cw" class="w-2.5 h-2.5" :class="refreshing ? 'animate-spin' : ''"></i>
                                        <span>Refresh Now</span>
                                    </button>
                                </div>

                                <!-- Manual Attendance Token -->
                                <div class="mt-3 pt-3 border-t border-[#334155]/80 w-full text-left">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#94A3B8] font-brand-display flex items-center gap-1">
                                            <i data-lucide="key" class="w-3 h-3 text-[#df747a]"></i>
                                            <span>Manual Token</span>
                                        </span>
                                        <span class="text-[10px] text-slate-500 font-mono" x-text="formattedTimeLeft + ' left'"></span>
                                    </div>

                                    <div class="flex items-center gap-2 bg-[#0B1120] border border-[#334155] hover:border-[#8F3033]/70 rounded-xl p-2 transition">
                                        <div class="flex-1 font-mono font-black text-center text-base tracking-widest text-[#F8FAFC] select-all tabular-nums"
                                             x-text="displayManualToken">
                                            {{ $dynamicPass['formatted_manual_token'] ?? '------' }}
                                        </div>

                                        <button type="button"
                                                @click="copyToken()"
                                                class="shrink-0 px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-[#172033] hover:bg-[#1E293B] text-slate-200 hover:text-white border border-[#334155] transition flex items-center gap-1 shadow-sm active:scale-95"
                                                :title="copied ? 'Copied' : 'Copy token'">
                                            <template x-if="!copied">
                                                <span class="flex items-center gap-1">
                                                    <i data-lucide="copy" class="w-3 h-3 text-[#df747a]"></i>
                                                    <span>Copy</span>
                                                </span>
                                            </template>
                                            <template x-if="copied">
                                                <span class="flex items-center gap-1 text-emerald-400 font-bold">
                                                    <i data-lucide="check" class="w-3 h-3"></i>
                                                    <span>Copied!</span>
                                                </span>
                                            </template>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <!-- Ineligible, Pending, or Revoked State -->
                        <div class="flex flex-col items-center text-center p-8 bg-slate-900/80 rounded-2xl border border-slate-800">
                            @if($ticket->status === 'pending')
                                <div class="w-12 h-12 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400 mb-3">
                                    <i data-lucide="clock" class="w-6 h-6"></i>
                                </div>
                                <h3 class="text-sm font-bold text-white font-brand-display">Registration Pending Review</h3>
                                <p class="text-xs text-slate-400 mt-1 max-w-xs leading-relaxed">
                                    Your event pass will be activated once your registration is approved by the event coordinator.
                                </p>
                            @elseif($ticket->status === 'rejected' || $ticket->status === 'cancelled')
                                <div class="w-12 h-12 rounded-2xl bg-rose-500/10 border border-rose-500/30 flex items-center justify-center text-rose-400 mb-3">
                                    <i data-lucide="slash" class="w-6 h-6"></i>
                                </div>
                                <h3 class="text-sm font-bold text-rose-400 font-brand-display">Event Pass Inactive</h3>
                                <p class="text-xs text-slate-400 mt-1 max-w-xs leading-relaxed">
                                    This event pass has been cancelled or revoked. Please contact the event administrator if you believe this is an error.
                                </p>
                            @else
                                <div class="w-12 h-12 rounded-2xl bg-slate-800 border border-slate-700 flex items-center justify-center text-slate-400 mb-3">
                                    <i data-lucide="shield-alert" class="w-6 h-6"></i>
                                </div>
                                <h3 class="text-sm font-bold text-white font-brand-display">Pass Unavailable</h3>
                                <p class="text-xs text-slate-400 mt-1 max-w-xs leading-relaxed">
                                    Your member account is currently inactive or not in good standing. Dynamic attendance passes require verified member status.
                                </p>
                            @endif
                        </div>
                    @endif

                    <div class="space-y-4 text-xs">
                        <div class="p-4 rounded-xl bg-slate-900/50 border border-slate-800/60 space-y-3">
                            <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider">Attendee Information</h3>
                            <div class="grid grid-cols-2 gap-2 text-slate-400">
                                <div>
                                    <span class="block text-[10px] text-slate-500">Full Name</span>
                                    <span class="font-bold text-white">{{ $ticket->full_name }}</span>
                                </div>
                                <div>
                                    <span class="block text-[10px] text-slate-500">Email Address</span>
                                    <span class="font-bold text-white truncate block">{{ $ticket->email }}</span>
                                </div>
                                <div>
                                    <span class="block text-[10px] text-slate-500">Academic Program</span>
                                    <span class="font-bold text-slate-200 truncate block">{{ $ticket->program ?: 'Computing Society' }}</span>
                                </div>
                                <div>
                                    <span class="block text-[10px] text-slate-500">Year / Block</span>
                                    <span class="font-bold text-slate-200">{{ $ticket->year_level ?: 'Member' }}</span>
                                </div>
                                @if($event->tshirt_enabled && $ticket->tshirt_size)
                                <div>
                                    <span class="block text-[10px] text-slate-500">T-Shirt Size</span>
                                    <span class="font-bold text-slate-200">{{ $ticket->tshirt_size }}</span>
                                </div>
                                @endif
                                @if($event->dietary_enabled && $ticket->food_restrictions)
                                <div>
                                    <span class="block text-[10px] text-slate-500">Dietary Needs</span>
                                    <span class="font-bold text-slate-200">{{ $ticket->food_restrictions }}</span>
                                </div>
                                @endif
                                <div>
                                    <span class="block text-[10px] text-slate-500">Registered On</span>
                                    <span class="font-bold text-slate-200">{{ $ticket->created_at->format('M d, Y') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 rounded-xl bg-slate-900/50 border border-slate-800/60 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400 text-xs">Activity Attendance Rewards</span>
                                <span class="text-xs font-bold text-amber-300">+{{ $event->reward_xp }} XP • +{{ $event->reward_points }} Pts</span>
                            </div>
                            <p class="text-[11px] text-slate-500">
                                Your account balances and season progression update automatically upon verified check-in.
                            </p>
                        </div>

                        <div class="p-4 rounded-xl bg-slate-900/30 border border-slate-800/40 text-[11px] text-slate-400 flex items-start gap-2.5">
                            <i data-lucide="shield-check" class="w-4 h-4 text-[#df747a] shrink-0 mt-0.5"></i>
                            <div>
                                <span class="font-bold text-slate-300 block mb-0.5">Security Notice</span>
                                <span>This pass updates every 30 seconds. Screenshots and static photos expire immediately and cannot be accepted at attendance checkpoints.</span>
                            </div>
                        </div>
                    </div>
                </div>

                @if($attendanceRecords->isNotEmpty())
                <div class="p-6 sm:p-8 border-t border-slate-800/80 bg-slate-900/60 space-y-3">
                    <div class="flex items-center gap-2">
                        <i data-lucide="check-check" class="w-4 h-4 text-emerald-400"></i>
                        <h3 class="text-xs font-bold text-white uppercase tracking-wider">Attendance Verification Log</h3>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach($attendanceRecords as $record)
                        <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-between text-xs">
                            <div>
                                <p class="font-bold text-white">{{ $record->attendanceSession ? $record->attendanceSession->name : 'Session Check-In' }}</p>
                                <p class="text-[10px] text-slate-500">{{ $record->scanned_at ? $record->scanned_at->format('M d, Y • g:i A') : 'Recorded' }}</p>
                            </div>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                {{ ucfirst($record->status) }}
                            </span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

        </div>
    </div>

    @push('scripts')
    <script>
        function eventDynamicQrPass(config) {
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

                initPass() {
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
                        this.errorMessage = 'QR library loading...';
                    }
                },

                renderQr(text) {
                    if (!text) {
                        this.errorMessage = 'No valid token payload received.';
                        return;
                    }
                    const container = document.getElementById('event-qrcode-canvas');
                    if (!container) return;
                    container.innerHTML = '';
                    this.errorMessage = '';

                    try {
                        this.qrInstance = new QRCode(container, {
                            text: text,
                            width: 190,
                            height: 190,
                            colorDark: "#0B1120",
                            colorLight: "#FFFFFF",
                            correctLevel: QRCode.CorrectLevel.M
                        });
                    } catch (err) {
                        console.error('QR Render Exception:', err);
                        this.errorMessage = 'Failed to generate QR canvas.';
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
                            }
                        });

                        if (!response.ok) {
                            const errData = await response.json().catch(() => null);
                            throw new Error(errData?.message || ('Server responded with ' + response.status));
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
                        this.errorMessage = error.message || 'Connection interrupted. Tap retry.';
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
