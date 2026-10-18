<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <a href="{{ route('helpdesk.index') }}" class="text-xs text-[#df747a] hover:underline flex items-center gap-1 mb-1 font-semibold">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                    Back to Tickets
                </a>
                <div class="flex items-center gap-2">
                    <span class="font-mono text-sm font-bold text-[#df747a]">{{ $ticket->ticket_number }}</span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase
                        {{ $ticket->status === 'resolved' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : ($ticket->status === 'in_review' ? 'bg-blue-500/20 text-blue-300 border border-blue-500/30' : 'bg-amber-500/20 text-amber-300 border border-amber-500/30') }}">
                        {{ $ticket->status_label }}
                    </span>
                </div>
                <h1 class="text-xl sm:text-2xl font-extrabold text-[#F8FAFC] font-brand-display tracking-tight mt-1">
                    {{ $ticket->subject }}
                </h1>
            </div>

            @if($ticket->status === 'resolved')
            <div class="px-3 py-1.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-semibold">
                ✓ Resolved by Staff ({{ $ticket->resolved_at?->format('M d, Y') }})
            </div>
            @endif
        </div>
    </x-slot>

    <div class="py-8 bg-[#0B1120]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if(session('status'))
            <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-semibold flex items-center gap-2">
                <i data-lucide="check-circle" class="w-4 h-4 shrink-0"></i>
                <span>{{ session('status') }}</span>
            </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Conversation Thread -->
                <div class="lg:col-span-2 space-y-6">

                    @if($ticket->status === 'resolved' && $ticket->resolution_summary)
                    <div class="p-5 rounded-2xl bg-emerald-950/30 border border-emerald-500/40">
                        <div class="flex items-center gap-2 text-emerald-400 mb-1">
                            <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                            <h3 class="text-xs font-bold uppercase tracking-wider">Official Resolution Summary</h3>
                        </div>
                        <p class="text-xs text-emerald-100 font-medium leading-relaxed">{{ $ticket->resolution_summary }}</p>
                        @if($ticket->resolution_code)
                        <span class="inline-block mt-2 px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300 text-[10px] font-mono">
                            Resolution Code: {{ $ticket->resolution_code }}
                        </span>
                        @endif
                    </div>
                    @endif

                    <!-- Message History -->
                    <div class="space-y-4">
                        @foreach($messages as $msg)
                        @php $isSelf = $msg->user_id === Auth::id(); @endphp
                        <div class="card p-5 {{ $msg->is_internal_note ? 'bg-amber-950/20 border-amber-500/40' : ($isSelf ? 'bg-[#172033] border-[#334155]' : 'bg-[#0B1120] border-[#8F3033]/50') }}">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-lg {{ $isSelf ? 'bg-[#560E0F]' : 'bg-slate-800' }} flex items-center justify-center text-xs font-bold text-white">
                                        {{ substr($msg->user->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-white font-brand-display">
                                            {{ $msg->user->name }}
                                            @if($msg->user->isAdmin() || $msg->user->isStaff())
                                            <span class="text-[10px] px-1.5 py-0.2 rounded bg-rose-500/20 text-rose-300 font-semibold border border-rose-500/30">Staff</span>
                                            @endif
                                        </p>
                                        <p class="text-[10px] text-slate-400">{{ $msg->created_at->format('M d, Y H:i') }} ({{ $msg->created_at->diffForHumans() }})</p>
                                    </div>
                                </div>

                                @if($msg->is_internal_note)
                                <span class="px-2 py-0.5 rounded bg-amber-500/20 text-amber-300 text-[10px] font-bold uppercase border border-amber-500/30">
                                    Staff Internal Note
                                </span>
                                @endif
                            </div>

                            <p class="text-xs text-slate-200 leading-relaxed whitespace-pre-line">{{ $msg->message }}</p>

                            @if($msg->attachment_path)
                            <div class="mt-3 pt-3 border-t border-[#334155]/60">
                                <a href="{{ route('helpdesk.attachment', $msg) }}" class="inline-flex items-center gap-1.5 text-xs text-[#df747a] hover:underline font-semibold bg-[#0B1120] px-3 py-1.5 rounded-lg border border-[#334155]">
                                    <i data-lucide="paperclip" class="w-3.5 h-3.5"></i>
                                    <span>Download {{ $msg->attachment_name ?: 'Attachment' }}</span>
                                </a>
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>

                    <!-- Reply Box -->
                    @if($ticket->status !== 'closed')
                    <div class="card bg-[#172033] border border-[#334155] p-6">
                        <h3 class="text-xs font-bold text-white uppercase tracking-wider mb-3">Post a Reply</h3>
                        <form method="POST" action="{{ route('helpdesk.reply', $ticket) }}" enctype="multipart/form-data" class="space-y-4">
                            @csrf
                            <textarea name="message" rows="4" required placeholder="Type your follow-up message or response to staff here..."
                                      class="w-full bg-[#0B1120] border border-[#334155] rounded-xl p-3 text-xs text-white focus:ring-2 focus:ring-[#8F3033]"></textarea>
                            @error('message') <p class="text-xs text-rose-400 mt-1">{{ $message }}</p> @enderror

                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-2">
                                <div>
                                    <input type="file" name="attachment" accept=".png,.jpg,.jpeg,.pdf,.txt" class="text-xs text-slate-400 file:mr-2 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-[11px] file:bg-[#1E293B] file:text-[#df747a]">
                                </div>
                                <button type="submit" class="btn-primary text-xs py-2 px-5 shrink-0 shadow-lg">Send Reply</button>
                            </div>
                        </form>
                    </div>
                    @endif
                </div>

                <!-- Ticket Meta & Audit Log -->
                <div class="space-y-6">
                    <div class="card bg-[#172033] border border-[#334155] p-5 space-y-4">
                        <h3 class="text-xs font-bold text-white uppercase tracking-wider border-b border-[#334155] pb-2">Ticket Details</h3>

                        <div class="text-xs space-y-2.5">
                            <div>
                                <span class="text-[#94A3B8] block text-[11px]">Category</span>
                                <span class="font-semibold text-white">{{ $ticket->category_label }}</span>
                            </div>
                            <div>
                                <span class="text-[#94A3B8] block text-[11px]">Priority</span>
                                <span class="font-semibold capitalize text-white">{{ $ticket->priority }}</span>
                            </div>
                            <div>
                                <span class="text-[#94A3B8] block text-[11px]">Filed On</span>
                                <span class="text-slate-300">{{ $ticket->created_at->format('M d, Y H:i:s') }}</span>
                            </div>
                            <div>
                                <span class="text-[#94A3B8] block text-[11px]">Assigned Staff</span>
                                <span class="text-slate-300">{{ $ticket->assignedStaff ? $ticket->assignedStaff->name : 'Queue / Not yet assigned' }}</span>
                            </div>
                            @if($ticket->event)
                            <div>
                                <span class="text-[#94A3B8] block text-[11px]">Related Event</span>
                                <span class="text-slate-300">{{ $ticket->event->name }}</span>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Audit Trail / History -->
                    <div class="card bg-[#172033] border border-[#334155] p-5">
                        <h3 class="text-xs font-bold text-white uppercase tracking-wider mb-3">Status History & Audit Trail</h3>
                        <div class="space-y-3">
                            @foreach($histories as $hist)
                            <div class="text-[11px] border-l-2 border-[#8F3033] pl-3 py-1">
                                <p class="font-bold text-slate-200 capitalize">{{ str_replace('_', ' ', $hist->action) }}</p>
                                <p class="text-[#94A3B8]">{{ $hist->notes }}</p>
                                <p class="text-[10px] text-slate-500 mt-0.5">{{ $hist->created_at->format('M d, H:i') }}</p>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
