<x-public-website-layout :settings="$settings" :title="'About Us'">

    <!-- Editorial Page Header -->
    <section class="py-12 sm:py-16 relative overflow-hidden bg-gradient-to-b from-[#0B1120] to-[#172033]/30">
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
    <section class="pt-8 sm:pt-10 pb-16 sm:pb-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-start" data-reveal>
                <div class="lg:col-span-4 space-y-4">
                    <span class="text-xs font-bold text-[#df747a] tracking-widest uppercase font-brand-accent">History & Identity</span>
                    <h2 class="text-2xl sm:text-3xl font-bold text-white font-brand-display">Organization Background</h2>
                    <span class="heading-accent-line" aria-hidden="true"></span>
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

    <!-- President's Message: Editorial Leadership Section -->
    @if($settings['president_message_visible'] ?? true)
    @php
        $presName = $president?->name ?? ($settings['president_name'] ?? 'John Mark M. Esperida');
        $presTitle = $president?->position ?? ($settings['president_title'] ?? 'President, Computing Society');
        $presProgram = $president?->program ?? 'BS Information Technology';
        $presTerm = $settings['president_term'] ?? 'Academic Year 2025–2026';
        $presPortrait = !empty($settings['president_portrait'])
            ? asset('storage/' . $settings['president_portrait'])
            : ($president?->portrait_path ? asset('storage/' . $president->portrait_path) : null);
        $presParagraphs = $settings['president_message_paragraphs'] ?? [];
        if (!is_array($presParagraphs)) {
            $presParagraphs = array_values(array_filter(array_map('trim', preg_split("/\r\n|\n|\r/", (string)$presParagraphs))));
        }
    @endphp
    <section id="presidents-message" class="py-16 sm:py-24 bg-gradient-to-b from-[#0B1120] via-[#172033]/35 to-[#0B1120] relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-16 items-start">

                <!-- Left Column: President Identity & Portrait -->
                <div class="lg:col-span-4 space-y-6" data-reveal>
                    <div class="space-y-4">
                        <div class="relative w-44 h-44 sm:w-52 sm:h-52 rounded-2xl overflow-hidden bg-[#0B1120] border-2 border-[#334155]/60 shadow-xl">
                            @if($presPortrait)
                                <img src="{{ $presPortrait }}" alt="{{ $presName }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex flex-col items-center justify-center text-slate-400 bg-gradient-to-br from-[#172033] to-[#0B1120] p-4 text-center">
                                    <div class="w-14 h-14 rounded-xl bg-[#560E0F]/30 text-[#df747a] flex items-center justify-center mb-2">
                                        <i data-lucide="user-check" class="w-7 h-7"></i>
                                    </div>
                                    <span class="text-xs font-mono uppercase tracking-wider text-slate-400">Executive Office</span>
                                </div>
                            @endif
                        </div>

                        <div class="space-y-1.5 pt-1">
                            <span class="inline-block px-2.5 py-0.5 rounded text-[11px] font-mono font-semibold tracking-wider uppercase bg-[#560E0F]/30 text-[#df747a] border border-[#560E0F]/50">
                                {{ $presTerm }}
                            </span>
                            <h3 class="text-xl sm:text-2xl font-bold text-white font-brand-display tracking-tight leading-tight">
                                {{ $presName }}
                            </h3>
                            <p class="text-xs sm:text-sm font-semibold text-[#df747a]">
                                {{ $presTitle }}
                            </p>
                            <p class="text-xs text-slate-400">
                                Computing Society Organization
                            </p>
                            <p class="text-xs text-slate-500 font-mono">
                                Sorsogon State University – Bulan Campus
                            </p>
                        </div>
                    </div>

                    <div class="p-4 rounded-xl bg-[#172033]/50 border border-[#334155]/40 text-xs text-slate-300 space-y-1.5">
                        <div class="flex items-center gap-2 text-white font-semibold">
                            <i data-lucide="shield-check" class="w-4 h-4 text-[#df747a]"></i>
                            <span>Leadership Direction</span>
                        </div>
                        <p class="text-slate-400 leading-relaxed">
                            Guided by the five pillars: Skills, Creation, Connection, Experience, and Community.
                        </p>
                    </div>
                </div>

                <!-- Right Column: President's Message -->
                <div class="lg:col-span-8 space-y-6" data-reveal-group>
                    <!-- Eyebrow & Headline -->
                    <div data-reveal-item class="space-y-2">
                        <span class="text-xs font-bold text-[#df747a] tracking-widest uppercase font-brand-accent">
                            {{ $settings['president_message_eyebrow'] ?? 'A Message From The President' }}
                        </span>
                        <h2 class="text-2xl sm:text-4xl font-extrabold text-white font-brand-display tracking-tight leading-tight">
                            {{ $settings['president_message_headline'] ?? 'A Computing Society That Does More' }}
                        </h2>
                    </div>

                    <!-- Opening Statement / Anchor Quote -->
                    @if(!empty($settings['president_message_quote']))
                    <div data-reveal-item class="pl-4 sm:pl-5 border-l-2 border-[#560E0F]">
                        <p class="text-base sm:text-lg text-white font-medium italic leading-relaxed">
                            “{{ $settings['president_message_quote'] }}”
                        </p>
                    </div>
                    @endif

                    <!-- Message Paragraphs in Restrained Sequence -->
                    <div class="space-y-4 text-sm sm:text-base text-slate-300 leading-relaxed font-normal">
                        @foreach($presParagraphs as $paragraph)
                            <p data-reveal-item>
                                {{ $paragraph }}
                            </p>
                        @endforeach
                    </div>

                    <!-- Signature Area -->
                    <div data-reveal-item class="pt-6 border-t border-[#334155]/40 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <div class="font-serif italic text-lg sm:text-xl text-white tracking-wide">
                                {{ $presName }}
                            </div>
                            <p class="text-xs text-[#df747a] font-semibold mt-0.5">
                                {{ $presTitle }}
                            </p>
                            <p class="text-[11px] text-slate-400 font-mono">
                                Sorsogon State University – Bulan Campus
                            </p>
                        </div>

                        <div class="flex items-center gap-2 text-xs font-mono text-slate-400">
                            <i data-lucide="award" class="w-4 h-4 text-amber-400"></i>
                            <span>{{ $presTerm }}</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>
    @endif

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
