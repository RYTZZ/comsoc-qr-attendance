<x-public-website-layout :settings="$settings" :title="'Leadership & Officers'">

    <!-- Editorial Page Header -->
    <section class="py-16 sm:py-24 relative overflow-hidden bg-gradient-to-b from-[#0B1120] to-[#172033]/30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl space-y-4">
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
    <section class="py-16 sm:py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-16">

            @if($officers->isEmpty())
                <div class="p-12 text-center rounded-2xl bg-[#172033]/40 border border-[#334155]/30 text-slate-400 text-sm max-w-xl mx-auto">
                    Leadership directory will be published shortly by authorized administrators.
                </div>
            @else
                <!-- Executive Officers -->
                @if($grouped['executive']->isNotEmpty())
                <div class="space-y-8">
                    <div class="pb-3 flex items-center justify-between border-b border-[#334155]/30">
                        <div class="flex items-center gap-2.5">
                            <i data-lucide="crown" class="w-5 h-5 text-amber-400"></i>
                            <h2 class="text-xl sm:text-2xl font-bold text-white font-brand-display">Executive Officers</h2>
                        </div>
                        <span class="text-xs text-slate-400 font-mono">Current Term</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        @foreach($grouped['executive'] as $officer)
                        <div class="p-6 rounded-2xl bg-[#172033]/40 border border-[#334155]/30 flex flex-col items-center text-center group hover:border-[#df747a]/50 transition-all">
                            <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-full overflow-hidden bg-[#0B1120] border-2 border-[#334155]/50 group-hover:border-[#df747a] mb-4 transition-colors">
                                @if($officer->portrait_path)
                                    <img src="{{ asset('storage/' . $officer->portrait_path) }}" alt="{{ $officer->name }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-slate-500">
                                        <i data-lucide="user" class="w-10 h-10"></i>
                                    </div>
                                @endif
                            </div>
                            <h3 class="text-base font-bold text-white font-brand-display leading-tight">{{ $officer->name }}</h3>
                            <p class="text-xs font-semibold text-[#df747a] mt-1">{{ $officer->position }}</p>
                            @if($officer->program)
                                <span class="inline-block mt-2 px-2.5 py-0.5 rounded text-[11px] font-mono bg-[#0B1120] text-slate-300 border border-[#334155]/40">
                                    {{ $officer->program }}
                                </span>
                            @endif
                            @if($officer->bio)
                                <p class="text-xs text-slate-400 mt-3 line-clamp-3 leading-relaxed">{{ $officer->bio }}</p>
                            @endif
                            @if($officer->social_link)
                                <a href="{{ $officer->social_link }}" target="_blank" rel="noopener noreferrer" class="mt-4 text-[11px] text-[#df747a] hover:text-white flex items-center gap-1 font-mono transition">
                                    <span>Profile</span>
                                    <i data-lucide="arrow-up-right" class="w-3.5 h-3.5"></i>
                                </a>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Board of Representatives -->
                @if($grouped['board']->isNotEmpty())
                <div class="space-y-8 pt-8">
                    <div class="pb-3 flex items-center justify-between border-b border-[#334155]/30">
                        <div class="flex items-center gap-2.5">
                            <i data-lucide="users" class="w-5 h-5 text-blue-400"></i>
                            <h2 class="text-xl sm:text-2xl font-bold text-white font-brand-display">Board of Representatives</h2>
                        </div>
                        <span class="text-xs text-slate-400 font-mono">Academic Year</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        @foreach($grouped['board'] as $officer)
                        <div class="p-6 rounded-2xl bg-[#172033]/40 border border-[#334155]/30 flex flex-col items-center text-center group hover:border-[#df747a]/50 transition-all">
                            <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-full overflow-hidden bg-[#0B1120] border-2 border-[#334155]/50 group-hover:border-[#df747a] mb-3 transition-colors">
                                @if($officer->portrait_path)
                                    <img src="{{ asset('storage/' . $officer->portrait_path) }}" alt="{{ $officer->name }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-slate-500">
                                        <i data-lucide="user" class="w-8 h-8"></i>
                                    </div>
                                @endif
                            </div>
                            <h3 class="text-sm font-bold text-white font-brand-display leading-tight">{{ $officer->name }}</h3>
                            <p class="text-xs text-slate-300 mt-0.5">{{ $officer->position }}</p>
                            @if($officer->program)
                                <span class="inline-block mt-2 px-2 py-0.5 rounded text-[10px] font-mono bg-[#0B1120] text-slate-400 border border-[#334155]/40">
                                    {{ $officer->program }}
                                </span>
                            @endif
                            @if($officer->social_link)
                                <a href="{{ $officer->social_link }}" target="_blank" rel="noopener noreferrer" class="mt-3 text-[11px] text-[#df747a] hover:text-white flex items-center gap-1 font-mono transition">
                                    <span>Profile</span>
                                    <i data-lucide="arrow-up-right" class="w-3.5 h-3.5"></i>
                                </a>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Advisers & Mentors -->
                @if($grouped['adviser']->isNotEmpty())
                <div class="space-y-8 pt-8">
                    <div class="pb-3 flex items-center justify-between border-b border-[#334155]/30">
                        <div class="flex items-center gap-2.5">
                            <i data-lucide="shield-check" class="w-5 h-5 text-emerald-400"></i>
                            <h2 class="text-xl sm:text-2xl font-bold text-white font-brand-display">Faculty Advisers & Mentors</h2>
                        </div>
                        <span class="text-xs text-slate-400 font-mono">Academic Mentorship</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($grouped['adviser'] as $officer)
                        <div class="p-6 rounded-2xl bg-[#172033]/40 border border-[#334155]/30 flex items-center gap-5 group hover:border-[#df747a]/50 transition-all">
                            <div class="w-20 h-20 rounded-full overflow-hidden bg-[#0B1120] border-2 border-[#334155]/50 group-hover:border-[#df747a] shrink-0 transition-colors">
                                @if($officer->portrait_path)
                                    <img src="{{ asset('storage/' . $officer->portrait_path) }}" alt="{{ $officer->name }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-slate-500">
                                        <i data-lucide="user" class="w-8 h-8"></i>
                                    </div>
                                @endif
                            </div>
                            <div class="space-y-1 text-left">
                                <h3 class="text-base font-bold text-white font-brand-display leading-tight">{{ $officer->name }}</h3>
                                <p class="text-xs font-semibold text-[#df747a]">{{ $officer->position }}</p>
                                <span class="text-[11px] text-slate-400 font-mono block">Department of Computing</span>
                                @if($officer->bio)
                                    <p class="text-xs text-slate-400 line-clamp-2 leading-relaxed pt-1">{{ $officer->bio }}</p>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

            @endif

        </div>
    </section>

</x-public-website-layout>
