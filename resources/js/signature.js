import SignaturePad from 'signature_pad';

/**
 * Alpine component wrapping szimek/signature_pad.
 * Lifecycle: init() creates the pad once; destroy() (called by Alpine on teardown) removes all listeners.
 * Client-side checks are usability only; the server independently validates and encrypts the payload.
 */
export default (config) => ({
    pad: null,
    registered: config.registered,
    state: config.state || (config.registered ? 'registered' : 'required'),
    label: config.label || (config.registered ? 'Signature Registered' : 'Signature Required'),
    version: config.version,
    replacing: false,
    consent: false,
    busy: false,
    error: '',
    success: '',
    previewUrl: null,
    ratio: 1,
    _onResize: null,

    init() {
        const canvas = this.$refs.canvas;
        this.pad = new SignaturePad(canvas, {
            penColor: '#000000',
            backgroundColor: 'rgba(0,0,0,0)', // transparent so the PDF cell shows through
            minWidth: 0.8,
            maxWidth: 2.4,
        });
        this._onResize = () => this.resizeCanvas();
        window.addEventListener('resize', this._onResize);
        window.addEventListener('orientationchange', this._onResize);
        this.$nextTick(() => this.resizeCanvas());
        if (this.registered) this.refreshPreview();
    },

    destroy() {
        window.removeEventListener('resize', this._onResize);
        window.removeEventListener('orientationchange', this._onResize);
        if (this.pad) this.pad.off(); // detaches mouse/touch/pointer handlers
        this.pad = null;
    },

    resizeCanvas() {
        const canvas = this.$refs.canvas;
        if (!canvas || !canvas.offsetWidth) return;
        const saved = this.pad.toData(); // keep strokes across resize (vector data, no distortion)
        this.ratio = Math.max(window.devicePixelRatio || 1, 1);
        canvas.width = canvas.offsetWidth * this.ratio;
        canvas.height = canvas.offsetHeight * this.ratio;
        canvas.getContext('2d').scale(this.ratio, this.ratio);
        this.pad.clear();
        if (saved.length) this.pad.fromData(saved);
    },

    clear() {
        this.pad.clear();
        this.error = '';
    },

    get canDraw() {
        return !this.registered || this.replacing;
    },

    startReplace() {
        this.replacing = true;
        this.success = '';
        this.$nextTick(() => this.resizeCanvas());
    },

    cancelReplace() {
        this.replacing = false;
        this.pad.clear();
        this.error = '';
    },

    // Downscale so very high DPI screens stay within the server's image-dimension limits.
    exportDataUrl() {
        const src = this.$refs.canvas;
        const maxW = 1000;
        const scale = Math.min(1, maxW / src.width);
        const out = document.createElement('canvas');
        out.width = Math.max(1, Math.round(src.width * scale));
        out.height = Math.max(1, Math.round(src.height * scale));
        out.getContext('2d').drawImage(src, 0, 0, out.width, out.height);
        return out.toDataURL('image/png');
    },

    async refreshPreview() {
        // Own signature only; server decides from the session, no ids sent. Cache-bust after replacement.
        this.previewUrl = config.previewUrl + '?t=' + Date.now();
    },

    async reset() {
        this.error = '';
        this.success = '';

        const confirmed = window.appConfirm
            ? await window.appConfirm({
                title: 'Reset Active Signature?',
                message: 'Resetting will deactivate your currently registered signature. A new handwritten signature must be registered before your signature can be used for future official attendance sheet exports. Your previous signature history remains securely archived.',
                confirmText: 'Reset Signature',
                cancelText: 'Keep Current Signature',
                type: 'danger',
            })
            : window.confirm('Resetting will deactivate your current signature. You will need to register a new signature for future attendance sheets. Continue?');

        if (!confirmed) {
            return;
        }

        this.busy = true;
        try {
            const resetUrl = config.resetUrl || config.url;
            const res = await window.axios.delete(resetUrl);
            this.registered = false;
            this.state = res.data.state || 'required';
            this.label = res.data.label || 'Signature Required';
            this.version = res.data.version || null;
            this.replacing = false;
            this.previewUrl = null;
            this.consent = false;
            this.success = res.data.message || 'Signature reset successfully. You may now draw a new signature.';

            if (this.pad) {
                this.pad.clear();
            }
            this.$nextTick(() => {
                this.resizeCanvas();
                window.dispatchEvent(new CustomEvent('signature-status-updated', {
                    detail: {
                        registered: false,
                        state: this.state,
                        label: this.label,
                        version: this.version,
                    }
                }));
            });
        } catch (e) {
            const d = e.response && e.response.data;
            this.error = (d && (d.message || (d.errors && Object.values(d.errors)[0][0])))
                || 'The signature could not be reset. Please try again.';
        } finally {
            this.busy = false;
        }
    },

    async save() {
        this.error = '';
        this.success = '';
        if (this.pad.isEmpty()) {
            this.error = 'Please draw your signature first.';
            return;
        }
        if (!this.registered && !this.consent) {
            this.error = 'Please acknowledge the consent statement before saving.';
            return;
        }
        this.busy = true;
        try {
            const payload = { signature: this.exportDataUrl() };
            if (!this.registered) payload.consent = true;
            const res = this.registered
                ? await window.axios.put(config.url, payload)
                : await window.axios.post(config.url, payload);
            this.registered = res.data.registered;
            this.state = res.data.state || (res.data.registered ? 'registered' : 'required');
            this.label = res.data.label || (res.data.registered ? 'Signature Registered' : 'Signature Required');
            this.version = res.data.version;
            this.success = res.data.message;
            this.replacing = false;
            this.pad.clear();
            this.refreshPreview();

            window.dispatchEvent(new CustomEvent('signature-status-updated', {
                detail: {
                    registered: this.registered,
                    state: this.state,
                    label: this.label,
                    version: this.version,
                }
            }));
        } catch (e) {
            const d = e.response && e.response.data;
            this.error = (d && (d.message || (d.errors && Object.values(d.errors)[0][0])))
                || 'The signature could not be saved. Please try again.';
        } finally {
            this.busy = false;
        }
    },
});

