<div wire:poll.15s.visible>
    <x-page-header eyebrow="Owning" title="My listings" subtitle="Manage the items you've listed for rent." />

    <div class="w-full px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-4 flex justify-end">
            <a href="{{ route('owner.listings.create') }}" wire:navigate class="flex items-center gap-2 rounded-lg bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:from-blue-700 hover:to-indigo-700">
                <x-icon name="tag" class="h-4 w-4" />
                New listing
            </a>
        </div>

        @if (session('status'))
            <div class="mb-6 rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-700">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div role="alert" class="mb-6 rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">
                <p class="font-semibold">Edit your listing to complete the required details before resuming.</p>
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        @if ($listings->isEmpty())
            <x-empty-state title="No listings yet" message="Create your first listing to start earning from items you rarely use." />
        @else
            <div class="overflow-hidden overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="border-b-2 border-indigo-100 bg-indigo-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-indigo-700">Item</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-indigo-700">Category</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-indigo-700">Price/day</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-indigo-700">Status</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-indigo-700">Actions</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-indigo-700">Availability</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($listings as $listing)
                            <tr wire:key="listing-{{ $listing->id }}" class="hover:bg-slate-50/75">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        @if ($listing->images->first())
                                            <img src="{{ $listing->images->first()->url() }}" class="h-10 w-10 rounded-lg object-cover">
                                        @else
                                            <div class="h-10 w-10 rounded-lg bg-slate-100"></div>
                                        @endif
                                        <span class="font-medium text-slate-800">{{ $listing->name }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3"><x-category-badge :category="$listing->category" /></td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-500">₱{{ number_format($listing->price_per_day, 2) }}</td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <x-badge :color="$listing->status->badgeColor()">{{ $listing->status->label() }}</x-badge>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('owner.listings.edit', $listing) }}" wire:navigate aria-label="Edit {{ $listing->name }}" title="Edit listing" class="grid h-10 w-10 shrink-0 place-items-center rounded-lg border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-600 focus:outline-none focus:ring-4 focus:ring-blue-100">
                                            <x-icon name="pencil" class="h-4 w-4" aria-hidden="true" />
                                        </a>
                                        @if ($listing->isPublished())
                                            <button type="button" wire:click="deactivate({{ $listing->id }})" wire:loading.attr="disabled" aria-label="Pause {{ $listing->name }}" title="Pause listing" class="min-h-10 rounded-lg border border-slate-200 px-3 text-xs font-semibold text-slate-600 hover:bg-slate-50">Pause</button>
                                        @elseif ($listing->status === \App\Enums\ListingStatus::Inactive)
                                            <button type="button" wire:click="reactivate({{ $listing->id }})" wire:loading.attr="disabled" aria-label="Resume {{ $listing->name }}" title="Resume listing" class="min-h-10 rounded-lg border border-blue-200 px-3 text-xs font-semibold text-blue-600 hover:bg-blue-50">Resume</button>
                                        @endif
                                        <button type="button" wire:click="delete({{ $listing->id }})" wire:confirm="Remove this listing from the marketplace? Existing bookings and transaction history will be retained." wire:loading.attr="disabled" aria-label="Remove {{ $listing->name }}" title="Remove listing" class="grid h-10 w-10 shrink-0 place-items-center rounded-lg border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-600 focus:outline-none focus:ring-4 focus:ring-rose-100 disabled:cursor-wait disabled:opacity-60">
                                            <x-icon name="trash" class="h-4 w-4" aria-hidden="true" />
                                        </button>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    @php $availability = $listing->availabilityStatus(); @endphp
                                    <div class="mb-1"><x-badge :color="$availability->badgeColor()">{{ $availability->label() }}</x-badge></div>
                                    @if ($listing->available_from && $listing->available_until)
                                        <p class="mb-1 whitespace-nowrap text-xs text-slate-500">Available {{ $listing->available_from->format('M d, Y') }} – {{ $listing->available_until->format('M d, Y') }}</p>
                                    @endif
                                    @foreach ($listing->paidReservations as $reservation)
                                        <p class="mb-1 whitespace-nowrap text-xs text-slate-500">Reserved {{ $reservation->start_date->format('M d, Y') }} – {{ $reservation->end_date->format('M d, Y') }}</p>
                                    @endforeach
                                    <button
                                        type="button"
                                        role="switch"
                                        aria-checked="{{ $listing->is_available ? 'true' : 'false' }}"
                                        aria-label="{{ $listing->name }} availability"
                                        wire:click="setAvailability({{ $listing->id }}, {{ $listing->is_available ? 'false' : 'true' }})"
                                        wire:loading.attr="disabled"
                                        wire:target="setAvailability"
                                        class="inline-flex min-h-10 items-center gap-2.5 whitespace-nowrap rounded-lg px-1 text-xs font-medium text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-blue-100 disabled:cursor-wait disabled:opacity-60">
                                        <span aria-hidden="true" @class(['relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors', 'bg-blue-600' => $listing->is_available, 'bg-slate-300' => ! $listing->is_available])>
                                            <span @class(['h-5 w-5 rounded-full bg-white shadow-sm transition-transform', 'translate-x-5' => $listing->is_available, 'translate-x-0.5' => ! $listing->is_available])></span>
                                        </span>
                                        <span>{{ $listing->is_available ? 'Accepting requests' : 'Requests paused' }}</span>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $listings->links() }}
            </div>
        @endif
    </div>
</div>
