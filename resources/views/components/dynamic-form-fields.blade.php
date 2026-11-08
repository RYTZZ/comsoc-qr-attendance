@props([
    'schema' => null,
    'namePrefix' => 'answers',
    'existingAnswers' => [],
])

@php
    $fields = collect($schema['fields'] ?? [])->where('is_enabled', true)->sortBy('sort_order');
@endphp

@if($fields->isNotEmpty())
<div class="space-y-4">
    @foreach($fields as $field)
        @php
            $key = $field['field_key'];
            $label = $field['label'];
            $type = $field['question_type'];
            $required = (bool) ($field['is_required'] ?? false);
            $helpText = $field['help_text'] ?? null;
            $placeholder = $field['placeholder'] ?? '';
            $options = $field['options'] ?? [];
            $rules = $field['validation_rules'] ?? [];
            $inputName = "{$namePrefix}[{$key}]";
            $errorKey = str_replace(['[', ']'], ['.', ''], $inputName);
            $oldValue = old($errorKey, $existingAnswers[$key] ?? null);
        @endphp

        <div class="p-4 rounded-xl bg-[#0B1120]/70 border border-slate-800/80 space-y-2">
            <label class="block text-xs font-semibold text-slate-200">
                {{ $label }}
                @if($required)
                    <span class="text-rose-400 ml-0.5">*</span>
                @else
                    <span class="text-[10px] text-slate-500 font-normal ml-1">(Optional)</span>
                @endif
            </label>

            @if($helpText)
                <p class="text-[11px] text-slate-400">{{ $helpText }}</p>
            @endif

            @if($type === 'short_answer')
                <input type="text"
                       name="{{ $inputName }}"
                       value="{{ is_scalar($oldValue) ? $oldValue : '' }}"
                       placeholder="{{ $placeholder }}"
                       @if($required) required @endif
                       class="input w-full text-xs bg-[#172033] border-slate-700 text-white">

            @elseif($type === 'paragraph')
                <textarea name="{{ $inputName }}"
                          rows="3"
                          placeholder="{{ $placeholder }}"
                          @if($required) required @endif
                          class="input w-full text-xs bg-[#172033] border-slate-700 text-white">{{ is_scalar($oldValue) ? $oldValue : '' }}</textarea>

            @elseif($type === 'number')
                <input type="number"
                       name="{{ $inputName }}"
                       value="{{ is_scalar($oldValue) ? $oldValue : '' }}"
                       placeholder="{{ $placeholder }}"
                       @if(isset($rules['min']) && $rules['min'] !== '') min="{{ $rules['min'] }}" @endif
                       @if(isset($rules['max']) && $rules['max'] !== '') max="{{ $rules['max'] }}" @endif
                       @if($required) required @endif
                       class="input w-full text-xs bg-[#172033] border-slate-700 text-white">

            @elseif($type === 'date')
                <input type="date"
                       name="{{ $inputName }}"
                       value="{{ is_scalar($oldValue) ? $oldValue : '' }}"
                       @if($required) required @endif
                       class="input w-full text-xs bg-[#172033] border-slate-700 text-white">

            @elseif($type === 'dropdown')
                <select name="{{ $inputName }}"
                        @if($required) required @endif
                        class="select w-full text-xs bg-[#172033] border-slate-700 text-white">
                    <option value="">— Select an option —</option>
                    @foreach($options as $opt)
                        <option value="{{ $opt['value'] }}" @selected((string)$oldValue === (string)$opt['value'])>
                            {{ $opt['label'] }}
                        </option>
                    @endforeach
                </select>

            @elseif($type === 'multiple_choice')
                <div class="space-y-1.5 pt-1">
                    @foreach($options as $opt)
                        <label class="flex items-center gap-2.5 text-xs text-slate-300 cursor-pointer hover:text-white">
                            <input type="radio"
                                   name="{{ $inputName }}"
                                   value="{{ $opt['value'] }}"
                                   @checked((string)$oldValue === (string)$opt['value'])
                                   @if($required) required @endif
                                   class="text-[#560E0F] bg-slate-900 border-slate-700 focus:ring-[#560E0F]">
                            <span>{{ $opt['label'] }}</span>
                        </label>
                    @endforeach
                </div>

            @elseif($type === 'checkboxes')
                @php
                    $checkedValues = is_array($oldValue) ? array_map('strval', $oldValue) : [];
                @endphp
                <div class="space-y-1.5 pt-1">
                    @foreach($options as $opt)
                        <label class="flex items-center gap-2.5 text-xs text-slate-300 cursor-pointer hover:text-white">
                            <input type="checkbox"
                                   name="{{ $inputName }}[]"
                                   value="{{ $opt['value'] }}"
                                   @checked(in_array((string)$opt['value'], $checkedValues, true))
                                   class="rounded text-[#560E0F] bg-slate-900 border-slate-700 focus:ring-[#560E0F]">
                            <span>{{ $opt['label'] }}</span>
                        </label>
                    @endforeach
                </div>

            @elseif($type === 'yes_no')
                <div class="flex items-center gap-6 pt-1">
                    @foreach(['Yes', 'No'] as $yn)
                        <label class="flex items-center gap-2 text-xs text-slate-300 cursor-pointer">
                            <input type="radio"
                                   name="{{ $inputName }}"
                                   value="{{ $yn }}"
                                   @checked((string)$oldValue === $yn)
                                   @if($required) required @endif
                                   class="text-[#560E0F] bg-slate-900 border-slate-700 focus:ring-[#560E0F]">
                            <span>{{ $yn }}</span>
                        </label>
                    @endforeach
                </div>

            @elseif($type === 'file_upload')
                @php
                    $exts = $rules['accepted_file_types'] ?? ['pdf', 'png', 'jpg', 'jpeg', 'doc', 'docx', 'zip'];
                    $maxKb = $rules['max_file_size_kb'] ?? 5120;
                    $acceptAttr = is_array($exts) ? implode(',', array_map(fn($e) => '.' . ltrim($e, '.'), $exts)) : '';
                @endphp
                <input type="file"
                       name="{{ $inputName }}"
                       @if($acceptAttr) accept="{{ $acceptAttr }}" @endif
                       @if($required) required @endif
                       class="block w-full text-xs text-slate-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-[#560E0F] file:text-white hover:file:bg-[#731416] cursor-pointer">
                <p class="text-[10px] text-slate-500">
                    Accepted formats: {{ is_array($exts) ? strtoupper(implode(', ', $exts)) : $exts }} · Max size: {{ round($maxKb / 1024, 1) }} MB
                </p>
            @endif

            @error($errorKey)
                <p class="text-[11px] text-rose-400 font-medium">{{ $message }}</p>
            @enderror
        </div>
    @endforeach
</div>
@endif

