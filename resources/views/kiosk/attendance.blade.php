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
    <style>
        #qr-camera-reader {
            width: 100% !important;
            height: 100% !important;
            border: none !important;
            background: #000 !important;
            position: relative;
        }
        #qr-camera-reader video {
            width: 100% !important;
            height: 100% !important;
            object-fit: contain !important;
            border-radius: 0.75rem !important;
        }
        #qr-camera-reader__scan_region {
            width: 100% !important;
            height: 100% !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        #qr-camera-reader__dashboard {
            display: none !important;
        }
        .scanner-laser-line {
            position: absolute;
            left: 10%;
            right: 10%;
            height: 2px;
            background: linear-gradient(90deg, transparent, #818cf8, #c084fc, #818cf8, transparent);
            box-shadow: 0 0 8px #818cf8, 0 0 16px #c084fc;
            animation: laserScan 2.2s ease-in-out infinite;
            pointer-events: none;
            z-index: 5;
        }
        @keyframes laserScan {
            0% { top: 15%; opacity: 0.2; }
            50% { top: 85%; opacity: 1; }
            100% { top: 15%; opacity: 0.2; }
        }
    </style>
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
            <div class="w-12 h-12 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center mx-auto mb-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 1l22 22M16.72 11.06A10.94 10.94 0 0119 12.55M5 12.55a10.94 10.94 0 015.17-2.39M10.71 5.05A16 16 0 0122.56 9M1.42 9a15.91 15.91 0 014.7-2.88m3.6-1.07A16.03 16.03 0 0112 5c.81 0 1.6.06 2.37.18M8.53 16.11a6 6 0 016.95 0M12 20h.01"/>
                </svg>
            </div>
            <p class="font-bold text-slate-200 text-base sm:text-lg">Connection Lost</p>
            <p class="text-slate-400 text-xs sm:text-sm mt-1">Attendance scanning is paused. Checking connection...</p>
        </div>

        <div x-show="isOnline" class="space-y-4">
            <div class="card space-y-3">
                <div>
                    <label class="label text-xs font-semibold text-slate-300">Active Event *</label>
                    <div class="relative" x-data="{ open: false }">
                        <button type="button"
                                @click="open = !open"
                                @keydown.escape.window="open = false"
                                :class="open ? 'ring-2 ring-[#7A1618] border-transparent shadow-md' : 'border-slate-700 hover:border-slate-600'"
                                class="w-full flex items-center justify-between gap-2 rounded-xl bg-[#12141c] border px-3.5 py-2.5 text-xs sm:text-sm text-left transition focus:outline-none focus:ring-2 focus:ring-[#7A1618]">
                            <span class="truncate block"
                                  :class="selectedEventId ? 'text-white font-medium' : 'text-slate-500'"
                                  x-text="eventsData[selectedEventId] ? eventsData[selectedEventId].name : '-- Choose an Event --'">
                            </span>
                            <span class="pointer-events-none flex items-center text-slate-400 transition-transform duration-200"
                                  :class="open ? 'rotate-180 text-[#cc7478]' : ''">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </span>
                        </button>
                        <div x-show="open"
                             @click.away="open = false"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             x-cloak
                             class="absolute z-50 mt-1.5 w-full rounded-xl bg-[#1e222d] border border-slate-700/80 shadow-2xl py-1 text-xs sm:text-sm max-h-60 overflow-y-auto focus:outline-none">
                            <template x-for="(ev, evId) in eventsData" :key="evId">
                                <div @click="selectedEventId = evId; open = false; onEventChanged()"
                                     :class="selectedEventId === evId ? 'bg-[#7A1618] text-white font-semibold' : 'text-slate-300 hover:bg-[#303644] hover:text-white'"
                                     class="flex items-center justify-between px-3.5 py-2.5 cursor-pointer transition select-none">
                                    <span x-text="ev.name" class="truncate"></span>
                                    <span x-show="selectedEventId === evId" class="text-white ml-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </span>
                                </div>
                            </template>
                        </div>
                    </div>
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
                                    <button type="button"
                                            x-show="cameras.length > 1"
                                            @click="toggleCamera()"
                                            title="Switch Camera"
                                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-800 text-slate-300 hover:bg-slate-700 hover:text-white transition text-[11px] font-semibold">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                        <span>Switch Camera</span>
                                    </button>
                                </div>
                            </div>

                            <div class="relative w-full aspect-square max-h-[380px] bg-black rounded-xl overflow-hidden border border-slate-800 flex items-center justify-center">
                                <div id="qr-camera-reader" class="w-full h-full"></div>

                                <!-- Centered Scanning Target Frame & Active Laser Indicator -->
                                <div x-show="cameraStatus === 'ready'" class="absolute inset-0 pointer-events-none flex items-center justify-center z-10" x-cloak>
                                    <div class="relative w-3/4 h-3/4 max-w-[280px] max-h-[280px] border-2 border-indigo-500/40 rounded-2xl">
                                        <!-- Corner Brackets -->
                                        <div class="absolute -top-1 -left-1 w-6 h-6 border-t-4 border-l-4 border-indigo-400 rounded-tl-lg"></div>
                                        <div class="absolute -top-1 -right-1 w-6 h-6 border-t-4 border-r-4 border-indigo-400 rounded-tr-lg"></div>
                                        <div class="absolute -bottom-1 -left-1 w-6 h-6 border-b-4 border-l-4 border-indigo-400 rounded-bl-lg"></div>
                                        <div class="absolute -bottom-1 -right-1 w-6 h-6 border-b-4 border-r-4 border-indigo-400 rounded-br-lg"></div>
                                        <!-- Animated Laser Scan Line -->
                                        <div class="scanner-laser-line"></div>
                                    </div>
                                </div>

                                <div x-show="cameraStatus === 'starting'" class="absolute inset-0 bg-[#171a23]/95 flex flex-col items-center justify-center p-6 text-center z-10" x-cloak>
                                    <div class="w-10 h-10 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin mb-3"></div>
                                    <p class="font-bold text-white text-sm">Starting Camera...</p>
                                    <p class="text-xs text-slate-400 mt-1">Please allow camera permissions if prompted by browser</p>
                                </div>

                                <div x-show="cameraStatus === 'permission_denied'" class="absolute inset-0 bg-[#171a23]/95 flex flex-col items-center justify-center p-6 text-center z-10 space-y-3" x-cloak>
                                    <div class="w-12 h-12 rounded-full bg-red-500/10 text-red-400 flex items-center justify-center mx-auto border border-red-500/20">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    </div>
                                    <h3 class="font-bold text-white text-base">Camera Permission Denied</h3>
                                    <p class="text-xs text-slate-300 max-w-xs leading-relaxed">
                                        Camera access is blocked. Please tap the lock/camera icon near your browser address bar and enable camera permissions, then try again.
                                    </p>
                                    <button type="button" @click="startScanner()" class="btn-primary btn-sm px-4 py-2 mt-2">
                                        Retry Camera Access
                                    </button>
                                </div>

                                <div x-show="cameraStatus === 'not_found'" class="absolute inset-0 bg-[#171a23]/95 flex flex-col items-center justify-center p-6 text-center z-10 space-y-3" x-cloak>
                                    <div class="w-12 h-12 rounded-full bg-amber-500/10 text-amber-400 flex items-center justify-center mx-auto border border-amber-500/20">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    </div>
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
                                <div class="py-8 text-center text-slate-500 space-y-2 flex flex-col items-center justify-center">
                                    <svg class="w-8 h-8 opacity-40 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <circle cx="12" cy="12" r="10" stroke-width="1.5"></circle>
                                        <polyline points="12 6 12 12 16 14" stroke-width="1.5"></polyline>
                                    </svg>
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
                            }" class="kiosk-feedback shadow-2xl py-3 px-4 flex flex-col items-center">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center mb-1.5"
                                     :class="{
                                         'bg-emerald-500/20 text-emerald-400': feedback?.type === 'success',
                                         'bg-red-500/20 text-red-400': feedback?.type === 'error',
                                         'bg-amber-500/20 text-amber-400': feedback?.type === 'duplicate'
                                     }">
                                    <template x-if="feedback?.type === 'success'">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    </template>
                                    <template x-if="feedback?.type === 'error'">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </template>
                                    <template x-if="feedback?.type === 'duplicate'">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    </template>
                                </div>
                                <p class="font-bold text-sm text-white text-center" x-text="feedback?.name || feedback?.message"></p>
                                <p x-show="feedback?.name" class="text-xs text-slate-300 mt-0.5 text-center" x-text="feedback?.message"></p>
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

        eventsData: {
            @foreach($events as $event)
            '{{ $event->id }}': {
                name: '{{ addslashes($event->name) }} ({{ $event->event_date ? $event->event_date->format('M d') : 'Date TBA' }})',
                sessions: {!! json_encode($event->attendanceSessions) !!}
            },
            @endforeach
        },

        init() {
            this.updateTime();
            setInterval(() => this.updateTime(), 1000);
            this.checkConnectivity();
            setInterval(() => this.checkConnectivity(), 5000);

            this.$nextTick(() => {
                const eventIds = Object.keys(this.eventsData);
                if (eventIds.length === 1 && !this.selectedEventId) {
                    this.selectedEventId = eventIds[0];
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
            if (!this.selectedEventId || !this.eventsData[this.selectedEventId]) return;
            this.sessions = this.eventsData[this.selectedEventId].sessions || [];
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
                this.qrScanner = new window.Html5Qrcode('qr-camera-reader', {
                    experimentalFeatures: {
                        useBarCodeDetectorIfSupported: true
                    },
                    verbose: false
                });

                let selectedCamera = devices[0].id;
                const backCamera = devices.find(d => /back|rear|environment/i.test(d.label));
                if (backCamera) {
                    selectedCamera = backCamera.id;
                }
                this.activeCameraId = selectedCamera;

                const scanConfig = {
                    fps: 20,
                    aspectRatio: 1.0,
                    videoConstraints: {
                        deviceId: selectedCamera,
                        focusMode: 'continuous',
                        advanced: [{ focusMode: 'continuous' }],
                        width: { min: 640, ideal: 1280 },
                        height: { min: 480, ideal: 720 }
                    }
                };

                await this.qrScanner.start(
                    selectedCamera,
                    scanConfig,
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
                                fps: 20,
                                aspectRatio: 1.0,
                                videoConstraints: {
                                    focusMode: 'continuous',
                                    advanced: [{ focusMode: 'continuous' }]
                                }
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
                this.qrScanner = new window.Html5Qrcode('qr-camera-reader', {
                    experimentalFeatures: {
                        useBarCodeDetectorIfSupported: true
                    },
                    verbose: false
                });
                await this.qrScanner.start(
                    cameraId,
                    {
                        fps: 20,
                        aspectRatio: 1.0,
                        videoConstraints: {
                            deviceId: cameraId,
                            focusMode: 'continuous',
                            advanced: [{ focusMode: 'continuous' }],
                            width: { min: 640, ideal: 1280 },
                            height: { min: 480, ideal: 720 }
                        }
                    },
                    (decodedText) => this.handleDecodedQr(decodedText),
                    () => {}
                );
                this.cameraStatus = 'ready';
            }
        },

        toggleCamera() {
            if (this.cameras.length < 2) return;
            const currentIndex = this.cameras.findIndex(c => c.id === this.activeCameraId);
            const nextIndex = (currentIndex + 1) % this.cameras.length;
            this.switchCamera(this.cameras[nextIndex].id);
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
            if (!decodedText) return;
            let cleanToken = decodedText.trim();

            const tokenMatch = cleanToken.match(/[A-Za-z0-9]{48}/);
            if (tokenMatch) {
                cleanToken = tokenMatch[0];
            } else if (cleanToken.includes('token=')) {
                try {
                    const parsedUrl = new URL(cleanToken, window.location.origin);
                    const urlToken = parsedUrl.searchParams.get('token');
                    if (urlToken) cleanToken = urlToken.trim();
                } catch {}
            }

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
            this.feedback = { type, name, message, status };
            this.playTone(type);

            if (this.feedbackTimer) clearTimeout(this.feedbackTimer);
            this.feedbackTimer = setTimeout(() => { this.feedback = null; }, 4000);
        },

        playTone(type) {
            try {
                if (navigator.vibrate) {
                    if (type === 'success') navigator.vibrate(80);
                    else if (type === 'duplicate') navigator.vibrate([60, 40, 60]);
                    else navigator.vibrate([100, 50, 100]);
                }
                const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = audioCtx.createOscillator();
                const gain = audioCtx.createGain();
                osc.connect(gain);
                gain.connect(audioCtx.destination);

                if (type === 'success') {
                    osc.frequency.setValueAtTime(880, audioCtx.currentTime);
                    osc.frequency.exponentialRampToValueAtTime(1200, audioCtx.currentTime + 0.12);
                    gain.gain.setValueAtTime(0.15, audioCtx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.12);
                    osc.start();
                    osc.stop(audioCtx.currentTime + 0.12);
                } else if (type === 'duplicate') {
                    osc.frequency.setValueAtTime(440, audioCtx.currentTime);
                    gain.gain.setValueAtTime(0.12, audioCtx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.2);
                    osc.start();
                    osc.stop(audioCtx.currentTime + 0.2);
                } else {
                    osc.frequency.setValueAtTime(260, audioCtx.currentTime);
                    gain.gain.setValueAtTime(0.15, audioCtx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.25);
                    osc.start();
                    osc.stop(audioCtx.currentTime + 0.25);
                }
            } catch {}
        }
    };
}
</script>
</body>
</html>
