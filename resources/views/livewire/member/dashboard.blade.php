<div>
    <x-page-header eyebrow="Lendly member" title="Welcome back, {{ auth()->user()->name }}" subtitle="Everything you're renting and everything you're listing, in one place." />

    <div class="w-full space-y-10 px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-center justify-end gap-2">
            <a href="{{ route('listings.index') }}" wire:navigate class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                <x-icon name="search" class="h-4 w-4" />
                Browse listings
            </a>
            <a href="{{ route('owner.listings.create') }}" wire:navigate class="flex items-center gap-2 rounded-lg bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:from-blue-700 hover:to-indigo-700">
                <x-icon name="tag" class="h-4 w-4" />
                New listing
            </a>
        </div>
        @if ($rentalsAwaitingPayment->isNotEmpty())
            <div class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                    <x-icon name="exclamation-triangle" class="h-5 w-5" />
                </span>
                <div>
                    <p class="text-sm font-semibold text-amber-800">
                        You have {{ $rentalsAwaitingPayment->count() }} approved rental{{ $rentalsAwaitingPayment->count() === 1 ? '' : 's' }} waiting for payment
                    </p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($rentalsAwaitingPayment as $rental)
                            <a href="{{ route('renter.rentals.show', $rental) }}" wire:navigate class="rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-700">
                                Pay for {{ $rental->listing->name }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-stat-card label="Active listings" :value="$activeListingsCount" icon="tag" accent="blue" />
            <x-stat-card label="Pending requests (as renter)" :value="$pendingCount" icon="inbox" accent="amber" />
        </div>

        <section>
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-base font-semibold text-slate-900">As Owner</h2>
                <a href="{{ route('owner.listings.index') }}" wire:navigate class="text-xs font-medium text-blue-600 hover:text-blue-800">Manage listings</a>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-stat-card label="Total earnings" :value="'₱' . number_format($totalEarnings, 2)" icon="archive" accent="indigo" />
                <x-stat-card label="Pending rental requests" :value="$pendingRequestsCount" icon="inbox" accent="amber" />
                <x-stat-card label="Active rentals" :value="$activeRentalsCount" icon="tag" accent="emerald" />
                <x-stat-card label="Average rating" :value="$averageRating !== null ? number_format($averageRating, 1) . ' ★' : '—'" hint="{{ $totalListingViews }} total listing views" icon="star" accent="rose" />
            </div>

            <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-blue-600">Pipeline</p>
                    <h3 class="mt-0.5 text-base font-bold text-slate-900">Rentals by status</h3>
                    <p class="mt-1 text-xs text-slate-500">Where your rentals sit right now.</p>
                    <div class="mt-5">
                        <x-pie-chart :data="$rentalsByStatus" />
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-blue-600">Breakdown</p>
                    <h3 class="mt-0.5 text-base font-bold text-slate-900">Listings by category</h3>
                    <p class="mt-1 text-xs text-slate-500">What you have listed, across all statuses.</p>
                    <div class="mt-5">
                        <x-stacked-bar-chart :data="$listingsByCategory" />
                    </div>
                </div>
            </div>

            <div class="mt-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-600">Trend</p>
                <h3 class="mt-0.5 text-base font-bold text-slate-900">Earnings, last 6 months</h3>
                <div class="mt-5">
                    <x-line-chart :data="$monthlyEarnings" color="#2563eb" />
                </div>
            </div>

            @if ($mostRentedListing || $mostProfitableListing)
                <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @if ($mostRentedListing)
                        <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                                <x-icon name="archive" class="h-5 w-5" />
                            </span>
                            <div>
                                <p class="text-xs font-medium text-slate-400">Most rented item</p>
                                <p class="font-semibold text-slate-800">{{ $mostRentedListing->name }}</p>
                                <p class="text-sm text-slate-500">{{ $mostRentedListing->rentals_count }} rental{{ $mostRentedListing->rentals_count === 1 ? '' : 's' }}</p>
                            </div>
                        </div>
                    @endif
                    @if ($mostProfitableListing)
                        <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-indigo-600">
                                <x-icon name="tag" class="h-5 w-5" />
                            </span>
                            <div>
                                <p class="text-xs font-medium text-slate-400">Most profitable item</p>
                                <p class="font-semibold text-slate-800">{{ $mostProfitableListing->name }}</p>
                                <p class="text-sm text-slate-500">₱{{ number_format($mostProfitableListing->revenue, 2) }} earned</p>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <div class="mt-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-700">Your listings</h3>
                    <a href="{{ route('owner.listings.index') }}" wire:navigate class="text-xs font-medium text-blue-600 hover:text-blue-800">View all</a>
                </div>
                <div class="mt-3">
                    @if ($recentListings->isEmpty())
                        <x-empty-state title="No listings yet" message="Create your first listing to start earning from items you rarely use." />
                    @else
                        <div class="overflow-hidden overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($recentListings as $listing)
                                        <tr wire:key="recent-{{ $listing->id }}">
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
                                            <td class="px-4 py-3">
                                                <x-category-badge :category="$listing->category" />
                                            </td>
                                            <td class="px-4 py-3">
                                                <x-badge :color="$listing->status->badgeColor()">{{ $listing->status->label() }}</x-badge>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="mt-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-700">Pending rental requests</h3>
                    <a href="{{ route('owner.rental-requests.index') }}" wire:navigate class="text-xs font-medium text-blue-600 hover:text-blue-800">View all</a>
                </div>
                <div class="mt-3">
                    @if ($pendingRequests->isEmpty())
                        <x-empty-state title="No pending requests" message="Requests from renters will appear here for you to approve or reject." />
                    @else
                        <div class="overflow-hidden overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($pendingRequests as $request)
                                        <tr wire:key="pending-request-{{ $request->id }}">
                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-3">
                                                    @if ($request->listing->images->first())
                                                        <img src="{{ $request->listing->images->first()->url() }}" class="h-10 w-10 rounded-lg object-cover">
                                                    @else
                                                        <div class="h-10 w-10 rounded-lg bg-slate-100"></div>
                                                    @endif
                                                    <span class="font-medium text-slate-800">{{ $request->listing->name }}</span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-slate-500">{{ $request->renter->name }}</td>
                                            <td class="px-4 py-3 text-slate-500">{{ $request->start_date->format('M d') }} &ndash; {{ $request->end_date->format('M d, Y') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </section>

        <section>
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-base font-semibold text-slate-900">As Renter</h2>
                <a href="{{ route('renter.rental-requests.index') }}" wire:navigate class="text-xs font-medium text-blue-600 hover:text-blue-800">Manage requests</a>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <x-stat-card label="Approved requests" :value="$approvedCount" icon="archive" accent="emerald" />
                <x-stat-card label="Pending requests" :value="$pendingCount" icon="inbox" accent="amber" />
                <x-stat-card label="Favorites" value="0" hint="Not built yet" icon="star" accent="rose" />
            </div>

            <div class="mt-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-700">Recent rental requests</h3>
                    <a href="{{ route('renter.rental-requests.index') }}" wire:navigate class="text-xs font-medium text-blue-600 hover:text-blue-800">View all</a>
                </div>
                <div class="mt-3">
                    @if ($recentRequests->isEmpty())
                        <x-empty-state title="No rental requests yet" message="Browse listings and request to rent an item to see it here." />
                    @else
                        <div class="overflow-hidden overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($recentRequests as $request)
                                        <tr wire:key="recent-request-{{ $request->id }}">
                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-3">
                                                    @if ($request->listing->images->first())
                                                        <img src="{{ $request->listing->images->first()->url() }}" class="h-10 w-10 rounded-lg object-cover">
                                                    @else
                                                        <div class="h-10 w-10 rounded-lg bg-slate-100"></div>
                                                    @endif
                                                    <span class="font-medium text-slate-800">{{ $request->listing->name }}</span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-slate-500">{{ $request->start_date->format('M d') }} &ndash; {{ $request->end_date->format('M d, Y') }}</td>
                                            <td class="px-4 py-3">
                                                <x-badge :color="$request->status->badgeColor()">{{ $request->status->label() }}</x-badge>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </section>
    </div>
</div>
