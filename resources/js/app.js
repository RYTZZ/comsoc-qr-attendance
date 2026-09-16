import './bootstrap';

import Alpine from 'alpinejs';
import { Html5Qrcode } from 'html5-qrcode';

window.Alpine = Alpine;
window.Html5Qrcode = Html5Qrcode;

Alpine.data('customDropdown', (config) => ({
    name: config.name,
    value: config.value || '',
    placeholder: config.placeholder || 'Select an option',
    options: config.options || [],
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
