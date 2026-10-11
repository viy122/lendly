<div wire:poll.5s.keep-alive>
    <x-page-header eyebrow="Renting" title="My rentals" subtitle="View current rentals and your complete booking and transaction history." />

    <div class="w-full px-4 py-8 sm:px-6 lg:px-8">
        @if ($rentals->isEmpty())
            <x-empty-state title="No rentals yet" message="Once both parties accept an approved rental agreement, the booking will appear here for payment." />
        @else
            <div class="space-y-4">
                @foreach ($rentals as $rental)
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm" wire:key="rental-{{ $rental->id }}">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div class="flex items-start gap-4">
                                @if ($rental->listing->images->first())
                                    <img src="{{ $rental->listing->images->first()->url() }}" class="h-16 w-16 rounded-lg object-cover">
                                @else
                                    <div class="h-16 w-16 rounded-lg bg-slate-100"></div>
                                @endif
                                <div>
                                    <div class="flex items-center gap-2">
                                        <p class="font-semibold text-slate-800">{{ $rental->listing->name }}</p>
                                        <x-badge :color="$rental->displayStatusColor()">{{ $rental->displayStatusLabel() }}</x-badge>
                                    </div>
                                    @if ($rental->listing->trashed())
                                        <p class="mt-1 text-xs text-slate-500">Listing removed</p>
                                    @endif
                                    <p class="mt-1 text-sm text-slate-500">
                                        {{ $rental->start_date->format('M d, Y') }} &ndash; {{ $rental->end_date->format('M d, Y') }}
                                    </p>
                                    <p class="mt-1 text-xs text-slate-400">
                                        Owner: {{ $rental->owner->name }} &middot; Total ₱{{ number_format($rental->total_amount, 2) }}
                                    </p>
                                </div>
                            </div>

                            <a href="{{ route('renter.rentals.show', $rental) }}" wire:navigate class="group inline-flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-xl bg-[#075cf5] px-5 py-2.5 text-sm font-bold text-white shadow-[0_8px_20px_rgba(7,92,245,0.22)] transition duration-200 hover:-translate-y-0.5 hover:bg-blue-700 hover:shadow-[0_12px_26px_rgba(7,92,245,0.30)] focus:outline-none focus:ring-4 focus:ring-blue-100">
                                <span>
                                    @if ($rental->isPaymentPending())
                                        Pay now
                                    @elseif ($rental->awaitingPickupConfirmation())
                                        Confirm pickup
                                    @elseif ($rental->awaitingReturnConfirmation())
                                        Confirm return
                                    @else
                                        View details
                                    @endif
                                </span>
                                <span class="transition-transform duration-200 group-hover:translate-x-0.5" aria-hidden="true">→</span>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $rentals->links() }}
            </div>
        @endif
    </div>
</div>
