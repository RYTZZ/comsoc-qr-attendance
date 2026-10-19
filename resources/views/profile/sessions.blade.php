<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('profile.edit') }}" class="p-2 rounded-xl bg-[#0B1120] border border-[#334155] text-slate-400 hover:text-white hover:border-slate-500 transition">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                </a>
                <div>
                    <h1 class="text-xl font-bold text-white font-brand-display">Active Sessions & Security</h1>
                    <p class="text-xs text-slate-400 mt-0.5">Manage your active devices and monitor login alerts</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('profile.edit') }}" class="btn-secondary text-xs flex items-center gap-1.5">
                    <i data-lucide="user" class="w-4 h-4"></i>
                    <span>Profile Settings</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-[#0B1120]">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if(session('status'))
            <div class="p-4 rounded-xl bg-emerald-950/40 border border-emerald-500/30 text-emerald-300 text-xs flex items-center gap-3">
                <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-400 shrink-0"></i>
                <span>{{ session('status') }}</span>
            </div>
            @endif

            <div class="card bg-[#172033] border border-[#334155] shadow-xl p-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-[#334155]">
                    <div>
                        <h2 class="text-base font-bold text-white font-brand-display flex items-center gap-2">
                            <i data-lucide="shield-check" class="w-5 h-5 text-[#df747a]"></i>
                            <span>Single Active Device Policy & Session Security</span>
                        </h2>
                        <p class="text-xs text-[#94A3B8] mt-1">
                            ComSoc Hub enforces a strict single active session security policy: when your account signs in on a new device, it becomes the sole authorized session and all prior sessions are revoked immediately.
                        </p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-[#560E0F]/40 text-[#df747a] border border-[#8F3033]/50 w-fit">
                        {{ $sessions->count() }} {{ Str::plural('Active Session', $sessions->count()) }}
                    </span>
                </div>

                <div class="mt-6 space-y-4">
                    @forelse($sessions as $session)
                    @php
                        $isCurrent = $session->isCurrent($currentSessionId);
                    @endphp
                    <div class="p-4 rounded-xl border transition {{ $isCurrent ? 'bg-[#560E0F]/10 border-[#8F3033]/60 ring-1 ring-[#8F3033]/30' : 'bg-[#0B1120] border-[#334155] hover:border-slate-500' }}">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="flex items-start sm:items-center gap-3.5">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 border {{ $isCurrent ? 'bg-[#560E0F] border-[#8F3033] text-white' : 'bg-[#172033] border-[#334155] text-slate-300' }}">
                                    @if($session->device_type === 'Mobile')
                                        <i data-lucide="smartphone" class="w-5 h-5"></i>
                                    @elseif($session->device_type === 'Tablet')
                                        <i data-lucide="tablet" class="w-5 h-5"></i>
                                    @else
                                        <i data-lucide="laptop" class="w-5 h-5"></i>
                                    @endif
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h3 class="text-sm font-bold text-white">
                                            {{ e($session->browser) }} on {{ e($session->platform) }}
                                        </h3>
                                        @if($isCurrent)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-950/80 text-emerald-400 border border-emerald-500/40">
                                            Current Device
                                        </span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-3 text-xs text-slate-400 mt-1 flex-wrap">
                                        <span class="flex items-center gap-1">
                                            <i data-lucide="clock" class="w-3.5 h-3.5 text-slate-500"></i>
                                            Last active: {{ $session->last_active_at ? $session->last_active_at->diffForHumans() : 'Just now' }}
                                        </span>
                                        <span class="text-slate-600">•</span>
                                        <span>First login: {{ $session->first_authenticated_at ? $session->first_authenticated_at->format('M d, Y h:i A') : 'Unknown' }}</span>
                                        @if($session->ip_address)
                                        <span class="text-slate-600">•</span>
                                        <span class="font-mono text-[11px] text-slate-500">IP: {{ e($session->ip_address) }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                @if(!$isCurrent)
                                <form method="POST" action="{{ route('profile.sessions.destroy', $session) }}" onsubmit="return confirm('Revoke this session? This device will be logged out immediately.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-secondary text-xs text-rose-300 hover:text-rose-200 hover:bg-rose-950/40 border-rose-900/50 py-1.5 px-3 flex items-center gap-1.5 transition">
                                        <i data-lucide="log-out" class="w-3.5 h-3.5 text-rose-400"></i>
                                        <span>Revoke Session</span>
                                    </button>
                                </form>
                                @else
                                <span class="text-xs text-emerald-400/90 font-medium px-2 py-1 flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                    <span>Active Now</span>
                                </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-8 text-slate-400 text-xs">
                        No active session records found.
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- Security Guidelines Box -->
            <div class="p-5 rounded-xl bg-[#172033] border border-[#334155] shadow-lg flex items-start gap-4">
                <div class="w-9 h-9 rounded-xl bg-amber-950/40 border border-amber-500/30 text-amber-400 flex items-center justify-center shrink-0">
                    <i data-lucide="info" class="w-5 h-5"></i>
                </div>
                <div class="text-xs space-y-1">
                    <h4 class="font-bold text-white">Security Note on Device Recognition</h4>
                    <p class="text-slate-400 leading-relaxed">
                        Device detection uses privacy-conscious client attributes (browser, operating system, and client type) to detect new logins. Clearing your browser data, using incognito mode, or switching browsers will register as a new session. If you observe an unrecognized session or location, revoke it immediately and update your password.
                    </p>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
