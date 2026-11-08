@php
    $authUser = $user ?? auth()->user();
    $expMinutes = (int) ($expirationMinutes ?? 15);
    $maxTries = (int) ($maxAttempts ?? 5);

    $nowTs = time();
    $serverTimeTs = (int) ($serverTime ?? $nowTs);
    $cooldownExpiresAtTs = session('cooldown_expires_at', $cooldownExpiresAt ?? null);

    if ($cooldownExpiresAtTs) {
        $initialCooldownSec = max(0, (int) ($cooldownExpiresAtTs - $serverTimeTs));
    } else {
        $initialCooldownSec = (int) (session('cooldown_seconds', $cooldownSeconds ?? 0));
        if ($initialCooldownSec > 0) {
            $cooldownExpiresAtTs = $nowTs + $initialCooldownSec;
        }
    }
@endphp

<x-guest-layout>
    <div x-data="{
            digits: ['', '', '', '', '', ''],
            cooldownExpiresAt: {{ $cooldownExpiresAtTs ? $cooldownExpiresAtTs : 'null' }},
            remainingSeconds: {{ $initialCooldownSec }},
            timer: null,
            isResending: false,
            resendSuccessMessage: '',
            resendErrorMessage: '',

            get formattedCountdown() {
                const total = Math.max(0, this.remainingSeconds);
                const mm = String(Math.floor(total / 60)).padStart(2, '0');
                const ss = String(total % 60).padStart(2, '0');
                return `${mm}:${ss}`;
            },

            get combinedCode() {
                return this.digits.join('');
            },

            init() {
                this.syncFromStorage();
                this.updateRemaining();

                if (this.remainingSeconds > 0) {
                    this.startTimer();
                }

                // Listen for browser tab visibility changes so countdown is recalculating immediately upon reactivation
                document.addEventListener('visibilitychange', () => {
                    if (!document.hidden) {
                        this.updateRemaining();
                    }
                });

                this.$nextTick(() => {
                    if (this.$refs.digit0) {
                        this.$refs.digit0.focus();
                    }
                });
            },

            syncFromStorage() {
                try {
                    const stored = localStorage.getItem('comsoc_otp_cooldown_expires_at');
                    if (stored) {
                        const parsed = parseInt(stored, 10);
                        const nowEpoch = Math.floor(Date.now() / 1000);
                        if (!isNaN(parsed) && parsed > nowEpoch) {
                            if (!this.cooldownExpiresAt || parsed > this.cooldownExpiresAt) {
                                this.cooldownExpiresAt = parsed;
                            }
                        }
                    }
                    if (this.cooldownExpiresAt) {
                        localStorage.setItem('comsoc_otp_cooldown_expires_at', this.cooldownExpiresAt.toString());
                    }
                } catch (e) {
                    // Ignore localStorage restrictions in private/sandboxed browsers
                }
            },

            updateRemaining() {
                if (!this.cooldownExpiresAt) {
                    this.remainingSeconds = 0;
                    return;
                }
                const nowEpoch = Math.floor(Date.now() / 1000);
                const diff = this.cooldownExpiresAt - nowEpoch;
                this.remainingSeconds = Math.max(0, diff);

                if (this.remainingSeconds <= 0) {
                    try {
                        localStorage.removeItem('comsoc_otp_cooldown_expires_at');
                    } catch (e) {}
                    if (this.timer) {
                        clearInterval(this.timer);
                        this.timer = null;
                    }
                }
            },

            startTimer() {
                if (this.timer) clearInterval(this.timer);
                this.timer = setInterval(() => {
                    this.updateRemaining();
                }, 1000);
            },

            async triggerResend() {
                if (this.remainingSeconds > 0 || this.isResending) {
                    return;
                }

                this.isResending = true;
                this.resendSuccessMessage = '';
                this.resendErrorMessage = '';

                try {
                    const response = await fetch('{{ route('verification.send') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || ''
                        },
                        body: JSON.stringify({})
                    });

                    const data = await response.json();

                    if (response.ok && data.success) {
                        const cooldown = data.cooldown_seconds || 60;
                        const nowEpoch = Math.floor(Date.now() / 1000);
                        this.cooldownExpiresAt = data.cooldown_expires_at || (nowEpoch + cooldown);
                        try {
                            localStorage.setItem('comsoc_otp_cooldown_expires_at', this.cooldownExpiresAt.toString());
                        } catch (e) {}

                        this.updateRemaining();
                        this.startTimer();
                        this.resendSuccessMessage = data.message || 'A new six-digit verification code has been sent.';
                    } else {
                        if (data.cooldown_seconds && data.cooldown_seconds > 0) {
                            const nowEpoch = Math.floor(Date.now() / 1000);
                            this.cooldownExpiresAt = data.cooldown_expires_at || (nowEpoch + data.cooldown_seconds);
                            try {
                                localStorage.setItem('comsoc_otp_cooldown_expires_at', this.cooldownExpiresAt.toString());
                            } catch (e) {}
                            this.updateRemaining();
                            this.startTimer();
                        }
                        this.resendErrorMessage = data.message || 'Unable to resend code. Please try again.';
                    }
                } catch (err) {
                    // Fallback to standard form submission if AJAX fetch fails
                    document.getElementById('fallback-resend-form')?.submit();
                } finally {
                    this.isResending = false;
                }
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

        <!-- Dynamic AJAX Alert Messages -->
        <div x-show="resendSuccessMessage" x-cloak role="status" class="rounded-xl bg-emerald-950/50 border border-emerald-500/40 p-3.5 text-xs font-medium text-emerald-300 flex items-start gap-2.5">
            <svg class="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span x-text="resendSuccessMessage"></span>
        </div>

        <div x-show="resendErrorMessage" x-cloak role="alert" class="rounded-xl bg-rose-950/50 border border-rose-500/40 p-3.5 text-xs font-medium text-rose-200 flex items-start gap-2.5">
            <svg class="w-4 h-4 text-rose-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
            </svg>
            <span x-text="resendErrorMessage"></span>
        </div>

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

            <!-- Hidden unified 6-digit code input -->
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

        <!-- Resend Countdown & Action Section -->
        <div class="pt-4 border-t border-[#334155]/80 space-y-3">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                
                <!-- Countdown Status & Resend Button -->
                <div class="w-full sm:w-auto flex flex-col items-center sm:items-start gap-1">
                    <!-- Status message text -->
                    <div class="text-[11px] font-medium" :class="remainingSeconds > 0 ? 'text-[#94A3B8]' : 'text-emerald-400'">
                        <span x-show="remainingSeconds > 0">
                            Resend code available in <strong class="font-mono text-white" x-text="formattedCountdown"></strong>
                        </span>
                        <span x-show="remainingSeconds <= 0" x-cloak>
                            You can now request a new verification code.
                        </span>
                    </div>

                    <!-- Resend Button with Disabled / Enabled states -->
                    <button type="button"
                            @click="triggerResend()"
                            :disabled="remainingSeconds > 0 || isResending"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold border transition"
                            :class="(remainingSeconds > 0 || isResending) 
                                ? 'bg-[#0B1120]/60 text-slate-500 border-[#334155]/50 cursor-not-allowed opacity-60' 
                                : 'bg-[#0B1120] text-[#CBD5E1] border-[#334155] hover:border-[#8F3033] hover:text-white hover:bg-[#1E293B] cursor-pointer'">
                        <svg x-show="isResending" class="animate-spin -ml-1 mr-1.5 h-3.5 w-3.5 text-[#df747a]" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>{{ __('Resend Verification Code') }}</span>
                    </button>
                </div>

                <!-- Log Out Form -->
                <form method="POST" action="{{ route('logout') }}" class="w-full sm:w-auto text-center">
                    @csrf
                    <button type="submit" class="underline text-xs text-[#94A3B8] hover:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-[#8F3033]">
                        {{ __('Log Out') }}
                    </button>
                </form>
            </div>
        </div>

        <!-- Hidden Fallback Form for non-JS / standard form post -->
        <form id="fallback-resend-form" method="POST" action="{{ route('verification.send') }}" style="display: none;">
            @csrf
        </form>
    </div>
</x-guest-layout>
