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
            alerts: [],
            dismissedIds: new Set(),
            timers: new Map(),
            pollInterval: null,
            channel: null,

            initMonitor() {
                // Initialize BroadcastChannel for cross-tab synchronization
                try {
                    if ('BroadcastChannel' in window) {
                        this.channel = new BroadcastChannel('comsoc_session_channel');
                        this.channel.onmessage = (event) => {
                            if (event.data && event.data.type === 'SESSION_REPLACED') {
                                this.triggerSessionReplaced();
                            }
                        };
                    }
                } catch (e) {}

                // Check localStorage marker in case another tab already detected takeover
                try {
                    const replacedAt = localStorage.getItem('comsoc_session_replaced');
                    if (replacedAt && (Date.now() - parseInt(replacedAt, 10)) < 86400000) {
                        // Handled on backend poll check
                    }
                } catch (e) {}

                // Initial poll immediately, then every 5 seconds
                this.poll();
                this.pollInterval = setInterval(() => this.poll(), 5000);

                // Prevent dismissing modal via Esc key
                window.addEventListener('keydown', (e) => {
                    if (this.sessionReplaced && (e.key === 'Escape' || e.key === 'Esc')) {
                        e.preventDefault();
                        e.stopPropagation();
                    }
                }, true);
            },

            triggerSessionReplaced() {
                this.sessionReplaced = true;
                if (this.pollInterval) {
                    clearInterval(this.pollInterval);
                    this.pollInterval = null;
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
                if (this.sessionReplaced) return;

                try {
                    const res = await fetch('{{ route('profile.sessions.poll') }}', {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        }
                    });

                    if (res.status === 401) {
                        // 401 Unauthorized means session is invalidated/replaced
                        this.triggerSessionReplaced();
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
