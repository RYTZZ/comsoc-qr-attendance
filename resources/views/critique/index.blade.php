<x-app-layout>
<div class="py-8 min-h-screen bg-[#0B1120]" x-data="{
    showcases: [],
    loading: true,
    language: 'all',
    status: 'all',
    search: '',
    showModal: false,
    isEditing: false,
    editShowcaseId: null,
    showcaseForm: {
        title: '',
        description: '',
        language: 'php',
        repository_url: '',
        live_demo_url: '',
        code_snippet: '',
        bounty_exp: 50
    },
    formErrors: {},
    submitting: false,
    // Delete Confirmation state
    showDeleteModal: false,
    showcaseToDelete: null,
    deleting: false,

    async fetchShowcases() {
        this.loading = true;
        try {
            const res = await window.axios.get('{{ route('critique.api.index') }}', {
                params: { language: this.language, status: this.status, q: this.search }
            });
            this.showcases = res.data.data || [];
            this.$nextTick(() => {
                if (window.lucideIcons && window.createIcons) {
                    window.createIcons({ icons: window.lucideIcons });
                }
            });
        } catch (e) {
            console.error(e);
            if (window.toast) window.toast('Failed to load code showcases.', 'error');
        } finally {
            this.loading = false;
        }
    },

    openCreateModal() {
        this.isEditing = false;
        this.editShowcaseId = null;
        this.showcaseForm = {
            title: '',
            description: '',
            language: 'php',
            repository_url: '',
            live_demo_url: '',
            code_snippet: '',
            bounty_exp: 50
        };
        this.formErrors = {};
        this.showModal = true;
    },

    openEditModal(item) {
        this.isEditing = true;
        this.editShowcaseId = item.id;
        this.showcaseForm = {
            title: item.title,
            description: item.description,
            language: item.language,
            repository_url: item.repository_url || '',
            live_demo_url: item.live_demo_url || '',
            code_snippet: item.code_snippet || '',
            bounty_exp: item.bounty_exp || 0
        };
        this.formErrors = {};
        this.showModal = true;
    },

    confirmDelete(item) {
        this.showcaseToDelete = item;
        this.showDeleteModal = true;
    },

    async executeDelete() {
        if (!this.showcaseToDelete) return;
        this.deleting = true;
        try {
            const res = await window.axios.delete(`/critique/api/showcases/${this.showcaseToDelete.id}`);
            this.showDeleteModal = false;
            this.showcaseToDelete = null;
            if (window.toast) window.toast(res.data.message || 'Showcase deleted.', 'success');
            this.fetchShowcases();
        } catch (e) {
            const msg = e.response?.data?.message || 'Failed to delete showcase.';
            if (window.toast) window.toast(msg, 'error');
        } finally {
            this.deleting = false;
        }
    },

    async saveShowcase() {
        this.formErrors = {};
        let hasError = false;

        if (!this.showcaseForm.title || this.showcaseForm.title.trim().length < 5) {
            this.formErrors.title = 'The project title must be at least 5 characters.';
            hasError = true;
        }
        if (!this.showcaseForm.description || this.showcaseForm.description.trim().length < 10) {
            this.formErrors.description = 'Please provide at least 10 characters describing your project or review questions.';
            hasError = true;
        }

        if (hasError) return;

        this.submitting = true;
        try {
            if (this.isEditing) {
                const res = await window.axios.put(`/critique/api/showcases/${this.editShowcaseId}`, this.showcaseForm);
                this.showModal = false;
                if (window.toast) window.toast(res.data.message || 'Project showcase updated!', 'success');
            } else {
                const res = await window.axios.post('{{ route('critique.api.store') }}', this.showcaseForm);
                this.showModal = false;
                if (window.toast) window.toast(res.data.message || 'Project showcase published!', 'success');
            }
            this.fetchShowcases();
        } catch (e) {
            if (e.response && e.response.status === 422 && e.response.data.errors) {
                this.formErrors = Object.keys(e.response.data.errors).reduce((acc, key) => {
                    acc[key] = e.response.data.errors[key][0];
                    return acc;
                }, {});
            } else {
                const msg = e.response?.data?.message || 'Failed to save showcase.';
                if (window.toast) window.toast(msg, 'error');
            }
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
                <p class="text-xs text-slate-400 mt-0.5">Submit personal projects, capstones, or code snippets for constructive peer reviews and earn reviewer karma.</p>
            </div>

            <button type="button" @click="openCreateModal()" class="btn-primary text-xs py-2 px-4 flex items-center gap-2 shrink-0">
                <i data-lucide="code" class="w-4 h-4"></i>
                <span>Submit for Review</span>
            </button>
        </div>

        <!-- Filter bar using COMSOC HUB Customized Dropdowns -->
        <div class="card bg-[#172033] border border-slate-800 p-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-3">
                <!-- Language Filter Dropdown -->
                <div class="w-48" @dropdown-selected.stop="language = $event.detail.value; fetchShowcases();">
                    <x-custom-dropdown
                        name="critique_lang_filter"
                        :options="[
                            ['value' => 'all', 'label' => 'All Languages'],
                            ['value' => 'php', 'label' => '🐘 PHP / Laravel'],
                            ['value' => 'javascript', 'label' => '⚡ JavaScript / Node'],
                            ['value' => 'python', 'label' => '🐍 Python'],
                            ['value' => 'cpp', 'label' => '⚙️ C / C++'],
                            ['value' => 'java', 'label' => '☕ Java'],
                            ['value' => 'sql', 'label' => '🗄️ SQL']
                        ]"
                        value="all"
                        buttonClass="py-1.5 px-3 text-xs bg-[#0B1120] border-slate-700"
                    />
                </div>

                <!-- Status Filter Dropdown -->
                <div class="w-44" @dropdown-selected.stop="status = $event.detail.value; fetchShowcases();">
                    <x-custom-dropdown
                        name="critique_status_filter"
                        :options="[
                            ['value' => 'all', 'label' => 'All Statuses'],
                            ['value' => 'open', 'label' => '🟢 Open for Review'],
                            ['value' => 'in_review', 'label' => '🟡 In Review'],
                            ['value' => 'solved', 'label' => '✅ Solved / Accepted']
                        ]"
                        value="all"
                        buttonClass="py-1.5 px-3 text-xs bg-[#0B1120] border-slate-700"
                    />
                </div>
            </div>

            <div class="relative w-full sm:w-64">
                <input type="text" x-model="search" @input.debounce.300ms="fetchShowcases()" placeholder="Search projects or code..." class="input-dark text-xs py-1.5 px-3 w-full rounded-lg bg-[#0B1120] border-slate-700">
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
                <div class="card bg-[#172033] border border-slate-800 p-5 rounded-2xl hover:border-slate-700 transition flex flex-col justify-between space-y-4 group">
                    <div class="space-y-2.5">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-[#0B1120] text-rose-300 font-bold uppercase border border-slate-800" x-text="item.language"></span>
                            
                            <div class="flex items-center gap-1.5">
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded"
                                      :class="{
                                          'bg-emerald-500/10 text-emerald-300 border border-emerald-500/20': item.status === 'open',
                                          'bg-amber-500/10 text-amber-300 border border-amber-500/20': item.status === 'in_review',
                                          'bg-blue-500/10 text-blue-300 border border-blue-500/20': item.status === 'solved',
                                      }" x-text="item.status.toUpperCase()"></span>

                                <!-- Edit / Delete actions -->
                                <button type="button"
                                        x-show="item.can_edit"
                                        @click="openEditModal(item)"
                                        class="p-1 rounded text-slate-400 hover:text-white hover:bg-slate-800 transition"
                                        title="Edit Showcase">
                                    <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                </button>
                                <button type="button"
                                        x-show="item.can_delete"
                                        @click="confirmDelete(item)"
                                        class="p-1 rounded text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 transition"
                                        title="Delete Showcase">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                </button>
                            </div>
                        </div>

                        <h3 class="text-sm font-bold text-white line-clamp-1" x-text="item.title"></h3>
                        <p class="text-xs text-slate-400 line-clamp-2 leading-relaxed" x-text="item.description"></p>
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

    <!-- Create / Edit Showcase Modal -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
        <div class="card bg-[#172033] border border-slate-700 w-full max-w-xl p-6 rounded-2xl space-y-4 shadow-2xl max-h-[90vh] overflow-y-auto" @click.away="showModal = false">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="text-sm font-bold text-white uppercase tracking-wider font-brand-display" x-text="isEditing ? 'Edit Project Showcase' : 'Submit Code for Review'"></h3>
                <button type="button" @click="showModal = false" class="text-slate-400 hover:text-white">&times;</button>
            </div>

            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Project / Snippet Title</label>
                    <input type="text"
                           x-model="showcaseForm.title"
                           placeholder="e.g. Student Registration Wizard State Machine"
                           :class="formErrors.title ? 'border-rose-500 ring-1 ring-rose-500' : 'border-slate-700'"
                           class="input-dark text-xs w-full py-2 px-3 rounded-xl bg-[#0B1120]">
                    <template x-if="formErrors.title">
                        <p class="text-[11px] text-rose-400 mt-1 font-medium" x-text="formErrors.title"></p>
                    </template>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Language</label>
                        <div @dropdown-selected.stop="showcaseForm.language = $event.detail.value">
                            <x-custom-dropdown
                                name="critique_modal_lang"
                                :options="[
                                    ['value' => 'php', 'label' => '🐘 PHP / Laravel'],
                                    ['value' => 'javascript', 'label' => '⚡ JavaScript / TS'],
                                    ['value' => 'python', 'label' => '🐍 Python'],
                                    ['value' => 'cpp', 'label' => '⚙️ C / C++'],
                                    ['value' => 'java', 'label' => '☕ Java'],
                                    ['value' => 'sql', 'label' => '🗄️ SQL / DB']
                                ]"
                                value="php"
                                buttonClass="py-2 px-3 text-xs bg-[#0B1120] border-slate-700"
                            />
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Review Bounty (EXP)</label>
                        <input type="number" x-model.number="showcaseForm.bounty_exp" min="0" max="500" class="input-dark text-xs w-full py-2 px-3 rounded-xl bg-[#0B1120] border-slate-700">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">GitHub Repo URL (Optional)</label>
                        <input type="url" x-model="showcaseForm.repository_url" placeholder="https://github.com/..." class="input-dark text-xs w-full py-2 px-3 rounded-xl bg-[#0B1120] border-slate-700">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Live Demo URL (Optional)</label>
                        <input type="url" x-model="showcaseForm.live_demo_url" placeholder="https://..." class="input-dark text-xs w-full py-2 px-3 rounded-xl bg-[#0B1120] border-slate-700">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Review Description & Questions</label>
                    <textarea x-model="showcaseForm.description"
                              rows="3"
                              placeholder="Explain what this code does and what specific feedback you are seeking (minimum 10 characters)..."
                              :class="formErrors.description ? 'border-rose-500 ring-1 ring-rose-500' : 'border-slate-700'"
                              class="input-dark text-xs w-full py-2 px-3 rounded-xl bg-[#0B1120]"></textarea>
                    <template x-if="formErrors.description">
                        <p class="text-[11px] text-rose-400 mt-1 font-medium" x-text="formErrors.description"></p>
                    </template>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Code Snippet (Optional direct paste)</label>
                    <textarea x-model="showcaseForm.code_snippet" rows="4" placeholder="// Paste code here for quick inline review..." class="input-dark font-mono text-xs w-full py-2 px-3 rounded-xl bg-slate-950 border-slate-700 text-rose-200"></textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-800">
                <button type="button" @click="showModal = false" class="btn-secondary text-xs py-1.5 px-3">Cancel</button>
                <button type="button" @click="saveShowcase()" :disabled="submitting" class="btn-primary text-xs py-1.5 px-4">
                    <span x-text="submitting ? 'Saving...' : (isEditing ? 'Save Changes' : 'Publish Showcase')"></span>
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
                    <h3 class="text-sm font-bold text-white">Delete Project Showcase</h3>
                    <p class="text-xs text-slate-400">This action cannot be undone.</p>
                </div>
            </div>

            <div class="p-3 bg-[#0B1120] rounded-xl border border-slate-800">
                <p class="text-xs font-semibold text-slate-200 line-clamp-2" x-text="showcaseToDelete?.title"></p>
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
