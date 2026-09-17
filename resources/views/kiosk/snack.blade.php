<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $kiosk->name }} — Snack Kiosk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Michroma&family=Sora:wght@600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-[#0f1117] font-sans" x-data="snackKiosk()" x-init="init()">

<div class="min-h-screen flex flex-col items-center justify-between p-3 sm:p-6 max-w-lg mx-auto w-full">
    <div class="w-full flex items-center justify-between py-2 border-b border-slate-800/80 mb-2">
        <div class="flex items-center gap-2.5 min-w-0">
            <div class="w-9 h-9 rounded-xl bg-[#171a23] border border-slate-800 p-1 flex items-center justify-center shadow shrink-0">
                <img src="{{ asset('images/COMSOC.png') }}" alt="Computing Society Logo" class="w-full h-full object-contain">
            </div>
            <div class="min-w-0">
                <h1 class="text-sm sm:text-base font-bold text-white truncate font-brand-display">{{ $kiosk->name }}</h1>
                <p class="text-[10px] sm:text-xs text-[#cc7478] font-brand-accent tracking-wider uppercase">COMPUTING SOCIETY • {{ $kiosk->identifier ?? ('KIOSK-' . $kiosk->id) }}</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-1.5">
                <span :class="isOnline ? 'bg-emerald-400 animate-pulse' : 'bg-red-500'" class="w-2 h-2 rounded-full"></span>
                <span class="text-[10px] text-slate-400" x-text="isOnline ? 'Online' : 'Offline'"></span>
            </div>
            @auth
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" class="p-1.5 rounded-lg bg-slate-900 border border-slate-800 text-slate-400 hover:text-red-400 transition" title="Log out">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </button>
            </form>
            @endauth
        </div>
    </div>

    <div class="w-full my-auto py-2 space-y-3 sm:space-y-4">
        <div x-show="!isOnline" class="kiosk-feedback-offline animate-fade-in">
            <div class="w-12 h-12 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center mx-auto mb-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 1l22 22M16.72 11.06A10.94 10.94 0 0119 12.55M5 12.55a10.94 10.94 0 015.17-2.39M10.71 5.05A16 16 0 0122.56 9M1.42 9a15.91 15.91 0 014.7-2.88m3.6-1.07A16.03 16.03 0 0112 5c.81 0 1.6.06 2.37.18M8.53 16.11a6 6 0 016.95 0M12 20h.01"/>
                </svg>
            </div>
            <p class="font-bold text-slate-200 text-base sm:text-lg">Connection Lost</p>
            <p class="text-slate-400 text-xs sm:text-sm mt-1">Snack scanning is paused.</p>
        </div>

        <div x-show="isOnline" class="space-y-3 sm:space-y-4">
            <div class="card space-y-3">
                <div>
                    <label class="label text-xs font-semibold text-slate-300">Snack Session *</label>
                    <div class="relative" x-data="{ open: false }">
                        <button type="button"
                                @click="open = !open"
                                @keydown.escape.window="open = false"
                                :class="open ? 'ring-2 ring-amber-500 border-transparent shadow-md' : 'border-slate-700 hover:border-slate-600'"
                                class="w-full flex items-center justify-between gap-2 rounded-xl bg-[#12141c] border px-3.5 py-2.5 text-xs sm:text-sm text-left transition focus:outline-none focus:ring-2 focus:ring-amber-500">
                            <span class="truncate block"
                                  :class="selectedSessionId ? 'text-white font-medium' : 'text-slate-500'"
                                  x-text="sessionsData[selectedSessionId] ? sessionsData[selectedSessionId].name : '-- Select a Snack Session --'">
                            </span>
                            <span class="pointer-events-none flex items-center text-slate-400 transition-transform duration-200"
                                  :class="open ? 'rotate-180 text-amber-400' : ''">
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
                            <template x-for="(ses, sId) in sessionsData" :key="sId">
                                <div @click="selectedSessionId = sId; open = false; loadInventories()"
                                     :class="selectedSessionId == sId ? 'bg-amber-600 text-white font-semibold' : 'text-slate-300 hover:bg-[#303644] hover:text-white'"
                                     class="flex items-center justify-between px-3.5 py-2.5 cursor-pointer transition select-none">
                                    <span x-text="ses.name" class="truncate"></span>
                                    <span x-show="selectedSessionId == sId" class="text-white ml-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <div x-show="inventories.length > 0">
                    <label class="label text-xs">Available Item *</label>
                    <div class="grid gap-2">
                        <template x-for="item in inventories" :key="item.id">
                            <button type="button" @click="selectedInventoryId = item.id"
                                    :class="selectedInventoryId === item.id
                                        ? 'border-amber-500 bg-amber-950/80 text-amber-200 ring-1 ring-amber-500/50 shadow-md'
                                        : 'border-slate-800 bg-slate-950 text-slate-400 hover:border-slate-700'"
                                    class="border rounded-xl px-3.5 py-3 text-xs sm:text-sm font-semibold transition-all flex justify-between items-center active:scale-[0.98] min-h-[44px]">
                                <span class="truncate font-medium text-white" x-text="item.item_name"></span>
                                <span class="badge bg-amber-500/10 text-amber-400 border border-amber-500/20 text-[10px] shrink-0 ml-2" x-text="(item.total_quantity - item.distributed_quantity) + ' left'"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <div x-show="selectedSessionId && selectedInventoryId" class="card border-amber-500/30">
                <div class="text-center mb-3">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20 uppercase tracking-wider mb-1">
                        Ready to Dispense
                    </span>
                    <h2 class="text-base sm:text-lg font-bold text-white">Scan Participant QR</h2>
                    <p class="text-xs text-slate-400">Claims are checked for duplicates per session</p>
                </div>

                <div class="relative">
                    <input type="text"
                           x-model="token"
                           @keydown.enter="submitClaim()"
                           @input="if(token.length >= 32) submitClaim()"
                           placeholder="Tap here or scan QR..."
                           class="input text-center font-mono text-xs sm:text-sm h-12 bg-slate-950 border-slate-700 text-white focus:ring-2 focus:ring-amber-500"
                           :disabled="scanning"
                           autofocus
                           id="snack-token-input"
                           autocomplete="off"
                           autocorrect="off"
                           autocapitalize="off"
                           spellcheck="false">
                </div>
            </div>

            <div x-show="feedback" x-transition class="animate-slide-up">
                <div :class="{
                    'kiosk-feedback-success': feedback?.type === 'success',
                    'kiosk-feedback-error': feedback?.type === 'error',
                    'kiosk-feedback-duplicate': feedback?.type === 'duplicate'
                }" class="kiosk-feedback shadow-2xl flex flex-col items-center">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center mb-2"
                         :class="{
                             'bg-emerald-500/20 text-emerald-400': feedback?.type === 'success',
                             'bg-red-500/20 text-red-400': feedback?.type === 'error',
                             'bg-amber-500/20 text-amber-400': feedback?.type === 'duplicate'
                         }">
                        <template x-if="feedback?.type === 'success'">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        </template>
                        <template x-if="feedback?.type === 'error'">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                        </template>
                        <template x-if="feedback?.type === 'duplicate'">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </template>
                    </div>
                    <p class="font-bold text-lg sm:text-xl text-white text-center" x-text="feedback?.message"></p>
                </div>
            </div>
        </div>
    </div>

    <div class="w-full text-center py-2 text-[11px] text-slate-600 border-t border-slate-900 mt-auto">
        ComSoc Snack Terminal — Mobile & Tablet Ready
    </div>
