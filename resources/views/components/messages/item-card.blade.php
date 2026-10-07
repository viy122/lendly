@props(['listing'])

<article class="ml-auto w-full max-w-xs overflow-hidden rounded-2xl rounded-tr-md border border-blue-100 bg-white shadow-sm" aria-label="Item being discussed">
    <p class="border-b border-blue-100 bg-blue-50 px-4 py-2 text-xs font-medium text-blue-700">You’re chatting about this item</p>
    <div class="flex items-center gap-3 p-4">
        @if ($listing->images->isNotEmpty())
            <div class="h-20 w-20 shrink-0" x-data="{ photoFailed: false }">
                <img src="{{ $listing->images->first()->url() }}" alt="{{ $listing->name }}" x-show="!photoFailed" x-on:error="photoFailed = true" class="h-full w-full rounded-lg bg-slate-50 object-contain" />
                <span x-show="photoFailed" x-cloak class="grid h-full w-full place-items-center rounded-lg bg-slate-100 text-slate-400" aria-label="Item photo unavailable"><x-icon name="camera" class="h-8 w-8" aria-hidden="true" /></span>
            </div>
        @else
            <span class="grid h-20 w-20 shrink-0 place-items-center rounded-lg bg-slate-100 text-slate-400" aria-label="No item photo"><x-icon name="camera" class="h-8 w-8" aria-hidden="true" /></span>
        @endif
        <div class="min-w-0">
            <h2 class="break-words text-sm font-semibold text-slate-900">{{ $listing->name }}</h2>
            <p class="mt-1 text-sm font-bold text-blue-600">₱{{ number_format($listing->price_per_day, 2) }} <span class="text-xs font-normal text-slate-500">/ day</span></p>
            <p class="mt-1 break-words text-xs text-slate-500">{{ $listing->location }}</p>
        </div>
    </div>
    <div class="border-t border-slate-100 px-4 py-2 text-right">
        @if (! $listing->trashed() && $listing->isPublished())
            <a href="{{ route('listings.show', $listing) }}" wire:navigate class="inline-flex min-h-9 items-center gap-1 rounded-lg px-2 text-xs font-semibold text-blue-600 hover:bg-blue-50 focus:outline-none focus:ring-4 focus:ring-blue-100">View details <x-icon name="chevron-down" class="h-3 w-3 -rotate-90" aria-hidden="true" /></a>
        @else
            <span class="text-xs text-slate-500">This item is no longer listed</span>
        @endif
    </div>
</article>
