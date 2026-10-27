<x-admin-layout title="Public Activities Visibility">

    <div class="space-y-6">

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-5">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-white font-brand-display">Public Activities Visibility</h1>
                <p class="text-xs sm:text-sm text-slate-400 mt-1">Select which existing system events are featured on the public website without duplicate data entry.</p>
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
            <a href="{{ route('admin.website.activities') }}" class="px-3 py-1.5 rounded-lg bg-[#560E0F] text-white font-semibold">Activities & Events Visibility</a>
            <a href="{{ route('admin.website.achievements') }}" class="px-3 py-1.5 rounded-lg text-slate-300 hover:bg-slate-800">Achievements</a>
            <a href="{{ route('admin.website.gallery') }}" class="px-3 py-1.5 rounded-lg text-slate-300 hover:bg-slate-800">Gallery</a>
            <a href="{{ route('admin.website.partners') }}" class="px-3 py-1.5 rounded-lg text-slate-300 hover:bg-slate-800">Partners</a>
            <a href="{{ route('admin.website.faqs') }}" class="px-3 py-1.5 rounded-lg text-slate-300 hover:bg-slate-800">FAQs</a>
        </div>

        <!-- Events List -->
        <div class="card bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
            <div class="p-4 border-b border-slate-800 flex items-center justify-between">
                <h3 class="text-sm font-bold text-white">System Events ({{ $events->total() }})</h3>
                <span class="text-xs text-slate-400">Events must be published to be visible publicly</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="bg-slate-950/60 text-slate-400 uppercase text-[10px] tracking-wider border-b border-slate-800">
                        <tr>
                            <th class="p-3">Event Name</th>
                            <th class="p-3">Date</th>
                            <th class="p-3">Internal Status</th>
                            <th class="p-3">Public Slug</th>
                            <th class="p-3">Public Visibility</th>
                            <th class="p-3 text-right">Update Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @forelse($events as $event)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="p-3">
                                <span class="font-semibold text-white block">{{ $event->name }}</span>
                                <span class="text-[11px] text-slate-400">{{ $event->effective_venue }}</span>
                            </td>
                            <td class="p-3 font-mono text-[11px]">
                                {{ $event->event_date ? $event->event_date->format('M d, Y') : '—' }}
                            </td>
                            <td class="p-3">
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold border {{ $event->status_badge_class }}">
                                    {{ $event->status_label }}
                                </span>
                            </td>
                            <td class="p-3 font-mono text-[11px] text-slate-400">
                                {{ $event->resolved_slug }}
                            </td>
                            <td class="p-3">
                                @if($event->is_public)
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                        Public Showcase
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-800 text-slate-400">
                                        Internal Only
                                    </span>
                                @endif
                            </td>
                            <td class="p-3 text-right">
                                <form method="POST" action="{{ route('admin.website.activities.toggle', $event) }}" class="inline-flex items-center gap-2">
                                    @csrf
                                    <input type="hidden" name="is_public" value="{{ $event->is_public ? 0 : 1 }}">
                                    <input type="hidden" name="public_slug" value="{{ $event->resolved_slug }}">
                                    <button type="submit" class="btn-secondary text-[11px] py-1 px-2.5">
                                        {{ $event->is_public ? 'Make Private' : 'Make Public' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="p-6 text-center text-slate-500">No events found in the database.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-800">
                {{ $events->links() }}
            </div>
        </div>

    </div>

</x-admin-layout>
