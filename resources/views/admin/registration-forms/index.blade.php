<x-layouts.admin :title="'Registration Form Builder'">
<div class="page-header mb-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="px-2 py-0.5 rounded bg-[#560E0F]/30 border border-[#560E0F] text-[#dfa6a9] text-[10px] font-bold uppercase tracking-wider">
                    Super Admin Exclusive
                </span>
                <span class="text-xs text-slate-400">Administration → Activity Management → Registration Form Builder</span>
            </div>
            <h1 class="page-title font-heading text-2xl font-bold text-white">Dynamic Registration Form Builder</h1>
            <p class="text-xs text-slate-400 mt-0.5">
                Design, version, publish, duplicate, and assign dynamic registration forms and reusable templates to parent activities or individual sessions.
            </p>
        </div>

        <a href="{{ route('admin.registration-forms.create') }}" class="btn-primary text-xs flex items-center gap-2">
            <i data-lucide="plus" class="w-4 h-4"></i>
            <span>Create New Form / Template</span>
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card bg-[#172033] border border-slate-800 p-4 mb-6">
    <form method="GET" action="{{ route('admin.registration-forms.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
        <div class="md:col-span-2">
            <label class="block text-[11px] font-semibold text-slate-400 mb-1">Search Forms</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by form title or description..."
                   class="input w-full text-xs bg-[#0B1120] border-slate-700 text-white">
        </div>

        <div>
            <label class="block text-[11px] font-semibold text-slate-400 mb-1">Status</label>
            <select name="status" class="select w-full text-xs bg-[#0B1120] border-slate-700 text-white">
                <option value="">All Statuses</option>
                <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                <option value="published" @selected(request('status') === 'published')>Published</option>
                <option value="archived" @selected(request('status') === 'archived')>Archived</option>
            </select>
        </div>

        <div>
            <label class="block text-[11px] font-semibold text-slate-400 mb-1">Activity Category</label>
            <select name="category" class="select w-full text-xs bg-[#0B1120] border-slate-700 text-white">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat }}" @selected(request('category') === $cat)>{{ $cat }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex items-center gap-2">
            <button type="submit" class="btn-primary text-xs py-2 px-4">Filter</button>
            <a href="{{ route('admin.registration-forms.index') }}" class="btn-secondary text-xs py-2 px-3">Reset</a>
        </div>
    </form>
</div>

<!-- Forms Table -->
<div class="card bg-[#172033] border border-slate-800 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="bg-[#0B1120] text-slate-400 border-b border-slate-800 uppercase text-[10px] tracking-wider">
                <tr>
                    <th class="py-3.5 px-4">Form Title & Categories</th>
                    <th class="py-3.5 px-4">Type / Version</th>
                    <th class="py-3.5 px-4">Questions</th>
                    <th class="py-3.5 px-4">Assignments</th>
                    <th class="py-3.5 px-4">Status</th>
                    <th class="py-3.5 px-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/70">
                @forelse($forms as $form)
                    <tr class="hover:bg-[#1E293B]/60 transition">
                        <td class="py-3.5 px-4">
                            <div class="font-bold text-white text-sm">{{ $form->title }}</div>
                            @if($form->description)
                                <p class="text-[11px] text-slate-400 line-clamp-1 mt-0.5">{{ $form->description }}</p>
                            @endif
                            @if(!empty($form->intended_categories))
                                <div class="flex flex-wrap gap-1 mt-1.5">
                                    @foreach($form->intended_categories as $cat)
                                        <span class="px-2 py-0.5 rounded bg-slate-800 border border-slate-700 text-[10px] text-slate-300">
                                            {{ $cat }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </td>

                        <td class="py-3.5 px-4">
                            <span class="font-mono text-xs text-slate-300">v{{ $form->version }}</span>
                            @if($form->is_template)
                                <span class="ml-1.5 px-2 py-0.5 rounded bg-indigo-500/20 border border-indigo-500/40 text-indigo-300 text-[10px] font-semibold">
                                    Template
                                </span>
                            @endif
                        </td>

                        <td class="py-3.5 px-4 font-semibold text-slate-200">
                            {{ $form->fields_count }} question(s)
                        </td>

                        <td class="py-3.5 px-4 text-slate-300">
                            <div>{{ $form->activities_count }} Parent Event(s)</div>
                            <div class="text-[11px] text-slate-400">{{ $form->sessions_count }} Session(s)</div>
                        </td>

                        <td class="py-3.5 px-4">
                            @if($form->status === 'published')
                                <span class="px-2.5 py-1 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-[10px] font-bold uppercase">
                                    Published
                                </span>
                            @elseif($form->status === 'archived')
                                <span class="px-2.5 py-1 rounded-full bg-slate-800 text-slate-400 border border-slate-700 text-[10px] font-bold uppercase">
                                    Archived
                                </span>
                            @else
                                <span class="px-2.5 py-1 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/30 text-[10px] font-bold uppercase">
                                    Draft
                                </span>
                            @endif
                        </td>

                        <td class="py-3.5 px-4 text-right whitespace-nowrap space-x-1">
                            <a href="{{ route('admin.registration-forms.preview', $form) }}" class="btn-secondary btn-sm text-xs">
                                Preview
                            </a>
                            <a href="{{ route('admin.registration-forms.edit', $form) }}" class="btn-primary btn-sm text-xs">
                                Configure Builder
                            </a>

                            @if($form->status !== 'published')
                                <form method="POST" action="{{ route('admin.registration-forms.publish', $form) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="btn-secondary btn-sm text-xs text-emerald-300 border-emerald-500/30">
                                        Publish
                                    </button>
                                </form>
                            @endif

                            <form method="POST" action="{{ route('admin.registration-forms.duplicate', $form) }}" class="inline">
                                @csrf
                                <button type="submit" class="btn-secondary btn-sm text-xs">
                                    Duplicate
                                </button>
                            </form>

                            @if($form->status !== 'archived')
                                <form method="POST" action="{{ route('admin.registration-forms.archive', $form) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="btn-secondary btn-sm text-xs text-amber-300">
                                        Archive
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-10 text-center text-slate-400">
                            No dynamic registration forms created yet. Click <strong>Create New Form / Template</strong> to build your first form.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4 border-t border-slate-800">
        {{ $forms->links() }}
    </div>
</div>
</x-layouts.admin>

