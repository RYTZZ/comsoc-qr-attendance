<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-white font-brand-display">Member Profile</h1>
                <p class="text-xs text-slate-400 mt-0.5">Manage your technical skills, portfolio links, and preferences</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('dashboard') }}" class="btn-secondary text-xs flex items-center gap-1.5">
                    <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                    <span>Dashboard</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-[#0B1120]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="card bg-[#172033] border border-[#334155] shadow-xl p-6 relative overflow-hidden">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-2xl bg-[#560E0F] border border-[#8F3033]/50 flex items-center justify-center text-white text-2xl font-bold font-brand-display shadow-lg">
                            {{ substr($user->name, 0, 1) }}
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-lg font-bold text-white font-brand-display">{{ $user->name }}</h2>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#560E0F] text-[#df747a] border border-[#8F3033]">
                                    {{ strtoupper($user->role) }}
                                </span>
                            </div>
                            <p class="text-xs text-[#94A3B8] mt-0.5">
                                {{ $student ? "{$student->program} • {$student->year_level} (Block {$student->block})" : 'Executive / Administrative Officer Account' }}
                            </p>
                            <p class="text-[11px] font-mono text-slate-500 mt-0.5">
                                {{ $user->member_id ? "Member ID: {$user->member_id}" : ($student ? "Student ID: {$student->student_number}" : "Account: {$user->email}") }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 bg-[#0B1120] border border-[#334155] p-3 rounded-xl">
                        <div class="text-center px-3 border-r border-[#334155]">
                            <span class="block text-[10px] uppercase font-semibold text-[#94A3B8]">Current Level</span>
                            <span class="text-base font-bold text-white font-brand-display">{{ $currentLevel->level }}</span>
                        </div>
                        <div class="text-center px-3 border-r border-[#334155]">
                            <span class="block text-[10px] uppercase font-semibold text-[#94A3B8]">Spendable EXP</span>
                            <span class="text-base font-bold text-amber-400 font-mono">{{ number_format($seasonPoints) }}</span>
                        </div>
                        <div class="text-center px-3">
                            <span class="block text-[10px] uppercase font-semibold text-[#94A3B8]">Lifetime EXP</span>
                            <span class="text-base font-bold text-[#df747a] font-mono">{{ number_format($lifetimeXp) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <div class="lg:col-span-2 space-y-6">
                    <div class="card bg-[#172033] border border-[#334155] shadow-xl p-6">
                        <div class="flex items-center gap-2 mb-4 border-b border-[#334155] pb-3">
                            <i data-lucide="user" class="w-4 h-4 text-[#df747a]"></i>
                            <h3 class="text-sm font-bold text-white font-brand-display uppercase tracking-wider">Profile Information & Portfolio</h3>
                        </div>

                        @if (session('status') === 'profile-updated')
                            <div class="alert-success mb-4 p-3 bg-emerald-950/40 border border-emerald-800 text-emerald-300 rounded-xl text-xs">
                                Profile details saved successfully.
                            </div>
                        @endif

                        <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
                            @csrf
                            @method('patch')

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="name" class="label text-xs text-[#94A3B8]">Display Name</label>
                                    <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" class="input text-xs" required>
                                    <x-input-error class="mt-1" :messages="$errors->get('name')" />
                                </div>

                                <div>
                                    <label for="email" class="label text-xs text-[#94A3B8]">Email Address</label>
                                    <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" class="input text-xs" required>
                                    <x-input-error class="mt-1" :messages="$errors->get('email')" />
                                </div>
                            </div>

                            <div>
                                <label for="bio" class="label text-xs text-[#94A3B8]">About / Bio</label>
                                <textarea name="bio" id="bio" rows="3" class="input text-xs">{{ old('bio', $profile->bio) }}</textarea>
                                <x-input-error class="mt-1" :messages="$errors->get('bio')" />
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="github_url" class="label text-xs text-[#94A3B8]">GitHub Profile URL</label>
                                    <input type="url" name="github_url" id="github_url" value="{{ old('github_url', $profile->github_url) }}" class="input text-xs">
                                    <x-input-error class="mt-1" :messages="$errors->get('github_url')" />
                                </div>

                                <div>
                                    <label for="linkedin_url" class="label text-xs text-[#94A3B8]">LinkedIn Profile URL</label>
                                    <input type="url" name="linkedin_url" id="linkedin_url" value="{{ old('linkedin_url', $profile->linkedin_url) }}" class="input text-xs">
                                    <x-input-error class="mt-1" :messages="$errors->get('linkedin_url')" />
                                </div>
                            </div>

                            <div>
                                <label for="interests" class="label text-xs text-[#94A3B8]">Interests & Topics (comma-separated)</label>
                                <input type="text" name="interests" id="interests"
                                       value="{{ old('interests', is_array($profile->interests) ? implode(', ', $profile->interests) : '') }}"
                                       class="input text-xs">
                                <x-input-error class="mt-1" :messages="$errors->get('interests')" />
                            </div>

                            <div>
                                <label class="label text-xs text-[#94A3B8]">Technical Skills & Stack</label>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 mt-1">
                                    @php
                                        $commonSkills = ['PHP', 'Laravel', 'JavaScript', 'React', 'Python', 'TailwindCSS', 'MySQL', 'Git', 'Linux'];
                                        $userSkillNames = old('skills') !== null 
                                            ? (array) old('skills') 
                                            : $user->skills->pluck('name')->toArray();
                                    @endphp
                                    @foreach($commonSkills as $sk)
                                    <label class="flex items-center gap-2 p-2 rounded-xl bg-[#0B1120] border border-[#334155] text-xs text-slate-300 hover:border-[#8F3033]/60 cursor-pointer">
                                        <input type="checkbox" name="skills[]" value="{{ $sk }}"
                                               {{ in_array($sk, $userSkillNames) ? 'checked' : '' }}
                                               class="w-3.5 h-3.5 rounded bg-[#0B1120] border-[#334155] text-[#560E0F] focus:ring-[#8F3033]">
                                        <span>{{ $sk }}</span>
                                    </label>
                                    @endforeach
                                </div>
                                <x-input-error class="mt-1" :messages="$errors->get('skills')" />
                                <x-input-error class="mt-1" :messages="$errors->get('skills.*')" />
                            </div>

                            <div class="pt-2">
                                <label class="flex items-center gap-2 text-xs text-[#94A3B8] cursor-pointer">
                                    <input type="checkbox" name="is_public" value="1" {{ old('is_public', $profile->is_public) ? 'checked' : '' }}
                                           class="w-4 h-4 rounded bg-[#0B1120] border-[#334155] text-[#560E0F] focus:ring-[#8F3033]">
                                    <span>Show my profile in the public Computing Society member directory</span>
                                </label>
                            </div>

                            <div class="pt-3 flex justify-end">
                                <button type="submit" class="btn-primary text-xs py-2 px-5">Save Profile Changes</button>
                            </div>
                        </form>
                    </div>

                    <div class="card bg-[#172033] border border-[#334155] shadow-xl p-6">
                        <div class="flex items-center gap-2 mb-4 border-b border-[#334155] pb-3">
                            <i data-lucide="key-round" class="w-4 h-4 text-amber-400"></i>
                            <h3 class="text-sm font-bold text-white font-brand-display uppercase tracking-wider">Update Account Password</h3>
                        </div>

                        <div class="max-w-xl">
                            @include('profile.partials.update-password-form')
                        </div>
                    </div>
                </div>

                <div class="space-y-6">

                    <div class="card bg-[#172033] border border-[#334155] shadow-xl p-5">
                        <div class="flex items-center justify-between mb-3 border-b border-[#334155] pb-2">
                            <span class="text-xs font-semibold text-white uppercase tracking-wider">XP Transaction Log</span>
                            <i data-lucide="sparkles" class="w-4 h-4 text-[#df747a]"></i>
                        </div>
                        <div class="space-y-2">
                            @forelse($xpHistory as $tx)
                            <div class="flex items-center justify-between text-xs py-1.5 border-b border-[#334155]/60 last:border-0">
                                <div>
                                    <p class="font-medium text-slate-200">{{ $tx->description }}</p>
                                    <p class="text-[10px] text-slate-500">{{ $tx->created_at->format('M d, Y') }}</p>
                                </div>
                                <span class="font-bold text-[#df747a] font-mono tabular-nums">+{{ $tx->amount }} EXP</span>
                            </div>
                            @empty
                            <p class="text-xs text-[#94A3B8] text-center py-3">No XP history available.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="card bg-[#172033] border border-[#334155] shadow-xl p-5">
                        <div class="flex items-center justify-between mb-3 border-b border-[#334155] pb-2">
                            <span class="text-xs font-semibold text-white uppercase tracking-wider">Point Ledger</span>
                            <i data-lucide="coins" class="w-4 h-4 text-amber-400"></i>
                        </div>
                        <div class="space-y-2">
                            @forelse($pointHistory as $pt)
                            <div class="flex items-center justify-between text-xs py-1.5 border-b border-[#334155]/60 last:border-0">
                                <div>
                                    <p class="font-medium text-slate-200">{{ $pt->description }}</p>
                                    <p class="text-[10px] text-slate-500">{{ $pt->created_at->format('M d, Y') }}</p>
                                </div>
                                <span class="font-bold font-mono tabular-nums {{ $pt->amount >= 0 ? 'text-amber-400' : 'text-red-400' }}">
                                    {{ $pt->amount >= 0 ? '+' : '' }}{{ $pt->amount }} pts
                                </span>
                            </div>
                            @empty
                            <p class="text-xs text-[#94A3B8] text-center py-3">No point transactions yet.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="card bg-[#172033] border border-[#334155] shadow-xl p-5">
                        <div class="flex items-center justify-between mb-3 border-b border-[#334155] pb-2">
                            <span class="text-xs font-semibold text-white uppercase tracking-wider">Recent Event Check-Ins</span>
                            <i data-lucide="calendar-check" class="w-4 h-4 text-emerald-400"></i>
                        </div>
                        <div class="space-y-2">
                            @forelse($attendanceHistory as $att)
                            <div class="p-2.5 rounded-xl bg-[#0B1120] border border-[#334155] text-xs">
                                <p class="font-bold text-white font-brand-display">{{ $att->event ? $att->event->name : 'General Event' }}</p>
                                <p class="text-[10px] text-[#94A3B8]">{{ $att->scanned_at ? $att->scanned_at->format('M d, Y H:i') : '' }}</p>
                            </div>
                            @empty
                            <p class="text-xs text-[#94A3B8] text-center py-3">No event attendance records yet.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="card bg-red-950/20 border border-red-900/40 p-5 shadow-xl">
                        <h4 class="text-xs font-bold text-red-300 uppercase tracking-wider mb-2 font-brand-display">Danger Zone</h4>
                        <div class="max-w-xl">
                            @include('profile.partials.delete-user-form')
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
</x-app-layout>
