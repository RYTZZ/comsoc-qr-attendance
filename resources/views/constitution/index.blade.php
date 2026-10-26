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
                            Official Governance
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

    <div class="py-6 sm:py-8 bg-[#0B1120] min-h-[calc(100vh-10rem)]">
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
                            {{ $document?->title ?? 'Computing Society Constitution and By-Laws' }}
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
                         zoomLevels: [75, 100, 125, 150, 175, 200],
                         currentZoom: 100,
                         isLoading: true,
                         hasError: false,
                         errorMessage: '',
                         previewUrl: '{{ route('constitution.preview') }}?v={{ $document->version }}-{{ $document->updated_at?->timestamp }}',
                         
                         zoomIn() {
                             const idx = this.zoomLevels.indexOf(this.currentZoom);
                             if (idx < this.zoomLevels.length - 1) {
                                 this.currentZoom = this.zoomLevels[idx + 1];
                             }
                         },
                         zoomOut() {
                             const idx = this.zoomLevels.indexOf(this.currentZoom);
                             if (idx > 0) {
                                 this.currentZoom = this.zoomLevels[idx - 1];
                             }
                         },
                         resetZoom() {
                             this.currentZoom = 100;
                         },
                         toggleFullscreen() {
                             const container = document.getElementById('pdf-viewer-container');
                             if (!document.fullscreenElement) {
                                 container.requestFullscreen().then(() => {
                                     this.isFullscreen = true;
                                 }).catch(() => {
                                     // Fallback css-based fullscreen
                                     this.isFullscreen = !this.isFullscreen;
                                 });
                             } else {
                                 document.exitFullscreen().then(() => {
                                     this.isFullscreen = false;
                                 });
                             }
                         },
                         onLoadSuccess() {
                             this.isLoading = false;
                             this.hasError = false;
                         },
                         onLoadFailure() {
                             this.isLoading = false;
                             this.hasError = true;
                             this.errorMessage = 'Unable to stream the PDF document preview. Please try refreshing or download the original file directly.';
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
                                    <span>Interactive Document Viewer</span>
                                    <span class="hidden sm:inline-block w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                </h3>
                                <p class="text-[10px] text-slate-400 hidden sm:block">
                                    Secure, native PDF render stream
                                </p>
                            </div>
                        </div>

                        <!-- Viewer Action Controls -->
                        <div class="flex items-center gap-2">
                            <!-- Zoom Controls -->
                            <div class="flex items-center bg-[#172033] border border-[#334155] rounded-xl p-1 text-slate-300">
                                <button type="button"
                                        @click="zoomOut()"
                                        :disabled="currentZoom <= 75"
                                        class="p-1.5 hover:text-white rounded-lg hover:bg-[#1E293B] disabled:opacity-30 disabled:cursor-not-allowed transition"
                                        title="Zoom Out">
                                    <i data-lucide="zoom-out" class="w-3.5 h-3.5"></i>
                                </button>
                                <button type="button"
                                        @click="resetZoom()"
                                        class="px-2 text-[11px] font-mono font-semibold text-slate-300 hover:text-white transition"
                                        title="Reset Zoom">
                                    <span x-text="currentZoom + '%'">100%</span>
                                </button>
                                <button type="button"
                                        @click="zoomIn()"
                                        :disabled="currentZoom >= 200"
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
                                <span class="hidden sm:inline">Download</span>
                            </a>
                        </div>
                    </div>

                    <!-- Viewer Container -->
                    <div id="pdf-viewer-container"
                         class="relative w-full bg-slate-950 flex flex-col items-center justify-start overflow-hidden transition-all duration-200"
                         :class="isFullscreen ? 'fixed inset-0 z-50 h-screen w-screen' : 'h-[750px] sm:h-[850px]'"
                         style="min-height: 520px;">

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
                             class="absolute inset-0 z-20 bg-slate-950/90 backdrop-blur-sm flex flex-col items-center justify-center p-6 text-center">
                            <div class="relative w-14 h-14 mb-4">
                                <div class="w-14 h-14 rounded-full border-4 border-[#8F3033]/30 border-t-[#df747a] animate-spin"></div>
                                <div class="absolute inset-0 flex items-center justify-center text-[#df747a]">
                                    <i data-lucide="scroll-text" class="w-5 h-5"></i>
                                </div>
                            </div>
                            <h4 class="text-sm font-bold text-white mb-1">Loading Constitutional and By-Laws...</h4>
                            <p class="text-xs text-slate-400 max-w-xs">Fetching verified governing document stream from secure storage.</p>
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
                                    Unable to load the PDF viewer. You can download the official document directly.
                                </p>
                            </div>
                            <div class="flex items-center gap-3">
                                <button type="button"
                                        @click="isLoading = true; hasError = false; $refs.pdfObject.data = previewUrl + '&t=' + Date.now()"
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

                        <!-- Responsive Document Frame with Zoom Scaling -->
                        <div class="w-full h-full flex-1 overflow-auto flex items-center justify-center p-0 sm:p-2 bg-slate-950">
                            <div class="w-full h-full transition-all duration-200 origin-top flex items-center justify-center"
                                 :style="'transform: scale(' + (currentZoom / 100) + '); height: ' + (10000 / currentZoom) + '%; width: ' + (10000 / currentZoom) + '%;'">
                                
                                <object x-ref="pdfObject"
                                        :data="previewUrl + '#toolbar=1&navpanes=1&scrollbar=1&view=FitH'"
                                        type="application/pdf"
                                        class="w-full h-full border-0 rounded-none sm:rounded-lg shadow-inner"
                                        x-on:load="onLoadSuccess()"
                                        x-on:error="onLoadFailure()">

                                    <!-- Fallback iframe if object element cannot render directly on this browser -->
                                    <iframe :src="previewUrl + '#toolbar=1&navpanes=1&scrollbar=1&view=FitH'"
                                            class="w-full h-full border-0"
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
                                </object>
                            </div>
                        </div>

                        <!-- Mobile Fallback Quick-Bar -->
                        <div class="sm:hidden w-full bg-[#172033] border-t border-[#334155] px-4 py-2.5 flex items-center justify-between text-xs text-slate-300">
                            <span>Having trouble viewing on mobile?</span>
                            <a href="{{ route('constitution.preview') }}"
                               target="_blank"
                               class="font-semibold text-[#df747a] hover:underline flex items-center gap-1">
                                <span>Open Full Screen</span>
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
