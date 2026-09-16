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
            <div class="text-3xl sm:text-4xl mb-2">📡</div>
            <p class="font-bold text-slate-200 text-base sm:text-lg">Connection Lost</p>
            <p class="text-slate-400 text-xs sm:text-sm mt-1">Snack scanning is paused.</p>
        </div>

        <div x-show="isOnline" class="space-y-3 sm:space-y-4">
            <div class="card space-y-3">
                <div>
                    <label class="label text-xs">Snack Session *</label>
                    <select x-model="selectedSessionId" @change="loadInventories()" class="select text-xs sm:text-sm">
                        <option value="">-- Select a Snack Session --</option>
                        @foreach($sessions as $session)
                        <option value="{{ $session->id }}" data-inventories="{{ json_encode($session->inventories) }}">
                            {{ $session->event->name }} — {{ $session->name }}
                        </option>
                        @endforeach
                    </select>
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
                }" class="kiosk-feedback shadow-2xl">
                    <div class="text-4xl sm:text-5xl mb-2 sm:mb-3" x-text="feedback?.icon"></div>
                    <p class="font-bold text-lg sm:text-xl text-white" x-text="feedback?.message"></p>
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
        isOnline: true,
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
            const select = document.querySelector(`option[value="${this.selectedSessionId}"]`);
            this.inventories = select ? JSON.parse(select.dataset.inventories || '[]') : [];
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
                const icon = data.success ? '🍪' : (data.code === 'duplicate' ? '⚠️' : '❌');
                const type = data.success ? 'success' : (data.code === 'duplicate' ? 'duplicate' : 'error');
                this.showFeedback(type, icon, data.message);
            } catch {
                this.isOnline = false;
            } finally {
                this.scanning = false;
                this.token = '';
                this.$nextTick(() => document.getElementById('snack-token-input')?.focus());
            }
        },

        showFeedback(type, icon, message) {
            this.feedback = { type, icon, message };
            if (this.feedbackTimer) clearTimeout(this.feedbackTimer);
            this.feedbackTimer = setTimeout(() => { this.feedback = null; }, 3500);
        },
    };
}
</script>

</body>
</html>
