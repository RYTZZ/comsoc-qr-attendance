<x-public-website-layout :settings="$settings" :title="'Leadership & Officers'">

    <!-- Editorial Page Header -->
    <section class="py-12 sm:py-16 relative overflow-hidden bg-gradient-to-b from-[#0B1120] to-[#172033]/30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl space-y-4" data-reveal>
                <span class="text-xs font-bold text-[#df747a] tracking-widest uppercase font-brand-accent">Organization Leadership</span>
                <h1 class="text-3xl sm:text-5xl font-black text-white font-brand-display tracking-tight leading-tight">
                    Officers & Board Members
                </h1>
                <p class="text-base sm:text-lg text-slate-300 leading-relaxed font-normal">
                    Meet the dedicated student leaders guiding the Computing Society across executive governance, academic program advocacy, and technology initiatives.
                </p>
            </div>
        </div>
    </section>

    <!-- Leadership Directory -->
    <section class="pt-8 sm:pt-10 pb-24 sm:pb-32">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">

            @if($officers->isEmpty())
                <div data-reveal class="p-12 text-center rounded-2xl bg-[#172033]/40 border border-[#334155]/30 text-slate-400 text-sm max-w-xl mx-auto">
                    Leadership directory will be published shortly by authorized administrators.
                </div>
            @else
                
                {{-- 1. COMSOC Executive Officers --}}
                @if(isset($grouped['executive']) && $grouped['executive']->isNotEmpty())
                <section class="space-y-6" data-reveal>
                    <div class="pb-3 flex items-center justify-between border-b border-[#334155]/30">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-[#560E0F]/30 border border-[#df747a]/30 flex items-center justify-center text-[#df747a]">
                                <i data-lucide="crown" class="w-4 h-4"></i>
                            </div>
                            <h2 class="text-xl sm:text-2xl font-bold text-white font-brand-display">COMSOC Executive Officers</h2>
                        </div>
                        <span class="text-xs text-slate-400 font-mono">Governing Board</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 sm:gap-6 items-stretch" data-reveal-group>
                        @foreach($grouped['executive'] as $officer)
                            <x-officer-card :officer="$officer" />
                        @endforeach
                    </div>
                </section>
                @endif

                {{-- 2. COMSOC Board of Directors --}}
                @if(isset($grouped['board']) && $grouped['board']->isNotEmpty())
                <section class="space-y-6 pt-6" data-reveal>
                    <div class="pb-3 flex items-center justify-between border-b border-[#334155]/30">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400">
                                <i data-lucide="shield" class="w-4 h-4"></i>
                            </div>
                            <h2 class="text-xl sm:text-2xl font-bold text-white font-brand-display">COMSOC Board of Directors</h2>
                        </div>
                        <span class="text-xs text-slate-400 font-mono">Program Representatives</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 sm:gap-6 items-stretch" data-reveal-group>
                        @foreach($grouped['board'] as $officer)
                            <x-officer-card :officer="$officer" />
                        @endforeach
                    </div>
                </section>
                @endif

                {{-- 3. Liaisons --}}
                @if(isset($grouped['liaison']) && $grouped['liaison']->isNotEmpty())
                <section class="space-y-6 pt-6" data-reveal>
                    <div class="pb-3 flex items-center justify-between border-b border-[#334155]/30">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-sky-500/10 border border-sky-500/20 flex items-center justify-center text-sky-400">
                                <i data-lucide="link-2" class="w-4 h-4"></i>
                            </div>
                            <h2 class="text-xl sm:text-2xl font-bold text-white font-brand-display">Liaisons</h2>
                        </div>
                        <span class="text-xs text-slate-400 font-mono">External & Internal</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 sm:gap-6 items-stretch" data-reveal-group>
                        @foreach($grouped['liaison'] as $officer)
                            <x-officer-card :officer="$officer" />
                        @endforeach
                    </div>
                </section>
                @endif

                {{-- 4. BSIT Program Officers --}}
                @if(isset($grouped['bsit']) && $grouped['bsit']->isNotEmpty())
                <section class="space-y-6 pt-6" data-reveal>
                    <div class="pb-3 flex items-center justify-between border-b border-[#334155]/30">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400">
                                <i data-lucide="terminal" class="w-4 h-4"></i>
                            </div>
                            <h2 class="text-xl sm:text-2xl font-bold text-white font-brand-display">BSIT Program Officers</h2>
                        </div>
                        <span class="text-xs text-amber-400/80 font-mono font-semibold">Information Technology</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 sm:gap-6 items-stretch" data-reveal-group>
                        @foreach($grouped['bsit'] as $officer)
                            <x-officer-card :officer="$officer" />
                        @endforeach
                    </div>
                </section>
                @endif

                {{-- 5. BSCS Program Officers --}}
                @if(isset($grouped['bscs']) && $grouped['bscs']->isNotEmpty())
                <section class="space-y-6 pt-6" data-reveal>
                    <div class="pb-3 flex items-center justify-between border-b border-[#334155]/30">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-cyan-500/10 border border-cyan-500/20 flex items-center justify-center text-cyan-400">
                                <i data-lucide="cpu" class="w-4 h-4"></i>
                            </div>
                            <h2 class="text-xl sm:text-2xl font-bold text-white font-brand-display">BSCS Program Officers</h2>
                        </div>
                        <span class="text-xs text-cyan-400/80 font-mono font-semibold">Computer Science</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 sm:gap-6 items-stretch" data-reveal-group>
                        @foreach($grouped['bscs'] as $officer)
                            <x-officer-card :officer="$officer" />
                        @endforeach
                    </div>
                </section>
                @endif

                {{-- 6. BSIS Program Officers --}}
                @if(isset($grouped['bsis']) && $grouped['bsis']->isNotEmpty())
                <section class="space-y-6 pt-6" data-reveal>
                    <div class="pb-3 flex items-center justify-between border-b border-[#334155]/30">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
                                <i data-lucide="database" class="w-4 h-4"></i>
                            </div>
                            <h2 class="text-xl sm:text-2xl font-bold text-white font-brand-display">BSIS Program Officers</h2>
                        </div>
                        <span class="text-xs text-emerald-400/80 font-mono font-semibold">Information Systems</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 sm:gap-6 items-stretch" data-reveal-group>
                        @foreach($grouped['bsis'] as $officer)
                            <x-officer-card :officer="$officer" />
                        @endforeach
                    </div>
                </section>
                @endif

                {{-- 7. BTVTED Program Officers --}}
                @if(isset($grouped['btvted']) && $grouped['btvted']->isNotEmpty())
                <section class="space-y-6 pt-6" data-reveal>
                    <div class="pb-3 flex items-center justify-between border-b border-[#334155]/30">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-violet-500/10 border border-violet-500/20 flex items-center justify-center text-violet-400">
                                <i data-lucide="book-open" class="w-4 h-4"></i>
                            </div>
                            <h2 class="text-xl sm:text-2xl font-bold text-white font-brand-display">BTVTED Program Officers</h2>
                        </div>
                        <span class="text-xs text-violet-400/80 font-mono font-semibold">Technical Vocational</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 sm:gap-6 items-stretch" data-reveal-group>
                        @foreach($grouped['btvted'] as $officer)
                            <x-officer-card :officer="$officer" />
                        @endforeach
                    </div>
                </section>
                @endif

                {{-- 8. Faculty Advisers --}}
                @if(isset($grouped['adviser']) && $grouped['adviser']->isNotEmpty())
                <section class="space-y-6 pt-6" data-reveal>
                    <div class="pb-3 flex items-center justify-between border-b border-[#334155]/30">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-teal-500/10 border border-teal-500/20 flex items-center justify-center text-teal-400">
                                <i data-lucide="graduation-cap" class="w-4 h-4"></i>
                            </div>
                            <h2 class="text-xl sm:text-2xl font-bold text-white font-brand-display">Faculty Advisers</h2>
                        </div>
                        <span class="text-xs text-teal-400/80 font-mono font-semibold">Academic Mentorship</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 sm:gap-6 items-stretch" data-reveal-group>
                        @foreach($grouped['adviser'] as $officer)
                            <x-officer-card :officer="$officer" />
                        @endforeach
                    </div>
                </section>
                @endif

                {{-- 9. Other Appointed Officers (Fallback so no published officer is omitted) --}}
                @php
                    $knownKeys = ['executive', 'board', 'liaison', 'bsit', 'bscs', 'bsis', 'btvted', 'adviser'];
                    $otherOfficers = $officers->reject(function ($o) use ($knownKeys) {
                        return in_array(strtolower(trim($o->category ?? '')), $knownKeys);
                    });
                @endphp
                @if($otherOfficers->isNotEmpty())
                <section class="space-y-6 pt-6" data-reveal>
                    <div class="pb-3 flex items-center justify-between border-b border-[#334155]/30">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-slate-500/10 border border-slate-500/20 flex items-center justify-center text-slate-400">
                                <i data-lucide="users" class="w-4 h-4"></i>
                            </div>
                            <h2 class="text-xl sm:text-2xl font-bold text-white font-brand-display">Additional Leadership & Appointees</h2>
                        </div>
                        <span class="text-xs text-slate-400 font-mono">Appointed Leaders</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 sm:gap-6 items-stretch" data-reveal-group>
                        @foreach($otherOfficers as $officer)
                            <x-officer-card :officer="$officer" />
                        @endforeach
                    </div>
                </section>
                @endif


            @endif

        </div>
    </section>

</x-public-website-layout>
