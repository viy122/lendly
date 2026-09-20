<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition ease-in-out duration-150 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-25']) }}>
    {{ $slot }}
</button>
