<div wire:poll.5s.keep-alive x-data="{ previousBodyOverflow: '' }">
    <x-page-header eyebrow="Administration" title="Transactions" subtitle="All rental bookings on the platform, across every owner and renter." />

    <div class="w-full px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
            <div class="relative w-full sm:min-w-64 sm:flex-1">
                <label for="transaction-search" class="sr-only">Search transactions</label>
                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" aria-hidden="true" />
                <input id="transaction-search" type="text" wire:model.live.debounce.400ms="search" placeholder="Search item, owner, or renter..."
                       class="min-h-11 w-full rounded-lg border-slate-300 pl-9 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div class="w-full sm:w-44">
                <label for="transaction-status" class="sr-only">Filter by transaction status</label>
                <select id="transaction-status" wire:model.live="status" class="min-h-11 w-full rounded-lg border-slate-300 bg-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $option)
                        <option value="{{ $option->value }}">{{ $option->label() }}</option>
                    @endforeach
                </select>
            </div>

            <button type="button" aria-haspopup="dialog" aria-controls="offline-payment-modal" x-on:click="previousBodyOverflow = document.body.style.overflow; $refs.offlinePaymentModal.showModal(); document.body.style.overflow = 'hidden'" class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-lg border border-blue-300 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700 shadow-sm transition hover:border-blue-500 hover:bg-blue-100 focus:outline-none focus:ring-4 focus:ring-blue-100 sm:w-auto">
                <x-icon name="cog" class="h-4 w-4 shrink-0" aria-hidden="true" />
                <span>Configure offline payment instructions</span>
            </button>
        </div>

        <div class="mt-4 flex w-full max-w-sm items-center gap-3 rounded-xl border border-slate-200 border-l-4 border-l-indigo-600 bg-white p-4 shadow-sm">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-indigo-600">
                <x-icon name="archive" class="h-5 w-5" />
            </span>
            <div>
                <p class="text-xs font-medium text-slate-400">Total transaction value (paid, matching filters)</p>
                <p class="mt-0.5 text-lg font-bold text-slate-900">₱{{ number_format($totalTransactionValue, 2) }}</p>
            </div>
        </div>

        <div class="mt-6">
            @if ($rentals->isEmpty())
                <x-empty-state title="No transactions found" message="Try a different search term or status filter." />
            @else
                <div class="overflow-hidden overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="border-b-2 border-indigo-100 bg-indigo-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-indigo-700">Item</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-indigo-700">Owner</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-indigo-700">Renter</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-indigo-700">Dates</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-indigo-700">Total</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-indigo-700">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-indigo-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($rentals as $rental)
                                <tr wire:key="rental-{{ $rental->id }}" class="hover:bg-slate-50/75">
                                    <td class="px-4 py-3 font-medium text-slate-800">{{ $rental->listing->name }}</td>
                                    <td class="px-4 py-3 text-slate-500">{{ $rental->owner->name }}</td>
                                    <td class="px-4 py-3 text-slate-500">{{ $rental->renter->name }}</td>
                                    <td class="px-4 py-3 text-slate-500">{{ $rental->start_date->format('M d') }} &ndash; {{ $rental->end_date->format('M d, Y') }}</td>
                                    <td class="px-4 py-3 font-medium text-slate-800">₱{{ number_format($rental->total_amount, 2) }}</td>
                                    <td class="px-4 py-3">
                                        <x-badge :color="$rental->displayStatusColor()">{{ $rental->displayStatusLabel() }}</x-badge>
                                    </td>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('admin.rentals.show', $rental) }}" wire:navigate class="inline-flex min-h-10 items-center rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-700">View and manage</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-6">
                    {{ $rentals->links() }}
                </div>
            @endif
        </div>
    </div>

    <dialog
        id="offline-payment-modal"
        x-ref="offlinePaymentModal"
        wire:ignore.self
        aria-labelledby="offline-payment-title"
        aria-describedby="offline-payment-description"
        x-on:close-offline-payment.stop="$el.close()"
        x-on:close="document.body.style.overflow = previousBodyOverflow"
        x-on:livewire:navigating.window="if ($el.open) { $el.close(); document.body.style.overflow = previousBodyOverflow; }"
        x-on:click="if ($event.target === $el) { const bounds = $el.getBoundingClientRect(); if ($event.clientX < bounds.left || $event.clientX > bounds.right || $event.clientY < bounds.top || $event.clientY > bounds.bottom) $el.close(); }"
        class="m-auto max-h-[calc(100dvh-2rem)] w-[calc(100%-2rem)] max-w-2xl overflow-y-auto rounded-2xl border-0 bg-slate-50 p-0 text-slate-900 shadow-xl backdrop:bg-slate-900/50"
    >
        <livewire:admin.payments.settings :modal="true" />
    </dialog>
</div>
