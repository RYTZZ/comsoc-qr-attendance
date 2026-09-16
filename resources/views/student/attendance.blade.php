<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Attendance — ComSoc</title>
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
            <a href="{{ route('student.attendance') }}" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-[#7A1618] text-white transition-colors">Attendance</a>
            <a href="{{ route('student.incidents') }}" class="px-3 py-1.5 text-xs font-medium rounded-lg text-slate-400 hover:text-white transition-colors">Incidents</a>
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
            <h1 class="text-xl font-bold text-white">Attendance History</h1>
            <p class="text-xs text-slate-400">All recorded scan events for your current QR token</p>
        </div>
        <a href="{{ route('student.dashboard') }}" class="text-xs text-brand-400 hover:underline">← Back to Dashboard</a>
    </div>

    <div class="card overflow-hidden">
        @if(empty($records) || $records->isEmpty())
            <div class="text-center py-12 text-slate-500">
                <svg class="w-12 h-12 mx-auto mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
                <p class="text-sm">No attendance records found yet.</p>
                <p class="text-xs text-slate-600 mt-1">Attend society events and present your QR code at kiosks to record attendance.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-950/60 text-slate-400 border-b border-slate-800">
                        <tr>
                            <th class="py-3 px-4">Event</th>
                            <th class="py-3 px-4">Session</th>
                            <th class="py-3 px-4">Scanned At</th>
                            <th class="py-3 px-4">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @foreach($records as $rec)
                            <tr class="hover:bg-slate-800/30 transition">
                                <td class="py-3 px-4 font-medium text-white">{{ $rec->event->name ?? '—' }}</td>
                                <td class="py-3 px-4 text-slate-300">
                                    {{ $rec->attendanceSession->session_name ?? '—' }}
                                    <span class="text-[10px] text-slate-500 uppercase ml-1">({{ $rec->attendanceSession->session_type ?? 'IN' }})</span>
                                </td>
                                <td class="py-3 px-4 text-slate-400">{{ $rec->scanned_at ? $rec->scanned_at->format('M j, Y h:i A') : '—' }}</td>
                                <td class="py-3 px-4">
                                    @if($rec->is_late)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-500/10 text-amber-400 border border-amber-500/20">LATE</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">ON TIME</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if(method_exists($records, 'links'))
                <div class="p-4 border-t border-slate-800">
                    {{ $records->links() }}
                </div>
            @endif
        @endif
    </div>
</main>

</body>
</html>
