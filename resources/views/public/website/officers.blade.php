<x-public-website-layout :settings="$settings" :title="'Leadership & Officers'">

    <section class="py-12 sm:py-20 border-b border-[#334155]/60 bg-[#0B1120]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl mx-auto text-center space-y-4">
                <span class="text-xs font-bold text-[#df747a] tracking-widest uppercase font-brand-accent">Organization Leadership</span>
                <h1 class="text-3xl sm:text-5xl font-extrabold text-white font-brand-display tracking-tight">
                    Officers & Board Members
                </h1>
                <p class="text-base text-slate-300 leading-relaxed">
                    Meet the dedicated student leaders guiding the Computing Society across executive governance and academic program representation.
                </p>
            </div>
        </div>
    </section>

    <section class="py-16 sm:py-24 border-b border-[#334155]/60">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-16">

            @if($officers->isEmpty())
                <div class="card bg-[#172033] border border-[#334155] p-12 text-center rounded-2xl text-slate-400 text-sm max-w-xl mx-auto">
                    Leadership directory will be published shortly by authorized administrators.
                </div>
            @else
                <!-- Executive Officers -->
                @if($grouped['executive']->isNotEmpty())
                <div class="space-y-6">
                    <div class="border-b border-[#334155]/80 pb-3 flex items-center gap-2">
                        <i data-lucide="crown" class="w-5 h-5 text-amber-400"></i>
                        <h2 class="text-xl font-bold text-white font-brand-display">Executive Officers</h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        @foreach($grouped['executive'] as $officer)
                        <div class="card bg-[#172033] border border-[#334155] p-6 rounded-2xl flex flex-col items-center text-center group hover:border-[#df747a] transition-all">
                            <div class="w-28 h-28 rounded-2xl overflow-hidden bg-[#0B1120] border border-[#334155] mb-4 p-1">
                                @if($officer->portrait_path)
                                    <img src="{{ asset('storage/' . $officer->portrait_path) }}" alt="{{ $officer->name }}" class="w-full h-full object-cover rounded-xl">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-slate-500">
                                        <i data-lucide="user" class="w-12 h-12"></i>
                                    </div>
                                @endif
                            </div>
                            <h3 class="text-base font-bold text-white font-brand-display">{{ $officer->name }}</h3>
                            <p class="text-xs font-semibold text-[#df747a] mt-1">{{ $officer->position }}</p>
                            @if($officer->program)
                                <span class="inline-block mt-2 px-2.5 py-0.5 rounded text-[11px] font-mono bg-[#0B1120] text-slate-300 border border-[#334155]">
                                    {{ $officer->program }}
                                </span>
                            @endif
                            @if($officer->bio)
                                <p class="text-xs text-slate-400 mt-3 line-clamp-3 leading-relaxed">{{ $officer->bio }}</p>
                            @endif
                            @if($officer->social_link)
                                <a href="{{ $officer->social_link }}" target="_blank" rel="noopener noreferrer" class="mt-4 text-[11px] text-slate-400 hover:text-white flex items-center gap-1 transition">
                                    <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                                    <span>Profile</span>
                                </a>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Board Members & Program Representatives -->
                @if($grouped['board']->isNotEmpty())
                <div class="space-y-6">
                    <div class="border-b border-[#334155]/80 pb-3 flex items-center gap-2">
                        <i data-lucide="users" class="w-5 h-5 text-blue-400"></i>
                        <h2 class="text-xl font-bold text-white font-brand-display">Board of Representatives</h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        @foreach($grouped['board'] as $officer)
                        <div class="card bg-[#172033] border border-[#334155] p-6 rounded-2xl flex flex-col items-center text-center group hover:border-[#df747a] transition-all">
                            <div class="w-24 h-24 rounded-2xl overflow-hidden bg-[#0B1120] border border-[#334155] mb-4 p-1">
                                @if($officer->portrait_path)
                                    <img src="{{ asset('storage/' . $officer->portrait_path) }}" alt="{{ $officer->name }}" class="w-full h-full object-cover rounded-xl">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-slate-500">
                                        <i data-lucide="user" class="w-10 h-10"></i>
                                    </div>
                                @endif
                            </div>
                            <h3 class="text-base font-bold text-white font-brand-display">{{ $officer->name }}</h3>
                            <p class="text-xs font-semibold text-[#df747a] mt-1">{{ $officer->position }}</p>
                            @if($officer->program)
                                <span class="inline-block mt-2 px-2 py-0.5 rounded text-[10px] font-mono bg-[#0B1120] text-slate-300 border border-[#334155]">
                                    {{ $officer->program }}
                                </span>
                            @endif
                            @if($officer->bio)
                                <p class="text-xs text-slate-400 mt-2 line-clamp-3 leading-relaxed">{{ $officer->bio }}</p>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Advisers -->
                @if($grouped['adviser']->isNotEmpty())
                <div class="space-y-6">
                    <div class="border-b border-[#334155]/80 pb-3 flex items-center gap-2">
                        <i data-lucide="graduation-cap" class="w-5 h-5 text-emerald-400"></i>
                        <h2 class="text-xl font-bold text-white font-brand-display">Faculty Advisers</h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($grouped['adviser'] as $officer)
                        <div class="card bg-[#172033] border border-[#334155] p-6 rounded-2xl flex flex-col items-center text-center group hover:border-[#df747a] transition-all">
                            <div class="w-24 h-24 rounded-2xl overflow-hidden bg-[#0B1120] border border-[#334155] mb-4 p-1">
                                @if($officer->portrait_path)
                                    <img src="{{ asset('storage/' . $officer->portrait_path) }}" alt="{{ $officer->name }}" class="w-full h-full object-cover rounded-xl">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-slate-500">
                                        <i data-lucide="user" class="w-10 h-10"></i>
                                    </div>
                                @endif
                            </div>
                            <h3 class="text-base font-bold text-white font-brand-display">{{ $officer->name }}</h3>
                            <p class="text-xs font-semibold text-emerald-400 mt-1">{{ $officer->position }}</p>
                            @if($officer->program)
                                <span class="inline-block mt-2 px-2 py-0.5 rounded text-[10px] font-mono bg-[#0B1120] text-slate-300 border border-[#334155]">
                                    {{ $officer->program }}
                                </span>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            @endif

        </div>
    </section>

</x-public-website-layout>
