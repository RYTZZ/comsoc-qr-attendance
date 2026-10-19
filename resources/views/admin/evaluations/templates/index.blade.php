<x-layouts.admin :title="'Evaluation Templates'">
<div class="page-header">
    <div>
        <h1 class="page-title">Event Evaluation Templates</h1>
        <p class="text-xs text-slate-400 mt-0.5">Reusable surveys and feedback questionnaires enforced before afternoon attendance time-out.</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('admin.evaluation-templates.create') }}" class="btn-primary btn-sm flex items-center gap-1.5 shadow-lg">
            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
            <span>Create Template</span>
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert-success mb-5 animate-fade-in text-xs sm:text-sm">{{ session('success') }}</div>
@endif

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="bg-slate-950 text-slate-400 border-b border-slate-800 uppercase tracking-wider font-semibold">
                <tr>
                    <th class="p-3.5">Template Title & Description</th>
                    <th class="p-3.5 text-center">Version</th>
                    <th class="p-3.5 text-center">Questions</th>
                    <th class="p-3.5 text-center">Events Using</th>
                    <th class="p-3.5 text-center">Submissions</th>
                    <th class="p-3.5 text-center">Status</th>
                    <th class="p-3.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800 text-slate-300">
                @forelse($templates as $t)
                <tr class="hover:bg-slate-800/40 transition">
                    <td class="p-3.5">
                        <div class="font-bold text-white text-sm font-brand-display">{{ $t->title }}</div>
                        <p class="text-slate-400 text-[11px] line-clamp-1 mt-0.5">{{ $t->description ?: 'No description provided.' }}</p>
                        <span class="text-[10px] text-slate-500 font-mono mt-1 block">Created by: {{ $t->creator?->name ?? 'System' }}</span>
                    </td>
                    <td class="p-3.5 text-center font-mono font-bold text-slate-200">
                        v{{ $t->version }}
                    </td>
                    <td class="p-3.5 text-center">
                        <span class="px-2 py-0.5 rounded-full bg-slate-800 text-slate-200 font-bold border border-slate-700">
                            {{ $t->questions_count }}
                        </span>
                    </td>
                    <td class="p-3.5 text-center">
                        <span class="px-2 py-0.5 rounded-full bg-blue-500/10 text-blue-300 font-bold border border-blue-500/20">
                            {{ $t->events_count }}
                        </span>
                    </td>
                    <td class="p-3.5 text-center">
                        <span class="px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-300 font-bold border border-emerald-500/20">
                            {{ $t->evaluations_count }}
                        </span>
                    </td>
                    <td class="p-3.5 text-center">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $t->is_active ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20' }}">
                            {{ $t->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="p-3.5 text-right space-x-1.5 whitespace-nowrap">
                        <a href="{{ route('admin.evaluation-templates.show', $t) }}" class="btn-secondary btn-xs">
                            Preview
                        </a>
                        <a href="{{ route('admin.evaluation-templates.edit', $t) }}" class="btn-secondary btn-xs">
                            Edit
                        </a>
                        <form method="POST" action="{{ route('admin.evaluation-templates.toggle-active', $t) }}" class="inline-block" onsubmit="return confirm('Toggle active status for this template?');">
                            @csrf
                            <button type="submit" class="btn-xs {{ $t->is_active ? 'text-amber-400 hover:text-amber-300' : 'text-emerald-400 hover:text-emerald-300' }}">
                                {{ $t->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="p-8 text-center text-slate-500">
                        <i data-lucide="file-question" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                        <p class="font-medium text-slate-400">No evaluation templates created yet.</p>
                        <p class="text-[11px] mt-1">Create your first template to assign to events for mandatory attendee feedback.</p>
                        <div class="mt-4">
                            <a href="{{ route('admin.evaluation-templates.create') }}" class="btn-primary btn-sm">
                                Create New Template
                            </a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($templates->hasPages())
    <div class="p-4 border-t border-slate-800">
        {{ $templates->links() }}
    </div>
    @endif
</div>
</x-layouts.admin>
