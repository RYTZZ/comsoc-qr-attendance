<section class="space-y-4">
    <header>
        <h2 class="text-base font-bold text-red-400 font-brand-display">
            {{ __('Delete Account') }}
        </h2>

        <p class="mt-1 text-xs text-slate-400">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
        </p>
    </header>

    <button
        type="button"
        class="btn-danger btn-sm"
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >{{ __('Delete Account') }}</button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6 bg-[#171a23] border border-slate-800 rounded-2xl text-slate-100">
            @csrf
            @method('delete')

            <h2 class="text-base font-bold text-white font-brand-display">
                {{ __('Are you sure you want to delete your account?') }}
            </h2>

            <p class="mt-2 text-xs text-slate-400 leading-relaxed">
                {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
            </p>

            <div class="mt-4">
                <label for="password" class="label sr-only">{{ __('Password') }}</label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    class="input w-full"
                    placeholder="{{ __('Enter your password to confirm') }}"
                />
                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-1" />
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <button type="button" class="btn-secondary btn-sm" x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </button>

                <button type="submit" class="btn-danger btn-sm">
                    {{ __('Delete Account') }}
                </button>
            </div>
        </form>
    </x-modal>
</section>
