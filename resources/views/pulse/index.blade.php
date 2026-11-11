<x-app-layout>
<div class="py-8 min-h-screen bg-[#0B1120]" x-data="{
    topics: [],
    loading: true,
    category: 'all',
    status: 'all',
    search: '',
    showModal: false,
    isEditing: false,
    editTopicId: null,
    topicForm: { category: 'lab_facilities', title: '', content: '' },
    formErrors: {},
    submitting: false,
    // Delete Confirmation Modal state
    showDeleteModal: false,
    topicToDelete: null,
    deleting: false,

    async fetchTopics() {
        this.loading = true;
        try {
            const res = await window.axios.get('{{ route('pulse.api.index') }}', {
                params: { category: this.category, status: this.status, q: this.search }
            });
            this.topics = res.data.data || [];
            this.$nextTick(() => {
                if (window.lucideIcons && window.createIcons) {
                    window.createIcons({ icons: window.lucideIcons });
                }
            });
        } catch (e) {
            console.error(e);
            if (window.toast) window.toast('Failed to load pulse feedback topics.', 'error');
        } finally {
            this.loading = false;
        }
    },

    openCreateModal() {
        this.isEditing = false;
        this.editTopicId = null;
        this.topicForm = { category: 'lab_facilities', title: '', content: '' };
        this.formErrors = {};
        this.showModal = true;
    },

    openEditModal(topic) {
        this.isEditing = true;
        this.editTopicId = topic.id;
        this.topicForm = {
            category: topic.category,
            title: topic.title,
            content: topic.content
        };
        this.formErrors = {};
        this.showModal = true;
    },

    confirmDelete(topic) {
        this.topicToDelete = topic;
        this.showDeleteModal = true;
    },

    async executeDelete() {
        if (!this.topicToDelete) return;
        this.deleting = true;
        try {
            const res = await window.axios.delete(`/pulse/api/topics/${this.topicToDelete.id}`);
            this.showDeleteModal = false;
            this.topicToDelete = null;
            if (window.toast) window.toast(res.data.message || 'Feedback topic deleted.', 'success');
            this.fetchTopics();
        } catch (e) {
            const msg = e.response?.data?.message || 'Failed to delete topic.';
            if (window.toast) window.toast(msg, 'error');
        } finally {
            this.deleting = false;
        }
    },

    async saveTopic() {
        this.formErrors = {};
        
        // Client-side quick checks
        let hasError = false;
        if (!this.topicForm.title || this.topicForm.title.trim().length < 5) {
            this.formErrors.title = 'The topic summary must be at least 5 characters.';
            hasError = true;
        }
        if (!this.topicForm.content || this.topicForm.content.trim().length < 15) {
            this.formErrors.content = 'Please provide at least 15 characters describing your concern or suggestion.';
            hasError = true;
        }

        if (hasError) return;

        this.submitting = true;
        try {
            if (this.isEditing) {
                const res = await window.axios.put(`/pulse/api/topics/${this.editTopicId}`, this.topicForm);
                this.showModal = false;
                if (window.toast) window.toast(res.data.message || 'Topic updated successfully.', 'success');
            } else {
                const res = await window.axios.post('{{ route('pulse.api.store') }}', this.topicForm);
                this.showModal = false;
                if (window.toast) window.toast(res.data.message || 'Anonymous feedback posted!', 'success');
            }
            this.fetchTopics();
        } catch (e) {
            if (e.response && e.response.status === 422 && e.response.data.errors) {
                this.formErrors = Object.keys(e.response.data.errors).reduce((acc, key) => {
                    acc[key] = e.response.data.errors[key][0];
                    return acc;
                }, {});
            } else {
                const msg = e.response?.data?.message || 'An error occurred while saving.';
                if (window.toast) window.toast(msg, 'error');
            }
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
            if (window.toast) window.toast('Unable to process vote.', 'error');
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
                <p class="text-xs text-slate-400 mt-0.5">Submit campus concerns, academic suggestions, and vote on what matters most.</p>
            </div>

            <button type="button" @click="openCreateModal()" class="btn-primary text-xs py-2 px-4 flex items-center gap-2 shrink-0">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span>Speak Anonymously</span>
            </button>
        </div>

        <!-- Filter bar using COMSOC HUB Customized Dropdowns -->
        <div class="card bg-[#172033] border border-slate-800 p-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-3">
                <!-- Channel Filter Dropdown -->
                <div class="w-48" @dropdown-selected.stop="category = $event.detail.value; fetchTopics();">
                    <x-custom-dropdown
                        name="pulse_category_filter"
                        :options="[
                            ['value' => 'all', 'label' => 'All Channels'],
                            ['value' => 'lab_facilities', 'label' => '💻 Lab & Equipment'],
                            ['value' => 'curriculum', 'label' => '📚 Curriculum & Workshops'],
                            ['value' => 'org_governance', 'label' => '🏛️ Org Governance'],
                            ['value' => 'student_welfare', 'label' => '🤝 Student Welfare'],
                            ['value' => 'general', 'label' => '💬 General']
                        ]"
                        value="all"
                        buttonClass="py-1.5 px-3 text-xs bg-[#0B1120] border-slate-700"
                    />
                </div>

                <!-- Status Filter Dropdown -->
                <div class="w-44" @dropdown-selected.stop="status = $event.detail.value; fetchTopics();">
                    <x-custom-dropdown
                        name="pulse_status_filter"
                        :options="[
                            ['value' => 'all', 'label' => 'All Statuses'],
                            ['value' => 'under_review', 'label' => '🟡 Under Review'],
                            ['value' => 'in_progress', 'label' => '🔵 In Progress'],
                            ['value' => 'resolved', 'label' => '🟢 Resolved'],
                            ['value' => 'closed', 'label' => '⚪ Closed']
                        ]"
                        value="all"
                        buttonClass="py-1.5 px-3 text-xs bg-[#0B1120] border-slate-700"
                    />
                </div>
            </div>

            <div class="relative w-full sm:w-64">
                <input type="text" x-model="search" @input.debounce.300ms="fetchTopics()" placeholder="Search topics..." class="input-dark text-xs py-1.5 px-3 w-full rounded-lg bg-[#0B1120] border-slate-700">
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
                <div class="card bg-[#172033] border border-slate-800 p-5 rounded-2xl hover:border-slate-700 transition flex gap-4 group relative">
                    <!-- Upvote Button -->
                    <button type="button" @click="toggleVote(topic)" class="flex flex-col items-center justify-center w-12 h-14 rounded-xl border transition shrink-0"
                            :class="topic.has_voted ? 'bg-[#560E0F] border-[#8F3033] text-white shadow-lg' : 'bg-[#0B1120] border-slate-800 text-slate-400 hover:text-white'">
                        <i data-lucide="chevron-up" class="w-4 h-4"></i>
                        <span class="text-xs font-bold font-mono" x-text="topic.votes_count"></span>
                    </button>

                    <!-- Content -->
                    <div class="flex-1 min-w-0 space-y-2">
                        <div class="flex items-center gap-2 flex-wrap justify-between">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-[#0B1120] text-slate-300 border border-slate-800" x-text="topic.category.replace('_', ' ').toUpperCase()"></span>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded"
                                      :class="{
                                          'bg-amber-500/10 text-amber-300 border border-amber-500/20': topic.status === 'under_review',
                                          'bg-blue-500/10 text-blue-300 border border-blue-500/20': topic.status === 'in_progress',
                                          'bg-emerald-500/10 text-emerald-300 border border-emerald-500/20': topic.status === 'resolved',
                                          'bg-slate-500/10 text-slate-400 border border-slate-500/20': topic.status === 'closed',
                                      }" x-text="topic.status.replace('_', ' ').toUpperCase()"></span>
                                <span class="text-[11px] text-slate-500 ml-1" x-text="topic.created_at"></span>
                            </div>

                            <!-- Edit & Delete Action Buttons (Owner / Admin) -->
                            <div class="flex items-center gap-1.5" x-show="topic.can_edit || topic.can_delete">
                                <button type="button"
                                        x-show="topic.can_edit"
                                        @click="openEditModal(topic)"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition"
                                        title="Edit this submission">
                                    <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                </button>
                                <button type="button"
                                        x-show="topic.can_delete"
                                        @click="confirmDelete(topic)"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 transition"
                                        title="Delete this submission">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                </button>
                            </div>
                        </div>

                        <h3 class="text-sm sm:text-base font-bold text-white" x-text="topic.title"></h3>
                        <p class="text-xs text-slate-300 leading-relaxed whitespace-pre-line" x-text="topic.content"></p>

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

    <!-- Create / Edit Submission Modal -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
        <div class="card bg-[#172033] border border-slate-700 w-full max-w-lg p-6 rounded-2xl space-y-4 shadow-2xl" @click.away="showModal = false">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="text-sm font-bold text-white uppercase tracking-wider font-brand-display" x-text="isEditing ? 'Edit Submission' : 'Speak Anonymously'"></h3>
                <button type="button" @click="showModal = false" class="text-slate-400 hover:text-white">&times;</button>
            </div>

            <p class="text-[11px] text-slate-400" x-show="!isEditing">
                🔒 <strong>Cryptographically Anonymous:</strong> Your user identity and student number are never exposed in public feedback feeds.
            </p>

            <div class="space-y-3">
                <!-- Channel selection with Customized Dropdown -->
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Feedback Channel</label>
                    <div @dropdown-selected.stop="topicForm.category = $event.detail.value">
                        <x-custom-dropdown
                            name="pulse_modal_category"
                            :options="[
                                ['value' => 'lab_facilities', 'label' => '💻 Lab & Equipment (Broken PCs, Wi-Fi, AC)'],
                                ['value' => 'curriculum', 'label' => '📚 Curriculum & Workshops (Elective requests, bootcamps)'],
                                ['value' => 'org_governance', 'label' => '🏛️ Org Governance (Fees, events, transparency)'],
                                ['value' => 'student_welfare', 'label' => '🤝 Student Welfare (Campus safety, mental health)'],
                                ['value' => 'general', 'label' => '💬 General Ideas & Feedback']
                            ]"
                            value="lab_facilities"
                            buttonClass="py-2 px-3 text-xs bg-[#0B1120] border-slate-700"
                        />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Topic Summary (Title)</label>
                    <input type="text"
                           x-model="topicForm.title"
                           maxlength="150"
                           placeholder="e.g. Lab 402 Network Switch Drops Every 20 Minutes"
                           :class="formErrors.title ? 'border-rose-500 ring-1 ring-rose-500' : 'border-slate-700'"
                           class="input-dark text-xs w-full py-2 px-3 rounded-xl bg-[#0B1120]">
                    <!-- Field-Specific Validation Message -->
                    <template x-if="formErrors.title">
                        <p class="text-[11px] text-rose-400 mt-1 font-medium" x-text="formErrors.title"></p>
                    </template>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Detailed Description</label>
                    <textarea x-model="topicForm.content"
                              rows="4"
                              maxlength="2500"
                              placeholder="Describe the concern or suggestion in detail (minimum 15 characters)..."
                              :class="formErrors.content ? 'border-rose-500 ring-1 ring-rose-500' : 'border-slate-700'"
                              class="input-dark text-xs w-full py-2 px-3 rounded-xl bg-[#0B1120]"></textarea>
                    <!-- Field-Specific Validation Message -->
                    <template x-if="formErrors.content">
                        <p class="text-[11px] text-rose-400 mt-1 font-medium" x-text="formErrors.content"></p>
                    </template>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-800">
                <button type="button" @click="showModal = false" class="btn-secondary text-xs py-1.5 px-3">Cancel</button>
                <button type="button" @click="saveTopic()" :disabled="submitting" class="btn-primary text-xs py-1.5 px-4 flex items-center gap-1.5">
                    <span x-text="submitting ? 'Saving...' : (isEditing ? 'Save Changes' : 'Post Anonymously')"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Customized Delete Confirmation Modal -->
    <div x-show="showDeleteModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
        <div class="card bg-[#172033] border border-slate-700 w-full max-w-md p-6 rounded-2xl space-y-4 shadow-2xl" @click.away="showDeleteModal = false">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 flex items-center justify-center shrink-0">
                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-white">Delete Feedback Topic</h3>
                    <p class="text-xs text-slate-400">This action cannot be undone.</p>
                </div>
            </div>

            <div class="p-3 bg-[#0B1120] rounded-xl border border-slate-800">
                <p class="text-xs font-semibold text-slate-200 line-clamp-2" x-text="topicToDelete?.title"></p>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" @click="showDeleteModal = false" class="btn-secondary text-xs py-1.5 px-3">Cancel</button>
                <button type="button" @click="executeDelete()" :disabled="deleting" class="px-4 py-1.5 rounded-xl text-xs font-semibold bg-rose-600 hover:bg-rose-700 text-white transition">
                    <span x-text="deleting ? 'Deleting...' : 'Confirm Delete'"></span>
                </button>
            </div>
        </div>
    </div>

</div>
</x-app-layout>
