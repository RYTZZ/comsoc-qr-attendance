<!-- Real-Time Session Takeover & Security Alert Notifier -->
<div x-data="sessionSecurityMonitor()" x-init="initMonitor()">
    <!-- Mandatory Blocking Session Replaced Modal -->
    <div x-show="sessionReplaced" 
         x-cloak
         style="display: none;"
         class="fixed inset-0 z-[99999] bg-[#0B1120]/95 backdrop-blur-md flex items-center justify-center p-4 select-none">
        <div class="card bg-[#172033] border-2 border-[#8F3033] shadow-2xl rounded-2xl max-w-md w-full p-6 text-center animate-slide-up transform transition-all">
            <div class="w-16 h-16 rounded-2xl bg-[#560E0F] border border-[#8F3033] text-red-300 flex items-center justify-center mx-auto mb-4 shadow-lg shadow-[#560E0F]/40">
                <i data-lucide="shield-alert" class="w-8 h-8 text-red-400"></i>
            </div>
            
            <h3 class="text-lg font-bold text-white font-brand-display tracking-tight">
                Another Login Detected
            </h3>
            
            <p class="text-xs sm:text-sm text-slate-300 mt-2 leading-relaxed">
                Your ComSoc Hub account has been logged in on another device. For your security, this session is no longer active. Please log out and sign in again if you want to use this device.
            </p>

            <div class="mt-6 pt-4 border-t border-[#334155]/80">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" 
                            class="w-full py-3 px-4 rounded-xl font-bold text-sm text-white bg-[#560E0F] hover:bg-[#8F3033] border border-[#8F3033] shadow-lg shadow-[#560E0F]/30 hover:shadow-[#8F3033]/50 transition-all flex items-center justify-center gap-2">
                        <i data-lucide="log-out" class="w-4 h-4"></i>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- 60-Minute Inactivity Warning Modal (Shown at 58 minutes of inactivity) -->
    <div x-show="inactivityWarning && !sessionExpired && !sessionReplaced" 
         x-cloak
         style="display: none;"
         class="fixed inset-0 z-[99998] bg-[#0B1120]/90 backdrop-blur-sm flex items-center justify-center p-4 select-none">
        <div class="card bg-[#172033] border-2 border-amber-500/60 shadow-2xl rounded-2xl max-w-md w-full p-6 text-center animate-slide-up transform transition-all">
            <div class="w-14 h-14 rounded-2xl bg-amber-500/20 border border-amber-500/40 text-amber-300 flex items-center justify-center mx-auto mb-4 shadow-lg">
                <i data-lucide="clock" class="w-7 h-7 text-amber-400"></i>
            </div>
            
            <h3 class="text-lg font-bold text-white font-brand-display tracking-tight">
                Session Inactivity Warning
            </h3>
            
            <p class="text-xs sm:text-sm text-slate-300 mt-2 leading-relaxed">
                You have been inactive. For your security, your session will automatically expire in <span class="font-bold text-amber-400 font-mono" x-text="formatSeconds(inactivityCountdown)"></span>. Click below or interact with the screen to stay signed in.
            </p>

            <div class="mt-6 pt-4 border-t border-[#334155]/80 flex gap-3">
                <button type="button" 
                        @click="staySignedIn()"
                        class="flex-1 py-2.5 px-4 rounded-xl font-bold text-xs sm:text-sm text-white bg-emerald-600 hover:bg-emerald-500 transition shadow">
                    Stay Signed In
                </button>
                <form method="POST" action="{{ route('logout') }}" class="flex-1">
                    @csrf
                    <button type="submit" 
                            class="w-full py-2.5 px-4 rounded-xl font-bold text-xs sm:text-sm text-slate-300 bg-slate-800 hover:bg-slate-700 transition">
                        Sign Out
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Mandatory Session Expired Modal (Triggered after 60 minutes) -->
    <div x-show="sessionExpired" 
         x-cloak
         style="display: none;"
         class="fixed inset-0 z-[99999] bg-[#0B1120]/95 backdrop-blur-md flex items-center justify-center p-4 select-none">
        <div class="card bg-[#172033] border-2 border-rose-500/70 shadow-2xl rounded-2xl max-w-md w-full p-6 text-center animate-slide-up transform transition-all">
            <div class="w-16 h-16 rounded-2xl bg-rose-500/20 border border-rose-500/40 text-rose-300 flex items-center justify-center mx-auto mb-4 shadow-lg">
                <i data-lucide="timer-off" class="w-8 h-8 text-rose-400"></i>
            </div>
            
            <h3 class="text-lg font-bold text-white font-brand-display tracking-tight">
                Session Expired
            </h3>
            
            <p class="text-xs sm:text-sm text-slate-300 mt-2 leading-relaxed">
                Your session expired due to inactivity. Please sign in again.
            </p>

            <div class="mt-6 pt-4 border-t border-[#334155]/80">
                <a href="{{ route('login') }}" 
                   class="w-full py-3 px-4 rounded-xl font-bold text-sm text-white bg-[#560E0F] hover:bg-[#8F3033] border border-[#8F3033] shadow-lg flex items-center justify-center gap-2">
                    <i data-lucide="log-in" class="w-4 h-4"></i>
                    <span>Sign In</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Auto-Dismissing Toast Notification for New Device Login Alerts -->
    <div class="fixed top-5 right-5 sm:top-6 sm:right-6 z-50 max-w-sm w-[calc(100vw-2.5rem)] sm:w-full space-y-3 pointer-events-none">
        <template x-for="alert in alerts" :key="alert.id">
            <div x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-[-12px] sm:translate-y-0 sm:translate-x-4 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0 scale-100"
                 x-transition:leave="transition ease-in duration-250"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 translate-y-[-8px] sm:translate-x-4 scale-95"
                 class="pointer-events-auto relative overflow-hidden bg-[#172033]/95 backdrop-blur-md border border-[#8F3033]/70 shadow-2xl rounded-2xl p-4 flex items-start gap-3.5 transition-all">
                
                <!-- Toast Accent Countdown Progress Bar (4.5s) -->
                <div class="absolute bottom-0 left-0 h-1 bg-gradient-to-r from-[#8F3033] to-[#df747a] animate-[toastProgress_4.5s_linear_forwards] w-full origin-left"></div>

                <div class="w-9 h-9 rounded-xl bg-[#560E0F]/80 border border-[#8F3033] text-red-300 flex items-center justify-center shrink-0 shadow-inner">
                    <i data-lucide="shield-alert" class="w-5 h-5 text-red-400"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-2">
                        <h4 class="text-xs font-bold text-white uppercase tracking-wider font-brand-display" x-text="alert.title"></h4>
                        <button type="button" @click="dismissAlert(alert.id)" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 transition" aria-label="Dismiss notification">
                            <i data-lucide="x" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>
                    <p class="text-xs text-slate-300 mt-1 leading-relaxed" x-text="alert.body"></p>
                    <div class="mt-3 flex items-center gap-2">
                        <a :href="alert.target_url || '{{ route('profile.sessions') }}'" 
                           class="btn-primary text-[11px] py-1 px-2.5 bg-[#560E0F] hover:bg-[#8F3033] border-[#8F3033] flex items-center gap-1 font-semibold shadow-sm">
                            <span>Review Sessions</span>
                            <i data-lucide="arrow-right" class="w-3 h-3"></i>
                        </a>
                        <button type="button" @click="dismissAlert(alert.id)" class="text-[11px] text-slate-400 hover:text-slate-200 py-1 px-2">
                            Dismiss
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>

