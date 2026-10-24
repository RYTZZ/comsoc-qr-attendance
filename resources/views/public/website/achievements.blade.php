<x-public-website-layout :settings="$settings" :title="'Achievements & Milestones'">

    <section class="py-12 sm:py-20 border-b border-[#334155]/60 bg-[#0B1120]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl mx-auto text-center space-y-4">
                <span class="text-xs font-bold text-[#df747a] tracking-widest uppercase font-brand-accent">Organizational Honors</span>
                <h1 class="text-3xl sm:text-5xl font-extrabold text-white font-brand-display tracking-tight">
                    Verified Achievements & Milestones
                </h1>
                <p class="text-base text-slate-300 leading-relaxed">
                    Showcasing verified competitive accomplishments, academic recognitions, and completed milestones of the Computing Society.
                </p>
            </div>
        </div>
    </section>

    <section class="py-12 sm:py-20 border-b border-[#334155]/60">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            @if($achievements->isEmpty())
                <div class="card bg-[#172033] border border-[#334155] p-12 text-center rounded-2xl text-slate-400 text-sm max-w-xl mx-auto">
                    Verified achievements and milestones will be updated by organization administrators.
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($achievements as $ach)
                    <div class="card bg-[#172033] border border-[#334155] rounded-2xl overflow-hidden p-6 flex flex-col justify-between hover:border-[#df747a] transition-all">
                        <div class="space-y-4">
                            @if($ach->image_path)
                                <div class="rounded-xl overflow-hidden aspect-video border border-[#334155] mb-2">
                                    <img src="{{ asset('storage/' . $ach->image_path) }}" alt="{{ $ach->title }}" class="w-full h-full object-cover">
                                </div>
                            @endif

                            <div class="flex items-center justify-between text-xs text-[#df747a]">
                                <span class="font-mono font-bold">{{ $ach->date_or_year ?? 'Verified Milestone' }}</span>
                                <i data-lucide="award" class="w-5 h-5"></i>
                            </div>

                            <h3 class="text-lg font-bold text-white font-brand-display">{{ $ach->title }}</h3>

                            <p class="text-xs text-slate-300 leading-relaxed">{{ $ach->description }}</p>
                        </div>

                        <div class="pt-4 mt-4 border-t border-[#334155]/60 space-y-2">
                            @if($ach->award_details)
                                <div class="text-xs text-amber-300 flex items-center gap-1.5">
                                    <i data-lucide="trophy" class="w-4 h-4 text-amber-400"></i>
                                    <span>{{ $ach->award_details }}</span>
                                </div>
                            @endif
                            @if($ach->verification_reference)
                                <a href="{{ $ach->verification_reference }}" target="_blank" rel="noopener noreferrer" class="text-[11px] text-slate-400 hover:text-white flex items-center gap-1 transition">
                                    <i data-lucide="external-link" class="w-3 h-3"></i>
                                    <span>Verification Reference</span>
                                </a>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="pt-8">
                    {{ $achievements->links() }}
                </div>
            @endif

        </div>
    </section>

</x-public-website-layout>
