@props([
    'name',
    'id' => null,
    'options' => [],
    'value' => null,
    'placeholder' => 'Select an option',
    'required' => false,
    'searchable' => false,
])

@php
    $elementId = $id ?? $name;
    $initialValue = old($name, $value ?? '');
@endphp

<div x-data="customDropdown({
        name: '{{ $name }}',
        value: '{{ addslashes($initialValue) }}',
        placeholder: '{{ addslashes($placeholder) }}',
        options: {{ json_encode($options) }}
    })"
    class="relative w-full text-left"
    @click.away="open = false"
    @keydown.escape.window="open = false">

    <input type="hidden"
           :name="name"
           id="{{ $elementId }}"
           :value="value"
           {{ $required ? 'required' : '' }}>

    <button type="button"
            @click="open = !open"
            @keydown.arrow-down.prevent="navigateOptions(1)"
            @keydown.arrow-up.prevent="navigateOptions(-1)"
            @keydown.enter.prevent="selectHighlighted()"
            :class="open ? 'ring-2 ring-[#7A1618] border-transparent shadow-md' : 'border-slate-700 hover:border-slate-600'"
            class="w-full flex items-center justify-between gap-2 rounded-xl bg-[#12141c] border px-3.5 py-2.5 text-sm text-left transition focus:outline-none focus:ring-2 focus:ring-[#7A1618] focus:border-transparent">
        <span class="truncate block"
              :class="value ? 'text-white font-medium' : 'text-slate-500'"
              x-text="displayLabel">
        </span>

        <span class="pointer-events-none flex items-center text-slate-400 transition-transform duration-200"
              :class="open ? 'rotate-180 text-[#cc7478]' : ''">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </span>
    </button>

    <div x-show="open"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         x-cloak
         class="absolute z-50 mt-1.5 w-full rounded-xl bg-[#1e222d] border border-slate-700/80 shadow-2xl py-1 text-sm max-h-60 overflow-y-auto focus:outline-none">
        
        <template x-for="(opt, idx) in normalizedOptions" :key="opt.value">
            <div @click="select(opt.value)"
                 @mouseenter="highlightedIndex = idx"
                 :class="{
                     'bg-[#7A1618] text-white font-semibold': value === opt.value,
                     'bg-[#303644] text-white': highlightedIndex === idx && value !== opt.value,
                     'text-slate-300 hover:text-white': value !== opt.value && highlightedIndex !== idx
                 }"
                 class="flex items-center justify-between px-3.5 py-2.5 cursor-pointer transition select-none text-xs sm:text-sm">
                <span x-text="opt.label" class="truncate"></span>
                <span x-show="value === opt.value" class="text-white ml-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                </span>
            </div>
        </template>
    </div>
</div>
