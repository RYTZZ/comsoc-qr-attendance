@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 text-gray-900 placeholder-gray-400 bg-white focus:border-[#7A1618] focus:ring-[#7A1618] rounded-md shadow-sm w-full disabled:bg-gray-50 disabled:text-gray-500']) }}>
