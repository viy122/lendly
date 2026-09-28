@php
    $accentSolid = [
        'amber' => 'border-transparent bg-amber-600 text-white shadow-sm',
        'violet' => 'border-transparent bg-violet-600 text-white shadow-sm',
        'fuchsia' => 'border-transparent bg-fuchsia-600 text-white shadow-sm',
        'emerald' => 'border-transparent bg-emerald-600 text-white shadow-sm',
        'rose' => 'border-transparent bg-rose-600 text-white shadow-sm',
        'cyan' => 'border-transparent bg-cyan-600 text-white shadow-sm',
        'teal' => 'border-transparent bg-teal-600 text-white shadow-sm',
        'slate' => 'border-transparent bg-slate-600 text-white shadow-sm',
    ];
    $accentText = [
        'amber' => 'text-amber-600',
        'violet' => 'text-violet-600',
        'fuchsia' => 'text-fuchsia-600',
        'emerald' => 'text-emerald-600',
        'rose' => 'text-rose-600',
        'cyan' => 'text-cyan-600',
        'teal' => 'text-teal-600',
        'slate' => 'text-slate-600',
    ];
@endphp

<div x-data="{ filtersOpen: false }">
    <x-page-header
        eyebrow="Lendly marketplace"
        title="Find what you need to rent"
        :subtitle="$listings->total() . ' item' . ($listings->total() === 1 ? '' : 's') . ' available right now.'"
    />

    <div class="w-full px-4 py-6 sm:px-6 lg:px-8">
        <div class="mb-4 flex justify-end">
            <a href="{{ route('map') }}" wire:navigate class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                <x-icon name="map-pin" class="h-4 w-4" />
                View on map
            </a>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row">
                <div class="relative flex-1">
                    <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
                    <input type="text" wire:model.live.debounce.400ms="keyword" placeholder="Search for tools, cameras, tents..."
                           class="w-full rounded-lg border-slate-300 py-3 pl-11 pr-4 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <select wire:model.live="category" class="rounded-lg border-slate-300 py-3 pl-4 pr-8 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All categories</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>

                <button type="button" @click="filtersOpen = ! filtersOpen"
                        class="flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                    <x-icon name="filter" class="h-4 w-4" />
                    Filters
                </button>

                <button type="button" wire:click="$refresh"
                        class="rounded-lg bg-gradient-to-r from-blue-600 to-indigo-600 px-8 py-3 text-sm font-semibold text-white shadow-sm hover:from-blue-700 hover:to-indigo-700">
                    Search
                </button>
            </div>

            <div class="mt-4 -mx-1 flex gap-2 overflow-x-auto px-1 pb-1">
                <button type="button" wire:click="$set('category', '')"
                        class="flex shrink-0 items-center gap-2 rounded-full border px-3.5 py-2 text-xs font-semibold transition {{ $category === '' ? 'border-transparent bg-blue-600 text-white shadow-sm' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300' }}">
                    <x-icon name="sparkles" class="h-3.5 w-3.5" />
                    All
                </button>
                @foreach ($categories as $cat)
                    @php($style = $cat->style())
                    <button type="button" wire:click="$set('category', '{{ $cat->id }}')"
                            class="flex shrink-0 items-center gap-2 rounded-full border px-3.5 py-2 text-xs font-semibold transition {{ (string) $category === (string) $cat->id ? ($accentSolid[$style['accent']] ?? $accentSolid['slate']) : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300' }}">
                        <x-icon :name="$style['icon']" class="h-3.5 w-3.5" />
                        {{ $cat->name }}
                    </button>
                @endforeach
            </div>

            <div x-show="filtersOpen" x-transition class="mt-4 grid grid-cols-1 gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 sm:grid-cols-2 lg:grid-cols-4" style="display: none;">
                <select wire:model.live="condition" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Any condition</option>
                    @foreach ($conditions as $option)
                        <option value="{{ $option->value }}">{{ $option->label() }}</option>
                    @endforeach
                </select>

                <select wire:model.live="brand" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Any brand</option>
                    @foreach ($brands as $brandOption)
                        <option value="{{ $brandOption }}">{{ $brandOption }}</option>
                    @endforeach
                </select>

                <select wire:model.live="sort" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="recent">Recently added</option>
                    <option value="cheapest">Cheapest</option>
                    <option value="popular">Most popular</option>
                </select>

                <div class="flex items-center gap-2">
                    <input type="number" wire:model.live.debounce.400ms="minPrice" placeholder="Min ₱/day" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <span class="text-slate-400">–</span>
                    <input type="number" wire:model.live.debounce.400ms="maxPrice" placeholder="Max ₱/day" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <button type="button" wire:click="resetFilters" class="text-left text-sm font-medium text-slate-500 hover:text-slate-700 sm:col-span-2 lg:col-span-4">
                    Clear filters
                </button>
            </div>
        </div>

        <div class="mt-6">
            @if ($listings->isEmpty())
                <x-empty-state title="No listings match your filters" message="Try widening your search or clearing some filters.">
                    <span class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-blue-50 text-blue-300">
                        <x-icon name="search" class="h-6 w-6" />
                    </span>
                </x-empty-state>
            @else
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach ($listings as $listing)
                        @php($style = $listing->category->style())
                        <a href="{{ route('listings.show', $listing) }}" wire:navigate wire:key="listing-{{ $listing->id }}" class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
                            <div class="relative aspect-[4/3] w-full overflow-hidden bg-slate-100">
                                @if ($listing->images->first())
                                    <img src="{{ $listing->images->first()->url() }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                @else
                                    <div class="flex h-full w-full items-center justify-center text-slate-300">No photo</div>
                                @endif
                                <div class="pointer-events-none absolute inset-x-0 bottom-0 h-16 bg-gradient-to-t from-black/40 to-transparent"></div>
                                <x-category-badge :category="$listing->category" class="absolute left-3 top-3 shadow-sm" />
                                <span class="absolute bottom-3 right-3 rounded-full bg-white/95 px-3 py-1 text-sm font-bold text-slate-900 shadow-sm">
                                    ₱{{ number_format($listing->price_per_day, 0) }}<span class="text-xs font-medium text-slate-400">/day</span>
                                </span>
                            </div>
                            <div class="p-4">
                                <h3 class="truncate font-semibold text-slate-900">{{ $listing->name }}</h3>
                                <p class="mt-1 flex items-center gap-1 text-sm text-slate-500">
                                    <x-icon name="map-pin" class="h-3.5 w-3.5 text-slate-400" />
                                    {{ $listing->location }}
                                </p>
                                <div class="mt-3 flex items-center justify-between">
                                    <x-badge color="slate">{{ $listing->condition->label() }}</x-badge>
                                    <span class="flex items-center gap-1 text-xs font-semibold {{ $accentText[$style['accent']] ?? $accentText['slate'] }}">
                                        View details
                                        <x-icon name="chevron-down" class="h-3.5 w-3.5 -rotate-90 transition group-hover:translate-x-0.5" />
                                    </span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>

                <div class="mt-8">
                    {{ $listings->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
