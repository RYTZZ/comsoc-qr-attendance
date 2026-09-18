<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Sign in to ComSoc QR Attendance System.">
    <title>Sign In — Computing Society QR Attendance</title>
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
        <h1 class="font-brand-display text-2xl sm:text-3xl font-bold text-white tracking-tight">QR Attendance System</h1>
        <p class="text-slate-400 mt-1 text-xs sm:text-sm">Manage memberships, events, and attendance records</p>
    </div>

    <div class="card shadow-xl border border-slate-800/80 bg-[#171a23]">
        <h2 class="font-brand-display text-base font-bold text-white mb-6">Sign in to your account</h2>

        @if($errors->any())
        <div class="alert-error mb-4">
            <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
            <span>{{ $errors->first() }}</span>
        </div>
        @endif

        @if(session('status'))
        <div class="alert-success mb-4">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <div>
                <label for="email" class="label">Email or Username</label>
                <input type="text" name="email" id="email" value="{{ old('email') }}"
                       class="input" placeholder="you@comsoc.local or username" required autofocus autocomplete="username">
            </div>

            <div>
                <label for="password" class="label">Password</label>
                <input type="password" name="password" id="password"
                       class="input" placeholder="••••••••" required autocomplete="current-password">
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 text-sm text-slate-400 cursor-pointer">
                    <input type="checkbox" name="remember" class="w-4 h-4 rounded bg-slate-800 border-slate-600 text-indigo-600">
                    Remember me
                </label>
                @if(Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-sm text-indigo-400 hover:text-indigo-300 transition-colors">Forgot password?</a>
                @endif
            </div>

            <button type="submit" class="btn-primary w-full justify-center py-2.5 mt-2">
                Sign In
            </button>
        </form>

        <div class="mt-6 pt-5 border-t border-slate-800/80 text-center">
            <p class="text-xs text-slate-400 mb-2">First time signing in or newly enrolled?</p>
            <a href="{{ route('student.activate') }}" class="inline-flex items-center justify-center gap-1.5 w-full py-2 px-4 rounded-xl text-xs font-semibold bg-indigo-500/10 text-indigo-300 border border-indigo-500/25 hover:bg-indigo-500/20 hover:text-white transition-all">
                <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
                Activate Your Account
            </a>
        </div>
    </div>

    <p class="text-center text-xs text-slate-600 mt-6">
        ComSoc QR Attendance v1.0 — Computing Society
    </p>
</div>

</body>
</html>
