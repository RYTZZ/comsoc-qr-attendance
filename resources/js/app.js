import './bootstrap';

import Alpine from 'alpinejs';
import { Html5Qrcode } from 'html5-qrcode';
import { createIcons, icons } from 'lucide';
import SignaturePad from 'signature_pad';
import signatureRegistration from './signature';
import constitutionPdfViewer from './constitution-viewer';
import { initMotionSystem } from './motion';

window.Alpine = Alpine;
window.Html5Qrcode = Html5Qrcode;
window.createIcons = createIcons;
window.lucideIcons = icons;
window.SignaturePad = SignaturePad;

document.addEventListener('DOMContentLoaded', () => {
    createIcons({ icons });
    initMotionSystem();
    initScrollManagement();
});

/**
 * Ensures genuine page transitions land at the top of the viewport (scroll position 0),
 * while preserving browser history navigation (back/forward) and explicit hash target anchors (#).
 */
function initScrollManagement() {
    if (!('scrollRestoration' in history)) return;

    const navEntries = performance.getEntriesByType('navigation');
    const navType = navEntries.length > 0 ? navEntries[0].type : '';

    // If navigated via standard link/redirect without an anchor hash, ensure top of page
    if (navType === 'navigate' && !window.location.hash) {
        window.scrollTo({ top: 0, left: 0, behavior: 'instant' });
    }
}

document.addEventListener('alpine:initialized', () => {
    createIcons({ icons });
});

window.refreshIcons = () => {
    createIcons({ icons });
};

Alpine.data('signatureRegistration', signatureRegistration);
Alpine.data('constitutionPdfViewer', constitutionPdfViewer);

Alpine.data('customDropdown', (config) => ({
    name: config.name,
    value: config.value || '',
    placeholder: config.placeholder || 'Select an option',
    options: config.options || [],
    autoSubmit: config.autoSubmit || false,
    open: false,
    highlightedIndex: -1,

    get normalizedOptions() {
        if (Array.isArray(this.options)) {
            return this.options.map(opt => {
                if (typeof opt === 'object' && opt !== null) {
                    return {
                        value: String(opt.value !== undefined ? opt.value : opt.id),
                        label: String(opt.label !== undefined ? opt.label : opt.name)
                    };
                }
                return { value: String(opt), label: String(opt) };
            });
        }
        return Object.entries(this.options).map(([val, lbl]) => ({
            value: String(val),
            label: String(lbl)
        }));
    },

    get displayLabel() {
        const found = this.normalizedOptions.find(o => String(o.value) === String(this.value));
        return found ? found.label : this.placeholder;
    },

    select(val) {
        this.value = val;
        this.open = false;
        this.$dispatch('input', val);
        this.$dispatch('change', val);
        this.$dispatch('dropdown-selected', { name: this.name, value: val });
        if (this.autoSubmit) {
            this.$nextTick(() => {
                const form = this.$el.closest('form');
                if (form) form.submit();
            });
        }
    },

    navigateOptions(direction) {
        if (!this.open) {
            this.open = true;
            this.highlightedIndex = 0;
            return;
        }
        const len = this.normalizedOptions.length;
        if (len === 0) return;
        this.highlightedIndex = (this.highlightedIndex + direction + len) % len;
    },

    selectHighlighted() {
        if (this.open && this.highlightedIndex >= 0 && this.highlightedIndex < this.normalizedOptions.length) {
            this.select(this.normalizedOptions[this.highlightedIndex].value);
        } else {
            this.open = !this.open;
        }
    }
}));

Alpine.start();
