<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Forgot Password — COMSOC QR Attendance System.">
    <title>Forgot Password — COMSOC QR Attendance System</title>
    <x-favicon />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@600;700;800;900&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#0B1120] text-[#F8FAFC] font-sans antialiased selection:bg-[#560E0F] selection:text-white flex flex-col justify-between relative overflow-x-hidden p-4 sm:p-6 lg:p-8">

    <!-- Ambient Background Decoration -->
    <x-public-auth-bg />

    <!-- Top Minimal Header / Navigation Pill -->
    <header class="w-full max-w-5xl mx-auto flex items-center justify-between py-2 relative z-10">
        <a href="{{ url('/') }}" class="inline-flex items-center gap-2.5 group focus:outline-none">
            <div class="w-8 h-8 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-center p-1 group-hover:border-[#8F3033]/60 transition">
                <img src="{{ asset('images/COMSOC.png') }}" alt="COMSOC" class="w-full h-full object-contain">
            </div>
            <span class="font-brand-display text-xs sm:text-sm font-bold text-white tracking-wide group-hover:text-rose-300 transition">
                COMSOC Hub
            </span>
        </a>

        <div class="flex items-center gap-2">
            <a href="{{ route('login') }}" class="text-xs text-slate-400 hover:text-white transition font-medium hidden sm:inline-block">
                Remembered? <span class="text-rose-400 font-semibold underline ml-1">Log In</span>
            </a>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-900/80 border border-slate-800 text-slate-400 text-[10px] font-mono">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>V 2.5.1</span>
            </span>
        </div>
    </header>

    <!-- Center Stage Recovery Card (Linear / Vercel style) -->
    <main class="w-full max-w-[440px] mx-auto my-auto relative z-10 py-6 sm:py-10">

        <!-- Centered Brand Identity Header -->
        <div class="text-center mb-6 space-y-2">
            <div class="inline-flex items-center justify-center p-3 rounded-2xl bg-gradient-to-b from-[#1E293B] to-[#0B1120] border border-slate-800 shadow-2xl relative group">
                <div class="absolute -inset-2 bg-rose-500/20 rounded-2xl blur-lg opacity-40 group-hover:opacity-75 transition duration-500"></div>
                <img src="{{ asset('images/COMSOC.png') }}" alt="Computing Society Logo" class="w-14 h-14 object-contain relative z-10 drop-shadow-md">
            </div>
            <div>
                <p class="font-brand-accent text-[10px] font-bold text-[#df747a] tracking-widest uppercase">
                    Account Security
                </p>
                <h1 class="font-brand-display text-2xl sm:text-3xl font-black text-white tracking-tight mt-0.5">
                    Forgot your password?
                </h1>
                <p class="text-xs text-slate-400 font-medium max-w-[340px] mx-auto leading-relaxed">
                    Enter your registered email address and we'll send you a 60-minute password reset token to securely create a new password.
                </p>
            </div>
        </div>

        <!-- Elevated Minimalist Glass Card -->
        <div class="card shadow-2xl border border-slate-800/80 bg-[#172033]/90 backdrop-blur-2xl p-6 sm:p-8 rounded-3xl">

            <!-- Session Status / Feedback -->
            <x-auth-session-status class="mb-4" :status="session('status')" />

            @if($errors->any())
            <div class="alert-error mb-4 text-xs py-2.5 px-3">
                <svg class="w-4 h-4 flex-shrink-0 mt-0.5 text-rose-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <span class="leading-snug">{{ $errors->first() }}</span>
            </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="space-y-4" x-data="{ submitting: false }" @submit="submitting = true">
                @csrf

                <!-- Email Address -->
                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                        Email Address
                    </label>
                    <input id="email" 
                           class="input bg-slate-900/60 border-slate-800 focus:border-[#8F3033] focus:ring-[#8F3033]/40 rounded-xl" 
                           type="email" 
                           name="email" 
                           value="{{ old('email') }}" 
                           placeholder="Enter your registered email address"
                           required 
                           autofocus 
                           autocomplete="email" />
                    <x-input-error :messages="$errors->get('email')" class="mt-1.5 text-xs text-rose-400" />
                </div>

                <div class="pt-2">
                    <button type="submit" 
                            class="btn-primary w-full justify-center py-2.5 shadow-xl disabled:opacity-50 disabled:cursor-not-allowed text-xs sm:text-sm font-semibold tracking-wide flex items-center gap-2 rounded-xl" 
                            :disabled="submitting">
                        <span x-show="!submitting" class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <span>Send Reset Token</span>
                        </span>
                        <span x-show="submitting" class="flex items-center gap-2" style="display: none;">
                            <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span>Sending Token...</span>
                        </span>
                    </button>
                </div>
            </form>

            <div class="mt-6 pt-5 border-t border-slate-800/80 flex items-center justify-between text-xs">
                <p class="text-slate-400">
                    Remember your password?
                    <a href="{{ route('login') }}" class="text-[#df747a] hover:text-white font-semibold transition ml-0.5">
                        Return to Login
                    </a>
                </p>

                <a href="{{ route('account.assistance') }}" class="text-[11px] text-slate-500 hover:text-slate-300 transition">
                    Account Assistance
                </a>
            </div>
        </div>
    </main>

    <!-- Clean Bottom Minimal Footer -->
    <footer class="w-full max-w-5xl mx-auto py-3 text-center text-xs text-slate-500 font-mono relative z-10 flex flex-col sm:flex-row items-center justify-between gap-1 border-t border-slate-800/60">
        <span>ComSoc Attendance System</span>
        <span>Innovating Today for a Better Community</span>
        <span>Developer: Rytzz G. Grieta</span>
    </footer>

</body>
</html>