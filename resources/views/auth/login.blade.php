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
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#0B1120] text-[#F8FAFC] font-sans antialiased selection:bg-[#560E0F] selection:text-white flex flex-col md:flex-row relative overflow-x-hidden">

    <!-- Ambient Background Decoration -->
    <x-public-auth-bg />

    <!-- ======================================================== -->
    <!-- LEFT BRANDING PANEL (Approx. 41% width on desktop)      -->
    <!-- ======================================================== -->
    <aside class="w-full md:w-[41%] flex flex-col items-center justify-center p-8 sm:p-12 lg:p-16 border-b md:border-b-0 md:border-r border-[#334155] select-none bg-[#0B1120]/70 backdrop-blur-sm relative z-10">
        <div class="w-full max-w-sm mx-auto flex flex-col items-center text-center">
            
            <!-- 1. The official COMSOC Logo (static, preserving aspect ratio, no container box) -->
            <div class="w-28 h-28 sm:w-32 sm:h-32 lg:w-40 lg:h-40 mx-auto mb-5 flex items-center justify-center">
                <img src="{{ asset('images/COMSOC.png') }}" alt="Computing Society Logo" class="w-full h-full object-contain">
            </div>

            <!-- 2. COMPUTING SOCIETY ORGANIZATION -->
            <p class="font-brand-accent text-xs sm:text-sm font-bold text-[#df747a] tracking-widest uppercase mb-1">
                COMPUTING SOCIETY ORGANIZATION
            </p>

            <!-- 3. QR Attendance (bold and prominent product title) -->
            <h1 class="font-brand-display text-3xl sm:text-4xl lg:text-5xl font-black text-white tracking-tight mt-1">
                QR Attendance
            </h1>

            <!-- 4. Tagline: Innovating Today for a Better Community. -->
            <p class="text-xs sm:text-sm text-[#94A3B8] mt-2 font-medium">
                Innovating Today for a Better Community.
            </p>

            <!-- 5. Compact row: ComSoc Attendance System & V 2.5.1 -->
            <div class="flex items-center justify-between w-full max-w-[280px] sm:max-w-[320px] mx-auto text-xs sm:text-sm font-bold text-slate-300 mt-4 pt-3 border-t border-[#334155]/60 font-mono">
                <span>ComSoc Attendance System</span>
                <span class="text-[#df747a]">V 2.5.1</span>
            </div>

            <!-- 6. Developer: Rytzz G. Grieta -->
            <p class="text-xs sm:text-sm font-semibold text-slate-400 mt-2">
                Developer: Rytzz G. Grieta
            </p>
        </div>
    </aside>

    <!-- ======================================================== -->
    <!-- RIGHT AUTHENTICATION PANEL (Approx. 59% width on desktop)-->
    <!-- ======================================================== -->
    <main class="w-full md:w-[59%] flex-1 flex flex-col justify-center items-center p-6 sm:p-10 lg:p-16 relative z-10 overflow-y-auto">
        <div class="w-full max-w-md mx-auto my-auto py-4 sm:py-8">

            <div class="card shadow-2xl border border-[#334155] bg-[#172033]/95 backdrop-blur-md p-6 sm:p-8">
                <!-- Heading replacing "Log In Fields" reference placeholder -->
                <div class="mb-6">
                    <h2 class="font-brand-display text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                        Log In
                    </h2>
                    <p class="text-xs sm:text-sm text-[#94A3B8] mt-1">
                        Sign in to your account to track attendance, manage events, and view EXP.
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
                <form method="POST" action="{{ route('login') }}" class="space-y-4">
                    @csrf

                    <!-- Email, Student Number, or Username -->
                    <div>
                        <label for="email" class="label">
                            Student Number, Email, or Username <span class="text-rose-400">*</span>
                        </label>
                        <input type="text"
                               name="email"
                               id="email"
                               value="{{ old('email') }}"
                               placeholder="Enter your student number, email, or username"
                               class="input"
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
                            <label for="password" class="label mb-0">
                                Password <span class="text-rose-400">*</span>
                            </label>
                        </div>
                        <div class="relative">
                            <input :type="show ? 'text' : 'password'"
                                   name="password"
                                   id="password"
                                   placeholder="Enter your password"
                                   class="input pr-10"
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

                    <!-- Remember Me & Forgot Password -->
                    <div class="flex items-center justify-between pt-1">
                        <label class="flex items-center gap-2 text-sm text-[#94A3B8] cursor-pointer select-none">
                            <input type="checkbox"
                                   name="remember"
                                   class="w-4 h-4 rounded bg-[#0B1120] border-[#334155] text-[#560E0F] focus:ring-[#8F3033]">
                            <span>Remember me</span>
                        </label>

                        @if(Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-sm text-[#df747a] hover:text-white transition-colors">
                            Forgot password?
                        </a>
                        @endif
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-3">
                        <button type="submit"
                                class="btn-primary w-full justify-center py-3 shadow-xl font-semibold tracking-wide flex items-center gap-2">
                            <span>Log In</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                        </button>
                    </div>
                </form>

                <!-- Bottom Navigation: Sign Up & Privacy Notice -->
                <div class="mt-6 pt-5 border-t border-[#334155] flex flex-col sm:flex-row items-center justify-between gap-2 text-xs">
                    <p class="text-[#94A3B8]">
                        New student member?
                        <a href="{{ route('signup') }}" class="text-[#df747a] hover:text-white font-semibold underline transition">
                            Create Student Account
                        </a>
                    </p>

                    <a href="{{ route('privacy') }}" class="text-[11px] text-slate-500 hover:text-slate-300 underline transition">
                        Privacy Notice
                    </a>
                </div>
            </div>

        </div>
    </main>

</body>
</html>
