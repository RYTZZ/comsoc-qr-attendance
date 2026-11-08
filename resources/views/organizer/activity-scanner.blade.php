<x-app-layout>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
<div class="page-header mb-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <span class="px-2 py-0.5 rounded bg-emerald-500/20 border border-emerald-500/40 text-emerald-300 text-[10px] font-bold uppercase">
                Session-Specific QR Attendance
            </span>
            <h1 class="page-title font-heading text-2xl font-bold text-white mt-1">
                Multi-Session Activity QR & Manual Token Scanner
            </h1>
            <p class="text-xs text-slate-400">
                Scan participant rolling QR passes or 6-character manual tokens for a specific activity session. Attendance for one session never counts toward another session.
            </p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6"
     x-data="{
         activityId: '{{ $selectedActivity?->id }}',
         sessionId: '{{ $selectedSession?->id }}',
         qrPayload: '',
         mode: 'auto',
         allowWalkIn: false,
         loading: false,
         lastResult: null,
         async submitScan() {
             if (!this.qrPayload.trim() || !this.activityId || !this.sessionId) return;
             this.loading = true;
             try {
                 const res = await fetch('{{ route('organizer.activity-scanner.scan') }}', {
                     method: 'POST',
                     headers: {
                         'Content-Type': 'application/json',
                         'Accept': 'application/json',
                         'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                     },
                     body: JSON.stringify({
                         activity_id: this.activityId,
                         session_id: this.sessionId,
                         qr_payload: this.qrPayload,
                         mode: this.mode,
                         allow_walk_in: this.allowWalkIn
                     })
                 });
                 this.lastResult = await res.json();
                 this.qrPayload = '';
             } catch (e) {
                 this.lastResult = { success: false, message: 'Network error processing scan.' };
             } finally {
                 this.loading = false;
             }
         }
     }">

    <!-- Scanner Controls -->
    <div class="lg:col-span-2 space-y-6">
        <div class="card bg-[#172033] border border-slate-800 p-5 space-y-4">
            <form method="GET" action="{{ route('organizer.activity-scanner') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">1. Select Parent Activity</label>
                    <select name="activity_id" onchange="this.form.submit()" class="select w-full text-xs bg-[#0B1120] border-slate-700 text-white">
                        @foreach($activities as $act)
                            <option value="{{ $act->id }}" @selected($selectedActivity?->id === $act->id)>{{ $act->title }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">2. Select Target Session</label>
                    <select name="session_id" onchange="this.form.submit()" class="select w-full text-xs bg-[#0B1120] border-slate-700 text-white">
                        @if($selectedActivity)
                            @foreach($selectedActivity->sessions as $sess)
                                <option value="{{ $sess->id }}" @selected($selectedSession?->id === $sess->id)>
                                    {{ $sess->name }} ({{ $sess->exp_reward }} EXP / {{ $sess->points_reward }} Pts)
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>
            </form>

            @if($selectedSession)
                @php
                    $sessionCheckInCount = $recentAttendances->whereNotNull('check_in_at')->count();
                    $sessionCheckOutCount = $recentAttendances->whereNotNull('check_out_at')->count();
                    $sessionRemainingSlots = $selectedSession->remainingSlots();
                @endphp
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                    <div class="p-3 rounded-xl bg-[#0B1120] border border-slate-800">
                        <span class="text-[10px] uppercase text-slate-400 block">Checked In</span>
                        <span class="text-lg font-extrabold text-emerald-400 font-mono-tabular">{{ $sessionCheckInCount }}</span>
                    </div>
                    <div class="p-3 rounded-xl bg-[#0B1120] border border-slate-800">
                        <span class="text-[10px] uppercase text-slate-400 block">Checked Out</span>
                        <span class="text-lg font-extrabold text-sky-400 font-mono-tabular">{{ $sessionCheckOutCount }}</span>
                    </div>
                    <div class="p-3 rounded-xl bg-[#0B1120] border border-slate-800">
                        <span class="text-[10px] uppercase text-slate-400 block">Session Reward</span>
                        <span class="text-sm font-extrabold text-[#dfa6a9] font-mono-tabular">+{{ $selectedSession->exp_reward }} XP / +{{ $selectedSession->points_reward }} P</span>
                    </div>
                    <div class="p-3 rounded-xl bg-[#0B1120] border border-slate-800">
                        <span class="text-[10px] uppercase text-slate-400 block">Remaining Slots</span>
                        <span class="text-lg font-extrabold text-amber-300 font-mono-tabular">{{ $sessionRemainingSlots ?? '∞' }}</span>
                    </div>
                </div>

                <div class="p-4 rounded-xl bg-[#0B1120] border border-slate-800 space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <div class="text-sm font-bold text-white">{{ $selectedSession->name }}</div>
                            <div class="text-[11px] text-slate-400">
                                Venue: {{ $selectedSession->venue ?: 'Default' }} · Reward: +{{ $selectedSession->exp_reward }} EXP / +{{ $selectedSession->points_reward }} Pts
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <select x-model="mode" class="select text-xs bg-[#172033] border-slate-700 text-white">
                                <option value="auto">Auto (Check-In then Check-Out)</option>
                                <option value="in">Force Check-In</option>
                                <option value="out">Force Check-Out</option>
                            </select>
                            <label class="inline-flex items-center gap-1.5 text-xs text-slate-300">
                                <input type="checkbox" x-model="allowWalkIn" class="rounded bg-slate-900 border-slate-700 text-[#560E0F]">
                                <span>Allow Walk-In</span>
                            </label>
                        </div>
                    </div>

                    <form @submit.prevent="submitScan()" class="flex gap-2">
                        <input type="text"
                               x-model="qrPayload"
                               placeholder="Scan QR payload or enter 6-char manual token (e.g., 7K9-MP4)..."
                               autofocus
                               class="input flex-1 text-sm font-mono bg-[#172033] border-slate-700 text-white">
                        <button type="submit" :disabled="loading" class="btn-primary text-xs px-6">
                            <span x-text="loading ? 'Verifying...' : 'Record Session Scan'"></span>
                        </button>
                    </form>

                    <template x-if="lastResult">
                        <div class="p-4 rounded-xl border text-xs"
                             :class="lastResult.success ? 'bg-emerald-500/15 border-emerald-500/40 text-emerald-200' : 'bg-rose-500/15 border-rose-500/40 text-rose-200'">
                            <div class="font-bold text-sm" x-text="lastResult.message"></div>
                        </div>
                    </template>
                </div>
            @endif
        </div>
    </div>

    <!-- Recent Session Attendance Log -->
    <div class="card bg-[#172033] border border-slate-800 p-5 space-y-3">
        <h3 class="text-sm font-bold text-white uppercase tracking-wider border-b border-slate-800 pb-2">
            Recent Scans ({{ $selectedSession?->name ?? 'Session' }})
        </h3>
        <div class="space-y-2 max-h-96 overflow-y-auto">
            @forelse($recentAttendances as $att)
                <div class="p-2.5 rounded-lg bg-[#0B1120] border border-slate-800 text-xs flex items-center justify-between">
                    <div>
                        <div class="font-bold text-white">{{ $att->user?->name }}</div>
                        <div class="text-[10px] text-slate-400">
                            In: {{ $att->check_in_at?->format('g:i A') ?? '—' }} · Out: {{ $att->check_out_at?->format('g:i A') ?? '—' }}
                        </div>
                    </div>
                    <span class="px-2 py-0.5 rounded bg-slate-800 text-[10px] font-bold uppercase text-emerald-400">
                        {{ $att->check_out_at ? 'Checked Out' : $att->check_in_status }}
                    </span>
                </div>
            @empty
                <p class="text-xs text-slate-500 text-center py-6">No scans recorded for this session yet.</p>
            @endforelse
        </div>
    </div>
</div>
</div>
</x-app-layout>

