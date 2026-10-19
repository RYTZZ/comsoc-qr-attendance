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
                        <x-user-avatar :user="$user" size="xl" class="shadow-lg border-2 border-[#8F3033]/60" />
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
                                {{ $user->member_id ? "Member ID: {$user->member_id}" : (auth()->user()?->isSuperAdmin() ? ($student ? "Student ID: {$student->student_number}" : "Account: {$user->email}") : "Account: {$user->email}") }}
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

                        <form method="POST" action="{{ route('profile.update') }}" class="space-y-6"
                              x-data="{
                                  selectedSeed: '{{ addslashes(old('avatar_seed', $user->avatar_seed ?: 'Felix')) }}',
                                  style: 'adventurer',
                                  get previewUrl() {
                                      return 'https://api.dicebear.com/9.x/adventurer/svg?seed=' + encodeURIComponent(this.selectedSeed);
                                  }
                              }">
                            @csrf
                            @method('patch')

                            <!-- Adventurer Avatar Selection Gallery -->
                            <div class="p-5 rounded-2xl bg-[#0B1120]/80 border border-[#334155] shadow-inner space-y-4">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-[#334155]">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-xl bg-burgundy-deep border border-[#8F3033]/60 flex items-center justify-center text-white text-xs">
                                            <i data-lucide="sparkles" class="w-4 h-4 text-[#df747a]"></i>
                                        </div>
                                        <div>
                                            <h4 class="text-xs font-bold text-white uppercase tracking-wider font-brand-display">Adventurer Character Avatar</h4>
                                            <p class="text-[11px] text-slate-400">Select your cartoon persona from the official DiceBear Adventurer collection</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="text-[10px] text-slate-400 font-medium">Live Preview:</span>
                                        <div class="w-12 h-12 rounded-2xl bg-[#172033] border-2 border-[#8F3033] p-1 shadow-md shrink-0 flex items-center justify-center overflow-hidden">
                                            <img :src="previewUrl" :alt="selectedSeed" class="w-full h-full object-cover rounded-xl" />
                                        </div>
                                    </div>
                                </div>

                                <input type="hidden" name="avatar_style" :value="style">
                                <input type="hidden" name="avatar_seed" :value="selectedSeed">

                                <!-- Character Grid Container -->
                                <div class="max-h-[380px] sm:max-h-[420px] overflow-y-auto pr-1 -mr-1 scrollbar-thin">
                                    <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-2.5 sm:gap-3"
                                         role="radiogroup"
                                         aria-label="Adventurer Character Avatar options">
                                        @foreach($avatarPresets as $preset)
                                        <button type="button"
                                                role="radio"
                                                :aria-checked="selectedSeed === '{{ $preset['seed'] }}'"
                                                aria-label="{{ $preset['name'] }}"
                                                @click="selectedSeed = '{{ $preset['seed'] }}'"
                                                class="group relative flex flex-col items-center justify-center p-2 rounded-xl border transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-[#8F3033] focus:ring-offset-1 focus:ring-offset-[#0B1120] cursor-pointer min-h-[92px] sm:min-h-[100px]"
                                                :class="selectedSeed === '{{ $preset['seed'] }}'
                                                    ? 'bg-[#560E0F]/30 border-[#df747a] ring-1 ring-[#df747a]/60 shadow-md'
                                                    : 'bg-[#172033] border-[#334155] hover:border-slate-500 hover:bg-[#1E293B]'">
                                            <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-xl overflow-hidden bg-[#0B1120] border border-[#334155] p-0.5 flex items-center justify-center shrink-0">
                                                <img src="{{ $preset['url'] }}" alt="" aria-hidden="true" class="w-full h-full object-cover transition-transform group-hover:scale-105" loading="lazy" />
                                            </div>
                                            <span class="text-[10px] sm:text-[11px] font-semibold mt-1.5 truncate max-w-full text-center tracking-tight"
                                                  :class="selectedSeed === '{{ $preset['seed'] }}' ? 'text-white font-bold' : 'text-slate-400 group-hover:text-slate-200'">
                                                {{ $preset['name'] }}
                                            </span>
                                            <div x-show="selectedSeed === '{{ $preset['seed'] }}'"
                                                 class="absolute top-1.5 right-1.5 w-4 h-4 rounded-full bg-[#560E0F] border border-[#df747a] text-white flex items-center justify-center shadow">
                                                <i data-lucide="check" class="w-2.5 h-2.5 text-white stroke-[3]"></i>
                                            </div>
                                        </button>
                                        @endforeach
                                    </div>
                                </div>
                                <x-input-error class="mt-1" :messages="$errors->get('avatar_seed')" />
                                <x-input-error class="mt-1" :messages="$errors->get('avatar_style')" />
                            </div>

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

                            @php
                                $userSkillNames = old('skills') !== null 
                                    ? array_values(array_filter((array) old('skills'))) 
                                    : $user->skills->pluck('name')->toArray();
                                
                                $userInterests = old('interests') !== null
                                    ? (is_array(old('interests')) ? old('interests') : array_values(array_filter(array_map('trim', explode(',', old('interests'))))))
                                    : (is_array($profile->interests) ? $profile->interests : []);

                                $currentVisibility = old('visibility', $profile->visibility ?? ($profile->is_public ? 'public' : 'private'));
                            @endphp

                            <!-- Technical Skills & Stack Section -->
                            <div x-data="skillsSelector({
                                initialSkills: {{ json_encode($userSkillNames) }},
                                categories: {{ json_encode($skillCategories) }}
                            })" class="space-y-3 pt-2 border-t border-[#334155]/60">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <label class="label text-xs font-semibold text-white flex items-center gap-1.5 mb-0.5">
                                            <i data-lucide="code-2" class="w-4 h-4 text-[#df747a]"></i>
                                            <span>Technical Skills & Stack</span>
                                        </label>
                                        <p class="text-[11px] text-[#94A3B8]">Languages, frameworks, databases, and core computing competencies.</p>
                                    </div>
                                    <button type="button" @click="openModal()" 
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-medium bg-[#0B1120] text-slate-200 border border-[#334155] hover:border-[#8F3033] hover:text-white transition">
                                        <i data-lucide="plus" class="w-3.5 h-3.5 text-[#df747a]"></i>
                                        <span x-text="savedSkills.length > 0 ? 'Edit Skills' : 'Add Skills'"></span>
                                    </button>
                                </div>

                                <!-- Preview Chips on Main Profile Card -->
                                <div>
                                    <template x-if="savedSkills.length === 0">
                                        <div class="p-3.5 rounded-xl border border-dashed border-[#334155] bg-[#0B1120]/50 text-center">
                                            <p class="text-xs text-slate-400">No skills selected yet.</p>
                                            <button type="button" @click="openModal()" class="mt-1 text-xs text-[#df747a] hover:underline font-semibold">
                                                + Explore skills catalog
                                            </button>
                                        </div>
                                    </template>

                                    <template x-if="savedSkills.length > 0">
                                        <div class="space-y-2">
                                            <div class="flex flex-wrap gap-1.5 items-center">
                                                <template x-for="(skill, index) in visibleSkills" :key="skill">
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-[#0B1120] border border-[#334155] text-slate-200">
                                                        <span x-text="skill"></span>
                                                        <button type="button" @click="removeSavedSkill(skill)" class="text-slate-400 hover:text-red-400 transition" title="Remove skill">
                                                            <i data-lucide="x" class="w-3 h-3"></i>
                                                        </button>
                                                    </span>
                                                </template>

                                                <template x-if="savedSkills.length > maxPreview && !showAll">
                                                    <button type="button" @click="showAll = true" 
                                                            class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-[#560E0F]/40 border border-[#8F3033]/60 text-[#df747a] hover:bg-[#560E0F]/70 transition">
                                                        +<span x-text="savedSkills.length - maxPreview"></span> more
                                                    </button>
                                                </template>

                                                <template x-if="showAll && savedSkills.length > maxPreview">
                                                    <button type="button" @click="showAll = false" 
                                                            class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-slate-800 text-slate-400 hover:text-white transition">
                                                        Show less
                                                    </button>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                <!-- Hidden inputs to submit with the form -->
                                <template x-for="skill in savedSkills" :key="'input-skill-' + skill">
                                    <input type="hidden" name="skills[]" :value="skill">
                                </template>
                                <x-input-error class="mt-1" :messages="$errors->get('skills')" />
                                <x-input-error class="mt-1" :messages="$errors->get('skills.*')" />

                                <!-- Alpine Modal: Skills Selector -->
                                <div x-show="isOpen" 
                                     x-cloak
                                     class="fixed inset-0 z-50 overflow-y-auto"
                                     style="background-color: rgba(3, 7, 18, 0.75); backdrop-filter: blur(4px);"
                                     @keydown.escape.window="cancelModal()">
                                    <div class="min-h-full flex items-center justify-center p-4">
                                        <div class="relative w-full max-w-3xl rounded-2xl bg-[#0F172A] border border-[#334155] shadow-2xl flex flex-col max-h-[88vh] overflow-hidden"
                                             @click.away="cancelModal()">
                                            
                                            <!-- Modal Header -->
                                            <div class="p-5 border-b border-[#334155] flex items-center justify-between">
                                                <div>
                                                    <h3 class="text-base font-bold text-white font-brand-display flex items-center gap-2">
                                                        <i data-lucide="code-2" class="w-5 h-5 text-[#df747a]"></i>
                                                        <span>Select Technical Skills & Stack</span>
                                                    </h3>
                                                    <p class="text-xs text-[#94A3B8] mt-0.5">Explore categories, search, or add custom technical skills.</p>
                                                </div>
                                                <button type="button" @click="cancelModal()" class="text-slate-400 hover:text-white p-1 rounded-lg">
                                                    <i data-lucide="x" class="w-5 h-5"></i>
                                                </button>
                                            </div>

                                            <!-- Modal Body: Search & Category Navigation -->
                                            <div class="p-5 space-y-4 overflow-y-auto flex-1">
                                                
                                                <!-- Search Bar & Custom Skill Input -->
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                    <div class="relative">
                                                        <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                                                        <input type="text" x-model="search" placeholder="Search predefined skills..." 
                                                               class="input text-xs pl-9 w-full bg-[#0B1120] border-[#334155] focus:border-[#8F3033]">
                                                    </div>
                                                    <div class="flex gap-2">
                                                        <input type="text" x-model="customInput" @keydown.enter.prevent="addCustomSkill()" 
                                                               placeholder="Add custom skill (e.g. Solidity)" 
                                                               class="input text-xs w-full bg-[#0B1120] border-[#334155] focus:border-[#8F3033]">
                                                        <button type="button" @click="addCustomSkill()" 
                                                                class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-[#560E0F] hover:bg-[#8F3033] text-white border border-[#8F3033] transition shrink-0">
                                                            Add
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Review Tray: Selected Skills Chips in Modal -->
                                                <div class="p-3 rounded-xl bg-[#0B1120] border border-[#334155]">
                                                    <div class="flex items-center justify-between mb-2">
                                                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                                            Selected Skills (<span x-text="draftSkills.length"></span>)
                                                        </span>
                                                        <button type="button" x-show="draftSkills.length > 0" @click="draftSkills = []" 
                                                                class="text-[10px] text-red-400 hover:underline">
                                                            Clear All
                                                        </button>
                                                    </div>
                                                    <template x-if="draftSkills.length === 0">
                                                        <p class="text-xs text-slate-500 italic">No skills selected in this session.</p>
                                                    </template>
                                                    <div class="flex flex-wrap gap-1.5 max-h-24 overflow-y-auto">
                                                        <template x-for="skill in draftSkills" :key="'draft-' + skill">
                                                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-xs font-medium bg-[#560E0F]/50 border border-[#8F3033] text-white">
                                                                <span x-text="skill"></span>
                                                                <button type="button" @click="toggleSkill(skill)" class="text-slate-300 hover:text-white">
                                                                    <i data-lucide="x" class="w-3 h-3"></i>
                                                                </button>
                                                            </span>
                                                        </template>
                                                    </div>
                                                </div>

                                                <!-- Category Filter Pills -->
                                                <div class="flex gap-1.5 overflow-x-auto pb-1.5 scrollbar-thin">
                                                    <button type="button" @click="activeCategory = 'All'" 
                                                            :class="activeCategory === 'All' ? 'bg-[#560E0F] text-white border-[#8F3033]' : 'bg-[#0B1120] text-slate-400 border-[#334155] hover:text-slate-200'"
                                                            class="px-2.5 py-1 rounded-lg text-xs font-medium border transition whitespace-nowrap">
                                                        All Categories
                                                    </button>
                                                    <template x-for="cat in categoryNames" :key="cat">
                                                        <button type="button" @click="activeCategory = cat" 
                                                                :class="activeCategory === cat ? 'bg-[#560E0F] text-white border-[#8F3033]' : 'bg-[#0B1120] text-slate-400 border-[#334155] hover:text-slate-200'"
                                                                class="px-2.5 py-1 rounded-lg text-xs font-medium border transition whitespace-nowrap"
                                                                x-text="cat">
                                                        </button>
                                                    </template>
                                                </div>

                                                <!-- Skills Options Grid -->
                                                <div class="space-y-4">
                                                    <template x-for="(skillsList, catName) in filteredGroupedSkills" :key="catName">
                                                        <div x-show="skillsList.length > 0" class="space-y-1.5">
                                                            <h4 class="text-[11px] font-bold uppercase tracking-wider text-slate-400" x-text="catName"></h4>
                                                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                                                <template x-for="item in skillsList" :key="item">
                                                                    <button type="button" @click="toggleSkill(item)"
                                                                            :class="isSkillSelected(item) ? 'bg-[#560E0F]/40 border-[#df747a] text-white font-semibold' : 'bg-[#0B1120] border-[#334155] text-slate-300 hover:border-slate-500'"
                                                                            class="p-2 rounded-xl border text-left text-xs flex items-center justify-between transition group">
                                                                        <span class="truncate" x-text="item"></span>
                                                                        <span x-show="isSkillSelected(item)" class="text-[#df747a]">
                                                                            <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                                                        </span>
                                                                    </button>
                                                                </template>
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>

                                            </div>

                                            <!-- Modal Footer Actions -->
                                            <div class="p-4 border-t border-[#334155] bg-[#0B1120] flex items-center justify-between">
                                                <span class="text-xs text-slate-400">
                                                    <span x-text="draftSkills.length"></span> skills selected
                                                </span>
                                                <div class="flex items-center gap-2">
                                                    <button type="button" @click="cancelModal()" class="btn-secondary text-xs py-1.5 px-4">
                                                        Cancel
                                                    </button>
                                                    <button type="button" @click="applyChanges()" class="btn-primary text-xs py-1.5 px-4 bg-[#560E0F] hover:bg-[#8F3033] border-[#8F3033]">
                                                        Apply Skills
                                                    </button>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Interests & Topics Section -->
                            <div x-data="interestsSelector({
                                initialInterests: {{ json_encode($userInterests) }},
                                categories: {{ json_encode($interestCategories) }}
                            })" class="space-y-3 pt-4 border-t border-[#334155]/60">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <label class="label text-xs font-semibold text-white flex items-center gap-1.5 mb-0.5">
                                            <i data-lucide="compass" class="w-4 h-4 text-[#df747a]"></i>
                                            <span>Interests & Topics</span>
                                        </label>
                                        <p class="text-[11px] text-[#94A3B8]">Professional focus, hackathons, open source, and learning interests.</p>
                                    </div>
                                    <button type="button" @click="openModal()" 
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-medium bg-[#0B1120] text-slate-200 border border-[#334155] hover:border-[#8F3033] hover:text-white transition">
                                        <i data-lucide="plus" class="w-3.5 h-3.5 text-[#df747a]"></i>
                                        <span x-text="savedInterests.length > 0 ? 'Edit Interests' : 'Add Interests'"></span>
                                    </button>
                                </div>

                                <!-- Preview Chips on Main Profile Card -->
                                <div>
                                    <template x-if="savedInterests.length === 0">
                                        <div class="p-3.5 rounded-xl border border-dashed border-[#334155] bg-[#0B1120]/50 text-center">
                                            <p class="text-xs text-slate-400">No interests selected yet.</p>
                                            <button type="button" @click="openModal()" class="mt-1 text-xs text-[#df747a] hover:underline font-semibold">
                                                + Explore interests catalog
                                            </button>
                                        </div>
                                    </template>

                                    <template x-if="savedInterests.length > 0">
                                        <div class="space-y-2">
                                            <div class="flex flex-wrap gap-1.5 items-center">
                                                <template x-for="(interest, index) in visibleInterests" :key="interest">
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-[#0B1120] border border-[#334155] text-slate-200">
                                                        <span x-text="interest"></span>
                                                        <button type="button" @click="removeSavedInterest(interest)" class="text-slate-400 hover:text-red-400 transition" title="Remove interest">
                                                            <i data-lucide="x" class="w-3 h-3"></i>
                                                        </button>
                                                    </span>
                                                </template>

                                                <template x-if="savedInterests.length > maxPreview && !showAll">
                                                    <button type="button" @click="showAll = true" 
                                                            class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-[#560E0F]/40 border border-[#8F3033]/60 text-[#df747a] hover:bg-[#560E0F]/70 transition">
                                                        +<span x-text="savedInterests.length - maxPreview"></span> more
                                                    </button>
                                                </template>

                                                <template x-if="showAll && savedInterests.length > maxPreview">
                                                    <button type="button" @click="showAll = false" 
                                                            class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-slate-800 text-slate-400 hover:text-white transition">
                                                        Show less
                                                    </button>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                <!-- Hidden comma-separated input to maintain backward compatibility -->
                                <input type="hidden" name="interests" :value="savedInterests.join(', ')">
                                <x-input-error class="mt-1" :messages="$errors->get('interests')" />

                                <!-- Alpine Modal: Interests Selector -->
                                <div x-show="isOpen" 
                                     x-cloak
                                     class="fixed inset-0 z-50 overflow-y-auto"
                                     style="background-color: rgba(3, 7, 18, 0.75); backdrop-filter: blur(4px);"
                                     @keydown.escape.window="cancelModal()">
                                    <div class="min-h-full flex items-center justify-center p-4">
                                        <div class="relative w-full max-w-3xl rounded-2xl bg-[#0F172A] border border-[#334155] shadow-2xl flex flex-col max-h-[88vh] overflow-hidden"
                                             @click.away="cancelModal()">
                                            
                                            <!-- Modal Header -->
                                            <div class="p-5 border-b border-[#334155] flex items-center justify-between">
                                                <div>
                                                    <h3 class="text-base font-bold text-white font-brand-display flex items-center gap-2">
                                                        <i data-lucide="compass" class="w-5 h-5 text-[#df747a]"></i>
                                                        <span>Select Interests & Topics</span>
                                                    </h3>
                                                    <p class="text-xs text-[#94A3B8] mt-0.5">Explore areas of interest, search, or add custom tags.</p>
                                                </div>
                                                <button type="button" @click="cancelModal()" class="text-slate-400 hover:text-white p-1 rounded-lg">
                                                    <i data-lucide="x" class="w-5 h-5"></i>
                                                </button>
                                            </div>

                                            <!-- Modal Body: Search & Category Navigation -->
                                            <div class="p-5 space-y-4 overflow-y-auto flex-1">
                                                
                                                <!-- Search Bar & Custom Tag Input -->
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                    <div class="relative">
                                                        <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                                                        <input type="text" x-model="search" placeholder="Search predefined topics..." 
                                                               class="input text-xs pl-9 w-full bg-[#0B1120] border-[#334155] focus:border-[#8F3033]">
                                                    </div>
                                                    <div class="flex gap-2">
                                                        <input type="text" x-model="customInput" @keydown.enter.prevent="addCustomInterest()" 
                                                               placeholder="Add custom topic (e.g. Bio-Informatics)" 
                                                               class="input text-xs w-full bg-[#0B1120] border-[#334155] focus:border-[#8F3033]">
                                                        <button type="button" @click="addCustomInterest()" 
                                                                class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-[#560E0F] hover:bg-[#8F3033] text-white border border-[#8F3033] transition shrink-0">
                                                            Add
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Review Tray: Selected Interests Chips in Modal -->
                                                <div class="p-3 rounded-xl bg-[#0B1120] border border-[#334155]">
                                                    <div class="flex items-center justify-between mb-2">
                                                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                                            Selected Interests (<span x-text="draftInterests.length"></span>)
                                                        </span>
                                                        <button type="button" x-show="draftInterests.length > 0" @click="draftInterests = []" 
                                                                class="text-[10px] text-red-400 hover:underline">
                                                            Clear All
                                                        </button>
                                                    </div>
                                                    <template x-if="draftInterests.length === 0">
                                                        <p class="text-xs text-slate-500 italic">No interests selected in this session.</p>
                                                    </template>
                                                    <div class="flex flex-wrap gap-1.5 max-h-24 overflow-y-auto">
                                                        <template x-for="interest in draftInterests" :key="'draft-int-' + interest">
                                                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-xs font-medium bg-[#560E0F]/50 border border-[#8F3033] text-white">
                                                                <span x-text="interest"></span>
                                                                <button type="button" @click="toggleInterest(interest)" class="text-slate-300 hover:text-white">
                                                                    <i data-lucide="x" class="w-3 h-3"></i>
                                                                </button>
                                                            </span>
                                                        </template>
                                                    </div>
                                                </div>

                                                <!-- Category Filter Pills -->
                                                <div class="flex gap-1.5 overflow-x-auto pb-1.5 scrollbar-thin">
                                                    <button type="button" @click="activeCategory = 'All'" 
                                                            :class="activeCategory === 'All' ? 'bg-[#560E0F] text-white border-[#8F3033]' : 'bg-[#0B1120] text-slate-400 border-[#334155] hover:text-slate-200'"
                                                            class="px-2.5 py-1 rounded-lg text-xs font-medium border transition whitespace-nowrap">
                                                        All Topics
                                                    </button>
                                                    <template x-for="cat in categoryNames" :key="cat">
                                                        <button type="button" @click="activeCategory = cat" 
                                                                :class="activeCategory === cat ? 'bg-[#560E0F] text-white border-[#8F3033]' : 'bg-[#0B1120] text-slate-400 border-[#334155] hover:text-slate-200'"
                                                                class="px-2.5 py-1 rounded-lg text-xs font-medium border transition whitespace-nowrap"
                                                                x-text="cat">
                                                        </button>
                                                    </template>
                                                </div>

                                                <!-- Interests Options Grid -->
                                                <div class="space-y-4">
                                                    <template x-for="(interestList, catName) in filteredGroupedInterests" :key="catName">
                                                        <div x-show="interestList.length > 0" class="space-y-1.5">
                                                            <h4 class="text-[11px] font-bold uppercase tracking-wider text-slate-400" x-text="catName"></h4>
                                                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                                                <template x-for="item in interestList" :key="item">
                                                                    <button type="button" @click="toggleInterest(item)"
                                                                            :class="isInterestSelected(item) ? 'bg-[#560E0F]/40 border-[#df747a] text-white font-semibold' : 'bg-[#0B1120] border-[#334155] text-slate-300 hover:border-slate-500'"
                                                                            class="p-2 rounded-xl border text-left text-xs flex items-center justify-between transition group">
                                                                        <span class="truncate" x-text="item"></span>
                                                                        <span x-show="isInterestSelected(item)" class="text-[#df747a]">
                                                                            <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                                                        </span>
                                                                    </button>
                                                                </template>
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>

                                            </div>

                                            <!-- Modal Footer Actions -->
                                            <div class="p-4 border-t border-[#334155] bg-[#0B1120] flex items-center justify-between">
                                                <span class="text-xs text-slate-400">
                                                    <span x-text="draftInterests.length"></span> interests selected
                                                </span>
                                                <div class="flex items-center gap-2">
                                                    <button type="button" @click="cancelModal()" class="btn-secondary text-xs py-1.5 px-4">
                                                        Cancel
                                                    </button>
                                                    <button type="button" @click="applyChanges()" class="btn-primary text-xs py-1.5 px-4 bg-[#560E0F] hover:bg-[#8F3033] border-[#8F3033]">
                                                        Apply Interests
                                                    </button>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Profile Visibility Settings Section -->
                            <div class="space-y-3 pt-4 border-t border-[#334155]/60" x-data="{ currentSetting: '{{ $currentVisibility }}' }">
                                <div>
                                    <label class="label text-xs font-semibold text-white flex items-center gap-1.5 mb-0.5">
                                        <i data-lucide="shield-check" class="w-4 h-4 text-[#df747a]"></i>
                                        <span>Profile Visibility</span>
                                    </label>
                                    <p class="text-[11px] text-[#94A3B8]">Control who can discover and view your profile information within ComSoc Hub.</p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                    
                                    <!-- Public Option -->
                                    <label class="p-3.5 rounded-xl border cursor-pointer transition flex flex-col justify-between"
                                           :class="currentSetting === 'public' ? 'bg-[#560E0F]/20 border-[#df747a] ring-1 ring-[#df747a]/50' : 'bg-[#0B1120] border-[#334155] hover:border-slate-500'">
                                        <div class="space-y-1.5">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-2">
                                                    <i data-lucide="globe" class="w-4 h-4" :class="currentSetting === 'public' ? 'text-[#df747a]' : 'text-slate-400'"></i>
                                                    <span class="text-xs font-bold text-white">Public</span>
                                                </div>
                                                <input type="radio" name="visibility" value="public" x-model="currentSetting" 
                                                       class="w-3.5 h-3.5 text-[#560E0F] focus:ring-[#8F3033] bg-[#0B1120] border-[#334155]">
                                            </div>
                                            <p class="text-[11px] text-slate-400 leading-relaxed">
                                                Visible to visitors and active members in the public directory. Student IDs always remain private.
                                            </p>
                                        </div>
                                    </label>

                                    <!-- Members Only Option -->
                                    <label class="p-3.5 rounded-xl border cursor-pointer transition flex flex-col justify-between"
                                           :class="currentSetting === 'members_only' ? 'bg-[#560E0F]/20 border-[#df747a] ring-1 ring-[#df747a]/50' : 'bg-[#0B1120] border-[#334155] hover:border-slate-500'">
                                        <div class="space-y-1.5">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-2">
                                                    <i data-lucide="users" class="w-4 h-4" :class="currentSetting === 'members_only' ? 'text-[#df747a]' : 'text-slate-400'"></i>
                                                    <span class="text-xs font-bold text-white">Members Only</span>
                                                </div>
                                                <input type="radio" name="visibility" value="members_only" x-model="currentSetting" 
                                                       class="w-3.5 h-3.5 text-[#560E0F] focus:ring-[#8F3033] bg-[#0B1120] border-[#334155]">
                                            </div>
                                            <p class="text-[11px] text-slate-400 leading-relaxed">
                                                Visible only to authenticated and enrolled Computing Society members and officers.
                                            </p>
                                        </div>
                                    </label>

                                    <!-- Private Option -->
                                    <label class="p-3.5 rounded-xl border cursor-pointer transition flex flex-col justify-between"
                                           :class="currentSetting === 'private' ? 'bg-[#560E0F]/20 border-[#df747a] ring-1 ring-[#df747a]/50' : 'bg-[#0B1120] border-[#334155] hover:border-slate-500'">
                                        <div class="space-y-1.5">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-2">
                                                    <i data-lucide="lock" class="w-4 h-4" :class="currentSetting === 'private' ? 'text-[#df747a]' : 'text-slate-400'"></i>
                                                    <span class="text-xs font-bold text-white">Private</span>
                                                </div>
                                                <input type="radio" name="visibility" value="private" x-model="currentSetting" 
                                                       class="w-3.5 h-3.5 text-[#560E0F] focus:ring-[#8F3033] bg-[#0B1120] border-[#334155]">
                                            </div>
                                            <p class="text-[11px] text-slate-400 leading-relaxed">
                                                Hidden from directories and public searches. Only you and authorized Super Admins can access.
                                            </p>
                                        </div>
                                    </label>

                                </div>
                                <x-input-error class="mt-1" :messages="$errors->get('visibility')" />
                            </div>

                            <div class="pt-4 flex justify-end">
                                <button type="submit" class="btn-primary text-xs py-2 px-5 bg-[#560E0F] hover:bg-[#8F3033] border-[#8F3033]">
                                    Save Profile Changes
                                </button>
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

                    <div class="card bg-[#172033] border border-[#334155] shadow-xl p-6">
                        <div class="flex items-center justify-between gap-4 mb-4 border-b border-[#334155] pb-3">
                            <div class="flex items-center gap-2">
                                <i data-lucide="shield-check" class="w-4 h-4 text-[#df747a]"></i>
                                <h3 class="text-sm font-bold text-white font-brand-display uppercase tracking-wider">Device & Session Security</h3>
                            </div>
                            <a href="{{ route('profile.sessions') }}" class="btn-secondary text-xs flex items-center gap-1.5 py-1 px-3">
                                <span>Manage Sessions</span>
                                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                            </a>
                        </div>
                        <p class="text-xs text-[#94A3B8] leading-relaxed">
                            Monitor recognized browsers, view your active logins across devices, and revoke unrecognized access sessions.
                        </p>
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

    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('skillsSelector', (config) => ({
                savedSkills: config.initialSkills || [],
                draftSkills: [],
                categories: config.categories || {},
                categoryNames: Object.keys(config.categories || {}),
                activeCategory: 'All',
                search: '',
                customInput: '',
                isOpen: false,
                maxPreview: 6,
                showAll: false,

                get visibleSkills() {
                    return this.showAll ? this.savedSkills : this.savedSkills.slice(0, this.maxPreview);
                },

                get filteredGroupedSkills() {
                    const searchLower = this.search.trim().toLowerCase();
                    const result = {};

                    for (const [catName, items] of Object.entries(this.categories)) {
                        if (this.activeCategory !== 'All' && this.activeCategory !== catName) {
                            continue;
                        }

                        const filteredItems = items.filter(item => {
                            if (!searchLower) return true;
                            return item.toLowerCase().includes(searchLower);
                        });

                        if (filteredItems.length > 0) {
                            result[catName] = filteredItems;
                        }
                    }

                    return result;
                },

                openModal() {
                    this.draftSkills = [...this.savedSkills];
                    this.search = '';
                    this.customInput = '';
                    this.activeCategory = 'All';
                    this.isOpen = true;
                    this.$nextTick(() => {
                        if (window.lucide) window.lucide.createIcons();
                    });
                },

                cancelModal() {
                    this.isOpen = false;
                    this.draftSkills = [];
                },

                applyChanges() {
                    this.savedSkills = [...this.draftSkills];
                    this.isOpen = false;
                    this.$nextTick(() => {
                        if (window.lucide) window.lucide.createIcons();
                    });
                },

                isSkillSelected(skill) {
                    const skillLower = skill.trim().toLowerCase();
                    return this.draftSkills.some(s => s.trim().toLowerCase() === skillLower);
                },

                toggleSkill(skill) {
                    const trimmed = skill.trim();
                    const skillLower = trimmed.toLowerCase();
                    const index = this.draftSkills.findIndex(s => s.trim().toLowerCase() === skillLower);
                    if (index >= 0) {
                        this.draftSkills.splice(index, 1);
                    } else {
                        this.draftSkills.push(trimmed);
                    }
                    this.$nextTick(() => {
                        if (window.lucide) window.lucide.createIcons();
                    });
                },

                addCustomSkill() {
                    const trimmed = this.customInput.trim();
                    if (!trimmed) return;
                    if (!this.isSkillSelected(trimmed)) {
                        this.draftSkills.push(trimmed);
                    }
                    this.customInput = '';
                    this.$nextTick(() => {
                        if (window.lucide) window.lucide.createIcons();
                    });
                },

                removeSavedSkill(skill) {
                    const skillLower = skill.trim().toLowerCase();
                    this.savedSkills = this.savedSkills.filter(s => s.trim().toLowerCase() !== skillLower);
                    this.$nextTick(() => {
                        if (window.lucide) window.lucide.createIcons();
                    });
                }
            }));

            Alpine.data('interestsSelector', (config) => ({
                savedInterests: config.initialInterests || [],
                draftInterests: [],
                categories: config.categories || {},
                categoryNames: Object.keys(config.categories || {}),
                activeCategory: 'All',
                search: '',
                customInput: '',
                isOpen: false,
                maxPreview: 6,
                showAll: false,

                get visibleInterests() {
                    return this.showAll ? this.savedInterests : this.savedInterests.slice(0, this.maxPreview);
                },

                get filteredGroupedInterests() {
                    const searchLower = this.search.trim().toLowerCase();
                    const result = {};

                    for (const [catName, items] of Object.entries(this.categories)) {
                        if (this.activeCategory !== 'All' && this.activeCategory !== catName) {
                            continue;
                        }

                        const filteredItems = items.filter(item => {
                            if (!searchLower) return true;
                            return item.toLowerCase().includes(searchLower);
                        });

                        if (filteredItems.length > 0) {
                            result[catName] = filteredItems;
                        }
                    }

                    return result;
                },

                openModal() {
                    this.draftInterests = [...this.savedInterests];
                    this.search = '';
                    this.customInput = '';
                    this.activeCategory = 'All';
                    this.isOpen = true;
                    this.$nextTick(() => {
                        if (window.lucide) window.lucide.createIcons();
                    });
                },

                cancelModal() {
                    this.isOpen = false;
                    this.draftInterests = [];
                },

                applyChanges() {
                    this.savedInterests = [...this.draftInterests];
                    this.isOpen = false;
                    this.$nextTick(() => {
                        if (window.lucide) window.lucide.createIcons();
                    });
                },

                isInterestSelected(interest) {
                    const intLower = interest.trim().toLowerCase();
                    return this.draftInterests.some(i => i.trim().toLowerCase() === intLower);
                },

                toggleInterest(interest) {
                    const trimmed = interest.trim();
                    const intLower = trimmed.toLowerCase();
                    const index = this.draftInterests.findIndex(i => i.trim().toLowerCase() === intLower);
                    if (index >= 0) {
                        this.draftInterests.splice(index, 1);
                    } else {
                        this.draftInterests.push(trimmed);
                    }
                    this.$nextTick(() => {
                        if (window.lucide) window.lucide.createIcons();
                    });
                },

                addCustomInterest() {
                    const trimmed = this.customInput.trim();
                    if (!trimmed) return;
                    if (!this.isInterestSelected(trimmed)) {
                        this.draftInterests.push(trimmed);
                    }
                    this.customInput = '';
                    this.$nextTick(() => {
                        if (window.lucide) window.lucide.createIcons();
                    });
                },

                removeSavedInterest(interest) {
                    const intLower = interest.trim().toLowerCase();
                    this.savedInterests = this.savedInterests.filter(i => i.trim().toLowerCase() !== intLower);
                    this.$nextTick(() => {
                        if (window.lucide) window.lucide.createIcons();
                    });
                }
            }));
        });
    </script>
    @endpush
</x-app-layout>
