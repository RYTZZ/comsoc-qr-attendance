<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-[#560E0F]/40 border border-[#df747a]/30 text-[#df747a]">
                        Account & System
                    </span>
                    <span class="text-xs text-slate-400">Hub Preferences</span>
                </div>
                <h1 class="text-2xl font-bold text-white font-brand-display tracking-tight mt-1">
                    Application Settings
                </h1>
                <p class="text-xs text-slate-400 mt-0.5">
                    Manage your account details, privacy & visibility, security credentials, and alert notifications.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('profile.edit') }}" class="btn-secondary text-xs flex items-center gap-1.5 py-2 px-3.5 border-[#334155] hover:border-[#df747a]">
                    <i data-lucide="user" class="w-4 h-4 text-[#df747a]"></i>
                    <span>Full Member Profile</span>
                </a>
                <a href="{{ route('dashboard') }}" class="btn-primary text-xs flex items-center gap-1.5 py-2 px-3.5">
                    <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                    <span>Dashboard</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-[#0B1120]">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            @if (session('status') || session('success'))
                <div class="p-4 rounded-xl bg-emerald-950/40 border border-emerald-500/40 text-emerald-300 text-xs flex items-center gap-2.5 shadow-lg">
                    <i data-lucide="check-circle" class="w-4 h-4 text-emerald-400 shrink-0"></i>
                    <span>{{ session('status') === 'password-updated' ? 'Your password has been changed successfully.' : (session('success') ?? session('status')) }}</span>
                </div>
            @endif

            <!-- Section 1: Account Information & Profile Preferences -->
            <section class="card bg-[#172033] border border-[#334155] shadow-xl rounded-2xl overflow-hidden">
                <div class="p-6 border-b border-[#334155]/60 flex items-center justify-between bg-[#172033]/60">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-[#560E0F]/20 border border-[#df747a]/30 text-[#df747a] flex items-center justify-center">
                            <i data-lucide="user-check" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-white font-brand-display">Account Information & Preferences</h2>
                            <p class="text-xs text-slate-400">Update your basic display information, bio, and directory visibility.</p>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('settings.profile.update') }}" class="p-6 space-y-6">
                    @csrf
                    @method('PATCH')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label for="name" class="label text-xs text-[#94A3B8] font-semibold">Full Name</label>
                            <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required class="input text-xs w-full">
                            <x-input-error class="mt-1" :messages="$errors->get('name')" />
                        </div>

                        <div>
                            <label for="email" class="label text-xs text-[#94A3B8] font-semibold">Email Address</label>
                            <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required class="input text-xs w-full">
                            <x-input-error class="mt-1" :messages="$errors->get('email')" />
                        </div>
                    </div>

                    @if($user->student)
                        <div class="p-3.5 rounded-xl bg-[#0B1120] border border-[#334155]/60 flex flex-wrap items-center justify-between gap-3 text-xs">
                            <div class="flex items-center gap-2 text-slate-300">
                                <i data-lucide="graduation-cap" class="w-4 h-4 text-[#df747a]"></i>
                                <span>Academic Record: <strong>{{ $user->student->program }}</strong> ({{ $user->student->year_level }}, Block {{ $user->student->block }})</span>
                            </div>
                            <span class="text-[11px] font-mono text-slate-400">Student ID is protected and verified</span>
                        </div>
                    @endif

                    <div>
                        <label for="bio" class="label text-xs text-[#94A3B8] font-semibold">Short Bio & Statement</label>
                        <textarea name="bio" id="bio" rows="3" class="input text-xs w-full resize-none" placeholder="Tell fellow members what computing projects or tech areas you are passionate about...">{{ old('bio', $profile->bio) }}</textarea>
                        <x-input-error class="mt-1" :messages="$errors->get('bio')" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label for="github_url" class="label text-xs text-[#94A3B8] font-semibold">GitHub Profile URL</label>
                            <input type="url" name="github_url" id="github_url" value="{{ old('github_url', $profile->github_url) }}" placeholder="https://github.com/username" class="input text-xs w-full">
                            <x-input-error class="mt-1" :messages="$errors->get('github_url')" />
                        </div>

                        <div>
                            <label for="linkedin_url" class="label text-xs text-[#94A3B8] font-semibold">LinkedIn Profile URL</label>
                            <input type="url" name="linkedin_url" id="linkedin_url" value="{{ old('linkedin_url', $profile->linkedin_url) }}" placeholder="https://linkedin.com/in/username" class="input text-xs w-full">
                            <x-input-error class="mt-1" :messages="$errors->get('linkedin_url')" />
                        </div>
                    </div>

                    <!-- Visibility Radio Group -->
                    <div class="pt-2 border-t border-[#334155]/60" x-data="{ currentSetting: '{{ old('visibility', $profile->visibility ?? 'public') }}' }">
                        <label class="label text-xs font-semibold text-white mb-2 block">Directory Visibility</label>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <label class="p-3.5 rounded-xl border cursor-pointer transition flex flex-col justify-between"
                                   :class="currentSetting === 'public' ? 'bg-[#560E0F]/20 border-[#df747a] ring-1 ring-[#df747a]/50' : 'bg-[#0B1120] border-[#334155] hover:border-slate-500'">
                                <div class="space-y-1">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2 text-xs font-bold text-white">
                                            <i data-lucide="globe" class="w-4 h-4 text-[#df747a]"></i>
                                            <span>Public</span>
                                        </div>
                                        <input type="radio" name="visibility" value="public" x-model="currentSetting" class="w-3.5 h-3.5 text-[#560E0F] focus:ring-[#8F3033] bg-[#0B1120] border-[#334155]">
                                    </div>
                                    <p class="text-[11px] text-slate-400">Discoverable by all visitors & members in directory.</p>
                                </div>
                            </label>

                            <label class="p-3.5 rounded-xl border cursor-pointer transition flex flex-col justify-between"
                                   :class="currentSetting === 'members_only' ? 'bg-[#560E0F]/20 border-[#df747a] ring-1 ring-[#df747a]/50' : 'bg-[#0B1120] border-[#334155] hover:border-slate-500'">
                                <div class="space-y-1">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2 text-xs font-bold text-white">
                                            <i data-lucide="users" class="w-4 h-4 text-sky-400"></i>
                                            <span>Members Only</span>
                                        </div>
                                        <input type="radio" name="visibility" value="members_only" x-model="currentSetting" class="w-3.5 h-3.5 text-[#560E0F] focus:ring-[#8F3033] bg-[#0B1120] border-[#334155]">
                                    </div>
                                    <p class="text-[11px] text-slate-400">Visible only to authenticated SorSU members.</p>
                                </div>
                            </label>

                            <label class="p-3.5 rounded-xl border cursor-pointer transition flex flex-col justify-between"
                                   :class="currentSetting === 'private' ? 'bg-[#560E0F]/20 border-[#df747a] ring-1 ring-[#df747a]/50' : 'bg-[#0B1120] border-[#334155] hover:border-slate-500'">
                                <div class="space-y-1">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2 text-xs font-bold text-white">
                                            <i data-lucide="lock" class="w-4 h-4 text-amber-400"></i>
                                            <span>Private</span>
                                        </div>
                                        <input type="radio" name="visibility" value="private" x-model="currentSetting" class="w-3.5 h-3.5 text-[#560E0F] focus:ring-[#8F3033] bg-[#0B1120] border-[#334155]">
                                    </div>
                                    <p class="text-[11px] text-slate-400">Hidden from member search and public lists.</p>
                                </div>
                            </label>
                        </div>
                        <x-input-error class="mt-1" :messages="$errors->get('visibility')" />
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" class="btn-primary text-xs py-2 px-5">
                            Save Account Information
                        </button>
                    </div>
                </form>
            </section>

            <!-- Section 2: Security & Password -->
            <section class="card bg-[#172033] border border-[#334155] shadow-xl rounded-2xl overflow-hidden">
                <div class="p-6 border-b border-[#334155]/60 flex items-center justify-between bg-[#172033]/60">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-400 flex items-center justify-center">
                            <i data-lucide="shield-alert" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-white font-brand-display">Security & Password</h2>
                            <p class="text-xs text-slate-400">Ensure your account is protected with a strong credentials policy.</p>
                        </div>
                    </div>
                </div>

                <div class="p-6 space-y-6">
                    <form method="POST" action="{{ route('settings.password.update') }}" class="space-y-4 max-w-xl">
                        @csrf
                        @method('PUT')

                        <div>
                            <label for="current_password" class="label text-xs text-[#94A3B8] font-semibold">Current Password</label>
                            <input type="password" name="current_password" id="current_password" required autocomplete="current-password" class="input text-xs w-full">
                            <x-input-error class="mt-1" :messages="$errors->updatePassword->get('current_password')" />
                        </div>

                        <div>
                            <label for="password" class="label text-xs text-[#94A3B8] font-semibold">New Password</label>
                            <input type="password" name="password" id="password" required autocomplete="new-password" class="input text-xs w-full">
                            <x-input-error class="mt-1" :messages="$errors->updatePassword->get('password')" />
                        </div>

                        <div>
                            <label for="password_confirmation" class="label text-xs text-[#94A3B8] font-semibold">Confirm New Password</label>
                            <input type="password" name="password_confirmation" id="password_confirmation" required autocomplete="new-password" class="input text-xs w-full">
                            <x-input-error class="mt-1" :messages="$errors->updatePassword->get('password_confirmation')" />
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="btn-primary text-xs py-2 px-5">
                                Update Password
                            </button>
                        </div>
                    </form>

                    <!-- Active Session & Single Device Rule Highlight -->
                    <div class="pt-6 border-t border-[#334155]/60 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                                <h3 class="text-xs font-bold text-white uppercase tracking-wider">One-Active-Device Rule Enforced</h3>
                            </div>
                            <p class="text-xs text-slate-400 leading-relaxed">
                                Logging into a new device automatically invalidates your previous active session to prevent shared account abuses.
                            </p>
                        </div>
                        <a href="{{ route('profile.sessions') }}" class="btn-secondary text-xs flex items-center gap-1.5 py-2 px-4 shrink-0">
                            <i data-lucide="shield-check" class="w-4 h-4 text-[#df747a]"></i>
                            <span>View All Active Sessions</span>
                        </a>
                    </div>
                </div>
            </section>

            <!-- Section 3: Notification Preferences -->
            <section class="card bg-[#172033] border border-[#334155] shadow-xl rounded-2xl overflow-hidden">
                <div class="p-6 border-b border-[#334155]/60 flex items-center justify-between bg-[#172033]/60">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-indigo-500/10 border border-indigo-500/30 text-indigo-400 flex items-center justify-center">
                            <i data-lucide="bell" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-white font-brand-display">Notification Preferences</h2>
                            <p class="text-xs text-slate-400">Customize which automated notifications and reminders you receive.</p>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('settings.notifications.update') }}" class="p-6 space-y-4">
                    @csrf
                    @method('PATCH')

                    <div class="divide-y divide-[#334155]/50">
                        <label class="py-3.5 flex items-center justify-between cursor-pointer gap-4">
                            <div>
                                <span class="text-xs font-semibold text-white block">Event Announcements & Deadlines</span>
                                <span class="text-[11px] text-slate-400 block">Get notified when new campus workshops, competitions, and assemblies are announced.</span>
                            </div>
                            <input type="checkbox" name="notify_events" value="1" {{ old('notify_events', $notificationPreferences->notify_events) ? 'checked' : '' }} class="rounded border-[#334155] bg-[#0B1120] text-[#560E0F] focus:ring-[#8F3033] w-4 h-4">
                        </label>

                        <label class="py-3.5 flex items-center justify-between cursor-pointer gap-4">
                            <div>
                                <span class="text-xs font-semibold text-white block">Milestone & Achievement Unlocks</span>
                                <span class="text-[11px] text-slate-400 block">Notifications whenever you earn EXP, level up, or unlock badges.</span>
                            </div>
                            <input type="checkbox" name="notify_achievements" value="1" {{ old('notify_achievements', $notificationPreferences->notify_achievements) ? 'checked' : '' }} class="rounded border-[#334155] bg-[#0B1120] text-[#560E0F] focus:ring-[#8F3033] w-4 h-4">
                        </label>

                        <label class="py-3.5 flex items-center justify-between cursor-pointer gap-4">
                            <div>
                                <span class="text-xs font-semibold text-white block">Shop Rewards & Redemptions</span>
                                <span class="text-[11px] text-slate-400 block">Status updates for merchandise and reward claims.</span>
                            </div>
                            <input type="checkbox" name="notify_redemption" value="1" {{ old('notify_redemption', $notificationPreferences->notify_redemption) ? 'checked' : '' }} class="rounded border-[#334155] bg-[#0B1120] text-[#560E0F] focus:ring-[#8F3033] w-4 h-4">
                        </label>

                        <label class="py-3.5 flex items-center justify-between cursor-pointer gap-4">
                            <div>
                                <span class="text-xs font-semibold text-white block">EXP Season Expiration Warning (7 Days)</span>
                                <span class="text-[11px] text-slate-400 block">Early reminder before the active semester season EXP ledger resets.</span>
                            </div>
                            <input type="checkbox" name="notify_expiration_7d" value="1" {{ old('notify_expiration_7d', $notificationPreferences->notify_expiration_7d) ? 'checked' : '' }} class="rounded border-[#334155] bg-[#0B1120] text-[#560E0F] focus:ring-[#8F3033] w-4 h-4">
                        </label>

                        <label class="py-3.5 flex items-center justify-between cursor-pointer gap-4">
                            <div>
                                <span class="text-xs font-semibold text-white block">Final Season Expiration Alert (24 Hours)</span>
                                <span class="text-[11px] text-slate-400 block">Urgent alert 24 hours prior to semester rewards closing.</span>
                            </div>
                            <input type="checkbox" name="notify_expiration_1d" value="1" {{ old('notify_expiration_1d', $notificationPreferences->notify_expiration_1d) ? 'checked' : '' }} class="rounded border-[#334155] bg-[#0B1120] text-[#560E0F] focus:ring-[#8F3033] w-4 h-4">
                        </label>
                    </div>

                    <div class="flex justify-end pt-3">
                        <button type="submit" class="btn-primary text-xs py-2 px-5">
                            Save Notification Preferences
                        </button>
                    </div>
                </form>
            </section>

        </div>
    </div>
</x-app-layout>
