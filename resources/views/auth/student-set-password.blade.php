<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Set Password — Computing Society QR Attendance</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Michroma&family=Sora:wght@600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-[#0f1117] flex items-center justify-center min-h-screen p-4 font-sans text-slate-100">

<div class="w-full max-w-md animate-slide-up">
    <div class="text-center mb-8">
        <div class="w-20 h-20 mx-auto mb-4 flex items-center justify-center p-2 rounded-2xl bg-[#171a23] border border-slate-800 shadow-md">
            <img src="{{ asset('images/COMSOC.png') }}" alt="Computing Society Logo" class="w-full h-full object-contain">
        </div>
        <p class="font-brand-accent text-[11px] text-[#dfa6a9] tracking-widest uppercase mb-1">COMPUTING SOCIETY</p>
        <h1 class="font-brand-display text-2xl sm:text-3xl font-bold text-white tracking-tight">Create Your Password</h1>
        <p class="text-slate-400 mt-1 text-xs sm:text-sm">Account activation for <span class="text-white font-medium">{{ $user->name }}</span></p>
    </div>

    <div class="card shadow-xl border border-slate-800/80 bg-[#171a23]">
        <h2 class="font-brand-display text-base font-bold text-white mb-2">Set Account Password</h2>
        <p class="text-slate-400 text-xs mb-6">Choose a secure password for your account. You will use this password to sign in.</p>

        @if($errors->any())
        <div class="alert-error mb-4">
            <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
            <span>{{ $errors->first() }}</span>
        </div>
        @endif

        @if(session('success'))
        <div class="alert-success mb-4">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('student.activate.complete') }}" class="space-y-4">
            @csrf

            <div>
                <label for="password" class="label">New Password</label>
                <input type="password" name="password" id="password"
                       class="input" placeholder="••••••••" required autofocus autocomplete="new-password">
                <p class="text-[11px] text-slate-500 mt-1">Must be at least 8 characters long.</p>
            </div>

            <div>
                <label for="password_confirmation" class="label">Confirm New Password</label>
                <input type="password" name="password_confirmation" id="password_confirmation"
                       class="input" placeholder="••••••••" required autocomplete="new-password">
            </div>

            <button type="submit" class="btn-primary w-full justify-center py-2.5 mt-2">
                Activate Account & Finish
            </button>
        </form>
    </div>

    <p class="text-center text-xs text-slate-600 mt-6">
        ComSoc QR Attendance v1.0 — Computing Society
    </p>
</div>

</body>
</html>
