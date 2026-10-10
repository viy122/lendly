<div>
    <x-page-header eyebrow="Administration" title="Transactions" subtitle="All rental bookings on the platform, across every owner and renter." />

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-wrap gap-2">
                <button type="button" wire:click="$set('status', '')"
                        class="rounded-full px-3 py-1.5 text-xs font-semibold {{ $status === '' ? 'bg-blue-600 text-white shadow-sm' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
                    All
                </button>
                @foreach ($statuses as $option)
                    <button type="button" wire:click="$set('status', '{{ $option->value }}')"
                            class="rounded-full px-3 py-1.5 text-xs font-semibold {{ $status === $option->value ? 'bg-blue-600 text-white shadow-sm' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
                        {{ $option->label() }}
                    </button>
                @endforeach
            </div>

            <div class="relative w-full sm:w-64">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                <input type="text" wire:model.live.debounce.400ms="search" placeholder="Search item, owner, or renter..."
                       class="w-full rounded-lg border-slate-300 pl-9 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
        </div>

        <div class="mt-4 flex items-center gap-3 rounded-xl border border-slate-200 border-l-4 border-l-indigo-600 bg-white p-4 shadow-sm">
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
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Item</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Owner</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Renter</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Dates</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Total</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Details</th>
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
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('admin.rentals.show', $rental) }}" wire:navigate class="whitespace-nowrap font-medium text-blue-600 hover:text-blue-800">View transaction</a>
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
</div>
