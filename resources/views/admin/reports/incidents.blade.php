<x-layouts.admin :title="'Incident Report'">
<div class="page-header">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.reports.index') }}" class="btn-secondary btn-sm">← Reports</a>
        <div>
            <h1 class="page-title">Incidents Report</h1>
            <p class="text-xs text-slate-400 mt-0.5">Summary of security, conduct, and technical reports.</p>
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
                    <th class="py-3 px-4">Date</th>
                    <th class="py-3 px-4">Category</th>
                    <th class="py-3 px-4">Severity</th>
                    <th class="py-3 px-4">Status</th>
                    <th class="py-3 px-4">Reporter</th>
                    <th class="py-3 px-4">Resolution Notes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
                @forelse($data as $inc)
                    <tr class="hover:bg-slate-800/30 transition">
                        <td class="py-3 px-4 text-slate-400 whitespace-nowrap">{{ $inc->created_at->format('M j, Y') }}</td>
                        <td class="py-3 px-4 text-white font-medium">{{ ucwords(str_replace('_', ' ', $inc->category)) }}</td>
                        <td class="py-3 px-4">
                            <span class="badge uppercase text-[10px]
                                @if($inc->severity === 'high') bg-red-500/10 text-red-400 border-red-500/20
                                @elseif($inc->severity === 'medium') bg-amber-500/10 text-amber-400 border-amber-500/20
                                @else bg-blue-500/10 text-blue-400 border-blue-500/20 @endif">
                                {{ $inc->severity }}
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            <span class="badge
                                @if($inc->status === 'resolved') badge-active
                                @elseif($inc->status === 'archived') bg-slate-500/10 text-slate-400 border-slate-500/20
                                @else bg-yellow-500/10 text-yellow-400 border-yellow-500/20 @endif">
                                {{ strtoupper($inc->status) }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-slate-300">{{ $inc->reportedByUser?->name ?? 'Staff' }}</td>
                        <td class="py-3 px-4 text-slate-400 max-w-xs truncate">{{ $inc->resolution_notes ?? 'Pending' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-500">No incident records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</x-layouts.admin>
