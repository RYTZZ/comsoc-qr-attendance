<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('dashboard') }}" class="text-xs text-[#df747a] hover:text-white transition flex items-center gap-1 mb-1 font-semibold">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                    <span>Back to Dashboard</span>
                </a>
                <h1 class="text-2xl font-extrabold text-[#F8FAFC] font-brand-display tracking-tight">
                    {{ $event->evaluation_snapshot['template_title'] ?? ($event->evaluationTemplate?->title ?? 'Mandatory Event Evaluation') }}
                </h1>
                <p class="text-xs text-[#94A3B8]">
                    {{ $event->name }} • {{ $event->event_date?->format('F d, Y') }}
                </p>
            </div>
            <div>
                <span class="px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider {{ $existingEvaluation ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30' : 'bg-burgundy-deep text-[#df747a] border border-[#8F3033]/60' }}">
                    {{ $existingEvaluation ? 'Completed' : 'Required for Afternoon Time-Out' }}
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-[#0B1120] text-[#F8FAFC]">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs sm:text-sm flex items-start gap-3 shadow-lg">
                    <i data-lucide="check-circle" class="w-5 h-5 text-emerald-400 shrink-0 mt-0.5"></i>
                    <div>
                        <p class="font-bold text-white text-sm">Evaluation Completed Successfully!</p>
                        <p class="mt-1">{{ session('success') }}</p>
                        <div class="mt-3 flex items-center gap-2">
                            <a href="{{ route('member.dynamic_qr') }}" class="btn-primary text-xs py-1.5 px-3 flex items-center gap-1.5 shadow-md">
                                <i data-lucide="qr-code" class="w-4 h-4"></i>
                                <span>Show My Dynamic QR Pass</span>
                            </a>
                            <a href="{{ route('dashboard') }}" class="btn-secondary text-xs py-1.5 px-3">
                                Return to Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            @if(session('info'))
                <div class="p-4 rounded-2xl bg-blue-500/10 border border-blue-500/30 text-blue-300 text-xs sm:text-sm flex items-start gap-3">
                    <i data-lucide="info" class="w-5 h-5 text-blue-400 shrink-0 mt-0.5"></i>
                    <div>
                        <p class="font-bold text-white text-sm">Evaluation Already Submitted</p>
                        <p class="mt-1">{{ session('info') }}</p>
                    </div>
                </div>
            @endif

            @if($errors->any())
                <div class="p-4 rounded-2xl bg-red-500/10 border border-red-500/30 text-red-300 text-xs space-y-1">
                    <p class="font-bold text-red-400 flex items-center gap-1.5">
                        <i data-lucide="alert-triangle" class="w-4 h-4 shrink-0"></i>
                        <span>Please answer all required questions:</span>
                    </p>
                    <ul class="list-disc list-inside space-y-0.5 pl-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Attendance Requirement Warning Banner -->
            <div class="p-4 rounded-2xl bg-[#172033] border border-[#8F3033]/60 shadow-xl flex items-start gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-burgundy-deep border border-[#8F3033]/60 flex items-center justify-center text-[#df747a] shrink-0 shadow">
                    <i data-lucide="shield-alert" class="w-5 h-5"></i>
                </div>
                <div class="space-y-1">
                    <h3 class="text-sm font-bold text-white font-brand-display">Mandatory Afternoon Attendance Policy</h3>
                    <p class="text-xs text-slate-300 leading-relaxed">
                        In accordance with COMSOC regulations, completing this event evaluation is required before your <strong class="text-white">Afternoon Time-Out</strong> attendance scan can be recorded.
                    </p>
                    @if($afternoonOutSession)
                    <p class="text-[11px] text-[#94A3B8]">
                        Afternoon Time-Out Window: <span class="font-mono text-white font-bold">{{ $afternoonOutSession->opens_at ? \Carbon\Carbon::parse($afternoonOutSession->opens_at)->format('g:i A') : 'TBA' }}</span> to <span class="font-mono text-white font-bold">{{ $afternoonOutSession->closes_at ? \Carbon\Carbon::parse($afternoonOutSession->closes_at)->format('g:i A') : 'TBA' }}</span>.
                        You may submit this evaluation in advance; once submitted, present your QR code during the time-out window.
                    </p>
                    @endif
                </div>
            </div>

            @if($existingEvaluation)
                <!-- Completed Summary Card -->
                <div class="bg-[#172033] border border-emerald-500/30 rounded-3xl p-6 shadow-xl space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-[#334155]">
                        <div class="flex items-center gap-2">
                            <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-400"></i>
                            <h2 class="text-base font-bold text-white font-brand-display">Your Evaluation is Saved</h2>
                        </div>
                        <span class="text-xs text-[#94A3B8] font-mono">
                            Submitted {{ $existingEvaluation->submitted_at?->diffForHumans() }}
                        </span>
                    </div>

                    <p class="text-xs text-slate-300">
                        Your response has been permanently saved in the system. When the Afternoon Time-Out checkpoint opens, please present your Dynamic QR Code to the event organizer to finalize your attendance record.
                    </p>

                    <div class="pt-2 flex flex-wrap items-center gap-3">
                        <a href="{{ route('member.dynamic_qr') }}" class="btn-primary text-xs py-2 px-4 flex items-center gap-2 shadow-lg">
                            <i data-lucide="qr-code" class="w-4 h-4"></i>
                            <span>Show My Dynamic QR Pass</span>
                        </a>
                        <a href="{{ route('dashboard') }}" class="btn-secondary text-xs py-2 px-4">
                            Back to Dashboard
                        </a>
                    </div>
                </div>
            @else
                <!-- Active Evaluation Form -->
                <form method="POST" action="{{ route('events.evaluation.store', $event) }}" class="space-y-6">
                    @csrf

                    @foreach($questions as $idx => $q)
                    <div class="bg-[#172033] border border-[#334155] hover:border-[#8F3033]/60 transition rounded-3xl p-5 sm:p-6 shadow-xl space-y-3">
                        <div class="flex items-start gap-3">
                            <span class="w-7 h-7 rounded-xl bg-burgundy-deep text-[#df747a] border border-[#8F3033]/60 text-xs font-bold flex items-center justify-center font-mono shrink-0 mt-0.5 shadow-sm">
                                {{ $idx + 1 }}
                            </span>
                            <div class="flex-1">
                                <h3 class="text-sm sm:text-base font-bold text-white font-brand-display">
                                    {{ $q['question_text'] }}
                                    @if($q['is_required'])
                                        <span class="text-rose-400 font-bold ml-1" title="Required question">*</span>
                                    @endif
                                </h3>
                                @if(!$q['is_required'])
                                    <span class="text-[11px] text-[#94A3B8] italic">(Optional)</span>
                                @endif
                            </div>
                        </div>

                        <div class="pt-2 pl-10">
                            @if($q['question_type'] === 'rating')
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between text-xs text-[#94A3B8] max-w-sm">
                                        <span>{{ $q['rating_min_label'] ?: 'Poor' }} ({{ $q['rating_min'] ?? 1 }})</span>
                                        <span>{{ $q['rating_max_label'] ?: 'Excellent' }} ({{ $q['rating_max'] ?? 5 }})</span>
                                    </div>
                                    <div class="flex items-center gap-2 sm:gap-3">
                                        @for($i = ($q['rating_min'] ?? 1); $i <= ($q['rating_max'] ?? 5); $i++)
                                        <label class="cursor-pointer">
                                            <input type="radio"
                                                   name="answers[{{ $q['id'] }}]"
                                                   value="{{ $i }}"
                                                   {{ old('answers.' . $q['id']) == $i ? 'checked' : '' }}
                                                   {{ $q['is_required'] ? 'required' : '' }}
                                                   class="sr-only peer">
                                            <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-2xl border border-[#334155] bg-[#0B1120] hover:bg-[#1E293B] peer-checked:bg-burgundy-deep peer-checked:border-[#df747a] peer-checked:text-white peer-checked:ring-2 peer-checked:ring-[#df747a]/50 text-slate-300 flex items-center justify-center font-bold text-sm transition shadow-sm">
                                                {{ $i }}
                                            </div>
                                        </label>
                                        @endfor
                                    </div>
                                </div>
                            @elseif($q['question_type'] === 'single_choice')
                                <div class="space-y-2 max-w-lg">
                                    @foreach($q['options'] ?? [] as $opt)
                                    <label class="flex items-center gap-3 p-3 rounded-xl bg-[#0B1120] hover:bg-[#1E293B] border border-[#334155] cursor-pointer transition">
                                        <input type="radio"
                                               name="answers[{{ $q['id'] }}]"
                                               value="{{ $opt }}"
                                               {{ old('answers.' . $q['id']) === $opt ? 'checked' : '' }}
                                               {{ $q['is_required'] ? 'required' : '' }}
                                               class="rounded-full bg-slate-900 border-slate-700 text-[#df747a] focus:ring-[#8F3033]">
                                        <span class="text-xs sm:text-sm text-slate-200">{{ $opt }}</span>
                                    </label>
                                    @endforeach
                                </div>
                            @elseif($q['question_type'] === 'multiple_choice')
                                <div class="space-y-2 max-w-lg">
                                    @foreach($q['options'] ?? [] as $opt)
                                    <label class="flex items-center gap-3 p-3 rounded-xl bg-[#0B1120] hover:bg-[#1E293B] border border-[#334155] cursor-pointer transition">
                                        <input type="checkbox"
                                               name="answers[{{ $q['id'] }}][]"
                                               value="{{ $opt }}"
                                               {{ is_array(old('answers.' . $q['id'])) && in_array($opt, old('answers.' . $q['id'])) ? 'checked' : '' }}
                                               class="rounded bg-slate-900 border-slate-700 text-[#df747a] focus:ring-[#8F3033]">
                                        <span class="text-xs sm:text-sm text-slate-200">{{ $opt }}</span>
                                    </label>
                                    @endforeach
                                </div>
                            @elseif($q['question_type'] === 'short_text')
                                <input type="text"
                                       name="answers[{{ $q['id'] }}]"
                                       value="{{ old('answers.' . $q['id']) }}"
                                       maxlength="255"
                                       placeholder="Your response..."
                                       {{ $q['is_required'] ? 'required' : '' }}
                                       class="w-full bg-[#0B1120] border border-[#334155] focus:border-[#df747a] focus:ring-1 focus:ring-[#df747a] rounded-xl px-3.5 py-2.5 text-xs sm:text-sm text-white placeholder-slate-500 outline-none transition">
                            @elseif($q['question_type'] === 'long_text')
                                <textarea name="answers[{{ $q['id'] }}]"
                                          rows="3"
                                          maxlength="2000"
                                          placeholder="Share your thoughts or recommendations..."
                                          {{ $q['is_required'] ? 'required' : '' }}
                                          class="w-full bg-[#0B1120] border border-[#334155] focus:border-[#df747a] focus:ring-1 focus:ring-[#df747a] rounded-xl px-3.5 py-2.5 text-xs sm:text-sm text-white placeholder-slate-500 outline-none transition resize-none">{{ old('answers.' . $q['id']) }}</textarea>
                            @endif
                        </div>
                    </div>
                    @endforeach

                    <div class="bg-[#172033] border border-[#334155] rounded-3xl p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-xl">
                        <div>
                            <p class="text-xs text-slate-300 font-semibold">Ready to submit?</p>
                            <p class="text-[11px] text-[#94A3B8]">Once submitted, you will be instructed to present your Dynamic QR code for Afternoon Time-Out.</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <a href="{{ route('dashboard') }}" class="btn-secondary text-xs py-2.5 px-4">Cancel</a>
                            <button type="submit" class="btn-primary text-xs py-2.5 px-5 shadow-lg flex items-center gap-2">
                                <i data-lucide="send" class="w-3.5 h-3.5"></i>
                                <span>Submit Evaluation</span>
                            </button>
                        </div>
                    </div>
                </form>
            @endif

        </div>
    </div>
</x-app-layout>
