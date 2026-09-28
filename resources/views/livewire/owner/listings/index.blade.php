<div>
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
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-indigo-700">Actions</th>
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
                                <td class="px-4 py-3 text-slate-500">₱{{ number_format($listing->price_per_day, 2) }}</td>
                                <td class="px-4 py-3">
                                    <x-badge :color="$listing->status->badgeColor()">{{ $listing->status->label() }}</x-badge>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-3 text-xs font-medium">
                                        <a href="{{ route('owner.listings.edit', $listing) }}" wire:navigate class="text-blue-600 hover:text-blue-800">Edit</a>

                                        @if ($listing->status->value === 'published')
                                            <button type="button" wire:click="deactivate({{ $listing->id }})" wire:confirm="Deactivate this listing?" class="text-slate-500 hover:text-slate-700">Deactivate</button>
                                        @elseif ($listing->status->value === 'inactive')
                                            <button type="button" wire:click="reactivate({{ $listing->id }})" class="text-blue-600 hover:text-blue-800">Reactivate</button>
                                        @endif

                                        <button type="button" wire:click="delete({{ $listing->id }})" wire:confirm="Delete this listing permanently? This cannot be undone." class="text-rose-600 hover:text-rose-800">Delete</button>
                                    </div>
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
