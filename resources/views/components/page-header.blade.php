@props(['eyebrow' => null, 'title', 'subtitle' => null, 'maxWidth' => 'w-full'])

<div class="relative overflow-hidden bg-gradient-to-r from-blue-600 via-blue-600 to-indigo-700 shadow-sm">
    <svg class="pointer-events-none absolute inset-0 h-full w-full opacity-[0.08]" aria-hidden="true">
        <defs>
            <pattern id="page-header-pattern" width="64" height="64" patternUnits="userSpaceOnUse">
                <path d="M12 20l8-4 8 4v10l-8 4-8-4z" fill="none" stroke="white" stroke-width="1.5" />
                <path d="M12 20v10l8 4M28 20v10l-8 4" fill="none" stroke="white" stroke-width="1.5" />
                <path d="M46 38a6 6 0 1 1 5-9.5" fill="none" stroke="white" stroke-width="1.5" stroke-linecap="round" />
                <path d="M49 26l2 2.5-2 2" fill="none" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
            </pattern>
        </defs>
        <rect width="100%" height="100%" fill="url(#page-header-pattern)" />
    </svg>

    <div {{ $attributes->merge(['class' => 'relative mx-auto flex min-h-20 ' . $maxWidth . ' flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-6 lg:px-8']) }}>
        <div class="flex items-center gap-3">
            <button type="button" @click="$dispatch('toggle-sidebar')" class="rounded-md p-1 text-blue-100 hover:bg-white/10 hover:text-white lg:hidden">
                <x-icon name="menu" class="h-5 w-5" />
            </button>

            <div>
                @if ($eyebrow)
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-blue-100">{{ $eyebrow }}</p>
                @endif
                <h1 class="{{ $eyebrow ? 'mt-0.5' : '' }} text-xl font-bold tracking-tight text-white">{{ $title }}</h1>
                @if ($subtitle)
                    <p class="mt-0.5 text-sm text-blue-100">{{ $subtitle }}</p>
                @endif
            </div>
        </div>

        @auth
            <livewire:notifications.bell />
        @endauth
    </div>
</div>
