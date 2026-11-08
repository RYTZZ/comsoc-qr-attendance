@php
    $authUser = $user ?? auth()->user();
    $initialCooldown = (int) (session('cooldown_seconds', $cooldownSeconds ?? 0));
    $expMinutes = (int) ($expirationMinutes ?? 15);
    $maxTries = (int) ($maxAttempts ?? 5);
@endphp

<x-guest-layout>
    <div x-data="{
            digits: ['', '', '', '', '', ''],
            cooldown: {{ $initialCooldown }},
            timer: null,
            get combinedCode() {
                return this.digits.join('');
            },
            init() {
                if (this.cooldown > 0) {
                    this.startCooldown();
                }
                this.$nextTick(() => {
                    if (this.$refs.digit0) {
                        this.$refs.digit0.focus();
                    }
                });
            },
            startCooldown() {
                if (this.timer) clearInterval(this.timer);
                this.timer = setInterval(() => {
                    if (this.cooldown > 0) {
                        this.cooldown--;
                    } else {
                        clearInterval(this.timer);
                    }
                }, 1000);
            },
            handleInput(index, event) {
                const raw = (event.target.value || '').replace(/\D/g, '');
                if (raw.length > 1) {
                    this.fillFromText(raw, index);
                    return;
                }
                this.digits[index] = raw;
                event.target.value = raw;
                if (raw !== '' && index < 5) {
                    const nextRef = this.$refs['digit' + (index + 1)];
                    if (nextRef) nextRef.focus();
                }
            },
            handleKeydown(index, event) {
                if (event.key === 'Backspace' && this.digits[index] === '' && index > 0) {
                    const prevRef = this.$refs['digit' + (index - 1)];
                    if (prevRef) {
                        this.digits[index - 1] = '';
                        prevRef.focus();
                    }
                } else if (event.key === 'ArrowLeft' && index > 0) {
                    this.$refs['digit' + (index - 1)]?.focus();
                } else if (event.key === 'ArrowRight' && index < 5) {
                    this.$refs['digit' + (index + 1)]?.focus();
                }
            },
            handlePaste(event) {
                const pasted = (event.clipboardData || window.clipboardData).getData('text') || '';
                const numeric = pasted.replace(/\D/g, '').slice(0, 6);
                if (numeric.length > 0) {
                    event.preventDefault();
                    this.fillFromText(numeric, 0);
                }
            },
            fillFromText(numericText, startIndex = 0) {
                const chars = numericText.replace(/\D/g, '').split('');
                for (let i = 0; i < chars.length && (startIndex + i) < 6; i++) {
                    this.digits[startIndex + i] = chars[i];
                }
                const focusIdx = Math.min(5, startIndex + chars.length);
                this.$nextTick(() => {
                    this.$refs['digit' + focusIdx]?.focus();
                });
            }
        }"
        class="space-y-6">

        <!-- Header -->
        <div class="text-center space-y-2">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-[#560E0F]/40 border border-[#8F3033] text-[#df747a] shadow-lg mb-1">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
            </div>
            <h1 class="text-xl sm:text-2xl font-extrabold text-white font-brand-display tracking-tight">
                Verify Your Email Address
            </h1>
            <p class="text-xs sm:text-sm text-[#94A3B8] leading-relaxed">
                We sent a six-digit verification code to
                <span class="font-semibold text-white break-all">{{ $authUser?->email }}</span>.
                Enter the code below to verify your COMSOC Hub account.
            </p>
        </div>

        <!-- Expiration & Attempt Notice -->
        <div class="rounded-xl bg-[#0B1120]/80 border border-[#334155] px-4 py-3 flex items-center justify-between gap-3 text-xs text-[#94A3B8]">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-[#df747a] shrink-0"></span>
                <span>Code expires in <strong class="text-white">{{ $expMinutes }} minutes</strong></span>
            </div>
            <span class="text-[11px] text-slate-400">Max {{ $maxTries }} attempts</span>
        </div>

        <!-- Status & Alert Banners -->
        @if (session('status') == 'verification-link-sent')
            <div role="status" class="rounded-xl bg-emerald-950/50 border border-emerald-500/40 p-3.5 text-xs font-medium text-emerald-300 flex items-start gap-2.5">
                <svg class="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ session('success') ?? __('A new six-digit verification code has been sent to the email address you provided.') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div role="alert" class="rounded-xl bg-rose-950/50 border border-rose-500/40 p-3.5 text-xs font-medium text-rose-200 space-y-1">
                @foreach ($errors->all() as $error)
                    <div class="flex items-start gap-2">
                        <svg class="w-4 h-4 text-rose-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                        </svg>
                        <span>{{ $error }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- OTP Verification Form -->
        <form method="POST" action="{{ route('verification.otp.verify') }}" class="space-y-5">
            @csrf

            <!-- Hidden unified 6-digit code input (also supports direct submission in non-JS/test environments) -->
            <input type="hidden" name="code" :value="combinedCode">

            <div>
                <label for="otp-digit-0" class="block text-xs font-semibold uppercase tracking-wider text-[#94A3B8] text-center mb-3">
                    Six-Digit Verification Code
                </label>

                <div class="grid grid-cols-6 gap-2 sm:gap-2.5 max-w-xs mx-auto" @paste="handlePaste($event)">
                    @for ($i = 0; $i < 6; $i++)
                        <input
                            type="text"
                            id="otp-digit-{{ $i }}"
                            x-ref="digit{{ $i }}"
                            name="digits[{{ $i }}]"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            maxlength="1"
                            autocomplete="{{ $i === 0 ? 'one-time-code' : 'off' }}"
                            aria-label="Verification code digit {{ $i + 1 }} of 6"
                            x-model="digits[{{ $i }}]"
                            @input="handleInput({{ $i }}, $event)"
                            @keydown="handleKeydown({{ $i }}, $event)"
                            class="w-full h-12 sm:h-14 text-center text-lg sm:text-xl font-extrabold font-mono text-white bg-[#0B1120] border border-[#334155] rounded-xl focus:border-[#df747a] focus:ring-2 focus:ring-[#8F3033]/50 focus:outline-none transition"
                        />
                    @endfor
                </div>
            </div>

            <button type="submit"
                    class="w-full btn-primary py-3 text-sm font-bold flex items-center justify-center gap-2 rounded-xl shadow-lg">
                <span>Verify Email Address</span>
            </button>
        </form>

        <!-- Resend & Logout Actions -->
        <div class="pt-4 border-t border-[#334155]/80 flex flex-col sm:flex-row items-center justify-between gap-3">
            <form method="POST" action="{{ route('verification.send') }}" class="w-full sm:w-auto">
                @csrf
                <button type="submit"
                        :disabled="cooldown > 0"
                        :class="cooldown > 0 ? 'opacity-60 cursor-not-allowed' : 'hover:border-[#8F3033] hover:text-white'"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold bg-[#0B1120] text-[#CBD5E1] border border-[#334155] transition">
                    <span x-show="cooldown <= 0">{{ __('Resend Verification Email') }}</span>
                    <span x-show="cooldown > 0" x-cloak>
                        Resend Code in <span x-text="cooldown"></span>s
                    </span>
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="w-full sm:w-auto text-center">
                @csrf
                <button type="submit" class="underline text-xs text-[#94A3B8] hover:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-[#8F3033]">
                    {{ __('Log Out') }}
                </button>
            </form>
        </div>
    </div>
</x-guest-layout>
