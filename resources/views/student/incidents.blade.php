<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Incident Reports — ComSoc</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Michroma&family=Sora:wght@600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#0f1117] text-slate-100 min-h-screen font-sans">

<header class="bg-[#12141c] border-b border-slate-800/80 sticky top-0 z-20">
    <div class="max-w-3xl mx-auto px-4 py-3 flex items-center justify-between">
        <div class="flex items-center gap-2.5">
            <img src="{{ asset('images/COMSOC.png') }}" alt="Computing Society Logo" class="w-8 h-8 object-contain">
            <span class="font-brand-accent text-xs tracking-wider text-slate-200">COMPUTING SOCIETY</span>
        </div>
        <nav class="flex items-center gap-1">
            <a href="{{ route('student.dashboard') }}" class="px-3 py-1.5 text-xs font-medium rounded-lg text-slate-400 hover:text-white transition-colors">Home</a>
            <a href="{{ route('student.qr') }}" class="px-3 py-1.5 text-xs font-medium rounded-lg text-slate-400 hover:text-white transition-colors">My QR</a>
            <a href="{{ route('student.attendance') }}" class="px-3 py-1.5 text-xs font-medium rounded-lg text-slate-400 hover:text-white transition-colors">Attendance</a>
            <a href="{{ route('student.incidents') }}" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-[#7A1618] text-white transition-colors">Incidents</a>
            <form method="POST" action="{{ route('logout') }}" class="ml-2">
                @csrf
                <button type="submit" class="text-xs text-slate-500 hover:text-red-400 transition-colors">Sign out</button>
            </form>
        </nav>
    </div>
</header>

<main class="max-w-3xl mx-auto px-4 py-6 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-white">Incident Records</h1>
            <p class="text-xs text-slate-400">Reports filed involving your account or submitted by you</p>
        </div>
        <a href="{{ route('student.dashboard') }}" class="text-xs text-brand-400 hover:underline">← Back to Dashboard</a>
    </div>

    <div class="card overflow-hidden">
        @if(empty($incidents) || $incidents->isEmpty())
            <div class="text-center py-12 text-slate-500">
                <svg class="w-12 h-12 mx-auto mb-3 opacity-40 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-sm font-medium text-slate-300">Clean Record</p>
                <p class="text-xs text-slate-600 mt-1">No incident reports associated with your profile.</p>
            </div>
        @else
            <div class="divide-y divide-slate-800">
                @foreach($incidents as $inc)
                    <div class="p-4 hover:bg-slate-800/20 transition">
                        <div class="flex items-start justify-between gap-3 mb-2">
                            <div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wider
                                    @if($inc->severity === 'high') bg-red-500/10 text-red-400 border border-red-500/20
                                    @elseif($inc->severity === 'medium') bg-amber-500/10 text-amber-400 border border-amber-500/20
                                    @else bg-blue-500/10 text-blue-400 border border-blue-500/20 @endif">
                                    {{ $inc->severity }} severity
                                </span>
                                <span class="text-xs text-slate-400 ml-2">{{ $inc->category ?? 'General Incident' }}</span>
                            </div>
                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold
                                @if($inc->status === 'resolved') bg-emerald-500/10 text-emerald-400 border border-emerald-500/20
                                @elseif($inc->status === 'archived') bg-slate-500/10 text-slate-400 border border-slate-500/20
                                @else bg-yellow-500/10 text-yellow-400 border border-yellow-500/20 @endif">
                                {{ strtoupper($inc->status) }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-200 mb-2">{{ $inc->description }}</p>
                        <div class="text-[11px] text-slate-500 flex items-center gap-4">
                            <span>Reported: {{ $inc->created_at->format('M j, Y h:i A') }}</span>
                            @if($inc->resolved_at)
                                <span class="text-emerald-400">Resolved: {{ $inc->resolved_at->format('M j, Y') }}</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            @if(method_exists($incidents, 'links'))
                <div class="p-4 border-t border-slate-800">
                    {{ $incidents->links() }}
                </div>
            @endif
        @endif
    </div>
</main>

</body>
</html>
