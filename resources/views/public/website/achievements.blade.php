<x-public-website-layout :settings="$settings" :title="'Achievements & Milestones'">

    <!-- Editorial Header -->
    <section class="py-12 sm:py-16 relative overflow-hidden bg-gradient-to-b from-[#0B1120] to-[#172033]/30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl space-y-4" data-reveal>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#560E0F]/20 border border-[#df747a]/30 text-[#df747a] text-xs font-mono font-semibold uppercase tracking-wider">
                    <i data-lucide="award" class="w-3.5 h-3.5"></i>
                    <span>Verified Distinctions</span>
                </div>
                <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black text-white font-brand-display tracking-tight leading-tight">
                    Verified Achievements & Organizational Honors
                </h1>
                <p class="text-base sm:text-lg text-slate-300 leading-relaxed max-w-2xl font-normal">
                    A permanent record of verified competitive achievements, regional hackathon recognitions, and university distinctions earned by COMSOC and our computing students.
                </p>
            </div>
        </div>
    </section>

    <!-- Achievements Grid / Timeline -->
    <section class="pt-8 sm:pt-10 pb-16 sm:pb-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            @if($achievements->isEmpty())
                <div data-reveal class="py-16 text-center max-w-md mx-auto space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-[#172033]/40 border border-[#334155]/30 flex items-center justify-center mx-auto text-slate-400">
                        <i data-lucide="trophy" class="w-6 h-6"></i>
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-base font-bold text-white font-brand-display">No Published Achievements</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            Verified achievements and milestones will be updated by organization administrators.
                        </p>
                    </div>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8" data-reveal-group>
                    @foreach($achievements as $ach)
                    <article data-reveal-item class="flex flex-col justify-between rounded-2xl bg-[#172033]/30 border border-[#334155]/30 p-6 hover:border-[#df747a]/50 transition-colors">
                        <div class="space-y-4">
                            @if($ach->image_path)
                                <div class="rounded-xl overflow-hidden aspect-video border border-[#334155]/40 bg-[#0B1120] img-reveal-container">
                                    <img src="{{ asset('storage/' . $ach->image_path) }}" alt="{{ $ach->title }}" class="w-full h-full object-cover">
                                </div>
                            @endif

                            <div class="flex items-center justify-between text-xs">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-[#172033] text-[#df747a] border border-[#334155]/40">
                                    {{ $ach->date_or_year ?? 'Verified Milestone' }}
                                </span>
                                <div class="w-7 h-7 rounded-full bg-[#560E0F]/20 text-[#df747a] flex items-center justify-center">
                                    <i data-lucide="award" class="w-4 h-4"></i>
                                </div>
                            </div>

                            <h2 class="text-xl font-bold text-white font-brand-display leading-snug">
                                {{ $ach->title }}
                            </h2>

                            <p class="text-xs text-slate-300 leading-relaxed font-normal">
                                {{ $ach->description }}
                            </p>
                        </div>

                        <div class="pt-5 mt-5 border-t border-[#334155]/20 space-y-2">
                            @if($ach->award_details)
                                <div class="text-xs text-amber-300 flex items-center gap-1.5 font-medium">
                                    <i data-lucide="trophy" class="w-3.5 h-3.5 text-amber-400 shrink-0"></i>
                                    <span>{{ $ach->award_details }}</span>
                                </div>
                            @endif
                            @if($ach->verification_reference)
                                <a href="{{ $ach->verification_reference }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-[11px] text-slate-400 hover:text-white transition font-mono">
                                    <i data-lucide="external-link" class="w-3 h-3 text-[#df747a]"></i>
                                    <span>Official Verification Reference</span>
                                </a>
                            @endif
                        </div>
                    </article>
                    @endforeach
                </div>

                <div class="pt-8 mt-8">
                    {{ $achievements->links() }}
                </div>
            @endif

        </div>
    </section>

</x-public-website-layout>
