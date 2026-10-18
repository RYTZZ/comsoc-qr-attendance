<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Server Notice | {{ config('app.name', 'COMSOC QR Attendance') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#0B1120] text-[#F8FAFC] min-h-screen flex items-center justify-center p-4 font-sans antialiased selection:bg-[#560E0F] selection:text-white">
    <div class="max-w-md w-full bg-[#172033] border border-[#334155] rounded-3xl p-6 sm:p-8 text-center shadow-2xl relative overflow-hidden">
        <div class="absolute -top-16 -right-16 w-32 h-32 bg-[#560E0F]/20 rounded-full blur-2xl pointer-events-none"></div>
        <div class="absolute -bottom-16 -left-16 w-32 h-32 bg-[#8F3033]/15 rounded-full blur-2xl pointer-events-none"></div>

        <div class="w-16 h-16 rounded-2xl bg-[#560E0F]/25 border border-[#8F3033]/40 flex items-center justify-center text-rose-400 mx-auto mb-5 shadow-lg shadow-[#560E0F]/20">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>

        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-rose-500/20 text-rose-300 border border-rose-500/30">
            System Alert
        </span>

        <h1 class="text-xl sm:text-2xl font-bold font-brand-display text-white mt-3">
            Temporary System Notice
        </h1>

        <p class="text-xs sm:text-sm text-[#94A3B8] mt-2.5 leading-relaxed">
            {{ $message ?? 'A database error occurred while processing your request. Please try again shortly or contact the COMSOC administrators.' }}
        </p>

        @if(!empty($correlationId))
            <p class="text-[11px] font-mono text-slate-400 mt-2 bg-slate-800/60 py-1 px-2.5 rounded-lg border border-slate-700/50 inline-block">
                Reference ID: <span class="text-rose-300 font-semibold select-all">{{ $correlationId }}</span>
            </p>
        @endif

        <div class="mt-6 flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="{{ url('/') }}" class="w-full sm:w-auto px-5 py-2.5 rounded-xl font-semibold text-xs text-white bg-[#560E0F] hover:bg-[#8F3033] transition shadow-md focus:outline-none focus:ring-2 focus:ring-[#560E0F] focus:ring-offset-2 focus:ring-offset-[#172033]">
                Return Home
            </a>
            <button onclick="window.location.reload()" class="w-full sm:w-auto px-5 py-2.5 rounded-xl font-semibold text-xs text-slate-300 bg-[#1E293B] hover:bg-slate-700 hover:text-white border border-[#334155] transition">
                Retry Action
            </button>
        </div>
    </div>
</body>
</html>
