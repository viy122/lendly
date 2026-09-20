<div>
    <div class="border-b border-blue-200 bg-gradient-to-r from-blue-100 to-indigo-100 shadow-sm">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-blue-600">Lendly marketplace</p>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Find items near you</h1>
                    <p class="mt-1 text-sm text-slate-500">Search for an item and we'll show the nearest available listings.</p>
                </div>
                <a href="{{ route('listings.index') }}" wire:navigate class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                    <x-icon name="list" class="h-4 w-4" />
                    Back to list view
                </a>
            </div>

            <div class="mt-6 flex flex-col gap-3 sm:flex-row" x-data="{ locating: false }">
                <div class="relative flex-1">
                    <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
                    <input type="text" wire:model.live.debounce.400ms="keyword" placeholder="Search for tools, cameras, tents..."
                           class="w-full rounded-lg border-slate-300 py-3 pl-11 pr-4 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <button type="button"
                        @click="locating = true; $dispatch('locate-user')"
                        @location-found.window="locating = false"
                        class="flex items-center justify-center gap-2 rounded-lg border px-5 py-3 text-sm font-semibold {{ $centerLat ? 'border-blue-300 bg-blue-50 text-blue-700' : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}">
                    <x-icon name="map-pin" class="h-4 w-4" />
                    <span x-show="! locating">{{ $centerLat ? 'Location set' : 'Use my location' }}</span>
                    <span x-show="locating" style="display: none;">Locating...</span>
                </button>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-2">
                <select wire:model.live="category" class="rounded-lg border-slate-300 py-1.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All categories</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>

                @if ($centerLat)
                    <div class="ml-auto flex items-center gap-2">
                        <select wire:model.live="radiusKm" class="rounded-lg border-slate-300 py-1.5 text-xs shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Any distance</option>
                            <option value="5">Within 5 km</option>
                            <option value="10">Within 10 km</option>
                            <option value="25">Within 25 km</option>
                            <option value="50">Within 50 km</option>
                        </select>
                        <button type="button" wire:click="$set('centerLat', null); $set('centerLng', null)" class="text-xs font-medium text-slate-500 hover:text-slate-700">
                            Clear location
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <p class="mb-3 text-sm font-semibold text-slate-700">
            {{ $centerLat ? 'Nearest available' : 'Available listings' }}
            <span class="font-normal text-slate-400">({{ count($markers) }})</span>
        </p>

        <div
            wire:ignore
            x-data="listingsMap(@js($markers), @js($centerLat), @js($centerLng))"
            @locate-user.window="useMyLocation()"
        >
            <div x-ref="map" class="h-[calc(100vh-16rem)] min-h-[32rem] w-full rounded-xl border border-slate-200 shadow-sm"></div>
        </div>

        @if (empty($markers))
            <div class="mt-6">
                <x-empty-state title="No pinned listings match your search" message="Try a different keyword, a wider category, or clear the distance filter." />
            </div>
        @endif
    </div>
</div>
