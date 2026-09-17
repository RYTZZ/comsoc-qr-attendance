<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My QR Code — ComSoc</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Michroma&family=Sora:wght@600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#0f1117] text-slate-100 min-h-screen font-sans">
<header class="bg-[#12141c] border-b border-slate-800/80 sticky top-0 z-20">
    <div class="max-w-sm mx-auto px-4 py-3 flex items-center justify-between">
        <a href="{{ route('student.dashboard') }}" class="text-xs text-slate-400 hover:text-white transition-colors">← Dashboard</a>
        <span class="text-xs font-semibold text-white tracking-wide uppercase font-brand-display">My QR Code</span>
        <a href="{{ route('student.qr.download') }}" class="btn-primary btn-sm">Download</a>
    </div>
</header>
<main class="max-w-sm mx-auto px-3 sm:px-4 py-6 sm:py-8 text-center">
    <div class="card p-4 sm:p-6 shadow-2xl bg-[#171a23] border border-slate-800">
        <div class="flex items-center justify-center gap-2.5 mb-4 pb-3 border-b border-slate-800/80">
            <img src="{{ asset('images/COMSOC.png') }}" alt="Computing Society Logo" class="w-8 h-8 object-contain">
            <span class="font-brand-accent text-xs tracking-wider text-slate-200">COMPUTING SOCIETY</span>
        </div>

        <div class="flex justify-center mb-5">
            <div class="p-3 sm:p-4 bg-white rounded-2xl shadow-xl max-w-full inline-block">
                <div class="w-48 h-48 sm:w-56 sm:h-56 flex items-center justify-center [&>svg]:w-full [&>svg]:h-full">
                    {!! $qrSvg !!}
                </div>
            </div>
        </div>
        <h2 class="text-lg sm:text-xl font-bold text-white truncate">{{ $student->display_name }}</h2>
        <p class="text-slate-400 text-xs sm:text-sm font-mono mt-0.5">{{ $student->student_number }}</p>
        <p class="text-xs text-brand-300 font-semibold mt-1">{{ ($student->program ?: 'BSIT') . ' • ' . ($student->year_level ?: '1st Year') }}</p>
        <div class="divider my-4"></div>
        <div class="grid grid-cols-2 gap-2.5 sm:gap-3 text-xs sm:text-sm text-left">
            <div class="p-2 rounded-lg bg-slate-950/60 border border-slate-800/80">
                <p class="label text-[11px] mb-0.5">Membership #</p>
                <p class="text-white font-mono text-xs truncate">{{ $membership->membership_number }}</p>
            </div>
            <div class="p-2 rounded-lg bg-slate-950/60 border border-slate-800/80">
                <p class="label text-[11px] mb-0.5">Status</p>
                <span class="badge-active text-[10px]">Active Member</span>
            </div>
            <div class="p-2 rounded-lg bg-slate-950/60 border border-slate-800/80">
                <p class="label text-[11px] mb-0.5">Batch</p>
                <p class="text-white font-mono text-xs">#{{ $activeQr->batch_number }}</p>
            </div>
            <div class="p-2 rounded-lg bg-slate-950/60 border border-slate-800/80">
                <p class="label text-[11px] mb-0.5">QR Status</p>
                <span class="badge-active text-[10px]">{{ ucfirst($activeQr->status) }}</span>
            </div>
        </div>
        <div class="flex flex-col gap-2 mt-4">
            <a href="{{ route('student.card.download') }}" class="btn-primary w-full py-2.5 flex items-center justify-center gap-2">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
                Download Official Member Card
            </a>
            <a href="{{ route('student.qr.download') }}" class="btn-secondary w-full py-2 text-xs flex items-center justify-center gap-2">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Download QR Code Only (.png)
            </a>
        </div>
        <p class="text-[11px] text-slate-500 mt-3">This card is linked to your membership. Keep it safe.</p>
    </div>
</main>
</body>
</html>
