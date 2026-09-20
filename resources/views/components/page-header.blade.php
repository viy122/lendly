@props(['eyebrow' => null, 'title', 'subtitle' => null, 'maxWidth' => 'max-w-7xl'])

<div class="border-b border-blue-200 bg-gradient-to-r from-blue-100 to-indigo-100 shadow-sm">
    <div {{ $attributes->merge(['class' => 'mx-auto flex ' . $maxWidth . ' flex-wrap items-center justify-between gap-3 px-4 py-2.5 sm:px-6 lg:px-8']) }}>
        <div>
            @if ($eyebrow)
                <p class="text-[11px] font-semibold uppercase tracking-wide text-blue-600">{{ $eyebrow }}</p>
            @endif
            <h1 class="{{ $eyebrow ? 'mt-0.5' : '' }} text-xl font-bold tracking-tight text-slate-900">{{ $title }}</h1>
            @if ($subtitle)
                <p class="mt-0.5 text-sm text-slate-500">{{ $subtitle }}</p>
            @endif
        </div>

        @isset($actions)
            <div class="flex items-center gap-2">
                {{ $actions }}
            </div>
        @endisset
    </div>
</div>
