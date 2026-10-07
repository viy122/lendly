<div class="mx-auto max-w-3xl px-4 py-6 sm:px-6 {{ $modal ? '' : 'lg:px-8 lg:py-8' }}">
    @unless ($modal)
        <a href="{{ route('listings.show', $listing) }}" wire:navigate class="flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-700">
            <x-icon name="chevron-down" class="h-3.5 w-3.5 rotate-90" /> Back to listing
        </a>
    @endunless

    <div class="flex items-start justify-between gap-4 {{ $modal ? '' : 'mt-3' }}">
        <div>
            <h1 id="rental-request-title" class="text-xl font-semibold text-slate-900">Request to rent</h1>
            <p id="rental-request-description" class="mt-1 text-sm text-slate-500">{{ $listing->name }} &middot; ₱{{ number_format($listing->price_per_day, 2) }}/day</p>
        </div>
        @if ($modal)
            <button type="button" x-on:click="$dispatch('close-rental-request')" aria-label="Close rental request" class="grid h-10 w-10 shrink-0 place-items-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-4 focus:ring-blue-100">
                <x-icon name="x-mark" class="h-5 w-5" aria-hidden="true" />
            </button>
        @endif
    </div>

    <form wire:submit="submit" class="mt-6 space-y-6">
        <x-input-error :messages="$errors->get('listing')" role="alert" />

        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-700">Rental period</h2>
            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="start_date" value="Start date" />
                    <x-text-input wire:model.live="start_date" id="start_date" type="date" min="{{ max(now()->addDay()->toDateString(), $listing->available_from?->toDateString() ?? '') }}" max="{{ $listing->available_until?->toDateString() }}" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="end_date" value="End date" />
                    <x-text-input wire:model.live="end_date" id="end_date" type="date" min="{{ max($start_date, now()->addDay()->toDateString(), $listing->available_from?->toDateString() ?? '') }}" max="{{ $listing->available_until?->toDateString() }}" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
                </div>
            </div>
            <p class="mt-2 text-xs text-slate-400">Maximum rental duration: {{ $listing->max_rental_duration_days }} days.</p>
            @if ($listing->available_from && $listing->available_until)
                <p class="mt-2 text-xs text-slate-500">Available {{ $listing->available_from->format('M d, Y') }} – {{ $listing->available_until->format('M d, Y') }}.</p>
            @endif

            @if ($listing->pickup_available && $listing->delivery_available)
                <div class="mt-4">
                    <x-input-label value="Fulfillment" />
                    <div class="mt-2 flex gap-4">
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input type="radio" wire:model.live="fulfillment_method" value="pickup" class="text-blue-600 focus:ring-blue-500">
                            Pickup
                        </label>
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input type="radio" wire:model.live="fulfillment_method" value="delivery" class="text-blue-600 focus:ring-blue-500">
                            Delivery
                        </label>
                    </div>
                </div>
            @else
                <p class="mt-4 text-sm text-slate-600">
                    Fulfillment: <span class="font-medium">{{ $listing->pickup_available ? 'Pickup' : 'Delivery' }}</span> (only option offered by the owner)
                </p>
            @endif
            <x-input-error :messages="$errors->get('fulfillment_method')" class="mt-2" />
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-700">Cost breakdown</h2>
            <dl class="mt-4 space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-slate-500">Rental fee ({{ $this->rentalDays() }} day{{ $this->rentalDays() === 1 ? '' : 's' }} &times; ₱{{ number_format($listing->price_per_day, 2) }})</dt>
                    <dd class="font-medium text-slate-800">₱{{ number_format($this->rentalFee(), 2) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Platform fee ({{ rtrim(rtrim(number_format($this->commissionRate(), 2), '0'), '.') }}%)</dt>
                    <dd class="font-medium text-slate-800">₱{{ number_format($this->commissionAmount(), 2) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Security deposit (refundable)</dt>
                    <dd class="font-medium text-slate-800">₱{{ number_format($listing->security_deposit, 2) }}</dd>
                </div>
                <div class="flex justify-between border-t border-slate-100 pt-2 text-base">
                    <dt class="font-semibold text-slate-900">Total due</dt>
                    <dd class="font-semibold text-slate-900">₱{{ number_format($this->totalAmount(), 2) }}</dd>
                </div>
            </dl>
        </section>

        <p class="text-sm text-slate-600">If the owner approves, both of you will review and accept the rental agreement before the booking is finalized.</p>

        <div class="flex flex-wrap justify-end gap-3">
            @if ($modal)
                <x-secondary-button x-on:click="$dispatch('close-rental-request')">Cancel</x-secondary-button>
            @endif
            <x-primary-button wire:loading.attr="disabled" wire:target="submit" class="disabled:cursor-wait disabled:opacity-60">Send rental request</x-primary-button>
        </div>
    </form>
</div>
