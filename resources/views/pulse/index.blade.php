<x-app-layout>
<div class="py-8 min-h-screen bg-[#0B1120]" x-data="{
    topics: [],
    loading: true,
    category: 'all',
    status: 'all',
    search: '',
    showModal: false,
    newTopic: { category: 'lab_facilities', title: '', content: '' },
    submitting: false,
    async fetchTopics() {
        this.loading = true;
        try {
            const res = await window.axios.get('{{ route('pulse.api.index') }}', {
                params: { category: this.category, status: this.status, q: this.search }
            });
            this.topics = res.data.data || [];
        } catch (e) {
            console.error(e);
        } finally {
            this.loading = false;
        }
    },
    async submitTopic() {
        if (!this.newTopic.title.trim() || !this.newTopic.content.trim()) return;
        this.submitting = true;
        try {
            await window.axios.post('{{ route('pulse.api.store') }}', this.newTopic);
            this.showModal = false;
            this.newTopic = { category: 'lab_facilities', title: '', content: '' };
            this.fetchTopics();
        } catch (e) {
            alert(e.response?.data?.message || 'Failed to submit feedback.');
        } finally {
            this.submitting = false;
        }
    },
    async toggleVote(topic) {
        try {
            const res = await window.axios.post(`/pulse/topics/${topic.id}/vote`);
            topic.votes_count = res.data.votes_count;
            topic.has_voted = res.data.has_voted;
        } catch (e) {
            console.error(e);
        }
    }
}" x-init="fetchTopics()">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-5">
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-rose-500/10 text-rose-300 border border-rose-500/20">
                        Zero-Knowledge Student Voice
                    </span>
                </div>
                <h1 class="text-2xl font-bold font-brand-display text-white mt-1">COMSOC Pulse</h1>
                <p class="text-xs text-slate-400 mt-0.5">Submit anonymous campus concerns, academic suggestions, and vote on what matters most.</p>
            </div>

            <button type="button" @click="showModal = true" class="btn-primary text-xs py-2 px-4 flex items-center gap-2 shrink-0">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span>Speak Anonymously</span>
            </button>
        </div>

        <!-- Filter bar -->
        <div class="card bg-[#172033] border border-slate-800 p-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <select x-model="category" @change="fetchTopics()" class="input-dark text-xs py-1.5 px-3 rounded-lg border-slate-700 bg-slate-900">
                    <option value="all">All Channels</option>
                    <option value="lab_facilities">💻 Lab & Equipment</option>
                    <option value="curriculum">📚 Curriculum & Workshops</option>
                    <option value="org_governance">🏛️ Organization Governance</option>
                    <option value="student_welfare">🤝 Student Welfare</option>
                    <option value="general">💬 General</option>
                </select>

                <select x-model="status" @change="fetchTopics()" class="input-dark text-xs py-1.5 px-3 rounded-lg border-slate-700 bg-slate-900">
                    <option value="all">All Statuses</option>
                    <option value="under_review">🟡 Under Review</option>
                    <option value="in_progress">🔵 In Progress</option>
                    <option value="resolved">🟢 Resolved</option>
                </select>
            </div>

            <div class="relative w-full sm:w-64">
                <input type="text" x-model="search" @input.debounce.300ms="fetchTopics()" placeholder="Search topics..." class="input-dark text-xs py-1.5 px-3 w-full rounded-lg bg-slate-900 border-slate-700">
            </div>
        </div>

        <!-- Topics List -->
        <div class="space-y-3">
            <template x-if="loading">
                <div class="p-8 text-center text-xs text-slate-400">Loading student voice topics...</div>
            </template>

            <template x-if="!loading && topics.length === 0">
                <div class="card bg-[#172033] border border-slate-800 p-12 text-center text-slate-400 text-xs">
                    No anonymous feedback topics found in this channel. Be the first to speak!
                </div>
            </template>

            <template x-for="topic in topics" :key="topic.id">
                <div class="card bg-[#172033] border border-slate-800 p-5 rounded-2xl hover:border-slate-700 transition flex gap-4">
                    <!-- Upvote Button -->
                    <button type="button" @click="toggleVote(topic)" class="flex flex-col items-center justify-center w-12 h-14 rounded-xl border transition shrink-0"
                            :class="topic.has_voted ? 'bg-[#560E0F] border-[#8F3033] text-white shadow-lg' : 'bg-slate-900/80 border-slate-800 text-slate-400 hover:text-white'">
                        <i data-lucide="chevron-up" class="w-4 h-4"></i>
                        <span class="text-xs font-bold font-mono" x-text="topic.votes_count"></span>
                    </button>

                    <!-- Content -->
                    <div class="flex-1 min-w-0 space-y-2">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-slate-800 text-slate-300" x-text="topic.category.replace('_', ' ').toUpperCase()"></span>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded"
                                  :class="{
                                      'bg-amber-500/10 text-amber-300 border border-amber-500/20': topic.status === 'under_review',
                                      'bg-blue-500/10 text-blue-300 border border-blue-500/20': topic.status === 'in_progress',
                                      'bg-emerald-500/10 text-emerald-300 border border-emerald-500/20': topic.status === 'resolved',
                                      'bg-slate-500/10 text-slate-400 border border-slate-500/20': topic.status === 'closed',
                                  }" x-text="topic.status.replace('_', ' ').toUpperCase()"></span>
                            <span class="text-[11px] text-slate-500 ml-auto" x-text="topic.created_at"></span>
                        </div>

                        <h3 class="text-sm sm:text-base font-bold text-white" x-text="topic.title"></h3>
                        <p class="text-xs text-slate-300 leading-relaxed" x-text="topic.content"></p>

                        <!-- Official Response Box if present -->
                        <template x-if="topic.official_response">
                            <div class="mt-3 p-3 rounded-xl bg-[#0B1120] border border-[#8F3033]/40 space-y-1">
                                <div class="flex items-center gap-2 text-[10px] font-bold text-rose-300 uppercase tracking-wider">
                                    <i data-lucide="shield-check" class="w-3.5 h-3.5 text-rose-400"></i>
                                    <span>Official Response by <span x-text="topic.responder?.name || 'COMSOC Officer'"></span></span>
                                    <span class="text-slate-500 ml-auto" x-text="topic.responded_at"></span>
                                </div>
                                <p class="text-xs text-slate-200" x-text="topic.official_response"></p>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>

    </div>

    <!-- Anonymous Submission Modal -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
        <div class="card bg-[#172033] border border-slate-700 w-full max-w-lg p-6 rounded-2xl space-y-4 shadow-2xl" @click.away="showModal = false">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="text-sm font-bold text-white uppercase tracking-wider font-brand-display">Speak Anonymously</h3>
                <button type="button" @click="showModal = false" class="text-slate-400 hover:text-white">&times;</button>
            </div>

            <p class="text-[11px] text-slate-400">
                🔒 <strong>Cryptographically Anonymous:</strong> Your user identity and student number are never stored in this feedback record.
            </p>

            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Feedback Channel</label>
                    <select x-model="newTopic.category" class="input-dark text-xs w-full py-2 px-3 rounded-xl bg-slate-900 border-slate-700">
                        <option value="lab_facilities">💻 Lab & Equipment (Broken PCs, Wi-Fi, AC)</option>
                        <option value="curriculum">📚 Curriculum & Workshops (Elective requests, bootcamps)</option>
                        <option value="org_governance">🏛️ Organization Governance (Fees, events, transparency)</option>
                        <option value="student_welfare">🤝 Student Welfare (Campus safety, mental health)</option>
                        <option value="general">💬 General Ideas & Feedback</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Topic Summary</label>
                    <input type="text" x-model="newTopic.title" maxlength="150" placeholder="e.g. Lab 402 Network Switch Drops Every 20 Minutes" class="input-dark text-xs w-full py-2 px-3 rounded-xl bg-slate-900 border-slate-700">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Detailed Description</label>
                    <textarea x-model="newTopic.content" rows="4" maxlength="2500" placeholder="Describe the concern or suggestion in detail..." class="input-dark text-xs w-full py-2 px-3 rounded-xl bg-slate-900 border-slate-700"></textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-800">
                <button type="button" @click="showModal = false" class="btn-secondary text-xs py-1.5 px-3">Cancel</button>
                <button type="button" @click="submitTopic()" :disabled="submitting || !newTopic.title.trim() || !newTopic.content.trim()" class="btn-primary text-xs py-1.5 px-4">
                    <span x-text="submitting ? 'Submitting...' : 'Post Anonymously'"></span>
                </button>
            </div>
        </div>
    </div>
</div>
</x-app-layout>

