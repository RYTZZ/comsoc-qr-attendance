<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Create your student account on COMSOC QR Attendance.">
    <title>Student Sign Up — Computing Society QR Attendance</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Michroma&family=Sora:wght@600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#0B1120] min-h-screen flex flex-col justify-center items-center p-2 sm:p-3 font-sans text-slate-100 relative overflow-x-hidden overflow-y-auto selection:bg-[#560E0F] selection:text-white">

<x-page-loader />
<x-public-auth-bg />

<div class="w-full max-w-2xl relative z-10 py-1 sm:py-2 my-auto">
    <div class="text-center mb-2 sm:mb-2.5">
        <div class="w-10 h-10 sm:w-11 sm:h-11 mx-auto mb-1 flex items-center justify-center p-1.5 rounded-2xl bg-[#172033]/90 border border-slate-800 shadow-xl enter-logo backdrop-blur-sm">
            <img src="{{ asset('images/COMSOC.png') }}" alt="Computing Society Logo" class="w-full h-full object-contain">
        </div>
        <div class="enter-header">
            <p class="font-brand-accent text-[9px] sm:text-[10px] text-[#dfa6a9] tracking-widest uppercase mb-0.5">COMPUTING SOCIETY</p>
            <h1 class="font-brand-display text-base sm:text-lg md:text-xl font-bold text-white tracking-tight">Student Registration</h1>
            <p class="text-slate-400 mt-0.5 text-[11px] sm:text-xs">Create your student account to track attendance, earn XP, and unlock rewards</p>
        </div>
    </div>

    <div class="card shadow-2xl border border-slate-800/80 bg-[#172033]/95 backdrop-blur-md p-3 sm:p-4 md:p-5 enter-card"
         x-data="{
             step: {{ $errors->hasAny(['email', 'password', 'password_confirmation', 'terms']) ? 2 : 1 }},
             submitting: false,
             showPass: false,
             showConfirm: false,
             studentId: '{{ addslashes(old('student_id', '')) }}',
             firstName: '{{ addslashes(old('first_name', '')) }}',
             lastName: '{{ addslashes(old('last_name', '')) }}',
             middleName: '{{ addslashes(old('middle_name', '')) }}',
             program: '{{ addslashes(old('program', '')) }}',
             year: '{{ addslashes(old('year', '')) }}',
             block: '{{ addslashes(old('block', '')) }}',
             email: '{{ addslashes(old('email', '')) }}',
             password: '',
             passwordConfirmation: '',
             avatarSeed: '{{ addslashes(old('avatar_seed', 'Felix')) }}',
             avatarStyle: 'adventurer',
             get avatarPreviewUrl() {
                 return 'https://api.dicebear.com/9.x/adventurer/svg?seed=' + encodeURIComponent(this.avatarSeed);
             },
             termsAccepted: {{ old('terms') ? 'true' : 'false' }},
             clientErrors: {},

             get studentIdDigits() {
                 return (this.studentId || '').replace(/\D/g, '');
             },

             validateStep1() {
                 this.clientErrors = {};
                 let valid = true;

                 const trimmedId = (this.studentId || '').trim();
                 if (!trimmedId) {
                     this.clientErrors.student_id = 'Student ID is required.';
                     valid = false;
                 } else if (!/^[\d-]+$/.test(trimmedId)) {
                     this.clientErrors.student_id = 'Student ID must only contain digits and hyphens.';
                     valid = false;
                 } else if (this.studentIdDigits.length === 0) {
                     this.clientErrors.student_id = 'Student ID must contain at least one digit.';
                     valid = false;
                 } else if (this.studentIdDigits.length > 8) {
                     this.clientErrors.student_id = 'Student ID may not exceed 8 digits.';
                     valid = false;
                 }

                 if (!(this.firstName || '').trim()) {
                     this.clientErrors.first_name = 'First name is required.';
                     valid = false;
                 }

                 if (!(this.lastName || '').trim()) {
                     this.clientErrors.last_name = 'Last name is required.';
                     valid = false;
                 }

                 if (!(this.program || '').trim()) {
                     this.clientErrors.program = 'Please select your academic program.';
                     valid = false;
                 }

                 if (!(this.year || '').trim()) {
                     this.clientErrors.year = 'Please select your year level.';
                     valid = false;
                 }

                 if (!(this.block || '').trim()) {
                     this.clientErrors.block = 'Please select your section block.';
                     valid = false;
                 }

                 return valid;
             },

             goToStep2() {
                 if (this.validateStep1()) {
                     this.step = 2;
                     window.scrollTo({ top: 0, behavior: 'smooth' });
                 }
             },

             goToStep1() {
                 this.step = 1;
                 window.scrollTo({ top: 0, behavior: 'smooth' });
             },

             get passwordMetrics() {
                 const p = this.password || '';
                 if (!p) {
                     return { score: 0, state: 'Empty', widthClass: 'w-0', colorClass: 'bg-slate-700', textClass: 'text-slate-500' };
                 }

                 let score = 0;
                 if (p.length >= 8) score += 1;
                 if (p.length >= 12) score += 1;
                 if (/[a-z]/.test(p) && /[A-Z]/.test(p)) score += 1;
                 if (/\d/.test(p)) score += 1;
                 if (/[^a-zA-Z0-9]/.test(p)) score += 1;

                 const repetitive = /(.)\1{2,}/.test(p);
                 const sequence = /1234|2345|3456|4567|5678|6789|abcd|bcde|cdef|qwerty|asdf|password/i.test(p);
                 if ((repetitive || sequence) && score > 1) {
                     score -= 1;
                 }

                 if (score <= 1) {
                     return { score: 1, state: 'Very Weak', widthClass: 'w-1/5', colorClass: 'bg-red-500', textClass: 'text-red-400' };
                 } else if (score === 2) {
                     return { score: 2, state: 'Weak', widthClass: 'w-2/5', colorClass: 'bg-orange-500', textClass: 'text-orange-400' };
                 } else if (score === 3) {
                     return { score: 3, state: 'Fair', widthClass: 'w-3/5', colorClass: 'bg-yellow-500', textClass: 'text-yellow-400' };
                 } else if (score === 4) {
                     return { score: 4, state: 'Strong', widthClass: 'w-4/5', colorClass: 'bg-emerald-500', textClass: 'text-emerald-400' };
                 } else {
                     return { score: 5, state: 'Very Strong', widthClass: 'w-full', colorClass: 'bg-teal-400', textClass: 'text-teal-300' };
                 }
             },

             validateStep2() {
                 this.clientErrors = {};
                 let valid = true;

                 const trimmedEmail = (this.email || '').trim();
                 if (!trimmedEmail) {
                     this.clientErrors.email = 'Email address is required.';
                     valid = false;
                 } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(trimmedEmail)) {
                     this.clientErrors.email = 'Please provide a valid email address.';
                     valid = false;
                 }

                 if (!this.password) {
                     this.clientErrors.password = 'Password is required.';
                     valid = false;
                 } else if (this.password.length < 8) {
                     this.clientErrors.password = 'Password must be at least 8 characters long.';
                     valid = false;
                 } else if (!/[a-zA-Z]/.test(this.password) || !/\d/.test(this.password)) {
                     this.clientErrors.password = 'Password must contain both letters and numbers.';
                     valid = false;
                 }

                 if (!this.passwordConfirmation) {
                     this.clientErrors.password_confirmation = 'Please confirm your password.';
                     valid = false;
                 } else if (this.password !== this.passwordConfirmation) {
                     this.clientErrors.password_confirmation = 'Password confirmation does not match.';
                     valid = false;
                 }

                 if (!this.termsAccepted) {
                     this.clientErrors.terms = 'You must accept the terms and privacy notice to continue.';
                     valid = false;
                 }

                 return valid;
             },

             submitForm(e) {
                 if (!this.validateStep1()) {
                     e.preventDefault();
                     this.step = 1;
                     return;
                 }
                 if (!this.validateStep2()) {
                     e.preventDefault();
                     return;
                 }
                 this.submitting = true;
             }
         }"
         @dropdown-selected.window="
             if ($event.detail.name === 'program') { program = $event.detail.value; clientErrors.program = ''; }
             if ($event.detail.name === 'year') { year = $event.detail.value; clientErrors.year = ''; }
             if ($event.detail.name === 'block') { block = $event.detail.value; clientErrors.block = ''; }
         ">

        <nav aria-label="Registration Progress" class="mb-3 sm:mb-3.5">
            <ol class="grid grid-cols-2 gap-2 sm:gap-3">
                <li class="flex items-center gap-2 p-1.5 sm:p-2 rounded-xl border transition"
                    :class="step === 1 ? 'bg-[#560E0F]/20 border-[#560E0F] text-white' : 'bg-slate-900/40 border-slate-800 text-slate-400'">
                    <span class="w-5 h-5 sm:w-6 sm:h-6 rounded-lg flex items-center justify-center font-bold text-xs shrink-0"
                          :class="step === 1 ? 'bg-[#560E0F] text-white' : (step > 1 ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/40' : 'bg-slate-800 text-slate-400')">
                        <template x-if="step > 1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                        </template>
                        <template x-if="step <= 1">
                            <span>1</span>
                        </template>
                    </span>
                    <div class="min-w-0">
                        <p class="text-[9px] sm:text-[10px] font-mono uppercase tracking-wider text-slate-400">Step 1 of 2</p>
                        <p class="text-xs sm:text-sm font-semibold truncate" :class="step === 1 ? 'text-white' : 'text-slate-300'">Academic Info</p>
                    </div>
                </li>

                <li class="flex items-center gap-2 p-1.5 sm:p-2 rounded-xl border transition"
                    :class="step === 2 ? 'bg-[#560E0F]/20 border-[#560E0F] text-white' : 'bg-slate-900/40 border-slate-800 text-slate-400'">
                    <span class="w-5 h-5 sm:w-6 sm:h-6 rounded-lg flex items-center justify-center font-bold text-xs shrink-0"
                          :class="step === 2 ? 'bg-[#560E0F] text-white' : 'bg-slate-800 text-slate-400'">
                        2
                    </span>
                    <div class="min-w-0">
                        <p class="text-[9px] sm:text-[10px] font-mono uppercase tracking-wider text-slate-400">Step 2 of 2</p>
                        <p class="text-xs sm:text-sm font-semibold truncate" :class="step === 2 ? 'text-white' : 'text-slate-300'">Account Security</p>
                    </div>
                </li>
            </ol>
        </nav>

        @if($errors->any())
        <div class="alert-error mb-3 flex items-start gap-2 bg-red-950/50 border border-red-800 text-red-200 px-3 py-2 rounded-xl text-xs" role="alert">
            <svg class="w-4 h-4 flex-shrink-0 mt-0.5 text-red-400" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <div>
                <p class="font-semibold text-white">Please correct the following errors:</p>
                <ul class="list-disc list-inside mt-0.5 text-[11px] text-red-300 space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

        <form method="POST" action="{{ route('signup.store') }}" @submit="submitForm($event)" class="space-y-3" novalidate>
            @csrf

            <div x-show="step === 1" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-2.5">
                <div class="border-b border-slate-800 pb-2 flex items-center justify-between">
                    <div>
                        <h2 class="font-brand-display text-sm sm:text-base font-bold text-white tracking-wide">Student Academic Information</h2>
                        <p class="text-[11px] sm:text-xs text-slate-400">Enter your institutional student identity and enrolled program</p>
                    </div>
                    <span class="text-xs font-mono text-slate-500 hidden sm:inline">1 / 2</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-6 gap-2 sm:gap-2.5">
                    <div class="sm:col-span-6">
                        <div class="flex items-center justify-between mb-0.5">
                            <label for="student_id" class="label text-xs">Student ID / Student Number <span class="text-red-400">*</span></label>
                            <span class="text-[11px] font-mono text-slate-400" :class="studentIdDigits.length > 8 ? 'text-red-400 font-bold' : ''">
                                <span x-text="studentIdDigits.length"></span>/8 digits
                            </span>
                        </div>
                        <input type="text"
                               name="student_id"
                               id="student_id"
                               x-model="studentId"
                               @input="clientErrors.student_id = ''"
                               maxlength="20"
                               autocomplete="off"
                               aria-describedby="student-id-helper student-id-error"
                               class="input py-2 text-xs sm:text-sm @error('student_id') border-red-500 @enderror"
                               :class="clientErrors.student_id ? 'border-red-500' : ''"
                               required
                               autofocus>
                        <p id="student-id-helper" class="text-[10.5px] text-slate-400 mt-0.5">Maximum 8 digits. Hyphens accepted per institutional ID format.</p>
                        <template x-if="clientErrors.student_id">
                            <p id="student-id-error" class="text-xs text-red-400 mt-0.5 font-medium" x-text="clientErrors.student_id"></p>
                        </template>
                        @error('student_id')
                            <p class="text-xs text-red-400 mt-0.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="first_name" class="label text-xs mb-0.5">First Name <span class="text-red-400">*</span></label>
                        <input type="text"
                               name="first_name"
                               id="first_name"
                               x-model="firstName"
                               @input="clientErrors.first_name = ''"
                               autocomplete="given-name"
                               class="input py-2 text-xs sm:text-sm @error('first_name') border-red-500 @enderror"
                               :class="clientErrors.first_name ? 'border-red-500' : ''"
                               required>
                        <template x-if="clientErrors.first_name">
                            <p class="text-xs text-red-400 mt-0.5 font-medium" x-text="clientErrors.first_name"></p>
                        </template>
                        @error('first_name')
                            <p class="text-xs text-red-400 mt-0.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="middle_name" class="label text-xs mb-0.5">Middle Name <span class="text-slate-500 font-normal">(Optional)</span></label>
                        <input type="text"
                               name="middle_name"
                               id="middle_name"
                               x-model="middleName"
                               autocomplete="additional-name"
                               class="input py-2 text-xs sm:text-sm @error('middle_name') border-red-500 @enderror">
                        @error('middle_name')
                            <p class="text-xs text-red-400 mt-0.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="last_name" class="label text-xs mb-0.5">Last Name <span class="text-red-400">*</span></label>
                        <input type="text"
                               name="last_name"
                               id="last_name"
                               x-model="lastName"
                               @input="clientErrors.last_name = ''"
                               autocomplete="family-name"
                               class="input py-2 text-xs sm:text-sm @error('last_name') border-red-500 @enderror"
                               :class="clientErrors.last_name ? 'border-red-500' : ''"
                               required>
                        <template x-if="clientErrors.last_name">
                            <p class="text-xs text-red-400 mt-0.5 font-medium" x-text="clientErrors.last_name"></p>
                        </template>
                        @error('last_name')
                            <p class="text-xs text-red-400 mt-0.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-6">
                        <label for="program" class="label text-xs mb-0.5">Academic Program <span class="text-red-400">*</span></label>
                        <x-custom-dropdown
                            name="program"
                            id="program"
                            :options="$programs"
                            :value="old('program')"
                            placeholder="Select Program"
                            required="true"
                            buttonClass="py-2 text-xs sm:text-sm bg-[#12141c]" />
                        <template x-if="clientErrors.program">
                            <p class="text-xs text-red-400 mt-0.5 font-medium" x-text="clientErrors.program"></p>
                        </template>
                        @error('program')
                            <p class="text-xs text-red-400 mt-0.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-3">
                        <label for="year" class="label text-xs mb-0.5">Year Level <span class="text-red-400">*</span></label>
                        <x-custom-dropdown
                            name="year"
                            id="year"
                            :options="$years"
                            :value="old('year')"
                            placeholder="Select Year Level"
                            required="true"
                            buttonClass="py-2 text-xs sm:text-sm bg-[#12141c]" />
                        <template x-if="clientErrors.year">
                            <p class="text-xs text-red-400 mt-0.5 font-medium" x-text="clientErrors.year"></p>
                        </template>
                        @error('year')
                            <p class="text-xs text-red-400 mt-0.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-3">
                        <label for="block" class="label text-xs mb-0.5">Block / Section <span class="text-red-400">*</span></label>
                        <x-custom-dropdown
                            name="block"
                            id="block"
                            :options="$blocks"
                            :value="old('block')"
                            placeholder="Select Block"
                            required="true"
                            buttonClass="py-2 text-xs sm:text-sm bg-[#12141c]" />
                        <template x-if="clientErrors.block">
                            <p class="text-xs text-red-400 mt-0.5 font-medium" x-text="clientErrors.block"></p>
                        </template>
                        @error('block')
                            <p class="text-xs text-red-400 mt-0.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="pt-1.5 flex justify-end">
                    <button type="button"
                            @click="goToStep2()"
                            class="btn-primary w-full sm:w-auto min-w-[130px] justify-center py-2 sm:py-2.5 font-semibold text-xs sm:text-sm tracking-wide transition flex items-center gap-2">
                        <span>Continue</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div x-show="step === 2" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-2.5">
                <div class="border-b border-slate-800 pb-2 flex items-center justify-between">
                    <div>
                        <h2 class="font-brand-display text-sm sm:text-base font-bold text-white tracking-wide">Account Credentials</h2>
                        <p class="text-[11px] sm:text-xs text-slate-400">Configure your secure sign-in email and password</p>
                    </div>
                    <span class="text-xs font-mono text-slate-500 hidden sm:inline">2 / 2</span>
                </div>

                <div class="space-y-2.5">
                    <!-- Adventurer Avatar Picker -->
                    <div class="p-2 sm:p-2.5 rounded-xl bg-slate-950/70 border border-slate-800 space-y-1.5">
                        <div class="flex items-center justify-between pb-1 border-b border-slate-800/80">
                            <div>
                                <label class="label text-xs font-bold text-white mb-0">Choose Your Adventurer Avatar</label>
                                <p class="text-[10px] sm:text-[11px] text-slate-400">Personalize your student profile across COMSOC Hub</p>
                            </div>
                            <div class="w-8 h-8 rounded-lg bg-[#172033] border border-[#560E0F] p-0.5 shadow-sm flex items-center justify-center shrink-0 overflow-hidden">
                                <img :src="avatarPreviewUrl" :alt="avatarSeed" class="w-full h-full object-cover rounded" />
                            </div>
                        </div>

                        <input type="hidden" name="avatar_style" :value="avatarStyle">
                        <input type="hidden" name="avatar_seed" :value="avatarSeed">

                        <div class="max-h-[120px] sm:max-h-[135px] overflow-y-auto pr-1 -mr-1 scrollbar-thin">
                            <div class="grid grid-cols-4 sm:grid-cols-8 gap-1"
                                 role="radiogroup"
                                 aria-label="Adventurer Character Avatar options">
                                @foreach($avatarPresets as $preset)
                                <button type="button"
                                        role="radio"
                                        :aria-checked="avatarSeed === '{{ $preset['seed'] }}'"
                                        aria-label="{{ $preset['name'] }}"
                                        @click="avatarSeed = '{{ $preset['seed'] }}'"
                                        class="group relative flex flex-col items-center justify-center p-1 rounded-lg border transition-all duration-150 focus:outline-none focus:ring-1 focus:ring-[#8F3033] min-h-[50px] sm:min-h-[54px]"
                                        :class="avatarSeed === '{{ $preset['seed'] }}'
                                            ? 'bg-[#560E0F]/30 border-[#df747a] ring-1 ring-[#df747a]/60 shadow'
                                            : 'bg-slate-900 border-slate-800 hover:border-slate-700 hover:bg-slate-800'">
                                    <div class="w-7 h-7 sm:w-8 sm:h-8 rounded overflow-hidden bg-slate-950 flex items-center justify-center border border-slate-800/80 p-0.5">
                                        <img src="{{ $preset['url'] }}" alt="" aria-hidden="true" class="w-full h-full object-cover group-hover:scale-105 transition-transform" loading="lazy" />
                                    </div>
                                    <span class="text-[8.5px] sm:text-[9px] font-semibold mt-0.5 truncate max-w-full text-center"
                                          :class="avatarSeed === '{{ $preset['seed'] }}' ? 'text-white font-bold' : 'text-slate-400 group-hover:text-slate-200'">
                                        {{ $preset['name'] }}
                                    </span>
                                    <div x-show="avatarSeed === '{{ $preset['seed'] }}'"
                                         class="absolute top-0.5 right-0.5 w-2.5 h-2.5 rounded-full bg-[#560E0F] border border-[#df747a] text-white flex items-center justify-center shadow">
                                        <svg class="w-1.5 h-1.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                </button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="email" class="label text-xs mb-0.5">Email Address <span class="text-red-400">*</span></label>
                        <input type="email"
                               name="email"
                               id="email"
                               x-model="email"
                               @input="clientErrors.email = ''"
                               autocomplete="email"
                               class="input py-2 text-xs sm:text-sm @error('email') border-red-500 @enderror"
                               :class="clientErrors.email ? 'border-red-500' : ''"
                               required>
                        <template x-if="clientErrors.email">
                            <p class="text-xs text-red-400 mt-0.5 font-medium" x-text="clientErrors.email"></p>
                        </template>
                        @error('email')
                            <p class="text-xs text-red-400 mt-0.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-2.5">
                        <div>
                            <label for="password" class="label text-xs mb-0.5">Password <span class="text-red-400">*</span></label>
                            <div class="relative">
                                <input :type="showPass ? 'text' : 'password'"
                                       name="password"
                                       id="password"
                                       x-model="password"
                                       @input="clientErrors.password = ''"
                                       autocomplete="new-password"
                                       aria-describedby="password-rules password-strength-live"
                                       class="input py-2 text-xs sm:text-sm pr-9 @error('password') border-red-500 @enderror"
                                       :class="clientErrors.password ? 'border-red-500' : ''"
                                       required>
                                <button type="button"
                                        @click="showPass = !showPass"
                                        :aria-label="showPass ? 'Hide password' : 'Show password'"
                                        class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-white transition focus:outline-none focus:text-white">
                                    <template x-if="!showPass">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </template>
                                    <template x-if="showPass">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                        </svg>
                                    </template>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label for="password_confirmation" class="label text-xs mb-0.5">Confirm Password <span class="text-red-400">*</span></label>
                            <div class="relative">
                                <input :type="showConfirm ? 'text' : 'password'"
                                       name="password_confirmation"
                                       id="password_confirmation"
                                       x-model="passwordConfirmation"
                                       @input="clientErrors.password_confirmation = ''"
                                       autocomplete="new-password"
                                       class="input py-2 text-xs sm:text-sm pr-9"
                                       :class="clientErrors.password_confirmation ? 'border-red-500' : ''"
                                       required>
                                <button type="button"
                                        @click="showConfirm = !showConfirm"
                                        :aria-label="showConfirm ? 'Hide password confirmation' : 'Show password confirmation'"
                                        class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-white transition focus:outline-none focus:text-white">
                                    <template x-if="!showConfirm">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </template>
                                    <template x-if="showConfirm">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                        </svg>
                                    </template>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="p-2 rounded-xl bg-slate-950/70 border border-slate-800/90 space-y-1">
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-slate-400 font-medium">Password Strength:</span>
                            <span id="password-strength-live"
                                  aria-live="polite"
                                  class="font-semibold transition-colors duration-200"
                                  :class="passwordMetrics.textClass"
                                  x-text="passwordMetrics.state"></span>
                        </div>

                        <div class="w-full bg-slate-800 h-1.5 rounded-full overflow-hidden" role="progressbar" :aria-valuenow="passwordMetrics.score" aria-valuemin="0" aria-valuemax="5" :aria-valuetext="passwordMetrics.state">
                            <div class="h-full transition-all duration-300 rounded-full"
                                 :class="[passwordMetrics.widthClass, passwordMetrics.colorClass]"></div>
                        </div>

                        <ul id="password-rules" class="flex flex-wrap gap-x-3 gap-y-0.5 text-[10.5px] text-slate-400">
                            <li class="flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full" :class="(password || '').length >= 8 ? 'bg-emerald-400' : 'bg-slate-600'"></span>
                                <span>At least 8 characters</span>
                            </li>
                            <li class="flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full" :class="/[a-zA-Z]/.test(password || '') && /\d/.test(password || '') ? 'bg-emerald-400' : 'bg-slate-600'"></span>
                                <span>Letters and numbers</span>
                            </li>
                        </ul>
                    </div>

                    <template x-if="clientErrors.password">
                        <p class="text-xs text-red-400 mt-0.5 font-medium" x-text="clientErrors.password"></p>
                    </template>
                    @error('password')
                        <p class="text-xs text-red-400 mt-0.5 font-medium">{{ $message }}</p>
                    @enderror
                    <template x-if="clientErrors.password_confirmation">
                        <p class="text-xs text-red-400 mt-0.5 font-medium" x-text="clientErrors.password_confirmation"></p>
                    </template>
                </div>

                <!-- Formal Privacy Notice -->
                <div class="p-2.5 rounded-xl bg-slate-950/80 border border-slate-800 text-[10.5px] text-slate-300 space-y-1">
                    <div class="flex items-center gap-1.5 text-white font-semibold">
                        <svg class="w-3.5 h-3.5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <span>COMSOC Privacy Notice & Consent</span>
                    </div>
                    <p class="text-[10px] sm:text-[10.5px] text-slate-400 leading-snug"><strong>Purpose &amp; Scope:</strong> COMSOC collects identity info solely for student verification, attendance, credentials, and reward operations. We do not import external lists or sell data.
                    </p>
                </div>

                <div>
                    <label class="flex items-start gap-2 text-[11px] sm:text-xs text-slate-400 cursor-pointer p-2 rounded-xl bg-slate-950/60 border border-slate-800 hover:border-slate-700 transition">
                        <input type="checkbox"
                               name="terms"
                               value="1"
                               x-model="termsAccepted"
                               @change="clientErrors.terms = ''"
                               required
                               class="w-3.5 h-3.5 mt-0.5 rounded bg-slate-900 border-slate-700 text-[#560E0F] focus:ring-[#560E0F]">
                        <span>I have read and agree to the <strong>COMSOC Privacy Notice</strong> and confirm all information is accurate.</span>
                    </label>
                    <template x-if="clientErrors.terms">
                        <p class="text-xs text-red-400 mt-0.5 font-medium" x-text="clientErrors.terms"></p>
                    </template>
                    @error('terms')
                        <p class="text-xs text-red-400 mt-0.5 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <div class="pt-1 flex flex-col-reverse sm:flex-row items-center justify-between gap-2.5">
                    <button type="button"
                            @click="goToStep1()"
                            class="btn-secondary w-full sm:w-auto min-w-[100px] justify-center py-2 sm:py-2.5 text-xs sm:text-sm flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        <span>Back</span>
                    </button>

                    <button type="submit"
                            :disabled="submitting"
                            class="btn-primary w-full sm:w-auto min-w-[160px] justify-center py-2 sm:py-2.5 font-semibold text-xs sm:text-sm tracking-wide transition flex items-center gap-1.5">
                        <span x-show="!submitting">Create Student Account</span>
                        <span x-show="submitting" class="flex items-center gap-1.5">
                            <svg class="animate-spin -ml-1 mr-1.5 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            Creating account...
                        </span>
                    </button>
                </div>
            </div>

            <div class="text-center pt-0.5">
                <p class="text-[11px] sm:text-xs text-slate-400">
                    Already have an account?
                    <a href="{{ route('login') }}" class="text-[#e69094] hover:text-white font-medium underline transition">Sign In here</a>
                </p>
            </div>
        </form>
    </div>

    <x-public-branding-footer />
</div>

</body>
</html>
