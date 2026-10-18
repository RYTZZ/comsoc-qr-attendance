<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('announcements.index') }}" class="p-2 rounded-lg bg-slate-800 text-slate-400 hover:text-white transition">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </a>
            <div>
                <span class="text-xs text-slate-400 uppercase tracking-wider font-semibold">Computing Society Announcement</span>
                <h2 class="text-xl font-black text-white tracking-tight">{{ $announcement->title }}</h2>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="card bg-[#172033] border border-slate-800 p-6 sm:p-8 rounded-2xl shadow-xl space-y-6">

                <div class="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-slate-800">
                    <div class="flex items-center gap-2">
                        @php
                            $priorityBadge = match($announcement->priority) {
                                'urgent' => 'bg-rose-500/20 text-rose-300 border-rose-500/40',
                                'high' => 'bg-amber-500/20 text-amber-300 border-amber-500/40',
                                'low' => 'bg-slate-800 text-slate-400 border-slate-700',
                                default => 'bg-blue-500/20 text-blue-300 border-blue-500/30',
                            };
                        @endphp
                        <span class="px-3 py-1 rounded-full text-xs font-bold border uppercase tracking-wider {{ $priorityBadge }}">
                            {{ $announcement->priority }} Priority
                        </span>
                        <span class="px-3 py-1 rounded-full text-xs font-medium bg-slate-800 text-slate-300 border border-slate-700">
                            {{ ucfirst($announcement->category) }}
                        </span>
                    </div>

                    <div class="text-xs text-slate-400">
                        Published {{ $announcement->published_at ? $announcement->published_at->format('M d, Y h:i A') : $announcement->created_at->format('M d, Y h:i A') }}
                    </div>
                </div>

                <div class="text-slate-200 text-sm sm:text-base leading-relaxed whitespace-pre-line">
                    {{ $announcement->body }}
                </div>

                @if($announcement->target_url)
                <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h4 class="text-xs font-bold text-white uppercase tracking-wider">Related Link / Action</h4>
                        <p class="text-xs text-slate-400 truncate max-w-md">{{ $announcement->target_url }}</p>
                    </div>

                    <a href="{{ $announcement->target_url }}" target="_blank" rel="noopener noreferrer"
                       class="btn-primary text-xs py-2 px-4 flex items-center gap-1.5 self-start sm:self-auto">
                        <span>Open Link</span>
                        <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                    </a>
                </div>
                @endif

                @if($announcement->requires_acknowledgment)
                <div class="p-4 rounded-xl {{ $receipt && $receipt->acknowledged_at ? 'bg-emerald-950/30 border border-emerald-500/40' : 'bg-amber-950/20 border border-amber-500/40' }} flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h4 class="text-xs font-bold {{ $receipt && $receipt->acknowledged_at ? 'text-emerald-400' : 'text-amber-400' }} uppercase tracking-wider">
                            {{ $receipt && $receipt->acknowledged_at ? 'Formal Acknowledgment Confirmed' : 'Acknowledgment Required' }}
                        </h4>
                        <p class="text-xs text-slate-300 mt-0.5">
                            {{ $receipt && $receipt->acknowledged_at ? 'You acknowledged this official announcement on ' . $receipt->acknowledged_at->format('M d, Y H:i') : 'The COMSOC executive board requires all eligible student members to confirm receipt of this directive.' }}
                        </p>
                    </div>

                    @if(!$receipt || !$receipt->acknowledged_at)
                    <form method="POST" action="{{ route('announcements.acknowledge', $announcement) }}">
                        @csrf
                        <button type="submit" class="btn-primary text-xs py-2 px-5 flex items-center gap-1.5 shadow-lg">
                            <i data-lucide="check" class="w-4 h-4"></i>
                            <span>I Acknowledge</span>
                        </button>
                    </form>
                    @endif
                </div>
                @endif

                <div class="pt-4 border-t border-slate-800 flex items-center justify-between text-xs text-slate-500">
                    <div>
                        Author: {{ $announcement->poster?->name ?? 'Executive Board' }}
                    </div>

                    @if($announcement->expires_at)
                    <div>
                        Expires on {{ $announcement->expires_at->format('M d, Y') }}
                    </div>
                    @endif
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
