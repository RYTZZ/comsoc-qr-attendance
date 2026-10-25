<x-public-website-layout :settings="$settings" :title="'Partners & Collaborations'">

    <!-- Editorial Header -->
    <section class="py-16 sm:py-24 relative overflow-hidden bg-gradient-to-b from-[#0B1120] to-[#172033]/30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl space-y-4" data-reveal>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#560E0F]/20 border border-[#df747a]/30 text-[#df747a] text-xs font-mono font-semibold uppercase tracking-wider">
                    <i data-lucide="handshake" class="w-3.5 h-3.5"></i>
                    <span>Alliances & Collaborations</span>
                </div>
                <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black text-white font-brand-display tracking-tight leading-tight">
                    Partners & Collaborators
                </h1>
                <p class="text-base sm:text-lg text-slate-300 leading-relaxed max-w-2xl font-normal">
                    Working together with academic departments, student councils, technology user groups, and industry sponsors to broaden student opportunities and community impact.
                </p>
            </div>
        </div>
    </section>

    <!-- Partners Grid -->
    <section class="py-16 sm:py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            @if($partners->isEmpty())
                <div data-reveal class="py-16 text-center max-w-md mx-auto space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-[#172033]/40 border border-[#334155]/30 flex items-center justify-center mx-auto text-slate-400">
                        <i data-lucide="users" class="w-6 h-6"></i>
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-base font-bold text-white font-brand-display">No Active Partners Listed</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            Confirmed partner collaborations will be published upon approval.
                        </p>
                    </div>
                </div>
            @else
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-8 items-stretch" data-reveal-group>
                    @foreach($partners as $partner)
                        <x-partner-card :partner="$partner" />
                    @endforeach
                </div>
            @endif

        </div>
    </section>

</x-public-website-layout>
