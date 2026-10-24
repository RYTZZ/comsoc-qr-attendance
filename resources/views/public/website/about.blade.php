<x-public-website-layout :settings="$settings" :title="'About Us'">

    <!-- Editorial Page Header -->
    <section class="py-16 sm:py-24 relative overflow-hidden bg-gradient-to-b from-[#0B1120] to-[#172033]/30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl space-y-4" data-reveal>
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

    <!-- Detailed Narrative & Background (Asymmetric open composition without rigid full-width lines) -->
    <section class="py-16 sm:py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-start" data-reveal>
                <div class="lg:col-span-4 space-y-4">
                    <span class="text-xs font-bold text-[#df747a] tracking-widest uppercase font-brand-accent">History & Identity</span>
                    <h2 class="text-2xl sm:text-3xl font-bold text-white font-brand-display">Organization Background</h2>
                    <p class="text-xs sm:text-sm text-slate-400 leading-relaxed">
                        Established as the recognized governing body for information technology, computer science, and technology education students at Sorsogon State University – Bulan Campus.
                    </p>
                </div>
                <div class="lg:col-span-8 space-y-6 text-sm sm:text-base text-slate-300 leading-relaxed">
                    <p class="text-lg text-white font-medium leading-relaxed">
                        {{ $settings['org_background'] }}
                    </p>
                    <p>
                        COMSOC bridges the gap between academic curriculum and practical industry application. Our membership encompasses students enrolled in:
                    </p>
                    <ul class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2 text-xs font-mono text-slate-300" data-reveal-group>
                        <li data-reveal-item class="p-3 rounded-xl bg-[#172033]/40 border border-[#334155]/30 flex items-center gap-2.5">
                            <i data-lucide="terminal" class="w-4 h-4 text-[#df747a]"></i>
                            <span>Bachelor of Science in Information Technology (BSIT)</span>
                        </li>
                        <li data-reveal-item class="p-3 rounded-xl bg-[#172033]/40 border border-[#334155]/30 flex items-center gap-2.5">
                            <i data-lucide="database" class="w-4 h-4 text-[#df747a]"></i>
                            <span>Bachelor of Science in Information Systems (BSIS)</span>
                        </li>
                        <li data-reveal-item class="p-3 rounded-xl bg-[#172033]/40 border border-[#334155]/30 flex items-center gap-2.5">
                            <i data-lucide="cpu" class="w-4 h-4 text-[#df747a]"></i>
                            <span>Bachelor of Science in Computer Science (BSCS)</span>
                        </li>
                        <li data-reveal-item class="p-3 rounded-xl bg-[#172033]/40 border border-[#334155]/30 flex items-center gap-2.5">
                            <i data-lucide="book-open" class="w-4 h-4 text-[#df747a]"></i>
                            <span>BTVTEd (Major in Computer Programming & ICT)</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Mission & Vision Dual Focus (Open, subtle contrast without harsh bright outlines) -->
    <section class="py-16 sm:py-24 bg-[#172033]/20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-10" data-reveal-group>
                <div data-reveal-item class="p-8 sm:p-10 rounded-2xl bg-[#172033]/40 border border-[#334155]/30 space-y-6 flex flex-col justify-between">
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-[#560E0F]/20 text-[#df747a] flex items-center justify-center">
                                <i data-lucide="target" class="w-5 h-5"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-white font-brand-display">Our Mission</h3>
                        </div>
                        <p class="text-sm sm:text-base text-slate-300 leading-relaxed font-normal">{{ $settings['org_mission'] }}</p>
                    </div>
                    <div class="text-xs font-semibold text-[#df747a] font-mono tracking-wider uppercase">
                        Excellence • Innovation • Service
                    </div>
                </div>

                <div data-reveal-item class="p-8 sm:p-10 rounded-2xl bg-[#172033]/40 border border-[#334155]/30 space-y-6 flex flex-col justify-between">
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-blue-500/10 text-blue-400 flex items-center justify-center">
                                <i data-lucide="eye" class="w-5 h-5"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-white font-brand-display">Our Vision</h3>
                        </div>
                        <p class="text-sm sm:text-base text-slate-300 leading-relaxed font-normal">{{ $settings['org_vision'] }}</p>
                    </div>
                    <div class="text-xs font-semibold text-blue-400 font-mono tracking-wider uppercase">
                        Leadership • Technical Depth • Impact
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- The Five Pillars Detailed Breakdown -->
    <section class="py-16 sm:py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            <div class="max-w-2xl" data-reveal>
                <span class="text-xs font-bold text-[#df747a] tracking-widest uppercase font-brand-accent">Guiding Principles</span>
                <h2 class="text-2xl sm:text-4xl font-extrabold text-white font-brand-display tracking-tight mt-1">
                    The Five Pillars of COMSOC
                </h2>
                <p class="text-sm text-slate-300 mt-2">
                    These foundational tenets guide all activities, programs, and leadership decisions.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6" data-reveal-group>
                @foreach($settings['pillars'] ?? [] as $pillar)
                <div data-reveal-item class="p-6 rounded-2xl bg-[#172033]/40 border border-[#334155]/30 space-y-3 hover:border-[#df747a]/50 transition-colors">
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
