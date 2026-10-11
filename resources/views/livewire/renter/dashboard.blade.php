<div>
    <x-page-header eyebrow="Renter interface" title="Renter dashboard" subtitle="Find items to rent and keep track of your requests and rentals." />
    <div class="w-full space-y-8 px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-wrap justify-end gap-3">
            <a href="{{ route('listings.index') }}" wire:navigate class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                <x-icon name="search" class="h-4 w-4" /> Browse items to rent
            </a>
            <a href="{{ route('renter.rentals.index') }}" wire:navigate class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                <x-icon name="archive" class="h-4 w-4" /> My rentals
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

        <section>
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-base font-semibold text-slate-900">Your rental activity</h2>
                <a href="{{ route('renter.rental-requests.index') }}" wire:navigate class="text-xs font-medium text-blue-600 hover:text-blue-800">Manage requests</a>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <x-stat-card label="Approved requests" :value="$approvedCount" icon="archive" accent="emerald" />
                <x-stat-card label="Pending requests" :value="$pendingCount" icon="inbox" accent="amber" />
                <x-stat-card label="Active rentals" :value="$activeRentalsCount" icon="archive" accent="blue" />
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
