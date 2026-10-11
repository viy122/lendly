<div class="listing-detail-page min-h-screen bg-slate-50" wire:poll.15s.visible x-data="{ previousBodyOverflow: '' }">
    <x-page-header eyebrow="Lendly marketplace" :title="$listing->name" :subtitle="$listing->location" />

    <div class="listing-detail-content w-full px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
        @if (session('status'))
            <div class="mb-6 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-700" role="status">{{ session('status') }}</div>
        @endif

        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('listings.index') }}" wire:navigate class="inline-flex min-h-10 items-center gap-2 rounded-lg text-sm font-semibold text-slate-600 transition hover:text-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-100">
                <x-icon name="chevron-down" class="h-4 w-4 rotate-90" aria-hidden="true" /> Back to browse
            </a>
            <div class="flex flex-wrap items-center gap-2">
                <x-category-badge :category="$listing->category" />
                @if ($listing->subcategory)
                    <x-badge color="slate">{{ $listing->subcategory->name }}</x-badge>
                @endif
                <x-badge color="slate">{{ $listing->condition->label() }} condition</x-badge>
                @if ($averageRating !== null)
                    <a href="#item-reviews" class="inline-flex min-h-10 items-center gap-1.5 rounded-lg px-2 text-sm text-slate-600 hover:text-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-100">
                        <x-icon name="star" class="h-4 w-4 fill-amber-400 text-amber-400" aria-hidden="true" />
                        <span class="font-semibold text-slate-800">{{ number_format($averageRating, 1) }}</span>
                        <span>· {{ $reviews->count() }} review{{ $reviews->count() === 1 ? '' : 's' }}</span>
                    </a>
                @endif
            </div>
        </div>

        <div class="listing-detail-grid">
            <div class="listing-detail-primary">
                @php $photoUrls = $listing->images->map(fn ($image) => $image->url())->values(); @endphp
                <section class="listing-detail-card listing-detail-gallery overflow-hidden rounded-2xl border border-slate-200 bg-white" x-data="{ photos: @js($photoUrls), selectedPhoto: 0 }" aria-labelledby="item-photos-title">
                    <div class="flex items-center justify-between gap-3 p-5 sm:p-6">
                        <div class="flex items-center gap-3">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-blue-50 text-blue-600"><x-icon name="camera" class="h-5 w-5" aria-hidden="true" /></span>
                            <h2 id="item-photos-title" class="text-base font-bold text-slate-900">Item photos</h2>
                        </div>
                        @if ($photoUrls->isNotEmpty())
                            <span class="shrink-0 rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-500" aria-live="polite"><span x-text="selectedPhoto + 1">1</span> / {{ $photoUrls->count() }}</span>
                        @endif
                    </div>
                    <div class="px-5 pb-5 sm:px-6 sm:pb-6">
                        @if ($photoUrls->isNotEmpty())
                            <div class="listing-detail-photo aspect-[4/3] overflow-hidden rounded-xl bg-slate-100 p-4">
                                <img src="{{ $photoUrls->first() }}" :src="photos[selectedPhoto]" alt="{{ $listing->name }} — photo 1" :alt="@js($listing->name) + ' — photo ' + (selectedPhoto + 1)" class="h-full w-full object-contain" />
                            </div>
                            @if ($photoUrls->count() > 1)
                                <div class="mt-4 flex flex-wrap gap-2" aria-label="Choose an item photo">
                                    @foreach ($photoUrls as $photoUrl)
                                        <button type="button" @click="selectedPhoto = {{ $loop->index }}" :aria-pressed="selectedPhoto === {{ $loop->index }}" :class="selectedPhoto === {{ $loop->index }} ? 'border-blue-500 ring-2 ring-blue-100' : 'border-slate-200'" aria-label="View item photo {{ $loop->iteration }}" class="h-16 w-16 shrink-0 overflow-hidden rounded-xl border-2 bg-slate-50 p-1 transition hover:border-blue-300 focus:outline-none focus:ring-4 focus:ring-blue-100">
                                            <img src="{{ $photoUrl }}" alt="{{ $listing->name }} thumbnail {{ $loop->iteration }}" class="h-full w-full object-contain" loading="lazy" />
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        @else
                            <div class="flex h-64 flex-col items-center justify-center gap-3 rounded-xl bg-slate-100 px-6 text-center sm:h-72">
                                <span class="grid h-16 w-16 place-items-center rounded-2xl bg-white text-slate-400"><x-icon name="camera" class="h-8 w-8" aria-hidden="true" /></span>
                                <p class="text-sm font-semibold text-slate-600">No photos added yet</p>
                                <p class="max-w-xs text-xs leading-5 text-slate-500">Check the item details below for more information.</p>
                            </div>
                        @endif
                    </div>
                </section>

                <x-listing-detail-panel title="About this item" icon="archive" class="listing-detail-about">
                    <p class="mt-5 whitespace-pre-line text-sm leading-7 text-slate-600">{{ $listing->description }}</p>
                    <dl class="mt-6 grid gap-5 border-t border-slate-100 pt-5 text-sm sm:grid-cols-2">
                        <div><dt class="flex items-center gap-2 text-xs font-medium text-slate-500"><x-icon name="map-pin" class="h-4 w-4" aria-hidden="true" /> Location</dt><dd class="mt-2 font-semibold text-slate-800">{{ $listing->location }}</dd></div>
                        <div><dt class="flex items-center gap-2 text-xs font-medium text-slate-500"><x-icon name="clipboard-list" class="h-4 w-4" aria-hidden="true" /> Maximum rental duration</dt><dd class="mt-2 font-semibold text-slate-800">{{ $listing->max_rental_duration_days }} day{{ (int) $listing->max_rental_duration_days === 1 ? '' : 's' }}</dd></div>
                        <div><dt class="flex items-center gap-2 text-xs font-medium text-slate-500"><x-icon name="archive" class="h-4 w-4" aria-hidden="true" /> Pickup & delivery</dt><dd class="mt-2 font-semibold text-slate-800">{{ collect([$listing->pickup_available ? 'Pickup' : null, $listing->delivery_available ? 'Delivery' : null])->filter()->join(' & ') ?: 'Confirm with the owner' }}</dd></div>
                        <div><dt class="flex items-center gap-2 text-xs font-medium text-slate-500"><x-icon name="tag" class="h-4 w-4" aria-hidden="true" /> Condition</dt><dd class="mt-2 font-semibold text-slate-800">{{ $listing->condition->label() }}</dd></div>
                        @if ($listing->brand)
                            <div><dt class="text-xs font-medium text-slate-500">Brand</dt><dd class="mt-2 font-semibold text-slate-800">{{ $listing->brand }}</dd></div>
                        @endif
                        @if ($listing->model)
                            <div><dt class="text-xs font-medium text-slate-500">Model</dt><dd class="mt-2 font-semibold text-slate-800">{{ $listing->model }}</dd></div>
                        @endif
                    </dl>
                    @if ($listing->rental_rules)
                        <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4 sm:p-5">
                            <h3 class="flex items-center gap-2 text-sm font-semibold text-amber-900"><x-icon name="exclamation-triangle" class="h-4 w-4 shrink-0" aria-hidden="true" /> Rental rules</h3>
                            <p class="mt-2 whitespace-pre-line text-sm leading-6 text-amber-900/80">{{ $listing->rental_rules }}</p>
                        </div>
                    @endif
                </x-listing-detail-panel>

                <x-listing-detail-panel title="Renter reviews" icon="star" class="listing-detail-reviews" id="item-reviews">
                    @if ($reviews->isNotEmpty())
                        <div class="mt-5 flex flex-wrap items-center gap-2 border-b border-slate-100 pb-5 text-sm">
                            <x-icon name="star" class="h-5 w-5 fill-amber-400 text-amber-400" aria-hidden="true" />
                            <span class="text-xl font-bold text-slate-900">{{ number_format($averageRating, 1) }}</span>
                            <span class="text-slate-500">from {{ $reviews->count() }} review{{ $reviews->count() === 1 ? '' : 's' }}</span>
                        </div>
                        <div class="divide-y divide-slate-100">
                            @foreach ($reviews as $review)
                                <article class="py-5 last:pb-0">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div><h3 class="text-sm font-semibold text-slate-800">{{ $review->rental->renter->name }}</h3><p class="mt-1 text-xs text-slate-500">{{ $review->created_at->format('M d, Y') }}</p></div>
                                        <span class="flex items-center gap-0.5" role="img" aria-label="{{ $review->rating }} out of 5 stars">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <x-icon name="star" class="h-4 w-4 {{ $i <= $review->rating ? 'fill-amber-400 text-amber-400' : 'text-slate-300' }}" aria-hidden="true" />
                                            @endfor
                                        </span>
                                    </div>
                                    @if ($review->comment)
                                        <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $review->comment }}</p>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    @else
                        <div class="mt-5 rounded-xl bg-slate-50 p-5 text-sm"><p class="font-medium text-slate-700">No reviews yet</p><p class="mt-1 leading-6 text-slate-500">Reviews from renters will appear here after their rentals.</p></div>
                    @endif
                </x-listing-detail-panel>
            </div>

            <aside class="listing-detail-sidebar" aria-label="Rental rates, availability, and owner">
                <section class="listing-detail-card listing-detail-booking overflow-hidden rounded-2xl border border-blue-200 bg-white" aria-labelledby="rental-rates-title">
                    <div class="border-b border-blue-100 bg-blue-50/70 p-5 sm:p-6">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h2 id="rental-rates-title" class="text-base font-bold text-slate-900">Rental rates</h2>
                            <x-badge :color="$availability->badgeColor()">{{ $availability->label() }}</x-badge>
                        </div>
                        <p class="mt-4 flex flex-wrap items-baseline gap-x-2 text-3xl font-extrabold tracking-tight text-slate-900">₱{{ number_format($listing->price_per_day, 2) }} <span class="text-sm font-medium tracking-normal text-slate-500">/ day</span></p>
                    </div>
                    <div class="p-5 sm:p-6">
                        <dl class="space-y-4 text-sm">
                            @if ($listing->price_per_hour)
                                <div class="flex flex-wrap justify-between gap-x-4 gap-y-1"><dt class="text-slate-500">Per hour</dt><dd class="font-semibold text-slate-800">₱{{ number_format($listing->price_per_hour, 2) }}</dd></div>
                            @endif
                            @if ($listing->price_per_week)
                                <div class="flex flex-wrap justify-between gap-x-4 gap-y-1"><dt class="text-slate-500">Per week</dt><dd class="font-semibold text-slate-800">₱{{ number_format($listing->price_per_week, 2) }}</dd></div>
                            @endif
                            <div class="flex flex-wrap justify-between gap-x-4 gap-y-1 border-t border-slate-100 pt-4"><dt class="text-slate-500">Security deposit</dt><dd class="font-semibold text-slate-800">₱{{ number_format($listing->security_deposit, 2) }}</dd></div>
                        </dl>
                        @if (! $listing->is_available || ! $listing->hasRequestableDates() || ! $listing->isPublished())
                            <button type="button" disabled class="mt-6 min-h-12 w-full cursor-not-allowed rounded-xl bg-slate-100 px-4 py-3 text-sm font-semibold text-slate-500">Currently unavailable</button>
                            <p class="mt-3 text-center text-xs leading-5 text-slate-500">This item is paused or has no upcoming available dates.</p>
                        @elseif (! auth()->check())
                            <div class="mt-6 flex items-stretch gap-2">
                                <a href="{{ route('login') }}" wire:navigate class="flex min-h-12 min-w-0 flex-1 items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-100"><x-icon name="clipboard-list" class="h-4 w-4" aria-hidden="true" /> Log in to request</a>
                                <a href="{{ route('listings.message', $listing) }}" wire:navigate aria-label="Message owner" title="Message owner" class="grid min-h-12 w-12 shrink-0 place-items-center rounded-xl border border-blue-200 bg-blue-50 text-blue-600 transition hover:border-blue-300 hover:bg-blue-100 focus:outline-none focus:ring-4 focus:ring-blue-100"><x-icon name="chat" class="h-5 w-5" aria-hidden="true" /></a>
                            </div>
                            <p class="mt-3 text-center text-xs leading-5 text-slate-500">Log in to choose your rental dates and send a request.</p>
                        @elseif (auth()->id() === $listing->owner_id)
                            <button type="button" disabled class="mt-6 min-h-12 w-full cursor-not-allowed rounded-xl bg-slate-100 px-4 py-3 text-sm font-semibold text-slate-500">This is your listing</button>
                        @elseif ($listing->isPublished() && auth()->user()->isRenter() && auth()->user()->activeInterface() === 'renter')
                            <div class="mt-6 flex items-stretch gap-2">
                                <button type="button" x-on:click="previousBodyOverflow = document.body.style.overflow; $refs.rentalRequestModal.showModal(); document.body.style.overflow = 'hidden'" aria-haspopup="dialog" aria-controls="rental-request-modal" class="flex min-h-12 min-w-0 flex-1 items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-100"><x-icon name="clipboard-list" class="h-4 w-4" aria-hidden="true" /> Request to rent</button>
                                @can('message', $listing)
                                    <a href="{{ route('listings.message', $listing) }}" wire:navigate aria-label="Message owner" title="Message owner" class="grid min-h-12 w-12 shrink-0 place-items-center rounded-xl border border-blue-200 bg-blue-50 text-blue-600 transition hover:border-blue-300 hover:bg-blue-100 focus:outline-none focus:ring-4 focus:ring-blue-100"><x-icon name="chat" class="h-5 w-5" aria-hidden="true" /></a>
                                @endcan
                            </div>
                            <p class="mt-3 text-center text-xs leading-5 text-slate-500">Choose your dates next. Your request needs the owner’s approval.</p>
                        @else
                            <button type="button" disabled class="mt-6 min-h-12 w-full cursor-not-allowed rounded-xl bg-slate-100 px-4 py-3 text-sm font-semibold text-slate-500">Only renters can request items</button>
                            <p class="mt-3 text-center text-xs leading-5 text-slate-500">Use the renter interface to send a rental request.</p>
                        @endif
                    </div>
                </section>

                <x-listing-detail-panel title="Availability" icon="clipboard-list" subtitle="Check the calendar before requesting dates." class="listing-detail-availability">
                    @if ($listing->available_from && $listing->available_until)
                        <p class="mt-4 text-sm font-medium text-slate-700">Available {{ $listing->available_from->format('M d, Y') }} – {{ $listing->available_until->format('M d, Y') }}</p>
                    @endif
                    <div class="mt-5" wire:key="availability-calendar-{{ $listing->id }}-{{ md5($bookedRanges->toJson().$reservedRanges->toJson().$listing->available_from?->toDateString().$listing->available_until?->toDateString()) }}" x-data="availabilityCalendar(@js($bookedRanges), @js($reservedRanges), @js($listing->available_from?->toDateString()), @js($listing->available_until?->toDateString()))">
                        <div class="flex items-center justify-between gap-2">
                            <button type="button" @click="prevMonth" aria-label="Previous month" class="grid h-10 w-10 shrink-0 place-items-center rounded-xl border border-slate-200 text-slate-500 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-100"><x-icon name="chevron-down" class="h-4 w-4 rotate-90" aria-hidden="true" /></button>
                            <p class="text-center text-sm font-semibold text-slate-800" x-text="monthLabel" aria-live="polite"></p>
                            <button type="button" @click="nextMonth" aria-label="Next month" class="grid h-10 w-10 shrink-0 place-items-center rounded-xl border border-slate-200 text-slate-500 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-100"><x-icon name="chevron-down" class="h-4 w-4 -rotate-90" aria-hidden="true" /></button>
                        </div>
                        <div class="mb-2 mt-5 grid grid-cols-7 gap-1 text-center text-xs font-medium text-slate-500"><span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span></div>
                        <div class="space-y-1">
                            <template x-for="(week, wi) in weeks" :key="wi">
                                <div class="grid grid-cols-7 gap-1">
                                    <template x-for="(day, di) in week" :key="di">
                                        <div class="flex h-10 items-center justify-center rounded-lg text-xs"
                                            :class="{
                                                'bg-amber-100 font-semibold text-amber-800': day && isReserved(day),
                                                'bg-rose-100 font-semibold text-rose-700': day && isBooked(day) && !isReserved(day),
                                                'text-slate-400': day && isPast(day) && !isBooked(day),
                                                'bg-blue-50 font-medium text-blue-700': day && !isPast(day) && !isBooked(day) && !isOutsideWindow(day) && @js((bool) $listing->is_available && $listing->isPublished()),
                                                'bg-slate-100 text-slate-500': day && !isPast(day) && !isBooked(day) && (isOutsideWindow(day) || !@js((bool) $listing->is_available && $listing->isPublished())),
                                            }"
                                            :aria-label="day ? day.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) + ': ' + (isReserved(day) ? 'Reserved' : isBooked(day) ? 'On hold' : isPast(day) ? 'Past date' : !isOutsideWindow(day) && @js((bool) $listing->is_available && $listing->isPublished()) ? 'Available' : 'Unavailable') : null"
                                            x-text="day ? day.getDate() : ''"></div>
                                    </template>
                                </div>
                            </template>
                        </div>
                        <div class="mt-5 flex flex-wrap items-center gap-x-4 gap-y-2 border-t border-slate-100 pt-4 text-xs text-slate-500">
                            <span class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-amber-200" aria-hidden="true"></span> Reserved</span>
                            <span class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-rose-200" aria-hidden="true"></span> On hold</span>
                            @if ($listing->is_available)
                                <span class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-blue-100" aria-hidden="true"></span> Available</span>
                            @else
                                <span class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-slate-200" aria-hidden="true"></span> Unavailable</span>
                            @endif
                            <span class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-slate-200" aria-hidden="true"></span> Outside available dates</span>
                        </div>
                        <p class="mt-3 text-xs text-slate-500">Approved requests hold dates. A recorded payment reserves the item for the agreed rental dates.</p>
                        @if ($paidReservationRanges->isNotEmpty())
                            <p class="mt-3 text-xs font-semibold text-amber-700">Reserved dates</p>
                            <ul class="mt-3 space-y-1 text-xs font-medium text-amber-700" aria-label="Paid reservation dates">
                                @foreach ($paidReservationRanges as $reservation)
                                    <li>Reserved {{ $reservation->start_date->format('M d, Y') }} – {{ $reservation->end_date->format('M d, Y') }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </x-listing-detail-panel>

                <x-listing-detail-panel title="Listed by" icon="user-circle" class="listing-detail-owner">
                    <div class="mt-5 flex items-center gap-3">
                        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-blue-100 text-base font-bold text-blue-700" aria-hidden="true">{{ Str::of($listing->owner->name)->substr(0, 1)->upper() }}</span>
                        <div class="min-w-0"><p class="text-sm font-semibold text-slate-800">{{ $listing->owner->name }}</p>
                            @if (! $listing->owner->trashed() && ! $listing->owner->isAdmin() && ! $listing->owner->isSuspended() && $listing->owner->hasVerifiedEmail())
                                <a href="{{ route('users.show', $listing->owner) }}" wire:navigate class="text-xs font-semibold text-blue-700 hover:underline">View public profile</a>
                            @endif
                            @if ($ownerAverageRating !== null)
                                <p class="mt-1 flex flex-wrap items-center gap-1.5 text-xs text-slate-500"><x-icon name="star" class="h-4 w-4 fill-amber-400 text-amber-400" aria-hidden="true" /><span class="font-semibold text-slate-700">{{ number_format($ownerAverageRating, 1) }}</span> owner rating</p>
                            @else
                                <p class="mt-1 text-xs text-slate-500">No owner ratings yet</p>
                            @endif
                        </div>
                    </div>
                </x-listing-detail-panel>
            </aside>
        </div>
    </div>

    @if ($listing->isPublished() && $listing->is_available && $listing->hasRequestableDates() && auth()->check() && auth()->id() !== $listing->owner_id && auth()->user()->isRenter() && auth()->user()->activeInterface() === 'renter')
        <dialog
            id="rental-request-modal"
            x-ref="rentalRequestModal"
            aria-labelledby="rental-request-title"
            aria-describedby="rental-request-description"
            x-on:close-rental-request.stop="$el.close()"
            x-on:close="document.body.style.overflow = previousBodyOverflow"
            x-on:livewire:navigating.window="if ($el.open) { $el.close(); document.body.style.overflow = previousBodyOverflow; }"
            x-on:click="if ($event.target === $el) { const bounds = $el.getBoundingClientRect(); if ($event.clientX < bounds.left || $event.clientX > bounds.right || $event.clientY < bounds.top || $event.clientY > bounds.bottom) $el.close(); }"
            class="m-auto max-h-[calc(100dvh-2rem)] w-[calc(100%-2rem)] max-w-2xl overflow-y-auto rounded-2xl border-0 bg-slate-50 p-0 text-slate-900 shadow-xl backdrop:bg-slate-900/50"
        >
            <livewire:rental-requests.create :listing="$listing" :modal="true" :key="'rental-request-'.$listing->id" />
        </dialog>
    @endif
</div>
