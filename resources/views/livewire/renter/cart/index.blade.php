<div class="min-h-screen bg-slate-50">
    <x-page-header eyebrow="Renting" title="My rental cart" subtitle="Choose your items, rental dates and handover, then send requests to their owners." />
    <x-identity-review-notice />
    <x-input-error :messages="$errors->get('identity')" class="mx-4 mt-4" role="alert" />

    <div class="w-full px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
        @if (session('status'))
            <p role="status" class="mb-5 rounded-xl bg-blue-50 p-4 text-sm text-blue-800">{{ session('status') }}</p>
        @endif
        <x-input-error :messages="$errors->get('cart')" class="mb-4" role="alert" />
        <x-input-error :messages="$errors->get('selected')" class="mb-4" role="alert" />
        <x-input-error :messages="$errors->get('listing')" class="mb-4" role="alert" />
        <x-input-error :messages="$errors->get('waitlist')" class="mb-4" role="alert" />

        @if ($waitlistEntries->isNotEmpty())
            <section class="mb-6 rounded-2xl border border-blue-100 bg-white p-5" aria-labelledby="waitlist-title">
                <h2 id="waitlist-title" class="text-lg font-semibold text-slate-900">My availability waitlist</h2>
                <p class="mt-2 text-sm text-slate-600">Alerts are sent in joining order when your exact dates are available. An alert does not reserve the item. Review the current price and send a request for owner approval.</p>
                <ul class="mt-4 divide-y divide-slate-100">
                    @foreach ($waitlistEntries as $entry)
                        <li wire:key="waitlist-{{ $entry->id }}" class="flex flex-wrap items-center justify-between gap-3 py-4">
                            <div><p class="font-semibold text-slate-800">{{ $entry->listing?->name ?? 'Removed item' }}</p><p class="mt-1 text-sm text-slate-500">{{ $entry->details['start_date'] }} {{ $entry->details['start_time'] ?? '' }} – {{ $entry->details['end_date'] }} {{ $entry->details['end_time'] ?? '' }} · {{ $entry->notified_at ? 'Availability alert sent — check dates again' : 'Waiting for these dates' }}</p></div>
                            <div class="flex items-center gap-4">
                                @if ($entry->listing && ! $entry->listing->trashed())<a href="{{ route('renter.rental-requests.create', ['listing' => $entry->listing_id, 'waitlist' => $entry->id]) }}" wire:navigate class="text-sm font-semibold text-blue-700">Review dates</a>@endif
                                <button type="button" wire:click="leaveWaitlist({{ $entry->id }})" wire:loading.attr="disabled" class="min-h-10 rounded px-2 text-sm font-semibold text-rose-700 focus:outline-none focus:ring-4 focus:ring-rose-100">Leave waitlist</button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($items->isEmpty())
            <section class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
                <span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-blue-50 text-blue-600"><x-icon name="inbox" class="h-7 w-7" aria-hidden="true" /></span>
                <h2 class="mt-5 text-lg font-semibold text-slate-900">Your rental cart is empty</h2>
                <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">Find an item, choose your dates and add it here. You can review several rentals before sending any requests.</p>
                <a href="{{ route('listings.index') }}" wire:navigate class="mt-6 inline-flex min-h-11 items-center justify-center rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-100">Browse listings</a>
            </section>
        @else
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-5 py-3">
                <label class="flex min-h-10 items-center gap-3 text-sm font-semibold text-slate-700">
                    <input type="checkbox" wire:click="selectAll" @checked(count($selected) === $items->count()) class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                    Select all ({{ $items->count() }})
                </label>
                <a href="{{ route('listings.index') }}" wire:navigate class="inline-flex min-h-10 items-center rounded-lg text-sm font-semibold text-blue-700 hover:underline focus:outline-none focus:ring-4 focus:ring-blue-100">Continue browsing</a>
            </div>

            <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
                <div class="min-w-0 space-y-5">
                    @foreach ($groups as $ownerId => $ownerItems)
                        @php
                            $ownerName = $ownerItems->first()->listing?->owner?->name ?? 'Unavailable owner';
                            $ownerSelected = $ownerItems->every(fn ($entry) => in_array($entry->id, $selected));
                        @endphp
                        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white" wire:key="cart-owner-{{ $ownerId }}" aria-labelledby="cart-owner-{{ $ownerId }}">
                            <header class="flex items-center gap-3 border-b border-slate-100 bg-slate-50/60 px-5 py-4">
                                <input id="select-owner-{{ $ownerId }}" type="checkbox" wire:click="selectOwner({{ (int) $ownerId }})" @checked($ownerSelected) aria-label="Select all items from {{ $ownerName }}" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                                <x-icon name="user-circle" class="h-5 w-5 shrink-0 text-slate-400" aria-hidden="true" />
                                <h2 id="cart-owner-{{ $ownerId }}" class="min-w-0 break-words text-sm font-semibold text-slate-800">{{ $ownerName }}</h2>
                                <span class="ml-auto shrink-0 text-xs text-slate-500">{{ $ownerItems->count() }} item{{ $ownerItems->count() === 1 ? '' : 's' }}</span>
                            </header>

                            <div class="divide-y divide-slate-100">
                                @foreach ($ownerItems as $item)
                                    @php
                                        $listing = $item->listing;
                                        $unavailable = ! $listing || $listing->trashed() || ! $listing->isPublished() || ! $listing->is_available || ! $listing->owner || $listing->owner->trashed() || $listing->owner->isSuspended();
                                        $photo = $listing?->images->first();
                                        $savedMethod = $edits[$item->id]['fulfillment_method'] ?? $item->fulfillment_method;
                                    @endphp
                                    <article class="p-5" wire:key="cart-item-{{ $item->id }}" aria-labelledby="cart-item-title-{{ $item->id }}">
                                        <div class="flex items-start gap-3 sm:gap-4">
                                            <input id="select-item-{{ $item->id }}" type="checkbox" wire:model.live="selected" value="{{ $item->id }}" aria-label="Select {{ $listing?->name ?? 'unavailable item' }}" class="mt-1 rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                                            <div class="grid h-20 w-20 shrink-0 place-items-center overflow-hidden rounded-xl border border-slate-100 bg-slate-50 p-1">
                                                @if ($photo)
                                                    <img src="{{ $photo->url() }}" alt="{{ $listing->name }}" class="h-full w-full object-contain" loading="lazy" />
                                                @else
                                                    <x-icon name="archive" class="h-7 w-7 text-slate-300" aria-hidden="true" />
                                                @endif
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <h3 id="cart-item-title-{{ $item->id }}" class="break-words text-sm font-semibold text-slate-900">
                                                    @if ($listing && ! $listing->trashed() && $listing->isPublished())
                                                        <a href="{{ route('listings.show', $listing) }}" wire:navigate class="rounded hover:text-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-100">{{ $listing->name }}</a>
                                                    @else
                                                        {{ $listing?->name ?? 'Unavailable item' }}
                                                    @endif
                                                </h3>
                                                <p class="mt-1 text-xs text-slate-500">₱{{ number_format($item->terms['price_per_day'], 2) }}/day · {{ $item->rentalDays() }} day{{ $item->rentalDays() === 1 ? '' : 's' }}</p>
                                                <p class="mt-2 text-base font-bold text-slate-900">₱{{ number_format($item->totalAmount(), 2) }} <span class="text-xs font-normal text-slate-500">estimated total</span></p>
                                            </div>
                                        </div>

                                        @if ($unavailable)
                                            <p class="mt-4 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800" role="status">Currently unavailable. This listing or owner is no longer accepting requests. You can remove it from your cart.</p>
                                        @endif

                                        <form wire:submit="updateItem({{ $item->id }})" class="mt-5 space-y-3">
                                            <div class="grid gap-3 sm:grid-cols-3">
                                                <div>
                                                    <x-input-label :for="'cart-start-'.$item->id" value="Start date" />
                                                    <x-text-input :id="'cart-start-'.$item->id" type="date" wire:model="edits.{{ $item->id }}.start_date" min="{{ max(now()->addDay()->toDateString(), $listing?->available_from?->toDateString() ?? '') }}" max="{{ $listing?->available_until?->toDateString() }}" :disabled="$unavailable" class="mt-1 w-full text-sm" />
                                                    <x-input-error :messages="$errors->get('items.'.$item->id.'.start_date')" class="mt-1" />
                                                    <x-input-error :messages="$errors->get('edits.'.$item->id.'.start_date')" class="mt-1" />
                                                </div>
                                                <div>
                                                    <x-input-label :for="'cart-end-'.$item->id" value="End date" />
                                                    <x-text-input :id="'cart-end-'.$item->id" type="date" wire:model="edits.{{ $item->id }}.end_date" min="{{ max($edits[$item->id]['start_date'] ?? '', now()->addDay()->toDateString(), $listing?->available_from?->toDateString() ?? '') }}" max="{{ $listing?->available_until?->toDateString() }}" :disabled="$unavailable" class="mt-1 w-full text-sm" />
                                                    <x-input-error :messages="$errors->get('items.'.$item->id.'.end_date')" class="mt-1" />
                                                    <x-input-error :messages="$errors->get('edits.'.$item->id.'.end_date')" class="mt-1" />
                                                </div>
                                                <div>
                                                    <x-input-label :for="'cart-handover-'.$item->id" value="Handover" />
                                                    <select id="cart-handover-{{ $item->id }}" wire:model="edits.{{ $item->id }}.fulfillment_method" @disabled($unavailable) class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 disabled:bg-slate-50 disabled:text-slate-400">
                                                        <option value="">Choose handover</option>
                                                        @if ($listing?->pickup_available)<option value="pickup">Pickup</option>@elseif ($savedMethod === 'pickup')<option value="pickup" disabled>Pickup (unavailable)</option>@endif
                                                        @if ($listing?->delivery_available)<option value="delivery">Delivery</option>@elseif ($savedMethod === 'delivery')<option value="delivery" disabled>Delivery (unavailable)</option>@endif
                                                    </select>
                                                    <x-input-error :messages="$errors->get('items.'.$item->id.'.fulfillment_method')" class="mt-1" />
                                                    <x-input-error :messages="$errors->get('edits.'.$item->id.'.fulfillment_method')" class="mt-1" />
                                                </div>
                                            </div>
                                            @if ($item->rental_unit === 'hour')
                                                <div class="grid gap-3 sm:grid-cols-2">
                                                    <div><x-input-label :for="'cart-start-time-'.$item->id" value="Start time (Philippine time)" /><x-text-input :id="'cart-start-time-'.$item->id" type="time" wire:model="edits.{{ $item->id }}.start_time" class="mt-1 w-full" /><x-input-error :messages="$errors->get('items.'.$item->id.'.start_time')" /></div>
                                                    <div><x-input-label :for="'cart-end-time-'.$item->id" value="Return time (Philippine time)" /><x-text-input :id="'cart-end-time-'.$item->id" type="time" wire:model="edits.{{ $item->id }}.end_time" class="mt-1 w-full" /><x-input-error :messages="$errors->get('items.'.$item->id.'.end_time')" /></div>
                                                </div>
                                            @endif
                                            <p class="text-xs leading-5 text-slate-500"><x-rental-period :booking="$item" /></p>
                                            <div class="flex flex-wrap items-center justify-between gap-3">
                                                <x-secondary-button type="submit" wire:loading.attr="disabled" wire:target="updateItem({{ $item->id }})" :disabled="$unavailable" class="min-h-10 text-xs">Update dates &amp; handover</x-secondary-button>
                                                @if (! $unavailable)<button type="button" wire:click="joinWaitlist({{ $item->id }})" wire:loading.attr="disabled" class="min-h-10 rounded px-2 text-xs font-semibold text-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-100">Join waitlist for saved dates</button>@endif
                                                <button type="button" wire:click="remove({{ $item->id }})" wire:loading.attr="disabled" wire:target="remove({{ $item->id }})" class="inline-flex min-h-10 items-center gap-1.5 rounded-lg px-2 text-xs font-semibold text-rose-600 hover:bg-rose-50 focus:outline-none focus:ring-4 focus:ring-rose-100 disabled:opacity-60"><x-icon name="trash" class="h-4 w-4" aria-hidden="true" /> Remove</button>
                                            </div>
                                        </form>
                                        <x-input-error :messages="$errors->get('items.'.$item->id)" class="mt-3" role="alert" />
                                        <x-input-error :messages="$errors->get('items.'.$item->id.'.dates')" class="mt-3" role="alert" />
                                        <x-input-error :messages="$errors->get('items.'.$item->id.'.listing')" class="mt-3" role="alert" />
                                        <x-input-error :messages="$errors->get('items.'.$item->id.'.terms')" class="mt-3" role="alert" />

                                        <details class="mt-4 rounded-xl border border-slate-100 bg-slate-50/60 px-4 py-3 text-sm">
                                            <summary class="cursor-pointer rounded font-medium text-slate-600 focus:outline-none focus:ring-4 focus:ring-blue-100">Charges and item rules</summary>
                                            <dl class="mt-3 space-y-2 text-xs">
                                                <div class="flex justify-between gap-3"><dt class="text-slate-500">Rental fee</dt><dd class="font-medium text-slate-800">₱{{ number_format($item->rentalFee(), 2) }}</dd></div>
                                                <div class="flex justify-between gap-3"><dt class="text-slate-500">Platform fee ({{ rtrim(rtrim(number_format($item->terms['commission_rate'], 2), '0'), '.') }}%)</dt><dd class="font-medium text-slate-800">₱{{ number_format($item->commissionAmount(), 2) }}</dd></div>
                                                <div class="flex justify-between gap-3"><dt class="text-slate-500">Refundable security deposit</dt><dd class="font-medium text-slate-800">₱{{ number_format($item->terms['security_deposit'], 2) }}</dd></div>
                                            </dl>
                                            <p class="mt-4 text-xs font-semibold text-slate-700">Item rules</p>
                                            <p class="mt-1 whitespace-pre-line break-words text-xs leading-5 text-slate-500">{{ $item->terms['rental_rules'] ?: 'No additional item-specific rules.' }}</p>
                                        </details>
                                    </article>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>

                <aside class="xl:sticky xl:top-6" aria-label="Selected rentals summary">
                    <section class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6">
                        <p class="text-xs font-semibold uppercase tracking-wider text-blue-600">Review your selection</p>
                        <h2 class="mt-2 text-lg font-bold text-slate-900">{{ count($selected) }} item{{ count($selected) === 1 ? '' : 's' }} selected</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-500">Your cart saves drafts. It does not reserve items or notify owners.</p>
                        <div class="mt-5 border-y border-slate-100 py-4" aria-live="polite">
                            <p class="text-sm text-slate-500">Selected estimate</p>
                            <p class="mt-1 text-2xl font-bold tracking-tight text-slate-900">₱{{ number_format($selectedTotal, 2) }}</p>
                            <p class="mt-2 text-xs leading-5 text-slate-500">Includes rental fees, platform fees and refundable deposits. Update changed dates and handover before sending. Availability and quotes are checked again when you send.</p>
                        </div>
                        <form wire:submit="checkout" class="mt-5 space-y-4">
                            <label for="cart-accept-terms" class="flex items-start gap-3 text-sm leading-6 text-slate-700">
                                <input id="cart-accept-terms" type="checkbox" wire:model="accept_terms" class="mt-1 rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                                <span>I have reviewed the selected items, saved dates, handover, rates, fees, deposits and item rules.</span>
                            </label>
                            <x-input-error :messages="$errors->get('accept_terms')" role="alert" />
                            <x-primary-button wire:loading.attr="disabled" wire:target="checkout" :disabled="count($selected) === 0" class="min-h-11 w-full justify-center rounded-xl disabled:cursor-not-allowed disabled:opacity-50">Send rental requests</x-primary-button>
                            <p class="text-center text-xs font-medium text-slate-500">No payment is taken now.</p>
                        </form>
                        <p class="mt-5 border-t border-slate-100 pt-4 text-xs leading-6 text-slate-500">Each item becomes a separate request for its owner to review. After approval, both parties review and accept the rental agreement. Each confirmed booking is paid separately.</p>
                    </section>
                </aside>
            </div>
        @endif
    </div>
</div>
