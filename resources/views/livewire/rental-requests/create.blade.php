<div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
    <a href="{{ route('listings.show', $listing) }}" wire:navigate class="flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-700">
        <x-icon name="chevron-down" class="h-3.5 w-3.5 rotate-90" /> Back to listing
    </a>

    <h1 class="mt-3 text-xl font-semibold text-slate-900">Request to rent</h1>
    <p class="mt-1 text-sm text-slate-500">{{ $listing->name }} &middot; ₱{{ number_format($listing->price_per_day, 2) }}/day</p>

    <form wire:submit="submit" class="mt-6 space-y-6">
        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-700">Rental period</h2>
            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="start_date" value="Start date" />
                    <x-text-input wire:model.live="start_date" id="start_date" type="date" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="end_date" value="End date" />
                    <x-text-input wire:model.live="end_date" id="end_date" type="date" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
                </div>
            </div>
            <p class="mt-2 text-xs text-slate-400">Maximum rental duration: {{ $listing->max_rental_duration_days }} days.</p>

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

        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm" x-data="{ open: false }">
            <button type="button" @click="open = ! open" class="flex w-full items-center justify-between text-left">
                <h2 class="text-sm font-semibold text-slate-700">Rental terms &amp; agreement</h2>
                <x-icon name="chevron-down" class="h-4 w-4 text-slate-400 transition" x-bind:class="{ 'rotate-180': open }" />
            </button>

            <div x-show="open" x-transition class="mt-3 space-y-3 text-sm text-slate-600" style="display: none;">
                @if ($listing->rental_rules)
                    <div>
                        <p class="font-medium text-slate-700">Item-specific rules (set by the owner)</p>
                        <p class="mt-1 whitespace-pre-line">{{ $listing->rental_rules }}</p>
                    </div>
                @endif
                <div>
                    <p class="font-medium text-slate-700">Cancellation policy</p>
                    <p class="mt-1">
                        You may cancel this request or booking any time before the item is handed over. Cancelling more
                        than 48 hours before the rental start date is free. Cancelling within 48 hours of the start
                        date incurs a cancellation fee of 20% of the rental fee.
                    </p>
                </div>
                <div>
                    <p class="font-medium text-slate-700">Damages &amp; security deposit</p>
                    <p class="mt-1">
                        The security deposit is held until the item is returned and inspected. If the owner reports
                        damage, an amount up to the deposit may be deducted; you may accept or dispute that claim.
                    </p>
                </div>
            </div>

            <label class="mt-4 flex items-start gap-2 text-sm text-slate-700">
                <input type="checkbox" wire:model="accept_terms" class="mt-0.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                <span>I have read and accept the rental terms and agreement above, including the cancellation policy.</span>
            </label>
            <x-input-error :messages="$errors->get('accept_terms')" class="mt-2" />
        </section>

        <div class="flex justify-end">
            <x-primary-button>Send rental request</x-primary-button>
        </div>
    </form>
</div>
