<div>
    <x-page-header eyebrow="Owning" title="My rentals" subtitle="Rentals created from your approved requests." />

    <div class="w-full px-4 py-8 sm:px-6 lg:px-8">
        @if ($rentals->isEmpty())
            <x-empty-state title="No rentals yet" message="Once you approve a rental request, it will appear here." />
        @else
            <div class="overflow-hidden overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="border-b-2 border-indigo-100 bg-indigo-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-indigo-700">Item</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-indigo-700">Renter</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-indigo-700">Dates</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-indigo-700">Earnings</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-indigo-700">Status</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($rentals as $rental)
                            <tr wire:key="rental-{{ $rental->id }}" class="hover:bg-slate-50/75">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        @if ($rental->listing->images->first())
                                            <img src="{{ $rental->listing->images->first()->url() }}" class="h-10 w-10 rounded-lg object-cover">
                                        @else
                                            <div class="h-10 w-10 rounded-lg bg-slate-100"></div>
                                        @endif
                                        <span class="font-medium text-slate-800">{{ $rental->listing->name }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-500">{{ $rental->renter->name }}</td>
                                <td class="px-4 py-3 text-slate-500">{{ $rental->start_date->format('M d') }} &ndash; {{ $rental->end_date->format('M d, Y') }}</td>
                                <td class="px-4 py-3 text-slate-500">₱{{ number_format($rental->rental_fee, 2) }}</td>
                                <td class="px-4 py-3">
                                    <x-badge :color="$rental->displayStatusColor()">{{ $rental->displayStatusLabel() }}</x-badge>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('owner.rentals.show', $rental) }}" wire:navigate class="text-xs font-medium text-blue-600 hover:text-blue-800">Manage</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $rentals->links() }}
            </div>
        @endif
    </div>
</div>
