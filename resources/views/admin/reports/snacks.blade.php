<x-layouts.admin :title="'Snack Distribution Report'">
<div class="page-header">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.reports.index') }}" class="btn-secondary btn-sm">← Reports</a>
        <div>
            <h1 class="page-title">Snack Distribution Report</h1>
            <p class="text-xs text-slate-400 mt-0.5">Audit snack distribution claims and inventory usage.</p>
        </div>
    </div>
    <div class="flex gap-2">
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn-primary text-xs">Export CSV</a>
    </div>
</div>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="bg-slate-950/60 text-slate-400 border-b border-slate-800">
                <tr>
                    <th class="py-3 px-4">Claimant Student / Guest</th>
                    <th class="py-3 px-4">Event</th>
                    <th class="py-3 px-4">Snack Session</th>
                    <th class="py-3 px-4">Item Claimed</th>
                    <th class="py-3 px-4">Claimed At</th>
                    <th class="py-3 px-4">Distributed By</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
                @forelse($data as $claim)
                    <tr class="hover:bg-slate-800/30 transition">
                        <td class="py-3 px-4 font-medium text-white">
                            {{ $claim->student?->display_name ?? $claim->eventRegistration?->full_name ?? ('QR: ' . substr($claim->qr_token, 0, 10)) }}
                        </td>
                        <td class="py-3 px-4 text-slate-300">{{ $claim->snackSession?->event?->name ?? '—' }}</td>
                        <td class="py-3 px-4 text-slate-300">{{ $claim->snackSession?->name ?? '—' }}</td>
                        <td class="py-3 px-4 text-brand-300 font-medium">{{ $claim->snackInventory?->item_name ?? 'Standard Ration' }}</td>
                        <td class="py-3 px-4 text-slate-400">{{ $claim->claimed_at ? $claim->claimed_at->format('M j, Y H:i:s') : '—' }}</td>
                        <td class="py-3 px-4 text-slate-400">{{ $claim->distributedByUser?->name ?? 'Kiosk' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-500">No snack claim records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</x-layouts.admin>
