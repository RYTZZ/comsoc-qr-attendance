<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $kiosk->name }} — Attendance Kiosk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Michroma&family=Sora:wght@600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-[#0f1117] font-sans text-slate-100" x-data="kioskApp()" x-init="init()">

<div class="min-h-screen flex flex-col justify-between p-3 sm:p-6 max-w-6xl mx-auto w-full">
    <header class="w-full flex items-center justify-between py-3 border-b border-slate-800/80 mb-4 shrink-0">
        <div class="flex items-center gap-2.5 min-w-0">
            <div class="w-10 h-10 rounded-xl bg-[#171a23] border border-slate-800 p-1.5 flex items-center justify-center shadow shrink-0">
                <img src="{{ asset('images/COMSOC.png') }}" alt="Computing Society Logo" class="w-full h-full object-contain">
            </div>
            <div class="min-w-0">
                <h1 class="text-base sm:text-lg font-bold text-white truncate font-brand-display">{{ $kiosk->name }}</h1>
                <p class="text-[10px] sm:text-xs text-[#cc7478] font-brand-accent tracking-wider uppercase">
                    COMPUTING SOCIETY • {{ $kiosk->identifier ?? ('KIOSK-' . $kiosk->id) }}
                </p>
            </div>
        </div>
        <div class="flex items-center gap-3 sm:gap-4">
            <div class="text-right">
                <p class="text-xs sm:text-sm font-semibold text-slate-300 font-mono" x-text="currentTime"></p>
                <div class="flex items-center justify-end gap-1.5 mt-0.5">
                    <span :class="isOnline ? 'bg-emerald-400 animate-pulse' : 'bg-red-500'" class="w-2 h-2 rounded-full"></span>
                    <span class="text-[10px] text-slate-400" x-text="isOnline ? 'Online' : 'Offline'"></span>
                </div>
            </div>
            @auth
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" class="p-2 rounded-lg bg-slate-900 border border-slate-800 text-slate-400 hover:text-red-400 transition" title="Log out">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </button>
            </form>
            @endauth
        </div>
    </header>

    <main class="w-full my-auto py-2 space-y-4">
        <div x-show="!isOnline" class="kiosk-feedback-offline animate-fade-in" x-cloak>
            <div class="text-3xl sm:text-4xl mb-2">📡</div>
            <p class="font-bold text-slate-200 text-base sm:text-lg">Connection Lost</p>
            <p class="text-slate-400 text-xs sm:text-sm mt-1">Attendance scanning is paused. Checking connection...</p>
        </div>

        <div x-show="isOnline" class="space-y-4">
            <div class="card space-y-3">
                <div>
                    <label class="label text-xs font-semibold text-slate-300">Active Event *</label>
                    <select x-model="selectedEventId" @change="onEventChanged()" class="select text-xs sm:text-sm bg-slate-950">
                        <option value="">-- Choose an Event --</option>
                        @foreach($events as $event)
                        <option value="{{ $event->id }}" data-sessions="{{ json_encode($event->attendanceSessions) }}">
                            {{ $event->name }} ({{ $event->event_date ? $event->event_date->format('M d') : 'Date TBA' }})
                        </option>
                        @endforeach
                    </select>
                </div>

                <div x-show="sessions.length > 0">
                    <label class="label text-xs font-semibold text-slate-300">Attendance Session *</label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        <template x-for="session in sessions" :key="session.id">
                            <button type="button" @click="selectSession(session.id)"
                                    :class="selectedSessionId === session.id
                                        ? 'border-indigo-500 bg-indigo-950/80 text-indigo-200 shadow-md ring-1 ring-indigo-500/50'
                                        : 'border-slate-800 bg-slate-950 text-slate-400 hover:border-slate-700'"
                                    class="border rounded-xl px-2.5 py-2.5 text-xs font-semibold transition-all text-center min-h-[44px] flex items-center justify-center active:scale-[0.98]"
                                    x-text="session.label || formatSessionType(session.type)">
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <div x-show="selectedEventId" x-transition class="space-y-4">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">
                    <div class="lg:col-span-7 space-y-3">
                        <div class="card border-slate-800 p-4 sm:p-5 relative overflow-hidden bg-[#141721]">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-800/80 mb-3">
                                <div>
                                    <h2 class="text-sm sm:text-base font-bold text-white flex items-center gap-2 font-brand-display">
                                        <span class="w-2.5 h-2.5 rounded-full bg-indigo-500 animate-ping"></span>
                                        LIVE CAMERA QR SCANNER
                                    </h2>
                                    <p class="text-[11px] text-slate-400">Position attendee QR code clearly within the view</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <template x-for="cam in cameras" :key="cam.id">
                                        <button type="button"
                                                x-show="cameras.length > 1"
                                                @click="switchCamera(cam.id)"
                                                :class="activeCameraId === cam.id ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-400'"
                                                class="px-2 py-1 rounded text-[10px] font-semibold transition hover:text-white"
                                                x-text="cam.label.length > 14 ? cam.label.substring(0, 12) + '...' : cam.label">
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <div class="relative w-full aspect-[4/3] bg-black rounded-xl overflow-hidden border border-slate-800 flex items-center justify-center">
                                <div id="qr-camera-reader" class="w-full h-full"></div>

                                <div x-show="cameraStatus === 'ready'" class="pointer-events-none absolute inset-0 flex items-center justify-center">
                                    <div class="w-56 h-56 sm:w-64 sm:h-64 border-2 border-indigo-500/80 rounded-2xl relative shadow-[0_0_20px_rgba(99,102,241,0.2)]">
                                        <span class="absolute -top-1 -left-1 w-6 h-6 border-t-4 border-l-4 border-indigo-400 rounded-tl-lg"></span>
                                        <span class="absolute -top-1 -right-1 w-6 h-6 border-t-4 border-r-4 border-indigo-400 rounded-tr-lg"></span>
                                        <span class="absolute -bottom-1 -left-1 w-6 h-6 border-b-4 border-l-4 border-indigo-400 rounded-bl-lg"></span>
                                        <span class="absolute -bottom-1 -right-1 w-6 h-6 border-b-4 border-r-4 border-indigo-400 rounded-br-lg"></span>
                                        <div class="absolute inset-x-2 top-0 h-0.5 bg-gradient-to-r from-transparent via-indigo-400 to-transparent animate-pulse"></div>
                                    </div>
                                </div>

                                <div x-show="cameraStatus === 'requesting'" class="absolute inset-0 bg-[#0f1117]/90 flex flex-col items-center justify-center p-4 text-center z-10">
                                    <div class="w-10 h-10 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin mb-3"></div>
                                    <p class="font-bold text-white text-sm">Starting Camera...</p>
                                    <p class="text-xs text-slate-400 mt-1">Please allow camera permissions if prompted by browser</p>
                                </div>

                                <div x-show="cameraStatus === 'permission_denied'" class="absolute inset-0 bg-[#171a23]/95 flex flex-col items-center justify-center p-6 text-center z-10 space-y-3" x-cloak>
                                    <div class="w-12 h-12 rounded-full bg-red-500/10 text-red-400 flex items-center justify-center text-2xl mx-auto border border-red-500/20">📷</div>
                                    <h3 class="font-bold text-white text-base">Camera Permission Denied</h3>
                                    <p class="text-xs text-slate-300 max-w-xs leading-relaxed">
                                        Camera access is blocked. Please tap the lock/camera icon near your browser address bar and enable camera permissions, then try again.
                                    </p>
                                    <button type="button" @click="startScanner()" class="btn-primary btn-sm px-4 py-2 mt-2">
                                        Retry Camera Access
                                    </button>
                                </div>

                                <div x-show="cameraStatus === 'not_found'" class="absolute inset-0 bg-[#171a23]/95 flex flex-col items-center justify-center p-6 text-center z-10 space-y-3" x-cloak>
                                    <div class="w-12 h-12 rounded-full bg-amber-500/10 text-amber-400 flex items-center justify-center text-2xl mx-auto border border-amber-500/20">⚠️</div>
                                    <h3 class="font-bold text-white text-base">No Camera Detected</h3>
                                    <p class="text-xs text-slate-300 max-w-xs leading-relaxed">
                                        No camera device found on this system. You can connect a webcam or use the keyboard/scanner input below.
                                    </p>
                                    <button type="button" @click="startScanner()" class="btn-secondary btn-sm px-4 py-2 mt-2">
                                        Check Devices Again
                                    </button>
                                </div>
                            </div>

                            <div class="flex items-center justify-between text-[11px] text-slate-400 mt-3 pt-2 border-t border-slate-800">
                                <span class="flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full" :class="cameraStatus === 'ready' ? 'bg-emerald-400' : 'bg-amber-400'"></span>
                                    <span x-text="cameraStatus === 'ready' ? 'Auto-detection active (Rear camera preferred)' : 'Scanner initializing...'"></span>
                                </span>
                                <span x-show="scanning" class="text-indigo-400 font-semibold animate-pulse">Processing QR...</span>
                            </div>
                        </div>

                        <div class="card border-slate-800 p-3 bg-slate-950/40">
                            <div class="flex items-center gap-2">
                                <input type="text"
                                       x-model="token"
                                       @keydown.enter="submitScan()"
                                       placeholder="Manual / Barcode Scanner input fallback..."
                                       class="input text-xs font-mono h-10 bg-slate-950 border-slate-800 text-white focus:ring-1 focus:ring-indigo-500"
                                       :disabled="scanning"
                                       id="kiosk-token-input"
                                       autocomplete="off"
                                       autocorrect="off"
                                       autocapitalize="off"
                                       spellcheck="false">
                                <button type="button" @click="submitScan()" :disabled="scanning || !token" class="btn-primary btn-sm px-4 h-10 shrink-0">
                                    Scan
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-5 space-y-4">
                        <div class="card border-slate-800 p-4 sm:p-5 bg-[#171a23]">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-800 mb-3">
                                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">LATEST SCAN</h3>
                                <span x-show="latestScan" class="text-[10px] text-emerald-400 font-mono">Recorded</span>
                            </div>

                            <template x-if="latestScan">
                                <div class="space-y-3 animate-fade-in">
                                    <div>
                                        <p class="text-xs text-slate-500">Student Name</p>
                                        <p class="text-lg font-bold text-white font-brand-display" x-text="latestScan.name || 'Attendee'"></p>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <p class="text-xs text-slate-500">Student Number</p>
                                            <p class="text-sm font-semibold text-slate-200 font-mono" x-text="latestScan.student_number || '—'"></p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-slate-500">Time</p>
                                            <p class="text-sm font-semibold text-slate-200 font-mono" x-text="latestScan.time"></p>
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-xs text-slate-500 mb-1">Attendance Status</p>
                                        <span :class="{
                                            'badge-present': latestScan.status === 'present' || latestScan.status === 'on_time',
                                            'badge-late': latestScan.status === 'late'
                                        }" class="badge text-xs px-3 py-1 font-bold" x-text="(latestScan.status || 'PRESENT').toUpperCase()"></span>
                                        <span class="text-xs text-slate-400 ml-2" x-text="latestScan.message"></span>
                                    </div>
                                </div>
                            </template>

                            <template x-if="!latestScan">
                                <div class="py-8 text-center text-slate-500 space-y-1">
                                    <div class="text-3xl opacity-40">⏱</div>
                                    <p class="text-xs">No scan recorded yet</p>
                                    <p class="text-[11px] text-slate-600">Scan QR codes to see real-time updates</p>
                                </div>
                            </template>
                        </div>

                        <div class="card border-slate-800 p-4 sm:p-5 bg-[#171a23]">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-800 mb-3">
                                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">PREVIOUS SCAN</h3>
                            </div>

                            <template x-if="previousScan">
                                <div class="space-y-2 text-xs animate-fade-in">
                                    <div>
                                        <p class="text-[11px] text-slate-500">Student Name</p>
                                        <p class="font-semibold text-white text-sm font-brand-display" x-text="previousScan.name || 'Attendee'"></p>
                                    </div>
                                    <div class="flex items-center justify-between pt-1">
                                        <div>
                                            <p class="text-[11px] text-slate-500">Status</p>
                                            <span :class="{
                                                'badge-present': previousScan.status === 'present' || previousScan.status === 'on_time',
                                                'badge-late': previousScan.status === 'late'
                                            }" class="badge text-[10px] px-2 py-0.5" x-text="(previousScan.status || 'PRESENT').toUpperCase()"></span>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-[11px] text-slate-500">Time</p>
                                            <p class="font-mono text-slate-300" x-text="previousScan.time"></p>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <template x-if="!previousScan">
                                <div class="py-6 text-center text-slate-500 text-xs">
                                    <p>No previous scan</p>
                                </div>
                            </template>
                        </div>

                        <div x-show="feedback" x-transition class="animate-slide-up" x-cloak>
                            <div :class="{
                                'kiosk-feedback-success': feedback?.type === 'success',
                                'kiosk-feedback-error': feedback?.type === 'error',
                                'kiosk-feedback-duplicate': feedback?.type === 'duplicate'
                            }" class="kiosk-feedback shadow-2xl py-3 px-4">
                                <div class="text-2xl mb-1" x-text="feedback?.icon"></div>
                                <p class="font-bold text-sm text-white" x-text="feedback?.name || feedback?.message"></p>
                                <p x-show="feedback?.name" class="text-xs text-slate-300 mt-0.5" x-text="feedback?.message"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="w-full text-center py-3 text-[11px] text-slate-600 border-t border-slate-900 mt-auto shrink-0">
        ComSoc QR Terminal — Mobile, Tablet & Laptop Camera Ready
    </footer>
