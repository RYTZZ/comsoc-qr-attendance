<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-2xl bg-[#172033] border border-[#334155] p-2 flex items-center justify-center shrink-0 shadow-lg text-[#df747a]">
                    <i data-lucide="scroll-text" class="w-6 h-6"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-burgundy-deep text-[#df747a] border border-[#8F3033]/60">
                            Organization
                        </span>
                        @if($document && $document->is_published)
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                                Approved v{{ $document->version }}
                            </span>
                        @endif
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-[#F8FAFC] font-brand-display tracking-tight mt-1">
                        Constitutional and By-Laws
                    </h1>
                    <p class="text-xs text-[#94A3B8] mt-0.5">
                        Computing Society of Sorsogon State University – Bulan Campus
                    </p>
                </div>
            </div>

            @if($isAvailable)
                <div class="flex flex-wrap items-center gap-2.5">
                    <button type="button"
                            @click="document.getElementById('cbl-viewer-card')?.scrollIntoView({ behavior: 'smooth' })"
                            class="btn-secondary text-xs flex items-center gap-2">
                        <i data-lucide="eye" class="w-4 h-4 text-slate-300"></i>
                        <span>Preview Document</span>
                    </button>
                    <a href="{{ route('constitution.download') }}"
                       id="btn-download-pdf-top"
                       class="btn-primary text-xs flex items-center gap-2 shadow-lg">
                        <i data-lucide="download" class="w-4 h-4"></i>
                        <span>Download PDF</span>
                    </a>
                </div>
            @endif
        </div>
    </x-slot>

    <div class="py-6 sm:py-8 bg-[#0B1120]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Institutional Document Overview Card -->
            <div class="bg-[#172033]/90 border border-[#334155] rounded-2xl p-5 sm:p-6 shadow-xl relative overflow-hidden backdrop-blur-md">
                <div class="absolute -right-16 -top-16 w-56 h-56 rounded-full bg-gradient-to-br from-[#8F3033]/20 to-transparent blur-3xl pointer-events-none"></div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start relative z-10">
                    <div class="lg:col-span-2 space-y-3">
                        <div class="flex items-center gap-2 text-xs font-semibold text-[#df747a]">
                            <i data-lucide="shield-check" class="w-4 h-4"></i>
                            <span>Official Organizational Governance & Charter</span>
                        </div>
                        <h2 class="text-lg sm:text-xl font-bold text-white tracking-tight">
                            {{ $document?->title ?? 'COMSOC Constitutional and By-Laws' }}
                        </h2>
                        <p class="text-sm text-slate-300 leading-relaxed">
                            {{ $document?->description ?? 'This official governing document establishes the Computing Society\'s governing principles, policies, executive structure, member rights and responsibilities, electoral rules, and operational procedures across all CICT degree programs.' }}
                        </p>

                        <!-- Organization Meta Highlights -->
                        <div class="pt-2 flex flex-wrap items-center gap-y-2 gap-x-4 text-xs text-slate-400">
                            <span class="inline-flex items-center gap-1.5">
                                <i data-lucide="building" class="w-3.5 h-3.5 text-slate-500"></i>
                                Sorsogon State University – Bulan Campus
                            </span>
                            <span class="inline-flex items-center gap-1.5">
                                <i data-lucide="graduation-cap" class="w-3.5 h-3.5 text-slate-500"></i>
                                CICT: BSCS • BSIT • BSIS • BTVTED-CSS
                            </span>
                        </div>
                    </div>

                    <!-- Document Metadata Panel -->
                    <div class="bg-[#0B1120]/80 border border-[#334155] rounded-xl p-4 space-y-3">
                        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider flex items-center justify-between border-b border-[#334155]/60 pb-2">
                            <span>Document Information</span>
                            <i data-lucide="file-text" class="w-3.5 h-3.5 text-slate-400"></i>
                        </div>

                        @if($document && $document->fileExists())
                            <dl class="text-xs space-y-2">
                                <div class="flex justify-between items-center">
                                    <dt class="text-slate-400">File Format:</dt>
                                    <dd class="font-semibold text-slate-200 uppercase flex items-center gap-1">
                                        <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                        PDF Document
                                    </dd>
                                </div>
                                <div class="flex justify-between items-center">
                                    <dt class="text-slate-400">File Size:</dt>
                                    <dd class="font-medium text-slate-300 font-mono">{{ $document->formattedFileSize() }}</dd>
                                </div>
                                <div class="flex justify-between items-center">
                                    <dt class="text-slate-400">Active Version:</dt>
                                    <dd class="font-semibold text-[#df747a] font-mono">v{{ $document->version }}.0</dd>
                                </div>
                                <div class="flex justify-between items-center">
                                    <dt class="text-slate-400">Last Updated:</dt>
                                    <dd class="text-slate-300 font-medium">{{ $document->updated_at?->format('M d, Y') ?? 'N/A' }}</dd>
                                </div>
                                <div class="flex justify-between items-center">
                                    <dt class="text-slate-400">Status:</dt>
                                    <dd>
                                        @if($document->is_published)
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                                Published
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                                Unpublished
                                            </span>
                                        @endif
                                    </dd>
                                </div>
                            </dl>
                        @else
                            <div class="py-2 text-center text-xs text-slate-400">
                                Document awaiting administrator publication.
                            </div>
                        @endif

                        @if(Auth::user()->isAdmin() || Auth::user()->isSuperAdmin())
                            <div class="pt-2 border-t border-[#334155]/60">
                                <a href="{{ route('admin.constitution.index') }}"
                                   class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-amber-500/10 text-amber-300 hover:bg-amber-500/20 border border-amber-500/30 transition">
                                    <i data-lucide="settings" class="w-3.5 h-3.5"></i>
                                    <span>Manage in Admin Console</span>
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- PDF Viewer Section -->
            @if($isAvailable)
                <div id="cbl-viewer-card"
                     class="bg-[#172033] border border-[#334155] rounded-2xl shadow-2xl overflow-hidden flex flex-col"
                     x-data="{
                         isFullscreen: false,
                         zoomLevels: ['page-width', 'page-fit', '75', '100', '125', '150'],
                         zoomLabels: { 'page-width': 'Fit Width', 'page-fit': 'Fit Page', '75': '75%', '100': '100%', '125': '125%', '150': '150%' },
                         currentZoomIdx: 0,
                         isLoading: true,
                         hasError: false,
                         errorMessage: '',
                         basePreviewUrl: '{{ route('constitution.preview') }}?v={{ $document->version }}-{{ $document->updated_at?->timestamp }}',
                         
                         get currentZoomMode() {
                             return this.zoomLevels[this.currentZoomIdx];
                         },
                         get currentZoomLabel() {
                             return this.zoomLabels[this.currentZoomMode] || '100%';
                         },
                         get computedViewerUrl() {
                             const mode = this.currentZoomMode;
                             let viewParam = 'view=FitH';
                             if (mode === 'page-fit') viewParam = 'view=Fit';
                             else if (mode !== 'page-width') viewParam = 'zoom=' + mode;
                             return this.basePreviewUrl + '#' + viewParam + '&toolbar=1&navpanes=1&scrollbar=1';
                         },
                         zoomIn() {
                             if (this.currentZoomIdx < this.zoomLevels.length - 1) {
                                 this.currentZoomIdx++;
                                 this.updateFrameUrl();
                             }
                         },
                         zoomOut() {
                             if (this.currentZoomIdx > 0) {
                                 this.currentZoomIdx--;
                                 this.updateFrameUrl();
                             }
                         },
                         resetZoom() {
                             this.currentZoomIdx = 0; // Fit Width default
                             this.updateFrameUrl();
                         },
                         updateFrameUrl() {
                             const frame = this.$refs.pdfFrame;
                             if (frame) {
                                 frame.src = this.computedViewerUrl;
                             }
                         },
                         toggleFullscreen() {
                             const container = document.getElementById('pdf-viewer-container');
                             if (!document.fullscreenElement) {
                                 if (container.requestFullscreen) {
                                     container.requestFullscreen().then(() => {
                                         this.isFullscreen = true;
                                     }).catch(() => {
                                         this.isFullscreen = !this.isFullscreen;
                                     });
                                 } else {
                                     this.isFullscreen = !this.isFullscreen;
                                 }
                             } else {
                                 if (document.exitFullscreen) {
                                     document.exitFullscreen().then(() => {
                                         this.isFullscreen = false;
                                     });
                                 } else {
                                     this.isFullscreen = false;
                                 }
                             }
                         },
                         onLoadSuccess() {
                             this.isLoading = false;
                             this.hasError = false;
                         },
                         onLoadFailure() {
                             this.isLoading = false;
                             this.hasError = true;
                             this.errorMessage = 'Unable to stream the PDF preview. You can refresh or download the document directly.';
                         }
                     }"
                     @fullscreenchange.window="isFullscreen = !!document.fullscreenElement">

                    <!-- Custom Interactive Toolbar -->
                    <div class="bg-[#0B1120] border-b border-[#334155] px-4 py-3 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-rose-500/10 border border-rose-500/30 text-rose-400">
                                <i data-lucide="file-text" class="w-4 h-4"></i>
                            </span>
                            <div>
                                <h3 class="text-xs sm:text-sm font-bold text-white tracking-tight flex items-center gap-2">
                                    <span>COMSOC Constitutional and By-Laws</span>
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                </h3>
                                <p class="text-[10px] text-slate-400">
                                    Official Ratified Document • Version {{ $document->version }}.0
                                </p>
                            </div>
                        </div>

                        <!-- Viewer Action Controls -->
                        <div class="flex items-center gap-2">
                            <!-- Zoom Controls -->
                            <div class="flex items-center bg-[#172033] border border-[#334155] rounded-xl p-1 text-slate-300">
                                <button type="button"
                                        @click="zoomOut()"
                                        :disabled="currentZoomIdx <= 0"
                                        class="p-1.5 hover:text-white rounded-lg hover:bg-[#1E293B] disabled:opacity-30 disabled:cursor-not-allowed transition"
                                        title="Zoom Out">
                                    <i data-lucide="zoom-out" class="w-3.5 h-3.5"></i>
                                </button>
                                <button type="button"
                                        @click="resetZoom()"
                                        class="px-2 text-[11px] font-mono font-semibold text-slate-300 hover:text-white transition"
                                        title="Reset to Fit Width">
                                    <span x-text="currentZoomLabel">Fit Width</span>
                                </button>
                                <button type="button"
                                        @click="zoomIn()"
                                        :disabled="currentZoomIdx >= zoomLevels.length - 1"
                                        class="p-1.5 hover:text-white rounded-lg hover:bg-[#1E293B] disabled:opacity-30 disabled:cursor-not-allowed transition"
                                        title="Zoom In">
                                    <i data-lucide="zoom-in" class="w-3.5 h-3.5"></i>
                                </button>
                            </div>

                            <!-- Full Screen Button -->
                            <button type="button"
                                    id="btn-toggle-fullscreen"
                                    @click="toggleFullscreen()"
                                    class="p-2 bg-[#172033] hover:bg-[#1E293B] border border-[#334155] text-slate-300 hover:text-white rounded-xl transition flex items-center gap-1.5 text-xs font-semibold"
                                    title="Expand Full Screen">
                                <i :data-lucide="isFullscreen ? 'minimize-2' : 'maximize-2'" class="w-3.5 h-3.5"></i>
                                <span class="hidden md:inline" x-text="isFullscreen ? 'Exit Full Screen' : 'Full Screen'">Full Screen</span>
                            </button>

                            <!-- Primary Download Button inside Toolbar -->
                            <a href="{{ route('constitution.download') }}"
                               id="btn-download-pdf-viewer"
                               class="btn-primary text-xs flex items-center gap-1.5 py-1.5 px-3 rounded-xl shadow">
                                <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                <span class="hidden sm:inline">Download PDF</span>
                            </a>
                        </div>
                    </div>

                    <!-- Viewer Container (Occupies available space, fits full width, no outer blank areas) -->
                    <div id="pdf-viewer-container"
                         class="relative w-full bg-slate-900 flex flex-col items-stretch justify-start overflow-hidden transition-all duration-200"
                         :class="isFullscreen ? 'fixed inset-0 z-50 h-screen w-screen' : 'h-[82vh] min-h-[640px] max-h-[1000px]'">

                        <!-- Fullscreen close bar if hardware fullscreen was fallback -->
                        <div x-show="isFullscreen && !document.fullscreenElement"
                             class="w-full bg-[#0B1120] border-b border-[#334155] p-3 flex justify-between items-center shrink-0">
                            <span class="text-xs font-semibold text-white">Full Screen Reader</span>
                            <button type="button"
                                    @click="toggleFullscreen()"
                                    class="text-xs text-rose-400 hover:text-rose-300 font-semibold px-3 py-1 rounded bg-rose-500/10 border border-rose-500/30">
                                Close Fullscreen
                            </button>
                        </div>

                        <!-- Loading Overlay -->
                        <div x-show="isLoading && !hasError"
                             class="absolute inset-0 z-20 bg-slate-950/80 backdrop-blur-sm flex flex-col items-center justify-center p-6 text-center">
                            <div class="relative w-14 h-14 mb-4">
                                <div class="w-14 h-14 rounded-full border-4 border-[#8F3033]/30 border-t-[#df747a] animate-spin"></div>
                                <div class="absolute inset-0 flex items-center justify-center text-[#df747a]">
                                    <i data-lucide="scroll-text" class="w-5 h-5"></i>
                                </div>
                            </div>
                            <h4 class="text-sm font-bold text-white mb-1">Loading Constitutional and By-Laws...</h4>
                            <p class="text-xs text-slate-400 max-w-xs">Streaming official governing document.</p>
                        </div>

                        <!-- Error State Overlay -->
                        <div x-show="hasError"
                             x-cloak
                             class="absolute inset-0 z-20 bg-slate-950/95 flex flex-col items-center justify-center p-6 text-center space-y-4">
                            <div class="w-14 h-14 rounded-2xl bg-rose-500/10 border border-rose-500/30 flex items-center justify-center text-rose-400">
                                <i data-lucide="alert-circle" class="w-7 h-7"></i>
                            </div>
                            <div>
                                <h4 class="text-base font-bold text-white">Document Preview Encountered An Error</h4>
                                <p class="text-xs text-slate-400 mt-1 max-w-md" x-text="errorMessage">
                                    Unable to stream the PDF viewer. You can download the official document directly.
                                </p>
                            </div>
                            <div class="flex items-center gap-3">
                                <button type="button"
                                        @click="isLoading = true; hasError = false; $refs.pdfFrame.src = computedViewerUrl + '&t=' + Date.now()"
                                        class="btn-secondary text-xs flex items-center gap-2">
                                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                                    <span>Retry Preview</span>
                                </button>
                                <a href="{{ route('constitution.download') }}" class="btn-primary text-xs flex items-center gap-2">
                                    <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                    <span>Download Original File</span>
                                </a>
                            </div>
                        </div>

                        <!-- Full-Viewport Embedded PDF Frame -->
                        <div class="w-full h-full flex-1 relative bg-slate-900">
                            <iframe x-ref="pdfFrame"
                                    :src="computedViewerUrl"
                                    class="w-full h-full border-0 block"
                                    title="COMSOC Constitutional and By-Laws Viewer"
                                    x-on:load="onLoadSuccess()"
                                    x-on:error="onLoadFailure()">
                                <div class="p-8 text-center text-slate-300">
                                    <p class="text-sm mb-4">Your browser does not support inline PDF viewing.</p>
                                    <a href="{{ route('constitution.download') }}" class="btn-primary text-xs inline-flex items-center gap-2">
                                        <i data-lucide="download" class="w-4 h-4"></i>
                                        Download Official PDF
                                    </a>
                                </div>
                            </iframe>
                        </div>

                        <!-- Mobile Fallback Quick-Bar -->
                        <div class="sm:hidden w-full bg-[#172033] border-t border-[#334155] px-4 py-2.5 flex items-center justify-between text-xs text-slate-300 shrink-0">
                            <span>Mobile reading view:</span>
                            <a href="{{ route('constitution.preview') }}"
                               target="_blank"
                               class="font-semibold text-[#df747a] hover:underline flex items-center gap-1">
                                <span>Open Full Screen Tab</span>
                                <i data-lucide="external-link" class="w-3 h-3"></i>
                            </a>
                        </div>
                    </div>
                </div>

            @else
                <!-- Empty State (No official document uploaded yet or unpublished) -->
                <div class="bg-[#172033] border border-[#334155] rounded-2xl p-10 sm:p-14 text-center shadow-xl max-w-2xl mx-auto space-y-4">
                    <div class="w-16 h-16 rounded-2xl bg-[#0B1120] border border-[#334155] flex items-center justify-center mx-auto text-slate-400">
                        <i data-lucide="file-question" class="w-8 h-8 text-slate-400"></i>
                    </div>

                    <div class="space-y-1">
                        <h3 class="text-lg sm:text-xl font-bold text-white">No Document Currently Published</h3>
                        <p class="text-xs sm:text-sm text-slate-400 max-w-md mx-auto">
                            @if($document && !$document->is_published)
                                The Constitutional and By-Laws document is currently in review or undergoing scheduled updates by the organization executive committee.
                            @else
                                The official Computing Society Constitutional and By-Laws document has not been uploaded to the system yet.
                            @endif
                        </p>
                    </div>

                    @if(Auth::user()->isAdmin() || Auth::user()->isSuperAdmin())
                        <div class="pt-4 flex flex-wrap items-center justify-center gap-3">
                            <a href="{{ route('admin.constitution.index') }}"
                               class="btn-primary text-xs flex items-center gap-2">
                                <i data-lucide="upload" class="w-4 h-4"></i>
                                <span>Upload Official Document in Admin</span>
                            </a>
                        </div>
                    @else
                        <div class="pt-2 text-xs text-slate-500">
                            Please check back later or contact the Computing Society Executive Officers for further information.
                        </div>
                    @endif
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
