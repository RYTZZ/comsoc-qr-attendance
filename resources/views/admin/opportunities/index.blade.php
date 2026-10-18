<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-indigo-500/20 border border-indigo-500/40 text-indigo-300">
                        Officer Operations
                    </span>
                    <span class="text-xs text-slate-400">Industry & Career Partnerships</span>
                </div>
                <h1 class="text-2xl font-bold text-white font-brand-display tracking-tight mt-1">
                    Opportunities Management
                </h1>
                <p class="text-xs text-slate-400 mt-0.5">
                    Curate external tech internships, freelance gigs, and jobs for computing society students.
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm flex items-center gap-3">
                    <i data-lucide="check-circle" class="w-5 h-5 flex-shrink-0"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                <div class="lg:col-span-5 space-y-6">
                    <div class="card bg-[#172033] border border-slate-800 p-6 rounded-2xl space-y-5">
                        <div class="flex items-center gap-2 pb-3 border-b border-slate-800">
                            <i data-lucide="briefcase" class="w-4 h-4 text-indigo-400"></i>
                            <h2 class="text-base font-bold text-white font-brand-display">Post Tech Opportunity</h2>
                        </div>

                        <form method="POST" action="{{ route('admin.opportunities.store') }}" class="space-y-4">
                            @csrf

                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1">
                                    Role / Job Title <span class="text-rose-400">*</span>
                                </label>
                                <input type="text" name="title" required
                                       class="input-dark text-xs w-full">
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-300 mb-1">
                                        Company / Organization <span class="text-rose-400">*</span>
                                    </label>
                                    <input type="text" name="company" required
                                           class="input-dark text-xs w-full">
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-300 mb-1">
                                        Employment Type <span class="text-rose-400">*</span>
                                    </label>
                                    @php
                                        $typeOpts = [];
                                        foreach($employmentTypes as $et) {
                                            $typeOpts[] = ['value' => $et, 'label' => $et];
                                        }
                                    @endphp
                                    <x-custom-dropdown
                                        name="job_type"
                                        :options="$typeOpts"
                                        selected="Internship"
                                        placeholder="Select Type"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-300 mb-1">
                                        Location / City
                                    </label>
                                    <input type="text" name="location" placeholder="e.g. Sorsogon / Manila"
                                           class="input-dark text-xs w-full">
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-300 mb-1">
                                        Compensation / Stipend
                                    </label>
                                    <input type="text" name="compensation" placeholder="e.g. Paid Stipend / Allowance"
                                           class="input-dark text-xs w-full">
                                </div>
                            </div>

                            <div class="flex items-center gap-2 pt-1">
                                <input type="checkbox" name="is_remote" id="opp_is_remote" value="1"
                                       class="rounded border-slate-700 bg-slate-800 text-[#560E0F] focus:ring-[#560E0F]">
                                <label for="opp_is_remote" class="text-xs text-slate-300">
                                    Remote work / Work from home friendly
                                </label>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1">
                                    External Application URL <span class="text-rose-400">*</span>
                                </label>
                                <input type="url" name="application_url" required placeholder="https://careers.company.com/apply/..."
                                       class="input-dark text-xs w-full">
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-300 mb-1">
                                        Original Source URL
                                    </label>
                                    <input type="url" name="source_url" placeholder="https://linkedin.com/jobs/..."
                                           class="input-dark text-xs w-full">
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-300 mb-1">
                                        Application Deadline
                                    </label>
                                    <input type="date" name="deadline_at"
                                           class="input-dark text-xs w-full">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1">
                                    Required Skills (Comma separated)
                                </label>
                                <input type="text" name="required_skills" placeholder="e.g. React, Node.js, Git, SQL"
                                       class="input-dark text-xs w-full">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1">
                                    Description & Requirements <span class="text-rose-400">*</span>
                                </label>
                                <textarea name="description" rows="4" required
                                          placeholder="Detailed job summary, responsibilities, and qualification guidelines..."
                                          class="input-dark text-xs w-full"></textarea>
                            </div>

                            <div class="space-y-2 pt-1">
                                <div class="flex items-center gap-2">
                                    <input type="checkbox" name="is_verified" id="opp_is_verified" value="1" checked
                                           class="rounded border-slate-700 bg-slate-800 text-[#560E0F] focus:ring-[#560E0F]">
                                    <label for="opp_is_verified" class="text-xs text-slate-300">
                                        Mark as verified partner listing
                                    </label>
                                </div>

                                <div class="flex items-center gap-2">
                                    <input type="checkbox" name="is_active" id="opp_is_active" value="1" checked
                                           class="rounded border-slate-700 bg-slate-800 text-[#560E0F] focus:ring-[#560E0F]">
                                    <label for="opp_is_active" class="text-xs text-slate-300">
                                        Publish immediately to member opportunities directory
                                    </label>
                                </div>
                            </div>

                            <div class="pt-3">
                                <button type="submit" class="w-full btn-primary py-2 text-xs flex items-center justify-center gap-2">
                                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                    <span>Publish Opportunity</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="lg:col-span-7 space-y-4">
                    <div class="card bg-[#172033] border border-slate-800 p-6 rounded-2xl" x-data="{ editModal: false, activeOpp: null }">
                        <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                            <div>
                                <h2 class="text-base font-bold text-white font-brand-display">All External Listings</h2>
                                <p class="text-[11px] text-slate-400">Total published opportunities and saved bookmarks count.</p>
                            </div>
                        </div>

                        @if($jobs->isEmpty())
                            <div class="p-8 text-center">
                                <p class="text-xs text-slate-400">No opportunities created yet.</p>
                            </div>
                        @else
                            <div class="divide-y divide-slate-800/80 mt-4">
                                @foreach($jobs as $job)
                                    <div class="py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-xl bg-indigo-500/10 border border-indigo-500/30 flex items-center justify-center text-indigo-400 shrink-0">
                                                <i data-lucide="briefcase" class="w-5 h-5"></i>
                                            </div>

                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-sm font-bold text-white">{{ $job->title }}</span>
                                                    <span class="px-2 py-0.2 rounded-full text-[10px] font-bold {{ $job->is_active ? 'bg-emerald-500/20 text-emerald-300' : 'bg-slate-800 text-slate-400' }}">
                                                        {{ $job->is_active ? 'Active' : 'Archived' }}
                                                    </span>
                                                    @if($job->is_verified)
                                                        <span class="px-2 py-0.2 rounded-full text-[10px] font-bold bg-cyan-500/20 text-cyan-300">
                                                            Verified
                                                        </span>
                                                    @endif
                                                </div>

                                                <div class="text-[11px] text-slate-400 flex items-center gap-3 mt-0.5">
                                                    <span class="text-slate-300 font-medium">{{ $job->company }}</span>
                                                    <span>•</span>
                                                    <span>{{ $job->job_type }}</span>
                                                    <span>•</span>
                                                    <span>{{ $job->bookmarks_count }} saved</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2 sm:self-center">
                                            <form method="POST" action="{{ route('admin.opportunities.toggle-active', $job) }}">
                                                @csrf
                                                <button type="submit" class="btn-secondary text-[11px] py-1 px-2.5">
                                                    {{ $job->is_active ? 'Archive' : 'Publish' }}
                                                </button>
                                            </form>

                                            <button type="button"
                                                    @click="activeOpp = {{ json_encode($job) }}; editModal = true;"
                                                    class="btn-primary text-xs py-1.5 px-3 flex items-center gap-1">
                                                <i data-lucide="edit-2" class="w-3.5 h-3.5"></i>
                                                <span>Edit</span>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <template x-teleport="body">
                                <div x-show="editModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm" style="display: none;">
                                    <div class="bg-[#172033] border border-slate-800 rounded-2xl max-w-lg w-full p-6 space-y-4 shadow-xl max-h-[90vh] overflow-y-auto" @click.outside="editModal = false">
                                        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                                            <h3 class="text-sm font-bold text-white">Edit Opportunity Listing</h3>
                                            <button @click="editModal = false" class="text-slate-400 hover:text-white">
                                                <i data-lucide="x" class="w-4 h-4"></i>
                                            </button>
                                        </div>

                                        <form :action="'/admin/opportunities/' + (activeOpp ? activeOpp.id : '')" method="POST" class="space-y-4">
                                            @csrf
                                            @method('PUT')

                                            <div>
                                                <label class="block text-xs font-semibold text-slate-300 mb-1">Title</label>
                                                <input type="text" name="title" :value="activeOpp?.title" required class="input-dark text-xs w-full">
                                            </div>

                                            <div class="grid grid-cols-2 gap-3">
                                                <div>
                                                    <label class="block text-xs font-semibold text-slate-300 mb-1">Company</label>
                                                    <input type="text" name="company" :value="activeOpp?.company" required class="input-dark text-xs w-full">
                                                </div>

                                                <div>
                                                    <label class="block text-xs font-semibold text-slate-300 mb-1">Job Type</label>
                                                    <input type="text" name="job_type" :value="activeOpp?.job_type" required class="input-dark text-xs w-full">
                                                </div>
                                            </div>

                                            <div>
                                                <label class="block text-xs font-semibold text-slate-300 mb-1">Application URL</label>
                                                <input type="url" name="application_url" :value="activeOpp?.application_url" required class="input-dark text-xs w-full">
                                            </div>

                                            <div>
                                                <label class="block text-xs font-semibold text-slate-300 mb-1">Description</label>
                                                <textarea name="description" rows="3" :value="activeOpp?.description" required class="input-dark text-xs w-full"></textarea>
                                            </div>

                                            <div class="flex items-center gap-2 pt-2">
                                                <input type="checkbox" name="is_active" id="edit_opp_is_active" value="1" :checked="activeOpp?.is_active"
                                                       class="rounded border-slate-700 bg-slate-800 text-[#560E0F] focus:ring-[#560E0F]">
                                                <label for="edit_opp_is_active" class="text-xs text-slate-300">
                                                    Active Listing
                                                </label>
                                            </div>

                                            <div class="flex items-center justify-end gap-2 pt-3">
                                                <button type="button" @click="editModal = false" class="btn-secondary text-xs py-1.5 px-3">
                                                    Cancel
                                                </button>
                                                <button type="submit" class="btn-primary text-xs py-1.5 px-4">
                                                    Save Changes
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </template>

                            <div class="p-4 border-t border-slate-800">
                                {{ $jobs->links() }}
                            </div>
                        @endif
                    </div>
                </div>

            </div>

        </div>
    </div>
</x-app-layout>
