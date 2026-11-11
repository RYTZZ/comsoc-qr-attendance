<x-coming-soon-layout
    badgeText="LEVEL UP INCOMING"
    badgeType="discovery"
    headline="Ready, Player One?"
    description="The arena is getting a little upgrade! We're preparing coding challenges, EXP rewards, leaderboards, and exciting battles of logic. Sharpen your skills. Your next coding adventure is loading!"
    progressLabel="COMPILING YOUR NEXT CHALLENGE..."
    buttonText="Back to Base"
    buttonRoute="dashboard"
    smallCaption="404? Nah. Just building something awesome."
    accentColor="rose">

    <!-- Interactive Terminal & Retro Gaming Pixel UI -->
    <div class="relative w-full rounded-2xl bg-[#0B1120] border border-slate-800 p-4 font-mono text-left shadow-xl overflow-hidden group">
        <!-- Terminal Header -->
        <div class="flex items-center justify-between border-b border-slate-800 pb-2 mb-3">
            <div class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-full bg-rose-500/80 inline-block"></span>
                <span class="w-3 h-3 rounded-full bg-amber-500/80 inline-block"></span>
                <span class="w-3 h-3 rounded-full bg-emerald-500/80 inline-block"></span>
                <span class="text-[10px] text-slate-500 font-bold ml-2">arena_compiler.sh</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-[9px] px-1.5 py-0.5 rounded bg-[#560E0F]/40 text-rose-300 font-extrabold uppercase border border-[#8F3033]/50">
                    +150 EXP BOUNTY
                </span>
            </div>
        </div>

        <!-- Terminal Output -->
        <div class="space-y-1.5 text-xs">
            <div class="flex items-center gap-2 text-slate-400">
                <span class="text-rose-400">$</span>
                <span>comsoc-arena init --mode=esports-competitive</span>
            </div>
            <div class="text-emerald-400 text-[11px] flex items-center gap-2">
                <span>✔</span>
                <span>Algorithm sandbox initialized [GCC / V8 / Python3]</span>
            </div>
            <div class="text-amber-300 text-[11px] flex items-center gap-2">
                <span>⚡</span>
                <span>Loading problem sets: Dynamic Programming, Graph Theory, Trees</span>
            </div>
            <div class="text-slate-300 text-[11px] flex items-center gap-2">
                <span class="text-rose-400">⚔</span>
                <span>Syncing student leaderboard & season rank progression...</span>
            </div>
        </div>

        <!-- Floating Gaming Badge Micro-animation -->
        <div class="absolute -bottom-1 -right-1 opacity-80 group-hover:scale-105 transition pointer-events-none p-3">
            <div class="bg-gradient-to-tr from-[#560E0F] to-[#8F3033] p-2.5 rounded-xl border border-rose-400/30 shadow-lg text-center">
                <i data-lucide="gamepad-2" class="w-5 h-5 text-white mx-auto"></i>
                <span class="text-[8px] font-bold text-rose-200 block mt-0.5 tracking-tighter">LVL 99 ARENA</span>
            </div>
        </div>
    </div>

</x-coming-soon-layout>