<style>
    @keyframes toastProgress {
        from { transform: scaleX(1); }
        to { transform: scaleX(0); }
    }
</style>

<script>
    function sessionSecurityMonitor() {
        return {
            sessionReplaced: false,
            sessionExpired: false,
            inactivityWarning: false,
            inactivityCountdown: 120, // 2 minutes countdown before 60m expiry
            alerts: [],
            dismissedIds: new Set(),
            timers: new Map(),
            pollInterval: null,
            inactivityCheckInterval: null,
            channel: null,
            INACTIVITY_TIMEOUT_MS: 60 * 60 * 1000, // 60 minutes
            WARNING_THRESHOLD_MS: 58 * 60 * 1000, // 58 minutes (2 min warning)

            initMonitor() {
                // Initialize BroadcastChannel for cross-tab synchronization
                try {
                    if ('BroadcastChannel' in window) {
                        this.channel = new BroadcastChannel('comsoc_session_channel');
                        this.channel.onmessage = (event) => {
                            if (event.data && event.data.type === 'SESSION_REPLACED') {
                                this.triggerSessionReplaced();
                            } else if (event.data && event.data.type === 'SESSION_EXPIRED') {
                                this.triggerSessionExpired();
                            } else if (event.data && event.data.type === 'USER_ACTIVITY') {
                                // Another tab had activity, sync local timestamp
                                const remoteTs = event.data.timestamp;
                                if (remoteTs) {
                                    localStorage.setItem('comsoc_last_activity', remoteTs.toString());
                                    this.inactivityWarning = false;
                                }
                            }
                        };
                    }
                } catch (e) {}

                // Initialize last activity timestamp in localStorage if not set
                if (!localStorage.getItem('comsoc_last_activity')) {
                    localStorage.setItem('comsoc_last_activity', Date.now().toString());
                }

                // Register meaningful user activity listeners
                this.bindActivityListeners();

                // Start checking inactivity every 2 seconds
                this.inactivityCheckInterval = setInterval(() => this.checkInactivity(), 2000);

                // Initial poll immediately, then every 10 seconds for background session validity
                this.poll();
                this.pollInterval = setInterval(() => this.poll(), 10000);

                // Prevent dismissing modal via Esc key
                window.addEventListener('keydown', (e) => {
                    if ((this.sessionReplaced || this.sessionExpired) && (e.key === 'Escape' || e.key === 'Esc')) {
                        e.preventDefault();
                        e.stopPropagation();
                    }
                }, true);
            },

            bindActivityListeners() {
                let throttleTimer = null;
                const recordActivity = () => {
                    if (this.sessionExpired || this.sessionReplaced) return;

                    const now = Date.now();
                    // Throttle writes to localStorage and broadcasts to once every 3 seconds
                    if (!throttleTimer) {
                        localStorage.setItem('comsoc_last_activity', now.toString());
                        if (this.inactivityWarning) {
                            this.inactivityWarning = false;
                        }
                        if (this.channel) {
                            this.channel.postMessage({ type: 'USER_ACTIVITY', timestamp: now });
                        }
                        throttleTimer = setTimeout(() => { throttleTimer = null; }, 3000);
                    }
                };

                // Track meaningful user activity (pointer, keyboard, touch, scroll)
                ['pointerdown', 'keydown', 'touchstart', 'scroll', 'click'].forEach(evt => {
                    window.addEventListener(evt, recordActivity, { passive: true });
                });
            },

            checkInactivity() {
                if (this.sessionExpired || this.sessionReplaced) return;

                const lastActivityStr = localStorage.getItem('comsoc_last_activity');
                const lastActivity = lastActivityStr ? parseInt(lastActivityStr, 10) : Date.now();
                const elapsed = Date.now() - lastActivity;

                if (elapsed >= this.INACTIVITY_TIMEOUT_MS) {
                    this.triggerSessionExpired();
                } else if (elapsed >= this.WARNING_THRESHOLD_MS) {
                    this.inactivityWarning = true;
                    this.inactivityCountdown = Math.max(0, Math.floor((this.INACTIVITY_TIMEOUT_MS - elapsed) / 1000));
                } else {
                    this.inactivityWarning = false;
                }
            },

            formatSeconds(secs) {
                const m = Math.floor(secs / 60);
                const s = secs % 60;
                return `${m}:${s < 10 ? '0' : ''}${s}`;
            },

            staySignedIn() {
                const now = Date.now();
                localStorage.setItem('comsoc_last_activity', now.toString());
                this.inactivityWarning = false;
                if (this.channel) {
                    this.channel.postMessage({ type: 'USER_ACTIVITY', timestamp: now });
                }
                // Ping poll endpoint to renew server session
                this.poll();
            },

            triggerSessionExpired() {
                this.sessionExpired = true;
                this.inactivityWarning = false;
                if (this.pollInterval) clearInterval(this.pollInterval);
                if (this.inactivityCheckInterval) clearInterval(this.inactivityCheckInterval);

                document.body.style.overflow = 'hidden';
                if (this.channel) {
                    this.channel.postMessage({ type: 'SESSION_EXPIRED' });
                }
                this.$nextTick(() => {
                    if (window.lucide) window.lucide.createIcons();
                });
            },

            triggerSessionReplaced() {
                this.sessionReplaced = true;
                if (this.pollInterval) {
                    clearInterval(this.pollInterval);
                    this.pollInterval = null;
                }
                if (this.inactivityCheckInterval) {
                    clearInterval(this.inactivityCheckInterval);
                }
                // Clear any active auto-dismiss timers
                this.timers.forEach(t => clearTimeout(t));
                this.timers.clear();

                document.body.style.overflow = 'hidden';
                try {
                    localStorage.setItem('comsoc_session_replaced', Date.now().toString());
                    if (this.channel) {
                        this.channel.postMessage({ type: 'SESSION_REPLACED' });
                    }
                } catch (e) {}
                this.$nextTick(() => {
                    if (window.lucide) window.lucide.createIcons();
                });
            },

            async poll() {
                if (this.sessionReplaced || this.sessionExpired) return;

                try {
                    const res = await fetch('{{ route('profile.sessions.poll') }}', {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        }
                    });

                    if (res.status === 401) {
                        // 401 Unauthorized means session has expired on server or replaced
                        const data = await res.json().catch(() => ({}));
                        if (data.session_replaced) {
                            this.triggerSessionReplaced();
                        } else {
                            this.triggerSessionExpired();
                        }
                        return;
                    }

                    if (res.ok) {
                        const data = await res.json();
                        if (data.session_replaced) {
                            this.triggerSessionReplaced();
                            return;
                        }

                        if (data.unread_alerts) {
                            const incomingAlerts = data.unread_alerts.filter(a => !this.dismissedIds.has(a.id));
                            
                            // Check for newly arrived alerts to schedule auto-dismiss (4.5s)
                            incomingAlerts.forEach(a => {
                                if (!this.timers.has(a.id)) {
                                    const timerId = setTimeout(() => {
                                        this.dismissAlert(a.id);
                                    }, 4500);
                                    this.timers.set(a.id, timerId);
                                }
                            });

                            this.alerts = incomingAlerts;
                            if (incomingAlerts.length > 0) {
                                this.$nextTick(() => {
                                    if (window.lucide) window.lucide.createIcons();
                                });
                            }
                        }
                    }
                } catch (e) {
                    // Network glitch or offline
                }
            },

            dismissAlert(id) {
                if (this.timers.has(id)) {
                    clearTimeout(this.timers.get(id));
                    this.timers.delete(id);
                }
                this.dismissedIds.add(id);
                this.alerts = this.alerts.filter(a => a.id !== id);

                const tokenMeta = document.querySelector('meta[name="csrf-token"]');
                fetch(`/notifications/${id}/read`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': tokenMeta ? tokenMeta.getAttribute('content') : '',
                        'X-Requested-With': 'XMLHttpRequest',
                    }
                }).catch(() => {});
            }
        };
    }
</script>
