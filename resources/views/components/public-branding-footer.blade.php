<footer {{ $attributes->merge(['class' => 'text-center my-6 sm:my-8 select-none relative z-10']) }}>
    <div class="inline-flex flex-col items-center max-w-sm mx-auto px-4">
        <!-- COMPUTING SOCIETY ORGANIZATION -->
        <p class="font-brand-accent text-[11px] sm:text-xs font-bold text-[#df747a] tracking-widest uppercase mb-1">
            COMPUTING SOCIETY ORGANIZATION
        </p>

        <!-- QR Attendance -->
        <h2 class="font-brand-display text-xl sm:text-2xl font-black text-white tracking-tight">
            QR Attendance
        </h2>

        <!-- Tagline: Innovating Today for a Better Community. -->
        <p class="text-[11px] sm:text-xs text-[#94A3B8] mt-1 font-medium">
            Innovating Today for a Better Community.
        </p>

        <!-- Compact row: ComSoc Attendance System & V 2.5.1 -->
        <div class="flex items-center justify-between w-full max-w-[260px] sm:max-w-[280px] mx-auto text-xs font-bold text-slate-300 mt-3 pt-2.5 border-t border-[#334155]/60 font-mono">
            <span>ComSoc Attendance System</span>
            <span class="text-[#df747a]">V 2.5.1</span>
        </div>

        <!-- Privacy Notice Link -->
        <div class="mt-2.5 pt-1">
            <a href="{{ route('privacy') }}" class="text-[11px] text-slate-500 hover:text-slate-300 underline transition-colors">
                Privacy Notice
            </a>
        </div>
    </div>
</footer>
