<div>
    <x-page-header eyebrow="Renting" title="My rental requests" subtitle="Track the status of items you've requested to rent." />

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
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
                                        <a href="{{ route('listings.show', $request->listing) }}" wire:navigate class="font-semibold text-slate-800 hover:text-blue-700">{{ $request->listing->name }}</a>
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
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <a href="{{ route('rental-requests.chat', $request) }}" wire:navigate class="text-xs font-medium text-blue-600 hover:text-blue-800">
                                    Message owner
                                </a>
                                @if ($request->isCancellableByRenter())
                                    <button type="button" wire:click="cancel({{ $request->id }})" wire:confirm="Cancel this rental request?" class="text-xs font-medium text-rose-600 hover:text-rose-800">
                                        Cancel
                                    </button>
                                @endif
                            </div>
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
