@props(['title', 'message' => null])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center']) }}>
    @if ($slot->isNotEmpty())
        {{ $slot }}
    @else
        <span class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-300">
            <x-icon name="inbox" class="h-6 w-6" />
        </span>
    @endif
    <p class="text-sm font-semibold text-slate-700">{{ $title }}</p>
    @if ($message)
        <p class="mt-1 text-sm text-slate-500">{{ $message }}</p>
    @endif
</div>
