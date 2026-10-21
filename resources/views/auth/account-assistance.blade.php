<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Need help accessing your COMSOC HUB account? Contact the Computing Society through our official communication channels.">
    <title>Account Assistance — COMSOC HUB</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@600;700;800;900&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#0B1120] text-[#F8FAFC] font-sans antialiased selection:bg-[#560E0F] selection:text-white flex flex-col relative overflow-x-hidden">

    <!-- Ambient Background Decoration -->
    <x-public-auth-bg />

    <!-- Top Navigation Header -->
    <header class="sticky top-0 z-30 bg-[#172033]/90 backdrop-blur-md border-b border-[#334155] px-4 sm:px-8 py-3.5 flex items-center justify-between shadow-lg">
        <a href="{{ route('login') }}" class="flex items-center gap-3 group">
            <div class="w-10 h-10 rounded-xl bg-[#0B1120] border border-[#334155] p-1.5 flex items-center justify-center shrink-0 shadow-md group-hover:border-[#8F3033] transition">
                <img src="{{ asset('images/COMSOC.png') }}" alt="Computing Society Logo" class="w-full h-full object-contain">
            </div>
            <div>
                <span class="font-extrabold text-[#F8FAFC] text-sm font-brand-display tracking-tight block">COMSOC HUB</span>
                <span class="text-[10px] text-[#df747a] font-semibold tracking-wider uppercase block">Computing Society Organization</span>
            </div>
        </a>

        <div class="flex items-center gap-2">
            <a href="{{ route('login') }}" class="btn-secondary text-xs py-2 px-3.5 flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                </svg>
                <span>Back to Sign In</span>
            </a>
        </div>
    </header>

    <!-- Main Container -->
    <main class="flex-1 flex flex-col justify-center items-center px-4 sm:px-6 lg:px-8 py-10 sm:py-16 relative z-10">
        <div class="w-full max-w-2xl mx-auto">

            <div class="card shadow-2xl border border-[#334155] bg-[#172033]/95 backdrop-blur-md p-6 sm:p-10 rounded-2xl">
                
                <!-- Badge & Header -->
                <div class="text-center mb-8">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#560E0F]/30 text-[#df747a] border border-[#8F3033]/40 text-xs font-semibold mb-3">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <span>Official Account Support</span>
                    </div>

                    <h1 class="font-brand-display text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                        Need Help Accessing Your Account?
                    </h1>

                    <p class="text-xs sm:text-sm text-[#94A3B8] mt-3 leading-relaxed max-w-xl mx-auto">
                        If you're unable to access your COMSOC HUB account, please contact the Computing Society through one of our official communication channels. Our team will help you verify your account and determine the appropriate next steps.
                    </p>
                </div>

                <!-- Support Channel Options -->
                <div class="space-y-4">
                    
                    <!-- Option A: Official COMSOC Facebook Page -->
                    <div class="p-5 rounded-xl border border-[#334155] bg-[#0B1120]/70 hover:border-[#8F3033] transition-all group flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div class="flex items-start gap-3.5">
                            <div class="w-10 h-10 rounded-xl bg-blue-600/20 text-blue-400 border border-blue-500/30 flex items-center justify-center shrink-0 mt-0.5 sm:mt-0">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-bold text-white group-hover:text-[#df747a] transition-colors">
                                    Official COMSOC Facebook Page
                                </h2>
                                <p class="text-xs text-[#94A3B8] mt-1 leading-relaxed">
                                    Message the official Computing Society page for account assistance and general inquiries.
                                </p>
                            </div>
                        </div>

                        <a href="https://www.facebook.com/comsocsorsubc"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="btn-primary text-xs py-2.5 px-4 w-full sm:w-auto shrink-0 flex items-center justify-center gap-1.5 shadow-md">
                            <span>Contact COMSOC</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                        </a>
                    </div>

                    <!-- Option B: Computing Society President / Developer -->
                    <div class="p-5 rounded-xl border border-[#334155] bg-[#0B1120]/70 hover:border-[#8F3033] transition-all group flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div class="flex items-start gap-3.5">
                            <div class="w-10 h-10 rounded-xl bg-purple-600/20 text-purple-400 border border-purple-500/30 flex items-center justify-center shrink-0 mt-0.5 sm:mt-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-bold text-white group-hover:text-[#df747a] transition-colors">
                                    Computing Society President / Developer
                                </h2>
                                <p class="text-xs text-[#94A3B8] mt-1 leading-relaxed">
                                    Reach out directly to the Computing Society President and COMSOC HUB developer for technical issues or account-access concerns.
                                </p>
                            </div>
                        </div>

                        <a href="https://www.facebook.com/kxze1029"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="btn-secondary text-xs py-2.5 px-4 w-full sm:w-auto shrink-0 flex items-center justify-center gap-1.5 shadow-md">
                            <span>Contact President</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                        </a>
                    </div>

                    <!-- Option C: Official COMSOC Email -->
                    <div class="p-5 rounded-xl border border-[#334155] bg-[#0B1120]/70 hover:border-[#8F3033] transition-all group flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div class="flex items-start gap-3.5">
                            <div class="w-10 h-10 rounded-xl bg-rose-600/20 text-rose-400 border border-rose-500/30 flex items-center justify-center shrink-0 mt-0.5 sm:mt-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-bold text-white group-hover:text-[#df747a] transition-colors">
                                    Official COMSOC Email
                                </h2>
                                <p class="text-xs text-[#94A3B8] mt-1 leading-relaxed">
                                    Send your account concern to the official Computing Society email address:
                                    <span class="text-slate-300 font-mono font-semibold block sm:inline">computingsocietysorsubc@gmail.com</span>
                                </p>
                            </div>
                        </div>

                        <a href="mailto:computingsocietysorsubc@gmail.com?subject=COMSOC%20HUB%20Account%20Assistance%20Request"
                           class="btn-secondary text-xs py-2.5 px-4 w-full sm:w-auto shrink-0 flex items-center justify-center gap-1.5 shadow-md">
                            <span>Send Us an Email</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </a>
                    </div>

                </div>

                <!-- Security & Verification Notice -->
                <div class="mt-8 pt-6 border-t border-[#334155] text-xs text-[#94A3B8] space-y-2">
                    <div class="flex items-start gap-2 text-amber-400/90 font-medium">
                        <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <span>Security Reminder:</span>
                    </div>
                    <p class="leading-relaxed pl-6">
                        COMSOC officers will never ask for your password. When contacting support, please prepare your official student ID number and full name for identity verification.
                    </p>
                </div>

                <!-- Footer Links -->
                <div class="mt-6 pt-5 border-t border-[#334155]/60 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs">
                    <a href="{{ route('login') }}" class="text-[#df747a] hover:text-white font-semibold underline transition">
                        &larr; Back to Sign In
                    </a>
                    <a href="{{ route('privacy') }}" class="text-slate-500 hover:text-slate-300 underline transition">
                        Privacy Notice
                    </a>
                </div>

            </div>

        </div>
    </main>

    <!-- Public Branding Footer -->
    <x-public-branding-footer />

</body>
</html>
