<div>
    <x-page-header eyebrow="Owning" title="My rentals" subtitle="Rentals created from your approved requests." />

    <div class="w-full px-4 py-7 sm:px-6 lg:px-8 lg:py-8">
        <div class="mb-6 flex flex-wrap gap-2">
            @foreach ([
                'all' => 'All',
                'payment_pending' => 'Payment pending',
                'paid' => 'Paid',
                'active' => 'Active',
                'overdue' => 'Overdue',
                'returned' => 'Returned',
                'completed' => 'Completed',
                'cancelled' => 'Cancelled',
            ] as $value => $label)
                <button
                    type="button"
                    wire:click="$set('filter', '{{ $value }}')"
                    class="rounded-full px-3 py-1.5 text-xs font-semibold {{ $filter === $value ? 'bg-blue-600 text-white shadow-sm' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        @if ($rentals->isEmpty())
            <x-empty-state :title="$filter === 'all' ? 'No rentals yet' : 'Nothing here'" :message="$filter === 'all' ? 'Once you approve a rental request, it will appear here.' : 'No rentals match this status filter right now.'" />
        @else
            <div class="overflow-hidden overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-[0_14px_42px_rgba(15,45,95,0.07)]">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="border-b border-blue-100 bg-gradient-to-r from-blue-50 to-sky-50/70">
                        <tr>
                            <th class="px-5 py-4 text-left text-[11px] font-bold uppercase tracking-[0.14em] text-blue-700">Item</th>
                            <th class="px-5 py-4 text-left text-[11px] font-bold uppercase tracking-[0.14em] text-blue-700">Renter</th>
                            <th class="px-5 py-4 text-left text-[11px] font-bold uppercase tracking-[0.14em] text-blue-700">Dates</th>
                            <th class="px-5 py-4 text-left text-[11px] font-bold uppercase tracking-[0.14em] text-blue-700">Earnings</th>
                            <th class="px-5 py-4 text-left text-[11px] font-bold uppercase tracking-[0.14em] text-blue-700">Status</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($rentals as $rental)
                            <tr wire:key="rental-{{ $rental->id }}" class="transition hover:bg-blue-50/40">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        @if ($rental->listing->images->first())
                                            <img src="{{ $rental->listing->images->first()->url() }}" class="h-12 w-12 rounded-xl object-cover shadow-sm">
                                        @else
                                            <div class="grid h-12 w-12 place-items-center rounded-xl bg-slate-100 text-slate-300"><x-icon name="archive" class="h-5 w-5" /></div>
                                        @endif
                                        <div><span class="font-bold text-slate-800">{{ $rental->listing->name }}</span><p class="mt-0.5 text-[11px] text-slate-400">Rental #{{ $rental->id }}</p></div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 font-medium text-slate-600">{{ $rental->renter->name }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-slate-500">{{ $rental->start_date->format('M d') }} &ndash; {{ $rental->end_date->format('M d, Y') }}</td>
                                <td class="whitespace-nowrap px-5 py-4 font-bold text-slate-700">₱{{ number_format($rental->rental_fee, 2) }}</td>
                                <td class="px-5 py-4">
                                    <x-badge :color="$rental->displayStatusColor()">{{ $rental->displayStatusLabel() }}</x-badge>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <a href="{{ route('owner.rentals.show', $rental) }}" wire:navigate class="group inline-flex min-h-10 items-center justify-center gap-2 rounded-xl bg-[#075cf5] px-4 py-2 text-xs font-bold text-white shadow-[0_7px_18px_rgba(7,92,245,0.22)] transition hover:-translate-y-0.5 hover:bg-blue-700 hover:shadow-[0_10px_24px_rgba(7,92,245,0.30)]">
                                        Manage
                                        <span class="transition-transform group-hover:translate-x-0.5" aria-hidden="true">→</span>
                                    </a>
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
