<x-layouts.admin :title="'Academic Semester Report'">
    <div class="space-y-6">

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-white tracking-tight">Academic Semester & Cycle Report</h1>
                <p class="text-xs text-slate-400 mt-1">
                    Comprehensive reporting for Computing Society activities, member engagement, attendance, and reward economy by term.
                </p>
            </div>

            <form method="GET" action="{{ route('admin.seasons.report') }}" class="flex items-center gap-2">
                @php
                    $seasonOpts = [];
                    foreach($all_seasons as $s) {
                        $seasonOpts[] = ['value' => $s->id, 'label' => $s->name . ' (' . $s->academic_year_label . ')'];
                    }
                @endphp
                <div class="w-64">
                    <x-custom-dropdown
                        name="season_id"
                        :options="$seasonOpts"
                        :selected="$season?->id"
                        placeholder="Select Cycle"
                        onchange="this.closest('form').submit()"
                    />
                </div>
                <button type="submit" class="btn-primary text-xs py-2 px-3">
                    Filter
                </button>
            </form>
        </div>

        @if($season)
        <div class="card p-4 bg-[#172033] border border-slate-800 flex flex-wrap items-center justify-between gap-4 text-xs">
            <div>
                <span class="text-slate-400">Reporting Term:</span>
                <span class="font-bold text-white ml-1">{{ $season->name }}</span>
                <span class="text-slate-500 font-mono ml-2">({{ $season->academic_year_label }})</span>
            </div>
            <div>
                <span class="text-slate-400">Effective Window:</span>
                <span class="font-mono text-slate-300 ml-1">{{ $start_date->format('M d, Y') }} &mdash; {{ $end_date->format('M d, Y') }}</span>
            </div>
        </div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="card p-5 bg-[#172033] border border-slate-800">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Events Organized</span>
                    <i data-lucide="calendar" class="w-5 h-5 text-indigo-400"></i>
                </div>
                <span class="text-2xl font-bold font-mono text-white">{{ number_format($events_count) }}</span>
                <p class="text-[11px] text-slate-400 mt-1">{{ number_format($registrations_count) }} total registrations</p>
            </div>

            <div class="card p-5 bg-[#172033] border border-slate-800">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Attendance Scans</span>
                    <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-400"></i>
                </div>
                <span class="text-2xl font-bold font-mono text-white">{{ number_format($attendance_scans_count) }}</span>
                <p class="text-[11px] text-slate-400 mt-1">Verified check-in records</p>
            </div>

            <div class="card p-5 bg-[#172033] border border-slate-800">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Missions Completed</span>
                    <i data-lucide="target" class="w-5 h-5 text-amber-400"></i>
                </div>
                <span class="text-2xl font-bold font-mono text-white">{{ number_format($missions_approved_count) }}</span>
                <p class="text-[11px] text-slate-400 mt-1">{{ number_format($missions_created_count) }} missions hosted</p>
            </div>

            <div class="card p-5 bg-[#172033] border border-slate-800">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Rewards Fulfilled</span>
                    <i data-lucide="gift" class="w-5 h-5 text-cyan-400"></i>
                </div>
                <span class="text-2xl font-bold font-mono text-white">{{ number_format($redemptions_fulfilled) }}</span>
                <p class="text-[11px] text-slate-400 mt-1">{{ number_format($redemptions_count) }} total claim requests</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="card p-5 bg-[#172033] border border-slate-800 space-y-3">
                <h2 class="text-sm font-bold text-white uppercase tracking-wider font-brand-display">Economy Progression In-Window</h2>
                <div class="space-y-2">
                    <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800 flex items-center justify-between text-xs">
                        <span class="text-slate-400">Total XP Distributed:</span>
                        <span class="font-bold text-[#dfa6a9] font-mono text-sm">+{{ number_format($xp_distributed) }} XP</span>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800 flex items-center justify-between text-xs">
                        <span class="text-slate-400">Total Points Awarded:</span>
                        <span class="font-bold text-amber-300 font-mono text-sm">+{{ number_format($points_distributed) }} pts</span>
                    </div>
                </div>
            </div>

            <div class="card p-5 bg-[#172033] border border-slate-800 space-y-3">
                <h2 class="text-sm font-bold text-white uppercase tracking-wider font-brand-display">Lifecycle Traceability</h2>
                <p class="text-xs text-slate-400 leading-relaxed">
                    This semester summary provides isolated analytics strictly calculated between the active term's start and end timestamps. Generating semester and term reports does not alter member balances or trigger season resets.
                </p>
                <div class="pt-2">
                    <a href="{{ route('admin.reports.index') }}" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300 flex items-center gap-1">
                        <span>Open General Reports & CSV Exports</span>
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                    </a>
                </div>
            </div>
        </div>

    </div>
</x-layouts.admin>
