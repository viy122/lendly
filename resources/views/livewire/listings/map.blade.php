<div
    class="grid bg-slate-100 lg:grid-cols-[minmax(0,1fr)_22rem]"
    x-data="listingsMap(@js($markers), @js($centerLat), @js($centerLng))"
    @locate-user.window="useMyLocation()"
    @location-found.window="locating = false"
>
    <div class="relative h-[65vh] min-h-[38rem] overflow-hidden lg:h-screen">
    <div wire:ignore class="absolute inset-0">
        <div x-ref="map" class="h-full w-full"></div>
    </div>

    <div class="pointer-events-none absolute inset-x-0 top-0 z-[500] flex items-start justify-between gap-4 p-3 sm:p-4">
        <div class="pointer-events-auto flex w-full max-w-2xl flex-col gap-2 sm:flex-row">
            <div class="flex h-11 min-w-0 flex-1 overflow-hidden rounded-md border border-slate-300 bg-white shadow-lg shadow-slate-900/10">
                <input
                    type="text"
                    wire:model.live.debounce.400ms="keyword"
                    placeholder="Search"
                    class="h-full min-w-0 flex-1 border-0 px-3 text-sm text-slate-800 shadow-none focus:ring-0"
                >
                <button
                    type="button"
                    class="grid h-11 w-11 shrink-0 place-items-center bg-indigo-500 text-white transition hover:bg-indigo-600"
                    title="Search listings"
                    aria-label="Search listings"
                >
                    <x-icon name="search" class="h-5 w-5" />
                </button>
            </div>

            <select wire:model.live="category" aria-label="Category" class="h-11 rounded-md border-slate-300 bg-white px-3 text-sm shadow-lg shadow-slate-900/10 focus:border-blue-500 focus:ring-blue-100 sm:w-44">
                <option value="">All categories</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>

        <a href="{{ route('listings.index') }}" wire:navigate class="pointer-events-auto hidden h-11 items-center gap-2 rounded-md border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-700 shadow-lg shadow-slate-900/10 transition hover:bg-slate-50 md:flex">
            <x-icon name="list" class="h-4 w-4" />
            List
        </a>
    </div>

    <div
        class="pointer-events-none absolute top-32 z-[530] flex flex-col gap-2 transition-all md:top-20"
        :class="layersOpen ? 'right-[21rem] sm:right-[25rem]' : 'right-4'"
    >
        <div class="pointer-events-auto overflow-hidden rounded bg-slate-700 text-white shadow-lg">
            <button type="button" @click="map?.zoomIn()" class="grid h-10 w-10 place-items-center text-2xl leading-none transition hover:bg-slate-800" title="Zoom in" aria-label="Zoom in">+</button>
            <button type="button" @click="map?.zoomOut()" class="grid h-10 w-10 place-items-center border-t border-white/15 text-2xl leading-none transition hover:bg-slate-800" title="Zoom out" aria-label="Zoom out">-</button>
        </div>
        <button type="button" @click="useMyLocation()" :disabled="locating" class="pointer-events-auto grid h-10 w-10 place-items-center rounded bg-slate-700 text-white shadow-lg transition hover:bg-slate-800 disabled:opacity-60" title="Use my location" aria-label="Use my location">
            <x-icon name="map-pin" class="h-5 w-5" />
        </button>
        <button type="button" @click="layersOpen = ! layersOpen" class="pointer-events-auto grid h-10 w-10 place-items-center rounded bg-emerald-500 text-white shadow-lg transition hover:bg-emerald-600" title="Map layers" aria-label="Map layers">
            <span class="flex flex-col gap-0.5" aria-hidden="true">
                <span class="block h-1 w-5 rounded-sm bg-white"></span>
                <span class="block h-1 w-5 rounded-sm bg-white"></span>
                <span class="block h-1 w-5 rounded-sm bg-white"></span>
            </span>
        </button>
    </div>

    <aside
        x-show="layersOpen"
        x-transition
        class="absolute inset-y-0 right-0 z-[520] w-full max-w-xs overflow-y-auto border-l border-slate-200 bg-white shadow-2xl sm:max-w-sm"
    >
        <div class="flex items-center justify-between px-4 py-4">
            <h2 class="text-2xl font-bold text-slate-900">Map Layers</h2>
            <button type="button" @click="layersOpen = false" class="grid h-9 w-9 place-items-center rounded-md text-slate-500 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close map layers">
                <x-icon name="x-mark" class="h-5 w-5" />
            </button>
        </div>

        <div class="space-y-3 px-4 pb-4">
            <template x-for="layer in layers" :key="layer.id">
                <button
                    type="button"
                    @click="setBaseLayer(layer.id)"
                    :aria-pressed="activeLayer === layer.id"
                    class="group relative flex h-14 w-full items-start overflow-hidden rounded-md border bg-slate-100 text-left shadow-sm transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2"
                    :class="activeLayer === layer.id ? 'border-indigo-500 ring-2 ring-indigo-400' : 'border-slate-200 hover:border-slate-400'"
                >
                    <img :src="layer.preview" alt="" aria-hidden="true" decoding="async" class="absolute inset-0 h-full w-full object-cover object-center">
                    <span class="relative m-1 rounded-sm bg-white/85 px-2 py-0.5 text-sm font-bold text-slate-900" x-text="layer.name"></span>
                </button>
            </template>
        </div>

        <div class="border-t border-slate-200 px-4 py-4">
            <p class="text-xs leading-5 text-slate-500">Enable overlays for troubleshooting the map.</p>
            <label class="mt-3 flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" x-model="showListingPins" @change="toggleListingPins()" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                Listing pins
            </label>
            <label class="mt-2 flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" x-model="showMyLocation" @change="toggleMyLocation()" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                My location
            </label>
        </div>
    </aside>

    <div class="absolute bottom-4 left-4 z-[500] rounded bg-white/90 px-3 py-2 text-xs font-semibold text-slate-700 shadow-lg">
        {{ $this->hasLocation() ? 'Nearest available' : 'Available listings' }}
        <span class="font-normal text-slate-500">({{ count($markers) }})</span>
        <p class="mt-1 font-normal">Hover or focus a pin for a preview. Select a pin to open item details.</p>
    </div>

    @if ($this->hasLocation())
        <div class="absolute bottom-4 right-4 z-[500] flex gap-2">
            <select wire:model.live="radiusKm" aria-label="Distance from your location" class="h-10 rounded-md border-slate-300 bg-white text-sm shadow-lg focus:border-blue-500 focus:ring-blue-100">
                <option value="">Any distance</option>
                <option value="5">Within 5 km</option>
                <option value="10">Within 10 km</option>
                <option value="25">Within 25 km</option>
                <option value="50">Within 50 km</option>
            </select>

            <button type="button" wire:click="clearLocation" class="grid h-10 w-10 place-items-center rounded-md bg-white text-rose-600 shadow-lg transition hover:bg-rose-50" title="Clear location" aria-label="Clear location">
                <x-icon name="x-mark" class="h-5 w-5" />
            </button>
        </div>
    @endif
    </div>

    <section class="min-w-0 border-t border-slate-200 bg-white lg:flex lg:h-screen lg:flex-col lg:border-l lg:border-t-0" aria-labelledby="map-results-title">
        <div class="border-b border-slate-100 p-4">
            <h1 id="map-results-title" class="text-lg font-bold text-slate-900">{{ $this->hasLocation() ? 'Nearest listings' : 'Listing results' }}</h1>
            <p x-show="locationError" x-text="locationError" x-cloak role="alert" class="mt-2 rounded-lg bg-amber-50 p-3 text-sm text-amber-900"></p>
            <p class="mt-1 text-sm text-slate-500" role="status" aria-live="polite">
                {{ count($markers) }} {{ count($markers) === 1 ? 'listing' : 'listings' }}{{ $this->hasLocation() ? ', sorted nearest to farthest.' : '.' }}
            </p>
            @unless ($this->hasLocation())
                <p class="mt-2 text-xs leading-5 text-slate-500">Set your location to see distances and sort by proximity.</p>
                <button type="button" @click="useMyLocation()" :disabled="locating" class="mt-3 inline-flex min-h-11 items-center gap-2 rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-100 disabled:opacity-60">
                    <x-icon name="map-pin" class="h-4 w-4" aria-hidden="true" />
                    <span x-text="locating ? 'Finding location…' : 'Use my location'">Use my location</span>
                </button>
            @endunless
        </div>
        @if (count($markers))
            <ol class="divide-y divide-slate-100 lg:min-h-0 lg:flex-1 lg:overflow-y-auto" aria-label="{{ $this->hasLocation() ? 'Listings sorted nearest to farthest' : 'Listing results' }}">
                @foreach ($markers as $marker)
                    <li wire:key="map-result-{{ $marker['id'] }}">
                        <a href="{{ $marker['url'] }}" wire:navigate class="flex min-h-24 gap-3 p-4 transition hover:bg-blue-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-blue-500">
                            @if ($marker['image'])
                                <img src="{{ $marker['image'] }}" alt="" loading="lazy" class="h-16 w-16 shrink-0 rounded-lg object-cover">
                            @else
                                <span class="grid h-16 w-16 shrink-0 place-items-center rounded-lg bg-slate-100 text-slate-400"><x-icon name="archive" class="h-6 w-6" aria-hidden="true" /></span>
                            @endif
                            <span class="min-w-0">
                                <span class="block break-words text-sm font-semibold text-slate-900">{{ $marker['name'] }}</span>
                                <span class="mt-1 block text-sm text-slate-600">₱{{ $marker['price'] }}/day</span>
                                @if ($marker['distance'] !== null)
                                    <span class="mt-1 block text-xs font-semibold text-blue-700">{{ $marker['distance'] }}</span>
                                @endif
                            </span>
                        </a>
                    </li>
                @endforeach
            </ol>
        @else
            <div class="p-5 text-sm text-slate-500">
                <p class="font-semibold text-slate-800">No matching listings</p>
                <p class="mt-2">{{ $this->hasLocation() && $radiusKm !== null ? 'Try a larger radius or adjust your search filters.' : 'Try another search or category.' }}</p>
            </div>
        @endif
    </section>
</div>
