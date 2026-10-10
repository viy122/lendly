<div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8" x-data>
    @if (session('status'))
        <div class="mb-6 rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-700">
            {{ session('status') }}
        </div>
    @endif

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                @if ($listing->images->isNotEmpty())
                    <div class="aspect-[4/3] w-full overflow-hidden bg-slate-100">
                        <img src="{{ $listing->images->first()->url() }}" class="h-full w-full object-cover">
                    </div>
                    @if ($listing->images->count() > 1)
                        <div class="grid grid-cols-4 gap-2 p-3">
                            @foreach ($listing->images->skip(1) as $image)
                                <img src="{{ $image->url() }}" class="aspect-square w-full rounded-lg object-cover">
                            @endforeach
                        </div>
                    @endif
                @else
                    <div class="flex aspect-[4/3] w-full items-center justify-center bg-slate-100 text-slate-300">No photos</div>
                @endif
            </div>

            <div class="mt-6">
                <div class="flex flex-wrap items-center gap-2">
                    <x-category-badge :category="$listing->category" />
                    @if ($listing->subcategory)
                        <x-badge color="slate">{{ $listing->subcategory->name }}</x-badge>
                    @endif
                    <x-badge color="slate">{{ $listing->condition->label() }}</x-badge>
                    @if (! $listing->is_available)
                        <x-badge color="red">Currently unavailable</x-badge>
                    @endif
                </div>
                <h1 class="mt-3 text-2xl font-bold tracking-tight text-slate-900">{{ $listing->name }}</h1>
                @if ($listing->brand || $listing->model)
                    <p class="mt-1 text-sm text-slate-500">{{ trim(($listing->brand ?? '').' '.($listing->model ?? '')) }}</p>
                @endif

                @if ($averageRating !== null)
                    <p class="mt-2 flex items-center gap-1.5 text-sm text-slate-600">
                        <x-icon name="star" class="h-4 w-4 fill-amber-400 text-amber-400" />
                        <span class="font-semibold text-slate-800">{{ number_format($averageRating, 1) }}</span>
                        <span class="text-slate-400">({{ $reviews->count() }} review{{ $reviews->count() === 1 ? '' : 's' }})</span>
                    </p>
                @endif

                <p class="mt-4 whitespace-pre-line text-sm text-slate-700">{{ $listing->description }}</p>

                @if ($listing->rental_rules)
                    <div class="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-4">
                        <p class="flex items-center gap-1.5 text-sm font-semibold text-amber-800">
                            <x-icon name="exclamation-triangle" class="h-4 w-4" />
                            Rental rules
                        </p>
                        <p class="mt-1 whitespace-pre-line text-sm text-amber-900/80">{{ $listing->rental_rules }}</p>
                    </div>
                @endif

                <dl class="mt-6 grid grid-cols-2 gap-3 text-sm sm:grid-cols-3">
                    <div class="rounded-lg border border-slate-200 bg-white p-3">
                        <dt class="flex items-center gap-1.5 text-xs text-slate-400"><x-icon name="map-pin" class="h-3.5 w-3.5" /> Location</dt>
                        <dd class="mt-1 font-medium text-slate-700">{{ $listing->location }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-3">
                        <dt class="flex items-center gap-1.5 text-xs text-slate-400"><x-icon name="clipboard-list" class="h-3.5 w-3.5" /> Max duration</dt>
                        <dd class="mt-1 font-medium text-slate-700">{{ $listing->max_rental_duration_days }} days</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-3">
                        <dt class="flex items-center gap-1.5 text-xs text-slate-400"><x-icon name="archive" class="h-3.5 w-3.5" /> Fulfillment</dt>
                        <dd class="mt-1 font-medium text-slate-700">
                            {{ collect([$listing->pickup_available ? 'Pickup' : null, $listing->delivery_available ? 'Delivery' : null])->filter()->join(' & ') }}
                        </dd>
                    </div>
                </dl>

                <div class="mt-8" x-data="availabilityCalendar(@js($bookedRanges))">
                    <h2 class="flex items-center gap-1.5 text-sm font-semibold text-slate-700">
                        <x-icon name="clipboard-list" class="h-4 w-4 text-blue-500" />
                        Availability
                    </h2>
                    <div class="mt-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="flex items-center justify-between">
                            <button type="button" @click="prevMonth" class="rounded-md p-1 text-slate-400 hover:bg-blue-50 hover:text-blue-600">
                                <x-icon name="chevron-down" class="h-4 w-4 rotate-90" />
                            </button>
                            <p class="text-sm font-medium text-slate-800" x-text="monthLabel"></p>
                            <button type="button" @click="nextMonth" class="rounded-md p-1 text-slate-400 hover:bg-blue-50 hover:text-blue-600">
                                <x-icon name="chevron-down" class="h-4 w-4 -rotate-90" />
                            </button>
                        </div>

                        <div class="mt-3 grid grid-cols-7 gap-1 text-center text-xs font-medium text-slate-400">
                            <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
                        </div>

                        <template x-for="(week, wi) in weeks" :key="wi">
                            <div class="grid grid-cols-7 gap-1">
                                <template x-for="(day, di) in week" :key="di">
                                    <div
                                        class="flex h-8 items-center justify-center rounded-md text-xs"
                                        :class="{
                                            'text-slate-200': !day,
                                            'bg-rose-100 text-rose-600 font-medium': day && isBooked(day),
                                            'text-slate-300': day && isPast(day) && !isBooked(day),
                                            'bg-blue-50 text-slate-700 hover:bg-blue-100': day && !isPast(day) && !isBooked(day),
                                        }"
                                        x-text="day ? day.getDate() : ''"
                                    ></div>
                                </template>
                            </div>
                        </template>

                        <div class="mt-3 flex items-center gap-4 text-xs text-slate-500">
                            <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-rose-200"></span> Booked</span>
                            <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-blue-100"></span> Available</span>
                        </div>
                    </div>
                </div>

                @if ($reservedRentals->isNotEmpty())
                    <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                        <h3 class="font-semibold">Reserved dates</h3>
                        <ul class="mt-2 space-y-1">
                            @foreach ($reservedRentals as $reservation)
                                <li>{{ $reservation->start_date->format('M d, Y') }} &ndash; {{ $reservation->end_date->format('M d, Y') }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($reviews->isNotEmpty())
                    <div class="mt-8">
                        <h2 class="flex items-center gap-1.5 text-sm font-semibold text-slate-700">
                            <x-icon name="star" class="h-4 w-4 fill-amber-400 text-amber-400" />
                            Reviews
                        </h2>
                        <div class="mt-3 space-y-4">
                            @foreach ($reviews as $review)
                                <div class="rounded-lg border border-slate-200 p-4">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-medium text-slate-800">{{ $review->rental->renter->name }}</p>
                                        <span class="flex items-center gap-0.5">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <x-icon name="star" class="h-3.5 w-3.5 {{ $i <= $review->rating ? 'fill-amber-400 text-amber-400' : 'fill-slate-200 text-slate-200' }}" />
                                            @endfor
                                        </span>
                                    </div>
                                    @if ($review->comment)
                                        <p class="mt-1 text-sm text-slate-600">{{ $review->comment }}</p>
                                    @endif
                                    <p class="mt-1 text-xs text-slate-400">{{ $review->created_at->format('M d, Y') }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div>
            <div class="sticky top-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="p-6">
                    <p class="text-2xl font-bold text-slate-900">
                        ₱{{ number_format($listing->price_per_day, 2) }}
                        <span class="text-sm font-normal text-slate-400">/day</span>
                    </p>

                    <dl class="mt-3 space-y-1 text-sm text-slate-500">
                        <div class="flex justify-between"><dt>Status today</dt><dd><x-badge :color="$listing->availabilityColor()">{{ $listing->availabilityLabel() }}</x-badge></dd></div>
                        @if ($listing->price_per_hour)
                            <div class="flex justify-between"><dt>Per hour</dt><dd>₱{{ number_format($listing->price_per_hour, 2) }}</dd></div>
                        @endif
                        @if ($listing->price_per_week)
                            <div class="flex justify-between"><dt>Per week</dt><dd>₱{{ number_format($listing->price_per_week, 2) }}</dd></div>
                        @endif
                        <div class="flex justify-between"><dt>Security deposit</dt><dd>₱{{ number_format($listing->security_deposit, 2) }}</dd></div>
                    </dl>

                    @if (! $listing->is_available)
                        <button type="button" disabled class="mt-5 w-full cursor-not-allowed rounded-lg bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-500">
                            Currently unavailable
                        </button>
                    @elseif (! auth()->check())
                        <a href="{{ route('login') }}" wire:navigate class="mt-5 block w-full rounded-lg bg-blue-600 px-4 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                            Log in to request
                        </a>
                    @elseif (auth()->user()->isRenter())
                        <a href="{{ route('renter.rental-requests.create', $listing) }}" wire:navigate class="mt-5 block w-full rounded-lg bg-blue-600 px-4 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                            Request to rent
                        </a>
                    @else
                        <button type="button" disabled class="mt-5 w-full cursor-not-allowed rounded-lg bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-500">
                            Only renters can request items
                        </button>
                    @endif

                    <div class="mt-6 flex items-center gap-3 border-t border-slate-100 pt-4">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-700">
                            {{ Str::of($listing->owner->name)->substr(0, 1)->upper() }}
                        </span>
                        <div>
                            <p class="text-xs font-medium text-slate-400">Listed by</p>
                            <a href="{{ route('users.show', $listing->owner) }}" wire:navigate class="font-medium text-slate-800 hover:text-blue-600">{{ $listing->owner->name }}</a>
                            @if ($ownerAverageRating !== null)
                                <p class="flex items-center gap-1 text-sm text-slate-500">
                                    <x-icon name="star" class="h-3.5 w-3.5 fill-amber-400 text-amber-400" />
                                    {{ number_format($ownerAverageRating, 1) }}
                                </p>
                            @endif
                        </div>
                    </div>
                    @auth
                        @if (auth()->user()->isRenter() && auth()->id() !== $listing->owner_id)
                            <a href="{{ route('listings.contact', $listing) }}" wire:navigate class="mt-4 block text-center text-sm font-medium text-blue-600">Message owner</a>
                        @endif
                    @endauth
                </div>
            </div>
        </div>
    </div>

</div>
