<x-admin-layout title="Organization Profile & Pillars">

    <div class="space-y-6">

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-5">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-white font-brand-display">Organization Profile & Pillars</h1>
                <p class="text-xs sm:text-sm text-slate-400 mt-1">Manage mission, vision, background narrative, and the five pillars of COMSOC.</p>
            </div>
            <a href="{{ route('public.about') }}" target="_blank" class="btn-secondary text-xs py-2 px-3 flex items-center gap-1.5">
                <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                <span>Preview About Page</span>
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
            <a href="{{ route('admin.website.profile') }}" class="px-3 py-1.5 rounded-lg bg-[#560E0F] text-white font-semibold">Organization Profile & Pillars</a>
            <a href="{{ route('admin.website.officers') }}" class="px-3 py-1.5 rounded-lg text-slate-300 hover:bg-slate-800">Officers & Leadership</a>
            <a href="{{ route('admin.website.activities') }}" class="px-3 py-1.5 rounded-lg text-slate-300 hover:bg-slate-800">Activities & Events Visibility</a>
            <a href="{{ route('admin.website.achievements') }}" class="px-3 py-1.5 rounded-lg text-slate-300 hover:bg-slate-800">Achievements</a>
            <a href="{{ route('admin.website.gallery') }}" class="px-3 py-1.5 rounded-lg text-slate-300 hover:bg-slate-800">Gallery</a>
            <a href="{{ route('admin.website.partners') }}" class="px-3 py-1.5 rounded-lg text-slate-300 hover:bg-slate-800">Partners</a>
            <a href="{{ route('admin.website.faqs') }}" class="px-3 py-1.5 rounded-lg text-slate-300 hover:bg-slate-800">FAQs</a>
        </div>

        <form method="POST" action="{{ route('admin.website.profile.update') }}" class="space-y-8">
            @csrf

            <!-- Intro, Background, Mission, Vision -->
            <div class="card bg-slate-900 border border-slate-800 p-6 rounded-2xl space-y-4">
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <i data-lucide="book-open" class="w-4 h-4 text-[#df747a]"></i>
                    <span>Profile & Constitutional Narrative</span>
                </h3>

                <div class="space-y-4">
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-slate-300">Short Introduction</label>
                        <textarea name="org_intro" rows="2" required class="form-input w-full text-sm">{{ old('org_intro', $settings['org_intro']) }}</textarea>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-slate-300">Extended Organization Background</label>
                        <textarea name="org_background" rows="4" required class="form-input w-full text-sm">{{ old('org_background', $settings['org_background']) }}</textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-xs font-semibold text-slate-300">Official Mission</label>
                            <textarea name="org_mission" rows="4" required class="form-input w-full text-sm">{{ old('org_mission', $settings['org_mission']) }}</textarea>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-xs font-semibold text-slate-300">Official Vision</label>
                            <textarea name="org_vision" rows="4" required class="form-input w-full text-sm">{{ old('org_vision', $settings['org_vision']) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- The Five Pillars -->
            <div class="card bg-slate-900 border border-slate-800 p-6 rounded-2xl space-y-4">
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <i data-lucide="columns-3" class="w-4 h-4 text-[#df747a]"></i>
                    <span>The Five Pillars</span>
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    @foreach($settings['pillars'] ?? [] as $index => $pillar)
                    <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800 space-y-3">
                        <input type="hidden" name="pillars[{{ $index }}][id]" value="{{ $pillar['id'] }}">

                        <div class="space-y-1">
                            <label class="text-[11px] font-semibold text-slate-400">Pillar Title</label>
                            <input type="text" name="pillars[{{ $index }}][title]" value="{{ $pillar['title'] }}" required class="form-input w-full text-xs">
                        </div>

                        <div class="space-y-1">
                            <label class="text-[11px] font-semibold text-slate-400">Lucide Icon</label>
                            <input type="text" name="pillars[{{ $index }}][icon]" value="{{ $pillar['icon'] }}" required class="form-input w-full text-xs font-mono">
                        </div>

                        <div class="space-y-1">
                            <label class="text-[11px] font-semibold text-slate-400">Description</label>
                            <textarea name="pillars[{{ $index }}][description]" rows="3" required class="form-input w-full text-xs">{{ $pillar['description'] }}</textarea>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
                <button type="submit" class="btn-primary py-2.5 px-6 text-sm flex items-center gap-2">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    <span>Save Profile & Pillars</span>
                </button>
            </div>
        </form>

    </div>

</x-admin-layout>
