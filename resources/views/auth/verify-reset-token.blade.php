<x-guest-layout>
    <div class="mb-4">
        <h2 class="font-brand-display text-xl sm:text-2xl font-bold text-white tracking-tight">Enter Reset Code</h2>
        <p class="text-xs sm:text-sm text-[#94A3B8] mt-1.5 leading-relaxed">
            Enter the six-character verification code sent to your email (<strong class="text-white">{{ $email }}</strong>).
        </p>
    </div>

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

    <div x-data="{
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
                const focusIdx = Math.min(5, startIndex + chars.length);
                this.$nextTick(() => {
                    this.$refs['box' + focusIdx]?.focus();
                });
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
         }"
         class="space-y-5">

        <!-- Form: Six-Box Input with Auto-Advance, Paste, and Auto-Uppercase -->
        <form method="POST" action="{{ route('password.verify.submit') }}" @submit="handleSubmit($event)" class="space-y-5">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">
            <input type="hidden" name="code" :value="combinedCode">

            <!-- 6 Individual Input Boxes -->
            <div>
                <label for="reset-digit-0" class="block text-xs font-semibold uppercase tracking-wider text-[#94A3B8] text-center mb-3">
                    Verification Code <span class="text-rose-400">*</span>
                </label>
                
                <div class="grid grid-cols-6 gap-2 sm:gap-2.5 max-w-xs sm:max-w-sm mx-auto" @paste="handlePaste($event)">
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
                            class="w-full h-12 sm:h-14 text-center font-mono text-xl sm:text-2xl font-extrabold uppercase rounded-xl bg-[#0B1120] border border-[#334155] text-white focus:border-[#df747a] focus:ring-2 focus:ring-[#8F3033]/50 focus:outline-none transition-all select-all shadow-inner"
                            required
                        />
                    @endfor
                </div>

                <p class="text-center text-[11px] text-[#94A3B8] mt-2.5">
                    Letters and numbers only (excluding ambiguous 0/O and 1/I).
                </p>
                <x-input-error :messages="$errors->get('code')" class="mt-2 text-center text-xs" />
            </div>

            <!-- Expiration Countdown & Cooldown Row -->
            <div class="bg-[#0B1120]/70 border border-[#334155]/70 rounded-xl p-3 text-xs flex flex-col sm:flex-row items-center justify-between gap-2">
                <!-- Expiration Countdown (15 minutes) -->
                <div class="flex items-center gap-1.5 text-slate-300">
                    <svg class="w-4 h-4 text-[#df747a]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Code expires in:</span>
                    <span class="font-mono font-bold text-white" x-text="formatExpiration()">15:00</span>
                </div>

                <!-- Resend Code Button with 60-Second Cooldown -->
                <div>
                    <button type="button"
                            @click="resendCode()"
                            :disabled="cooldown > 0 || resending"
                            class="text-xs font-semibold text-[#df747a] hover:text-white underline disabled:opacity-50 disabled:no-underline disabled:cursor-not-allowed transition flex items-center gap-1">
                        <span x-show="cooldown > 0" x-text="'Resend in ' + cooldown + 's'"></span>
                        <span x-show="cooldown === 0 && !resending">Resend Code</span>
                        <span x-show="resending" style="display: none;">Sending...</span>
                    </button>
                </div>
            </div>

            <!-- Submit Button (Enabled ONLY when full 6 valid characters entered) -->
            <div class="pt-1">
                <button type="submit" 
                        class="btn-primary w-full justify-center py-3 shadow-xl disabled:opacity-50 disabled:cursor-not-allowed text-xs sm:text-sm font-semibold tracking-wide flex items-center gap-2" 
                        :disabled="submitting || combinedCode.length !== 6">
                    <span x-show="!submitting" class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Verify Code</span>
                    </span>
                    <span x-show="submitting" class="flex items-center gap-2" style="display: none;">
                        <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span>Verifying Code...</span>
                    </span>
                </button>
            </div>
        </form>

        <div class="pt-2 border-t border-[#334155] flex flex-col sm:flex-row items-center justify-between gap-2 text-xs">
            <p class="text-[#94A3B8]">
                Wrong email address?
                <a href="{{ route('password.request') }}" class="text-[#df747a] hover:text-white font-semibold underline transition">
                    Change Email
                </a>
            </p>

            <a href="{{ route('login') }}" class="text-[11px] text-slate-400 hover:text-white underline transition">
                Return to Login
            </a>
        </div>
    </div>
</x-guest-layout>
