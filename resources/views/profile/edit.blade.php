<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-white font-brand-display">Member Profile</h1>
                <p class="text-xs text-slate-400 mt-0.5">Manage your identity, technical stack, progression, and account security</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('dashboard') }}" class="btn-secondary text-xs flex items-center gap-1.5">
                    <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                    <span>Dashboard</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8 bg-[#0B1120]" 
         x-data="{
             activeTab: (window.location.hash ? window.location.hash.substring(1) : 'profile'),
             setTab(tab) {
                 this.activeTab = tab;
                 window.location.hash = tab;
                 this.$nextTick(() => {
                     if (window.lucide) window.lucide.createIcons();
                 });
             }
         }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Compact Identity Hero Banner -->
            <div id="academic-info" class="card bg-gradient-to-r from-[#172033]/90 via-[#111827] to-[#0B1120] border border-slate-800/80 shadow-2xl p-5 sm:p-6 rounded-3xl relative overflow-hidden">
                <!-- Ambient background glow -->
                <div class="absolute -top-12 -right-12 w-48 h-48 bg-[#8F3033]/15 rounded-full blur-3xl pointer-events-none"></div>

                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-5 relative z-10">
                    <div class="flex items-center gap-4 sm:gap-5">
                        <div class="relative group cursor-pointer" @click="setTab('profile'); $nextTick(() => { document.getElementById('avatar-section')?.scrollIntoView({ behavior: 'smooth' }) })">
                            <x-user-avatar :user="$user" size="xl" class="shadow-2xl border-2 border-[#8F3033]/70 group-hover:border-[#df747a] transition-all" />
                            <div class="absolute -bottom-1 -right-1 w-6 h-6 rounded-full bg-[#560E0F] border border-[#df747a] flex items-center justify-center text-white text-[10px] shadow group-hover:scale-110 transition">
                                <i data-lucide="edit-3" class="w-3 h-3 text-white"></i>
                            </div>
                        </div>
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-xl sm:text-2xl font-extrabold text-white font-brand-display tracking-tight">{{ $user->name }}</h2>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-rose-500/15 text-rose-300 border border-rose-500/30">
                                    {{ strtoupper($user->role) }}
                                </span>
                            </div>
                            <p class="text-xs sm:text-sm text-slate-300 mt-1 font-medium">
                                {{ $student ? "{$student->program} • {$student->year_level} (Block {$student->block})" : 'Executive / Administrative Officer Account' }}
                            </p>
                            <div class="flex items-center gap-3 text-xs text-slate-400 font-mono mt-1">
                                <span>{{ $user->member_id ? "ID: {$user->member_id}" : (auth()->user()?->isSuperAdmin() ? ($student ? "Student ID: {$student->student_number}" : "Account: {$user->email}") : "Account: {$user->email}") }}</span>
                                @if($user->username)
                                <span class="text-slate-500">•</span>
                                <span class="text-rose-400/90 font-semibold">{{ '@' . $user->username }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Metric Bento Badges in Header -->
                    <div class="flex flex-wrap items-center gap-2 sm:gap-3 bg-[#0B1120]/90 border border-slate-800/80 p-2.5 sm:p-3.5 rounded-2xl shadow-inner">
                        <div class="px-2 flex items-center">
                            <x-level-badge :level="$currentLevel" size="md" :showLevelNumber="true" />
                        </div>
                        <div class="text-left px-3 border-l border-slate-800">
                            <span class="block text-[10px] uppercase font-bold tracking-wider text-slate-400">Rank & Level</span>
                            <span class="text-sm font-bold text-white font-brand-display block truncate max-w-[140px]">{{ $currentLevel->title }}</span>
                            <span class="text-[10px] font-semibold text-[#df747a]">Lvl {{ $currentLevel->level }} ({{ $currentLevel->tier }})</span>
                        </div>
                        <div class="text-center px-3 border-l border-slate-800">
                            <span class="block text-[10px] uppercase font-bold tracking-wider text-slate-400">Spendable</span>
                            <span class="text-base font-extrabold text-amber-400 font-mono-tabular">{{ number_format($seasonPoints) }}</span>
                            <span class="text-[10px] text-slate-500 block font-sans">pts</span>
                        </div>
                        <div class="text-center px-3 border-l border-slate-800">
                            <span class="block text-[10px] uppercase font-bold tracking-wider text-slate-400">Lifetime</span>
                            <span class="text-base font-extrabold text-[#df747a] font-mono-tabular">{{ number_format($lifetimeXp) }}</span>
                            <span class="text-[10px] text-slate-500 block font-sans">EXP</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabbed Navigation Bar -->
            <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
                <nav class="flex items-center gap-2 sm:gap-3 overflow-x-auto scrollbar-thin py-1" aria-label="Profile Sections">
                    <button type="button" 
                            @click="setTab('profile')"
                            :class="activeTab === 'profile' 
                                ? 'bg-gradient-to-r from-[#8F3033] to-[#6B2426] text-white shadow-lg shadow-[#8F3033]/25 border-[#df747a]/50' 
                                : 'bg-[#172033]/70 text-slate-400 hover:text-white hover:bg-[#172033] border-slate-800'"
                            class="px-4 py-2 rounded-xl text-xs font-semibold border transition-all flex items-center gap-2 shrink-0">
                        <i data-lucide="user" class="w-4 h-4"></i>
                        <span>Profile &amp; Identity</span>
                    </button>

                    <button type="button" 
                            @click="setTab('progression')"
                            :class="activeTab === 'progression' 
                                ? 'bg-gradient-to-r from-[#8F3033] to-[#6B2426] text-white shadow-lg shadow-[#8F3033]/25 border-[#df747a]/50' 
                                : 'bg-[#172033]/70 text-slate-400 hover:text-white hover:bg-[#172033] border-slate-800'"
                            class="px-4 py-2 rounded-xl text-xs font-semibold border transition-all flex items-center gap-2 shrink-0">
                        <i data-lucide="award" class="w-4 h-4"></i>
                        <span>Progression &amp; Badges</span>
                    </button>

                    <button type="button" 
                            @click="setTab('security')"
                            :class="activeTab === 'security' 
                                ? 'bg-gradient-to-r from-[#8F3033] to-[#6B2426] text-white shadow-lg shadow-[#8F3033]/25 border-[#df747a]/50' 
                                : 'bg-[#172033]/70 text-slate-400 hover:text-white hover:bg-[#172033] border-slate-800'"
                            class="px-4 py-2 rounded-xl text-xs font-semibold border transition-all flex items-center gap-2 shrink-0">
                        <i data-lucide="shield-check" class="w-4 h-4"></i>
                        <span>Account &amp; Security</span>
                    </button>
                </nav>

                <!-- Profile Completion Quick Indicator -->
                <div class="hidden md:flex items-center gap-2 text-xs">
                    @php
                        $compPercent = (int) ($profileCompletion['percentage'] ?? 0);
                    @endphp
                    <span class="text-slate-400">Completion:</span>
                    <div class="w-24 h-2 rounded-full bg-slate-800 overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-[#8F3033] to-emerald-500 rounded-full" style="width: {{ $compPercent }}%;"></div>
                    </div>
                    <span class="font-mono font-bold {{ $compPercent === 100 ? 'text-emerald-400' : 'text-[#df747a]' }}">{{ $compPercent }}%</span>
                </div>
            </div>

            <!-- TAB 1: PROFILE & IDENTITY -->
            <div x-show="activeTab === 'profile'" x-cloak class="space-y-6">
                
                @if (session('status') === 'profile-updated')
                    <div class="alert-success p-3.5 bg-emerald-950/40 border border-emerald-800 text-emerald-300 rounded-2xl text-xs flex items-center gap-2 shadow-lg">
                        <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-400 shrink-0"></i>
                        <span>Profile details and settings saved successfully.</span>
                    </div>
                @endif

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    <!-- Left 2 Cols: Main Information Form -->
                    <div class="lg:col-span-2 space-y-6">
                        <div class="card bg-[#172033]/90 border border-slate-800/80 shadow-2xl p-6 sm:p-7 rounded-2xl backdrop-blur-xl">
                            
                            <form method="POST" action="{{ route('profile.update') }}" class="space-y-6"
                                  x-data="{
                                      selectedSeed: '{{ addslashes(old('avatar_seed', $user->avatar_seed ?: 'Felix')) }}',
                                      style: 'adventurer',
                                      showAvatarModal: false,
                                      get previewUrl() {
                                          return 'https://api.dicebear.com/9.x/adventurer/svg?seed=' + encodeURIComponent(this.selectedSeed);
                                      }
                                  }">
                                @csrf
                                @method('patch')

                                <!-- Adventurer Avatar Compact Card -->
                                <div id="avatar-section" class="p-4 sm:p-5 rounded-2xl bg-[#0B1120]/80 border border-slate-800 shadow-inner flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                    <div class="flex items-center gap-3.5">
                                        <div class="w-14 h-14 rounded-2xl bg-[#172033] border-2 border-[#8F3033] p-1 shadow-lg shrink-0 flex items-center justify-center overflow-hidden">
                                            <img :src="previewUrl" :alt="selectedSeed" class="w-full h-full object-cover rounded-xl" />
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <h4 class="text-xs font-bold text-white uppercase tracking-wider font-brand-display">Adventurer Avatar</h4>
                                                <span class="text-[10px] font-mono text-[#df747a] bg-[#8F3033]/20 px-2 py-0.5 rounded-md border border-[#8F3033]/40" x-text="selectedSeed"></span>
                                            </div>
                                            <p class="text-[11px] text-slate-400 mt-0.5">Select a character persona from the official collection</p>
                                        </div>
                                    </div>

                                    <button type="button" @click="showAvatarModal = true" 
                                            class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-medium bg-[#172033] text-slate-200 border border-slate-700/80 hover:border-[#df747a] hover:text-white transition shadow-sm">
                                        <i data-lucide="sparkles" class="w-3.5 h-3.5 text-[#df747a]"></i>
                                        <span>Change Avatar</span>
                                    </button>

                                    <input type="hidden" name="avatar_style" :value="style">
                                    <input type="hidden" name="avatar_seed" :value="selectedSeed">

                                    <!-- Avatar Selection Modal -->
                                    <div x-show="showAvatarModal" x-cloak
                                         class="fixed inset-0 z-50 overflow-y-auto"
                                         style="background-color: rgba(3, 7, 18, 0.8); backdrop-filter: blur(6px);"
                                         @keydown.escape.window="showAvatarModal = false">
                                        <div class="min-h-full flex items-center justify-center p-4">
                                            <div class="relative w-full max-w-2xl rounded-2xl bg-[#0F172A] border border-slate-700/80 shadow-2xl p-6 space-y-4"
                                                 @click.away="showAvatarModal = false">
                                                
                                                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                                                    <div>
                                                        <h3 class="text-sm font-bold text-white font-brand-display uppercase tracking-wider">Select Adventurer Character</h3>
                                                        <p class="text-xs text-slate-400 mt-0.5">Click any avatar below to select as your active persona</p>
                                                    </div>
                                                    <button type="button" @click="showAvatarModal = false" class="text-slate-400 hover:text-white p-1 rounded-lg">
                                                        <i data-lucide="x" class="w-5 h-5"></i>
                                                    </button>
                                                </div>

                                                <div class="max-h-[380px] overflow-y-auto pr-1 scrollbar-thin">
                                                    <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-2.5" role="radiogroup" aria-label="Adventurer Character Avatar options">
                                                        @foreach($avatarPresets as $preset)
                                                        <button type="button"
                                                                role="radio"
                                                                :aria-checked="selectedSeed === '{{ $preset['seed'] }}'"
                                                                aria-label="{{ $preset['name'] }}"
                                                                @click="selectedSeed = '{{ $preset['seed'] }}'; showAvatarModal = false"
                                                                class="group relative flex flex-col items-center justify-center p-2 rounded-xl border transition-all duration-150 cursor-pointer min-h-[90px]"
                                                                :class="selectedSeed === '{{ $preset['seed'] }}'
                                                                    ? 'bg-[#560E0F]/30 border-[#df747a] ring-1 ring-[#df747a]/60 shadow-md'
                                                                    : 'bg-[#172033] border-slate-700/60 hover:border-slate-500 hover:bg-[#1E293B]'">
                                                            <div class="w-11 h-11 rounded-xl overflow-hidden bg-[#0B1120] border border-slate-700 p-0.5 flex items-center justify-center shrink-0">
                                                                <img src="{{ $preset['url'] }}" alt="" aria-hidden="true" class="w-full h-full object-cover transition-transform group-hover:scale-105" loading="lazy" />
                                                            </div>
                                                            <span class="text-[10px] font-semibold mt-1.5 truncate max-w-full text-center"
                                                                  :class="selectedSeed === '{{ $preset['seed'] }}' ? 'text-white font-bold' : 'text-slate-400 group-hover:text-slate-200'">
                                                                {{ $preset['name'] }}
                                                            </span>
                                                            <div x-show="selectedSeed === '{{ $preset['seed'] }}'"
                                                                 class="absolute top-1 right-1 w-3.5 h-3.5 rounded-full bg-[#560E0F] border border-[#df747a] text-white flex items-center justify-center">
                                                                <i data-lucide="check" class="w-2 h-2 text-white stroke-[3]"></i>
                                                            </div>
                                                        </button>
                                                        @endforeach
                                                    </div>
                                                </div>

                                                <div class="pt-2 border-t border-slate-800 flex justify-end">
                                                    <button type="button" @click="showAvatarModal = false" class="btn-primary text-xs py-1.5 px-4 bg-[#8F3033] hover:bg-[#a0363a]">
                                                        Done
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Personal Identity Inputs -->
                                <div id="personal-info" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div>
                                        <label for="username" class="label text-xs text-slate-300">
                                            Username <span class="text-[#df747a]">*</span>
                                        </label>
                                        <div class="relative mt-1">
                                            <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-500 font-bold text-xs">@</span>
                                            <input type="text"
                                                   name="username"
                                                   id="username"
                                                   value="{{ old('username', $user->username) }}"
                                                   placeholder="username"
                                                   maxlength="20"
                                                   class="input text-xs pl-7"
                                                   required>
                                        </div>
                                        <p class="text-[10px] text-slate-400 mt-1">Letters, numbers, underscores.</p>
                                        <x-input-error class="mt-1" :messages="$errors->get('username')" />
                                    </div>

                                    <div>
                                        <label for="name" class="label text-xs text-slate-300">Official Name <span class="text-[#df747a]">*</span></label>
                                        <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" class="input text-xs mt-1" placeholder="Enter your full name" required>
                                        <x-input-error class="mt-1" :messages="$errors->get('name')" />
                                    </div>

                                    <div id="contact-info">
                                        <label for="email" class="label text-xs text-slate-300">Institutional Email <span class="text-[#df747a]">*</span></label>
                                        <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" class="input text-xs mt-1" placeholder="student@sorsu.edu.ph" required>
                                        <x-input-error class="mt-1" :messages="$errors->get('email')" />
                                    </div>
                                </div>

                                <!-- Bio -->
                                <div id="about-me">
                                    <label for="bio" class="label text-xs text-slate-300">Bio &amp; Summary</label>
                                    <textarea name="bio" id="bio" rows="2" class="input text-xs mt-1" placeholder="Briefly describe your focus, interests, and aspirations...">{{ old('bio', $profile->bio) }}</textarea>
                                    <x-input-error class="mt-1" :messages="$errors->get('bio')" />
                                </div>

                                <!-- Portfolio Links -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label for="github_url" class="label text-xs text-slate-300 flex items-center gap-1.5">
                                            <i data-lucide="github" class="w-3.5 h-3.5 text-slate-400"></i>
                                            <span>GitHub URL</span>
                                        </label>
                                        <input type="url" name="github_url" id="github_url" value="{{ old('github_url', $profile->github_url) }}" placeholder="https://github.com/..." class="input text-xs mt-1">
                                        <x-input-error class="mt-1" :messages="$errors->get('github_url')" />
                                    </div>

                                    <div>
                                        <label for="linkedin_url" class="label text-xs text-slate-300 flex items-center gap-1.5">
                                            <i data-lucide="linkedin" class="w-3.5 h-3.5 text-[#0A66C2]"></i>
                                            <span>LinkedIn URL</span>
                                        </label>
                                        <input type="url" name="linkedin_url" id="linkedin_url" value="{{ old('linkedin_url', $profile->linkedin_url) }}" placeholder="https://linkedin.com/in/..." class="input text-xs mt-1">
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
                                })" class="space-y-3 pt-3 border-t border-slate-800">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <label class="label text-xs font-semibold text-white flex items-center gap-1.5 mb-0.5">
                                                <i data-lucide="code-2" class="w-4 h-4 text-[#df747a]"></i>
                                                <span>Technical Stack &amp; Skills</span>
                                            </label>
                                            <p class="text-[11px] text-slate-400">Languages, frameworks, databases, and competencies.</p>
                                        </div>
                                        <button type="button" @click="openModal()" 
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-medium bg-[#0B1120] text-slate-200 border border-slate-700 hover:border-[#df747a] hover:text-white transition">
                                            <i data-lucide="plus" class="w-3.5 h-3.5 text-[#df747a]"></i>
                                            <span x-text="savedSkills.length > 0 ? 'Edit Stack' : 'Add Stack'"></span>
                                        </button>
                                    </div>

                                    <!-- Preview Chips on Main Profile Card -->
                                    <div>
                                        <template x-if="savedSkills.length === 0">
                                            <div class="p-3 rounded-xl border border-dashed border-slate-800 bg-[#0B1120]/50 text-center">
                                                <p class="text-xs text-slate-400">No skills added yet.</p>
                                            </div>
                                        </template>

                                        <template x-if="savedSkills.length > 0">
                                            <div class="space-y-2">
                                                <div class="flex flex-wrap gap-1.5 items-center">
                                                    <template x-for="(skill, index) in visibleSkills" :key="skill">
                                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-[#0B1120] border border-slate-700/80 text-slate-200">
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
                                         style="background-color: rgba(3, 7, 18, 0.8); backdrop-filter: blur(6px);"
                                         @keydown.escape.window="cancelModal()">
                                        <div class="min-h-full flex items-center justify-center p-4">
                                            <div class="relative w-full max-w-3xl rounded-2xl bg-[#0F172A] border border-slate-700/80 shadow-2xl flex flex-col max-h-[88vh] overflow-hidden"
                                                 @click.away="cancelModal()">
                                                
                                                <div class="p-5 border-b border-slate-800 flex items-center justify-between">
                                                    <div>
                                                        <h3 class="text-base font-bold text-white font-brand-display flex items-center gap-2">
                                                            <i data-lucide="code-2" class="w-5 h-5 text-[#df747a]"></i>
                                                            <span>Select Technical Stack &amp; Skills</span>
                                                        </h3>
                                                        <p class="text-xs text-slate-400 mt-0.5">Explore categories, search, or add custom technical skills.</p>
                                                    </div>
                                                    <button type="button" @click="cancelModal()" class="text-slate-400 hover:text-white p-1 rounded-lg">
                                                        <i data-lucide="x" class="w-5 h-5"></i>
                                                    </button>
                                                </div>

                                                <div class="p-5 space-y-4 overflow-y-auto flex-1">
                                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                        <div class="relative">
                                                            <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                                                            <input type="text" x-model="search" placeholder="Enter search term for skills" 
                                                                   class="input text-xs pl-9 w-full bg-[#0B1120] border-slate-700 focus:border-[#df747a]">
                                                        </div>
                                                        <div class="flex gap-2">
                                                            <input type="text" x-model="customInput" @keydown.enter.prevent="addCustomSkill()" 
                                                                   placeholder="Enter custom skill name" 
                                                                   class="input text-xs w-full bg-[#0B1120] border-slate-700 focus:border-[#df747a]">
                                                            <button type="button" @click="addCustomSkill()" 
                                                                    class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-[#560E0F] hover:bg-[#8F3033] text-white border border-[#8F3033] transition shrink-0">
                                                                Add
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <div class="p-3 rounded-xl bg-[#0B1120] border border-slate-800">
                                                        <div class="flex items-center justify-between mb-2">
                                                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                                                Selected Stack (<span x-text="draftSkills.length"></span>)
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

                                                    <div class="flex gap-1.5 overflow-x-auto pb-1.5 scrollbar-thin">
                                                        <button type="button" @click="activeCategory = 'All'" 
                                                                :class="activeCategory === 'All' ? 'bg-[#560E0F] text-white border-[#8F3033]' : 'bg-[#0B1120] text-slate-400 border-slate-700 hover:text-slate-200'"
                                                                class="px-2.5 py-1 rounded-lg text-xs font-medium border transition whitespace-nowrap">
                                                            All Categories
                                                        </button>
                                                        <template x-for="cat in categoryNames" :key="cat">
                                                            <button type="button" @click="activeCategory = cat" 
                                                                    :class="activeCategory === cat ? 'bg-[#560E0F] text-white border-[#8F3033]' : 'bg-[#0B1120] text-slate-400 border-slate-700 hover:text-slate-200'"
                                                                    class="px-2.5 py-1 rounded-lg text-xs font-medium border transition whitespace-nowrap"
                                                                    x-text="cat">
                                                            </button>
                                                        </template>
                                                    </div>

                                                    <div class="space-y-4">
                                                        <template x-for="(skillsList, catName) in filteredGroupedSkills" :key="catName">
                                                            <div x-show="skillsList.length > 0" class="space-y-1.5">
                                                                <h4 class="text-[11px] font-bold uppercase tracking-wider text-slate-400" x-text="catName"></h4>
                                                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                                                    <template x-for="item in skillsList" :key="item">
                                                                        <button type="button" @click="toggleSkill(item)"
                                                                                :class="isSkillSelected(item) ? 'bg-[#560E0F]/40 border-[#df747a] text-white font-semibold' : 'bg-[#0B1120] border-slate-700 text-slate-300 hover:border-slate-500'"
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

                                                <div class="p-4 border-t border-slate-800 bg-[#0B1120] flex items-center justify-between">
                                                    <span class="text-xs text-slate-400">
                                                        <span x-text="draftSkills.length"></span> skills selected
                                                    </span>
                                                    <div class="flex items-center gap-2">
                                                        <button type="button" @click="cancelModal()" class="btn-secondary text-xs py-1.5 px-4">
                                                            Cancel
                                                        </button>
                                                        <button type="button" @click="applyChanges()" class="btn-primary text-xs py-1.5 px-4 bg-[#8F3033] hover:bg-[#a0363a]">
                                                            Apply Stack
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
                                })" class="space-y-3 pt-3 border-t border-slate-800">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <label class="label text-xs font-semibold text-white flex items-center gap-1.5 mb-0.5">
                                                <i data-lucide="compass" class="w-4 h-4 text-[#df747a]"></i>
                                                <span>Interests &amp; Topics</span>
                                            </label>
                                            <p class="text-[11px] text-slate-400">Professional focus, hackathons, and open source.</p>
                                        </div>
                                        <button type="button" @click="openModal()" 
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-medium bg-[#0B1120] text-slate-200 border border-slate-700 hover:border-[#df747a] hover:text-white transition">
                                            <i data-lucide="plus" class="w-3.5 h-3.5 text-[#df747a]"></i>
                                            <span x-text="savedInterests.length > 0 ? 'Edit Interests' : 'Add Interests'"></span>
                                        </button>
                                    </div>

                                    <div>
                                        <template x-if="savedInterests.length === 0">
                                            <div class="p-3 rounded-xl border border-dashed border-slate-800 bg-[#0B1120]/50 text-center">
                                                <p class="text-xs text-slate-400">No interests selected yet.</p>
                                            </div>
                                        </template>

                                        <template x-if="savedInterests.length > 0">
                                            <div class="space-y-2">
                                                <div class="flex flex-wrap gap-1.5 items-center">
                                                    <template x-for="(interest, index) in visibleInterests" :key="interest">
                                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-[#0B1120] border border-slate-700/80 text-slate-200">
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

                                    <input type="hidden" name="interests" :value="savedInterests.join(', ')">
                                    <x-input-error class="mt-1" :messages="$errors->get('interests')" />

                                    <!-- Alpine Modal: Interests Selector -->
                                    <div x-show="isOpen" 
                                         x-cloak
                                         class="fixed inset-0 z-50 overflow-y-auto"
                                         style="background-color: rgba(3, 7, 18, 0.8); backdrop-filter: blur(6px);"
                                         @keydown.escape.window="cancelModal()">
                                        <div class="min-h-full flex items-center justify-center p-4">
                                            <div class="relative w-full max-w-3xl rounded-2xl bg-[#0F172A] border border-slate-700/80 shadow-2xl flex flex-col max-h-[88vh] overflow-hidden"
                                                 @click.away="cancelModal()">
                                                
                                                <div class="p-5 border-b border-slate-800 flex items-center justify-between">
                                                    <div>
                                                        <h3 class="text-base font-bold text-white font-brand-display flex items-center gap-2">
                                                            <i data-lucide="compass" class="w-5 h-5 text-[#df747a]"></i>
                                                            <span>Select Interests &amp; Topics</span>
                                                        </h3>
                                                        <p class="text-xs text-slate-400 mt-0.5">Explore areas of interest, search, or add custom tags.</p>
                                                    </div>
                                                    <button type="button" @click="cancelModal()" class="text-slate-400 hover:text-white p-1 rounded-lg">
                                                        <i data-lucide="x" class="w-5 h-5"></i>
                                                    </button>
                                                </div>

                                                <div class="p-5 space-y-4 overflow-y-auto flex-1">
                                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                        <div class="relative">
                                                            <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                                                            <input type="text" x-model="search" placeholder="Enter search term for topics" 
                                                                   class="input text-xs pl-9 w-full bg-[#0B1120] border-slate-700 focus:border-[#df747a]">
                                                        </div>
                                                        <div class="flex gap-2">
                                                            <input type="text" x-model="customInput" @keydown.enter.prevent="addCustomInterest()" 
                                                                   placeholder="Enter custom topic name" 
                                                                   class="input text-xs w-full bg-[#0B1120] border-slate-700 focus:border-[#df747a]">
                                                            <button type="button" @click="addCustomInterest()" 
                                                                    class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-[#560E0F] hover:bg-[#8F3033] text-white border border-[#8F3033] transition shrink-0">
                                                                Add
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <div class="p-3 rounded-xl bg-[#0B1120] border border-slate-800">
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

                                                    <div class="flex gap-1.5 overflow-x-auto pb-1.5 scrollbar-thin">
                                                        <button type="button" @click="activeCategory = 'All'" 
                                                                :class="activeCategory === 'All' ? 'bg-[#560E0F] text-white border-[#8F3033]' : 'bg-[#0B1120] text-slate-400 border-slate-700 hover:text-slate-200'"
                                                                class="px-2.5 py-1 rounded-lg text-xs font-medium border transition whitespace-nowrap">
                                                            All Topics
                                                        </button>
                                                        <template x-for="cat in categoryNames" :key="cat">
                                                            <button type="button" @click="activeCategory = cat" 
                                                                    :class="activeCategory === cat ? 'bg-[#560E0F] text-white border-[#8F3033]' : 'bg-[#0B1120] text-slate-400 border-slate-700 hover:text-slate-200'"
                                                                    class="px-2.5 py-1 rounded-lg text-xs font-medium border transition whitespace-nowrap"
                                                                    x-text="cat">
                                                            </button>
                                                        </template>
                                                    </div>

                                                    <div class="space-y-4">
                                                        <template x-for="(interestList, catName) in filteredGroupedInterests" :key="catName">
                                                            <div x-show="interestList.length > 0" class="space-y-1.5">
                                                                <h4 class="text-[11px] font-bold uppercase tracking-wider text-slate-400" x-text="catName"></h4>
                                                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                                                    <template x-for="item in interestList" :key="item">
                                                                        <button type="button" @click="toggleInterest(item)"
                                                                                :class="isInterestSelected(item) ? 'bg-[#560E0F]/40 border-[#df747a] text-white font-semibold' : 'bg-[#0B1120] border-slate-700 text-slate-300 hover:border-slate-500'"
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

                                                <div class="p-4 border-t border-slate-800 bg-[#0B1120] flex items-center justify-between">
                                                    <span class="text-xs text-slate-400">
                                                        <span x-text="draftInterests.length"></span> interests selected
                                                    </span>
                                                    <div class="flex items-center gap-2">
                                                        <button type="button" @click="cancelModal()" class="btn-secondary text-xs py-1.5 px-4">
                                                            Cancel
                                                        </button>
                                                        <button type="button" @click="applyChanges()" class="btn-primary text-xs py-1.5 px-4 bg-[#8F3033] hover:bg-[#a0363a]">
                                                            Apply Interests
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Profile Visibility Settings Section -->
                                <div class="space-y-3 pt-3 border-t border-slate-800" x-data="{ currentSetting: '{{ $currentVisibility }}' }">
                                    <div>
                                        <label class="label text-xs font-semibold text-white flex items-center gap-1.5 mb-0.5">
                                            <i data-lucide="eye" class="w-4 h-4 text-[#df747a]"></i>
                                            <span>Directory Visibility</span>
                                        </label>
                                        <p class="text-[11px] text-slate-400">Control who can discover your profile within ComSoc Hub.</p>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                        <!-- Public Option -->
                                        <label class="p-3 rounded-xl border cursor-pointer transition flex flex-col justify-between"
                                               :class="currentSetting === 'public' ? 'bg-[#560E0F]/20 border-[#df747a] ring-1 ring-[#df747a]/50' : 'bg-[#0B1120] border-slate-800 hover:border-slate-600'">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-2">
                                                    <i data-lucide="globe" class="w-3.5 h-3.5" :class="currentSetting === 'public' ? 'text-[#df747a]' : 'text-slate-400'"></i>
                                                    <span class="text-xs font-bold text-white">Public</span>
                                                </div>
                                                <input type="radio" name="visibility" value="public" x-model="currentSetting" 
                                                       class="w-3 h-3 text-[#8F3033] focus:ring-[#8F3033] bg-[#0B1120] border-slate-700">
                                            </div>
                                            <p class="text-[10px] text-slate-400 mt-2 leading-relaxed">Visible to directory visitors.</p>
                                        </label>

                                        <!-- Members Only Option -->
                                        <label class="p-3 rounded-xl border cursor-pointer transition flex flex-col justify-between"
                                               :class="currentSetting === 'members_only' ? 'bg-[#560E0F]/20 border-[#df747a] ring-1 ring-[#df747a]/50' : 'bg-[#0B1120] border-slate-800 hover:border-slate-600'">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-2">
                                                    <i data-lucide="users" class="w-3.5 h-3.5" :class="currentSetting === 'members_only' ? 'text-[#df747a]' : 'text-slate-400'"></i>
                                                    <span class="text-xs font-bold text-white">Members Only</span>
                                                </div>
                                                <input type="radio" name="visibility" value="members_only" x-model="currentSetting" 
                                                       class="w-3 h-3 text-[#8F3033] focus:ring-[#8F3033] bg-[#0B1120] border-slate-700">
                                            </div>
                                            <p class="text-[10px] text-slate-400 mt-2 leading-relaxed">Logged-in students only.</p>
                                        </label>

                                        <!-- Private Option -->
                                        <label class="p-3 rounded-xl border cursor-pointer transition flex flex-col justify-between"
                                               :class="currentSetting === 'private' ? 'bg-[#560E0F]/20 border-[#df747a] ring-1 ring-[#df747a]/50' : 'bg-[#0B1120] border-slate-800 hover:border-slate-600'">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-2">
                                                    <i data-lucide="lock" class="w-3.5 h-3.5" :class="currentSetting === 'private' ? 'text-[#df747a]' : 'text-slate-400'"></i>
                                                    <span class="text-xs font-bold text-white">Private</span>
                                                </div>
                                                <input type="radio" name="visibility" value="private" x-model="currentSetting" 
                                                       class="w-3 h-3 text-[#8F3033] focus:ring-[#8F3033] bg-[#0B1120] border-slate-700">
                                            </div>
                                            <p class="text-[10px] text-slate-400 mt-2 leading-relaxed">Hidden from searches.</p>
                                        </label>
                                    </div>
                                    <x-input-error class="mt-1" :messages="$errors->get('visibility')" />
                                </div>

                                <div class="pt-4 flex justify-end">
                                    <button type="submit" class="btn-primary text-xs py-2.5 px-6 bg-[#8F3033] hover:bg-[#a0363a] border-[#8F3033] shadow-lg shadow-[#8F3033]/25 flex items-center gap-2">
                                        <i data-lucide="check" class="w-4 h-4"></i>
                                        <span>Save Profile Changes</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Right Column: Quick Status Sidebars -->
                    <div class="space-y-6">

                        <!-- Digital Signature Status Card -->
                        <div id="profile-signature-status-card" class="card bg-[#172033]/90 border border-slate-800/80 shadow-2xl p-5 rounded-2xl backdrop-blur-xl">
                            <div class="flex items-center justify-between mb-3 border-b border-slate-800 pb-2">
                                <span class="text-xs font-bold text-white uppercase tracking-wider font-brand-display">Digital Signature</span>
                                <i data-lucide="pen-tool" class="w-4 h-4 text-[#df747a]"></i>
                            </div>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-slate-400">Status:</span>
                                    @if(($signatureStatus['state'] ?? 'required') === 'registered')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-950/60 text-emerald-300 border border-emerald-500/40">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                            {{ $signatureStatus['label'] }}
                                        </span>
                                    @elseif(($signatureStatus['state'] ?? 'required') === 'unavailable')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-950/60 text-rose-300 border border-rose-500/40">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                                            {{ $signatureStatus['label'] }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-950/60 text-amber-300 border border-amber-500/40">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                                            {{ $signatureStatus['label'] }}
                                        </span>
                                    @endif
                                </div>

                                <p class="text-[11px] text-slate-400 leading-relaxed">
                                    @if(($signatureStatus['state'] ?? 'required') === 'registered')
                                        Your signature is encrypted and active for attendance sheets.
                                    @else
                                        Required for official event sign-in sheets.
                                    @endif
                                </p>

                                <div class="pt-1">
                                    <a href="{{ route('signature.show') }}" class="w-full btn-secondary text-xs flex items-center justify-center gap-2 py-2">
                                        <i data-lucide="pen-tool" class="w-3.5 h-3.5 text-[#df747a]"></i>
                                        <span>{{ ($signatureStatus['state'] ?? 'required') === 'registered' ? 'Manage Signature' : 'Register Signature' }}</span>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Profile Completion Mini Card -->
                        <div class="card bg-[#172033]/90 border border-slate-800/80 shadow-2xl p-5 rounded-2xl backdrop-blur-xl">
                            <div class="flex items-center justify-between mb-3 border-b border-slate-800 pb-2">
                                <span class="text-xs font-bold text-white uppercase tracking-wider font-brand-display">Profile Strength</span>
                                <span class="text-xs font-mono font-bold text-amber-400">{{ $profileCompletion['percentage'] ?? 0 }}%</span>
                            </div>
                            <div class="space-y-3">
                                <div class="w-full h-2 rounded-full bg-[#0B1120] overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-[#8F3033] to-emerald-500 rounded-full" style="width: {{ $profileCompletion['percentage'] ?? 0 }}%;"></div>
                                </div>
                                <p class="text-[11px] text-slate-400 leading-relaxed">
                                    @if(($profileCompletion['is_complete'] ?? false))
                                        Profile is 100% complete! Rewards have been issued.
                                    @else
                                        Complete remaining fields to earn +{{ $profileCompletion['exp_reward'] ?? 100 }} EXP and +{{ $profileCompletion['points_reward'] ?? 50 }} Points.
                                    @endif
                                </p>
                                <button type="button" @click="setTab('progression')" class="text-xs text-[#df747a] hover:text-white underline transition flex items-center gap-1 font-medium">
                                    <span>View completion breakdown</span>
                                    <i data-lucide="arrow-right" class="w-3 h-3"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Quick Account Links -->
                        <div class="card bg-[#172033]/90 border border-slate-800/80 shadow-2xl p-5 rounded-2xl backdrop-blur-xl space-y-2">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-2">Security Shortcuts</span>
                            <button type="button" @click="setTab('security')" class="w-full text-left p-2.5 rounded-xl bg-[#0B1120]/70 border border-slate-800 hover:border-slate-700 text-xs text-slate-300 hover:text-white flex items-center justify-between transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="key-round" class="w-3.5 h-3.5 text-amber-400"></i>
                                    <span>Update Password</span>
                                </span>
                                <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-500"></i>
                            </button>
                            <a href="{{ route('profile.sessions') }}" class="w-full text-left p-2.5 rounded-xl bg-[#0B1120]/70 border border-slate-800 hover:border-slate-700 text-xs text-slate-300 hover:text-white flex items-center justify-between transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="shield-check" class="w-3.5 h-3.5 text-[#df747a]"></i>
                                    <span>Manage Active Sessions</span>
                                </span>
                                <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-500"></i>
                            </a>
                        </div>
                    </div>

                </div>
            </div>

            <!-- TAB 2: PROGRESSION & BADGES -->
            <div x-show="activeTab === 'progression'" x-cloak class="space-y-6">
                <!-- Profile Completion Detailed Card -->
                <x-profile-completion-card :completion="$profileCompletion ?? null" />

                <!-- 20 Level Badges Collection Grid -->
                <x-level-collection-grid :lifetimeExp="$lifetimeXp" :currentLevel="$currentLevel" />

                <!-- History Bento Grids (XP, Points, Attendance) -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- XP Transactions -->
                    <div class="card bg-[#172033]/90 border border-slate-800/80 shadow-2xl p-5 rounded-2xl backdrop-blur-xl">
                        <div class="flex items-center justify-between mb-3 border-b border-slate-800 pb-2">
                            <span class="text-xs font-bold text-white uppercase tracking-wider font-brand-display">XP History</span>
                            <i data-lucide="sparkles" class="w-4 h-4 text-[#df747a]"></i>
                        </div>
                        <div class="space-y-2 max-h-72 overflow-y-auto pr-1 scrollbar-thin">
                            @forelse($xpHistory as $tx)
                            <div class="flex items-center justify-between text-xs py-1.5 border-b border-slate-800/60 last:border-0">
                                <div>
                                    <p class="font-medium text-slate-200">{{ $tx->description }}</p>
                                    <p class="text-[10px] text-slate-500">{{ $tx->created_at->format('M d, Y') }}</p>
                                </div>
                                <span class="font-bold text-[#df747a] font-mono tabular-nums">+{{ $tx->amount }} EXP</span>
                            </div>
                            @empty
                            <p class="text-xs text-slate-500 text-center py-4">No XP history available.</p>
                            @endforelse
                        </div>
                    </div>

                    <!-- Points Transactions -->
                    <div class="card bg-[#172033]/90 border border-slate-800/80 shadow-2xl p-5 rounded-2xl backdrop-blur-xl">
                        <div class="flex items-center justify-between mb-3 border-b border-slate-800 pb-2">
                            <span class="text-xs font-bold text-white uppercase tracking-wider font-brand-display">Point Ledger</span>
                            <i data-lucide="coins" class="w-4 h-4 text-amber-400"></i>
                        </div>
                        <div class="space-y-2 max-h-72 overflow-y-auto pr-1 scrollbar-thin">
                            @forelse($pointHistory as $pt)
                            <div class="flex items-center justify-between text-xs py-1.5 border-b border-slate-800/60 last:border-0">
                                <div>
                                    <p class="font-medium text-slate-200">{{ $pt->description }}</p>
                                    <p class="text-[10px] text-slate-500">{{ $pt->created_at->format('M d, Y') }}</p>
                                </div>
                                <span class="font-bold font-mono tabular-nums {{ $pt->amount >= 0 ? 'text-amber-400' : 'text-red-400' }}">
                                    {{ $pt->amount >= 0 ? '+' : '' }}{{ $pt->amount }} pts
                                </span>
                            </div>
                            @empty
                            <p class="text-xs text-slate-500 text-center py-4">No point transactions yet.</p>
                            @endforelse
                        </div>
                    </div>

                    <!-- Event Check-Ins -->
                    <div class="card bg-[#172033]/90 border border-slate-800/80 shadow-2xl p-5 rounded-2xl backdrop-blur-xl">
                        <div class="flex items-center justify-between mb-3 border-b border-slate-800 pb-2">
                            <span class="text-xs font-bold text-white uppercase tracking-wider font-brand-display">Event Check-Ins</span>
                            <i data-lucide="calendar-check" class="w-4 h-4 text-emerald-400"></i>
                        </div>
                        <div class="space-y-2 max-h-72 overflow-y-auto pr-1 scrollbar-thin">
                            @forelse($attendanceHistory as $att)
                            <div class="p-2 rounded-xl bg-[#0B1120] border border-slate-800 text-xs">
                                <p class="font-bold text-white font-brand-display">{{ $att->event ? $att->event->name : 'General Event' }}</p>
                                <p class="text-[10px] text-slate-400">{{ $att->scanned_at ? $att->scanned_at->format('M d, Y H:i') : '' }}</p>
                            </div>
                            @empty
                            <p class="text-xs text-slate-500 text-center py-4">No event attendance records yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 3: ACCOUNT & SECURITY -->
            <div x-show="activeTab === 'security'" x-cloak class="space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    
                    <!-- Update Password -->
                    <div class="card bg-[#172033]/90 border border-slate-800/80 shadow-2xl p-6 rounded-2xl backdrop-blur-xl">
                        <div class="flex items-center gap-2 mb-4 border-b border-slate-800 pb-3">
                            <i data-lucide="key-round" class="w-4 h-4 text-amber-400"></i>
                            <h3 class="text-sm font-bold text-white font-brand-display uppercase tracking-wider">Update Account Password</h3>
                        </div>

                        <div class="max-w-xl">
                            @include('profile.partials.update-password-form')
                        </div>
                    </div>

                    <!-- Sessions & Devices -->
                    <div class="card bg-[#172033]/90 border border-slate-800/80 shadow-2xl p-6 rounded-2xl backdrop-blur-xl flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between gap-4 mb-4 border-b border-slate-800 pb-3">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="shield-check" class="w-4 h-4 text-[#df747a]"></i>
                                    <h3 class="text-sm font-bold text-white font-brand-display uppercase tracking-wider">Device &amp; Session Security</h3>
                                </div>
                            </div>
                            <p class="text-xs text-slate-300 leading-relaxed mb-4">
                                Monitor recognized browsers, view active logins, and invalidate unrecognized access sessions across desktop and mobile devices.
                            </p>
                        </div>
                        <div>
                            <a href="{{ route('profile.sessions') }}" class="btn-secondary text-xs flex items-center justify-center gap-2 py-2.5 w-full">
                                <span>Open Active Sessions Manager</span>
                                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                            </a>
                        </div>
                    </div>

                </div>

                <!-- Danger Zone -->
                <div class="card bg-red-950/20 border border-red-900/40 p-6 rounded-2xl shadow-xl">
                    <div class="flex items-center gap-2 mb-2">
                        <i data-lucide="alert-triangle" class="w-4 h-4 text-red-400"></i>
                        <h4 class="text-xs font-bold text-red-300 uppercase tracking-wider font-brand-display">Danger Zone</h4>
                    </div>
                    <p class="text-xs text-slate-400 mb-4">
                        Permanently delete your user account and purge personal profile information.
                    </p>
                    <div class="max-w-xl">
                        @include('profile.partials.delete-user-form')
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
                },

                toggleSkill(skill) {
                    const idx = this.draftSkills.indexOf(skill);
                    if (idx > -1) {
                        this.draftSkills.splice(idx, 1);
                    } else {
                        this.draftSkills.push(skill);
                    }
                },

                isSkillSelected(skill) {
                    return this.draftSkills.includes(skill);
                },

                addCustomSkill() {
                    const val = this.customInput.trim();
                    if (!val) return;
                    if (!this.draftSkills.includes(val)) {
                        this.draftSkills.push(val);
                    }
                    this.customInput = '';
                },

                applyChanges() {
                    this.savedSkills = [...this.draftSkills];
                    this.isOpen = false;
                    this.$nextTick(() => {
                        if (window.lucide) window.lucide.createIcons();
                    });
                },

                removeSavedSkill(skill) {
                    const idx = this.savedSkills.indexOf(skill);
                    if (idx > -1) {
                        this.savedSkills.splice(idx, 1);
                    }
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
                },

                toggleInterest(interest) {
                    const idx = this.draftInterests.indexOf(interest);
                    if (idx > -1) {
                        this.draftInterests.splice(idx, 1);
                    } else {
                        this.draftInterests.push(interest);
                    }
                },

                isInterestSelected(interest) {
                    return this.draftInterests.includes(interest);
                },

                addCustomInterest() {
                    const val = this.customInput.trim();
                    if (!val) return;
                    if (!this.draftInterests.includes(val)) {
                        this.draftInterests.push(val);
                    }
                    this.customInput = '';
                },

                applyChanges() {
                    this.savedInterests = [...this.draftInterests];
                    this.isOpen = false;
                    this.$nextTick(() => {
                        if (window.lucide) window.lucide.createIcons();
                    });
                },

                removeSavedInterest(interest) {
                    const idx = this.savedInterests.indexOf(interest);
                    if (idx > -1) {
                        this.savedInterests.splice(idx, 1);
                    }
                }
            }));
        });
    </script>
    @endpush
</x-app-layout>
