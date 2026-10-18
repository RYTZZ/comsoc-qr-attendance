<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('opportunities.index') }}" class="btn-secondary p-2 rounded-xl text-slate-400 hover:text-white">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-indigo-500/20 border border-indigo-500/40 text-indigo-300">
                        {{ $job->job_type }}
                    </span>
                    <span class="text-xs text-slate-400">External Tech Opportunity</span>
                </div>
                <h1 class="text-2xl font-bold text-white font-brand-display tracking-tight mt-0.5">
                    {{ $job->title }}
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

            <div class="card bg-[#172033] border border-slate-800 p-6 sm:p-8 rounded-2xl space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6 pb-6 border-b border-slate-800">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-indigo-500/10 border border-indigo-500/30 flex items-center justify-center text-indigo-400 font-bold text-xl">
                            <i data-lucide="building-2" class="w-7 h-7"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-white font-brand-display">{{ $job->title }}</h2>
                            <p class="text-sm font-semibold text-indigo-300">{{ $job->company }}</p>

                            <div class="flex flex-wrap items-center gap-2 mt-2">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-300 border border-slate-700">
                                    {{ $job->job_type }}
                                </span>

                                @if($job->is_remote)
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                        Remote
                                    </span>
                                @elseif($job->location)
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-slate-800 text-slate-300">
                                        {{ $job->location }}
                                    </span>
                                @endif

                                @if($job->is_verified)
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-cyan-500/20 text-cyan-300 border border-cyan-500/30 flex items-center gap-1">
                                        <i data-lucide="badge-check" class="w-3 h-3"></i>
                                        <span>Verified Partner Listing</span>
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <form method="POST" action="{{ route('opportunities.bookmark', $job) }}">
                            @csrf
                            <button type="submit" class="btn-secondary text-xs py-2 px-3 flex items-center gap-1.5 {{ $isBookmarked ? 'text-amber-400 border-amber-500/30' : '' }}">
                                <i data-lucide="bookmark" class="w-4 h-4 {{ $isBookmarked ? 'fill-amber-400' : '' }}"></i>
                                <span>{{ $isBookmarked ? 'Saved' : 'Bookmark' }}</span>
                            </button>
                        </form>

                        <a href="{{ $job->application_url }}" target="_blank" rel="noopener noreferrer"
                           class="btn-primary text-xs py-2 px-4 flex items-center gap-1.5 shadow-lg">
                            <span>Apply Externally</span>
                            <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                        </a>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 p-4 rounded-xl bg-slate-900 border border-slate-800">
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-500 tracking-wider block">Compensation</span>
                        <span class="text-xs font-bold text-white mt-0.5 block">{{ $job->compensation ?? 'Competitive / Disclosed upon application' }}</span>
                    </div>

                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-500 tracking-wider block">Application Deadline</span>
                        <span class="text-xs font-bold {{ $job->isExpired() ? 'text-rose-400' : 'text-white' }} mt-0.5 block">
                            {{ $job->deadline_at ? $job->deadline_at->format('F d, Y') : 'Open until filled' }}
                        </span>
                    </div>

                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-500 tracking-wider block">Original Source</span>
                        @if($job->source_url)
                            <a href="{{ $job->source_url }}" target="_blank" rel="noopener noreferrer" class="text-xs text-indigo-400 hover:underline flex items-center gap-1 mt-0.5">
                                <span>View Source Post</span>
                                <i data-lucide="arrow-up-right" class="w-3 h-3"></i>
                            </a>
                        @else
                            <span class="text-xs text-slate-500 mt-0.5 block">Direct partner referral</span>
                        @endif
                    </div>
                </div>

                @if(!empty($job->required_skills))
                    <div class="space-y-2">
                        <h3 class="text-xs uppercase font-bold text-slate-400 tracking-wider">Required / Preferred Skills</h3>
                        <div class="flex flex-wrap gap-2">
                            @foreach($job->required_skills as $skill)
                                <span class="px-3 py-1 rounded-xl bg-slate-900 border border-slate-700 text-xs font-semibold text-slate-200">
                                    {{ $skill }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="space-y-3 pt-4 border-t border-slate-800">
                    <h3 class="text-xs uppercase font-bold text-slate-400 tracking-wider">Opportunity Description & Role Details</h3>
                    <div class="text-sm text-slate-300 leading-relaxed whitespace-pre-line">
                        {{ $job->description }}
                    </div>
                </div>

                <div class="pt-6 border-t border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <p class="text-[11px] text-slate-500 max-w-md">
                        Notice: Applications are submitted directly to the external employer via their official portal. COMSOC does not collect applications or process internal hiring decisions.
                    </p>

                    <a href="{{ $job->application_url }}" target="_blank" rel="noopener noreferrer"
                       class="btn-primary text-xs py-2.5 px-5 flex items-center justify-center gap-2">
                        <span>Proceed to Application Portal</span>
                        <i data-lucide="external-link" class="w-4 h-4"></i>
                    </a>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