</div>

<script>
function snackKiosk() {
    return {
        token: '',
        scanning: false,
        feedback: null,
        sessionsData: {
            @foreach($sessions as $session)
            '{{ $session->id }}': {
                name: '{{ addslashes($session->event->name . " — " . $session->name) }}',
                inventories: @json($session->inventories)
            },
            @endforeach
        },
        selectedSessionId: '',
        selectedInventoryId: '',
        inventories: [],
        feedbackTimer: null,

        init() {
            this.checkConnectivity();
            setInterval(() => this.checkConnectivity(), 5000);
        },

        async checkConnectivity() {
            try {
                const res = await fetch('/kiosk/health', { cache: 'no-cache', signal: AbortSignal.timeout(4000) });
                this.isOnline = res.ok;
            } catch {
                this.isOnline = false;
            }
        },

        loadInventories() {
            const ses = this.sessionsData[this.selectedSessionId];
            this.inventories = ses ? (ses.inventories || []) : [];
            this.selectedInventoryId = '';
        },

        async submitClaim() {
            if (!this.token || this.scanning || !this.isOnline) return;
            this.scanning = true;

            try {
                const res = await fetch('/kiosk/{{ $kiosk->id }}/snack/scan', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        token: this.token,
                        snack_session_id: this.selectedSessionId,
                        snack_inventory_id: this.selectedInventoryId,
                    }),
                });

                const data = await res.json();
                const type = data.success ? 'success' : (data.code === 'duplicate' ? 'duplicate' : 'error');
                this.showFeedback(type, data.message);
            } catch {
                this.isOnline = false;
            } finally {
                this.scanning = false;
                this.token = '';
                this.$nextTick(() => document.getElementById('snack-token-input')?.focus());
            }
        },

        showFeedback(type, message) {
            this.feedback = { type, message };
            if (this.feedbackTimer) clearTimeout(this.feedbackTimer);
            this.feedbackTimer = setTimeout(() => { this.feedback = null; }, 3500);
        },
    };
}
</script>

</body>
</html>
