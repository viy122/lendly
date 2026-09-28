<div>
    <x-page-header eyebrow="Owning" title="Rental requests" subtitle="Approve or reject requests for your listings." />

    <div class="w-full px-4 py-8 sm:px-6 lg:px-8">
        @error('approve')
            <div class="mb-6 rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">{{ $message }}</div>
        @enderror

        <div class="flex flex-wrap gap-2">
            @foreach (['requested' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'cancelled' => 'Cancelled', 'all' => 'All'] as $value => $label)
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
            @if ($rentalRequests->isEmpty())
                <x-empty-state title="Nothing here" message="No rental requests match this filter right now." />
            @else
                <div class="space-y-4">
                    @foreach ($rentalRequests as $request)
                        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm" wire:key="request-{{ $request->id }}">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div class="flex items-start gap-4">
                                    @if ($request->listing->images->first())
                                        <img src="{{ $request->listing->images->first()->url() }}" class="h-16 w-16 rounded-lg object-cover">
                                    @else
                                        <div class="h-16 w-16 rounded-lg bg-slate-100"></div>
                                    @endif
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <p class="font-semibold text-slate-800">{{ $request->listing->name }}</p>
                                            <x-badge :color="$request->status->badgeColor()">{{ $request->status->label() }}</x-badge>
                                        </div>
                                        <p class="mt-1 text-sm text-slate-500">
                                            {{ $request->start_date->format('M d, Y') }} &ndash; {{ $request->end_date->format('M d, Y') }}
                                            ({{ $request->rental_days }} day{{ $request->rental_days === 1 ? '' : 's' }})
                                        </p>
                                        <p class="mt-1 text-xs text-slate-400">
                                            Renter: {{ $request->renter->name }} ({{ $request->renter->email }}) &middot; {{ ucfirst($request->fulfillment_method->value) }} &middot; Total ₱{{ number_format($request->total_amount, 2) }}
                                        </p>
                                        @if ($request->status->value === 'rejected' && $request->rejection_reason)
                                            <p class="mt-1 text-xs text-rose-600">Reason: {{ $request->rejection_reason }}</p>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex items-center gap-3 text-xs font-medium">
                                    <a href="{{ route('rental-requests.chat', $request) }}" wire:navigate class="text-blue-600 hover:text-blue-800">Message renter</a>
                                    @if ($request->isPending())
                                        <button type="button" wire:click="startApproving({{ $request->id }})" class="rounded-lg bg-blue-600 px-3 py-1.5 text-white shadow-sm hover:bg-blue-700">Approve</button>
                                        <button type="button" wire:click="startRejecting({{ $request->id }})" class="rounded-lg border border-rose-200 px-3 py-1.5 text-rose-600 hover:bg-rose-50">Reject</button>
                                    @endif
                                </div>
                            </div>

                            @if ($approving === $request->id)
                                <div class="mt-4 rounded-lg border border-blue-200 bg-blue-50 p-4">
                                    <p class="text-sm font-semibold text-blue-800">Approve this request</p>
                                    <p class="mt-1 text-xs text-blue-700">
                                        By approving, you agree to the rental terms and agreement for this booking, including the
                                        cancellation policy (free more than 48 hours before the start date, 20% fee within 48 hours).
                                    </p>
                                    <label class="mt-3 flex items-start gap-2 text-sm text-slate-700">
                                        <input type="checkbox" wire:model="accept_terms" class="mt-0.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                        <span>I accept the rental terms and agreement for this booking.</span>
                                    </label>
                                    <x-input-error :messages="$errors->get('accept_terms')" class="mt-2" />
                                    <div class="mt-3 flex justify-end gap-2">
                                        <button type="button" wire:click="$set('approving', null)" class="text-xs font-medium text-slate-500 hover:text-slate-700">Cancel</button>
                                        <button type="button" wire:click="approve({{ $request->id }})" class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-blue-700">Confirm approval</button>
                                    </div>
                                </div>
                            @endif

                            @if ($rejecting === $request->id)
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
                    {{ $rentalRequests->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
