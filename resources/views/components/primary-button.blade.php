<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-[#7A1618] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#621214] active:bg-[#4f0f11] focus:outline-none focus:ring-2 focus:ring-[#7A1618] focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
