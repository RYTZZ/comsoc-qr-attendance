<x-public-website-layout :settings="$settings" :title="'Partners & Collaborations'">

    <section class="py-12 sm:py-20 border-b border-[#334155]/60 bg-[#0B1120]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl mx-auto text-center space-y-4">
                <span class="text-xs font-bold text-[#df747a] tracking-widest uppercase font-brand-accent">Collaborations</span>
                <h1 class="text-3xl sm:text-5xl font-extrabold text-white font-brand-display tracking-tight">
                    Partners & Collaborators
                </h1>
                <p class="text-base text-slate-300 leading-relaxed">
                    Collaborating with student organizations, technology communities, and industry innovators to elevate student opportunities.
                </p>
            </div>
        </div>
    </section>

    <section class="py-16 sm:py-24 border-b border-[#334155]/60">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            @if($partners->isEmpty())
                <div class="card bg-[#172033] border border-[#334155] p-12 text-center rounded-2xl text-slate-400 text-sm max-w-xl mx-auto">
                    Confirmed partner collaborations will be published upon approval.
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($partners as $partner)
                    <div class="card bg-[#172033] border border-[#334155] rounded-2xl p-6 flex flex-col justify-between hover:border-[#df747a] transition-all">
                        <div class="space-y-4">
                            <div class="h-16 flex items-center">
                                @if($partner->logo_path)
                                    <img src="{{ asset('storage/' . $partner->logo_path) }}" alt="{{ $partner->name }}" class="max-h-12 max-w-[160px] object-contain">
                                @else
                                    <div class="w-12 h-12 rounded-xl bg-[#0B1120] border border-[#334155] flex items-center justify-center text-[#df747a]">
                                        <i data-lucide="handshake" class="w-6 h-6"></i>
                                    </div>
                                @endif
                            </div>

                            <div>
                                <h3 class="text-lg font-bold text-white font-brand-display">{{ $partner->name }}</h3>
                                @if($partner->collaboration_type)
                                    <span class="inline-block mt-1 px-2.5 py-0.5 rounded text-[10px] font-mono bg-[#0B1120] text-emerald-400 border border-emerald-500/20">
                                        {{ $partner->collaboration_type }}
                                    </span>
                                @endif
                            </div>

                            <p class="text-xs text-slate-300 leading-relaxed">{{ $partner->description }}</p>
                        </div>

                        @if($partner->website_url)
                        <div class="pt-4 mt-4 border-t border-[#334155]/60">
                            <a href="{{ $partner->website_url }}" target="_blank" rel="noopener noreferrer" class="text-xs font-semibold text-[#df747a] hover:text-white flex items-center gap-1.5 transition">
                                <span>Visit Partner Website</span>
                                <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                            </a>
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            @endif

        </div>
    </section>

</x-public-website-layout>
