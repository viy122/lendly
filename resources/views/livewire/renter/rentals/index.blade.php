<div>
    <x-page-header eyebrow="Renting" title="My rentals" subtitle="Approved rentals waiting for payment, and rentals you've paid for." />

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @if ($rentals->isEmpty())
            <x-empty-state title="No rentals yet" message="Once an owner approves your rental request, it will appear here for payment." />
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
                                    <p class="mt-1 text-sm text-slate-500">
                                        {{ $rental->start_date->format('M d, Y') }} &ndash; {{ $rental->end_date->format('M d, Y') }}
                                    </p>
                                    <p class="mt-1 text-xs text-slate-400">
                                        Owner: {{ $rental->listing->owner->name }} &middot; Total ₱{{ number_format($rental->total_amount, 2) }}
                                    </p>
                                </div>
                            </div>

                            <a href="{{ route('renter.rentals.show', $rental) }}" wire:navigate class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-blue-700">
                                @if ($rental->isPaymentPending())
                                    Pay now
                                @elseif ($rental->awaitingPickupConfirmation())
                                    Confirm pickup
                                @elseif ($rental->awaitingReturnConfirmation())
                                    Confirm return
                                @else
                                    View details
                                @endif
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
