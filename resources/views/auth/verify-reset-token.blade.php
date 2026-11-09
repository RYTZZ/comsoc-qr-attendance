<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Enter Reset Code - {{ config('app.name', 'COMSOC Hub') }}</title>

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
        <a href="{{ route('login') }}" class="group flex items-center gap-2.5 transition-transform duration-200 hover:scale-[1.02]">
            <img src="{{ asset('images/comsoc-logo.png') }}" alt="COMSOC Hub Logo" class="w-8 h-8 rounded-lg object-contain shadow-md shadow-black/40">
            <span class="font-bold tracking-tight text-white text-base group-hover:text-slate-200 transition-colors">
                COMSOC <span class="text-[#df747a]">HUB</span>
            </span>
        </a>

        <div class="flex items-center gap-4 text-xs font-medium">
            <span class="text-slate-400 hidden sm:inline">Wrong email?</span>
            <a href="{{ route('password.request') }}" class="text-slate-200 hover:text-white px-3 py-1.5 rounded-lg border border-slate-700/60 bg-slate-800/40 hover:bg-slate-800 transition">
                Change Email
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
                    <div class="relative w-14 h-14 rounded-2xl bg-[#172033] border border-slate-700/70 p-2.5 flex items-center justify-center shadow-2xl">
                        <img src="{{ asset('images/comsoc-logo.png') }}" alt="COMSOC Logo" class="w-full h-full object-contain">
                    </div>
                </div>
            </div>

            <!-- Card Container -->
            <div class="rounded-2xl border border-slate-800/80 bg-[#172033]/85 backdrop-blur-2xl shadow-2xl shadow-black/50 p-6 sm:p-8"
                 x-data="{
                    email: '{{ addslashes($email ?? '') }}',
                    digits: ['', '', '', '', '', ''],
                    submitting: false,
                    resending: false,
                    cooldown: {{ (int) (session('cooldown_seconds') ?? $cooldown_seconds ?? 0) }},
                    expiresInSeconds: 900,
                    allowedChars: '23456789ABCDEFGHJKLMNPQRSTUVWXYZ',

                    init() {
                        this.$nextTick(() => {
                            this.$refs.box0?.focus();
                        });

                        if (this.cooldown > 0) {
                            const cdInterval = setInterval(() => {
                                if (this.cooldown > 0) {
                                    this.cooldown--;
                                } else {
                                    clearInterval(cdInterval);
                                }
                            }, 1000);
                        }

                        const expInterval = setInterval(() => {
                            if (this.expiresInSeconds > 0) {
                                this.expiresInSeconds--;
                            } else {
                                clearInterval(expInterval);
                            }
                        }, 1000);
                    },

                    get combinedCode() {
                        return this.digits.join('').toUpperCase();
                    },

                    formatExpiration() {
                        const m = Math.floor(this.expiresInSeconds / 60);
                        const s = this.expiresInSeconds % 60;
                        return `${m}:${s < 10 ? '0' : ''}${s}`;
                    },

                    handleInput(index, event) {
                        const raw = (event.target.value || '').toUpperCase();
                        const filtered = raw.split('').filter(c => this.allowedChars.includes(c));

                        if (filtered.length > 1) {
                            this.fillFromText(filtered.join(''), index);
                            return;
                        }

                        const cleanChar = filtered[0] || '';
                        this.digits[index] = cleanChar;
                        event.target.value = cleanChar;

                        if (cleanChar !== '' && index < 5) {
                            this.$refs['box' + (index + 1)]?.focus();
                        }

                        this.checkAutoSubmit();
                    },

                    handleKeydown(index, event) {
                        if (event.key === 'Backspace') {
                            if (this.digits[index] === '' && index > 0) {
                                this.digits[index - 1] = '';
                                this.$refs['box' + (index - 1)]?.focus();
                            } else {
                                this.digits[index] = '';
                            }
                        } else if (event.key === 'ArrowLeft' && index > 0) {
                            event.preventDefault();
                            this.$refs['box' + (index - 1)]?.focus();
                        } else if (event.key === 'ArrowRight' && index < 5) {
                            event.preventDefault();
                            this.$refs['box' + (index + 1)]?.focus();
                        }
                    },

                    handlePaste(event) {
                        event.preventDefault();
                        const pasted = (event.clipboardData || window.clipboardData).getData('text') || '';
                        const filtered = pasted.toUpperCase().split('').filter(c => this.allowedChars.includes(c)).join('').slice(0, 6);
                        if (filtered.length > 0) {
                            this.fillFromText(filtered, 0);
                        }
                    },

                    fillFromText(text, startIndex = 0) {
                        const chars = text.split('');
                        for (let i = 0; i < chars.length && (startIndex + i) < 6; i++) {
                            this.digits[startIndex + i] = chars[i];
                        }
                        this.$nextTick(() => {
                            this.$refs['box' + Math.min(5, startIndex + chars.length)]?.focus();
                            this.checkAutoSubmit();
                        });
                    },

                    checkAutoSubmit() {
                        if (this.combinedCode.length === 6 && !this.submitting) {
                            setTimeout(() => {
                                if (this.combinedCode.length === 6 && !this.submitting) {
                                    this.$refs.verifyForm?.requestSubmit();
                                }
                            }, 180);
                        }
                    },

                    handleSubmit(event) {
                        if (this.combinedCode.length !== 6 || this.submitting) {
                            event.preventDefault();
                            return;
                        }
                        this.submitting = true;
                    },

                    async resendCode() {
                        if (this.cooldown > 0 || this.resending) return;
                        this.resending = true;

                        try {
                            const response = await fetch('{{ route('password.email') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                },
                                body: JSON.stringify({ email: this.email }),
                            });

                            const data = await response.json();
                            if (response.ok) {
                                this.cooldown = data.cooldown_seconds || 60;
                                this.expiresInSeconds = 900;
                                this.digits = ['', '', '', '', '', ''];
                                this.$refs.box0?.focus();

                                const cdInterval = setInterval(() => {
                                    if (this.cooldown > 0) {
                                        this.cooldown--;
                                    } else {
                                        clearInterval(cdInterval);
                                    }
                                }, 1000);
                            } else {
                                alert(data.message || 'Unable to resend reset code.');
                                if (data.cooldown_seconds) {
                                    this.cooldown = data.cooldown_seconds;
                                }
                            }
                        } catch (e) {
                            alert('Network error while requesting a new reset code. Please try again.');
                        } finally {
                            this.resending = false;
                        }
                    }
                 }">

                <!-- Header Title & Subtitle -->
                <div class="text-center mb-6">
                    <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-white">Enter Reset Code</h1>
                    <p class="text-xs sm:text-sm text-slate-400 mt-2 leading-relaxed">
                        We sent a 6-character code to
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

                <!-- Verification Form -->
                <form x-ref="verifyForm" method="POST" action="{{ route('password.verify.submit') }}" @submit="handleSubmit($event)" class="space-y-6">
                    @csrf
                    <input type="hidden" name="email" value="{{ $email }}">
                    <input type="hidden" name="code" :value="combinedCode">

                    <!-- 6 Digit Input Cells -->
                    <div>
                        <div class="flex items-center justify-between mb-3 px-1">
                            <label for="reset-digit-0" class="text-[11px] font-semibold tracking-wider uppercase text-slate-400">
                                Verification Code <span class="text-[#df747a]">*</span>
                            </label>
                            <span class="text-[10px] text-slate-500">6 Characters</span>
                        </div>

                        <div class="grid grid-cols-6 gap-2 sm:gap-2.5" @paste="handlePaste($event)">
                            @for ($i = 0; $i < 6; $i++)
                                <input
                                    type="text"
                                    id="reset-digit-{{ $i }}"
                                    x-ref="box{{ $i }}"
                                    name="digits[{{ $i }}]"
                                    inputmode="text"
                                    maxlength="1"
                                    x-model="digits[{{ $i }}]"
                                    @input="handleInput({{ $i }}, $event)"
                                    @keydown="handleKeydown({{ $i }}, $event)"
                                    autocomplete="{{ $i === 0 ? 'one-time-code' : 'off' }}"
                                    autocapitalize="characters"
                                    spellcheck="false"
                                    aria-label="Verification code character {{ $i + 1 }} of 6"
                                    class="w-full h-13 sm:h-14 text-center font-mono text-xl sm:text-2xl font-bold uppercase rounded-xl bg-[#0B1120] border text-white transition-all select-all shadow-inner focus:outline-none"
                                    :class="combinedCode.length === 6 ? 'border-emerald-500/70 ring-2 ring-emerald-500/20' : 'border-slate-700/80 focus:border-[#df747a] focus:ring-2 focus:ring-[#8F3033]/40'"
                                    required
                                />
                            @endfor
                        </div>

                        <p class="text-center text-[11px] text-slate-400 mt-2.5">
                            Letters & numbers only (excluding ambiguous 0/O and 1/I).
                        </p>
                        <x-input-error :messages="$errors->get('code')" class="mt-2 text-center text-xs" />
                    </div>

                    <!-- Expiration & Resend Inline Row -->
                    <div class="flex items-center justify-between text-xs px-1 py-2 rounded-xl bg-slate-900/60 border border-slate-800/80">
                        <div class="flex items-center gap-1.5 text-slate-400 pl-2.5">
                            <svg class="w-3.5 h-3.5 text-[#df747a]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Expires in:</span>
                            <span class="font-mono font-bold text-white text-[11px]" x-text="formatExpiration()">15:00</span>
                        </div>

                        <div class="pr-2.5">
                            <button type="button"
                                    @click="resendCode()"
                                    :disabled="cooldown > 0 || resending"
                                    class="text-xs font-semibold text-[#df747a] hover:text-white transition disabled:opacity-40 disabled:cursor-not-allowed">
                                <span x-show="cooldown > 0" x-text="'Resend in ' + cooldown + 's'"></span>
                                <span x-show="cooldown === 0 && !resending">Resend code</span>
                                <span x-show="resending" style="display: none;">Sending...</span>
                            </button>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit"
                            :disabled="submitting || combinedCode.length !== 6"
                            class="w-full relative group overflow-hidden rounded-xl bg-gradient-to-r from-[#8F3033] to-[#6B2426] hover:from-[#a0363a] hover:to-[#7c2a2c] text-white font-semibold py-3 px-4 text-xs sm:text-sm tracking-wide shadow-lg shadow-[#8F3033]/25 hover:shadow-xl hover:shadow-[#8F3033]/40 transition-all duration-200 disabled:opacity-40 disabled:cursor-not-allowed disabled:shadow-none flex items-center justify-center gap-2">
                        <span x-show="!submitting" class="flex items-center gap-2">
                            <span>Verify Code</span>
                            <svg class="w-4 h-4 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                        </span>
                        <span x-show="submitting" class="flex items-center gap-2" style="display: none;">
                            <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span>Verifying Code...</span>
                        </span>
                    </button>
                </form>

                <!-- Card Footer Navigation -->
                <div class="mt-6 pt-5 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-400">
                    <a href="{{ route('password.request') }}" class="hover:text-white transition">
                        ← Change Email
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
