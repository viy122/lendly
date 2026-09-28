<div>
    <x-page-header eyebrow="Administration" title="Listings" subtitle="Review pending listings and moderate published ones." />

    <div class="w-full px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-wrap gap-2">
            @foreach (['pending_approval' => "Pending ({$pendingCount})", 'published' => 'Published', 'rejected' => 'Rejected', 'inactive' => 'Inactive', 'all' => 'All'] as $value => $label)
                <button
                    type="button"
                    wire:click="$set('filter', '{{ $value }}')"
                    class="rounded-full px-3 py-1.5 text-xs font-semibold {{ $filter === $value ? 'bg-blue-600 text-white shadow-sm' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}"
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <div class="mt-6">
            @if ($listings->isEmpty())
                <x-empty-state title="Nothing here" message="No listings match this filter right now." />
            @else
                <div class="space-y-4">
                    @foreach ($listings as $listing)
                        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm" wire:key="listing-{{ $listing->id }}">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div class="flex items-start gap-4">
                                    @if ($listing->images->first())
                                        <img src="{{ $listing->images->first()->url() }}" class="h-16 w-16 rounded-lg object-cover">
                                    @else
                                        <div class="h-16 w-16 rounded-lg bg-slate-100"></div>
                                    @endif
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <p class="font-semibold text-slate-800">{{ $listing->name }}</p>
                                            <x-badge :color="$listing->status->badgeColor()">{{ $listing->status->label() }}</x-badge>
                                        </div>
                                        <div class="mt-1.5 flex items-center gap-2">
                                            <x-category-badge :category="$listing->category" />
                                            <span class="text-sm text-slate-500">₱{{ number_format($listing->price_per_day, 2) }}/day &middot; {{ $listing->location }}</span>
                                        </div>
                                        <p class="mt-1 text-xs text-slate-400">Listed by {{ $listing->owner->name }} ({{ $listing->owner->email }})</p>
                                        @if ($listing->status->value === 'rejected' && $listing->rejection_reason)
                                            <p class="mt-1 text-xs text-rose-600">Reason: {{ $listing->rejection_reason }}</p>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex items-center gap-3 text-xs font-medium">
                                    <a href="{{ route('listings.show', $listing) }}" wire:navigate target="_blank" class="text-slate-500 hover:text-slate-700">View</a>

                                    @if ($listing->status->value === 'pending_approval')
                                        <button type="button" wire:click="approve({{ $listing->id }})" class="rounded-lg bg-blue-600 px-3 py-1.5 text-white shadow-sm hover:bg-blue-700">Approve</button>
                                        <button type="button" wire:click="startRejecting({{ $listing->id }})" class="rounded-lg border border-rose-200 px-3 py-1.5 text-rose-600 hover:bg-rose-50">Reject</button>
                                    @elseif ($listing->status->value === 'published')
                                        <button type="button" wire:click="remove({{ $listing->id }})" wire:confirm="Remove this listing from the marketplace?" class="rounded-lg border border-rose-200 px-3 py-1.5 text-rose-600 hover:bg-rose-50">Remove</button>
                                    @endif
                                </div>
                            </div>

                            @if ($rejecting === $listing->id)
                                <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 p-4">
                                    <x-input-label for="rejection_reason" value="Reason for rejection" />
                                    <textarea wire:model="rejection_reason" id="rejection_reason" rows="2" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-rose-500 focus:ring-rose-500"></textarea>
                                    <x-input-error :messages="$errors->get('rejection_reason')" class="mt-2" />
                                    <div class="mt-3 flex justify-end gap-2">
                                        <button type="button" wire:click="$set('rejecting', null)" class="text-xs font-medium text-slate-500 hover:text-slate-700">Cancel</button>
                                        <button type="button" wire:click="confirmReject" class="rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-rose-700">Confirm rejection</button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $listings->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
