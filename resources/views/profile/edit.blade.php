<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-white font-brand-display">User Profile</h1>
                <p class="text-xs text-slate-400 mt-0.5">Manage your personal account credentials and preferences</p>
            </div>
            <div class="w-8 h-8 rounded-full bg-[#7A1618] flex items-center justify-center text-white shadow">
                <i data-lucide="user-round" class="w-4 h-4"></i>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="card shadow-xl border border-slate-800 bg-[#171a23]">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="card shadow-xl border border-slate-800 bg-[#171a23]">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="card shadow-xl border border-red-900/30 bg-red-950/10">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
