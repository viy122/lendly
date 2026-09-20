<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition ease-in-out duration-150 hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 active:from-blue-800 active:to-indigo-800']) }}>
    {{ $slot }}
</button>
