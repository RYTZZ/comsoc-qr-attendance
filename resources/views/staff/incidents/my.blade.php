<x-layouts.admin :title="'My Incident Reports'">
<div class="page-header">
    <div>
        <h1 class="page-title">My Filed Incident Reports</h1>
        <p class="text-xs text-slate-400 mt-1">Reports submitted by you during duty or event operations.</p>
    </div>
    <a href="{{ route('staff.incidents.create') }}" class="btn-primary">+ File New Report</a>
</div>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="bg-slate-950/60 text-slate-400 border-b border-slate-800">
                <tr>
                    <th class="py-3 px-4">Date Filed</th>
                    <th class="py-3 px-4">Category</th>
                    <th class="py-3 px-4">Associated Event</th>
                    <th class="py-3 px-4">Status</th>
                    <th class="py-3 px-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
                @forelse($incidents as $inc)
                    <tr class="hover:bg-slate-800/30 transition">
                        <td class="py-3 px-4 text-slate-400 whitespace-nowrap">{{ $inc->created_at->format('M j, Y H:i') }}</td>
                        <td class="py-3 px-4 font-medium text-white">{{ ucwords(str_replace('_', ' ', $inc->category)) }}</td>
                        <td class="py-3 px-4 text-slate-300">{{ $inc->event->name ?? '—' }}</td>
                        <td class="py-3 px-4">
                            <span class="badge
                                @if($inc->status === 'resolved') badge-active
                                @elseif($inc->status === 'archived') bg-slate-500/10 text-slate-400 border-slate-500/20
                                @else bg-yellow-500/10 text-yellow-400 border-yellow-500/20 @endif">
                                {{ strtoupper($inc->status) }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-right">
                            <a href="{{ route('staff.incidents.show', $inc) }}" class="btn-secondary btn-sm">View Details</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-8 text-center text-slate-500">You have not submitted any incident reports.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($incidents, 'links'))
        <div class="p-4 border-t border-slate-800">
            {{ $incidents->links() }}
        </div>
    @endif
</div>
</x-layouts.admin>
