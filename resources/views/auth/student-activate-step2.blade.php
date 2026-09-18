<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Activate Account (Step 2) — Computing Society QR Attendance</title>
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
        <h1 class="font-brand-display text-2xl sm:text-3xl font-bold text-white tracking-tight">Account Activation</h1>
        <p class="text-slate-400 mt-1 text-xs sm:text-sm">Step 2 of 4 — Associate Email Address</p>
    </div>

    <div class="card shadow-xl border border-slate-800/80 bg-[#171a23]">
        <div class="p-3 mb-4 rounded-xl bg-slate-900/80 border border-slate-800 flex items-center justify-between">
            <div>
                <p class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold">Verified Student</p>
                <p class="text-sm font-bold text-white">{{ $student->display_name }}</p>
            </div>
            <span class="font-mono text-xs text-indigo-400 bg-indigo-500/10 px-2 py-1 rounded border border-indigo-500/20">{{ $student->student_number }}</span>
        </div>

        <h2 class="font-brand-display text-base font-bold text-white mb-2">Provide Your Email</h2>
        <p class="text-slate-400 text-xs mb-6">Enter the email address you want to associate with your account. A one-time verification code will be sent to it.</p>

        @if($errors->any())
        <div class="alert-error mb-4">
            <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
            <span>{{ $errors->first() }}</span>
        </div>
        @endif

        <form method="POST" action="{{ route('student.activate.email.submit') }}" class="space-y-4">
            @csrf

            <div>
                <label for="email" class="label">Email Address</label>
                <input type="email" name="email" id="email" value="{{ old('email', $currentEmail) }}"
                       class="input" placeholder="youremail@example.com" required autofocus autocomplete="email">
                <p class="text-[11px] text-slate-500 mt-1">This email will be used for account verification and attendance notifications.</p>
            </div>

            <button type="submit" class="btn-primary w-full justify-center py-2.5 mt-2">
                Send Verification OTP →
            </button>
        </form>

        <div class="mt-6 pt-4 border-t border-slate-800/80 flex items-center justify-between text-xs">
            <a href="{{ route('student.activate') }}" class="text-slate-400 hover:text-white transition-colors">
                ← Change Student Number
            </a>
            <a href="{{ route('login') }}" class="text-slate-400 hover:text-white transition-colors">
                Sign In
            </a>
        </div>
    </div>

    <p class="text-center text-xs text-slate-600 mt-6">
        ComSoc QR Attendance v1.0 — Computing Society
    </p>
</div>

</body>
</html>
