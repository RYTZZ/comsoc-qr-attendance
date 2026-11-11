<x-coming-soon-layout
    badgeText="SOMETHING IS BREWING"
    badgeType="success"
    headline="It's Quiet... Too Quiet."
    description="Your community feed is taking a quick breather. We're preparing a better space for updates, conversations, campus moments, and everything happening in the COMSOC community. Don't worry. The notifications aren't running away!"
    progressLabel="GETTING THE COMMUNITY TALKING..."
    buttonText="Back to Base"
    buttonRoute="dashboard"
    smallCaption="The community never sleeps. Well, almost never."
    accentColor="emerald">

    <!-- Interactive Heartbeat / Pulse Wave & Chat Bubbles Animation -->
    <div class="relative w-full rounded-2xl bg-[#0B1120] border border-slate-800 p-5 shadow-xl overflow-hidden group">
        
        <!-- Live Pulse Wave SVG Animation -->
        <div class="relative h-16 w-full flex items-center justify-center">
            <svg class="w-full h-12 text-emerald-400 stroke-current fill-none" viewBox="0 0 500 100" preserveAspectRatio="none">
                <path d="M0,50 L120,50 L140,20 L160,80 L180,30 L200,60 L220,50 L500,50" 
                      stroke-width="3" 
                      stroke-linecap="round" 
                      stroke-linejoin="round"
                      class="opacity-40" />
                <path d="M0,50 L120,50 L140,20 L160,80 L180,30 L200,60 L220,50 L500,50" 
                      stroke-width="3" 
                      stroke-linecap="round" 
                      stroke-linejoin="round"
                      stroke-dasharray="150 350"
                      class="animate-[dash_3s_linear_infinite]" />
            </svg>

            <!-- Floating Active Pulse Ping Point -->
            <div class="absolute left-1/3 w-3 h-3 rounded-full bg-emerald-400 ring-4 ring-emerald-500/30 animate-ping"></div>
        </div>

        <!-- Chat Bubble Micro-dialogue -->
        <div class="grid grid-cols-2 gap-3 pt-3 border-t border-slate-800/80 text-left text-xs">
            <div class="bg-[#172033] p-2.5 rounded-xl border border-slate-700/60 text-slate-300 flex items-start gap-2">
                <span class="text-emerald-400 text-sm">💬</span>
                <div>
                    <span class="text-[10px] text-slate-400 font-bold block">@anonymous_student</span>
                    <span class="text-[11px] leading-tight block">Has anyone checked Lab 402 AC?</span>
                </div>
            </div>

            <div class="bg-[#172033] p-2.5 rounded-xl border border-slate-700/60 text-slate-300 flex items-start gap-2">
                <span class="text-rose-400 text-sm">🏛️</span>
                <div>
                    <span class="text-[10px] text-rose-300 font-bold block">@comsoc_officer</span>
                    <span class="text-[11px] leading-tight block">Officer response published! ✔</span>
                </div>
            </div>
        </div>

    </div>

</x-coming-soon-layout>
