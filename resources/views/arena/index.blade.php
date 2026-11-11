<x-app-layout>
<div class="py-8 min-h-screen bg-[#0B1120]" x-data="{
    challenges: [],
    loading: true,
    activeChallenge: null,
    sourceCode: 'function solve(input) {\n    // Write algorithm here\n    return input;\n}',
    language: 'javascript',
    submitting: false,
    verdictResult: null,
    async fetchChallenges() {
        this.loading = true;
        try {
            const res = await window.axios.get('{{ route('arena.api.index') }}');
            this.challenges = res.data.challenges || [];
            if (this.challenges.length > 0) {
                this.selectChallenge(this.challenges[0]);
            }
        } catch (e) {
            console.error(e);
        } finally {
            this.loading = false;
        }
    },
    async selectChallenge(c) {
        try {
            const res = await window.axios.get(`/arena/api/challenges/${c.id}`);
            this.activeChallenge = res.data.challenge;
            this.verdictResult = null;
        } catch (e) {
            console.error(e);
        }
    },
    async submitSolution() {
        if (!this.activeChallenge || !this.sourceCode.trim()) return;
        this.submitting = true;
        this.verdictResult = null;
        try {
            const res = await window.axios.post(`/arena/api/challenges/${this.activeChallenge.id}/submit`, {
                language: this.language,
                source_code: this.sourceCode
            });
            this.verdictResult = res.data;
            if (res.data.verdict === 'accepted') {
                this.activeChallenge.user_solved = true;
            }
        } catch (e) {
            alert(e.response?.data?.message || 'Submission failed.');
        } finally {
            this.submitting = false;
        }
    }
}" x-init="fetchChallenges()">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-5">
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-rose-500/10 text-rose-300 border border-rose-500/20">
                        Competitive Coding & EXP
                    </span>
                </div>
                <h1 class="text-2xl font-bold font-brand-display text-white mt-1">COMSOC Code Arena</h1>
                <p class="text-xs text-slate-400 mt-0.5">Solve algorithmic puzzles, earn EXP, and sharpen your technical coding interview skills.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Left Challenge List (4 cols) -->
            <div class="lg:col-span-4 space-y-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Available Problems</h3>
                
                <template x-if="loading">
                    <div class="card bg-[#172033] border border-slate-800 p-8 text-center text-xs text-slate-400">Loading problems...</div>
                </template>

                <div class="space-y-2">
                    <template x-for="c in challenges" :key="c.id">
                        <div @click="selectChallenge(c)"
                             class="card bg-[#172033] border p-4 rounded-xl cursor-pointer transition flex items-center justify-between gap-3"
                             :class="activeChallenge && activeChallenge.id === c.id ? 'border-[#8F3033] bg-[#1a2337]' : 'border-slate-800 hover:border-slate-700'">
                            <div class="min-w-0 flex-1 space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-[9px] font-bold px-1.5 py-0.5 rounded uppercase"
                                          :class="{
                                              'bg-emerald-500/10 text-emerald-300 border border-emerald-500/20': c.difficulty === 'novice',
                                              'bg-amber-500/10 text-amber-300 border border-amber-500/20': c.difficulty === 'adept',
                                              'bg-rose-500/10 text-rose-300 border border-rose-500/20': c.difficulty === 'master',
                                          }" x-text="c.difficulty"></span>
                                    <span class="text-[10px] text-slate-400 font-mono" x-text="c.category"></span>
                                </div>
                                <h4 class="text-xs font-bold text-white truncate" x-text="c.title"></h4>
                            </div>

                            <div class="text-right shrink-0">
                                <span class="text-xs font-bold text-amber-400 font-mono block" x-text="'+' + c.exp_reward + ' EXP'"></span>
                                <span x-show="c.user_solved" class="text-[9px] text-emerald-400 font-bold">SOLVED</span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Right Problem & Code Workspace (8 cols) -->
            <div class="lg:col-span-8 space-y-4">
                <template x-if="activeChallenge">
                    <div class="space-y-4">
                        <!-- Problem Description Card -->
                        <div class="card bg-[#172033] border border-slate-800 p-6 rounded-2xl space-y-4">
                            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                                <div>
                                    <h2 class="text-lg font-bold text-white font-brand-display" x-text="activeChallenge.title"></h2>
                                    <span class="text-xs text-rose-400 font-mono" x-text="activeChallenge.difficulty.toUpperCase()"></span>
                                </div>
                                <span class="text-sm font-bold text-amber-400 font-mono" x-text="'+' + activeChallenge.exp_reward + ' EXP'"></span>
                            </div>

                            <div class="text-xs text-slate-300 space-y-2 leading-relaxed" x-text="activeChallenge.description"></div>

                            <div class="grid grid-cols-2 gap-3 pt-2">
                                <div class="bg-slate-900 p-3 rounded-xl border border-slate-800">
                                    <span class="text-[10px] font-bold text-slate-400 block uppercase">Sample Input</span>
                                    <pre class="text-xs text-slate-200 font-mono mt-1" x-text="activeChallenge.sample_input || 'N/A'"></pre>
                                </div>
                                <div class="bg-slate-900 p-3 rounded-xl border border-slate-800">
                                    <span class="text-[10px] font-bold text-slate-400 block uppercase">Sample Output</span>
                                    <pre class="text-xs text-slate-200 font-mono mt-1" x-text="activeChallenge.sample_output || 'N/A'"></pre>
                                </div>
                            </div>
                        </div>

                        <!-- Code Editor & Runner Card -->
                        <div class="card bg-[#172033] border border-slate-800 p-5 rounded-2xl space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-300 uppercase tracking-wider">Solution Editor</span>
                                <select x-model="language" class="input-dark text-xs py-1 px-3 rounded-lg bg-slate-900 border-slate-700">
                                    <option value="javascript">JavaScript (Node)</option>
                                    <option value="php">PHP 8.2</option>
                                    <option value="python">Python 3.11</option>
                                </select>
                            </div>

                            <textarea x-model="sourceCode" rows="10" class="input-dark font-mono text-xs w-full p-4 rounded-xl bg-slate-950 border-slate-800 text-rose-100 focus:border-[#8F3033]"></textarea>

                            <div class="flex items-center justify-between pt-2">
                                <template x-if="verdictResult">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2.5 py-1 rounded-lg text-xs font-bold uppercase font-mono"
                                              :class="verdictResult.verdict === 'accepted' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40' : 'bg-rose-500/20 text-rose-300 border border-rose-500/40'"
                                              x-text="verdictResult.verdict === 'accepted' ? 'ACCEPTED ✓' : 'WRONG ANSWER ✗'"></span>
                                        <span class="text-xs text-slate-400 font-mono" x-text="verdictResult.tests_passed + '/' + verdictResult.total_tests + ' tests passed'"></span>
                                    </div>
                                </template>
                                <span x-show="!verdictResult"></span>

                                <button type="button" @click="submitSolution()" :disabled="submitting || !sourceCode.trim()" class="btn-primary text-xs py-2 px-5 flex items-center gap-2">
                                    <i data-lucide="play" class="w-3.5 h-3.5"></i>
                                    <span x-text="submitting ? 'Running Tests...' : 'Run & Submit Solution'"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

    </div>
</div>
</x-app-layout>

