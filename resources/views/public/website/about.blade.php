<x-public-website-layout :settings="$settings" :title="'About Us'">

    <!-- Editorial Page Header -->
    <section class="py-16 sm:py-24 border-b border-[#334155]/40 relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl space-y-4">
                <span class="text-xs font-bold text-[#df747a] tracking-widest uppercase font-brand-accent">About COMSOC</span>
                <h1 class="text-3xl sm:text-5xl font-black text-white font-brand-display tracking-tight leading-tight">
                    Empowering Future Computing Leaders
                </h1>
                <p class="text-base sm:text-lg text-slate-300 leading-relaxed font-normal">
                    {{ $settings['org_intro'] ?? 'The official recognized student organization representing all computing and technology discipline students at Sorsogon State University – Bulan Campus.' }}
                </p>
            </div>
        </div>
    </section>

    <!-- Detailed Narrative & Background (Asymmetric open composition) -->
    <section class="py-16 sm:py-24 border-b border-[#334155]/40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-start">
                <div class="lg:col-span-4 space-y-4">
                    <span class="text-xs font-bold text-[#df747a] tracking-widest uppercase font-brand-accent">History & Identity</span>
                    <h2 class="text-2xl sm:text-3xl font-bold text-white font-brand-display">Organization Background</h2>
                    <p class="text-xs sm:text-sm text-slate-400 leading-relaxed">
                        Established as the recognized governing body for information technology, computer science, and technology education students at Sorsogon State University – Bulan Campus.
                    </p>
                </div>
                <div class="lg:col-span-8 space-y-6 text-sm sm:text-base text-slate-300 leading-relaxed border-l-0 lg:border-l border-[#334155]/40 lg:pl-10">
                    <p class="text-lg text-white font-medium leading-relaxed">
                        {{ $settings['org_background'] }}
                    </p>
                    <p>
                        COMSOC bridges the gap between academic curriculum and practical industry application. Our membership encompasses students enrolled in:
                    </p>
                    <ul class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2 text-xs font-mono text-slate-300">
                        <li class="p-3 rounded-xl bg-[#172033]/60 border border-[#334155]/40 flex items-center gap-2.5">
                            <i data-lucide="terminal" class="w-4 h-4 text-[#df747a]"></i>
                            <span>Bachelor of Science in Information Technology (BSIT)</span>
                        </li>
                        <li class="p-3 rounded-xl bg-[#172033]/60 border border-[#334155]/40 flex items-center gap-2.5">
                            <i data-lucide="database" class="w-4 h-4 text-[#df747a]"></i>
                            <span>Bachelor of Science in Information Systems (BSIS)</span>
                        </li>
                        <li class="p-3 rounded-xl bg-[#172033]/60 border border-[#334155]/40 flex items-center gap-2.5">
                            <i data-lucide="cpu" class="w-4 h-4 text-[#df747a]"></i>
                            <span>Bachelor of Science in Computer Science (BSCS)</span>
                        </li>
                        <li class="p-3 rounded-xl bg-[#172033]/60 border border-[#334155]/40 flex items-center gap-2.5">
                            <i data-lucide="book-open" class="w-4 h-4 text-[#df747a]"></i>
                            <span>BTVTEd (Major in Computer Programming & ICT)</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Mission & Vision Dual Focus -->
    <section class="py-16 sm:py-24 border-b border-[#334155]/40 bg-[#0B1120]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                <div class="p-8 sm:p-10 rounded-2xl bg-[#172033]/60 border border-[#334155]/50 space-y-4 flex flex-col justify-between">
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <i data-lucide="target" class="w-6 h-6 text-[#df747a]"></i>
                            <h3 class="text-2xl font-bold text-white font-brand-display">Our Mission</h3>
                        </div>
                        <p class="text-sm sm:text-base text-slate-300 leading-relaxed">{{ $settings['org_mission'] }}</p>
                    </div>
                    <div class="pt-6 border-t border-[#334155]/40 text-xs font-semibold text-[#df747a]">
                        Excellence • Innovation • Service
                    </div>
                </div>

                <div class="p-8 sm:p-10 rounded-2xl bg-[#172033]/60 border border-[#334155]/50 space-y-4 flex flex-col justify-between">
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <i data-lucide="eye" class="w-6 h-6 text-blue-400"></i>
                            <h3 class="text-2xl font-bold text-white font-brand-display">Our Vision</h3>
                        </div>
                        <p class="text-sm sm:text-base text-slate-300 leading-relaxed">{{ $settings['org_vision'] }}</p>
                    </div>
                    <div class="pt-6 border-t border-[#334155]/40 text-xs font-semibold text-blue-400">
                        Leadership • Technical Depth • Impact
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- The Five Pillars Detailed Breakdown -->
    <section class="py-16 sm:py-24 border-b border-[#334155]/40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            <div class="max-w-2xl">
                <span class="text-xs font-bold text-[#df747a] tracking-widest uppercase font-brand-accent">Guiding Principles</span>
                <h2 class="text-2xl sm:text-4xl font-extrabold text-white font-brand-display tracking-tight mt-1">
                    The Five Pillars of COMSOC
                </h2>
                <p class="text-sm text-slate-300 mt-2">
                    These foundational tenets guide all activities, programs, and leadership decisions.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
                @foreach($settings['pillars'] ?? [] as $pillar)
                <div class="p-6 rounded-2xl bg-[#172033]/60 border border-[#334155]/40 space-y-3">
                    <div class="text-[#df747a]">
                        <i data-lucide="{{ $pillar['icon'] ?? 'check' }}" class="w-5 h-5"></i>
                    </div>
                    <h4 class="text-base font-bold text-white font-brand-display">{{ $pillar['title'] }}</h4>
                    <p class="text-xs text-slate-300 leading-relaxed">{{ $pillar['description'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

</x-public-website-layout>
