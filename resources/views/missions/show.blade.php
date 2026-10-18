<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <a href="{{ route('missions.index') }}" class="text-xs text-[#dfa6a9] hover:underline flex items-center gap-1 mb-1">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                    <span>Back to Missions & Quests</span>
                </a>
                <h1 class="text-2xl font-bold text-white font-brand-display tracking-tight">
                    {{ $mission->title }}
                </h1>
                <p class="text-xs text-slate-400 mt-0.5">
                    {{ ucfirst($mission->category) }} Quest • Requires {{ ucfirst($mission->submission_type) }} Proof
                </p>
            </div>

            <div class="flex items-center gap-2">
                <span class="px-3 py-1 rounded-full text-xs font-bold border border-amber-500/30 bg-amber-500/10 text-amber-300">
                    +{{ $mission->xp_reward }} XP • +{{ $mission->points_reward }} Points
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                <div class="md:col-span-2 space-y-6">
                    <div class="card bg-[#172033] border border-slate-800 p-6 space-y-5">
                        <div>
                            <h2 class="text-base font-bold text-white font-brand-display mb-2">Mission Objectives</h2>
                            <p class="text-xs text-slate-300 leading-relaxed whitespace-pre-line">
                                {{ $mission->objectives ?: $mission->description }}
                            </p>
                        </div>

                        @if($mission->instructions)
                        <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 space-y-2">
                            <h3 class="text-xs font-bold text-[#e69094] uppercase tracking-wider flex items-center gap-1.5">
                                <i data-lucide="info" class="w-4 h-4"></i>
                                <span>Submission Instructions</span>
                            </h3>
                            <p class="text-xs text-slate-300 leading-relaxed whitespace-pre-line">
                                {{ $mission->instructions }}
                            </p>
                        </div>
                        @endif

                        @if($submission)
                        <div class="p-5 rounded-2xl border {{ $submission->status === 'approved' ? 'bg-emerald-500/10 border-emerald-500/30' : ($submission->status === 'rejected' ? 'bg-rose-500/10 border-rose-500/30' : 'bg-amber-500/10 border-amber-500/30') }} space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase tracking-wider text-white">Your Submission Status</span>
                                <span class="px-2.5 py-0.5 rounded text-xs font-bold {{ $submission->status === 'approved' ? 'bg-emerald-500 text-white' : ($submission->status === 'rejected' ? 'bg-rose-500 text-white' : 'bg-amber-500 text-slate-900') }}">
                                    {{ ucfirst($submission->status) }}
                                </span>
                            </div>

                            @if($submission->submission_text)
                            <div class="text-xs text-slate-300 bg-slate-900/60 p-3 rounded-lg border border-slate-800">
                                <span class="block text-[10px] text-slate-500 mb-1">Your Narrative:</span>
                                {{ $submission->submission_text }}
                            </div>
                            @endif

                            @if($submission->proof_url)
                            <div class="text-xs">
                                <span class="text-slate-400">Proof URL: </span>
                                <a href="{{ $submission->proof_url }}" target="_blank" rel="noopener noreferrer" class="text-indigo-400 hover:underline">
                                    {{ $submission->proof_url }}
                                </a>
                            </div>
                            @endif

                            @if($submission->review_notes)
                            <div class="p-3 rounded-lg bg-black/40 border border-slate-800/80 text-xs">
                                <span class="block font-bold text-slate-300 mb-0.5">Officer Feedback:</span>
                                <p class="text-slate-400">{{ $submission->review_notes }}</p>
                            </div>
                            @endif

                            <div class="text-[11px] text-slate-500 flex justify-between pt-1">
                                <span>Submitted: {{ $submission->created_at->format('M d, Y • g:i A') }}</span>
                                @if($submission->reviewed_at)
                                <span>Reviewed: {{ $submission->reviewed_at->format('M d, Y') }}</span>
                                @endif
                            </div>
                        </div>
                        @endif

                        @if(!$submission || ($submission->status === 'rejected' && $mission->allow_resubmission))
                        <div class="pt-4 border-t border-slate-800 space-y-4">
                            <h3 class="text-sm font-bold text-white font-brand-display">
                                {{ $submission && $submission->status === 'rejected' ? 'Resubmit Mission Evidence' : 'Submit Quest Completion Proof' }}
                            </h3>

                            <form method="POST" action="{{ route('missions.submit', $mission) }}" enctype="multipart/form-data" class="space-y-4">
                                @csrf

                                @if(in_array($mission->submission_type, ['text', 'any']))
                                <div>
                                    <label class="block text-xs font-semibold text-slate-300 mb-1">Mission Narrative / Solution</label>
                                    <textarea name="submission_text" rows="4" required class="input-dark text-xs w-full"></textarea>
                                </div>
                                @endif

                                @if($mission->submission_type === 'link')
                                <div>
                                    <label class="block text-xs font-semibold text-slate-300 mb-1">Evidence URL (GitHub / Google Drive / Figma / Live URL)</label>
                                    <input type="url" name="proof_url" required class="input-dark text-xs w-full">
                                </div>
                                @endif

                                @if($mission->submission_type === 'file')
                                <div>
                                    <label class="block text-xs font-semibold text-slate-300 mb-1">Upload Proof File (PDF, Image, or ZIP up to 10MB)</label>
                                    <input type="file" name="proof_file" required class="input-dark text-xs w-full file:mr-4 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-xs file:bg-slate-800 file:text-white">
                                </div>
                                @endif

                                <button type="submit" class="btn-primary w-full text-xs py-2.5 font-bold flex items-center justify-center gap-2">
                                    <i data-lucide="send" class="w-4 h-4"></i>
                                    <span>Submit for Officer Review</span>
                                </button>
                            </form>
                        </div>
                        @endif

                    </div>
                </div>

                <div class="space-y-5">
                    <div class="card bg-[#172033] border border-slate-800 p-5 space-y-4 text-xs">
                        <h3 class="text-xs font-bold text-white uppercase tracking-wider pb-2 border-b border-slate-800">
                            Quest Parameters
                        </h3>

                        <div class="space-y-3">
                            <div>
                                <span class="block text-slate-500 text-[10px]">Category</span>
                                <span class="font-bold text-slate-200">{{ ucfirst($mission->category) }}</span>
                            </div>

                            <div>
                                <span class="block text-slate-500 text-[10px]">Completion Reward</span>
                                <span class="font-bold text-amber-300">+{{ $mission->xp_reward }} XP • +{{ $mission->points_reward }} Pts</span>
                            </div>

                            @if($mission->due_date)
                            <div>
                                <span class="block text-slate-500 text-[10px]">Submission Deadline</span>
                                <span class="font-bold text-white">{{ $mission->due_date->format('M d, Y • g:i A') }}</span>
                            </div>
                            @endif

                            <div>
                                <span class="block text-slate-500 text-[10px]">Verification Type</span>
                                <span class="font-bold text-slate-200">Authorized Officer Review</span>
                            </div>

                            <div>
                                <span class="block text-slate-500 text-[10px]">Resubmission Allowed</span>
                                <span class="font-bold text-slate-200">{{ $mission->allow_resubmission ? 'Yes (If revision required)' : 'Single Attempt Only' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
</x-app-layout>
