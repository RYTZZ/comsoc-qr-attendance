<x-app-layout>
<div class="py-8 min-h-screen bg-[#0B1120]" x-data="{
    showcases: [],
    loading: true,
    language: 'all',
    status: 'all',
    search: '',
    showModal: false,
    newShowcase: {
        title: '',
        description: '',
        language: 'php',
        repository_url: '',
        live_demo_url: '',
        code_snippet: '',
        bounty_exp: 50
    },
    submitting: false,
    async fetchShowcases() {
        this.loading = true;
        try {
            const res = await window.axios.get('{{ route('critique.api.index') }}', {
                params: { language: this.language, status: this.status, q: this.search }
            });
            this.showcases = res.data.data || [];
        } catch (e) {
            console.error(e);
        } finally {
            this.loading = false;
        }
    },
    async submitShowcase() {
        if (!this.newShowcase.title.trim() || !this.newShowcase.description.trim()) return;
        this.submitting = true;
        try {
            await window.axios.post('{{ route('critique.api.store') }}', this.newShowcase);
            this.showModal = false;
            this.newShowcase = { title: '', description: '', language: 'php', repository_url: '', live_demo_url: '', code_snippet: '', bounty_exp: 50 };
            this.fetchShowcases();
        } catch (e) {
            alert(e.response?.data?.message || 'Failed to submit showcase.');
        } finally {
            this.submitting = false;
        }
    }
}" x-init="fetchShowcases()">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-5">
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-rose-500/10 text-rose-300 border border-rose-500/20">
                        Peer Review & Git Showcase
                    </span>
                </div>
                <h1 class="text-2xl font-bold font-brand-display text-white mt-1">COMSOC Code Critique</h1>
                <p class="text-xs text-slate-400 mt-0.5">Submit personal projects, capstone prototypes, or code snippets for constructive peer reviews and earn reviewer karma.</p>
            </div>

            <button type="button" @click="showModal = true" class="btn-primary text-xs py-2 px-4 flex items-center gap-2 shrink-0">
                <i data-lucide="code" class="w-4 h-4"></i>
                <span>Submit for Review</span>
            </button>
        </div>

        <!-- Filter bar -->
        <div class="card bg-[#172033] border border-slate-800 p-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <select x-model="language" @change="fetchShowcases()" class="input-dark text-xs py-1.5 px-3 rounded-lg border-slate-700 bg-slate-900">
                    <option value="all">All Languages</option>
                    <option value="php">🐘 PHP / Laravel</option>
                    <option value="javascript">⚡ JavaScript / Node</option>
                    <option value="python">🐍 Python</option>
                    <option value="cpp">⚙️ C / C++</option>
                    <option value="java">☕ Java</option>
                    <option value="sql">🗄️ SQL</option>
                </select>

                <select x-model="status" @change="fetchShowcases()" class="input-dark text-xs py-1.5 px-3 rounded-lg border-slate-700 bg-slate-900">
                    <option value="all">All Statuses</option>
                    <option value="open">🟢 Open for Review</option>
                    <option value="in_review">🟡 In Review</option>
                    <option value="solved">✅ Solved / Accepted</option>
                </select>
            </div>

            <div class="relative w-full sm:w-64">
                <input type="text" x-model="search" @input.debounce.300ms="fetchShowcases()" placeholder="Search projects or code..." class="input-dark text-xs py-1.5 px-3 w-full rounded-lg bg-slate-900 border-slate-700">
            </div>
        </div>

        <!-- Showcase Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <template x-if="loading">
                <div class="col-span-full p-8 text-center text-xs text-slate-400">Loading code showcases...</div>
            </template>

            <template x-if="!loading && showcases.length === 0">
                <div class="col-span-full card bg-[#172033] border border-slate-800 p-12 text-center text-slate-400 text-xs">
                    No project showcases found. Be the first to share your code for peer critique!
                </div>
            </template>

            <template x-for="item in showcases" :key="item.id">
                <div class="card bg-[#172033] border border-slate-800 p-5 rounded-2xl hover:border-slate-700 transition flex flex-col justify-between space-y-4">
                    <div class="space-y-2.5">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-slate-800 text-rose-300 font-bold uppercase" x-text="item.language"></span>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded"
                                  :class="{
                                      'bg-emerald-500/10 text-emerald-300 border border-emerald-500/20': item.status === 'open',
                                      'bg-amber-500/10 text-amber-300 border border-amber-500/20': item.status === 'in_review',
                                      'bg-blue-500/10 text-blue-300 border border-blue-500/20': item.status === 'solved',
                                  }" x-text="item.status.toUpperCase()"></span>
                        </div>

                        <h3 class="text-sm font-bold text-white line-clamp-1" x-text="item.title"></h3>
                        <p class="text-xs text-slate-400 line-clamp-2" x-text="item.description"></p>
                    </div>

                    <div class="pt-3 border-t border-slate-800/80 flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-full bg-slate-800 overflow-hidden shrink-0">
                                <img :src="item.author.avatar" class="w-full h-full object-cover" x-show="item.author.avatar">
                            </div>
                            <span class="text-[11px] text-slate-300 truncate max-w-[100px]" x-text="item.author.name"></span>
                        </div>

                        <div class="flex items-center gap-3 text-slate-400 text-[11px] font-mono">
                            <span class="flex items-center gap-1" title="Reviews count">
                                <i data-lucide="message-square" class="w-3.5 h-3.5"></i>
                                <span x-text="item.reviews_count"></span>
                            </span>
                            <span class="text-amber-400 font-bold" x-show="item.bounty_exp > 0" x-text="'+' + item.bounty_exp + ' EXP'"></span>
                        </div>
                    </div>
                </div>
            </template>
        </div>

    </div>

    <!-- Submission Modal -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
        <div class="card bg-[#172033] border border-slate-700 w-full max-w-xl p-6 rounded-2xl space-y-4 shadow-2xl max-h-[90vh] overflow-y-auto" @click.away="showModal = false">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="text-sm font-bold text-white uppercase tracking-wider font-brand-display">Submit Code for Review</h3>
                <button type="button" @click="showModal = false" class="text-slate-400 hover:text-white">&times;</button>
            </div>

            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Project / Snippet Title</label>
                    <input type="text" x-model="newShowcase.title" placeholder="e.g. Student Registration Wizard State Machine" class="input-dark text-xs w-full py-2 px-3 rounded-xl bg-slate-900 border-slate-700">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Language</label>
                        <select x-model="newShowcase.language" class="input-dark text-xs w-full py-2 px-3 rounded-xl bg-slate-900 border-slate-700">
                            <option value="php">PHP / Laravel</option>
                            <option value="javascript">JavaScript / TypeScript</option>
                            <option value="python">Python</option>
                            <option value="cpp">C / C++</option>
                            <option value="java">Java</option>
                            <option value="sql">SQL / Database</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Review Bounty (EXP)</label>
                        <input type="number" x-model.number="newShowcase.bounty_exp" min="0" max="500" class="input-dark text-xs w-full py-2 px-3 rounded-xl bg-slate-900 border-slate-700">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">GitHub Repo URL (Optional)</label>
                        <input type="url" x-model="newShowcase.repository_url" placeholder="https://github.com/..." class="input-dark text-xs w-full py-2 px-3 rounded-xl bg-slate-900 border-slate-700">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Live Demo URL (Optional)</label>
                        <input type="url" x-model="newShowcase.live_demo_url" placeholder="https://..." class="input-dark text-xs w-full py-2 px-3 rounded-xl bg-slate-900 border-slate-700">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Review Description & Questions</label>
                    <textarea x-model="newShowcase.description" rows="3" placeholder="Explain what this code does and what specific feedback you are seeking (e.g. security checks, query optimization)..." class="input-dark text-xs w-full py-2 px-3 rounded-xl bg-slate-900 border-slate-700"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Code Snippet (Optional direct paste)</label>
                    <textarea x-model="newShowcase.code_snippet" rows="4" placeholder="// Paste code here for quick inline review..." class="input-dark font-mono text-xs w-full py-2 px-3 rounded-xl bg-slate-950 border-slate-700 text-rose-200"></textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-800">
                <button type="button" @click="showModal = false" class="btn-secondary text-xs py-1.5 px-3">Cancel</button>
                <button type="button" @click="submitShowcase()" :disabled="submitting || !newShowcase.title.trim() || !newShowcase.description.trim()" class="btn-primary text-xs py-1.5 px-4">
                    <span x-text="submitting ? 'Submitting...' : 'Publish Showcase'"></span>
                </button>
            </div>
        </div>
    </div>
</div>
</x-app-layout>

