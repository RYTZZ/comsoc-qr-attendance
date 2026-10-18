<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-[#560E0F]/30 border border-[#560E0F]/60 text-[#dfa6a9]">
                        Officer Operations
                    </span>
                    <span class="text-xs text-slate-400">Quest Review Desk</span>
                </div>
                <h1 class="text-2xl font-bold text-white font-brand-display tracking-tight mt-1">
                    Missions & Submissions Review
                </h1>
                <p class="text-xs text-slate-400 mt-0.5">
                    Create student engagement missions, review proof submissions, and award completed bounties.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.economy.index') }}" class="btn-secondary text-xs flex items-center gap-1.5">
                    <i data-lucide="coins" class="w-4 h-4"></i>
                    <span>Reward Economy</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <div class="lg:col-span-2 space-y-6">

                    <div class="card bg-[#172033] border border-amber-500/30 p-6 space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                            <div class="flex items-center gap-2">
                                <i data-lucide="clock" class="w-4 h-4 text-amber-400"></i>
                                <h2 class="text-base font-bold text-white font-brand-display">Pending Member Submissions</h2>
                            </div>
                            <span class="px-2 py-0.5 rounded text-xs font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                {{ $pendingSubmissions->count() }} awaiting review
                            </span>
                        </div>

                        @if($pendingSubmissions->isEmpty())
                            <div class="p-8 text-center border border-dashed border-slate-800 rounded-xl">
                                <i data-lucide="check-check" class="w-8 h-8 mx-auto text-emerald-500 mb-2"></i>
                                <p class="text-xs font-bold text-white">All submissions are reviewed</p>
                                <p class="text-[11px] text-slate-500 mt-0.5">No member proof pending approval at this time.</p>
                            </div>
                        @else
                            <div class="space-y-4">
                                @foreach($pendingSubmissions as $sub)
                                <div class="p-4 rounded-xl bg-slate-900/80 border border-slate-800 space-y-3">
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                        <div>
                                            <span class="text-[10px] font-mono text-[#e69094] uppercase font-bold">{{ $sub->mission ? $sub->mission->category : 'General' }}</span>
                                            <h3 class="text-sm font-bold text-white">{{ $sub->mission ? $sub->mission->title : 'Unknown Mission' }}</h3>
                                            <p class="text-xs text-slate-400">
                                                Submitted by <span class="text-white font-semibold">{{ $sub->user ? $sub->user->name : 'Member' }}</span> • {{ $sub->created_at->diffForHumans() }}
                                            </p>
                                        </div>
                                        <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-500/20 text-amber-300 font-mono">
                                            +{{ $sub->mission ? $sub->mission->xp_reward : 0 }} XP • +{{ $sub->mission ? $sub->mission->points_reward : 0 }} Pts
                                        </span>
                                    </div>

                                    @if($sub->submission_text)
                                    <div class="p-3 rounded-lg bg-black/40 border border-slate-800/80 text-xs text-slate-300">
                                        <span class="block text-[10px] text-slate-500 mb-0.5">Member Narrative / Answer:</span>
                                        {{ $sub->submission_text }}
                                    </div>
                                    @endif

                                    @if($sub->proof_url)
                                    <div class="text-xs flex items-center gap-2">
                                        <span class="text-slate-400">Proof Link:</span>
                                        <a href="{{ $sub->proof_url }}" target="_blank" rel="noopener noreferrer" class="text-indigo-400 hover:underline flex items-center gap-1 font-mono">
                                            <span>{{ $sub->proof_url }}</span>
                                            <i data-lucide="external-link" class="w-3 h-3"></i>
                                        </a>
                                    </div>
                                    @endif

                                    @if($sub->file_path)
                                    <div class="text-xs flex items-center gap-2">
                                        <span class="text-slate-400">Attached File:</span>
                                        <a href="{{ asset('storage/' . $sub->file_path) }}" target="_blank" class="text-emerald-400 hover:underline flex items-center gap-1">
                                            <i data-lucide="file" class="w-3.5 h-3.5"></i>
                                            <span>View Evidence Document</span>
                                        </a>
                                    </div>
                                    @endif

                                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-800" x-data="{ openRejectModal: false }">
                                        <button @click="openRejectModal = true" class="btn-secondary text-xs py-1.5 px-3 text-rose-400 hover:text-rose-300">
                                            Request Revision
                                        </button>

                                        <form method="POST" action="{{ route('admin.missions.approve', $sub) }}">
                                            @csrf
                                            <button type="submit" class="btn-primary text-xs py-1.5 px-3 bg-emerald-600 hover:bg-emerald-500">
                                                Approve & Grant Rewards
                                            </button>
                                        </form>

                                        <div x-show="openRejectModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm text-left">
                                            <div @click.away="openRejectModal = false" class="card bg-[#172033] border border-slate-700 p-6 max-w-md w-full space-y-3">
                                                <h4 class="text-sm font-bold text-white">Reject / Request Revision</h4>
                                                <form method="POST" action="{{ route('admin.missions.reject', $sub) }}" class="space-y-3">
                                                    @csrf
                                                    <div>
                                                        <label class="block text-xs text-slate-300 mb-1">Feedback / Rejection Reason</label>
                                                        <textarea name="review_notes" rows="3" required class="input-dark text-xs w-full"></textarea>
                                                    </div>
                                                    <div class="flex justify-end gap-2 pt-2">
                                                        <button type="button" @click="openRejectModal = false" class="btn-secondary text-xs py-1.5 px-3">Cancel</button>
                                                        <button type="submit" class="btn-primary text-xs py-1.5 px-3 bg-rose-600 hover:bg-rose-500">Submit Feedback</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="card bg-[#172033] border border-slate-800 p-6 space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                            <div>
                                <h2 class="text-base font-bold text-white font-brand-display">All Configured Missions</h2>
                                <p class="text-xs text-slate-400">Active engagement quests published to members.</p>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr class="border-b border-slate-800 text-slate-400 text-[10px] uppercase font-mono">
                                        <th class="py-2.5 px-3">Mission Title</th>
                                        <th class="py-2.5 px-3">Category</th>
                                        <th class="py-2.5 px-3">Format</th>
                                        <th class="py-2.5 px-3 text-right">Rewards</th>
                                        <th class="py-2.5 px-3 text-right">Total Submissions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800 text-slate-300">
                                    @foreach($missions as $m)
                                    <tr class="hover:bg-slate-900/40">
                                        <td class="py-3 px-3 font-semibold text-white">{{ $m->title }}</td>
                                        <td class="py-3 px-3">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-900 text-slate-300 border border-slate-800">
                                                {{ ucfirst($m->category) }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-3 text-slate-400 uppercase font-mono text-[10px]">{{ $m->submission_type }}</td>
                                        <td class="py-3 px-3 text-right font-mono text-amber-300 font-bold">+{{ $m->xp_reward }} XP • +{{ $m->points_reward }} Pts</td>
                                        <td class="py-3 px-3 text-right font-mono text-white">{{ $m->submissions_count }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="pt-3">
                            {{ $missions->links() }}
                        </div>
                    </div>

                </div>

                <div class="space-y-6">

                    <div class="card bg-[#172033] border border-slate-800 p-6 space-y-4">
                        <div class="flex items-center gap-2 pb-2 border-b border-slate-800">
                            <i data-lucide="plus-circle" class="w-4 h-4 text-[#e69094]"></i>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-white">Post New Mission</h3>
                        </div>

                        <form method="POST" action="{{ route('admin.missions.store') }}" class="space-y-3">
                            @csrf

                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1">Mission Title</label>
                                <input type="text" name="title" required class="input-dark text-xs w-full">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1">Category</label>
                                <x-custom-dropdown
                                    name="category"
                                    :options="[
                                        ['value' => 'coding', 'label' => 'Coding / Development'],
                                        ['value' => 'workshop', 'label' => 'Workshop / Seminar'],
                                        ['value' => 'volunteer', 'label' => 'Volunteer / Organization'],
                                        ['value' => 'design', 'label' => 'UI/UX & Design'],
                                        ['value' => 'general', 'label' => 'General Challenge']
                                    ]"
                                    selected="coding"
                                    placeholder="Select Category"
                                />
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-300 mb-1">XP Bounty</label>
                                    <input type="number" name="xp_reward" min="0" value="100" required class="input-dark text-xs w-full font-mono">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-300 mb-1">Points Bounty</label>
                                    <input type="number" name="points_reward" min="0" value="30" required class="input-dark text-xs w-full font-mono">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1">Submission Requirement Type</label>
                                <x-custom-dropdown
                                    name="submission_type"
                                    :options="[
                                        ['value' => 'text', 'label' => 'Text Narrative / Answer'],
                                        ['value' => 'link', 'label' => 'External Link (GitHub/Drive/Figma)'],
                                        ['value' => 'file', 'label' => 'File Upload (PDF/Image/ZIP)']
                                    ]"
                                    selected="text"
                                    placeholder="Select Type"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1">Overview & Objectives</label>
                                <textarea name="description" rows="2" required class="input-dark text-xs w-full"></textarea>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1">Detailed Instructions</label>
                                <textarea name="instructions" rows="2" class="input-dark text-xs w-full"></textarea>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1">Due Date (Optional)</label>
                                <input type="date" name="due_date" class="input-dark text-xs w-full">
                            </div>

                            <label class="flex items-center gap-2 text-xs text-slate-300 cursor-pointer pt-1">
                                <input type="checkbox" name="allow_resubmission" value="1" checked class="rounded border-slate-700 bg-slate-900 text-[#560E0F]">
                                <span>Allow resubmission upon rejection</span>
                            </label>

                            <button type="submit" class="btn-primary w-full text-xs py-2 font-bold flex items-center justify-center gap-1.5 mt-2">
                                <i data-lucide="check" class="w-4 h-4"></i>
                                <span>Publish Mission</span>
                            </button>
                        </form>
                    </div>

                </div>

            </div>

        </div>
    </div>
</x-app-layout>
