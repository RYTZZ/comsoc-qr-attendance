<x-coming-soon-layout
    badgeText="DEBUGGING THE FUTURE"
    badgeType="discovery"
    headline="Your Code Deserves Better."
    description="We're cooking up a space where ideas meet feedback, bugs meet their match, and developers help developers become better. Your code might have bugs. Our platform shouldn't!"
    progressLabel="REVIEWING SOMETHING AWESOME..."
    buttonText="Back to Base"
    buttonRoute="dashboard"
    smallCaption="Keep calm and debug on."
    accentColor="sky">

    <!-- Interactive Code Editor & Diff Critique Mockup -->
    <div class="relative w-full rounded-2xl bg-[#0B1120] border border-slate-800 p-4 font-mono text-left shadow-xl overflow-hidden group">
        <!-- Editor Header -->
        <div class="flex items-center justify-between border-b border-slate-800 pb-2 mb-3">
            <div class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-full bg-rose-500/80 inline-block"></span>
                <span class="w-3 h-3 rounded-full bg-amber-500/80 inline-block"></span>
                <span class="w-3 h-3 rounded-full bg-emerald-500/80 inline-block"></span>
                <span class="text-[10px] text-slate-400 font-bold ml-2">ShowcaseDiff.php</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-[9px] px-1.5 py-0.5 rounded bg-emerald-500/10 text-emerald-300 font-bold uppercase border border-emerald-500/30 flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    PEER REVIEW ACTIVE
                </span>
            </div>
        </div>

        <!-- Code Snippet with Peer Diff -->
        <div class="space-y-1 text-xs">
            <div class="text-slate-500">1  // Refactoring legacy loop into clean collection pipeline</div>
            <div class="text-rose-400/90 bg-rose-950/30 px-2 py-0.5 rounded font-bold">
                - foreach ($students as $s) { if ($s->exp > 100) $honor[] = $s; }
            </div>
            <div class="text-emerald-300 bg-emerald-950/30 px-2 py-0.5 rounded font-bold">
                + return $students->filter(fn ($s) => $s->exp > 100)->values();
            </div>
            <div class="text-slate-500">4  // Accepted Solution! +75 EXP bounty awarded.</div>
        </div>

        <!-- Reviewer Comment Card Micro-dialogue -->
        <div class="mt-3 p-2.5 rounded-xl bg-[#172033] border border-slate-700/80 flex items-center justify-between gap-3 shadow-md">
            <div class="flex items-center gap-2">
                <div class="w-5 h-5 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-xs font-bold">
                    ✓
                </div>
                <span class="text-[11px] text-slate-300 font-medium">100% test pass rate with cleaner memory footprint!</span>
            </div>
            <span class="text-[9px] text-amber-400 font-mono font-bold shrink-0">+75 EXP</span>
        </div>
    </div>

</x-coming-soon-layout>
