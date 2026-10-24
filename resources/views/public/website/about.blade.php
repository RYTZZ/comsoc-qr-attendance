<x-public-website-layout :settings="$settings" :title="'About Us'">

    <section class="py-12 sm:py-20 border-b border-[#334155]/60 bg-[#0B1120]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl mx-auto text-center space-y-4">
                <span class="text-xs font-bold text-[#df747a] tracking-widest uppercase font-brand-accent">About COMSOC</span>
                <h1 class="text-3xl sm:text-5xl font-extrabold text-white font-brand-display tracking-tight">
                    Empowering Future Computing Leaders
                </h1>
                <p class="text-base text-slate-300 leading-relaxed">
                    {{ $settings['org_intro'] ?? 'The official recognized student organization representing all computing and technology discipline students at Sorsogon State University – Bulan Campus.' }}
                </p>
            </div>
        </div>
    </section>

    <!-- Detailed Background & Narrative -->
    <section class="py-16 sm:py-24 border-b border-[#334155]/60">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            <div class="card bg-[#172033] border border-[#334155] p-8 sm:p-12 rounded-3xl space-y-6">
                <h2 class="text-2xl font-bold text-white font-brand-display">Organization Background</h2>
                <div class="text-sm sm:text-base text-slate-300 leading-relaxed space-y-4">
                    <p>{{ $settings['org_background'] }}</p>
                    <p>
                        COMSOC represents students across Bachelor of Science in Information Technology (BSIT), Bachelor of Science in Information Systems (BSIS), Bachelor of Science in Computer Science (BSCS), and Bachelor of Technical-Vocational Teacher Education (BTVTEd - Major in Computer Programming & Information and Communication Technology).
                    </p>
                </div>
            </div>

            <!-- Mission & Vision -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="card bg-[#172033] border border-[#334155] p-6 sm:p-8 rounded-2xl space-y-4">
                    <div class="w-12 h-12 rounded-xl bg-[#560E0F]/40 border border-[#560E0F] flex items-center justify-center text-[#df747a]">
                        <i data-lucide="target" class="w-6 h-6"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white font-brand-display">Mission</h3>
                    <p class="text-sm text-slate-300 leading-relaxed">{{ $settings['org_mission'] }}</p>
                </div>

                <div class="card bg-[#172033] border border-[#334155] p-6 sm:p-8 rounded-2xl space-y-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-500/10 border border-blue-500/30 flex items-center justify-center text-blue-400">
                        <i data-lucide="eye" class="w-6 h-6"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white font-brand-display">Vision</h3>
                    <p class="text-sm text-slate-300 leading-relaxed">{{ $settings['org_vision'] }}</p>
                </div>
            </div>

            <!-- The Five Pillars -->
            <div class="space-y-6 pt-6">
                <h2 class="text-2xl font-bold text-white font-brand-display text-center">The Five Pillars</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    @foreach($settings['pillars'] ?? [] as $pillar)
                    <div class="card bg-[#172033] border border-[#334155] p-5 rounded-xl space-y-2">
                        <div class="text-[#df747a] mb-2">
                            <i data-lucide="{{ $pillar['icon'] ?? 'check' }}" class="w-5 h-5"></i>
                        </div>
                        <h4 class="text-base font-bold text-white font-brand-display">{{ $pillar['title'] }}</h4>
                        <p class="text-xs text-slate-300 leading-relaxed">{{ $pillar['description'] }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

</x-public-website-layout>
