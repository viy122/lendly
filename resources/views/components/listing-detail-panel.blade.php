@props(['title', 'icon', 'subtitle' => null])

<section {{ $attributes->merge(['class' => 'listing-detail-card min-w-0 rounded-2xl border border-slate-200 bg-white p-5 sm:p-6']) }}>
    <div class="flex items-center gap-3">
        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-blue-50 text-blue-600"><x-icon :name="$icon" class="h-5 w-5" aria-hidden="true" /></span>
        <div class="min-w-0">
            <h2 class="text-base font-bold text-slate-900">{{ $title }}</h2>
            @if ($subtitle)
                <p class="mt-1 text-xs leading-5 text-slate-500">{{ $subtitle }}</p>
            @endif
        </div>
    </div>
    {{ $slot }}
</section>
