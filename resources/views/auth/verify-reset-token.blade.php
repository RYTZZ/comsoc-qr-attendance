<x-guest-layout>
    <div class="mb-4">
        <h2 class="font-brand-display text-xl sm:text-2xl font-bold text-white tracking-tight">Verify Reset Token</h2>
        <p class="text-xs sm:text-sm text-[#94A3B8] mt-1.5 leading-relaxed">
            Enter the password reset token sent to your email address to continue resetting your password.
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

    <form method="POST" action="{{ route('password.verify.submit') }}" class="space-y-4" x-data="{ verifying: false }" @submit="verifying = true">
        @csrf

        <!-- Email Address -->
        <div>
            <label for="email" class="label">Email Address <span class="text-rose-400">*</span></label>
            <input id="email" 
                   class="input" 
                   type="email" 
                   name="email" 
                   value="{{ old('email', $email ?? '') }}" 
                   placeholder="Enter your registered email"
                   required 
                   autocomplete="email" />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5 text-xs" />
        </div>

        <!-- Token Input -->
        <div>
            <label for="token" class="label">Reset Token <span class="text-rose-400">*</span></label>
            <input id="token" 
                   class="input font-mono tracking-wider" 
                   type="text" 
                   name="token" 
                   value="{{ old('token', $token ?? '') }}" 
                   placeholder="Paste your reset token here"
                   required 
                   autofocus 
                   autocomplete="off" />
            <p class="text-[11px] text-[#94A3B8] mt-1">
                Check your spam or junk folder if the token does not appear in your inbox within a few minutes.
            </p>
            <x-input-error :messages="$errors->get('token')" class="mt-1.5 text-xs" />
        </div>

        <div class="pt-2">
            <button type="submit" 
                    class="btn-primary w-full justify-center py-3 shadow-xl disabled:opacity-50 disabled:cursor-not-allowed text-xs sm:text-sm font-semibold tracking-wide flex items-center gap-2" 
                    :disabled="verifying">
                <span x-show="!verifying" class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Verify Token</span>
                </span>
                <span x-show="verifying" class="flex items-center gap-2" style="display: none;">
                    <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>Verifying Token...</span>
                </span>
            </button>
        </div>
    </form>

    <div class="mt-6 pt-5 border-t border-[#334155] flex flex-col sm:flex-row items-center justify-between gap-2 text-xs">
        <p class="text-[#94A3B8]">
            Need a new token?
            <a href="{{ route('password.request') }}" class="text-[#df747a] hover:text-white font-semibold underline transition">
                Request Again
            </a>
        </p>

        <a href="{{ route('login') }}" class="text-[11px] text-slate-400 hover:text-white underline transition">
            Return to Login
        </a>
    </div>
</x-guest-layout>
