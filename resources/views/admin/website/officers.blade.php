<x-admin-layout title="Public Officers Management">

    <div class="space-y-6">

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-5">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-white font-brand-display">Public Leadership & Officers</h1>
                <p class="text-xs sm:text-sm text-slate-400 mt-1">Manage public officer profiles, portraits, display order, and publication status.</p>
            </div>
            <a href="{{ route('public.officers') }}" target="_blank" class="btn-secondary text-xs py-2 px-3 flex items-center gap-1.5">
                <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                <span>Preview Officers Page</span>
            </a>
        </div>

        @if(session('success'))
            <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm flex items-center gap-2">
                <i data-lucide="check-circle-2" class="w-4 h-4 shrink-0"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <!-- Management Navigation Tabs -->
        <div class="flex flex-wrap items-center gap-2 border-b border-slate-800 pb-2 text-xs">
            <a href="{{ route('admin.website.settings') }}" class="px-3 py-1.5 rounded-lg text-slate-300 hover:bg-slate-800">Homepage & General</a>
            <a href="{{ route('admin.website.profile') }}" class="px-3 py-1.5 rounded-lg text-slate-300 hover:bg-slate-800">Organization Profile & Pillars</a>
            <a href="{{ route('admin.website.officers') }}" class="px-3 py-1.5 rounded-lg bg-[#560E0F] text-white font-semibold">Officers & Leadership</a>
            <a href="{{ route('admin.website.activities') }}" class="px-3 py-1.5 rounded-lg text-slate-300 hover:bg-slate-800">Activities & Events Visibility</a>
            <a href="{{ route('admin.website.achievements') }}" class="px-3 py-1.5 rounded-lg text-slate-300 hover:bg-slate-800">Achievements</a>
            <a href="{{ route('admin.website.gallery') }}" class="px-3 py-1.5 rounded-lg text-slate-300 hover:bg-slate-800">Gallery</a>
            <a href="{{ route('admin.website.partners') }}" class="px-3 py-1.5 rounded-lg text-slate-300 hover:bg-slate-800">Partners</a>
            <a href="{{ route('admin.website.faqs') }}" class="px-3 py-1.5 rounded-lg text-slate-300 hover:bg-slate-800">FAQs</a>
        </div>

        <!-- Add Officer Form Card -->
        <div class="card bg-slate-900 border border-slate-800 p-6 rounded-2xl space-y-4">
            <h3 class="text-base font-bold text-white flex items-center gap-2">
                <i data-lucide="user-plus" class="w-4 h-4 text-[#df747a]"></i>
                <span>Add New Public Officer Profile</span>
            </h3>

            <form method="POST" action="{{ route('admin.website.officers.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-slate-300">Link System Account (Optional)</label>
                        <select name="user_id" class="form-input w-full text-xs">
                            <option value="">-- No Account Association --</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->officer_position ?: $user->role }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-slate-300">Officer Full Name</label>
                        <input type="text" name="name" required class="form-input w-full text-xs" placeholder="e.g. Maria Santos">
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-slate-300">Official Position</label>
                        <input type="text" name="position" required class="form-input w-full text-xs" placeholder="e.g. President, Vice President...">
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-slate-300">Academic Program</label>
                        <select name="program" class="form-input w-full text-xs">
                            <option value="">-- Select Program --</option>
                            @foreach($programs as $prog)
                                <option value="{{ $prog->display_name }}">{{ $prog->display_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-slate-300">Category</label>
                        <select name="category" required class="form-input w-full text-xs">
                            @foreach(\App\Models\OrgOfficer::CATEGORIES as $catKey => $catLabel)
                                <option value="{{ $catKey }}">{{ $catLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-slate-300">Display Order</label>
                        <input type="number" name="sort_order" value="0" class="form-input w-full text-xs">
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-slate-300">Professional / Social Link (Optional)</label>
                        <input type="url" name="social_link" class="form-input w-full text-xs" placeholder="https://linkedin.com/in/...">
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-slate-300">Portrait Image</label>
                        <input type="file" name="portrait" accept="image/*" class="block w-full text-xs text-slate-400 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:bg-slate-800 file:text-white">
                    </div>

                    <div class="space-y-1.5 sm:col-span-2 lg:col-span-4">
                        <label class="text-xs font-semibold text-slate-300">Short Biography (Optional)</label>
                        <textarea name="bio" rows="2" class="form-input w-full text-xs" placeholder="Brief statement about the officer's involvement or goals..."></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-2">
                    <div class="flex items-center gap-4 text-xs">
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="checkbox" name="is_current" value="1" checked class="rounded border-slate-700 bg-slate-950 text-[#df747a]">
                            <span>Current Leadership</span>
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="checkbox" name="is_published" value="1" checked class="rounded border-slate-700 bg-slate-950 text-[#df747a]">
                            <span>Publish to Public Site</span>
                        </label>
                    </div>

                    <button type="submit" class="btn-primary py-2 px-4 text-xs flex items-center gap-1.5">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                        <span>Add Officer</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Officers List Table -->
        <div class="card bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
            <div class="p-4 border-b border-slate-800">
                <h3 class="text-sm font-bold text-white">Current & Historical Officers ({{ $officers->total() }})</h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="bg-slate-950/60 text-slate-400 uppercase text-[10px] tracking-wider border-b border-slate-800">
                        <tr>
                            <th class="p-3">Officer</th>
                            <th class="p-3">Position</th>
                            <th class="p-3">Category</th>
                            <th class="p-3">Program</th>
                            <th class="p-3">Order</th>
                            <th class="p-3">Status</th>
                            <th class="p-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @forelse($officers as $officer)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="p-3 flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg overflow-hidden bg-slate-950 border border-slate-800 shrink-0">
                                    @if($officer->portrait_path)
                                        <img src="{{ asset('storage/' . $officer->portrait_path) }}" alt="{{ $officer->name }}" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-slate-600">
                                            <i data-lucide="user" class="w-4 h-4"></i>
                                        </div>
                                    @endif
                                </div>
                                <span class="font-semibold text-white">{{ $officer->name }}</span>
                            </td>
                            <td class="p-3 text-slate-200">{{ $officer->position }}</td>
                            <td class="p-3 capitalize font-mono text-[11px]">{{ $officer->category }}</td>
                            <td class="p-3 truncate max-w-[150px]">{{ $officer->program ?: '—' }}</td>
                            <td class="p-3 font-mono">{{ $officer->sort_order }}</td>
                            <td class="p-3">
                                <div class="flex items-center gap-1.5">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold {{ $officer->is_published ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-slate-800 text-slate-400' }}">
                                        {{ $officer->is_published ? 'Published' : 'Draft' }}
                                    </span>
                                    @if(!$officer->is_current)
                                        <span class="px-1.5 py-0.5 rounded text-[10px] bg-amber-500/10 text-amber-400">Past</span>
                                    @endif
                                </div>
                            </td>
                            <td class="p-3 text-right">
                                <form method="POST" action="{{ route('admin.website.officers.destroy', $officer) }}" onsubmit="return confirm('Remove this officer profile?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-400 hover:text-red-300 p-1">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="p-6 text-center text-slate-500">No officers found. Add your first officer profile above.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-800">
                {{ $officers->links() }}
            </div>
        </div>

    </div>

</x-admin-layout>
