<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Choose your adventurer avatar and username on COMSOC HUB.">
    <title>Choose Your Adventurer — COMSOC HUB</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@600;700;800;900&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#0B1120] text-[#F8FAFC] font-sans antialiased selection:bg-[#560E0F] selection:text-white flex flex-col relative overflow-x-hidden">

    <!-- Ambient Background Decoration -->
    <x-public-auth-bg />

    <!-- Top Navigation Header -->
    <header class="w-full border-b border-[#334155]/60 bg-[#0B1120]/80 backdrop-blur-md sticky top-0 z-30">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 py-3.5 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center">
                    <img src="{{ asset('images/COMSOC.png') }}" alt="Computing Society" class="w-full h-full object-contain">
                </div>
                <div class="flex flex-col">
                    <span class="font-brand-display text-sm font-bold text-white tracking-tight">COMSOC HUB</span>
                    <span class="text-[10px] font-mono text-[#df747a] font-semibold uppercase tracking-wider">Identity Setup</span>
                </div>
            </div>

            <a href="{{ route('dashboard') }}" class="btn-secondary text-xs px-3 py-1.5 flex items-center gap-1.5 text-slate-300 hover:text-white">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                <span>Back</span>
            </a>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-1 flex flex-col justify-center items-center px-4 py-8 sm:py-12 relative z-10"
          x-data="{
              selectedSeed: '{{ addslashes(old('avatar_seed', $currentSeed)) }}',
              username: '{{ addslashes(old('username', $currentUsername)) }}',
              style: 'adventurer',
              isSubmitting: false,
              checkStatus: 'idle', // 'idle' | 'checking' | 'available' | 'unavailable'
              checkMessage: '',
              checkTimer: null,
              get previewUrl() {
                  return 'https://api.dicebear.com/9.x/adventurer/svg?seed=' + encodeURIComponent(this.selectedSeed);
              },
              onUsernameInput() {
                  clearTimeout(this.checkTimer);
                  const clean = this.username.trim();
                  if (clean.length < 3) {
                      this.checkStatus = 'idle';
                      this.checkMessage = '';
                      return;
                  }
                  this.checkStatus = 'checking';
                  this.checkMessage = 'Checking availability...';
                  this.checkTimer = setTimeout(() => {
                      fetch('{{ route('onboarding.adventurer.check') }}?username=' + encodeURIComponent(clean))
                          .then(res => res.json())
                          .then(data => {
                              this.checkStatus = data.available ? 'available' : 'unavailable';
                              this.checkMessage = data.message;
                          })
                          .catch(() => {
                              this.checkStatus = 'idle';
                              this.checkMessage = '';
                          });
                  }, 350);
              }
          }">

        <div class="w-full max-w-3xl space-y-6">

            <!-- Page Heading & Supporting Text -->
            <div class="text-center space-y-2">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#560E0F]/40 border border-[#8F3033]/60 text-[#df747a] text-xs font-semibold mb-1">
                    <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                    <span>Persona Customization</span>
                </div>
                <h1 class="font-brand-display text-2xl sm:text-3xl lg:text-4xl font-extrabold text-white tracking-tight">
                    Choose Your Adventurer
                </h1>
                <p class="text-sm sm:text-base text-[#94A3B8] max-w-md mx-auto">
                    Create your identity in the COMSOC HUB community.
                </p>
            </div>

            <!-- Form Card -->
            <div class="bg-[#172033] border border-[#334155] rounded-3xl p-6 sm:p-8 shadow-2xl relative overflow-hidden">

                @if(session('status'))
                    <div class="alert-success mb-6 p-4 bg-emerald-950/40 border border-emerald-800 text-emerald-300 rounded-2xl text-xs flex items-center gap-2">
                        <i data-lucide="check-circle" class="w-4 h-4 shrink-0"></i>
                        <span>{{ session('status') }}</span>
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-6 p-4 bg-rose-950/40 border border-rose-800 text-rose-300 rounded-2xl text-xs space-y-1">
                        <div class="font-bold flex items-center gap-1.5 text-rose-200">
                            <i data-lucide="alert-circle" class="w-4 h-4"></i>
                            <span>Please correct the errors below:</span>
                        </div>
                        <ul class="list-disc list-inside space-y-0.5 pl-2">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST"
                      action="{{ route('onboarding.adventurer.update') }}"
                      @submit="isSubmitting = true"
                      class="space-y-8">
                    @csrf

                    <!-- SECTION 1: Identity Live Preview Card -->
                    <div class="p-4 sm:p-5 rounded-2xl bg-gradient-to-r from-[#0B1120] via-[#172033] to-[#0B1120] border border-[#334155] flex flex-col sm:flex-row items-center gap-4 shadow-inner">
                        <div class="w-20 h-20 rounded-2xl bg-[#0B1120] border-2 border-[#8F3033] p-1 shadow-lg shrink-0 flex items-center justify-center overflow-hidden">
                            <img :src="previewUrl" :alt="selectedSeed" class="w-full h-full object-cover rounded-xl" />
                        </div>
                        <div class="text-center sm:text-left flex-1 min-w-0">
                            <span class="text-[10px] font-mono font-bold uppercase tracking-wider text-[#df747a]">Public Persona Preview</span>
                            <h2 class="text-lg sm:text-xl font-black text-white font-brand-display truncate mt-0.5"
                                x-text="username.trim() ? '@' + username.trim() : '@your_username'">
                                {{ '@' . ($currentUsername ?: 'your_username') }}
                            </h2>
                            <p class="text-xs text-[#94A3B8] mt-1">
                                {{ $user->student ? "{$user->student->program} • {$user->student->year_level}" : 'Computing Society Member' }}
                            </p>
                        </div>
                        <div class="hidden sm:block text-right">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-[#560E0F]/60 text-slate-200 border border-[#8F3033]">
                                <i data-lucide="shield-check" class="w-3 h-3 text-[#df747a]"></i>
                                <span>Official Member</span>
                            </span>
                        </div>
                    </div>

                    <!-- Hidden Inputs -->
                    <input type="hidden" name="avatar_style" :value="style">
                    <input type="hidden" name="avatar_seed" :value="selectedSeed">

                    <!-- SECTION 2: Adventurer Avatar Selection Gallery -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="label text-sm font-bold text-white mb-0">
                                Select an Avatar <span class="text-rose-400">*</span>
                            </label>
                            <span class="text-xs text-[#94A3B8]">Choose your character</span>
                        </div>

                        <div class="p-3.5 sm:p-4 rounded-2xl bg-[#0B1120]/80 border border-[#334155]">
                            <div class="avatar-grid-responsive grid grid-cols-4 sm:grid-cols-8 gap-2 sm:gap-2.5"
                                 role="radiogroup"
                                 aria-label="Adventurer Character Avatar options">
                                @foreach($avatarPresets as $preset)
                                <button type="button"
                                        role="radio"
                                        :aria-checked="selectedSeed === '{{ $preset['seed'] }}'"
                                        aria-label="{{ $preset['name'] }}"
                                        @click="selectedSeed = '{{ $preset['seed'] }}'"
                                        class="group relative flex flex-col items-center justify-center p-2 rounded-xl border transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-[#8F3033] focus:ring-offset-1 focus:ring-offset-[#0B1120] cursor-pointer min-h-[78px] sm:min-h-[86px]"
                                        :class="selectedSeed === '{{ $preset['seed'] }}'
                                            ? 'bg-[#560E0F]/40 border-[#df747a] ring-1 ring-[#df747a]/60 shadow-md'
                                            : 'bg-[#172033] border-[#334155] hover:border-slate-500 hover:bg-[#1E293B]'">
                                    <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-lg overflow-hidden bg-[#0B1120] border border-[#334155] p-0.5 flex items-center justify-center shrink-0">
                                        <img src="{{ $preset['url'] }}" alt="{{ $preset['name'] }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform" loading="lazy" />
                                    </div>
                                    <span class="text-[9.5px] sm:text-[10px] font-semibold mt-1 truncate max-w-full text-center tracking-tight"
                                          :class="selectedSeed === '{{ $preset['seed'] }}' ? 'text-white font-bold' : 'text-slate-400 group-hover:text-slate-200'">
                                        {{ $preset['name'] }}
                                    </span>
                                    <div x-show="selectedSeed === '{{ $preset['seed'] }}'"
                                         class="absolute top-1 right-1 w-3.5 h-3.5 rounded-full bg-[#560E0F] border border-[#df747a] text-white flex items-center justify-center shadow">
                                        <i data-lucide="check" class="w-2 h-2 text-white stroke-[3]"></i>
                                    </div>
                                </button>
                                @endforeach
                            </div>
                        </div>
                        @error('avatar_seed')
                            <p class="text-xs text-rose-400 mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- SECTION 3: Username Field -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label for="username" class="label text-sm font-bold text-white mb-0">
                                Username <span class="text-rose-400">*</span>
                            </label>
                            <span class="text-xs text-[#94A3B8]">3–20 characters</span>
                        </div>

                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#94A3B8] font-bold">
                                @
                            </div>
                            <input type="text"
                                   name="username"
                                   id="username"
                                   x-model="username"
                                   @input="onUsernameInput"
                                   placeholder="Enter your username"
                                   required
                                   maxlength="20"
                                   autocomplete="off"
                                   spellcheck="false"
                                   class="input pl-8 pr-10 text-sm font-semibold @error('username') border-rose-500 @enderror">

                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <template x-if="checkStatus === 'checking'">
                                    <svg class="animate-spin h-4 w-4 text-[#df747a]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                </template>
                                <template x-if="checkStatus === 'available'">
                                    <i data-lucide="check-circle" class="w-4 h-4 text-emerald-400"></i>
                                </template>
                                <template x-if="checkStatus === 'unavailable'">
                                    <i data-lucide="x-circle" class="w-4 h-4 text-rose-400"></i>
                                </template>
                            </div>
                        </div>

                        <!-- Availability & Helper Feedback -->
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 text-xs">
                            <p class="text-[#94A3B8]">
                                Letters, numbers, and underscores only. You can update this later in Profile Settings.
                            </p>
                            <template x-if="checkMessage">
                                <span class="font-medium shrink-0"
                                      :class="{
                                          'text-[#94A3B8]': checkStatus === 'checking',
                                          'text-emerald-400': checkStatus === 'available',
                                          'text-rose-400': checkStatus === 'unavailable'
                                      }"
                                      x-text="checkMessage">
                                </span>
                            </template>
                        </div>
                        @error('username')
                            <p class="text-xs text-rose-400 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Page Actions -->
                    <div class="pt-4 border-t border-[#334155] flex flex-col-reverse sm:flex-row items-center justify-between gap-3">
                        <a href="{{ route('dashboard') }}"
                           class="btn-secondary w-full sm:w-auto px-5 py-2.5 text-xs text-center">
                            Back
                        </a>

                        <button type="submit"
                                :disabled="isSubmitting"
                                class="btn-primary w-full sm:w-auto px-6 py-2.5 text-xs font-bold flex items-center justify-center gap-2 shadow-lg disabled:opacity-60 disabled:cursor-not-allowed">
                            <template x-if="isSubmitting">
                                <svg class="animate-spin h-3.5 w-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                            </template>
                            <span x-text="isSubmitting ? 'Saving Identity...' : 'Save and Continue'">Save and Continue</span>
                        </button>
                    </div>

                </form>

            </div>

        </div>

    </main>

</body>
</html>
