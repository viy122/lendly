<div>
    <x-page-header eyebrow="Administration" title="Listings" subtitle="Review pending listings and moderate published ones." />

    <div class="w-full px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-wrap gap-2">
            @foreach (['pending_approval' => "Pending ({$pendingCount})", 'published' => 'Published', 'rejected' => 'Rejected', 'inactive' => 'Inactive', 'all' => 'All'] as $value => $label)
                <button
                    type="button"
                    wire:click="$set('filter', '{{ $value }}')"
                    aria-pressed="{{ $filter === $value ? 'true' : 'false' }}"
                    class="rounded-full px-3 py-1.5 text-xs font-semibold transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 {{ $filter === $value ? 'bg-blue-600 text-white shadow-sm' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}"
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
                            </div>

                            @if ($rejecting === $listing->id)
                                <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 p-4">
                                    <x-input-label for="rejection_reason" value="Reason for rejection" />
                                    <textarea wire:model="rejection_reason" id="rejection_reason" rows="2" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-rose-500 focus:ring-rose-500"></textarea>
                                    <x-input-error :messages="$errors->get('rejection_reason')" class="mt-2" />
                                    <div class="mt-4 flex flex-wrap justify-end gap-2">
                                        <button type="button" wire:click="$set('rejecting', null)" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition-colors hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-400 focus-visible:ring-offset-2">Cancel</button>
                                        <button type="button" wire:click="confirmReject" wire:loading.attr="disabled" wire:target="confirmReject" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-rose-600 bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:border-rose-700 hover:bg-rose-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-500 focus-visible:ring-offset-2 disabled:cursor-wait disabled:opacity-60">
                                            <span wire:loading.remove wire:target="confirmReject">Confirm rejection</span>
                                            <span wire:loading wire:target="confirmReject">Rejecting&hellip;</span>
                                        </button>
                                    </div>
                                </div>
                            @endif
                            <div class="-mx-5 -mb-5 mt-5 grid grid-cols-2 gap-2.5 rounded-b-xl border-t border-slate-100 bg-slate-50/60 px-5 py-4 sm:flex sm:flex-wrap sm:items-center sm:justify-end">
                                <a href="{{ route('listings.show', $listing) }}" wire:navigate target="_blank" class="col-span-2 inline-flex min-h-11 items-center justify-center gap-2 whitespace-nowrap rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition-colors hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 sm:mr-auto">
                                    <x-icon name="search" class="h-4 w-4 shrink-0" aria-hidden="true" />
                                    <span>View</span>
                                </a>
                                @if ($listing->status->value === 'pending_approval')
                                    <button type="button" wire:click="approve({{ $listing->id }})" wire:loading.attr="disabled" wire:target="approve({{ $listing->id }}), startRejecting({{ $listing->id }}), confirmReject" class="inline-flex min-h-11 items-center justify-center gap-2 whitespace-nowrap rounded-xl border border-blue-600 bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:border-blue-700 hover:bg-blue-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 disabled:cursor-wait disabled:opacity-60">
                                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" />
                                        </svg>
                                        <span>Approve</span>
                                    </button>
                                    <button type="button" wire:click="startRejecting({{ $listing->id }})" wire:loading.attr="disabled" wire:target="approve({{ $listing->id }}), startRejecting({{ $listing->id }}), confirmReject" class="inline-flex min-h-11 items-center justify-center gap-2 whitespace-nowrap rounded-xl border border-rose-200 bg-white px-4 py-2.5 text-sm font-semibold text-rose-600 shadow-sm transition-colors hover:border-rose-300 hover:bg-rose-50 hover:text-rose-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-500 focus-visible:ring-offset-2 disabled:cursor-wait disabled:opacity-60">
                                        <x-icon name="x-mark" class="h-4 w-4 shrink-0" aria-hidden="true" />
                                        <span>Reject</span>
                                    </button>
                                @elseif ($listing->status->value === 'published')
                                    <button type="button" wire:click="remove({{ $listing->id }})" wire:confirm="Remove this listing from the marketplace?" wire:loading.attr="disabled" wire:target="remove({{ $listing->id }})" class="col-span-2 inline-flex min-h-11 items-center justify-center gap-2 whitespace-nowrap rounded-xl border border-rose-200 bg-white px-4 py-2.5 text-sm font-semibold text-rose-600 shadow-sm transition-colors hover:border-rose-300 hover:bg-rose-50 hover:text-rose-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-500 focus-visible:ring-offset-2 disabled:cursor-wait disabled:opacity-60">
                                        <x-icon name="trash" class="h-4 w-4 shrink-0" aria-hidden="true" />
                                        <span>Remove</span>
                                    </button>
                                @endif
                            </div>
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
