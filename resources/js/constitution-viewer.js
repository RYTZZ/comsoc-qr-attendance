import * as pdfjsLib from 'pdfjs-dist';

// Configure worker URL for Vite bundling with pdfjs-dist v4+
pdfjsLib.GlobalWorkerOptions.workerSrc = new URL(
    'pdfjs-dist/build/pdf.worker.min.mjs',
    import.meta.url
).toString();

export default function constitutionPdfViewer(config) {
    return {
        url: config.url,
        isFullscreen: false,
        isLoading: true,
        hasError: false,
        errorMessage: '',
        pdfDoc: null,
        totalPages: 0,
        renderedPages: 0,
        
        // Zoom configuration
        zoomLevels: ['page-width', 'page-fit', '75', '100', '125', '150'],
        zoomLabels: {
            'page-width': 'Fit Width',
            'page-fit': 'Fit Page',
            '75': '75%',
            '100': '100%',
            '125': '125%',
            '150': '150%'
        },
        currentZoomIdx: 0, // 'page-width' is default
        renderTaskQueue: [],
        isRendering: false,

        get currentZoomMode() {
            return this.zoomLevels[this.currentZoomIdx];
        },

        get currentZoomLabel() {
            return this.zoomLabels[this.currentZoomMode] || '100%';
        },

        init() {
            this.loadPdf();

            // Handle resize / orientation change to re-fit if in 'page-width' or 'page-fit'
            let resizeTimer;
            window.addEventListener('resize', () => {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(() => {
                    if (this.currentZoomMode === 'page-width' || this.currentZoomMode === 'page-fit') {
                        this.renderAllPages();
                    }
                }, 200);
            });
        },

        async loadPdf() {
            this.isLoading = true;
            this.hasError = false;
            this.errorMessage = '';

            try {
                const loadingTask = pdfjsLib.getDocument({
                    url: this.url,
                    cMapUrl: 'https://unpkg.com/pdfjs-dist@3.11.174/cmaps/',
                    cMapPacked: true,
                });

                this.pdfDoc = await loadingTask.promise;
                this.totalPages = this.pdfDoc.numPages;
                this.isLoading = false;

                await this.$nextTick();
                await this.renderAllPages();
            } catch (err) {
                console.error('PDF Loading Error:', err);
                this.isLoading = false;
                this.hasError = true;
                this.errorMessage = err.message || 'Unable to stream or parse the document.';
            }
        },

        async renderAllPages() {
            if (!this.pdfDoc) return;
            const container = this.$refs.pagesContainer;
            if (!container) return;

            // Clear previous canvas elements
            container.innerHTML = '';
            this.renderedPages = 0;

            const containerWidth = container.clientWidth || 600;
            const containerHeight = container.clientHeight || 750;

            for (let pageNum = 1; pageNum <= this.totalPages; pageNum++) {
                try {
                    const page = await this.pdfDoc.getPage(pageNum);
                    const unscaledViewport = page.getViewport({ scale: 1 });

                    // Compute scale based on selected zoom mode
                    let scale = 1.0;
                    const mode = this.currentZoomMode;

                    if (mode === 'page-width') {
                        // Fit to container width with padding subtracted
                        const availableWidth = Math.max(280, containerWidth - 32);
                        scale = availableWidth / unscaledViewport.width;
                    } else if (mode === 'page-fit') {
                        const availableWidth = Math.max(280, containerWidth - 32);
                        const availableHeight = Math.max(300, containerHeight - 32);
                        const scaleW = availableWidth / unscaledViewport.width;
                        const scaleH = availableHeight / unscaledViewport.height;
                        scale = Math.min(scaleW, scaleH);
                    } else {
                        scale = (parseFloat(mode) || 100) / 100;
                    }

                    // Mobile density devicePixelRatio adjustment for crisp text
                    const outputScale = window.devicePixelRatio || 1;
                    const viewport = page.getViewport({ scale });

                    // Outer page wrapper card
                    const pageCard = document.createElement('div');
                    pageCard.className = 'cbl-pdf-page-card bg-white shadow-2xl rounded-sm mx-auto my-3 relative overflow-hidden transition-all';
                    pageCard.style.width = `${Math.floor(viewport.width)}px`;
                    pageCard.style.maxWidth = '100%';

                    // Canvas setup
                    const canvas = document.createElement('canvas');
                    const context = canvas.getContext('2d');

                    canvas.width = Math.floor(viewport.width * outputScale);
                    canvas.height = Math.floor(viewport.height * outputScale);
                    canvas.style.width = `${Math.floor(viewport.width)}px`;
                    canvas.style.height = `${Math.floor(viewport.height)}px`;
                    canvas.style.display = 'block';

                    const transform = outputScale !== 1
                        ? [outputScale, 0, 0, outputScale, 0, 0]
                        : null;

                    const renderContext = {
                        canvasContext: context,
                        transform: transform,
                        viewport: viewport,
                    };

                    pageCard.appendChild(canvas);

                    // Page number badge
                    const badge = document.createElement('div');
                    badge.className = 'absolute bottom-2 right-2 px-2 py-0.5 rounded text-[10px] font-mono font-semibold bg-slate-900/80 text-white select-none pointer-events-none';
                    badge.textContent = `Page ${pageNum} of ${this.totalPages}`;
                    pageCard.appendChild(badge);

                    container.appendChild(pageCard);

                    await page.render(renderContext).promise;
                    this.renderedPages = pageNum;
                } catch (pageErr) {
                    console.error(`Error rendering page ${pageNum}:`, pageErr);
                }
            }
        },

        zoomIn() {
            if (this.currentZoomIdx < this.zoomLevels.length - 1) {
                this.currentZoomIdx++;
                this.renderAllPages();
            }
        },

        zoomOut() {
            if (this.currentZoomIdx > 0) {
                this.currentZoomIdx--;
                this.renderAllPages();
            }
        },

        resetZoom() {
            this.currentZoomIdx = 0; // Fit Width default
            this.renderAllPages();
        },

        toggleFullscreen() {
            if (!this.isFullscreen) {
                this.enterFullscreen();
            } else {
                this.exitFullscreen();
            }
        },

        enterFullscreen() {
            this.isFullscreen = true;
            window.dispatchEvent(new CustomEvent('reader-fullscreen', { detail: true }));
            const el = document.getElementById('cbl-viewer-card');
            if (el && el.requestFullscreen && !document.fullscreenElement) {
                el.requestFullscreen().catch(() => {});
            }
            setTimeout(() => this.renderAllPages(), 150);
        },

        exitFullscreen() {
            this.isFullscreen = false;
            window.dispatchEvent(new CustomEvent('reader-fullscreen', { detail: false }));
            if (document.fullscreenElement && document.exitFullscreen) {
                document.exitFullscreen().catch(() => {});
            }
            setTimeout(() => this.renderAllPages(), 150);
        },

        retry() {
            this.loadPdf();
        }
    };
}