</div>

<script>
function kioskApp() {
    return {
        token: '',
        scanning: false,
        feedback: null,
        isOnline: true,
        currentTime: '',
        selectedEventId: '',
        selectedSessionId: '',
        sessions: [],
        feedbackTimer: null,
        qrScanner: null,
        cameraStatus: 'idle',
        cameras: [],
        activeCameraId: null,
        latestScan: null,
        previousScan: null,
        lastScannedToken: '',
        lastScanTimestamp: 0,

        init() {
            this.updateTime();
            setInterval(() => this.updateTime(), 1000);
            this.checkConnectivity();
            setInterval(() => this.checkConnectivity(), 5000);

            this.$nextTick(() => {
                const select = document.querySelector('select');
                if (select && select.options.length === 2 && !this.selectedEventId) {
                    this.selectedEventId = select.options[1].value;
                    this.onEventChanged();
                }
            });
        },

        updateTime() {
            this.currentTime = new Date().toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        },

        async checkConnectivity() {
            try {
                const res = await fetch('/kiosk/health', { cache: 'no-cache', signal: AbortSignal.timeout(4000) });
                this.isOnline = res.ok;
            } catch {
                this.isOnline = false;
            }
        },

        onEventChanged() {
            this.loadSessions();
            if (this.selectedEventId) {
                this.$nextTick(() => {
                    this.startScanner();
                });
            } else {
                this.stopScanner();
            }
        },

        loadSessions() {
            this.sessions = [];
            this.selectedSessionId = '';
            if (!this.selectedEventId) return;
            const selectEl = document.querySelector('select');
            if (selectEl) {
                for (const opt of selectEl.options) {
                    if (opt.value === this.selectedEventId && opt.dataset.sessions) {
                        try {
                            this.sessions = JSON.parse(opt.dataset.sessions);
                        } catch (e) {
                            this.sessions = [];
                        }
                        break;
                    }
                }
            }
            if (this.sessions.length > 0) {
                this.selectedSessionId = this.sessions[0].id;
            }
        },

        selectSession(sessionId) {
            this.selectedSessionId = sessionId;
        },

        formatSessionType(type) {
            const labels = {
                morning_in: 'Morning IN',
                morning_out: 'Morning OUT',
                afternoon_in: 'Afternoon IN',
                afternoon_out: 'Afternoon OUT',
            };
            return labels[type] || type;
        },

        async startScanner() {
            if (!window.Html5Qrcode) {
                setTimeout(() => this.startScanner(), 200);
                return;
            }

            const element = document.getElementById('qr-camera-reader');
            if (!element) return;

            await this.stopScanner();

            this.cameraStatus = 'requesting';

            try {
                const devices = await window.Html5Qrcode.getCameras();
                if (!devices || devices.length === 0) {
                    this.cameraStatus = 'not_found';
                    return;
                }

                this.cameras = devices;
                this.qrScanner = new window.Html5Qrcode('qr-camera-reader');

                let selectedCamera = devices[0].id;
                const backCamera = devices.find(d => /back|rear|environment/i.test(d.label));
                if (backCamera) {
                    selectedCamera = backCamera.id;
                }
                this.activeCameraId = selectedCamera;

                await this.qrScanner.start(
                    selectedCamera,
                    {
                        fps: 10,
                        qrbox: (viewfinderWidth, viewfinderHeight) => {
                            const minEdge = Math.min(viewfinderWidth, viewfinderHeight);
                            const qrboxEdge = Math.floor(minEdge * 0.75);
                            return { width: qrboxEdge, height: qrboxEdge };
                        },
                        aspectRatio: 1.333333
                    },
                    (decodedText) => {
                        this.handleDecodedQr(decodedText);
                    },
                    () => {}
                );

                this.cameraStatus = 'ready';
            } catch (err) {
                const errStr = String(err).toLowerCase();
                if (errStr.includes('notallowed') || errStr.includes('permission')) {
                    this.cameraStatus = 'permission_denied';
                } else if (errStr.includes('notfound') || errStr.includes('device')) {
                    this.cameraStatus = 'not_found';
                } else {
                    try {
                        await this.qrScanner.start(
                            { facingMode: 'environment' },
                            {
                                fps: 10,
                                qrbox: { width: 250, height: 250 }
                            },
                            (decodedText) => this.handleDecodedQr(decodedText),
                            () => {}
                        );
                        this.cameraStatus = 'ready';
                    } catch {
                        this.cameraStatus = 'permission_denied';
                    }
                }
            }
        },

        async switchCamera(cameraId) {
            this.activeCameraId = cameraId;
            if (this.qrScanner) {
                await this.stopScanner();
                this.qrScanner = new window.Html5Qrcode('qr-camera-reader');
                await this.qrScanner.start(
                    cameraId,
                    {
                        fps: 10,
                        qrbox: (viewfinderWidth, viewfinderHeight) => {
                            const minEdge = Math.min(viewfinderWidth, viewfinderHeight);
                            const qrboxEdge = Math.floor(minEdge * 0.75);
                            return { width: qrboxEdge, height: qrboxEdge };
                        }
                    },
                    (decodedText) => this.handleDecodedQr(decodedText),
                    () => {}
                );
                this.cameraStatus = 'ready';
            }
        },

        async stopScanner() {
            if (this.qrScanner) {
                try {
                    await this.qrScanner.stop();
                    this.qrScanner.clear();
                } catch {}
                this.qrScanner = null;
            }
        },

        handleDecodedQr(decodedText) {
            const cleanToken = decodedText.trim();
            const now = Date.now();
            if (cleanToken === this.lastScannedToken && (now - this.lastScanTimestamp) < 3000) {
                return;
            }

            this.lastScannedToken = cleanToken;
            this.lastScanTimestamp = now;
            this.token = cleanToken;
            this.submitScan();
        },

        async submitScan() {
            if (!this.token || this.scanning || !this.isOnline) return;

            if (!this.selectedEventId) {
                this.showFeedback('error', null, 'Please select an event first.');
                return;
            }

            if (!this.selectedSessionId) {
                this.showFeedback('error', null, 'Please select an attendance session.');
                return;
            }

            this.scanning = true;

            try {
                const res = await fetch('/kiosk/{{ $kiosk->id }}/scan', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        token: this.token,
                        event_id: this.selectedEventId,
                        session_id: this.selectedSessionId,
                    }),
                });

                const data = await res.json();
                const scanTime = new Date().toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit', second: '2-digit' });

                if (data.success) {
                    if (this.latestScan) {
                        this.previousScan = { ...this.latestScan };
                    }
                    this.latestScan = {
                        name: data.name,
                        student_number: data.student_number || 'N/A',
                        status: data.status,
                        time: scanTime,
                        message: data.message
                    };
                    this.showFeedback('success', data.name, data.message, data.status);
                } else if (data.code === 'duplicate') {
                    this.showFeedback('duplicate', null, data.message);
                } else {
                    this.showFeedback('error', null, data.message);
                }
            } catch {
                this.isOnline = false;
                this.showFeedback('error', null, 'Connection lost. Scan not recorded.');
            } finally {
                this.scanning = false;
                this.token = '';
            }
        },

        showFeedback(type, name, message, status = null) {
            const icons = { success: '✅', error: '❌', duplicate: '⚠️' };
            this.feedback = { type, icon: icons[type], name, message, status };

            if (this.feedbackTimer) clearTimeout(this.feedbackTimer);
            this.feedbackTimer = setTimeout(() => { this.feedback = null; }, 4000);
        }
    };
}
</script>
</body>
</html>
