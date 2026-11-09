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

    <div x-data="resetCodeVerifier({
            email: '{{ addslashes($email ?? '') }}',
            initialCooldown: {{ (int) (session('cooldown_seconds') ?? $cooldown_seconds ?? 0) }},
            resendUrl: '{{ route('password.email') }}',
            csrfToken: '{{ csrf_token() }}'
         })"
         class="space-y-5">

        <!-- Form: Six-Box Input with Auto-Advance, Paste, and Auto-Uppercase -->
        <form method="POST" action="{{ route('password.verify.submit') }}" @submit="handleSubmit($event)" class="space-y-5">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">
            <input type="hidden" name="code" :value="fullCode">

            <!-- 6 Individual Input Boxes -->
            <div>
                <label class="label text-center mb-3">Verification Code <span class="text-rose-400">*</span></label>
                
                <div class="flex items-center justify-between gap-2 sm:gap-2.5 max-w-sm mx-auto" @paste="handlePaste($event)">
                    <template x-for="(digit, idx) in digits" :key="idx">
                        <input type="text"
                               maxlength="1"
                               x-ref="'box' + idx"
                               :value="digits[idx]"
                               @input="handleInput($event, idx)"
                               @keydown="handleKeydown($event, idx)"
                               class="w-11 h-13 sm:w-12 sm:h-14 text-center font-mono text-xl sm:text-2xl font-extrabold uppercase rounded-xl bg-[#0B1120] border border-[#334155] text-white focus:border-[#8F3033] focus:ring-2 focus:ring-[#560E0F] transition-all select-all shadow-inner"
                               autocomplete="off"
                               autocapitalize="characters"
                               spellcheck="false"
                               required />
                    </template>
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

            <!-- Submit Button -->
            <div class="pt-1">
                <button type="submit" 
                        class="btn-primary w-full justify-center py-3 shadow-xl disabled:opacity-50 disabled:cursor-not-allowed text-xs sm:text-sm font-semibold tracking-wide flex items-center gap-2" 
                        :disabled="submitting || fullCode.length !== 6">
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

    <script>
        function resetCodeVerifier(config) {
            return {
                email: config.email,
                digits: ['', '', '', '', '', ''],
                submitting: false,
                resending: false,
                cooldown: config.initialCooldown || 0,
                expiresInSeconds: 900, // 15 minutes
                allowedChars: '23456789ABCDEFGHJKLMNPQRSTUVWXYZ',

                init() {
                    this.$nextTick(() => {
                        this.focusBox(0);
                    });

                    // Cooldown countdown timer
                    if (this.cooldown > 0) {
                        const cdInterval = setInterval(() => {
                            if (this.cooldown > 0) {
                                this.cooldown--;
                            } else {
                                clearInterval(cdInterval);
                            }
                        }, 1000);
                    }

                    // 15-minute expiration timer
                    const expInterval = setInterval(() => {
                        if (this.expiresInSeconds > 0) {
                            this.expiresInSeconds--;
                        } else {
                            clearInterval(expInterval);
                        }
                    }, 1000);
                },

                get fullCode() {
                    return this.digits.join('').toUpperCase();
                },

                formatExpiration() {
                    const m = Math.floor(this.expiresInSeconds / 60);
                    const s = this.expiresInSeconds % 60;
                    return `${m}:${s < 10 ? '0' : ''}${s}`;
                },

                focusBox(index) {
                    const ref = this.$refs['box' + index];
                    if (ref) ref.focus();
                },

                handleInput(event, index) {
                    const rawVal = event.target.value.toUpperCase();
                    // Filter to only allowed alphanumeric characters
                    const cleanChar = rawVal.split('').filter(c => this.allowedChars.includes(c)).pop() || '';
                    
                    this.digits[index] = cleanChar;
                    event.target.value = cleanChar;

                    if (cleanChar && index < 5) {
                        this.focusBox(index + 1);
                    }
                },

                handleKeydown(event, index) {
                    if (event.key === 'Backspace' && !this.digits[index] && index > 0) {
                        this.digits[index - 1] = '';
                        this.focusBox(index - 1);
                    } else if (event.key === 'ArrowLeft' && index > 0) {
                        this.focusBox(index - 1);
                    } else if (event.key === 'ArrowRight' && index < 5) {
                        this.focusBox(index + 1);
                    }
                },

                handlePaste(event) {
                    event.preventDefault();
                    const pasteData = (event.clipboardData || window.clipboardData).getData('text').toUpperCase().trim();
                    const filteredChars = pasteData.split('').filter(c => this.allowedChars.includes(c));

                    for (let i = 0; i < 6; i++) {
                        this.digits[i] = filteredChars[i] || '';
                    }

                    const nextIndex = Math.min(5, filteredChars.length);
                    this.focusBox(nextIndex);
                },

                handleSubmit(event) {
                    if (this.fullCode.length !== 6) {
                        event.preventDefault();
                        return;
                    }
                    this.submitting = true;
                },

                async resendCode() {
                    if (this.cooldown > 0 || this.resending) return;
                    this.resending = true;

                    try {
                        const response = await fetch(config.resendUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': config.csrfToken,
                            },
                            body: JSON.stringify({ email: this.email }),
                        });

                        const data = await response.json();
                        if (response.ok) {
                            this.cooldown = data.cooldown_seconds || 60;
                            this.expiresInSeconds = 900; // Reset 15-minute countdown
                            this.digits = ['', '', '', '', '', ''];
                            this.focusBox(0);

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
            };
        }
    </script>
</x-guest-layout>
