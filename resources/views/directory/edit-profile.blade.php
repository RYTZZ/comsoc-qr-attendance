<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('directory.index') }}" class="btn-secondary p-2 rounded-xl text-slate-400 hover:text-white">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-cyan-500/20 border border-cyan-500/40 text-cyan-300">
                        Profile Settings
                    </span>
                    <span class="text-xs text-slate-400">Collaborator Portfolio</span>
                </div>
                <h1 class="text-2xl font-bold text-white font-brand-display tracking-tight mt-0.5">
                    Edit Collaborator Profile & Skills
                </h1>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm flex items-center gap-3">
                    <i data-lucide="check-circle" class="w-5 h-5 flex-shrink-0"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('directory.profile.update') }}" class="card bg-[#172033] border border-slate-800 p-6 sm:p-8 rounded-2xl space-y-6">
                @csrf
                @method('PUT')

                <div class="space-y-4">
                    <h2 class="text-base font-bold text-white font-brand-display pb-3 border-b border-slate-800 flex items-center gap-2">
                        <i data-lucide="shield" class="w-4 h-4 text-cyan-400"></i>
                        <span>Privacy & Availability</span>
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="p-4 rounded-xl bg-slate-900 border border-slate-800 flex items-start gap-3">
                            <input type="checkbox" name="is_public" id="is_public" value="1" {{ old('is_public', $profile->is_public) ? 'checked' : '' }}
                                   class="mt-1 rounded border-slate-700 bg-slate-800 text-[#560E0F] focus:ring-[#560E0F]">
                            <div>
                                <label for="is_public" class="text-xs font-bold text-white block cursor-pointer">
                                    Show in Member Directory & Leaderboard
                                </label>
                                <p class="text-[11px] text-slate-400 mt-0.5">
                                    When disabled, your profile will remain hidden from other students in search and public leaderboards.
                                </p>
                            </div>
                        </div>

                        <div class="p-4 rounded-xl bg-slate-900 border border-slate-800 flex items-start gap-3">
                            <input type="checkbox" name="open_to_collaborate" id="open_to_collaborate" value="1" {{ old('open_to_collaborate', $profile->open_to_collaborate) ? 'checked' : '' }}
                                   class="mt-1 rounded border-slate-700 bg-slate-800 text-[#560E0F] focus:ring-[#560E0F]">
                            <div>
                                <label for="open_to_collaborate" class="text-xs font-bold text-white block cursor-pointer">
                                    Open to Project Collaboration
                                </label>
                                <p class="text-[11px] text-slate-400 mt-0.5">
                                    Allow other computing society members to discover you as a potential hackathon or study group teammate.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <h2 class="text-base font-bold text-white font-brand-display pb-3 border-b border-slate-800 flex items-center gap-2">
                        <i data-lucide="user" class="w-4 h-4 text-cyan-400"></i>
                        <span>About & Links</span>
                    </h2>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">
                            Bio / Intro Summary
                        </label>
                        <textarea name="bio" rows="3"
                                  placeholder="Brief introduction about yourself, career aspirations, or background..."
                                  class="input-dark text-xs w-full">{{ old('bio', $profile->bio) }}</textarea>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">
                                GitHub Profile URL
                            </label>
                            <input type="url" name="github_url" value="{{ old('github_url', $profile->github_url) }}"
                                   placeholder="https://github.com/username"
                                   class="input-dark text-xs w-full">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">
                                LinkedIn Profile URL
                            </label>
                            <input type="url" name="linkedin_url" value="{{ old('linkedin_url', $profile->linkedin_url) }}"
                                   placeholder="https://linkedin.com/in/username"
                                   class="input-dark text-xs w-full">
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <h2 class="text-base font-bold text-white font-brand-display pb-3 border-b border-slate-800 flex items-center gap-2">
                        <i data-lucide="cpu" class="w-4 h-4 text-cyan-400"></i>
                        <span>Technical Skills & Stacks</span>
                    </h2>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-2">
                            Select Your Technical Skills
                        </label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2.5 max-h-56 overflow-y-auto p-3 rounded-xl bg-slate-900 border border-slate-800">
                            @foreach($allSkills as $skill)
                                <label class="flex items-center gap-2 text-xs text-slate-300 cursor-pointer hover:text-white p-1 rounded transition-colors">
                                    <input type="checkbox" name="skill_ids[]" value="{{ $skill->id }}"
                                           {{ in_array($skill->id, old('skill_ids', $userSkillIds)) ? 'checked' : '' }}
                                           class="rounded border-slate-700 bg-slate-800 text-[#560E0F] focus:ring-[#560E0F]">
                                    <span class="truncate">{{ $skill->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">
                            Languages & Tech Stack (Comma separated)
                        </label>
                        <input type="text" name="tech_stack"
                               value="{{ old('tech_stack', is_array($profile->tech_stack) ? implode(', ', $profile->tech_stack) : '') }}"
                               placeholder="e.g. Laravel, React, Tailwind CSS, PostgreSQL, Python"
                               class="input-dark text-xs w-full">
                    </div>
                </div>

                <div class="space-y-4">
                    <h2 class="text-base font-bold text-white font-brand-display pb-3 border-b border-slate-800 flex items-center gap-2">
                        <i data-lucide="sparkles" class="w-4 h-4 text-cyan-400"></i>
                        <span>Interests & Project Domains</span>
                    </h2>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-2">
                            Preferred Project Domains (Check all that apply)
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach($projectCategories as $domain)
                                <label class="flex items-center gap-2 text-xs text-slate-300 cursor-pointer hover:text-white p-2 rounded-xl bg-slate-900 border border-slate-800 transition-colors">
                                    <input type="checkbox" name="project_categories[]" value="{{ $domain }}"
                                           {{ in_array($domain, old('project_categories', $profile->project_categories ?? [])) ? 'checked' : '' }}
                                           class="rounded border-slate-700 bg-slate-800 text-[#560E0F] focus:ring-[#560E0F]">
                                    <span>{{ $domain }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">
                                Areas of Interest (Comma separated)
                            </label>
                            <input type="text" name="interests"
                                   value="{{ old('interests', is_array($profile->interests) ? implode(', ', $profile->interests) : '') }}"
                                   placeholder="e.g. Open Source, Cloud Architecture, Competitive Programming"
                                   class="input-dark text-xs w-full">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">
                                Collaboration Styles (Comma separated)
                            </label>
                            <input type="text" name="collaboration_preferences"
                                   value="{{ old('collaboration_preferences', is_array($profile->collaboration_preferences) ? implode(', ', $profile->collaboration_preferences) : '') }}"
                                   placeholder="e.g. Remote Pairing, Weekend Hackathons, Async Reviews"
                                   class="input-dark text-xs w-full">
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-800 flex items-center justify-end gap-3">
                    <a href="{{ route('directory.index') }}" class="btn-secondary text-xs py-2 px-4">
                        Cancel
                    </a>
                    <button type="submit" class="btn-primary text-xs py-2 px-5 flex items-center gap-1.5">
                        <i data-lucide="save" class="w-3.5 h-3.5"></i>
                        <span>Save Portfolio Settings</span>
                    </button>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>
