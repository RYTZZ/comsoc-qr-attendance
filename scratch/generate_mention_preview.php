<?php
$html = file_get_contents(__DIR__ . '/../public/preview_discussion.html');
$html = str_replace("newCommentText: ''", "newCommentText: 'Hello @Rytzz '", $html);

// Inject preview items directly into the dropdown container
$target = '<div x-show="mentionDropdownOpen && mentionContext === \'main\'"';
$replace = '<div style="display: block !important;" class="absolute left-0 bottom-full mb-2 w-80 max-w-[calc(100vw-2rem)] bg-[#0B1120] border border-[#334155] rounded-xl shadow-2xl overflow-hidden z-50 py-1 divide-y divide-slate-800/80 backdrop-blur-md">
    <div class="px-3 py-1.5 text-[10px] uppercase font-bold tracking-wider text-slate-400 flex items-center justify-between bg-slate-900/60">
        <span>Mention Member</span>
        <span class="text-rose-400 font-mono text-[11px]">@rytzz</span>
    </div>
    <div class="max-h-52 overflow-y-auto py-1">
        <button type="button" class="w-full text-left px-3 py-2 flex items-center gap-2.5 bg-gradient-to-r from-[#560E0F] to-[#8F3033]/80 text-white text-xs">
            <div class="w-7 h-7 rounded-full bg-[#560E0F] border border-[#8F3033]/60 flex items-center justify-center shrink-0 text-[10px] font-bold text-white">RG</div>
            <div class="min-w-0 flex-1">
                <div class="font-semibold truncate text-slate-100 flex items-center gap-1.5">
                    <span>Rytzz Grieta</span>
                    <span class="text-[9px] uppercase tracking-wider px-1.5 py-0.2 rounded bg-amber-500/20 text-amber-300 font-mono">admin</span>
                </div>
                <div class="text-[10px] text-slate-400 font-mono">@rytzz.grieta</div>
            </div>
        </button>
        <button type="button" class="w-full text-left px-3 py-2 flex items-center gap-2.5 text-slate-200 hover:bg-slate-800/80 text-xs">
            <div class="w-7 h-7 rounded-full bg-[#560E0F] border border-[#8F3033]/60 flex items-center justify-center shrink-0 text-[10px] font-bold text-white">FB</div>
            <div class="min-w-0 flex-1">
                <div class="font-semibold truncate text-slate-100">Faye Buckridge</div>
                <div class="text-[10px] text-slate-400 font-mono">@faye.buckridge</div>
            </div>
        </button>
    </div>
</div>
<!--';

// Comment out the Alpine version in the preview file
$html = str_replace($target, $replace, $html);
$html = str_replace('<!-- Submit Button:', '--> <!-- Submit Button:', $html);

file_put_contents(__DIR__ . '/../public/preview_discussion_mention_visible.html', $html);
echo "Preview file written\n";

