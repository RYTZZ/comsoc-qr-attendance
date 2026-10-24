<x-admin-layout title="Public Gallery Management">

    <div class="space-y-6">

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-5">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-white font-brand-display">Approved Photo Gallery</h1>
                <p class="text-xs sm:text-sm text-slate-400 mt-1">Upload and manage approved photographs from COMSOC events and student activities.</p>
            </div>
            <a href="{{ route('public.gallery') }}" target="_blank" class="btn-secondary text-xs py-2 px-3 flex items-center gap-1.5">
                <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                <span>Preview Gallery</span>
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
            <a href="{{ route('admin.website.officers') }}" class="px-3 py-1.5 rounded-lg text-slate-300 hover:bg-slate-800">Officers & Leadership</a>
            <a href="{{ route('admin.website.activities') }}" class="px-3 py-1.5 rounded-lg text-slate-300 hover:bg-slate-800">Activities & Events Visibility</a>
            <a href="{{ route('admin.website.achievements') }}" class="px-3 py-1.5 rounded-lg text-slate-300 hover:bg-slate-800">Achievements</a>
            <a href="{{ route('admin.website.gallery') }}" class="px-3 py-1.5 rounded-lg bg-[#560E0F] text-white font-semibold">Gallery</a>
            <a href="{{ route('admin.website.partners') }}" class="px-3 py-1.5 rounded-lg text-slate-300 hover:bg-slate-800">Partners</a>
            <a href="{{ route('admin.website.faqs') }}" class="px-3 py-1.5 rounded-lg text-slate-300 hover:bg-slate-800">FAQs</a>
        </div>

        <!-- Upload Form -->
        <div class="card bg-slate-900 border border-slate-800 p-6 rounded-2xl space-y-4">
            <h3 class="text-base font-bold text-white flex items-center gap-2">
                <i data-lucide="image-plus" class="w-4 h-4 text-[#df747a]"></i>
                <span>Upload Approved Photo</span>
            </h3>

            <form method="POST" action="{{ route('admin.website.gallery.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="space-y-1.5 sm:col-span-2">
                        <label class="text-xs font-semibold text-slate-300">Photo File (JPEG, PNG, WebP up to 4MB)</label>
                        <input type="file" name="image" accept="image/*" required class="block w-full text-xs text-slate-400 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:bg-slate-800 file:text-white">
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-slate-300">Photo Title</label>
                        <input type="text" name="title" class="form-input w-full text-xs" placeholder="e.g. Hackathon Assembly 2026">
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-slate-300">Associated Event (Optional)</label>
                        <select name="event_id" class="form-input w-full text-xs">
                            <option value="">-- No Specific Event --</option>
                            @foreach($events as $ev)
                                <option value="{{ $ev->id }}">{{ $ev->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-1.5 sm:col-span-2">
                        <label class="text-xs font-semibold text-slate-300">Caption / Description</label>
                        <input type="text" name="caption" class="form-input w-full text-xs" placeholder="Brief context about this photograph...">
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-slate-300">Alt Text (Accessibility)</label>
                        <input type="text" name="alt_text" class="form-input w-full text-xs" placeholder="Descriptive alt text for screen readers">
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-slate-300">Display Order</label>
                        <input type="number" name="sort_order" value="0" class="form-input w-full text-xs">
                    </div>
                </div>

                <div class="flex items-center justify-between pt-2">
                    <label class="flex items-center gap-1.5 cursor-pointer text-xs">
                        <input type="checkbox" name="is_published" value="1" checked class="rounded border-slate-700 bg-slate-950 text-[#df747a]">
                        <span>Publish Photo Immediately</span>
                    </label>

                    <button type="submit" class="btn-primary py-2 px-4 text-xs flex items-center gap-1.5">
                        <i data-lucide="upload" class="w-3.5 h-3.5"></i>
                        <span>Upload Photograph</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Gallery Grid -->
        <div class="card bg-slate-900 border border-slate-800 p-6 rounded-2xl space-y-4">
            <h3 class="text-sm font-bold text-white">Gallery Archive ({{ $gallery->total() }})</h3>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                @forelse($gallery as $item)
                <div class="card bg-slate-950 border border-slate-800 rounded-xl overflow-hidden group relative flex flex-col justify-between">
                    <div class="aspect-video relative overflow-hidden">
                        <img src="{{ asset('storage/' . $item->image_path) }}" alt="{{ $item->alt_text }}" class="w-full h-full object-cover">
                    </div>
                    <div class="p-2.5 flex items-center justify-between text-[11px] text-slate-400">
                        <span class="truncate max-w-[100px]">{{ $item->title ?: 'Photo' }}</span>
                        <form method="POST" action="{{ route('admin.website.gallery.destroy', $item) }}" onsubmit="return confirm('Delete this photograph?');" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-400 hover:text-red-300">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                            </button>
                        </form>
                    </div>
                </div>
                @empty
                <div class="col-span-full py-8 text-center text-slate-500 text-xs">
                    No gallery photographs uploaded yet.
                </div>
                @endforelse
            </div>

            <div class="pt-4 border-t border-slate-800">
                {{ $gallery->links() }}
            </div>
        </div>

    </div>

</x-admin-layout>
