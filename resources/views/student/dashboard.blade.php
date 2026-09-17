<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Dashboard — Computing Society</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Michroma&family=Sora:wght@600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#0f1117] text-slate-100 min-h-screen font-sans">

<header class="bg-[#171a23] border-b border-slate-800 sticky top-0 z-20">
    <div class="max-w-2xl mx-auto px-4 py-3 flex items-center justify-between">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-[#12141c] border border-slate-800 p-0.5 flex items-center justify-center">
                <img src="{{ asset('images/COMSOC.png') }}" alt="Computing Society Logo" class="w-full h-full object-contain">
            </div>
            <span class="font-bold text-white text-sm tracking-tight font-brand-display">Computing Society</span>
        </div>
        <nav class="flex items-center gap-1">
            <a href="{{ route('student.dashboard') }}" class="px-3 py-1.5 text-xs font-semibold rounded-lg {{ request()->routeIs('student.dashboard') ? 'bg-[#7A1618] text-white' : 'text-slate-400 hover:text-white' }} transition-colors">Home</a>
            <a href="{{ route('student.qr') }}" class="px-3 py-1.5 text-xs font-semibold rounded-lg {{ request()->routeIs('student.qr') ? 'bg-[#7A1618] text-white' : 'text-slate-400 hover:text-white' }} transition-colors">My QR</a>
            <a href="{{ route('student.attendance') }}" class="px-3 py-1.5 text-xs font-semibold rounded-lg {{ request()->routeIs('student.attendance') ? 'bg-[#7A1618] text-white' : 'text-slate-400 hover:text-white' }} transition-colors">Attendance</a>
            <form method="POST" action="{{ route('logout') }}" class="ml-2">
                @csrf
                <button type="submit" class="text-xs text-slate-500 hover:text-red-400 transition-colors">Sign out</button>
            </form>
        </nav>
    </div>
</header>

<main class="max-w-2xl mx-auto px-3 sm:px-4 py-4 sm:py-6 space-y-4 sm:space-y-6">
    @if(session('success'))
    <div class="alert-success animate-fade-in text-xs sm:text-sm">{{ session('success') }}</div>
    @endif

    @if($activeQr)
    <div class="card text-center border-indigo-500/30 bg-gradient-to-b from-indigo-950/20 via-slate-900 to-slate-900">
        <div class="mb-2">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 uppercase tracking-wide">
                Active QR Pass
            </span>
        </div>
        <p class="text-xs sm:text-sm font-medium text-slate-300 mb-4">Present this code at event checkpoints</p>
        
        <div class="flex justify-center">
            <a href="{{ route('student.qr') }}" class="inline-block p-3 sm:p-5 bg-white rounded-2xl shadow-xl hover:scale-[1.02] active:scale-[0.99] transition-transform">
                <div class="w-44 h-44 sm:w-56 sm:h-56 flex items-center justify-center [&>svg]:w-full [&>svg]:h-full">
                    {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(220)->margin(0)->generate($activeQr->token) !!}
                </div>
            </a>
        </div>

        <div class="flex flex-col sm:flex-row gap-2 justify-center mt-5 max-w-sm mx-auto">
            <a href="{{ route('student.card.download') }}" class="btn-primary w-full py-2.5 text-xs flex items-center justify-center gap-1.5">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
                Download Member Card
            </a>
            <a href="{{ route('student.qr') }}" class="btn-secondary w-full py-2.5 text-xs flex items-center justify-center gap-1.5">
                Full Details
            </a>
        </div>
        <p class="text-[11px] text-slate-500 mt-3 font-mono">TOKEN: {{ substr($activeQr->token, 0, 16) }}...</p>
    </div>
    @else
    <div class="card text-center">
        <div class="empty-state py-6">
            <div class="empty-state-icon flex items-center justify-center">
                <svg class="w-8 h-8 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <rect x="3" y="3" width="7" height="7" rx="1" stroke-width="1.5"></rect>
                    <rect x="14" y="3" width="7" height="7" rx="1" stroke-width="1.5"></rect>
                    <rect x="3" y="14" width="7" height="7" rx="1" stroke-width="1.5"></rect>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14 14h3m4 0v3m-3 4h4m-4-3v3"></path>
                </svg>
            </div>
            <p class="empty-state-title text-base font-semibold">No Active QR Code</p>
            <p class="empty-state-body text-xs text-slate-400 mt-1">Your QR code will activate once society membership is confirmed for the current academic year.</p>
        </div>
    </div>
    @endif

    @if($membership)
    <div class="card border-slate-800">
        <div class="flex items-center gap-3 sm:gap-4">
            <div class="w-12 h-12 sm:w-14 sm:h-14 gradient-brand rounded-xl flex items-center justify-center flex-shrink-0 shadow-lg text-xl sm:text-2xl font-bold text-white">
                {{ strtoupper(substr($student->first_name, 0, 1)) }}
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-base sm:text-lg font-bold text-white truncate">{{ $student->display_name }}</p>
                <p class="text-xs sm:text-sm text-slate-400 font-mono">{{ $student->student_number }}</p>
                <p class="text-[11px] sm:text-xs text-brand-300 font-medium mt-0.5">{{ ($student->program ?: 'BSIT') . ' • ' . ($student->year_level ?: '1st Year') }}</p>
                <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 mt-1.5">
                    <span class="badge {{ $membership->status === 'active' ? 'badge-active' : 'badge-inactive' }}">
                        {{ ucfirst($membership->status) }} Member
                    </span>
                    @if($activeYear)
                    <span class="text-[10px] sm:text-xs text-slate-500">{{ $activeYear->label }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    @if($recentAttendance->isNotEmpty())
    <div class="card">
        <div class="flex items-center justify-between mb-3">
            <h2 class="section-title mb-0">Recent Attendance</h2>
            <a href="{{ route('student.attendance') }}" class="text-xs text-indigo-400 hover:text-indigo-300 font-medium">View all →</a>
        </div>
        <div class="space-y-2">
            @foreach($recentAttendance as $record)
            <div class="flex items-center justify-between p-3 rounded-lg bg-slate-800/50 hover:bg-slate-800/80 transition gap-2">
                <div class="min-w-0 flex-1">
                    <p class="text-xs sm:text-sm font-semibold text-white truncate">{{ $record->event->name }}</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">
                        {{ $record->attendanceSession->session_name ?? $record->attendanceSession->label ?? 'Session' }}
                        • {{ $record->scanned_at ? $record->scanned_at->format('M d, g:i A') : '—' }}
                    </p>
                </div>
                <div class="flex items-center gap-1.5 shrink-0">
                    <span class="badge {{ $record->is_late ? 'badge-late' : 'badge-present' }}">
                        {{ $record->is_late ? 'LATE' : 'ON TIME' }}
                    </span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</main>

</body>
</html>
