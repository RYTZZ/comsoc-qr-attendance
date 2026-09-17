<x-layouts.admin :title="'QR Codes'">
<div class="page-header">
    <div>
        <h1 class="page-title">Membership QR Codes</h1>
        <p class="text-xs text-slate-400 mt-1">Generate dynamic cryptographic QR codes for verified memberships and export print batches.</p>
    </div>
    <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
        <form method="GET" class="flex gap-2 w-full sm:w-auto">
            @php
                $yearOpts = ['' => 'All Years'];
                foreach($academicYears as $year) {
                    $yearOpts[$year->id] = $year->label;
                }
            @endphp
            <div class="w-full sm:w-44">
                <x-custom-dropdown
                    name="academic_year_id"
                    :options="$yearOpts"
                    :value="request('academic_year_id', '')"
                    placeholder="All Years"
                    :autoSubmit="true"
                    buttonClass="py-2 text-xs" />
            </div>
        </form>
        <a href="{{ route('admin.qr-codes.download', ['academic_year_id' => $filterYearId]) }}" class="btn-secondary text-xs w-full sm:w-auto text-center flex items-center gap-2 justify-center">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Download PNG (ZIP)
        </a>
        <form method="POST" action="{{ route('admin.qr-codes.generate') }}" class="w-full sm:w-auto">
            @csrf
            <input type="hidden" name="academic_year_id" value="{{ $filterYearId }}">
            <button type="submit" class="btn-primary text-xs w-full justify-center"
                    onclick="return confirm('Generate missing QR codes for active memberships in the selected academic year?')">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Generate Missing QR Codes
            </button>
        </form>
    </div>
</div>

@if($missingQrCount > 0)
<div class="mb-4 flex items-center justify-between gap-4 rounded-xl border border-amber-500/30 bg-amber-950/30 px-5 py-3">
    <div class="flex items-center gap-3">
        <svg class="w-5 h-5 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <div>
            <p class="text-sm font-semibold text-amber-300">{{ $missingQrCount }} active membership(s) are missing a QR code.</p>
            <p class="text-xs text-slate-400">Click "Generate Missing QR Codes" to create them.</p>
        </div>
    </div>
</div>
@endif

<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
    <div class="card-sm text-center">
        <p class="text-xl sm:text-2xl font-bold text-emerald-400">{{ \App\Models\QrCode::whereHas('membership', fn($q) => $q->when($filterYearId, fn($m) => $m->where('academic_year_id', $filterYearId)))->where('status', 'active')->count() }}</p>
        <p class="text-[11px] text-slate-400 mt-0.5">Active Passes</p>
    </div>
    <div class="card-sm text-center">
        <p class="text-xl sm:text-2xl font-bold text-red-400">{{ \App\Models\QrCode::whereHas('membership', fn($q) => $q->when($filterYearId, fn($m) => $m->where('academic_year_id', $filterYearId)))->where('status', 'revoked')->count() }}</p>
        <p class="text-[11px] text-slate-400 mt-0.5">Revoked</p>
    </div>
    <div class="card-sm text-center">
        <p class="text-xl sm:text-2xl font-bold text-purple-400">{{ \App\Models\Card::whereHas('qrCode.membership', fn($q) => $q->when($filterYearId, fn($m) => $m->where('academic_year_id', $filterYearId)))->where('status', 'claimed')->count() }}</p>
        <p class="text-[11px] text-slate-400 mt-0.5">Claimed PVC Cards</p>
    </div>
    <div class="card-sm text-center">
        <p class="text-xl sm:text-2xl font-bold text-amber-400">{{ \App\Models\Card::whereHas('qrCode.membership', fn($q) => $q->when($filterYearId, fn($m) => $m->where('academic_year_id', $filterYearId)))->where('status', 'for_claiming')->count() }}</p>
        <p class="text-[11px] text-slate-400 mt-0.5">For Claiming</p>
    </div>
</div>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="bg-slate-950/60 text-slate-400 border-b border-slate-800">
                <tr>
                    <th class="py-3 px-4">Student</th>
                    <th class="py-3 px-4">Student #</th>
                    <th class="py-3 px-4">Academic Year</th>
                    <th class="py-3 px-4">Membership</th>
                    <th class="py-3 px-4">Token Preview</th>
                    <th class="py-3 px-4">QR Status</th>
                    <th class="py-3 px-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
                @forelse($qrCodes as $qr)
                    <tr class="hover:bg-slate-800/30 transition">
                        <td class="py-3 px-4 font-semibold text-white">{{ $qr->membership?->student?->display_name ?? '—' }}</td>
                        <td class="py-3 px-4 text-slate-400 font-mono">{{ $qr->membership?->student?->student_number ?? '—' }}</td>
                        <td class="py-3 px-4 text-slate-300">{{ $qr->membership?->academicYear?->label ?? '—' }}</td>
                        <td class="py-3 px-4">
                            @if($qr->membership)
                                <span class="badge {{ $qr->membership->status === 'active' ? 'badge-active' : 'badge-inactive' }} text-[10px]">
                                    {{ ucfirst($qr->membership->status) }}
                                </span>
                            @else
                                <span class="text-slate-600">—</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 font-mono text-slate-500">{{ substr($qr->token, 0, 16) }}...</td>
                        <td class="py-3 px-4">
                            @if($qr->status === 'active')
                                <span class="badge badge-active">Active</span>
                            @elseif($qr->status === 'revoked')
                                <span class="badge bg-red-500/10 text-red-400 border-red-500/20">Revoked</span>
                            @else
                                <span class="badge badge-inactive">{{ ucfirst($qr->status) }}</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-right space-x-1">
                            @if($qr->status === 'active')
                                <form method="POST" action="{{ route('admin.qr-codes.revoke', $qr) }}" class="inline" id="revoke-form-{{ $qr->id }}">
                                    @csrf
                                    <input type="hidden" name="reason" id="reason-{{ $qr->id }}" value="">
                                    <button type="button" class="btn-secondary btn-sm text-red-400 border-red-500/20"
                                            onclick="
                                                var r = prompt('Reason for revoking this QR code:');
                                                if(r && r.trim()) {
                                                    document.getElementById('reason-{{ $qr->id }}').value = r.trim();
                                                    document.getElementById('revoke-form-{{ $qr->id }}').submit();
                                                }
                                            ">Revoke</button>
                                </form>
                            @elseif($qr->status === 'revoked')
                                <form method="POST" action="{{ route('admin.qr-codes.reissue', $qr) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="btn-secondary btn-sm text-brand-400">Reissue</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-500">
                            @if($missingQrCount > 0)
                                No QR codes generated yet for this filter. Click "Generate Missing QR Codes" above.
                            @else
                                No QR codes found for this filter.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($qrCodes, 'links'))
        <div class="p-4 border-t border-slate-800">
            {{ $qrCodes->links() }}
        </div>
    @endif
</div>
</x-layouts.admin>
