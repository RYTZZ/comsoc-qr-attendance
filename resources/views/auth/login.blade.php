<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Log In to COMSOC QR Attendance System.">
    <title>Log In — COMSOC QR Attendance System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@600;700;800;900&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
    <style>
        /* Critical auth layout: independent of the compiled Vite bundle. */
        .auth-branding-aside, .auth-content-main { width: 100%; min-width: 0; box-sizing: border-box; }
        @media (min-width: 768px) {
            .auth-branding-aside { width: 41.666667%; max-width: 41.666667%; flex: 0 0 41.666667%; }
            .auth-content-main { width: 58.333333%; max-width: 58.333333%; flex: 1 1 58.333333%; }
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#0B1120] text-[#F8FAFC] font-sans antialiased selection:bg-[#560E0F] selection:text-white flex flex-col md:flex-row relative overflow-x-hidden">

    <!-- Ambient Background Decoration -->
    <x-public-auth-bg />

    <!-- ======================================================== -->
    <!-- LEFT BRANDING PANEL (Approx. 41.6% width on desktop)     -->
    <!-- ======================================================== -->
    <aside class="auth-branding-aside w-full md:w-5/12 flex flex-col items-center justify-between p-8 sm:p-12 lg:p-16 border-b md:border-b-0 md:border-r border-slate-800/80 select-none bg-gradient-to-b from-[#111827]/90 via-[#0B1120] to-[#0B1120] backdrop-blur-md relative z-10 shrink-0">

        <!-- Top ambient micro-pill -->
        <div class="w-full flex items-center justify-center md:justify-start">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-rose-500/10 border border-rose-500/20 text-[#df747a] text-[10px] font-bold uppercase tracking-wider shadow-xs">
                <span class="w-1.5 h-1.5 rounded-full bg-rose-400 animate-pulse"></span>
                <span>COMSOC Hub Portal</span>
            </span>
        </div>

        <div class="w-full max-w-sm mx-auto flex flex-col items-center text-center my-auto py-8">
            <!-- 1. The official COMSOC Logo with ambient glow -->
            <div class="relative group mb-6">
                <div class="absolute -inset-4 bg-gradient-to-r from-[#560E0F]/40 to-rose-600/20 rounded-full blur-xl opacity-60 group-hover:opacity-100 transition duration-500 pointer-events-none"></div>
                <div class="w-28 h-28 sm:w-32 sm:h-32 lg:w-36 lg:h-36 relative flex items-center justify-center">
                    <img src="{{ asset('images/COMSOC.png') }}" alt="Computing Society Logo" class="w-full h-full object-contain drop-shadow-2xl">
                </div>
            </div>

            <!-- 2. COMPUTING SOCIETY ORGANIZATION -->
            <p class="font-brand-accent text-xs font-bold text-[#df747a] tracking-widest uppercase mb-1">
                COMPUTING SOCIETY ORGANIZATION
            </p>

            <!-- 3. QR Attendance (bold and prominent product title) -->
            <h1 class="font-brand-display text-3xl sm:text-4xl font-black text-white tracking-tight">
                QR Attendance
            </h1>

            <!-- 4. Tagline: Innovating Today for a Better Community. -->
            <p class="text-xs sm:text-sm text-slate-400 mt-2 font-medium max-w-[280px]">
                Innovating Today for a Better Community.
            </p>
        </div>

        <!-- 5. Subtle Footer Credentials -->
        <div class="w-full max-w-xs text-center border-t border-slate-800/80 pt-4 font-mono text-[11px] text-slate-500 flex items-center justify-between">
            <span>ComSoc Attendance System</span>
            <span class="text-[#df747a] font-bold">V 2.5.1</span>
        </div>
    </aside>

    <!-- ======================================================== -->
    <!-- RIGHT AUTHENTICATION PANEL (Approx. 58.3% width on desktop)-->
    <!-- ======================================================== -->
    <main class="auth-content-main w-full md:w-7/12 flex-1 flex flex-col justify-center items-center p-6 sm:p-10 lg:p-16 relative z-10 overflow-y-auto">
        <div class="w-full max-w-[420px] mx-auto my-auto py-4 sm:py-8">

            <div class="card shadow-2xl border border-slate-800/80 bg-[#172033]/90 backdrop-blur-xl p-6 sm:p-8 rounded-3xl">
                <!-- Heading -->
                <div class="mb-6">
                    <h2 class="font-brand-display text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                        Log In
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">
                        Sign in to track attendance, manage events, and view EXP.
                    </p>
                </div>

                <!-- Validation Feedback / Alerts -->
                @if($errors->any())
                <div class="alert-error mb-4">
                    <svg class="w-4 h-4 flex-shrink-0 mt-0.5 text-rose-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-xs sm:text-sm">{{ $errors->first() }}</span>
                </div>
                @endif

                @if(session('status'))
                <div class="alert-success mb-4">
                    <svg class="w-4 h-4 flex-shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span class="text-xs sm:text-sm">{{ session('status') }}</span>
                </div>
                @endif

                <!-- Log In Form -->
                <form method="POST" action="{{ route('login') }}" class="space-y-4" onsubmit="try { localStorage.removeItem('comsoc_session_replaced'); localStorage.setItem('comsoc_last_activity', Date.now().toString()); } catch (e) {}">
                    @csrf

                    <!-- Email, Student Number, or Username -->
                    <div>
                        <label for="email" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                            Student Number, Email, or Username
                        </label>
                        <input type="text"
                               name="email"
                               id="email"
                               value="{{ old('email') }}"
                               placeholder="e.g. 21-00123 or student@sorsu.edu.ph"
                               class="input bg-slate-900/60 border-slate-800 focus:border-[#8F3033] focus:ring-[#8F3033]/40 rounded-xl"
                               required
                               autofocus
                               autocomplete="username">
                        @error('email')
                            <p class="text-xs text-rose-400 mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password with Visibility Control -->
                    <div x-data="{ show: false }">
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="password" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">
                                Password
                            </label>
                        </div>
                        <div class="relative">
                            <input :type="show ? 'text' : 'password'"
                                   name="password"
                                   id="password"
                                   placeholder="Enter your account password"
                                   class="input pr-10 bg-slate-900/60 border-slate-800 focus:border-[#8F3033] focus:ring-[#8F3033]/40 rounded-xl"
                                   required
                                   autocomplete="current-password">
                            <button type="button"
                                    @click="show = !show"
                                    aria-label="Toggle password visibility"
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
                        @error('password')
                            <p class="text-xs text-rose-400 mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Remember Me & Forgot Password Row -->
                    <div class="flex items-center justify-between pt-1">
                        <label class="inline-flex items-center gap-2 text-xs text-slate-400 hover:text-slate-300 cursor-pointer select-none transition-colors">
                            <input type="checkbox"
                                   name="remember"
                                   class="w-4 h-4 rounded bg-[#0B1120] border-slate-700 text-[#560E0F] focus:ring-[#8F3033] transition">
                            <span>Remember me</span>
                        </label>

                        <a href="{{ route('password.request') }}" class="text-xs text-[#df747a] hover:text-white font-medium transition-colors">
                            Forgot Password?
                        </a>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button type="submit"
                                class="btn-primary w-full justify-center py-2.5 shadow-xl font-semibold tracking-wide flex items-center gap-2 rounded-xl">
                            <span>Log In</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Account Assistance Help CTA -->
                    <div class="pt-1 text-center">
                        <p class="text-xs text-slate-400">
                            Having trouble accessing?
                            <a href="{{ route('account.assistance') }}" class="text-[#df747a] hover:text-white font-semibold transition-colors ml-1 inline-flex items-center gap-1">
                                <span>Get Help</span>
                                <svg class="w-3.5 h-3.5 inline-block opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        </p>
                    </div>
                </form>

                <!-- Bottom Navigation: Sign Up & Privacy Notice -->
                <div class="mt-6 pt-5 border-t border-slate-800/80 flex items-center justify-between text-xs">
                    <p class="text-slate-400">
                        New student?
                        <a href="{{ route('signup') }}" class="text-[#df747a] hover:text-white font-semibold transition ml-0.5">
                            Create Account
                        </a>
                    </p>

                    <a href="{{ route('privacy') }}" class="text-[11px] text-slate-500 hover:text-slate-300 transition">
                        Privacy Notice
                    </a>
                </div>
            </div>

        </div>
    </main>

</body>
</html>
