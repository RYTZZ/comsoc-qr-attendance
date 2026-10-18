@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 text-gray-900 bg-white focus:border-[#560E0F] focus:ring-[#560E0F] rounded-md shadow-sm w-full disabled:bg-gray-50 disabled:text-gray-500']) }}>
