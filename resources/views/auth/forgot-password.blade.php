<x-guest-layout>
    <div class="mb-4">
        <h2 class="font-brand-display text-lg sm:text-xl font-bold text-white tracking-tight">Forgot your password?</h2>
        <p class="text-xs text-[#94A3B8] mt-1 leading-relaxed">
            Enter your registered email address and we'll send you a secure password reset link to create a new one.
        </p>
    </div>

    <!-- Session Status / Feedback -->
    <x-auth-session-status class="mb-3.5" :status="session('status')" />

    @if($errors->has('error'))
    <div class="alert-error mb-3.5 text-xs py-2 px-3">
        <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
        </svg>
        <span class="leading-snug">{{ $errors->first('error') }}</span>
    </div>
    @elseif($errors->any() && !$errors->has('email'))
    <div class="alert-error mb-3.5 text-xs py-2 px-3">
        <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
        </svg>
        <span class="leading-snug">{{ $errors->first() }}</span>
    </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-3.5" x-data="{ submitting: false }" @submit="submitting = true">
        @csrf

        <!-- Email Address -->
        <div>
            <label for="email" class="label text-xs">Email Address</label>
            <input id="email" 
                   class="input" 
                   type="email" 
                   name="email" 
                   value="{{ old('email') }}" 
                   placeholder="e.g. member@sorsu.edu.ph"
                   required 
                   autofocus 
                   autocomplete="email" />
            <x-input-error :messages="$errors->get('email')" class="mt-1 text-xs" />
        </div>

        <div class="pt-1">
            <button type="submit" 
                    class="btn-primary w-full justify-center py-2.5 shadow-xl disabled:opacity-50 disabled:cursor-not-allowed text-xs sm:text-sm" 
                    :disabled="submitting">
                <span x-show="!submitting">Email Password Reset Link</span>
                <span x-show="submitting" class="flex items-center gap-2" style="display: none;">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    Sending Link...
                </span>
            </button>
        </div>
    </form>

    <div class="mt-4 pt-3.5 border-t border-[#334155] text-center">
        <p class="text-xs text-[#94A3B8]">
            Remembered your credentials?
            <a href="{{ route('login') }}" class="text-[#df747a] hover:text-white font-semibold underline transition">Back to Sign In</a>
        </p>
    </div>
</x-guest-layout>
