<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <a href="{{ route('activities.index') }}" class="text-xs text-slate-400 hover:text-white inline-flex items-center gap-1 mb-1">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                    Browse COMSOC Activities
                </a>
                <h1 class="text-2xl font-bold text-white font-brand-display tracking-tight">
                    My Activity Registrations & Session Progress
                </h1>
                <p class="text-xs text-slate-400 mt-0.5">
                    Track your parent event registrations, selected sessions, team status, QR attendance, completion, and earned EXP/Points.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <div class="px-3.5 py-2 rounded-xl bg-[#172033] border border-slate-800 text-xs">
                    <span class="text-slate-400">Earned from Activities:</span>
                    <span class="font-bold text-[#dfa6a9] ml-1">+{{ number_format($totalExpEarned) }} EXP</span>
                    <span class="font-bold text-amber-300 ml-1">· +{{ number_format($totalPointsEarned) }} Points</span>
                </div>
                <a href="{{ route('member.dynamic_qr') }}" class="btn-primary text-xs flex items-center gap-1.5">
                    <i data-lucide="qr-code" class="w-4 h-4"></i>
                    <span>Open Attendance QR</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @forelse($registrations as $reg)
                <div class="card bg-[#172033] border border-slate-800 overflow-hidden">
                    <!-- Parent Event Header -->
                    <div class="p-5 bg-[#0B1120]/70 border-b border-slate-800 flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-mono text-xs font-bold text-[#dfa6a9] px-2 py-0.5 rounded bg-[#560E0F]/30 border border-[#560E0F]">
                                    {{ $reg->ticket_code }}
                                </span>
                                <a href="{{ route('activities.show', $reg->activity) }}" class="text-base font-bold text-white hover:text-[#dfa6a9]">
                                    {{ $reg->activity?->title }}
                                </a>
                                <span class="px-2 py-0.5 rounded bg-slate-800 text-[10px] text-slate-300 font-semibold">
                                    {{ $reg->activity?->category }}
                                </span>
                            </div>

                            @if($reg->team)
                                <div class="text-xs text-indigo-300 mt-1">
                                    <strong>Team:</strong> {{ $reg->team->name }}
                                    ({{ $reg->team->members->where('invitation_status', 'accepted')->count() }} accepted members)
                                </div>
                            @endif
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <span class="px-2.5 py-1 rounded-full bg-slate-800 border border-slate-700 text-[10px] font-bold uppercase text-white">
                                Registration: {{ $reg->status }}
                            </span>
                            <span class="px-2.5 py-1 rounded-full bg-slate-800 border border-slate-700 text-[10px] font-bold uppercase {{ $reg->completion_status === 'completed' ? 'text-emerald-400' : 'text-sky-300' }}">
                                Overall: {{ str_replace('_', ' ', $reg->completion_status) }}
                            </span>

                            @if(!in_array($reg->status, ['cancelled', 'withdrawn', 'rejected'], true) && $reg->activity?->allow_cancellation)
                                <form method="POST" action="{{ route('activities.registrations.cancel', $reg) }}">
                                    @csrf
                                    <button type="submit" class="btn-secondary btn-sm text-xs text-rose-300">Cancel</button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <!-- Selected Sessions Table -->
                    <div class="p-5 space-y-4">
                        <div class="text-xs font-bold text-slate-300 uppercase tracking-wider">Selected Sessions & Independent Progress</div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-[#0B1120] text-slate-400 border-b border-slate-800 text-[10px] uppercase">
                                    <tr>
                                        <th class="py-2.5 px-3">Session</th>
                                        <th class="py-2.5 px-3">Schedule & Venue</th>
                                        <th class="py-2.5 px-3">Reg Status</th>
                                        <th class="py-2.5 px-3">QR Attendance</th>
                                        <th class="py-2.5 px-3">Completion</th>
                                        <th class="py-2.5 px-3 text-right">EXP & Points</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800/70">
                                    @foreach($reg->sessionRegistrations as $sr)
                                        <tr>
                                            <td class="py-3 px-3 font-semibold text-white">
                                                {{ $sr->session?->name }}
                                                @if($sr->session?->track_or_category)
                                                    <div class="text-[10px] text-slate-400">{{ $sr->session->track_or_category }}</div>
                                                @endif
                                            </td>
                                            <td class="py-3 px-3 text-slate-300">
                                                <div>{{ $sr->session?->starts_at ? $sr->session->starts_at->format('M d, Y g:i A') : 'TBA' }}</div>
                                                <div class="text-[10px] text-slate-400">{{ $sr->session?->venue ?: $reg->activity?->venue }}</div>
                                            </td>
                                            <td class="py-3 px-3">
                                                <span class="px-2 py-0.5 rounded bg-slate-800 text-[10px] font-bold uppercase text-slate-200">
                                                    {{ $sr->status }}
                                                </span>
                                            </td>
                                            <td class="py-3 px-3 text-slate-300">
                                                @if($sr->attendance)
                                                    <div>In: {{ $sr->attendance->check_in_at?->format('g:i A') ?? '—' }}</div>
                                                    <div class="text-[10px] text-slate-400">Out: {{ $sr->attendance->check_out_at?->format('g:i A') ?? 'Pending' }}</div>
                                                @else
                                                    <span class="text-slate-500">Not Scanned Yet</span>
                                                @endif
                                            </td>
                                            <td class="py-3 px-3">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $sr->completion_status === 'completed' ? 'bg-emerald-500/20 text-emerald-300' : 'bg-slate-800 text-amber-300' }}">
                                                    {{ str_replace('_', ' ', $sr->completion_status) }}
                                                </span>
                                            </td>
                                            <td class="py-3 px-3 text-right font-semibold">
                                                @if($sr->rewards_awarded)
                                                    <span class="text-emerald-400">+{{ $sr->exp_awarded }} EXP · +{{ $sr->points_awarded }} Pts</span>
                                                @else
                                                    <span class="text-slate-500">{{ $sr->session?->exp_reward ?? 0 }} EXP · {{ $sr->session?->points_reward ?? 0 }} Pts (Upon Completion)</span>
                                                @endif
                                                @if($reg->activity?->allow_session_change && in_array($sr->attendance_status, ['not_recorded', null], true) && $sr->completion_status !== 'completed' && ($reg->activity?->sessions?->count() ?? 0) > 1)
                                                    <form method="POST" action="{{ route('activities.sessions.switch', $sr) }}" class="mt-1.5 flex items-center justify-end gap-1">
                                                        @csrf
                                                        <select name="target_session_id" class="select text-[10px] py-0.5 px-1.5 bg-[#0B1120] border-slate-700 text-slate-300">
                                                            @foreach($reg->activity->sessions as $optSess)
                                                                @if($optSess->id !== $sr->activity_session_id)
                                                                    <option value="{{ $optSess->id }}">{{ $optSess->name }}</option>
                                                                @endif
                                                            @endforeach
                                                        </select>
                                                        <button type="submit" class="px-2 py-0.5 rounded bg-slate-800 border border-slate-700 text-[10px] text-slate-200 hover:bg-slate-700">
                                                            Switch
                                                        </button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @empty
                <div class="card bg-[#172033] border border-slate-800 p-12 text-center">
                    <h3 class="text-base font-bold text-white">No Activity Registrations Yet</h3>
                    <p class="text-xs text-slate-400 mt-1 mb-4">Browse available COMSOC activities and select your sessions to get started.</p>
                    <a href="{{ route('activities.index') }}" class="btn-primary text-xs">Explore Activities</a>
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>

