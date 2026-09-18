<section>
    <header>
        <h2 class="text-base font-bold text-white font-brand-display">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-xs text-slate-400">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-4">
        @csrf
        @method('patch')

        <div>
            <label for="name" class="label">{{ __('Full Name') }}</label>
            <input id="name" name="name" type="text" class="input w-full" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name" />
            @error('name')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="email" class="label">{{ __('Email Address') }}</label>
            <input id="email" name="email" type="email" class="input w-full" value="{{ old('email', $user->email) }}" required autocomplete="username" />
            @error('email')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-2">
                    <p class="text-xs text-amber-400">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-xs text-indigo-400 hover:text-indigo-300 ml-1">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-1 font-medium text-xs text-emerald-400">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4 pt-2">
            <button type="submit" class="btn-primary">{{ __('Save Changes') }}</button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2500)"
                    class="text-xs text-emerald-400 font-medium"
                >{{ __('Saved successfully.') }}</p>
            @endif
        </div>
    </form>
</section>
