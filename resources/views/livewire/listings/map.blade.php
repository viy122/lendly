<div>
    <x-page-header
        eyebrow="Lendly marketplace"
        title="Find items near you"
        subtitle="Search for an item and we'll show the nearest available listings."
    />

    <div class="w-full px-4 py-6 sm:px-6 lg:px-8">
        <div class="mb-4 flex justify-end">
            <a href="{{ route('listings.index') }}" wire:navigate class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                <x-icon name="list" class="h-4 w-4" />
                Back to list view
            </a>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap xl:flex-nowrap" x-data="{ locating: false }">
                <div class="relative min-w-0 flex-1 sm:min-w-[16rem]">
                    <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
                    <input type="text" wire:model.live.debounce.400ms="keyword" placeholder="Search for tools, cameras, tents..."
                           class="h-12 w-full rounded-xl border-slate-300 py-3 pl-11 pr-4 text-sm shadow-sm transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                </div>

                <select wire:model.live="category" class="h-12 w-full rounded-xl border-slate-300 py-3 pl-4 pr-9 text-sm shadow-sm transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100 sm:w-auto sm:min-w-[11rem]">
                    <option value="">All categories</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>

                <button type="button"
                        @click="locating = true; $dispatch('locate-user')"
                        @location-found.window="locating = false"
                        class="flex h-12 shrink-0 items-center justify-center gap-2 rounded-xl border px-4 py-3 text-sm font-semibold shadow-sm transition hover:-translate-y-0.5 {{ $centerLat ? 'border-blue-300 bg-blue-50 text-blue-700 hover:bg-blue-100' : 'border-slate-300 bg-white text-slate-700 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700' }}">
                    <x-icon name="map-pin" class="h-4 w-4" />
                    <span x-show="! locating">{{ $centerLat ? 'Location set' : 'Use my location' }}</span>
                    <span x-show="locating" style="display: none;">Locating...</span>
                </button>

                @if ($centerLat)
                    <select wire:model.live="radiusKm" aria-label="Distance from your location" class="h-12 w-full rounded-xl border-slate-300 py-3 pl-4 pr-9 text-sm shadow-sm transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100 sm:w-auto sm:min-w-[10rem]">
                        <option value="">Any distance</option>
                        <option value="5">Within 5 km</option>
                        <option value="10">Within 10 km</option>
                        <option value="25">Within 25 km</option>
                        <option value="50">Within 50 km</option>
                    </select>

                    <button
                        type="button"
                        wire:click="$set('centerLat', null); $set('centerLng', null)"
                        class="grid h-12 w-12 shrink-0 place-items-center rounded-xl border border-rose-200 bg-rose-50 text-rose-600 shadow-sm transition hover:-translate-y-0.5 hover:border-rose-300 hover:bg-rose-100 hover:text-rose-700 focus:outline-none focus:ring-4 focus:ring-rose-100"
                        title="Clear location"
                        aria-label="Clear location">
                        <x-icon name="x-mark" class="h-5 w-5" />
                    </button>
                @endif
            </div>
        </div>

        <div class="mt-6">
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
</div>
