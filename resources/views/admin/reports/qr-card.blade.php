<x-layouts.admin :title="'QR & Physical Card Report'">
<div class="page-header">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.reports.index') }}" class="btn-secondary btn-sm">← Reports</a>
        <div>
            <h1 class="page-title">QR & Physical Cards Report</h1>
            <p class="text-xs text-slate-400 mt-0.5">Audit QR code generations, card issuance, and lost cards.</p>
        </div>
    </div>
</div>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="bg-slate-950/60 text-slate-400 border-b border-slate-800">
                <tr>
                    <th class="py-3 px-4">Student</th>
                    <th class="py-3 px-4">Student #</th>
                    <th class="py-3 px-4">QR Status</th>
                    <th class="py-3 px-4">Card Status</th>
                    <th class="py-3 px-4">Generated At</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
                @forelse($data as $qr)
                    <tr class="hover:bg-slate-800/30 transition">
                        <td class="py-3 px-4 font-medium text-white">{{ $qr->membership?->student?->display_name ?? '—' }}</td>
                        <td class="py-3 px-4 text-slate-400 font-mono">{{ $qr->membership?->student?->student_number ?? '—' }}</td>
                        <td class="py-3 px-4">
                            <span class="badge {{ $qr->status === 'active' ? 'badge-active' : 'badge-inactive' }}">
                                {{ ucfirst($qr->status) }}
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            @if($qr->card)
                                <span class="badge {{ $qr->card->status === 'claimed' ? 'badge-active' : 'badge-inactive' }}">
                                    {{ ucfirst($qr->card->status) }}
                                </span>
                            @else
                                <span class="text-slate-500">No physical card</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-slate-400">{{ $qr->created_at->format('M j, Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-8 text-center text-slate-500">No QR or card records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</x-layouts.admin>
