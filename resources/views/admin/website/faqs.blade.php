<x-admin-layout title="Public FAQs Management">

    <div class="space-y-6">

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-5">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-white font-brand-display">Frequently Asked Questions (FAQs)</h1>
                <p class="text-xs sm:text-sm text-slate-400 mt-1">Manage public inquiries, membership guidelines, QR attendance explanations, and account policies.</p>
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
            <a href="{{ route('admin.website.gallery') }}" class="px-3 py-1.5 rounded-lg text-slate-300 hover:bg-slate-800">Gallery</a>
            <a href="{{ route('admin.website.partners') }}" class="px-3 py-1.5 rounded-lg text-slate-300 hover:bg-slate-800">Partners</a>
            <a href="{{ route('admin.website.faqs') }}" class="px-3 py-1.5 rounded-lg bg-[#560E0F] text-white font-semibold">FAQs</a>
        </div>

        <!-- Add FAQ Form -->
        <div class="card bg-slate-900 border border-slate-800 p-6 rounded-2xl space-y-4">
            <h3 class="text-base font-bold text-white flex items-center gap-2">
                <i data-lucide="help-circle" class="w-4 h-4 text-[#df747a]"></i>
                <span>Add New FAQ Question</span>
            </h3>

            <form method="POST" action="{{ route('admin.website.faqs.store') }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="space-y-1.5 sm:col-span-3">
                        <label class="text-xs font-semibold text-slate-300">Question</label>
                        <input type="text" name="question" required class="form-input w-full text-xs" placeholder="e.g. How can students register for COMSOC Hub?">
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-slate-300">Category</label>
                        <input type="text" name="category" value="General" class="form-input w-full text-xs" placeholder="e.g. General, Attendance, Accounts">
                    </div>

                    <div class="space-y-1.5 sm:col-span-4">
                        <label class="text-xs font-semibold text-slate-300">Answer</label>
                        <textarea name="answer" rows="3" required class="form-input w-full text-xs" placeholder="Clear and accurate answer explaining the policy or procedure..."></textarea>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-slate-300">Display Order</label>
                        <input type="number" name="sort_order" value="0" class="form-input w-full text-xs">
                    </div>

                    <div class="flex items-center gap-2 pt-6">
                        <label class="flex items-center gap-1.5 cursor-pointer text-xs">
                            <input type="checkbox" name="is_published" value="1" checked class="rounded border-slate-700 bg-slate-950 text-[#df747a]">
                            <span>Publish to FAQ Section</span>
                        </label>
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit" class="btn-primary py-2 px-4 text-xs flex items-center gap-1.5">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                        <span>Add Question</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- FAQs List -->
        <div class="card bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
            <div class="p-4 border-b border-slate-800">
                <h3 class="text-sm font-bold text-white">Configured FAQs ({{ $faqs->total() }})</h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="bg-slate-950/60 text-slate-400 uppercase text-[10px] tracking-wider border-b border-slate-800">
                        <tr>
                            <th class="p-3">Question</th>
                            <th class="p-3">Category</th>
                            <th class="p-3">Order</th>
                            <th class="p-3">Status</th>
                            <th class="p-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @forelse($faqs as $faq)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="p-3">
                                <span class="font-semibold text-white block">{{ $faq->question }}</span>
                                <span class="text-slate-400 text-[11px] line-clamp-1 mt-0.5">{{ $faq->answer }}</span>
                            </td>
                            <td class="p-3 font-mono text-[11px]">{{ $faq->category }}</td>
                            <td class="p-3 font-mono">{{ $faq->sort_order }}</td>
                            <td class="p-3">
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold {{ $faq->is_published ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-slate-800 text-slate-400' }}">
                                    {{ $faq->is_published ? 'Published' : 'Draft' }}
                                </span>
                            </td>
                            <td class="p-3 text-right">
                                <form method="POST" action="{{ route('admin.website.faqs.destroy', $faq) }}" onsubmit="return confirm('Delete this FAQ question?');" class="inline">
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
                            <td colspan="5" class="p-6 text-center text-slate-500">No FAQs configured yet. Default questions will be shown.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-800">
                {{ $faqs->links() }}
            </div>
        </div>

    </div>

</x-admin-layout>
