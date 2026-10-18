<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Sign in to COMSOC QR Attendance, EXP & Rewards System.">
    <title>Sign In — COMSOC QR Attendance, EXP & Rewards System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@600;700;800&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-[#0B1120] flex items-center justify-center min-h-screen p-4 font-sans text-[#F8FAFC] relative overflow-x-hidden selection:bg-[#560E0F] selection:text-white">

<x-page-loader />
<x-public-auth-bg />

<div class="w-full max-w-md relative z-10">
    <div class="text-center mb-8">
        <div class="w-20 h-20 mx-auto mb-4 flex items-center justify-center p-2 rounded-2xl bg-[#172033]/90 border border-[#334155] shadow-2xl enter-logo backdrop-blur-sm">
            <img src="{{ asset('images/COMSOC.png') }}" alt="Computing Society Logo" class="w-full h-full object-contain">
        </div>
        <div class="enter-header">
            <p class="font-brand-accent text-[11px] text-[#df747a] tracking-widest uppercase mb-1 font-bold">COMPUTING SOCIETY ORGANIZATION</p>
            <h1 class="font-brand-display text-2xl sm:text-3xl font-extrabold text-white tracking-tight">QR Attendance & EXP</h1>
            <p class="text-[#94A3B8] mt-1 text-xs sm:text-sm">Sorsogon State University – Bulan Campus</p>
        </div>
    </div>

    <div class="card shadow-2xl border border-[#334155] bg-[#172033]/95 backdrop-blur-md enter-card">
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
                <label for="email" class="label">Student Number, Email, or Username</label>
                <input type="text" name="email" id="email" value="{{ old('email') }}"
                       placeholder="e.g. 2023-01042 or user@sorsu.edu.ph"
                       class="input" required autofocus autocomplete="username">
            </div>

            <div x-data="{ show: false }">
                <label for="password" class="label">Password</label>
                <div class="relative">
                    <input :type="show ? 'text' : 'password'" name="password" id="password"
                           class="input pr-10" required autocomplete="current-password">
                    <button type="button" @click="show = !show"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-white transition">
                        <template x-if="!show">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </template>
                        <template x-if="show">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                            </svg>
                        </template>
                    </button>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 text-sm text-[#94A3B8] cursor-pointer">
                    <input type="checkbox" name="remember" class="w-4 h-4 rounded bg-[#0B1120] border-[#334155] text-[#560E0F] focus:ring-[#8F3033]">
                    Remember me
                </label>
                @if(Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-sm text-[#df747a] hover:text-white transition-colors">Forgot password?</a>
                @endif
            </div>

            <button type="submit" class="btn-primary w-full justify-center py-3 mt-2 shadow-xl">
                Sign In
            </button>
        </form>

        <div class="mt-6 pt-5 border-t border-[#334155] text-center">
            <p class="text-xs text-[#94A3B8]">
                New student member?
                <a href="{{ route('signup') }}" class="text-[#df747a] hover:text-white font-semibold underline transition">Create Student Account</a>
            </p>
        </div>
    </div>

    <x-public-branding-footer />
</div>

</body>
</html>
