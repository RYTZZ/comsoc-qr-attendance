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

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            @if($isAvailable)
                <!-- Two-Column Side-by-Side Document Layout on Desktop -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start"
                     x-data="constitutionPdfViewer({ url: '{{ route('constitution.preview') }}?v={{ $document?->version }}-{{ $document?->updated_at?->timestamp }}' })"
                     @fullscreenchange.window="if (!document.fullscreenElement && isFullscreen) { isFullscreen = false; window.dispatchEvent(new CustomEvent('reader-fullscreen', { detail: false })); }"
                     @keydown.escape.window="if (isFullscreen) { exitFullscreen(); }">

                    <!-- ======================================================== -->
                    <!-- LEFT COLUMN: Document Information (Approx 35% on desktop)-->
                    <!-- ======================================================== -->
                    <div x-show="!isFullscreen"
                         class="lg:col-span-4 xl:col-span-4 space-y-5">

                        <!-- Main Governance Card -->
                        <div class="bg-[#172033]/90 border border-[#334155] rounded-2xl p-5 sm:p-6 shadow-xl relative overflow-hidden backdrop-blur-md space-y-4">
                            <div class="absolute -right-12 -top-12 w-44 h-44 rounded-full bg-gradient-to-br from-[#8F3033]/20 to-transparent blur-2xl pointer-events-none"></div>

                            <div class="space-y-2 relative z-10">
                                <div class="flex items-center gap-2 text-xs font-semibold text-[#df747a]">
                                    <i data-lucide="shield-check" class="w-4 h-4 shrink-0"></i>
                                    <span>Official Organizational Governance & Charter</span>
                                </div>
                                <h2 class="text-lg sm:text-xl font-bold text-white tracking-tight">
                                    {{ $document?->title ?? 'COMSOC Constitutional and By-Laws' }}
                                </h2>
                                <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                                    {{ $document?->description ?? 'This official governing document establishes the Computing Society\'s governing principles, policies, executive structure, member rights and responsibilities, electoral rules, and operational procedures across all CICT degree programs.' }}
                                </p>
                            </div>

                            <!-- Campus and Academic Program Information -->
                            <div class="pt-3 border-t border-[#334155]/60 space-y-2 text-xs text-slate-400 relative z-10">
                                <div class="flex items-start gap-2">
                                    <i data-lucide="building" class="w-3.5 h-3.5 text-slate-500 shrink-0 mt-0.5"></i>
                                    <span>Sorsogon State University – Bulan Campus</span>
                                </div>
                                <div class="flex items-start gap-2">
                                    <i data-lucide="graduation-cap" class="w-3.5 h-3.5 text-slate-500 shrink-0 mt-0.5"></i>
                                    <span>CICT: BSCS • BSIT • BSIS • BTVTED-CSS</span>
                                </div>
                            </div>
                        </div>

                        <!-- Compact Document Metadata Panel -->
                        <div class="bg-[#172033]/90 border border-[#334155] rounded-2xl p-5 shadow-xl backdrop-blur-md space-y-3.5">
                            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider flex items-center justify-between border-b border-[#334155]/60 pb-2.5">
                                <span class="flex items-center gap-1.5">
                                    <i data-lucide="file-text" class="w-3.5 h-3.5 text-[#df747a]"></i>
                                    <span>Document Details</span>
                                </span>
                                <span class="font-mono text-[10px] text-slate-500">RATIFIED</span>
                            </div>

                            @if($document && $document->fileExists())
                                <dl class="text-xs space-y-2.5">
                                    <div class="flex justify-between items-center">
                                        <dt class="text-slate-400">File Format:</dt>
                                        <dd class="font-semibold text-slate-200 uppercase flex items-center gap-1.5">
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
                                        <dt class="text-slate-400">Publication Status:</dt>
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

                            <!-- Quick Download CTA Card on Left Column -->
                            <div class="pt-3 border-t border-[#334155]/60 flex flex-col gap-2">
                                <a href="{{ route('constitution.download') }}"
                                   id="btn-download-pdf-sidebar"
                                   class="btn-primary text-xs w-full py-2.5 flex items-center justify-center gap-2 shadow-lg">
                                    <i data-lucide="download" class="w-4 h-4"></i>
                                    <span>Download Official PDF</span>
                                </a>

                                @if(Auth::user()->isAdmin() || Auth::user()->isSuperAdmin())
                                    <a href="{{ route('admin.constitution.index') }}"
                                       class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold bg-amber-500/10 text-amber-300 hover:bg-amber-500/20 border border-amber-500/30 transition">
                                        <i data-lucide="settings" class="w-3.5 h-3.5"></i>
                                        <span>Manage in Admin Console</span>
                                    </a>
                                @endif
                            </div>
                        </div>

                    </div>

                    <!-- ======================================================== -->
                    <!-- RIGHT COLUMN: PDF Preview Section (Approx 65% on desktop)-->
                    <!-- ======================================================== -->
                    <div class="lg:col-span-8 xl:col-span-8 w-full min-w-0"
                         :class="isFullscreen ? 'fixed inset-0 z-50 col-span-12 w-screen h-screen' : ''">

                        <div id="cbl-viewer-card"
                             class="transition-all duration-200 overflow-hidden flex flex-col"
                             :class="isFullscreen 
                                ? 'fixed inset-0 z-50 h-screen w-screen bg-[#0B1120] rounded-none border-0' 
                                : 'bg-[#172033] border border-[#334155] rounded-2xl shadow-2xl'">

                            <!-- Responsive Unified Document Toolbar -->
                            <div class="bg-[#0B1120] border-b border-[#334155] px-3 sm:px-4 py-2.5 sm:py-3 flex flex-wrap sm:flex-nowrap items-center justify-between gap-2.5 shrink-0 z-10">
                                <div class="flex items-center gap-2.5 min-w-0 max-w-full">
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-rose-500/10 border border-rose-500/30 text-rose-400 shrink-0">
                                        <i data-lucide="file-text" class="w-4 h-4"></i>
                                    </span>
                                    <div class="min-w-0">
                                        <h3 class="text-xs sm:text-sm font-bold text-white tracking-tight truncate flex items-center gap-2">
                                            <span>COMSOC Constitutional and By-Laws</span>
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse hidden sm:inline-block"></span>
                                        </h3>
                                        <p class="text-[10px] text-slate-400 truncate hidden sm:block">
                                            Official Ratified Document • Version {{ $document->version }}.0 
                                            <template x-if="totalPages > 0">
                                                <span>• <span x-text="totalPages"></span> Pages</span>
                                            </template>
                                        </p>
                                    </div>
                                </div>

                                <!-- Viewer Action Controls -->
                                <div class="flex items-center gap-1.5 sm:gap-2 shrink-0 ml-auto">
                                    <!-- Zoom Controls -->
                                    <div class="flex items-center bg-[#172033] border border-[#334155] rounded-xl p-0.5 sm:p-1 text-slate-300">
                                        <button type="button"
                                                @click="zoomOut()"
                                                :disabled="currentZoomIdx <= 0"
                                                class="p-1 sm:p-1.5 hover:text-white rounded-lg hover:bg-[#1E293B] disabled:opacity-30 disabled:cursor-not-allowed transition"
                                                title="Zoom Out">
                                            <i data-lucide="zoom-out" class="w-3.5 h-3.5"></i>
                                        </button>
                                        <button type="button"
                                                @click="resetZoom()"
                                                class="px-1.5 sm:px-2 text-[10px] sm:text-[11px] font-mono font-semibold text-slate-300 hover:text-white transition whitespace-nowrap"
                                                title="Reset to Fit Width">
                                            <span x-text="currentZoomLabel">Fit Width</span>
                                        </button>
                                        <button type="button"
                                                @click="zoomIn()"
                                                :disabled="currentZoomIdx >= zoomLevels.length - 1"
                                                class="p-1 sm:p-1.5 hover:text-white rounded-lg hover:bg-[#1E293B] disabled:opacity-30 disabled:cursor-not-allowed transition"
                                                title="Zoom In">
                                            <i data-lucide="zoom-in" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </div>

                                    <!-- Full Screen Button -->
                                    <button type="button"
                                            id="btn-toggle-fullscreen"
                                            @click="toggleFullscreen()"
                                            class="p-1.5 sm:px-3 sm:py-2 bg-[#172033] hover:bg-[#1E293B] border border-[#334155] text-slate-300 hover:text-white rounded-xl transition flex items-center gap-1.5 text-xs font-semibold"
                                            :class="isFullscreen ? 'border-rose-500/40 text-rose-300 bg-rose-500/10 hover:bg-rose-500/20' : ''"
                                            :title="isFullscreen ? 'Exit Full Screen' : 'Full Screen Reading'">
                                        <i :data-lucide="isFullscreen ? 'minimize-2' : 'maximize-2'" class="w-3.5 h-3.5"></i>
                                        <span class="hidden md:inline" x-text="isFullscreen ? 'Exit Full Screen' : 'Full Screen'">Full Screen</span>
                                    </button>

                                    <!-- Download Button inside Toolbar -->
                                    <a href="{{ route('constitution.download') }}"
                                       id="btn-download-pdf-viewer"
                                       class="btn-primary text-xs flex items-center gap-1.5 py-1.5 px-2.5 sm:px-3 rounded-xl shadow"
                                       title="Download Official PDF">
                                        <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                        <span class="hidden sm:inline">Download</span>
                                    </a>
                                </div>
                            </div>

                            <!-- PDF Viewport Container (750-900px on desktop, 65-75vh on mobile) -->
                            <div id="pdf-viewer-container"
                                 class="relative w-full bg-slate-950 flex-1 flex flex-col items-stretch justify-start overflow-hidden transition-all duration-200"
                                 :class="isFullscreen 
                                    ? 'h-[calc(100vh-3.5rem)]' 
                                    : 'h-[68vh] min-h-[480px] sm:min-h-[580px] lg:h-[min(78vh,900px)] lg:min-h-[750px] xl:min-h-[800px]'">

                                <!-- Loading Overlay -->
                                <div x-show="isLoading && !hasError"
                                     class="absolute inset-0 z-20 bg-slate-950/85 backdrop-blur-sm flex flex-col items-center justify-center p-6 text-center">
                                    <div class="relative w-12 h-12 sm:w-14 sm:h-14 mb-3">
                                        <div class="w-full h-full rounded-full border-4 border-[#8F3033]/30 border-t-[#df747a] animate-spin"></div>
                                        <div class="absolute inset-0 flex items-center justify-center text-[#df747a]">
                                            <i data-lucide="scroll-text" class="w-5 h-5"></i>
                                        </div>
                                    </div>
                                    <h4 class="text-xs sm:text-sm font-bold text-white mb-0.5">Loading Complete Document...</h4>
                                    <p class="text-[11px] text-slate-400 max-w-xs">Rendering all pages in high-resolution vector fidelity.</p>
                                </div>

                                <!-- Error State Overlay -->
                                <div x-show="hasError"
                                     x-cloak
                                     class="absolute inset-0 z-20 bg-slate-950/95 flex flex-col items-center justify-center p-6 text-center space-y-4">
                                    <div class="w-12 h-12 rounded-2xl bg-rose-500/10 border border-rose-500/30 flex items-center justify-center text-rose-400">
                                        <i data-lucide="alert-circle" class="w-6 h-6"></i>
                                    </div>
                                    <div>
                                        <h4 class="text-sm sm:text-base font-bold text-white">Document Preview Encountered An Error</h4>
                                        <p class="text-xs text-slate-400 mt-1 max-w-md" x-text="errorMessage">
                                            Unable to render the document. You can download the official document directly.
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <button type="button"
                                                @click="retry()"
                                                class="btn-secondary text-xs flex items-center gap-2">
                                            <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                                            <span>Retry Preview</span>
                                        </button>
                                        <a href="{{ route('constitution.download') }}" class="btn-primary text-xs flex items-center gap-2">
                                            <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                            <span>Download File</span>
                                        </a>
                                    </div>
                                </div>

                                <!-- Scrollable Multi-Page Pages Container -->
                                <div x-ref="pagesContainer"
                                     class="w-full h-full flex-1 overflow-y-auto overflow-x-auto p-3 sm:p-5 flex flex-col items-center space-y-4 select-text focus:outline-none"
                                     tabindex="0"
                                     aria-label="Constitutional and By-Laws multi-page document stream">
                                </div>

                                <!-- Mobile Compact Helper Footnote (Normal mode only) -->
                                <div x-show="!isFullscreen"
                                     class="sm:hidden w-full bg-[#172033]/90 border-t border-[#334155] px-3 py-1.5 flex items-center justify-between text-[11px] text-slate-400 shrink-0">
                                    <span>Scroll down to read all pages</span>
                                    <button type="button"
                                            @click="enterFullscreen()"
                                            class="font-semibold text-[#df747a] hover:underline flex items-center gap-1">
                                        <span>Full Screen</span>
                                        <i data-lucide="maximize-2" class="w-3 h-3"></i>
                                    </button>
                                </div>
                            </div>
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
