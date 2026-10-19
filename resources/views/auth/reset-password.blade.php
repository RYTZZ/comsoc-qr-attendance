<x-guest-layout>
    <div class="p-2 sm:p-4">
        <div class="mb-6">
            <h2 class="font-brand-display text-xl font-bold text-white tracking-tight">Set New Password</h2>
            <p class="text-xs text-[#94A3B8] mt-1.5 leading-relaxed">
                Choose a strong password to secure your ComSoc Hub account.
            </p>
        </div>

        @if($errors->any())
        <div class="alert-error mb-4">
            <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <span>{{ $errors->first() }}</span>
        </div>
        @endif

        <form method="POST" action="{{ route('password.store') }}" class="space-y-4" x-data="{ submitting: false, showPassword: false, showConfirm: false }" @submit="submitting = true">
            @csrf

            <!-- Password Reset Token -->
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <!-- Email Address -->
            <div>
                <label for="email" class="label">Email Address</label>
                <input id="email" 
                       class="input" 
                       type="email" 
                       name="email" 
                       value="{{ old('email', $request->email) }}" 
                       required 
                       autofocus 
                       autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="label">New Password</label>
                <div class="relative">
                    <input :type="showPassword ? 'text' : 'password'" 
                           id="password" 
                           class="input pr-10" 
                           type="password" 
                           name="password" 
                           placeholder="At least 8 characters"
                           required 
                           autocomplete="new-password" />
                    <button type="button" @click="showPassword = !showPassword" 
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-white transition">
                        <svg x-show="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <svg x-show="showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                        </svg>
                    </button>
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <!-- Confirm Password -->
            <div>
                <label for="password_confirmation" class="label">Confirm New Password</label>
                <div class="relative">
                    <input :type="showConfirm ? 'text' : 'password'" 
                           id="password_confirmation" 
                           class="input pr-10"
                           type="password"
                           name="password_confirmation" 
                           placeholder="Re-enter your new password"
                           required 
                           autocomplete="new-password" />
                    <button type="button" @click="showConfirm = !showConfirm" 
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-white transition">
                        <svg x-show="!showConfirm" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <svg x-show="showConfirm" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                        </svg>
                    </button>
                </div>
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>

            <div class="pt-2">
                <button type="submit" 
                        class="btn-primary w-full justify-center py-2.5 shadow-xl disabled:opacity-50 disabled:cursor-not-allowed" 
                        :disabled="submitting">
                    <span x-show="!submitting">Reset Password</span>
                    <span x-show="submitting" class="flex items-center gap-2" style="display: none;">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        Saving New Password...
                    </span>
                </button>
            </div>
        </form>

        <div class="mt-6 pt-5 border-t border-[#334155] text-center">
            <p class="text-xs text-[#94A3B8]">
                Already know your password?
                <a href="{{ route('login') }}" class="text-[#df747a] hover:text-white font-semibold underline transition">Back to Sign In</a>
            </p>
        </div>
    </div>
</x-guest-layout>
