<x-public-website-layout :settings="$settings" :title="'Partners & Collaborations'">

    <!-- Editorial Header -->
    <section class="py-12 sm:py-16 relative overflow-hidden bg-gradient-to-b from-[#0B1120] to-[#172033]/30">
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

    <!-- "Be a Partner With Us" Value-Proposition Section -->
    <section id="partnership-invitation" class="pt-10 sm:pt-14 pb-12 sm:pb-16 bg-[#172033]/20 border-b border-[#334155]/40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12 sm:space-y-16">
            
            <!-- Section Header: Left-aligned value proposition -->
            <div class="max-w-3xl space-y-4" data-reveal>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#560E0F]/20 border border-[#df747a]/30 text-[#df747a] text-xs font-mono font-semibold uppercase tracking-wider">
                    <i data-lucide="sparkles" class="w-3.5 h-3.5" aria-hidden="true"></i>
                    <span>PARTNERSHIPS & COLLABORATION</span>
                </div>
                
                <h2 class="text-2xl sm:text-4xl lg:text-5xl font-black text-white font-brand-display tracking-tight leading-tight">
                    Let's Build What's Next, Together.
                </h2>
                <span class="heading-accent-line" aria-hidden="true"></span>
                
                <p class="text-base sm:text-lg font-semibold text-[#df747a] tracking-wide">
                    Your expertise. Our community. Shared opportunities for innovation.
                </p>
                
                <p class="text-sm sm:text-base text-slate-300 leading-relaxed font-normal">
                    COMSOC connects aspiring computing professionals with organizations that believe in technology, collaboration, and student development. Together, we can create meaningful experiences, nurture emerging talent, and turn innovative ideas into real-world impact.
                </p>
            </div>

            <!-- Four Partnership Benefit Cards Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 sm:gap-6" data-reveal-group>
                
                <!-- Benefit Card 1: Connect With Emerging Tech Talent -->
                <article data-reveal-item class="group rounded-2xl bg-[#172033]/30 border border-[#334155]/40 hover:border-[#df747a]/60 hover:bg-[#172033]/50 transition-all duration-300 p-6 flex flex-col justify-between shadow-md shadow-black/10">
                    <div class="space-y-4">
                        <div class="w-12 h-12 rounded-xl bg-[#560E0F]/20 border border-[#df747a]/30 text-[#df747a] flex items-center justify-center group-hover:scale-105 transition-transform duration-300">
                            <i data-lucide="code-2" class="w-6 h-6" aria-hidden="true"></i>
                        </div>
                        <h3 class="text-base font-bold text-white font-brand-display tracking-tight leading-snug">
                            Connect With Emerging Tech Talent
                        </h3>
                        <p class="text-xs sm:text-sm text-slate-300 leading-relaxed font-normal">
                            Engage with aspiring computing professionals developing skills in programming, design, problem-solving, and innovation.
                        </p>
                    </div>
                </article>

                <!-- Benefit Card 2: Create Real-World Opportunities -->
                <article data-reveal-item class="group rounded-2xl bg-[#172033]/30 border border-[#334155]/40 hover:border-[#df747a]/60 hover:bg-[#172033]/50 transition-all duration-300 p-6 flex flex-col justify-between shadow-md shadow-black/10">
                    <div class="space-y-4">
                        <div class="w-12 h-12 rounded-xl bg-[#560E0F]/20 border border-[#df747a]/30 text-[#df747a] flex items-center justify-center group-hover:scale-105 transition-transform duration-300">
                            <i data-lucide="rocket" class="w-6 h-6" aria-hidden="true"></i>
                        </div>
                        <h3 class="text-base font-bold text-white font-brand-display tracking-tight leading-snug">
                            Create Real-World Opportunities
                        </h3>
                        <p class="text-xs sm:text-sm text-slate-300 leading-relaxed font-normal">
                            Collaborate on hackathons, ideathons, workshops, technology projects, mentorship programs, and practical learning experiences.
                        </p>
                    </div>
                </article>

                <!-- Benefit Card 3: Expand Your Network -->
                <article data-reveal-item class="group rounded-2xl bg-[#172033]/30 border border-[#334155]/40 hover:border-[#df747a]/60 hover:bg-[#172033]/50 transition-all duration-300 p-6 flex flex-col justify-between shadow-md shadow-black/10">
                    <div class="space-y-4">
                        <div class="w-12 h-12 rounded-xl bg-[#560E0F]/20 border border-[#df747a]/30 text-[#df747a] flex items-center justify-center group-hover:scale-105 transition-transform duration-300">
                            <i data-lucide="share-2" class="w-6 h-6" aria-hidden="true"></i>
                        </div>
                        <h3 class="text-base font-bold text-white font-brand-display tracking-tight leading-snug">
                            Expand Your Network
                        </h3>
                        <p class="text-xs sm:text-sm text-slate-300 leading-relaxed font-normal">
                            Build meaningful connections with students, educators, academic institutions, industry professionals, government agencies, and community organizations across Sorsogon and the Bicol Region.
                        </p>
                    </div>
                </article>

                <!-- Benefit Card 4: Make a Lasting Impact -->
                <article data-reveal-item class="group rounded-2xl bg-[#172033]/30 border border-[#334155]/40 hover:border-[#df747a]/60 hover:bg-[#172033]/50 transition-all duration-300 p-6 flex flex-col justify-between shadow-md shadow-black/10">
                    <div class="space-y-4">
                        <div class="w-12 h-12 rounded-xl bg-[#560E0F]/20 border border-[#df747a]/30 text-[#df747a] flex items-center justify-center group-hover:scale-105 transition-transform duration-300">
                            <i data-lucide="heart-handshake" class="w-6 h-6" aria-hidden="true"></i>
                        </div>
                        <h3 class="text-base font-bold text-white font-brand-display tracking-tight leading-snug">
                            Make a Lasting Impact
                        </h3>
                        <p class="text-xs sm:text-sm text-slate-300 leading-relaxed font-normal">
                            Support technology education, student development, digital literacy, and community initiatives that help prepare students for future opportunities.
                        </p>
                    </div>
                </article>

            </div>

            <!-- Call-to-Action Box: Invitation to Collaborate -->
            <div class="rounded-2xl bg-gradient-to-r from-[#172033]/60 via-[#172033]/40 to-[#0B1120] border border-[#334155]/50 p-6 sm:p-8 md:p-10 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6 shadow-xl" data-reveal>
                <div class="max-w-2xl space-y-2">
                    <h3 class="text-xl sm:text-2xl font-bold text-white font-brand-display tracking-tight">
                        Have an Idea for Collaboration?
                    </h3>
                    <p class="text-xs sm:text-sm text-slate-300 leading-relaxed font-normal">
                        Whether you're an industry partner, academic institution, government agency, or community organization, let's explore how we can work together to create meaningful opportunities for students and the wider community.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto shrink-0">
                    <!-- Primary CTA: Direct mailto link -->
                    <a 
                        href="mailto:computingsocietysorsubc@gmail.com?subject=COMSOC%20Partnership%20Inquiry" 
                        class="btn-primary text-xs sm:text-sm py-2.5 px-5 rounded-xl inline-flex items-center justify-center gap-2 shadow-md w-full sm:w-auto"
                        aria-label="Send a partnership inquiry email to computingsocietysorsubc@gmail.com"
                    >
                        <i data-lucide="mail" class="w-4 h-4" aria-hidden="true"></i>
                        <span>Partner With Us</span>
                    </a>

                    <!-- Secondary CTA: Smooth scroll to the existing Collaborators section -->
                    <a 
                        href="#partners-roster" 
                        class="btn-secondary text-xs sm:text-sm py-2.5 px-5 rounded-xl inline-flex items-center justify-center gap-2 border-[#334155]/60 hover:border-[#df747a] text-slate-300 hover:text-white w-full sm:w-auto"
                        aria-label="Scroll to view existing partners and collaborators"
                    >
                        <i data-lucide="arrow-down" class="w-4 h-4" aria-hidden="true"></i>
                        <span>Explore Our Collaborators</span>
                    </a>
                </div>
            </div>

        </div>
    </section>

    <!-- Partners Grid Section -->
    <section id="partners-roster" class="pt-8 sm:pt-10 pb-16 sm:pb-24 scroll-mt-16">
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
