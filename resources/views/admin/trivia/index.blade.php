<x-layouts.admin :title="'Friday Trivia Management'">
<div class="page-header">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="page-title font-heading">Friday Trivia</h1>
            <p class="text-xs text-slate-400 mt-0.5">Create, schedule, publish, and manage weekly computing trivia challenges.</p>
        </div>

        <a href="{{ route('admin.trivia.create') }}" class="btn-primary btn-sm flex items-center gap-1.5">
            <i data-lucide="plus-circle" class="w-4 h-4"></i>
            <span>New Trivia Question</span>
        </a>
    </div>
</div>

@if (session('success'))
    <div role="status" class="mb-4 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-xs text-emerald-200">
        {{ session('success') }}
    </div>
@endif
@if (session('error'))
    <div role="alert" class="mb-4 rounded-xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-xs text-rose-200">
        {{ session('error') }}
    </div>
@endif

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="bg-slate-950/60 text-slate-400 border-b border-slate-800">
                <tr>
                    <th class="py-3 px-4">Question</th>
                    <th class="py-3 px-4">Category</th>
                    <th class="py-3 px-4">Schedule (PHT)</th>
                    <th class="py-3 px-4">Status / State</th>
                    <th class="py-3 px-4">Answers</th>
                    <th class="py-3 px-4">Points</th>
                    <th class="py-3 px-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
                @php $tz = \App\Models\TriviaQuestion::TZ; @endphp
                @forelse($questions as $q)
                    <tr class="hover:bg-slate-800/30 transition">
                        <td class="py-3 px-4 font-semibold text-white max-w-xs truncate" title="{{ $q->question }}">
                            {{ $q->question }}
                        </td>
                        <td class="py-3 px-4 text-slate-300">
                            {{ \App\Models\TriviaQuestion::CATEGORIES[$q->category] ?? $q->category }}
                        </td>
                        <td class="py-3 px-4 text-slate-300 whitespace-nowrap">
                            <div>{{ $q->publish_at ? $q->publish_at->timezone($tz)->format('M j, Y g:i A') : '—' }}</div>
                            <div class="text-[10px] text-slate-500">to {{ $q->closes_at ? $q->closes_at->timezone($tz)->format('g:i A') : '—' }}</div>
                        </td>
                        <td class="py-3 px-4 whitespace-nowrap">
                            @if($q->status === 'draft')
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-800 text-slate-300 border border-slate-700">DRAFT</span>
                            @elseif($q->state === 'scheduled')
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-500/20 text-blue-300 border border-blue-500/30">SCHEDULED</span>
                            @elseif($q->state === 'live')
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 animate-pulse">LIVE</span>
                            @else
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">CLOSED</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-slate-300 font-mono">
                            {{ $q->answers_count }}
                        </td>
                        <td class="py-3 px-4 text-slate-300 font-mono">
                            {{ $q->points }}
                        </td>
                        <td class="py-3 px-4 text-right space-x-1 whitespace-nowrap">
                            <form action="{{ route('admin.trivia.toggle', $q) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="p-1 rounded text-slate-400 hover:text-white transition" title="{{ $q->status === 'published' ? 'Unpublish to draft' : 'Publish' }}">
                                    @if($q->status === 'published')
                                        <i data-lucide="eye-off" class="w-4 h-4 text-amber-400"></i>
                                    @else
                                        <i data-lucide="eye" class="w-4 h-4 text-emerald-400"></i>
                                    @endif
                                </button>
                            </form>
                            <a href="{{ route('admin.trivia.edit', $q) }}" class="p-1 inline-block text-slate-400 hover:text-white transition" title="Edit">
                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                            </a>
                            @if($q->answers_count === 0)
                                <form action="{{ route('admin.trivia.destroy', $q) }}" method="POST" class="inline" onsubmit="return confirm('Delete this trivia question?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1 rounded text-slate-400 hover:text-rose-400 transition" title="Delete">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-400 text-xs">
                            No Friday Trivia questions created yet. Click "New Trivia Question" to start.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($questions->hasPages())
        <div class="p-4 border-t border-slate-800">
            {{ $questions->links() }}
        </div>
    @endif
</div>
</x-layouts.admin>

