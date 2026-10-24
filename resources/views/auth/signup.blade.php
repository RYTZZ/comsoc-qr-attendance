<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Sign Up for COMSOC QR Attendance System.">
    <title>Sign Up — COMSOC QR Attendance System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@600;700;800;900&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* Critical auth layout: independent of the compiled Vite bundle. */
        .auth-branding-aside, .auth-content-main { width: 100%; min-width: 0; box-sizing: border-box; }
        @media (min-width: 768px) {
            .auth-branding-aside { width: 41.666667%; max-width: 41.666667%; flex: 0 0 41.666667%; }
            .auth-content-main { width: 58.333333%; max-width: 58.333333%; flex: 1 1 58.333333%; }
        }
        .academic-name-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.75rem;
        }
        @media (min-width: 640px) {
            .academic-name-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }
        .academic-cohort-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.75rem;
        }
        @media (min-width: 640px) {
            .academic-cohort-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        .security-passwords-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.75rem;
        }
        @media (min-width: 640px) {
            .security-passwords-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        .avatar-grid-responsive {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.5rem;
        }
        @media (min-width: 640px) {
            .avatar-grid-responsive {
                grid-template-columns: repeat(8, minmax(0, 1fr));
                gap: 0.625rem;
            }
        }
    </style>
</head>
<body class="min-h-screen bg-[#0B1120] text-[#F8FAFC] font-sans antialiased selection:bg-[#560E0F] selection:text-white flex flex-col md:flex-row relative overflow-x-hidden">

    <!-- Ambient Background Decoration -->
    <x-public-auth-bg />

    <!-- ======================================================== -->
    <!-- LEFT BRANDING PANEL (Approx. 41.6% width on desktop)     -->
    <!-- ======================================================== -->
    <aside class="auth-branding-aside w-full md:w-5/12 flex flex-col items-center justify-center p-8 sm:p-12 lg:p-16 border-b md:border-b-0 md:border-r border-[#334155] select-none bg-[#0B1120]/70 backdrop-blur-sm relative z-10 shrink-0">
        <!-- Return to Public Website Link -->
        <div class="w-full max-w-sm mb-6 text-left">
            <a href="{{ route('public.home') }}" class="inline-flex items-center gap-1.5 text-xs text-slate-400 hover:text-white transition group focus:outline-none focus:ring-2 focus:ring-[#df747a] rounded-lg px-2 py-1">
                <svg class="w-3.5 h-3.5 text-[#df747a] group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span>Return to COMSOC Website</span>
            </a>
        </div>

        <div class="w-full max-w-sm mx-auto flex flex-col items-center text-center">
            
            <!-- 1. The official COMSOC Logo (static, preserving aspect ratio, no container box) -->
            <div class="w-28 h-28 sm:w-32 sm:h-32 lg:w-40 lg:h-40 mx-auto mb-5 flex items-center justify-center">
                <img src="{{ asset('images/COMSOC.png') }}" alt="Computing Society Logo" class="w-full h-full object-contain">
            </div>

            <!-- 2. COMPUTING SOCIETY ORGANIZATION -->
            <p class="font-brand-accent text-xs sm:text-sm font-bold text-[#df747a] tracking-widest uppercase mb-1">
                COMPUTING SOCIETY ORGANIZATION
            </p>

            <!-- 3. QR Attendance (bold and prominent product title) -->
            <h1 class="font-brand-display text-3xl sm:text-4xl lg:text-5xl font-black text-white tracking-tight mt-1">
                QR Attendance
            </h1>

            <!-- 4. Tagline: Innovating Today for a Better Community. -->
            <p class="text-xs sm:text-sm text-[#94A3B8] mt-2 font-medium">
                Innovating Today for a Better Community.
            </p>

            <!-- 5. Compact row: ComSoc Attendance System & V 2.5.1 -->
            <div class="flex items-center justify-between w-full max-w-[280px] sm:max-w-[320px] mx-auto text-xs sm:text-sm font-bold text-slate-300 mt-4 pt-3 border-t border-[#334155]/60 font-mono">
                <span>ComSoc Attendance System</span>
                <span class="text-[#df747a]">V 2.5.1</span>
            </div>

            <!-- 6. Developer: Rytzz G. Grieta -->
            <p class="text-xs sm:text-sm font-semibold text-slate-400 mt-2">
                Developer: Rytzz G. Grieta
            </p>
        </div>
    </aside>

    <!-- ======================================================== -->
    <!-- RIGHT AUTHENTICATION PANEL (Approx. 58.3% width on desktop)-->
    <!-- ======================================================== -->
    <main class="auth-content-main w-full md:w-7/12 flex-1 flex flex-col justify-start sm:justify-center items-center p-4 sm:p-8 lg:p-12 relative z-10 overflow-y-auto"
          x-data="{
              step: {{ $errors->hasAny(['signature', 'signature_consent']) ? 4 : ($errors->hasAny(['password', 'password_confirmation', 'terms']) ? 3 : ($errors->hasAny(['username', 'avatar_seed', 'avatar_style']) ? 2 : 1)) }},
              steps: [
                  { id: 1, number: 1, title: 'Student Information', shortTitle: 'Student Info', subtitle: 'Enter your institutional student details and enrolled program' },
                  { id: 2, number: 2, title: 'Avatar & Username', shortTitle: 'Avatar & Username', subtitle: 'Choose your Adventurer Avatar and set your unique public identity' },
                  { id: 3, number: 3, title: 'Account Security', shortTitle: 'Account Security', subtitle: 'Configure your password and review privacy policies' },
                  { id: 4, number: 4, title: 'Register Signature', shortTitle: 'Register Signature', subtitle: 'Provide your handwritten signature to be securely encrypted and reused for official attendance sheets' }
              ],
              get totalSteps() {
                  return this.steps.length;
              },
              get currentStepConfig() {
                  return this.steps.find(s => s.number === this.step) || this.steps[0];
              },
              submitting: false,
              showPass: false,
              showConfirm: false,
              studentId: '{{ addslashes(old('student_id', '')) }}',
              firstName: '{{ addslashes(old('first_name', '')) }}',
              lastName: '{{ addslashes(old('last_name', '')) }}',
              middleName: '{{ addslashes(old('middle_name', '')) }}',
              email: '{{ addslashes(old('email', '')) }}',
              program: '{{ addslashes(old('program', '')) }}',
              year: '{{ addslashes(old('year', '')) }}',
              block: '{{ addslashes(old('block', '')) }}',
              username: '{{ addslashes(old('username', '')) }}',
              avatarSeed: '{{ addslashes(old('avatar_seed', 'Felix')) }}',
              avatarStyle: 'adventurer',
              checkStatus: 'idle',
              checkMessage: '',
              debounceTimer: null,
              password: '',
              passwordConfirmation: '',
              get avatarPreviewUrl() {
                  return 'https://api.dicebear.com/9.x/adventurer/svg?seed=' + encodeURIComponent(this.avatarSeed || 'Felix');
              },
              selectAvatar(seed) {
                  this.avatarSeed = seed;
                  if (this.clientErrors.avatar_seed) {
                      this.clientErrors.avatar_seed = '';
                  }
              },
              onUsernameInput() {
                  this.clientErrors.username = '';
                  const u = (this.username || '').trim();
                  if (!u) {
                      this.checkStatus = 'idle';
                      this.checkMessage = '';
                      return;
                  }
                  if (u.length < 3 || u.length > 20) {
                      this.checkStatus = 'invalid';
                      this.checkMessage = 'Must be between 3 and 20 characters.';
                      return;
                  }
                  if (!/^[a-zA-Z0-9_]+$/.test(u)) {
                      this.checkStatus = 'invalid';
                      this.checkMessage = 'Only letters, numbers, and underscores allowed.';
                      return;
                  }
                  this.checkStatus = 'checking';
                  this.checkMessage = 'Checking availability...';
                  clearTimeout(this.debounceTimer);
                  this.debounceTimer = setTimeout(() => {
                      fetch('/signup/check-username?username=' + encodeURIComponent(u))
                          .then(res => res.json())
                          .then(data => {
                              if (data.available) {
                                  this.checkStatus = 'available';
                                  this.checkMessage = 'Username is available!';
                              } else {
                                  this.checkStatus = 'unavailable';
                                  this.checkMessage = data.message || 'Username is already taken.';
                              }
                          })
                          .catch(() => {
                              this.checkStatus = 'available';
                              this.checkMessage = 'Format valid';
                          });
                  }, 350);
              },
              termsAccepted: {{ old('terms') ? 'true' : 'false' }},
              clientErrors: {},

              // Step 4 Signature Pad State
              signaturePad: null,
              signatureData: '{{ addslashes(old('signature', '')) }}',
              signatureConsent: {{ old('signature_consent') ? 'true' : 'false' }},
              signaturePreview: null,
              showSignaturePreview: false,
              signatureRegisteredSuccess: false,
              _sigResizeListener: null,

              initSignaturePad() {
                  const canvas = this.$refs.signatureCanvas;
                  if (!canvas) return;

                  if (typeof SignaturePad === 'undefined' && window.SignaturePad) {
                      window.SignaturePad = window.SignaturePad;
                  }

                  if (!this.signaturePad) {
                      const PadConstructor = window.SignaturePad || (window.default && window.default.SignaturePad) || null;
                      if (!PadConstructor) {
                          // Dynamic import fallback or global check
                          return;
                      }

                      this.signaturePad = new PadConstructor(canvas, {
                          penColor: '#000000',
                          backgroundColor: 'rgba(0,0,0,0)',
                          minWidth: 0.8,
                          maxWidth: 2.4,
                      });

                      this.signaturePad.addEventListener('endStroke', () => {
                          this.syncSignature();
                      });

                      if (!this._sigResizeListener) {
                          this._sigResizeListener = () => this.resizeCanvas();
                          window.addEventListener('resize', this._sigResizeListener);
                          window.addEventListener('orientationchange', this._sigResizeListener);
                      }
                  }

                  this.resizeCanvas();

                  if (this.signatureData && this.signaturePad.isEmpty()) {
                      try {
                          this.signaturePad.fromDataURL(this.signatureData);
                      } catch (e) {}
                  }
              },

              resizeCanvas() {
                  const canvas = this.$refs.signatureCanvas;
                  if (!canvas || !canvas.offsetWidth || !this.signaturePad) return;
                  const saved = this.signaturePad.toData();
                  const ratio = Math.max(window.devicePixelRatio || 1, 1);
                  canvas.width = canvas.offsetWidth * ratio;
                  canvas.height = canvas.offsetHeight * ratio;
                  canvas.getContext('2d').scale(ratio, ratio);
                  this.signaturePad.clear();
                  if (saved.length) {
                      this.signaturePad.fromData(saved);
                  }
              },

              syncSignature() {
                  if (!this.signaturePad || this.signaturePad.isEmpty()) {
                      this.signatureData = '';
                      this.signaturePreview = null;
                      return;
                  }
                  const src = this.$refs.signatureCanvas;
                  const maxW = 1000;
                  const scale = Math.min(1, maxW / src.width);
                  const out = document.createElement('canvas');
                  out.width = Math.max(1, Math.round(src.width * scale));
                  out.height = Math.max(1, Math.round(src.height * scale));
                  out.getContext('2d').drawImage(src, 0, 0, out.width, out.height);
                  this.signatureData = out.toDataURL('image/png');
                  this.signaturePreview = this.signatureData;
                  if (this.clientErrors.signature) {
                      this.clientErrors.signature = '';
                  }
              },

              clearSignature() {
                  if (this.signaturePad) {
                      this.signaturePad.clear();
                  }
                  this.signatureData = '';
                  this.signaturePreview = null;
                  this.showSignaturePreview = false;
                  this.clientErrors.signature = '';
              },

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

                  const trimmedEmail = (this.email || '').trim();
                  if (!trimmedEmail) {
                      this.clientErrors.email = 'University email address is required.';
                      valid = false;
                  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(trimmedEmail)) {
                      this.clientErrors.email = 'Please provide a valid email address.';
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

              validateStep2() {
                  this.clientErrors = {};
                  let valid = true;

                  if (!this.avatarSeed) {
                      this.clientErrors.avatar_seed = 'Please select an Adventurer Avatar.';
                      valid = false;
                  }

                  const u = (this.username || '').trim();
                  if (!u) {
                      this.clientErrors.username = 'Username is required.';
                      valid = false;
                  } else if (u.length < 3 || u.length > 20) {
                      this.clientErrors.username = 'Username must be between 3 and 20 characters.';
                      valid = false;
                  } else if (!/^[a-zA-Z0-9_]+$/.test(u)) {
                      this.clientErrors.username = 'Username may only contain letters, numbers, and underscores.';
                      valid = false;
                  } else if (this.checkStatus === 'unavailable') {
                      this.clientErrors.username = this.checkMessage || 'This username is already taken.';
                      valid = false;
                  }

                  return valid;
              },

              goToStep3() {
                  if (this.validateStep2()) {
                      this.step = 3;
                      window.scrollTo({ top: 0, behavior: 'smooth' });
                  }
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

              validateStep3() {
                  this.clientErrors = {};
                  let valid = true;

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

              goToStep4() {
                  if (this.validateStep3()) {
                      this.step = 4;
                      window.scrollTo({ top: 0, behavior: 'smooth' });
                      this.$nextTick(() => {
                          this.initSignaturePad();
                      });
                  }
              },

              validateStep4() {
                  this.clientErrors = {};
                  let valid = true;

                  this.syncSignature();

                  if (!this.signatureData || (this.signaturePad && this.signaturePad.isEmpty())) {
                      this.clientErrors.signature = 'Your handwritten signature is required to complete registration.';
                      valid = false;
                  }

                  if (!this.signatureConsent) {
                      this.clientErrors.signature_consent = 'You must acknowledge the signature consent statement.';
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
                      this.step = 2;
                      return;
                  }
                  if (!this.validateStep3()) {
                      e.preventDefault();
                      this.step = 3;
                      return;
                  }
                  if (!this.validateStep4()) {
                      e.preventDefault();
                      this.step = 4;
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

        <div class="w-full max-w-2xl mx-auto my-auto py-4 sm:py-6">

            <div class="card shadow-2xl border border-slate-800/80 bg-[#172033]/95 backdrop-blur-md p-4 sm:p-6 md:p-8">
                <!-- Heading -->
                <div class="mb-5">
                    <h2 class="font-brand-display text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                        Sign Up
                    </h2>
                    <p class="text-xs sm:text-sm text-[#94A3B8] mt-1">
                        Student Registration: Create your student account to track attendance, earn EXP, and unlock rewards.
                    </p>
                </div>

                <!-- Registration Progress Navigation -->
                <nav aria-label="Registration Progress" class="mb-5">
                    <!-- Mobile Compact Progress Indicator (< 640px) -->
                    <div class="sm:hidden p-3 rounded-2xl bg-slate-900/60 border border-slate-800 shadow-sm space-y-2.5">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-[11px] font-mono uppercase tracking-wider text-slate-400 font-semibold"
                                  x-text="'STEP ' + step + ' OF ' + totalSteps"></span>
                            <span class="font-bold text-white text-xs font-mono"
                                  x-text="step + ' / ' + totalSteps"></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-[#560E0F] text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-sm"
                                  x-text="step"></span>
                            <span class="text-xs font-semibold text-white truncate"
                                  x-text="currentStepConfig.title"></span>
                        </div>
                        <!-- Progress bar rail -->
                        <div class="w-full bg-slate-800 rounded-full h-1.5 overflow-hidden">
                            <div class="bg-gradient-to-r from-[#560E0F] to-[#df747a] h-1.5 rounded-full transition-all duration-300"
                                 :style="'width: ' + ((step / totalSteps) * 100) + '%'"></div>
                        </div>
                    </div>

                    <!-- Desktop / Tablet Grid Progress Navigation (>= 640px) -->
                    <ol class="hidden sm:grid sm:grid-cols-4 gap-2">
                        @php
                            $wizardSteps = [
                                ['number' => 1, 'title' => 'Student Information'],
                                ['number' => 2, 'title' => 'Avatar & Username'],
                                ['number' => 3, 'title' => 'Account Security'],
                                ['number' => 4, 'title' => 'Register Signature'],
                            ];
                            $stepCount = count($wizardSteps);
                        @endphp
                        @foreach($wizardSteps as $ws)
                            <li class="flex items-start gap-2.5 p-2.5 rounded-xl border transition min-h-[58px]"
                                :class="step === {{ $ws['number'] }} ? 'bg-[#560E0F]/20 border-[#560E0F] text-white shadow-sm' : 'bg-slate-900/40 border-slate-800 text-slate-400'">
                                <span class="w-6 h-6 rounded-lg flex items-center justify-center font-bold text-xs shrink-0 mt-0.5"
                                      :class="step === {{ $ws['number'] }} ? 'bg-[#560E0F] text-white shadow' : (step > {{ $ws['number'] }} ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/40' : 'bg-slate-800 text-slate-400')">
                                    <template x-if="step > {{ $ws['number'] }}">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </template>
                                    <template x-if="step <= {{ $ws['number'] }}">
                                        <span>{{ $ws['number'] }}</span>
                                    </template>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-[9px] font-mono uppercase tracking-wider text-slate-400 leading-tight">STEP {{ $ws['number'] }} OF {{ $stepCount }}</p>
                                    <p class="text-xs font-semibold leading-snug mt-0.5 break-words"
                                       :class="step === {{ $ws['number'] }} ? 'text-white' : 'text-slate-300'">{{ $ws['title'] }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </nav>

                <!-- Global Validation Error Feedback -->
                @if($errors->any())
                <div class="alert-error mb-4 flex items-start gap-2 text-xs" role="alert">
                    <svg class="w-4 h-4 flex-shrink-0 mt-0.5 text-rose-400" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <p class="font-semibold text-white">Please correct the following errors:</p>
                        <ul class="list-disc list-inside mt-0.5 text-[11px] text-rose-300 space-y-0.5">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                @endif

                <form method="POST" action="{{ route('signup.store') }}" @submit="submitForm($event)" class="space-y-4" novalidate>
                    @csrf

                    <!-- ============================================== -->
                    <!-- STEP 1: Student Information                    -->
                    <!-- ============================================== -->
                    <div x-show="step === 1" x-transition:enter="transition ease-out duration-200" class="space-y-3.5">
                        <div class="border-b border-slate-800 pb-2 flex items-center justify-between">
                            <div>
                                <h3 class="font-brand-display text-sm sm:text-base font-bold text-white tracking-wide"
                                    x-text="'STEP 1 OF ' + totalSteps + ' — Student Information'"></h3>
                                <p class="text-xs text-[#94A3B8]">Enter your institutional student details and enrolled program</p>
                            </div>
                            <span class="text-xs font-mono text-slate-500 hidden sm:inline"
                                  x-text="'1 / ' + totalSteps"></span>
                        </div>

                        <!-- Student ID / Student Number -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label for="student_id" class="label mb-0">
                                    Student ID / Student Number <span class="text-rose-400">*</span>
                                </label>
                                <span class="text-xs font-mono text-slate-400 whitespace-nowrap" :class="studentIdDigits.length > 8 ? 'text-rose-400 font-bold' : ''">
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
                                   placeholder="Enter your student ID"
                                   aria-describedby="student-id-helper student-id-error"
                                   class="input @error('student_id') border-rose-500 @enderror"
                                   :class="clientErrors.student_id ? 'border-rose-500' : ''"
                                   required
                                   autofocus>
                            <p id="student-id-helper" class="text-[11px] text-slate-400 mt-1">Maximum 8 digits. Hyphens accepted per institutional ID format.</p>
                            <template x-if="clientErrors.student_id">
                                <p id="student-id-error" class="text-xs text-rose-400 mt-1 font-medium" x-text="clientErrors.student_id"></p>
                            </template>
                            @error('student_id')
                                <p class="text-xs text-rose-400 mt-1 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- First Name, Middle Name, Last Name Grid -->
                        <div class="academic-name-grid">
                            <div>
                                <label for="first_name" class="label mb-1">First Name <span class="text-rose-400">*</span></label>
                                <input type="text"
                                       name="first_name"
                                       id="first_name"
                                       x-model="firstName"
                                       @input="clientErrors.first_name = ''"
                                       autocomplete="given-name"
                                       placeholder="Enter your first name"
                                       class="input @error('first_name') border-rose-500 @enderror"
                                       :class="clientErrors.first_name ? 'border-rose-500' : ''"
                                       required>
                                <template x-if="clientErrors.first_name">
                                    <p class="text-xs text-rose-400 mt-1 font-medium" x-text="clientErrors.first_name"></p>
                                </template>
                                @error('first_name')
                                    <p class="text-xs text-rose-400 mt-1 font-medium">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="middle_name" class="label mb-1">Middle Name <span class="text-slate-500 font-normal">(Optional)</span></label>
                                <input type="text"
                                       name="middle_name"
                                       id="middle_name"
                                       x-model="middleName"
                                       autocomplete="additional-name"
                                       placeholder="Enter your middle name"
                                       class="input @error('middle_name') border-rose-500 @enderror">
                                @error('middle_name')
                                    <p class="text-xs text-rose-400 mt-1 font-medium">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="last_name" class="label mb-1">Last Name <span class="text-rose-400">*</span></label>
                                <input type="text"
                                       name="last_name"
                                       id="last_name"
                                       x-model="lastName"
                                       @input="clientErrors.last_name = ''"
                                       autocomplete="family-name"
                                       placeholder="Enter your last name"
                                       class="input @error('last_name') border-rose-500 @enderror"
                                       :class="clientErrors.last_name ? 'border-rose-500' : ''"
                                       required>
                                <template x-if="clientErrors.last_name">
                                    <p class="text-xs text-rose-400 mt-1 font-medium" x-text="clientErrors.last_name"></p>
                                </template>
                                @error('last_name')
                                    <p class="text-xs text-rose-400 mt-1 font-medium">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- University Email Address -->
                        <div>
                            <label for="email" class="label mb-1">
                                University Email <span class="text-rose-400">*</span>
                            </label>
                            <input type="email"
                                   name="email"
                                   id="email"
                                   x-model="email"
                                   @input="clientErrors.email = ''"
                                   autocomplete="email"
                                   placeholder="Enter your university email"
                                   class="input @error('email') border-rose-500 @enderror"
                                   :class="clientErrors.email ? 'border-rose-500' : ''"
                                   required>
                            <p class="text-[11px] text-slate-400 mt-1">Must be your official university student email (<code class="font-mono text-slate-300">@sorsu.edu.ph</code>).</p>
                            <template x-if="clientErrors.email">
                                <p class="text-xs text-rose-400 mt-1 font-medium" x-text="clientErrors.email"></p>
                            </template>
                            @error('email')
                                <p class="text-xs text-rose-400 mt-1 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Academic Program Dropdown -->
                        <div>
                            <label for="program" class="label mb-1">Academic Program <span class="text-rose-400">*</span></label>
                            <x-custom-dropdown
                                name="program"
                                id="program"
                                :options="$programs"
                                :value="old('program')"
                                placeholder="Select your program"
                                required="true" />
                            <template x-if="clientErrors.program">
                                <p class="text-xs text-rose-400 mt-1 font-medium" x-text="clientErrors.program"></p>
                            </template>
                            @error('program')
                                <p class="text-xs text-rose-400 mt-1 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Year Level & Block Section Grid -->
                        <div class="academic-cohort-grid">
                            <div>
                                <label for="year" class="label mb-1">Year Level <span class="text-rose-400">*</span></label>
                                <x-custom-dropdown
                                    name="year"
                                    id="year"
                                    :options="$years"
                                    :value="old('year')"
                                    placeholder="Select your year level"
                                    required="true" />
                                <template x-if="clientErrors.year">
                                    <p class="text-xs text-rose-400 mt-1 font-medium" x-text="clientErrors.year"></p>
                                </template>
                                @error('year')
                                    <p class="text-xs text-rose-400 mt-1 font-medium">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="block" class="label mb-1">Block / Section <span class="text-rose-400">*</span></label>
                                <x-custom-dropdown
                                    name="block"
                                    id="block"
                                    :options="$blocks"
                                    :value="old('block')"
                                    placeholder="Select your block"
                                    required="true" />
                                <template x-if="clientErrors.block">
                                    <p class="text-xs text-rose-400 mt-1 font-medium" x-text="clientErrors.block"></p>
                                </template>
                                @error('block')
                                    <p class="text-xs text-rose-400 mt-1 font-medium">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Step 1 Continue Action -->
                        <div class="pt-5 sm:pt-6 flex justify-end">
                            <button type="button"
                                    @click="goToStep2()"
                                    class="btn-primary w-full sm:w-auto min-w-[140px] justify-center py-2.5 sm:py-3 font-semibold text-xs sm:text-sm tracking-wide shadow-xl flex items-center gap-2">
                                <span>Continue</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- ============================================== -->
                    <!-- STEP 2: Avatar & Username                      -->
                    <!-- ============================================== -->
                    <div x-show="step === 2" x-cloak x-transition:enter="transition ease-out duration-200" class="space-y-4">
                        <div class="border-b border-slate-800 pb-2 flex items-center justify-between">
                            <div>
                                <h3 class="font-brand-display text-sm sm:text-base font-bold text-white tracking-wide"
                                    x-text="'STEP 2 OF ' + totalSteps + ' — Avatar & Username'"></h3>
                                <p class="text-xs text-[#94A3B8]">Choose your Adventurer Avatar and set your unique public identity</p>
                            </div>
                            <span class="text-xs font-mono text-slate-500 hidden sm:inline"
                                  x-text="'2 / ' + totalSteps"></span>
                        </div>

                        <!-- Public Identity Live Preview Card -->
                        <div class="p-3.5 sm:p-4 rounded-2xl bg-gradient-to-r from-slate-950 via-[#172033] to-slate-950 border border-slate-800/90 shadow-inner flex items-center gap-3.5 sm:gap-4">
                            <div class="relative shrink-0">
                                <img :src="avatarPreviewUrl" alt="Avatar Preview" class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-slate-900 border-2 border-[#df747a]/60 p-1 shadow-lg object-contain">
                                <span class="absolute -bottom-1 -right-1 px-1.5 py-0.5 rounded-full bg-[#560E0F] border border-[#df747a] text-[9px] font-bold text-white uppercase tracking-wider">
                                    Live
                                </span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 rounded-md bg-[#df747a]/15 text-[#df747a] text-[10px] font-bold tracking-wide uppercase">
                                        Public Identity
                                    </span>
                                </div>
                                <h4 class="text-base sm:text-lg font-bold text-white tracking-tight truncate mt-1">
                                    @<span x-text="username || 'username'"></span>
                                </h4>
                                <p class="text-xs text-slate-400 truncate">
                                    <span x-text="(firstName + ' ' + lastName).trim() || 'Student Adventurer'"></span>
                                </p>
                            </div>
                        </div>

                        <!-- Choose Your Adventurer Avatar -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="label mb-0">
                                    Choose Your Adventurer Avatar <span class="text-rose-400">*</span>
                                </label>
                                <span class="text-xs text-slate-400">16 official styles</span>
                            </div>
                            <div class="avatar-grid-responsive p-3 bg-slate-950/70 rounded-2xl border border-slate-800/90">
                                @foreach($avatarPresets as $preset)
                                <button type="button"
                                        @click="selectAvatar('{{ $preset['seed'] }}')"
                                        :aria-label="'Select {{ $preset['name'] }}'"
                                        class="relative aspect-square rounded-xl p-1.5 transition-all duration-200 border flex flex-col items-center justify-center focus:outline-none"
                                        :class="avatarSeed === '{{ $preset['seed'] }}' ? 'bg-[#560E0F]/40 border-[#df747a] ring-2 ring-[#df747a]/70 shadow-lg scale-105' : 'bg-slate-900/60 border-slate-800 hover:border-slate-700 hover:bg-slate-800/70'">
                                    <img src="{{ $preset['url'] }}" alt="{{ $preset['name'] }}" class="w-full h-full object-contain" loading="lazy">
                                    <template x-if="avatarSeed === '{{ $preset['seed'] }}'">
                                        <span class="absolute -top-1.5 -right-1.5 w-4 h-4 rounded-full bg-[#df747a] text-slate-950 flex items-center justify-center shadow">
                                            <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                        </span>
                                    </template>
                                </button>
                                @endforeach
                            </div>
                            <input type="hidden" name="avatar_seed" :value="avatarSeed">
                            <input type="hidden" name="avatar_style" value="adventurer">
                            <template x-if="clientErrors.avatar_seed">
                                <p class="text-xs text-rose-400 mt-1 font-medium" x-text="clientErrors.avatar_seed"></p>
                            </template>
                            @error('avatar_seed')
                                <p class="text-xs text-rose-400 mt-1 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Username Input -->
                        <div>
                            <label for="username" class="label mb-1">
                                Username <span class="text-rose-400">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 font-mono font-bold text-sm">@</span>
                                <input type="text"
                                       name="username"
                                       id="username"
                                       x-model="username"
                                       @input="onUsernameInput()"
                                       placeholder="Enter your username"
                                       autocomplete="username"
                                       maxlength="20"
                                       class="input pl-8 pr-10 @error('username') border-rose-500 @enderror"
                                       :class="clientErrors.username ? 'border-rose-500' : (checkStatus === 'available' ? 'border-emerald-500' : (checkStatus === 'unavailable' || checkStatus === 'invalid' ? 'border-rose-500' : ''))"
                                       required>
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <template x-if="checkStatus === 'checking'">
                                        <svg class="animate-spin h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                        </svg>
                                    </template>
                                    <template x-if="checkStatus === 'available'">
                                        <svg class="h-4 w-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </template>
                                    <template x-if="checkStatus === 'unavailable' || checkStatus === 'invalid'">
                                        <svg class="h-4 w-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </template>
                                </div>
                            </div>
                            <div class="mt-1 flex items-center justify-between text-[11px]">
                                <span class="text-slate-400">3-20 characters: letters, numbers, and underscores.</span>
                                <span x-show="checkMessage"
                                      :class="checkStatus === 'available' ? 'text-emerald-400' : (checkStatus === 'checking' ? 'text-slate-400' : 'text-rose-400')"
                                      x-text="checkMessage"
                                      class="font-medium"></span>
                            </div>
                            <template x-if="clientErrors.username">
                                <p class="text-xs text-rose-400 mt-1 font-medium" x-text="clientErrors.username"></p>
                            </template>
                            @error('username')
                                <p class="text-xs text-rose-400 mt-1 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Step 2 Actions: Back and Continue -->
                        <div class="pt-5 sm:pt-6 flex flex-col-reverse sm:flex-row items-center justify-between gap-3">
                            <button type="button"
                                    @click="goToStep1()"
                                    class="btn-secondary w-full sm:w-auto min-w-[110px] justify-center py-2.5 sm:py-3 text-xs sm:text-sm flex items-center gap-1.5 font-semibold">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                                </svg>
                                <span>Back</span>
                            </button>

                            <button type="button"
                                    @click="goToStep3()"
                                    class="btn-primary w-full sm:w-auto min-w-[140px] justify-center py-2.5 sm:py-3 font-semibold text-xs sm:text-sm tracking-wide shadow-xl flex items-center gap-2">
                                <span>Continue</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- ============================================== -->
                    <!-- STEP 3: Account Security                       -->
                    <!-- ============================================== -->
                    <div x-show="step === 3" x-cloak x-transition:enter="transition ease-out duration-200" class="space-y-4">
                        <div class="border-b border-slate-800 pb-2 flex items-center justify-between">
                            <div>
                                <h3 class="font-brand-display text-sm sm:text-base font-bold text-white tracking-wide"
                                    x-text="'STEP 3 OF ' + totalSteps + ' — Account Security'"></h3>
                                <p class="text-xs text-[#94A3B8]">Configure your password and review privacy policies</p>
                            </div>
                            <span class="text-xs font-mono text-slate-500 hidden sm:inline"
                                  x-text="'3 / ' + totalSteps"></span>
                        </div>

                        <!-- Passwords Grid with Visibility Toggles -->
                        <div class="security-passwords-grid">
                            <div>
                                <label for="password" class="label mb-1">Password <span class="text-rose-400">*</span></label>
                                <div class="relative">
                                    <input :type="showPass ? 'text' : 'password'"
                                           name="password"
                                           id="password"
                                           x-model="password"
                                           @input="clientErrors.password = ''"
                                           autocomplete="new-password"
                                           aria-describedby="password-rules password-strength-live"
                                           placeholder="Enter your password"
                                           class="input pr-10 @error('password') border-rose-500 @enderror"
                                           :class="clientErrors.password ? 'border-rose-500' : ''"
                                           required>
                                    <button type="button"
                                            @click="showPass = !showPass"
                                            :aria-label="showPass ? 'Hide password' : 'Show password'"
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-white transition">
                                        <template x-if="!showPass">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </template>
                                        <template x-if="showPass">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                            </svg>
                                        </template>
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label for="password_confirmation" class="label mb-1">Confirm Password <span class="text-rose-400">*</span></label>
                                <div class="relative">
                                    <input :type="showConfirm ? 'text' : 'password'"
                                           name="password_confirmation"
                                           id="password_confirmation"
                                           x-model="passwordConfirmation"
                                           @input="clientErrors.password_confirmation = ''"
                                           autocomplete="new-password"
                                           placeholder="Confirm your password"
                                           class="input pr-10"
                                           :class="clientErrors.password_confirmation ? 'border-rose-500' : ''"
                                           required>
                                    <button type="button"
                                            @click="showConfirm = !showConfirm"
                                            :aria-label="showConfirm ? 'Hide password confirmation' : 'Show password confirmation'"
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-white transition">
                                        <template x-if="!showConfirm">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </template>
                                        <template x-if="showConfirm">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                            </svg>
                                        </template>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Password Strength & Rules -->
                        <div class="p-3 rounded-xl bg-slate-950/70 border border-slate-800/90 space-y-1.5">
                            <div class="flex items-center justify-between text-xs">
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

                            <ul id="password-rules" class="flex flex-wrap gap-x-4 gap-y-0.5 text-[10.5px] text-slate-400">
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
                            <p class="text-xs text-rose-400 mt-1 font-medium" x-text="clientErrors.password"></p>
                        </template>
                        @error('password')
                            <p class="text-xs text-rose-400 mt-1 font-medium">{{ $message }}</p>
                        @enderror
                        <template x-if="clientErrors.password_confirmation">
                            <p class="text-xs text-rose-400 mt-1 font-medium" x-text="clientErrors.password_confirmation"></p>
                        </template>

                        <!-- Privacy Notice & Consent -->
                        <div class="p-3.5 rounded-xl bg-slate-950/80 border border-slate-800 text-[11px] text-slate-300 space-y-1.5">
                            <div class="flex items-center justify-between text-white font-semibold">
                                <div class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                    <span>COMSOC Privacy Notice & Consent</span>
                                </div>
                                <a href="{{ route('privacy') }}" target="_blank" class="text-[10px] text-[#df747a] hover:text-white underline font-semibold transition">
                                    View Full Notice
                                </a>
                            </div>
                            <p class="text-[10.5px] text-slate-400 leading-snug">
                                <strong>Purpose &amp; Scope:</strong> COMSOC collects identity info solely for student verification, attendance, credentials, and reward operations. We do not sell or disclose data to commercial third parties.
                            </p>
                        </div>

                        <!-- Terms Agreement Checkbox -->
                        <div>
                            <label class="flex items-start gap-2.5 text-xs text-slate-300 cursor-pointer p-3 rounded-xl bg-slate-950/60 border border-slate-800 hover:border-slate-700 transition">
                                <input type="checkbox"
                                       name="terms"
                                       value="1"
                                       x-model="termsAccepted"
                                       @change="clientErrors.terms = ''"
                                       required
                                       class="w-4 h-4 mt-0.5 rounded bg-slate-900 border-slate-700 text-[#560E0F] focus:ring-[#560E0F]">
                                <span>I have read and agree to the <a href="{{ route('privacy') }}" target="_blank" class="text-[#df747a] hover:text-white font-semibold underline transition">COMSOC Privacy Notice</a> and confirm all information is accurate.</span>
                            </label>
                            <template x-if="clientErrors.terms">
                                <p class="text-xs text-rose-400 mt-1 font-medium" x-text="clientErrors.terms"></p>
                            </template>
                            @error('terms')
                                <p class="text-xs text-rose-400 mt-1 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Step 3 Actions: Back and Continue to Step 4 -->
                        <div class="pt-5 sm:pt-6 flex flex-col-reverse sm:flex-row items-center justify-between gap-3">
                            <button type="button"
                                    @click="goToStep2()"
                                    class="btn-secondary w-full sm:w-auto min-w-[110px] justify-center py-2.5 sm:py-3 text-xs sm:text-sm flex items-center gap-1.5 font-semibold">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                                </svg>
                                <span>Back</span>
                            </button>

                            <button type="button"
                                    @click="goToStep4()"
                                    class="btn-primary w-full sm:w-auto min-w-[160px] justify-center py-2.5 sm:py-3 font-semibold text-xs sm:text-sm tracking-wide shadow-xl flex items-center gap-2">
                                <span>Next: Register Signature</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- ============================================== -->
                    <!-- STEP 4: Register Your Signature                -->
                    <!-- ============================================== -->
                    <div x-show="step === 4" x-cloak x-transition:enter="transition ease-out duration-200" class="space-y-4">
                        <div class="border-b border-slate-800 pb-2 flex items-center justify-between">
                            <div>
                                <h3 class="font-brand-display text-sm sm:text-base font-bold text-white tracking-wide"
                                    x-text="'STEP 4 OF ' + totalSteps + ' — Register Your Signature'"></h3>
                                <p class="text-xs text-[#94A3B8]">Provide your handwritten signature to be securely encrypted and reused for official attendance sheets</p>
                            </div>
                            <span class="text-xs font-mono text-slate-500 hidden sm:inline"
                                  x-text="'4 / ' + totalSteps"></span>
                        </div>

                        <!-- Signature Canvas Card -->
                        <div class="p-4 rounded-xl bg-slate-950/70 border border-slate-800 space-y-3">
                            <div class="flex items-center justify-between">
                                <label for="signature-canvas" class="text-xs font-semibold text-slate-300 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-[#df747a]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                    <span>Draw Your Handwritten Signature <span class="text-rose-400">*</span></span>
                                    <span class="text-[11px] font-normal text-slate-400 hidden sm:inline">(mouse, touchscreen, or stylus)</span>
                                </label>

                                <div class="flex items-center gap-2">
                                    <button type="button"
                                            @click="clearSignature()"
                                            :disabled="submitting"
                                            class="btn-secondary text-[11px] py-1 px-2.5 flex items-center gap-1 font-medium">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        <span>Clear & Redraw</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Canvas Container -->
                            <div class="relative rounded-xl bg-white border-2 border-dashed border-slate-700 overflow-hidden shadow-inner"
                                 :class="clientErrors.signature ? 'border-rose-500' : ''">
                                <canvas id="signature-canvas"
                                        x-ref="signatureCanvas"
                                        role="img"
                                        aria-label="Signature drawing area"
                                        class="w-full block cursor-crosshair"
                                        style="height: 180px; touch-action: none; background: #ffffff;"></canvas>

                                <div class="absolute bottom-2 right-3 pointer-events-none select-none text-[10px] text-slate-400 font-mono opacity-60">
                                    Sign above this line
                                </div>
                            </div>

                            <div class="flex items-center justify-between text-[11px] text-slate-400">
                                <span>Please sign neatly within the white drawing area.</span>
                                <button type="button"
                                        x-show="signatureData"
                                        @click="showSignaturePreview = !showSignaturePreview"
                                        class="text-[#df747a] hover:text-white underline font-semibold transition">
                                    <span x-text="showSignaturePreview ? 'Hide Preview' : 'Show Preview'"></span>
                                </button>
                            </div>

                            <!-- Signature Preview Section -->
                            <div x-show="showSignaturePreview && signaturePreview" x-cloak class="p-3 rounded-lg bg-slate-900 border border-slate-800 space-y-1">
                                <p class="text-[11px] font-semibold text-slate-300">Signature Preview (Decrypted In-Memory Only):</p>
                                <div class="inline-block p-2 rounded-lg bg-white">
                                    <img :src="signaturePreview" alt="Decrypted Signature Preview" class="max-h-20 object-contain">
                                </div>
                            </div>

                            <!-- Validation Feedback for Signature Canvas -->
                            <template x-if="clientErrors.signature">
                                <p class="text-xs text-rose-400 mt-1 font-medium" x-text="clientErrors.signature"></p>
                            </template>
                            @error('signature')
                                <p class="text-xs text-rose-400 mt-1 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Signature Consent Statement -->
                        <div class="p-3.5 rounded-xl bg-slate-950/80 border border-slate-800 text-[11px] text-slate-300 space-y-2">
                            <div class="flex items-center gap-1.5 text-white font-semibold">
                                <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                                <span>Official Signature Authorization &amp; Consent</span>
                            </div>

                            <p class="text-[10.5px] text-slate-300 leading-relaxed italic border-l-2 border-[#560E0F] pl-3 py-0.5">
                                "I confirm that this is my handwritten signature and authorize COMSOC Hub to securely store and reuse it for generating official Computing Society attendance sheets and related organizational records. I understand that I may update my registered signature, subject to the system's version history and record-retention policies."
                            </p>

                            <label class="flex items-start gap-2.5 text-xs text-slate-300 cursor-pointer pt-1">
                                <input type="checkbox"
                                       name="signature_consent"
                                       id="signature_consent"
                                       value="1"
                                       x-model="signatureConsent"
                                       @change="clientErrors.signature_consent = ''"
                                       required
                                       class="w-4 h-4 mt-0.5 rounded bg-slate-900 border-slate-700 text-[#560E0F] focus:ring-[#560E0F]">
                                <span class="font-medium text-slate-200">
                                    I have read, understood, and explicitly agree to the signature authorization and consent terms above. <span class="text-rose-400">*</span>
                                </span>
                            </label>

                            <template x-if="clientErrors.signature_consent">
                                <p class="text-xs text-rose-400 mt-1 font-medium" x-text="clientErrors.signature_consent"></p>
                            </template>
                            @error('signature_consent')
                                <p class="text-xs text-rose-400 mt-1 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Success status indicator when signature is valid and confirmed -->
                        <div x-show="signatureData && signatureConsent" x-cloak class="p-3 rounded-xl bg-emerald-950/40 border border-emerald-500/30 text-emerald-300 text-xs flex items-center gap-2">
                            <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Your signature is drawn and consent is acknowledged. It will be securely encrypted upon submission.</span>
                        </div>

                        <!-- Hidden Signature Form Field -->
                        <input type="hidden" name="signature" :value="signatureData">

                        <!-- Step 4 Actions: Back to Step 3 and Final Account Creation -->
                        <div class="pt-5 sm:pt-6 flex flex-col-reverse sm:flex-row items-center justify-between gap-3">
                            <button type="button"
                                    @click="step = 3; window.scrollTo({ top: 0, behavior: 'smooth' });"
                                    :disabled="submitting"
                                    class="btn-secondary w-full sm:w-auto min-w-[110px] justify-center py-2.5 sm:py-3 text-xs sm:text-sm flex items-center gap-1.5 font-semibold">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                                </svg>
                                <span>Back</span>
                            </button>

                            <button type="submit"
                                    :disabled="submitting"
                                    class="btn-primary w-full sm:w-auto min-w-[160px] justify-center py-2.5 sm:py-3 font-semibold text-xs sm:text-sm tracking-wide transition flex items-center gap-2 shadow-xl">
                                <span x-show="!submitting">Create Account</span>
                                <span x-show="submitting" class="flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                    <span>Creating account & encrypting signature...</span>
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Bottom Navigation: Log In Link -->
                    <div class="text-center pt-4 border-t border-[#334155] mt-6">
                        <p class="text-xs text-[#94A3B8]">
                            Already have an account?
                            <a href="{{ route('login') }}" class="text-[#df747a] hover:text-white font-semibold underline transition">
                                Log In to Account
                            </a>
                        </p>
                    </div>
                </form>
            </div>

        </div>
    </main>

</body>
</html>
