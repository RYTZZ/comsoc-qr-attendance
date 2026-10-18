<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-black text-white tracking-tight flex items-center gap-3">
                    <span class="w-2.5 h-8 bg-[#560E0F] rounded-full inline-block"></span>
                    <span>Announcements</span>
                </h2>
                <p class="text-xs text-slate-400 mt-1">
                    Official bulletins, activities schedule notices, seasonal updates, and urgent alerts from the Computing Society.
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('announcements.index', ['category' => 'all']) }}"
                       class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-colors {{ $selectedCategory === 'all' ? 'bg-[#560E0F] text-white shadow' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                        All Announcements
                    </a>
                    @foreach($categories as $key => $label)
                    <a href="{{ route('announcements.index', ['category' => $key]) }}"
                       class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-colors {{ $selectedCategory === $key ? 'bg-[#560E0F] text-white shadow' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                        {{ $label }}
                    </a>
                    @endforeach
                </div>
            </div>

            @if($announcements->isEmpty())
                <div class="card bg-[#172033] border border-slate-800 p-12 text-center">
                    <div class="w-14 h-14 mx-auto rounded-full bg-slate-800/80 border border-slate-700 flex items-center justify-center text-slate-500 mb-3">
                        <i data-lucide="megaphone" class="w-7 h-7"></i>
                    </div>
                    <h3 class="text-base font-bold text-white font-brand-display">No Active Announcements</h3>
                    <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                        There are no active bulletins in this category right now.
                    </p>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($announcements as $ann)
                    @php
                        $priorityBadge = match($ann->priority) {
                            'urgent' => 'bg-rose-500/20 text-rose-300 border-rose-500/40',
                            'high' => 'bg-amber-500/20 text-amber-300 border-amber-500/40',
                            'low' => 'bg-slate-800 text-slate-400 border-slate-700',
                            default => 'bg-blue-500/20 text-blue-300 border-blue-500/30',
                        };
                    @endphp
                    <div class="card bg-[#172033] border border-slate-800 p-5 rounded-2xl hover:border-slate-700 transition">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                            <div class="flex items-center gap-2.5">
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold border uppercase tracking-wider {{ $priorityBadge }}">
                                    {{ $ann->priority }}
                                </span>
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-slate-800 text-slate-300 border border-slate-700">
                                    {{ ucfirst($ann->category) }}
                                </span>
                            </div>

                            <span class="text-xs text-slate-400">
                                Published {{ $ann->published_at ? $ann->published_at->format('M d, Y') : $ann->created_at->format('M d, Y') }}
                            </span>
                        </div>

                        <h3 class="text-lg font-bold text-white mb-2 font-brand-display">
                            <a href="{{ route('announcements.show', $ann) }}" class="hover:text-indigo-400 transition-colors">
                                {{ $ann->title }}
                            </a>
                        </h3>

                        <div class="text-xs sm:text-sm text-slate-300 leading-relaxed whitespace-pre-line line-clamp-3">
                            {{ $ann->body }}
                        </div>

                        <div class="flex items-center justify-between mt-4 pt-3 border-t border-slate-800/80">
                            <div class="text-xs text-slate-500">
                                Posted by {{ $ann->poster?->name ?? 'ComSoc Executive Board' }}
                            </div>

                            <a href="{{ route('announcements.show', $ann) }}" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300 flex items-center gap-1.5">
                                <span>Read Full Notice</span>
                                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $announcements->withQueryString()->links() }}
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
