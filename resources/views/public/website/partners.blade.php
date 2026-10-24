<x-public-website-layout :settings="$settings" :title="'Partners & Collaborations'">

    <!-- Editorial Header -->
    <section class="py-16 sm:py-24 border-b border-[#334155]/40 bg-gradient-to-b from-[#0B1120] to-[#172033]/40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl space-y-4">
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
    <section class="py-16 sm:py-24 border-b border-[#334155]/40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            @if($partners->isEmpty())
                <div class="py-16 text-center max-w-md mx-auto space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-[#172033]/80 border border-[#334155]/60 flex items-center justify-center mx-auto text-slate-400">
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
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    @foreach($partners as $partner)
                    <article class="flex flex-col justify-between border-b border-[#334155]/50 pb-8 hover:border-[#df747a]/50 transition-colors">
                        <div class="space-y-4">
                            <!-- Natural Logo Showcase (No boxes around boxes) -->
                            <div class="h-20 flex items-center">
                                @if($partner->logo_path)
                                    <img src="{{ asset('storage/' . $partner->logo_path) }}" alt="{{ $partner->name }}" class="max-h-16 max-w-[200px] object-contain filter drop-shadow">
                                @else
                                    <div class="w-12 h-12 rounded-xl bg-[#560E0F]/20 text-[#df747a] flex items-center justify-center border border-[#df747a]/30">
                                        <i data-lucide="handshake" class="w-6 h-6"></i>
                                    </div>
                                @endif
                            </div>

                            <div>
                                <h2 class="text-xl font-bold text-white font-brand-display">
                                    {{ $partner->name }}
                                </h2>
                                @if($partner->collaboration_type)
                                    <span class="inline-block mt-1 px-2.5 py-0.5 rounded-full text-[10px] font-mono font-semibold bg-[#172033] text-emerald-400 border border-emerald-500/20">
                                        {{ $partner->collaboration_type }}
                                    </span>
                                @endif
                            </div>

                            <p class="text-xs text-slate-300 leading-relaxed font-normal">
                                {{ $partner->description }}
                            </p>
                        </div>

                        @if($partner->website_url)
                        <div class="pt-6 mt-6 border-t border-[#334155]/30">
                            <a href="{{ $partner->website_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#df747a] hover:text-white transition font-mono">
                                <span>Official Web Address</span>
                                <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                            </a>
                        </div>
                        @endif
                    </article>
                    @endforeach
                </div>
            @endif

        </div>
    </section>

</x-public-website-layout>
