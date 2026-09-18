<x-layouts.admin :title="'Card Management'">
<div class="page-header">
    <div>
        <h1 class="page-title">Physical ID Cards</h1>
        <p class="text-xs text-slate-400 mt-1">Track physical PVC/laminated membership cards, claims, and replacements.</p>
    </div>
    <div class="flex items-center gap-2">
        <button type="button" onclick="window.print()" class="btn-secondary btn-sm flex items-center gap-1.5 text-xs">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            Print Sheet (Grid Mode)
        </button>
    </div>
</div>

<div class="card p-5 mb-6 border-brand-500/20 bg-gradient-to-br from-slate-900 via-slate-900 to-brand-950/30">
    <div class="flex flex-col md:flex-row items-center justify-between gap-6">
        <div>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-brand-500/10 text-brand-400 border border-brand-500/20 uppercase tracking-wider mb-2">
                Official PVC / Laminated ID Card Specification
            </span>
            <h2 class="text-base font-bold text-white">Physical Card Layout Standard</h2>
            <p class="text-xs text-slate-400 mt-1 max-w-md">
                Standard CR80 membership cards feature the official Computing Society emblem, student credentials, academic year, and un-obscured attendance QR pass.
            </p>
        </div>

        <div class="w-72 h-44 rounded-2xl p-4 bg-gradient-to-br from-slate-900 to-slate-950 border-2 border-brand-500/40 shadow-2xl relative flex flex-col justify-between overflow-hidden">
            <div class="absolute -right-6 -bottom-6 w-28 h-28 bg-brand-500/10 rounded-full blur-xl pointer-events-none"></div>

            <div class="flex items-center justify-between relative z-10 border-b border-slate-800 pb-2">
                <div class="flex items-center gap-2">
                    <img src="{{ asset('images/COMSOC.png') }}" alt="Computing Society Logo" class="w-8 h-8 object-contain">
                    <div>
                        <p class="text-xs font-bold text-white tracking-tight leading-tight">Computing Society</p>
                        <p class="text-[9px] text-amber-400 font-semibold tracking-wider uppercase">Official Member</p>
                    </div>
                </div>
                <span class="text-[10px] font-mono text-slate-400">AY 2025–2026</span>
            </div>

            <div class="flex items-center justify-between gap-3 relative z-10 my-auto">
                <div class="min-w-0">
                    <p class="text-xs font-bold text-white truncate">JUAN DELA CRUZ</p>
                    <p class="text-[10px] font-mono text-brand-400">2023-10023-BN-0</p>
                    <p class="text-[9px] text-slate-400 mt-0.5">BSIT • 3rd Year</p>
                </div>

                <div class="w-14 h-14 bg-white rounded-lg p-1 shrink-0 flex items-center justify-center shadow">
                    <svg class="w-full h-full text-slate-950" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M3 3h8v8H3V3zm2 2v4h4V5H5zm8-2h8v8h-8V3zm2 2v4h4V5h-4zM3 13h8v8H3v-8zm2 2v4h4v-4H5zm13-2h3v2h-3v-2zm-5 5h2v3h-2v-3zm2-3h3v2h-3v-2zm3 3h3v3h-3v-3z"/>
                    </svg>
                </div>
            </div>

            <div class="flex items-center justify-between text-[8px] text-slate-500 relative z-10 pt-1 border-t border-slate-800/80">
                <span>MEM-2025-0042</span>
                <span>AUTHORIZED ATTENDANCE PASS</span>
            </div>
        </div>
    </div>
</div>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs table-sticky-header">
            <thead class="bg-slate-950/60 text-slate-400 border-b border-slate-800">
                <tr>
                    <th class="py-3 px-4">Card / Batch UID</th>
                    <th class="py-3 px-4">Student</th>
                    <th class="py-3 px-4">Student #</th>
                    <th class="py-3 px-4">Program & Year</th>
                    <th class="py-3 px-4">Status</th>
                    <th class="py-3 px-4">Claimed At</th>
                    <th class="py-3 px-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
                @forelse($cards as $card)
                @php
                    $cardStudent = $card->qrCode?->membership?->student;
                @endphp
                    <tr class="hover:bg-slate-800/30 transition">
                        <td class="py-3 px-4 font-mono font-semibold text-white">{{ $card->card_uid ?? 'CARD-' . str_pad($card->id, 5, '0', STR_PAD_LEFT) }}</td>
                        <td class="py-3 px-4 text-slate-200">{{ $cardStudent?->display_name ?? '—' }}</td>
                        <td class="py-3 px-4 text-slate-400 font-mono">{{ $cardStudent?->student_number ?? '—' }}</td>
                        <td class="py-3 px-4">
                            @if($cardStudent)
                                <span class="text-white font-medium">{{ $cardStudent->program ?: 'BSIT' }}</span>
                                <span class="text-slate-500">•</span>
                                <span class="text-slate-400">{{ $cardStudent->year_level ?: '—' }}</span>
                            @else
                                <span class="text-slate-500">—</span>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            @if($card->status === 'claimed')
                                <span class="badge badge-active inline-flex items-center gap-1">
                                    <svg class="w-3 h-3 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    Claimed
                                </span>
                            @elseif($card->status === 'lost')
                                <span class="badge badge-rejected inline-flex items-center gap-1">
                                    <svg class="w-3 h-3 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    Lost
                                </span>
                            @elseif($card->status === 'reissued')
                                <span class="badge bg-purple-950 text-purple-300 ring-1 ring-purple-500/30 inline-flex items-center gap-1">
                                    <svg class="w-3 h-3 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    Reissued
                                </span>
                            @else
                                <span class="badge badge-pending inline-flex items-center gap-1">
                                    <svg class="w-3 h-3 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2"></circle><polyline points="12 6 12 12 16 14" stroke-width="2"></polyline></svg>
                                    For Claiming
                                </span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-right space-x-1">
                            <a href="{{ route('admin.cards.download', $card) }}" class="btn-secondary btn-sm text-brand-400 border-brand-500/20 inline-flex items-center gap-1" title="Download Official Member Card">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                Card
                            </a>
                            @if($card->status !== 'claimed')
                                <form method="POST" action="{{ route('admin.cards.claim', $card) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="btn-primary btn-sm">Mark Claimed</button>
                                </form>
                            @endif
                            @if($card->status === 'claimed')
                                <form method="POST" action="{{ route('admin.cards.lost', $card) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="btn-secondary btn-sm text-red-400 border-red-500/20">Mark Lost</button>
                                </form>
                            @endif
                            @if($card->status === 'lost')
                                <form method="POST" action="{{ route('admin.cards.reissue', $card) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="btn-secondary btn-sm text-brand-400 border-brand-500/20">Reissue Card</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-500">No physical cards generated yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($cards, 'links'))
        <div class="p-4 border-t border-slate-800">
            {{ $cards->links() }}
        </div>
    @endif
</div>
</x-layouts.admin>
