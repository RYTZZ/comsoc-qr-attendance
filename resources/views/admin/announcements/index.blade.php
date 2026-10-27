<x-layouts.admin :title="'Official Announcements & Bulletins'">
<div class="space-y-6">

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-white tracking-tight">Official Announcements & Bulletins</h1>
                <p class="text-xs text-slate-400 mt-1">
                    Publish official announcements, configure audience targeting, require acknowledgments, and track read receipts.
                </p>
            </div>

            <button type="button" @click="$dispatch('open-modal', 'create-announcement-modal')"
                    class="btn-primary text-xs py-2 px-4 flex items-center gap-2 self-start sm:self-auto shadow-md">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span>Publish Official Announcement</span>
            </button>
        </div>

        @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-semibold flex items-center gap-2">
            <i data-lucide="check-circle" class="w-4 h-4 shrink-0"></i>
            <span>{{ session('success') }}</span>
        </div>
        @endif

        <div class="card bg-[#172033] border border-slate-800 p-4">
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.announcements.index', ['status' => 'all']) }}"
                   class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-colors {{ $status === 'all' ? 'bg-[#560E0F] text-white shadow' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                    All Bulletins
                </a>
                <a href="{{ route('admin.announcements.index', ['status' => 'active']) }}"
                   class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-colors {{ $status === 'active' ? 'bg-[#560E0F] text-white shadow' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                    Active Only
                </a>
                <a href="{{ route('admin.announcements.index', ['status' => 'archived']) }}"
                   class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-colors {{ $status === 'archived' ? 'bg-[#560E0F] text-white shadow' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                    Archived
                </a>
            </div>
        </div>

        <div class="card bg-[#172033] border border-slate-800 overflow-hidden shadow-xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-900/80 text-slate-400 border-b border-slate-800 uppercase tracking-wider text-[11px]">
                        <tr>
                            <th class="py-3 px-4">Title & Bulletin</th>
                            <th class="py-3 px-4">Category / Priority</th>
                            <th class="py-3 px-4">Target Audience</th>
                            <th class="py-3 px-4">Read Receipts</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300">
                        @forelse($announcements as $ann)
                        <tr class="hover:bg-slate-900/40 transition">
                            <td class="py-3.5 px-4 max-w-xs">
                                <a href="{{ route('admin.announcements.show', $ann) }}" class="font-bold text-white hover:text-[#df747a] block truncate">
                                    {{ $ann->title }}
                                </a>
                                <p class="text-[11px] text-slate-400 line-clamp-1 mt-0.5">{{ $ann->body }}</p>
                                @if($ann->requires_acknowledgment)
                                <span class="inline-block mt-1 px-1.5 py-0.2 rounded text-[9px] bg-rose-500/20 text-rose-300 border border-rose-500/30 font-bold uppercase">
                                    Acknowledgment Required
                                </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <div class="flex items-center gap-1.5">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold border uppercase
                                        {{ $ann->priority === 'urgent' ? 'bg-rose-500/20 text-rose-300 border-rose-500/30' : ($ann->priority === 'high' ? 'bg-amber-500/20 text-amber-300 border-amber-500/30' : 'bg-slate-800 text-slate-300 border-slate-700') }}">
                                        {{ $ann->priority }}
                                    </span>
                                    <span class="px-2 py-0.5 rounded text-[10px] bg-slate-800 text-slate-300 border border-slate-700">
                                        {{ $ann->category }}
                                    </span>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap text-slate-300 text-[11px]">
                                <span class="capitalize font-semibold text-white">{{ $ann->target_audience_type ?? 'All' }}</span>
                                @if(!empty($ann->target_audience_values))
                                <span class="text-slate-500">({{ implode(', ', $ann->target_audience_values) }})</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap text-xs">
                                <a href="{{ route('admin.announcements.show', $ann) }}" class="text-blue-400 hover:underline font-mono">
                                    {{ $ann->receipts_count }} Receipts
                                </a>
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                @if($ann->is_active)
                                    <span class="px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-[10px] font-semibold">
                                        Active
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded bg-rose-500/20 text-rose-300 border border-rose-500/30 text-[10px]">
                                        Archived
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.announcements.show', $ann) }}" class="px-2 py-1 rounded text-[11px] bg-slate-800 hover:bg-slate-700 text-slate-300 transition">
                                        Receipts
                                    </a>
                                    <form method="POST" action="{{ route('admin.announcements.toggle-active', $ann) }}">
                                        @csrf
                                        <button type="submit" class="px-2 py-1 rounded text-[11px] bg-slate-800 hover:bg-slate-700 text-slate-300 transition">
                                            {{ $ann->is_active ? 'Archive' : 'Publish' }}
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('admin.announcements.destroy', $ann) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                data-confirm="Permanently delete this announcement?"
                                                data-confirm-title="Delete Announcement"
                                                data-confirm-type="danger"
                                                data-confirm-btn="Delete"
                                                class="p-1 text-slate-500 hover:text-red-400 transition"
                                                title="Delete">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-500">
                                No announcements found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($announcements->hasPages())
            <div class="p-4 border-t border-slate-800">
                {{ $announcements->withQueryString()->links() }}
            </div>
            @endif
        </div>

    </div>

    <!-- Create Announcement Modal with Audience Targeting & Acknowledgment -->
    <x-modal name="create-announcement-modal" :show="$errors->any()" maxWidth="2xl">
        <form method="POST" action="{{ route('admin.announcements.store') }}" class="p-6 space-y-4">
            @csrf
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <h3 class="text-base font-bold text-white">Publish Official COMSOC Announcement</h3>
                <button type="button" @click="$dispatch('close')" class="text-slate-400 hover:text-white">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            @if($errors->any())
            <div class="p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs space-y-1">
                <div class="font-bold flex items-center gap-1.5">
                    <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                    <span>Please fix the following validation errors:</span>
                </div>
                <ul class="list-disc list-inside space-y-0.5 text-[11px] text-rose-300">
                    @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <div class="space-y-1">
                <label class="text-xs font-semibold text-slate-300">Title *</label>
                <input type="text" name="title" required value="{{ old('title') }}" placeholder="Enter announcement title" class="input-dark w-full text-xs">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="text-xs font-semibold text-slate-300">Priority *</label>
                    <select name="priority" required class="input-dark w-full text-xs">
                        <option value="low">Low</option>
                        <option value="normal" selected>Normal</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-semibold text-slate-300">Category *</label>
                    <select name="category" required class="input-dark w-full text-xs">
                        @foreach(\App\Models\Announcement::CATEGORIES as $k => $label)
                        <option value="{{ $k }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Audience Targeting -->
            <div class="p-3.5 rounded-xl bg-slate-950 border border-slate-800 space-y-3" x-data="{ audienceType: 'all' }">
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-bold text-amber-400 uppercase tracking-wider">Audience Targeting</h4>
                    <span class="text-[10px] text-slate-400">Target specific groups or leave as all members</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="text-[11px] text-slate-400 block mb-1">Target Audience Criterion</label>
                        <select name="target_audience_type" x-model="audienceType" class="input-dark w-full text-xs">
                            <option value="all">All Members (Default)</option>
                            <option value="program">By Program / Course</option>
                            <option value="year_level">By Year Level</option>
                            <option value="role">By Account Role</option>
                        </select>
                    </div>

                    <div x-show="audienceType !== 'all'" x-cloak>
                        <label class="text-[11px] text-slate-400 block mb-1">
                            <span x-show="audienceType === 'program'">Programs (comma-separated, e.g. BSIT, BSCS)</span>
                            <span x-show="audienceType === 'year_level'">Year Levels (e.g. 1st Year, 2nd Year or 1, 2)</span>
                            <span x-show="audienceType === 'role'">Roles (e.g. student, officer)</span>
                        </label>
                        <input type="text" name="target_audience_values" placeholder="Enter target audience values (comma-separated)" class="input-dark w-full text-xs">
                    </div>
                </div>
            </div>

            <div class="space-y-1">
                <label class="text-xs font-semibold text-slate-300">Bulletin Content *</label>
                <textarea name="body" rows="4" required class="input-dark w-full text-xs" placeholder="Enter announcement content"></textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="text-xs font-semibold text-slate-300">Associated Event (Optional)</label>
                    <select name="event_id" class="input-dark w-full text-xs">
                        <option value="">None</option>
                        @foreach($events as $ev)
                        <option value="{{ $ev->id }}">{{ $ev->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-semibold text-slate-300">Related Destination URL (Optional)</label>
                    <input type="url" name="target_url" placeholder="Enter destination URL" class="input-dark w-full text-xs">
                </div>
            </div>

            <div class="pt-2 space-y-2">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="requires_acknowledgment" value="1" class="rounded border-slate-700 bg-slate-900 text-[#560E0F]">
                    <span class="text-xs font-bold text-amber-400">Require explicit "Acknowledge" button click from members</span>
                </label>

                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_pinned" value="1" class="rounded border-slate-700 bg-slate-900 text-[#560E0F]">
                    <span class="text-xs text-slate-300">Pin to top of member announcements feed</span>
                </label>

                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="notify_members" value="1" checked class="rounded border-slate-700 bg-slate-900 text-[#560E0F]">
                    <span class="text-xs text-slate-300">Broadcast notification to eligible targeted members</span>
                </label>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
                <button type="button" @click="$dispatch('close')" class="btn-secondary text-xs py-2 px-4">
                    Cancel
                </button>
                <button type="submit" class="btn-primary text-xs py-2 px-4 shadow-lg">
                    Publish Announcement
                </button>
            </div>
        </form>
    </x-modal>
</div>
</x-layouts.admin>
