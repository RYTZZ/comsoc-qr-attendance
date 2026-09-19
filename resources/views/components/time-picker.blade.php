@props([
    'name',
    'id' => null,
    'value' => null,
    'required' => false,
    'placeholder' => 'Select time',
    'class' => '',
    'disabled' => false,
])

@php
    $elementId = $id ?? str_replace(['[', ']'], ['_', ''], $name);
    $initialRaw = old($name, $value ?? '');
    if ($initialRaw && strlen($initialRaw) > 5) {
        $initialRaw = substr($initialRaw, 0, 5);
    }
@endphp

<div x-data="comsocTimePicker({
        name: '{{ $name }}',
        value: '{{ $initialRaw }}',
        placeholder: '{{ $placeholder }}',
        disabled: {{ $disabled ? 'true' : 'false' }}
    })"
    class="relative {{ $class }}"
    @click.outside="open = false"
    @keydown.escape.window="open = false">

    <input type="hidden"
           :name="name"
           id="{{ $elementId }}"
           :value="value"
           {{ $required ? 'required' : '' }}
           @input="$dispatch('time-change', { name: name, value: value })">

    <button type="button"
            @click="if(!disabled) open = !open"
            :disabled="disabled"
            :aria-expanded="open.toString()"
            class="w-full flex items-center justify-between gap-2 rounded-lg bg-[#12141c] border px-3 py-2 text-sm text-left transition focus:outline-none focus:ring-2 focus:ring-[#7A1618] focus:border-transparent min-h-[38px] sm:min-h-[42px]"
            :class="[
                disabled ? 'opacity-50 cursor-not-allowed border-slate-800' : 'cursor-pointer hover:border-slate-600',
                open ? 'ring-2 ring-[#7A1618] border-transparent shadow-md' : 'border-slate-700'
            ]">
        <div class="flex items-center gap-2 truncate">
            <svg class="w-4 h-4 shrink-0 transition-colors" :class="value ? 'text-[#e07e83]' : 'text-slate-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10" stroke-width="1.8"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6v6l4 2"/>
            </svg>
            <span class="truncate block font-mono text-xs sm:text-sm font-medium"
                  :class="value ? 'text-white' : 'text-slate-500'"
                  x-text="displayLabel">
            </span>
        </div>

        <div class="flex items-center gap-1 shrink-0">
            <template x-if="value && !disabled">
                <span @click.stop="clearTime()" role="button" tabindex="0" class="p-0.5 rounded text-slate-500 hover:text-red-400 hover:bg-slate-800/80 transition" title="Clear">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </span>
            </template>
            <span class="text-slate-400 transition-transform duration-200" :class="open ? 'rotate-180 text-[#e07e83]' : ''">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </span>
        </div>
    </button>

    <div x-show="open"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="transform opacity-0 scale-95 -translate-y-1"
         x-transition:enter-end="transform opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="transform opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="transform opacity-0 scale-95 -translate-y-1"
         class="absolute z-50 mt-1.5 w-64 rounded-xl bg-[#171a23] border border-slate-700 shadow-2xl p-3 space-y-3"
         style="display: none;">
        
        <div class="flex items-center justify-between border-b border-slate-800 pb-2">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Select Time</span>
            <div class="flex items-center bg-slate-950/80 rounded-lg p-0.5 border border-slate-800">
                <button type="button"
                        @click="setAmPm('AM')"
                        :class="ampm === 'AM' ? 'bg-[#7A1618] text-white font-bold shadow' : 'text-slate-400 hover:text-white'"
                        class="px-2 py-0.5 text-xs rounded transition">
                    AM
                </button>
                <button type="button"
                        @click="setAmPm('PM')"
                        :class="ampm === 'PM' ? 'bg-[#7A1618] text-white font-bold shadow' : 'text-slate-400 hover:text-white'"
                        class="px-2 py-0.5 text-xs rounded transition">
                    PM
                </button>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-2 text-center font-mono">
            <div>
                <label class="text-[10px] text-slate-500 uppercase tracking-wider font-sans font-semibold block mb-1">Hour</label>
                <div class="max-h-36 overflow-y-auto space-y-1 pr-1 scrollbar-thin">
                    <template x-for="h in hours" :key="h">
                        <button type="button"
                                @click="setHour(h)"
                                :class="selectedHour === h ? 'bg-[#7A1618] text-white font-bold' : 'text-slate-300 hover:bg-slate-800/80'"
                                class="w-full py-1 text-xs rounded-md transition text-center"
                                x-text="h">
                        </button>
                    </template>
                </div>
            </div>
            <div>
                <label class="text-[10px] text-slate-500 uppercase tracking-wider font-sans font-semibold block mb-1">Minute</label>
                <div class="max-h-36 overflow-y-auto space-y-1 pr-1 scrollbar-thin">
                    <template x-for="m in minutes" :key="m">
                        <button type="button"
                                @click="setMinute(m)"
                                :class="selectedMinute === m ? 'bg-[#7A1618] text-white font-bold' : 'text-slate-300 hover:bg-slate-800/80'"
                                class="w-full py-1 text-xs rounded-md transition text-center"
                                x-text="m">
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <div class="pt-2 border-t border-slate-800 flex items-center justify-between">
            <span class="text-[11px] font-mono font-semibold text-[#e07e83]" x-text="previewTimeText"></span>
            <button type="button"
                    @click="confirmSelection()"
                    class="btn-primary btn-sm py-1 px-3 text-xs">
                Apply
            </button>
        </div>
    </div>
