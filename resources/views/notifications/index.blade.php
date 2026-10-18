<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-black text-white tracking-tight flex items-center gap-3 font-brand-display">
                    <span class="w-2.5 h-8 bg-[#560E0F] rounded-full inline-block"></span>
                    <span>Notifications</span>
                </h2>
                <p class="text-xs text-[#94A3B8] mt-1">
                    Stay updated with real-time alerts on your attendance, tickets, XP, level promotions, and announcements.
                </p>
            </div>

            @if($unreadCount > 0)
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button type="submit" class="btn-secondary text-xs py-2 px-3.5 flex items-center gap-2">
                    <i data-lucide="check-check" class="w-3.5 h-3.5 text-emerald-400"></i>
                    <span>Mark All Read</span>
                </button>
            </form>
            @endif
        </div>
    </x-slot>

    <div class="py-8 bg-[#0B1120]">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm flex items-center gap-3">
                    <i data-lucide="check-circle" class="w-5 h-5 flex-shrink-0"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <div class="flex items-center gap-2 border-b border-[#334155] pb-3">
                <a href="{{ route('notifications.index', ['filter' => 'all']) }}"
                   class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-colors flex items-center gap-2 {{ $filter === 'all' ? 'bg-[#560E0F] text-white border border-[#8F3033] shadow' : 'text-[#94A3B8] hover:text-white hover:bg-[#172033]' }}">
                    <span>All</span>
                </a>

                <a href="{{ route('notifications.index', ['filter' => 'unread']) }}"
                   class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-colors flex items-center gap-2 {{ $filter === 'unread' ? 'bg-[#560E0F] text-white border border-[#8F3033] shadow' : 'text-[#94A3B8] hover:text-white hover:bg-[#172033]' }}">
                    <span>Unread</span>
                    @if($unreadCount > 0)
                    <span class="px-1.5 py-0.2 bg-red-500/30 text-red-200 border border-red-500/40 rounded text-[10px] font-bold">
                        {{ $unreadCount }}
                    </span>
                    @endif
                </a>

                <a href="{{ route('notifications.index', ['filter' => 'read']) }}"
                   class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-colors flex items-center gap-2 {{ $filter === 'read' ? 'bg-[#560E0F] text-white border border-[#8F3033] shadow' : 'text-[#94A3B8] hover:text-white hover:bg-[#172033]' }}">
                    <span>Read History</span>
                </a>
            </div>

            @if($notifications->isEmpty())
                <div class="card bg-[#172033] border border-[#334155] p-12 text-center">
                    <div class="w-14 h-14 mx-auto rounded-full bg-[#0B1120] border border-[#334155] flex items-center justify-center text-[#94A3B8] mb-3">
                        <i data-lucide="bell-off" class="w-7 h-7"></i>
                    </div>
                    <h3 class="text-base font-bold text-white font-brand-display">No Notifications</h3>
                    <p class="text-xs text-[#94A3B8] mt-1 max-w-sm mx-auto">
                        @if($filter === 'unread')
                            You have caught up with all your notifications.
                        @else
                            You do not have any notifications recorded at this time.
                        @endif
                    </p>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($notifications as $notif)
                    @php
                        $isUnread = is_null($notif->read_at);
                        $icon = match($notif->type) {
                            'event_registration' => 'ticket',
                            'attendance_confirmed' => 'check-circle-2',
                            'mission_approved' => 'award',
                            'mission_rejected' => 'alert-triangle',
                            'achievement_unlocked' => 'trophy',
                            'level_up' => 'sparkles',
                            'redemption_fulfilled' => 'gift',
                            'redemption_rejected' => 'rotate-ccw',
                            'balance_adjusted' => 'coins',
                            'announcement' => 'megaphone',
                            default => 'bell',
                        };

                        $iconColor = match($notif->type) {
                            'attendance_confirmed', 'mission_approved', 'redemption_fulfilled' => 'text-emerald-400 bg-emerald-500/10 border-emerald-500/20',
                            'level_up', 'achievement_unlocked' => 'text-amber-400 bg-amber-500/10 border-amber-500/20',
                            'mission_rejected', 'redemption_rejected' => 'text-rose-400 bg-rose-500/10 border-rose-500/20',
                            'event_registration' => 'text-indigo-400 bg-indigo-500/10 border-indigo-500/20',
                            'announcement' => 'text-cyan-400 bg-cyan-500/10 border-cyan-500/20',
                            default => 'text-slate-400 bg-slate-800/50 border-slate-700',
                        };
                    @endphp
                    <div class="p-4 rounded-2xl border transition flex items-start gap-4 {{ $isUnread ? 'bg-[#172033] border-[#8F3033]/60 shadow-lg shadow-[#560E0F]/10' : 'bg-[#172033]/70 border-[#334155] opacity-90' }}">
                        <div class="w-10 h-10 rounded-xl border flex items-center justify-center flex-shrink-0 {{ $iconColor }}">
                            <i data-lucide="{{ $icon }}" class="w-5 h-5"></i>
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2 min-w-0">
                                    <h4 class="text-sm font-bold text-white truncate font-brand-display">{{ $notif->title }}</h4>
                                    @if($isUnread)
                                    <span class="w-2 h-2 rounded-full bg-[#df747a] flex-shrink-0 animate-ping"></span>
                                    @endif
                                </div>
                                <span class="text-[11px] text-[#94A3B8] font-mono flex-shrink-0">{{ $notif->created_at->diffForHumans() }}</span>
                            </div>

                            <p class="text-xs text-slate-300 mt-1 leading-relaxed">
                                {{ $notif->body }}
                            </p>

                            <div class="flex items-center justify-between gap-3 mt-3 pt-2 border-t border-[#334155]">
                                <div>
                                    @if($notif->target_url)
                                    <form method="POST" action="{{ route('notifications.read', $notif) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-xs font-semibold text-[#df747a] hover:text-white flex items-center gap-1 transition">
                                            <span>View Details</span>
                                            <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>

                                <div class="flex items-center gap-2">
                                    @if($isUnread)
                                    <form method="POST" action="{{ route('notifications.read', $notif) }}">
                                        @csrf
                                        <button type="submit" class="text-[11px] text-[#94A3B8] hover:text-white px-2.5 py-1 rounded-lg bg-[#0B1120] border border-[#334155] hover:bg-[#1E293B] transition">
                                            Mark Read
                                        </button>
                                    </form>
                                    @endif

                                    <form method="POST" action="{{ route('notifications.destroy', $notif) }}" onsubmit="return confirm('Delete this notification?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-[11px] text-slate-500 hover:text-red-400 p-1" title="Delete notification">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $notifications->withQueryString()->links() }}
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
