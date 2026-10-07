<div>
    <x-page-header eyebrow="Renting" title="My rental requests" subtitle="View current and past rental requests." />

    <div class="w-full px-4 py-8 sm:px-6 lg:px-8">
        @if (session('status'))
            <div class="mb-6 rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-700">
                {{ session('status') }}
            </div>
        @endif

        @if ($rentalRequests->isEmpty())
            <x-empty-state title="No rental requests yet" message="Browse listings and request to rent an item to see it here." />
        @else
            <div class="space-y-4">
                @foreach ($rentalRequests as $request)
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm" wire:key="request-{{ $request->id }}">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div class="flex items-start gap-4">
                                @if ($request->listing->images->first())
                                    <img src="{{ $request->listing->images->first()->url() }}" class="h-16 w-16 rounded-lg object-cover">
                                @else
                                    <div class="h-16 w-16 rounded-lg bg-slate-100"></div>
                                @endif
                                <div>
                                    <div class="flex items-center gap-2">
                                        @if ($request->listing->trashed())
                                            <span class="font-semibold text-slate-800">{{ $request->listing->name }} (listing removed)</span>
                                        @else
                                            <a href="{{ route('listings.show', $request->listing) }}" wire:navigate class="font-semibold text-slate-800 hover:text-blue-700">{{ $request->listing->name }}</a>
                                        @endif
                                        <x-badge :color="$request->status->badgeColor()">{{ $request->status->label() }}</x-badge>
                                    </div>
                                    <p class="mt-1 text-sm text-slate-500">
                                        {{ $request->start_date->format('M d, Y') }} &ndash; {{ $request->end_date->format('M d, Y') }}
                                        ({{ $request->rental_days }} day{{ $request->rental_days === 1 ? '' : 's' }})
                                    </p>
                                    <p class="mt-1 text-xs text-slate-400">
                                        Owner: {{ $request->listing->owner->name }} &middot; {{ ucfirst($request->fulfillment_method->value) }} &middot; Total ₱{{ number_format($request->total_amount, 2) }}
                                    </p>
                                    @if ($request->status->value === 'rejected' && $request->rejection_reason)
                                        <p class="mt-1 text-xs text-rose-600">Reason: {{ $request->rejection_reason }}</p>
                                    @endif
                                    @if ($request->status->value === 'cancelled')
                                        <x-cancellation-details :record="$request" />
                                    @endif
                                </div>
                            </div>
                        </div>
                        @if ($cancelling === $request->id && ! $request->rental && $request->isCancellableByRenter())
                            <form wire:submit="cancel({{ $request->id }})" class="mt-4 rounded-lg border border-rose-200 bg-rose-50 p-4">
                                <p class="mb-3 text-sm text-rose-700">Cancelling a request before the booking is finalized is free.</p>
                                <x-input-label :for="'cancellation-reason-'.$request->id" value="Reason for cancellation (optional)" />
                                <textarea wire:model="cancellation_reason" id="cancellation-reason-{{ $request->id }}" rows="2" maxlength="500" class="mt-1 block w-full rounded-lg border-rose-300 text-sm shadow-sm focus:border-rose-500 focus:ring-rose-500"></textarea>
                                <x-input-error :messages="$errors->get('cancellation_reason')" class="mt-2" />
                                <div class="mt-3 flex justify-end gap-3">
                                    <button type="button" wire:click="$set('cancelling', null)" class="min-h-11 px-3 text-sm font-medium text-slate-600">Keep request</button>
                                    <button type="submit" wire:loading.attr="disabled" wire:target="cancel" wire:confirm="Cancel this rental request?" class="min-h-11 rounded-lg bg-rose-600 px-4 text-sm font-semibold text-white disabled:opacity-60">Confirm cancellation</button>
                                </div>
                            </form>
                        @endif
                        <div class="-mx-5 -mb-5 mt-5 grid grid-cols-1 gap-2.5 rounded-b-xl border-t border-slate-100 bg-slate-50/60 px-5 py-4 sm:flex sm:flex-wrap sm:items-center sm:justify-end">
                            <a href="{{ route('rental-requests.chat', $request) }}" wire:navigate class="inline-flex min-h-11 items-center justify-center gap-2 whitespace-nowrap rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition-colors hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 sm:mr-auto">
                                <x-icon name="chat" class="h-4 w-4 shrink-0" aria-hidden="true" />
                                <span>Message owner</span>
                            </a>
                            @if ($request->rental)
                                <a href="{{ route('renter.rentals.show', $request->rental) }}" wire:navigate class="inline-flex min-h-11 items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-blue-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2">View booking</a>
                            @elseif ($request->isApproved())
                                <a href="{{ route('renter.rental-requests.agreement', $request) }}" wire:navigate class="inline-flex min-h-11 items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-blue-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2">Review rental agreement</a>
                            @endif
                            @if (! $request->rental && $request->isCancellableByRenter())
                                <button type="button" wire:click="startCancelling({{ $request->id }})" class="inline-flex min-h-11 items-center justify-center gap-2 whitespace-nowrap rounded-xl border border-rose-200 bg-white px-4 py-2.5 text-sm font-semibold text-rose-600 shadow-sm transition-colors hover:border-rose-300 hover:bg-rose-50 hover:text-rose-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-500 focus-visible:ring-offset-2">
                                    <x-icon name="x-mark" class="h-4 w-4 shrink-0" aria-hidden="true" />
                                    <span>Cancel</span>
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $rentalRequests->links() }}
            </div>
        @endif
    </div>
</div>