</div>

@once
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('comsocTimePicker', (config) => ({
        name: config.name,
        value: config.value || '',
        placeholder: config.placeholder || 'Select time',
        disabled: config.disabled || false,
        open: false,
        hours: ['12', '01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11'],
        minutes: ['00', '05', '10', '15', '20', '25', '30', '35', '40', '45', '50', '55'],
        selectedHour: '08',
        selectedMinute: '00',
        ampm: 'AM',

        init() {
            if (this.value) {
                this.parseValue(this.value);
            }
            this.$watch('value', (val) => {
                if (val) this.parseValue(val);
                this.$dispatch('input', val);
            });
        },

        parseValue(val) {
            const parts = val.split(':');
            if (parts.length >= 2) {
                let h = parseInt(parts[0], 10);
                const m = parts[1].padStart(2, '0');
                if (h === 0) {
                    this.selectedHour = '12';
                    this.ampm = 'AM';
                } else if (h === 12) {
                    this.selectedHour = '12';
                    this.ampm = 'PM';
                } else if (h > 12) {
                    this.selectedHour = String(h - 12).padStart(2, '0');
                    this.ampm = 'PM';
                } else {
                    this.selectedHour = String(h).padStart(2, '0');
                    this.ampm = 'AM';
                }
                this.selectedMinute = m;
            }
        },

        setHour(h) {
            this.selectedHour = h;
            this.updateValue();
        },

        setMinute(m) {
            this.selectedMinute = m;
            this.updateValue();
        },

        setAmPm(val) {
            this.ampm = val;
            this.updateValue();
        },

        updateValue() {
            let h = parseInt(this.selectedHour, 10);
            if (this.ampm === 'AM') {
                if (h === 12) h = 0;
            } else {
                if (h < 12) h += 12;
            }
            const hStr = String(h).padStart(2, '0');
            const mStr = String(this.selectedMinute).padStart(2, '0');
            this.value = `${hStr}:${mStr}`;
        },

        confirmSelection() {
            this.updateValue();
            this.open = false;
        },

        clearTime() {
            this.value = '';
            this.open = false;
        },

        get displayLabel() {
            if (!this.value) return this.placeholder;
            let parts = this.value.split(':');
            if (parts.length < 2) return this.value;
            let h = parseInt(parts[0], 10);
            let m = parts[1];
            let period = h >= 12 ? 'PM' : 'AM';
            let formattedH = h % 12;
            if (formattedH === 0) formattedH = 12;
            return `${String(formattedH).padStart(2, '0')}:${m} ${period}`;
        },

        get previewTimeText() {
            return `${this.selectedHour}:${this.selectedMinute} ${this.ampm}`;
        }
    }));
});
</script>
@endonce
