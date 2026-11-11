<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Create New Password - {{ config('app.name', 'COMSOC Hub') }}</title>

    <x-favicon />

    <!-- Preconnect to Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="min-h-screen bg-[#0B1120] text-slate-100 font-sans antialiased selection:bg-[#8F3033] selection:text-white flex flex-col justify-between relative overflow-x-hidden">

    <!-- Ambient Background Glow Elements -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden z-0" aria-hidden="true">
        <div class="absolute -top-40 left-1/2 -translate-x-1/2 w-[600px] h-[350px] bg-gradient-to-b from-[#8F3033]/25 via-[#6B2426]/10 to-transparent blur-3xl rounded-full"></div>
        <div class="absolute bottom-0 right-0 w-[450px] h-[300px] bg-gradient-to-tl from-[#df747a]/10 via-[#8F3033]/5 to-transparent blur-3xl rounded-full"></div>
        <div class="absolute inset-0 bg-[linear-gradient(to_right,#1f293d0a_1px,transparent_1px),linear-gradient(to_bottom,#1f293d0a_1px,transparent_1px)] bg-[size:4rem_4rem]"></div>
    </div>

    <!-- Top Minimal Navigation -->
    <header class="relative z-10 w-full px-6 py-5 flex items-center justify-between max-w-7xl mx-auto">
        <a href="{{ route('login') }}" class="inline-flex items-center gap-2.5 transition opacity-90 hover:opacity-100">
            <div class="w-8 h-8 rounded-lg bg-[#172033] border border-slate-700/60 p-1 flex items-center justify-center">
                <img src="{{ asset('images/COMSOC.png') }}" alt="COMSOC" class="w-full h-full object-contain">
            </div>
            <span class="font-bold tracking-tight text-white text-base">
                COMSOC <span class="text-[#df747a]">Hub</span>
            </span>
        </a>

        <div class="flex items-center gap-4 text-xs font-medium">
            <span class="text-slate-400 hidden sm:inline">Already remember?</span>
            <a href="{{ route('login') }}" class="text-slate-200 hover:text-white px-3 py-1.5 rounded-lg border border-slate-700/60 bg-slate-800/40 hover:bg-slate-800 transition">
                Sign In
            </a>
        </div>
    </header>

    <!-- Main Content: Centered Minimalist Card -->
    <main class="relative z-10 flex-1 flex flex-col items-center justify-center px-4 py-8 sm:px-6">
        <div class="w-full max-w-[440px]">

            <!-- Top Logo Avatar with Halo -->
            <div class="flex justify-center mb-6">
                <div class="relative">
                    <div class="absolute inset-0 rounded-2xl bg-gradient-to-tr from-[#8F3033] to-[#df747a] blur-xl opacity-40 animate-pulse"></div>
                    <div class="relative w-14 h-14 rounded-2xl bg-[#172033] border border-slate-700/70 p-2 flex items-center justify-center shadow-2xl">
                        <img src="{{ asset('images/COMSOC.png') }}" alt="Computing Society Logo" class="w-full h-full object-contain relative z-10 drop-shadow-md">
                    </div>
                </div>
            </div>

            <!-- Card Container -->
            <div class="rounded-2xl border border-slate-800/80 bg-[#172033]/85 backdrop-blur-2xl shadow-2xl shadow-black/50 p-6 sm:p-8">

                <!-- Header Title & Subtitle -->
                <div class="text-center mb-6">
                    <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-white">Create New Password</h1>
                    <p class="text-xs sm:text-sm text-slate-400 mt-2 leading-relaxed">
                        Code verified! Set a strong password for
                        <span class="inline-block font-medium text-white px-2 py-0.5 rounded-md bg-slate-800/80 border border-slate-700/60 mt-1 break-all">
                            {{ $email }}
                        </span>
                    </p>
                </div>

                <!-- Session Status / Alert Messages -->
                @if (session('status'))
                    <div class="mb-5 p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs flex items-start gap-2.5">
                        <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>{{ session('status') }}</span>
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-5 p-3 rounded-xl bg-rose-500/10 border border-rose-500/25 text-rose-300 text-xs flex items-start gap-2.5">
                        <svg class="w-4 h-4 flex-shrink-0 mt-0.5 text-rose-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <span class="leading-relaxed">{{ $errors->first() }}</span>
                    </div>
                @endif

                <!-- Password Reset Form -->
                <form method="POST" action="{{ route('password.store') }}" class="space-y-4" x-data="{ submitting: false, showPassword: false, showConfirm: false }" @submit="submitting = true">
                    @csrf
                    <input type="hidden" name="email" value="{{ $email }}">

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">
                            New Password <span class="text-[#df747a]">*</span>
                        </label>
                        <div class="relative">
                            <input :type="showPassword ? 'text' : 'password'" 
                                   id="password" 
                                   name="password" 
                                   placeholder="At least 8 characters"
                                   required 
                                   autofocus 
                                   autocomplete="new-password"
                                   class="w-full rounded-xl bg-[#0B1120] border border-slate-700/80 px-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-[#df747a] focus:ring-2 focus:ring-[#8F3033]/40 transition pr-10" />
                            <button type="button" @click="showPassword = !showPassword" 
                                    aria-label="Toggle password visibility"
                                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-white transition">
                                <svg x-show="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg x-show="showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                                </svg>
                            </button>
                        </div>
                        <x-input-error :messages="$errors->get('password')" class="mt-1.5 text-xs" />
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label for="password_confirmation" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">
                            Confirm New Password <span class="text-[#df747a]">*</span>
                        </label>
                        <div class="relative">
                            <input :type="showConfirm ? 'text' : 'password'" 
                                   id="password_confirmation" 
                                   name="password_confirmation" 
                                   placeholder="Re-enter your new password"
                                   required 
                                   autocomplete="new-password"
                                   class="w-full rounded-xl bg-[#0B1120] border border-slate-700/80 px-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-[#df747a] focus:ring-2 focus:ring-[#8F3033]/40 transition pr-10" />
                            <button type="button" @click="showConfirm = !showConfirm" 
                                    aria-label="Toggle password confirmation visibility"
                                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-white transition">
                                <svg x-show="!showConfirm" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg x-show="showConfirm" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                                </svg>
                            </button>
                        </div>
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1.5 text-xs" />
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button type="submit"
                                :disabled="submitting"
                                class="w-full relative group overflow-hidden rounded-xl bg-gradient-to-r from-[#8F3033] to-[#6B2426] hover:from-[#a0363a] hover:to-[#7c2a2c] text-white font-semibold py-3 px-4 text-xs sm:text-sm tracking-wide shadow-lg shadow-[#8F3033]/25 hover:shadow-xl hover:shadow-[#8F3033]/40 transition-all duration-200 disabled:opacity-40 disabled:cursor-not-allowed disabled:shadow-none flex items-center justify-center gap-2">
                            <span x-show="!submitting" class="flex items-center gap-2">
                                <span>Update Password</span>
                                <svg class="w-4 h-4 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                </svg>
                            </span>
                            <span x-show="submitting" class="flex items-center gap-2" style="display: none;">
                                <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                <span>Saving New Password...</span>
                            </span>
                        </button>
                    </div>
                </form>

                <!-- Card Footer Navigation -->
                <div class="mt-6 pt-5 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-400">
                    <a href="{{ route('password.request') }}" class="hover:text-white transition">
                        ← Request New Code
                    </a>
                    <a href="{{ route('login') }}" class="hover:text-white transition">
                        Return to Login
                    </a>
                </div>
            </div>

            <!-- Single Line Footer Notice -->
            <p class="mt-6 text-center text-[11px] text-slate-400 tracking-wide">
                &copy; {{ date('Y') }} COMSOC Hub. Attendance &amp; Progression Platform.
            </p>
        </div>
    </main>

</body>
</html>
