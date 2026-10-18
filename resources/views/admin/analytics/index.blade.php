<x-layouts.admin :title="'Event Analytics & Impact Reports'">
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-white font-brand-display tracking-tight">
                Event Analytics & Impact Reports
            </h1>
            <p class="text-xs text-slate-400 mt-1">
                Evidence-based insights into attendance performance, participant engagement, volunteer hours, and EXP distribution.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.analytics.export.csv', request()->query()) }}" class="btn-primary text-xs flex items-center gap-1.5 shadow-md">
                <i data-lucide="download" class="w-4 h-4"></i>
                <span>Export Filtered CSV</span>
            </a>
        </div>
    </div>

    <div class="py-6 space-y-6">

        <!-- Filters Form -->
        <div class="card bg-slate-900 border border-slate-800 p-4">
            <form method="GET" action="{{ route('admin.analytics.index') }}" class="grid grid-cols-1 sm:grid-cols-5 gap-3">
                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1">Target Event</label>
                    <select name="event_id" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                        <option value="">All Events (Aggregate)</option>
                        @foreach($events as $ev)
                        <option value="{{ $ev->id }}" {{ $selectedEventId === $ev->id ? 'selected' : '' }}>{{ $ev->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1">Academic Year</label>
                    <select name="academic_year_id" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                        <option value="">All Academic Years</option>
                        @foreach($academicYears as $ay)
                        <option value="{{ $ay->id }}" {{ $academicYearId === $ay->id ? 'selected' : '' }}>{{ $ay->label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1">Date Range (From)</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                </div>

                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1">Date Range (To)</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="btn-primary text-xs py-2 px-4 w-full">Apply Filters</button>
                    <a href="{{ route('admin.analytics.index') }}" class="btn-secondary text-xs py-2 px-3">Reset</a>
                </div>
            </form>
        </div>

        <!-- Metric Cards: Participation & Attendance Performance -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="card bg-slate-900 border border-slate-800 p-4">
                <span class="block text-xs text-slate-400">Total Registered</span>
                <span class="text-2xl font-black text-white font-brand-display">{{ number_format($totalRegistrations) }}</span>
                <span class="text-[10px] text-slate-500 mt-1 block">Confirmed: {{ $confirmedRegistrations }}</span>
            </div>

            <div class="card bg-slate-900 border border-emerald-500/30 p-4">
                <span class="block text-xs text-emerald-400">Unique Attendees</span>
                <span class="text-2xl font-black text-emerald-300 font-brand-display">{{ number_format($uniqueAttendees) }}</span>
                <span class="text-[10px] text-emerald-500 mt-1 block">Authoritative verified students</span>
            </div>

            <div class="card bg-slate-900 border border-[#8F3033]/50 p-4">
                <span class="block text-xs text-[#df747a]">Attendance Rate</span>
                <span class="text-2xl font-black text-white font-brand-display">{{ number_format($attendanceRate, 1) }}%</span>
                <span class="text-[10px] text-slate-400 mt-1 block">Unique Attendees / Registrations</span>
            </div>

            <div class="card bg-slate-900 border border-amber-500/30 p-4">
                <span class="block text-xs text-amber-400">No-Show Rate</span>
                <span class="text-2xl font-black text-amber-300 font-brand-display">{{ number_format($noShowRate, 1) }}%</span>
                <span class="text-[10px] text-slate-400 mt-1 block">{{ $noShowCount }} Registered absentees</span>
            </div>
        </div>

        <!-- Secondary Performance Metrics: Check-Ins, Check-Outs, Scans -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="card bg-slate-900 border border-slate-800 p-4">
                <span class="block text-xs text-slate-400">Check-In Scans</span>
                <span class="text-xl font-bold text-white">{{ number_format($checkInScans) }}</span>
            </div>
            <div class="card bg-slate-900 border border-slate-800 p-4">
                <span class="block text-xs text-slate-400">Check-Out Scans</span>
                <span class="text-xl font-bold text-white">{{ number_format($checkOutScans) }}</span>
            </div>
            <div class="card bg-slate-900 border border-slate-800 p-4">
                <span class="block text-xs text-slate-400">Completion Rate</span>
                <span class="text-xl font-bold text-cyan-400">{{ $completionRate }}%</span>
                <span class="text-[10px] text-slate-500">Completed in & out</span>
            </div>
            <div class="card bg-slate-900 border border-slate-800 p-4">
                <span class="block text-xs text-slate-400">Authorized Corrections</span>
                <span class="text-xl font-bold text-purple-400">{{ $correctedScans }}</span>
                <span class="text-[10px] text-slate-500">Audited manual adjustments</span>
            </div>
        </div>

        <!-- Demographic / Program & Year Level Engagement -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Program Breakdown -->
            <div class="card bg-slate-900 border border-slate-800 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xs font-bold text-white uppercase tracking-wider">Attendance by Academic Program</h3>
                    <i data-lucide="graduation-cap" class="w-4 h-4 text-slate-400"></i>
                </div>

                @if($programBreakdown->isEmpty())
                <p class="text-xs text-slate-500 text-center py-6">No program participation data recorded for this selection.</p>
                @else
                <div class="space-y-3">
                    @foreach($programBreakdown as $prog)
                    @php 
                        $pct = $uniqueAttendees > 0 ? round(($prog->attendee_count / $uniqueAttendees) * 100, 1) : 0; 
                    @endphp
                    <div>
                        <div class="flex justify-between text-xs mb-1">
                            <span class="font-semibold text-white">{{ $prog->program ?: 'General IT' }}</span>
                            <span class="text-slate-400">{{ $prog->attendee_count }} attendees ({{ $pct }}%)</span>
                        </div>
                        <div class="w-full bg-slate-950 rounded-full h-2 overflow-hidden border border-slate-800">
                            <div class="bg-burgundy-gradient h-full rounded-full" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            <!-- Year Level Breakdown -->
            <div class="card bg-slate-900 border border-slate-800 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xs font-bold text-white uppercase tracking-wider">Attendance by Year Level</h3>
                    <i data-lucide="layers" class="w-4 h-4 text-slate-400"></i>
                </div>

                @if($yearLevelBreakdown->isEmpty())
                <p class="text-xs text-slate-500 text-center py-6">No year level data recorded for this selection.</p>
                @else
                <div class="space-y-3">
                    @foreach($yearLevelBreakdown as $yr)
                    @php 
                        $pct = $uniqueAttendees > 0 ? round(($yr->attendee_count / $uniqueAttendees) * 100, 1) : 0; 
                    @endphp
                    <div>
                        <div class="flex justify-between text-xs mb-1">
                            <span class="font-semibold text-white">Year Level {{ $yr->year_level }}</span>
                            <span class="text-slate-400">{{ $yr->attendee_count }} attendees ({{ $pct }}%)</span>
                        </div>
                        <div class="w-full bg-slate-950 rounded-full h-2 overflow-hidden border border-slate-800">
                            <div class="bg-amber-500 h-full rounded-full" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

        <!-- Feedback & EXP Distribution Section -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Event Feedback Survey Insights -->
            <div class="lg:col-span-2 card bg-slate-900 border border-slate-800 p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-xs font-bold text-white uppercase tracking-wider">Student Feedback & Impact Scores</h3>
                        <p class="text-[11px] text-slate-400 mt-0.5">Authoritative responses from verified attendees</p>
                    </div>
                    <span class="px-2.5 py-1 rounded bg-slate-950 border border-slate-800 text-xs font-bold text-amber-400">
                        {{ $feedbackCount }} Submissions
                    </span>
                </div>

                <div class="grid grid-cols-3 gap-3 mb-4 text-center">
                    <div class="p-3 bg-slate-950 rounded-xl border border-slate-800">
                        <span class="text-[11px] text-slate-400 block">Overall Satisfaction</span>
                        <span class="text-xl font-black text-amber-300">{{ $averageRating }} / 5.0</span>
                    </div>
                    <div class="p-3 bg-slate-950 rounded-xl border border-slate-800">
                        <span class="text-[11px] text-slate-400 block">Organization & AV</span>
                        <span class="text-xl font-black text-blue-300">{{ $orgRating }} / 5.0</span>
                    </div>
                    <div class="p-3 bg-slate-950 rounded-xl border border-slate-800">
                        <span class="text-[11px] text-slate-400 block">Content & Topic</span>
                        <span class="text-xl font-black text-emerald-300">{{ $contentRating }} / 5.0</span>
                    </div>
                </div>

                @if($feedbackSamples->isNotEmpty())
                <div class="space-y-2 border-t border-slate-800 pt-3">
                    <p class="text-[10px] uppercase font-bold text-slate-400">Recent Attendee Feedback Quotes</p>
                    @foreach($feedbackSamples as $fb)
                    <div class="p-2.5 bg-slate-950 rounded-lg text-xs text-slate-300 italic">
                        "{{ $fb->positive_feedback }}"
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            <!-- EXP and Volunteer Impact -->
            <div class="card bg-slate-900 border border-slate-800 p-6 space-y-4">
                <h3 class="text-xs font-bold text-white uppercase tracking-wider border-b border-slate-800 pb-2">
                    Rewards & Volunteer Impact
                </h3>

                <div class="space-y-3">
                    <div>
                        <span class="text-xs text-slate-400 block">Authoritative EXP Issued</span>
                        <span class="text-2xl font-black text-[#df747a] font-brand-display">{{ number_format($expIssued) }} EXP</span>
                        <p class="text-[10px] text-slate-500 mt-0.5">Credited directly to attendee ledgers for attendance</p>
                    </div>

                    <div class="pt-3 border-t border-slate-800">
                        <span class="text-xs text-slate-400 block">Volunteer Mobilization</span>
                        <span class="text-lg font-bold text-cyan-400">{{ $volunteerStats->volunteer_count ?? 0 }} Staff Volunteers</span>
                        <p class="text-xs text-slate-300 mt-0.5">Approved Service: {{ number_format($volunteerStats->total_hours ?? 0, 1) }} hours</p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
</x-layouts.admin>
