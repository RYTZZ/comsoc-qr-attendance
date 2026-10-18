<x-layouts.admin :title="'Ticket #' . $ticket->ticket_number">
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <a href="{{ route('admin.support.index') }}" class="text-xs text-[#df747a] hover:underline flex items-center gap-1 mb-1 font-semibold">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                Back to Support Inbox
            </a>
            <div class="flex items-center gap-2">
                <span class="font-mono text-base font-bold text-[#df747a]">{{ $ticket->ticket_number }}</span>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase
                    {{ $ticket->status === 'resolved' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-amber-500/20 text-amber-300' }}">
                    {{ $ticket->status_label }}
                </span>
                @if($ticket->is_escalated)
                <span class="px-2 py-0.5 rounded bg-rose-500/20 text-rose-300 border border-rose-500/30 text-[10px] font-bold uppercase">
                    Escalated: {{ $ticket->escalation_reason }}
                </span>
                @endif
            </div>
            <h1 class="text-xl sm:text-2xl font-extrabold text-white font-brand-display tracking-tight mt-1">
                {{ $ticket->subject }}
            </h1>
        </div>

            <!-- Action buttons: Assign, Resolve, Escalate -->
            <div class="flex flex-wrap items-center gap-2">
                @if($ticket->status !== 'resolved' && $ticket->status !== 'closed')
                <button type="button" onclick="document.getElementById('resolveModal').classList.remove('hidden')" class="btn-primary text-xs py-1.5 px-3">
                    Resolve Ticket
                </button>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-6 space-y-6">

        @if(session('status'))
        <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-semibold flex items-center gap-2">
            <i data-lucide="check-circle" class="w-4 h-4 shrink-0"></i>
            <span>{{ session('status') }}</span>
        </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left 2 Cols: Messages & Form -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Related Entity Details (If Attendance, EXP, or Reward issue) -->
                @if($ticket->attendanceRecord || $ticket->xpTransaction || $ticket->rewardRedemption)
                <div class="card bg-slate-900 border border-blue-500/40 p-4 space-y-2">
                    <h3 class="text-xs font-bold text-blue-400 uppercase tracking-wider flex items-center gap-1.5">
                        <i data-lucide="link" class="w-3.5 h-3.5"></i>
                        Referenced System Record
                    </h3>
                    @if($ticket->attendanceRecord)
                    <div class="text-xs text-slate-300 flex items-center justify-between">
                        <span>Attendance Scan ID: {{ $ticket->attendanceRecord->id }} ({{ $ticket->attendanceRecord->event?->name }})</span>
                        @if(auth()->user()->isSuperAdmin())
                        <a href="{{ route('admin.attendance.index', ['search' => $ticket->attendanceRecord->qr_token]) }}" target="_blank" class="text-blue-400 hover:underline">
                            Open in Master Attendance →
                        </a>
                        @endif
                    </div>
                    @endif

                    @if($ticket->xpTransaction)
                    <div class="text-xs text-slate-300">
                        EXP Transaction: {{ $ticket->xpTransaction->amount }} EXP ({{ $ticket->xpTransaction->description }})
                    </div>
                    @endif

                    @if($ticket->rewardRedemption)
                    <div class="text-xs text-slate-300">
                        Reward Redemption: {{ $ticket->rewardRedemption->reward?->title }} (Status: {{ $ticket->rewardRedemption->status }})
                    </div>
                    @endif
                </div>
                @endif

                <!-- Thread Messages -->
                <div class="space-y-4">
                    @foreach($messages as $msg)
                    @php $isStaff = $msg->user->isAdmin() || $msg->user->isStaff(); @endphp
                    <div class="card p-5 {{ $msg->is_internal_note ? 'bg-amber-950/20 border border-amber-500/40' : ($isStaff ? 'bg-slate-900 border-slate-800' : 'bg-slate-950 border border-slate-800') }}">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-lg {{ $isStaff ? 'bg-rose-900 text-white' : 'bg-slate-800 text-slate-300' }} flex items-center justify-center text-xs font-bold">
                                    {{ substr($msg->user->name, 0, 1) }}
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-white">
                                        {{ $msg->user->name }}
                                        @if($isStaff)
                                        <span class="text-[10px] px-1.5 py-0.2 rounded bg-rose-500/20 text-rose-300 font-semibold border border-rose-500/30">Staff</span>
                                        @else
                                        <span class="text-[10px] text-slate-400">Student</span>
                                        @endif
                                    </p>
                                    <p class="text-[10px] text-slate-400">{{ $msg->created_at->format('M d, Y H:i') }}</p>
                                </div>
                            </div>

                            @if($msg->is_internal_note)
                            <span class="px-2 py-0.5 rounded bg-amber-500/20 text-amber-300 text-[10px] font-bold uppercase border border-amber-500/30">
                                Internal Staff Note (Invisible to Student)
                            </span>
                            @endif
                        </div>

                        <p class="text-xs text-slate-200 leading-relaxed whitespace-pre-line">{{ $msg->message }}</p>

                        @if($msg->attachment_path)
                        <div class="mt-3 pt-3 border-t border-slate-800">
                            <a href="{{ route('helpdesk.attachment', $msg) }}" class="inline-flex items-center gap-1.5 text-xs text-[#df747a] hover:underline font-semibold bg-slate-950 px-3 py-1.5 rounded-lg border border-slate-800">
                                <i data-lucide="paperclip" class="w-3.5 h-3.5"></i>
                                <span>Download {{ $msg->attachment_name ?: 'Attachment' }}</span>
                            </a>
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>

                <!-- Staff Reply / Note Box -->
                <div class="card bg-slate-900 border border-slate-800 p-6 space-y-4">
                    <h3 class="text-xs font-bold text-white uppercase tracking-wider">Reply or Add Internal Note</h3>
                    <form method="POST" action="{{ route('admin.support.reply', $ticket) }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <textarea name="message" rows="4" required placeholder="Type reply to student or internal staff note..."
                                  class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-xs text-white focus:ring-2 focus:ring-[#8F3033]"></textarea>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="flex items-center gap-2 cursor-pointer text-xs text-slate-300">
                                    <input type="checkbox" name="is_internal_note" value="1" class="rounded bg-slate-950 border-slate-800 text-[#8F3033] focus:ring-[#8F3033]">
                                    <span class="font-bold text-amber-400">Save as Internal Note (Student cannot see)</span>
                                </label>
                            </div>

                            <div>
                                <select name="shift_status" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-1.5 text-xs text-white">
                                    <option value="">Keep current status ({{ $ticket->status_label }})</option>
                                    <option value="in_review">Set to: In Review</option>
                                    <option value="awaiting_member">Set to: Awaiting Member</option>
                                    <option value="resolved">Set to: Resolved</option>
                                    <option value="closed">Set to: Closed</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-2">
                            <input type="file" name="attachment" accept=".png,.jpg,.jpeg,.pdf,.txt" class="text-xs text-slate-400 file:mr-2 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-[11px] file:bg-slate-800 file:text-[#df747a]">
                            <button type="submit" class="btn-primary text-xs py-2 px-5">Submit</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Right Col: Ticket Management Sidebar -->
            <div class="space-y-6">
                <!-- Reassignment Widget -->
                <div class="card bg-slate-900 border border-slate-800 p-5 space-y-4">
                    <h3 class="text-xs font-bold text-white uppercase tracking-wider border-b border-slate-800 pb-2">Assignment & Triage</h3>
                    <form method="POST" action="{{ route('admin.support.assign', $ticket) }}" class="space-y-3">
                        @csrf
                        <div>
                            <label class="block text-[11px] text-slate-400 mb-1">Assign to Officer / Staff:</label>
                            <select name="assigned_to" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                                @foreach($staffUsers as $st)
                                <option value="{{ $st->id }}" {{ $ticket->assigned_to === $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn-secondary text-xs py-1.5 px-3 w-full">Update Assignee</button>
                    </form>

                    @if(!$ticket->is_escalated)
                    <form method="POST" action="{{ route('admin.support.escalate', $ticket) }}" class="pt-3 border-t border-slate-800 space-y-2">
                        @csrf
                        <input type="text" name="escalation_reason" required placeholder="Reason for escalation to Super Admin..."
                               class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-1.5 text-xs text-white">
                        <button type="submit" class="w-full text-xs py-1.5 px-3 rounded-xl bg-rose-500/20 text-rose-300 border border-rose-500/30 hover:bg-rose-500/30 font-bold transition">
                            Escalate to Super Admin
                        </button>
                    </form>
                    @endif
                </div>

                <!-- Member Info -->
                <div class="card bg-slate-900 border border-slate-800 p-5 space-y-3 text-xs">
                    <h3 class="font-bold text-white uppercase tracking-wider border-b border-slate-800 pb-2">Student Information</h3>
                    <p><span class="text-slate-400">Name:</span> <span class="font-bold text-white">{{ $ticket->user?->name }}</span></p>
                    <p><span class="text-slate-400">Student #:</span> <span class="font-mono text-slate-200">{{ $ticket->user?->student?->student_number ?? 'N/A' }}</span></p>
                    <p><span class="text-slate-400">Program / Year:</span> <span class="text-slate-200">{{ $ticket->user?->student?->program ?? 'N/A' }} - Yr {{ $ticket->user?->student?->year_level ?? 'N/A' }}</span></p>
                    <p><span class="text-slate-400">Email:</span> <span class="text-slate-200">{{ $ticket->user?->email }}</span></p>
                </div>

                <!-- Audit History -->
                <div class="card bg-slate-900 border border-slate-800 p-5">
                    <h3 class="text-xs font-bold text-white uppercase tracking-wider mb-3">Audit History</h3>
                    <div class="space-y-3">
                        @foreach($histories as $hist)
                        <div class="text-[11px] border-l-2 border-[#8F3033] pl-3 py-1">
                            <p class="font-bold text-slate-200 capitalize">{{ str_replace('_', ' ', $hist->action) }}</p>
                            <p class="text-slate-400">{{ $hist->notes }}</p>
                            <p class="text-[10px] text-slate-500 mt-0.5">By {{ $hist->actor?->name ?? 'System' }} • {{ $hist->created_at->format('M d, H:i') }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Modal for Resolution -->
    <div id="resolveModal" class="hidden fixed inset-0 z-50 bg-black/75 flex items-center justify-center p-4">
        <div class="card bg-slate-900 border border-slate-800 p-6 max-w-lg w-full space-y-4">
            <h3 class="text-sm font-bold text-white uppercase tracking-wider">Resolve Support Request</h3>
            <form method="POST" action="{{ route('admin.support.resolve', $ticket) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs text-slate-400 mb-1">Resolution Code *</label>
                    <input type="text" name="resolution_code" required placeholder="e.g. ATTENDANCE_CONFIRMED, EXP_ADJUSTED, QR_REISSUED, REJECTED"
                           class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
                </div>

                <div>
                    <label class="block text-xs text-slate-400 mb-1">Resolution Summary for Student *</label>
                    <textarea name="resolution_summary" rows="4" required placeholder="Describe how the issue was resolved or the outcome of the investigation..."
                              class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-xs text-white"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" onclick="document.getElementById('resolveModal').classList.add('hidden')" class="btn-secondary text-xs py-2 px-4">Cancel</button>
                    <button type="submit" class="btn-primary text-xs py-2 px-5">Confirm Resolution</button>
                </div>
            </form>
        </div>
    </div>
</div>
</x-layouts.admin>
