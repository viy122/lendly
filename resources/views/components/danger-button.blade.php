<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center gap-2 rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition ease-in-out duration-150 hover:bg-rose-500 active:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2']) }}>
    {{ $slot }}
</button>
