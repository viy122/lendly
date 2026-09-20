<div class="mx-auto max-w-2xl px-4 py-8 sm:px-6 lg:px-8">
    <a href="{{ route('renter.rentals.index') }}" wire:navigate class="flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-700">
        <x-icon name="chevron-down" class="h-3.5 w-3.5 rotate-90" /> Back to my rentals
    </a>

    @if ($rental->isPaymentPending())
        <h1 class="mt-3 text-xl font-semibold text-slate-900">Confirm payment</h1>
        <p class="mt-1 text-sm text-slate-500">{{ $rental->listing->name }} &middot; {{ $rental->start_date->format('M d, Y') }} &ndash; {{ $rental->end_date->format('M d, Y') }}</p>

        <div class="mt-6 rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="text-sm font-semibold text-slate-700">Payment summary</h2>
            <dl class="mt-4 space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-slate-500">Rental fee</dt>
                    <dd class="font-medium text-slate-800">₱{{ number_format($rental->rental_fee, 2) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Platform fee</dt>
                    <dd class="font-medium text-slate-800">₱{{ number_format($rental->commission_amount, 2) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Security deposit (refundable)</dt>
                    <dd class="font-medium text-slate-800">₱{{ number_format($rental->security_deposit, 2) }}</dd>
                </div>
                <div class="flex justify-between border-t border-slate-100 pt-2 text-base">
                    <dt class="font-semibold text-slate-900">Total</dt>
                    <dd class="font-semibold text-slate-900">₱{{ number_format($rental->total_amount, 2) }}</dd>
                </div>
            </dl>

            <p class="mt-4 text-xs text-slate-400">
                This is a simulated payment for demonstration purposes only. No real money is processed.
            </p>

            <button type="button" wire:click="confirmPayment" wire:loading.attr="disabled" class="mt-5 w-full rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50">
                Confirm Simulated Payment
            </button>

            <button type="button" wire:click="$toggle('showCancelForm')" class="mt-3 w-full text-center text-xs font-medium text-slate-500 hover:text-slate-700">
                Cancel this booking instead
            </button>
        </div>

        @include('livewire.renter.rentals._cancel-form')
    @else
        <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <h1 class="text-xl font-semibold text-slate-900">{{ $rental->listing->name }}</h1>
                <x-badge :color="$rental->displayStatusColor()">{{ $rental->displayStatusLabel() }}</x-badge>
            </div>
            <a href="{{ route('rental-requests.chat', $rental->rental_request_id) }}" wire:navigate class="text-xs font-medium text-blue-600 hover:text-blue-800">
                Message owner
            </a>
        </div>
        <p class="mt-1 text-sm text-slate-500">{{ $rental->start_date->format('M d, Y') }} &ndash; {{ $rental->end_date->format('M d, Y') }}</p>

        @if ($rental->isOverdue())
            <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
                <p class="font-semibold">This rental is overdue by {{ $rental->days_overdue }} day{{ $rental->days_overdue === 1 ? '' : 's' }}.</p>
                <p class="mt-1">Late fee so far: ₱{{ number_format($rental->late_fee, 2) }}</p>
            </div>
        @endif

        @if ($rental->isCancelled())
            <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm">
                <p class="font-semibold text-slate-700">This booking was cancelled.</p>
                <p class="mt-1 text-slate-600">Reason: {{ $rental->cancellation_reason }}</p>
                <p class="mt-1 text-slate-600">Cancellation fee: ₱{{ number_format($rental->cancellation_fee, 2) }}</p>
                <p class="mt-1 text-xs text-slate-400">Cancelled {{ $rental->cancelled_at->format('M d, Y \a\t g:i A') }}</p>
            </div>
        @elseif ($rental->isCancellableByRenter())
            <div class="mt-4">
                <button type="button" wire:click="$toggle('showCancelForm')" class="text-xs font-medium text-slate-500 hover:text-slate-700">
                    Cancel this booking
                </button>
            </div>

            @include('livewire.renter.rentals._cancel-form')
        @endif

        @if ($rental->awaitingPickupConfirmation())
            <div class="mt-6 rounded-xl border border-slate-200 bg-white p-6">
                <h2 class="text-sm font-semibold text-slate-700">Pickup confirmation</h2>
                <p class="mt-1 text-sm text-slate-500">Both you and the owner need to confirm the item was handed over before the rental starts.</p>
                <ul class="mt-3 space-y-1 text-sm">
                    <li class="{{ $rental->pickup_confirmed_by_owner_at ? 'text-blue-700' : 'text-slate-400' }}">
                        {{ $rental->pickup_confirmed_by_owner_at ? '✓ Owner confirmed' : 'Waiting on owner confirmation' }}
                    </li>
                    <li class="{{ $rental->pickup_confirmed_by_renter_at ? 'text-blue-700' : 'text-slate-400' }}">
                        {{ $rental->pickup_confirmed_by_renter_at ? '✓ You confirmed' : 'Waiting on your confirmation' }}
                    </li>
                </ul>

                @if (! $rental->pickup_confirmed_by_renter_at)
                    <button type="button" wire:click="confirmPickup" class="mt-4 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                        Confirm I picked up the item
                    </button>
                @endif
            </div>
        @endif

        @if ($rental->awaitingReturnConfirmation())
            <div class="mt-6 rounded-xl border border-slate-200 bg-white p-6">
                <h2 class="text-sm font-semibold text-slate-700">Return confirmation</h2>
                <p class="mt-1 text-sm text-slate-500">Both you and the owner need to confirm the item was returned.</p>
                <ul class="mt-3 space-y-1 text-sm">
                    <li class="{{ $rental->return_confirmed_by_owner_at ? 'text-blue-700' : 'text-slate-400' }}">
                        {{ $rental->return_confirmed_by_owner_at ? '✓ Owner confirmed' : 'Waiting on owner confirmation' }}
                    </li>
                    <li class="{{ $rental->return_confirmed_by_renter_at ? 'text-blue-700' : 'text-slate-400' }}">
                        {{ $rental->return_confirmed_by_renter_at ? '✓ You confirmed' : 'Waiting on your confirmation' }}
                    </li>
                </ul>

                @if (! $rental->return_confirmed_by_renter_at)
                    <button type="button" wire:click="confirmReturn" class="mt-4 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                        Confirm I returned the item
                    </button>
                @endif
            </div>
        @endif

        @if ($rental->isReturned() && ! $rental->afterConditionRecord)
            <div class="mt-6 rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                Item returned. Waiting for the owner to complete their inspection.
            </div>
        @endif

        @if ($rental->beforeConditionRecord || $rental->afterConditionRecord)
            <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                @if ($rental->beforeConditionRecord)
                    <div class="rounded-xl border border-slate-200 bg-white p-5">
                        <h3 class="text-sm font-semibold text-slate-700">Condition before rental</h3>
                        <p class="mt-2 text-sm text-slate-600">{{ $rental->beforeConditionRecord->condition->label() }}</p>
                        @if ($rental->beforeConditionRecord->notes)
                            <p class="mt-1 text-sm text-slate-500">{{ $rental->beforeConditionRecord->notes }}</p>
                        @endif
                        @if ($rental->beforeConditionRecord->photos->isNotEmpty())
                            <div class="mt-3 grid grid-cols-3 gap-2">
                                @foreach ($rental->beforeConditionRecord->photos as $photo)
                                    <img src="{{ $photo->url() }}" class="aspect-square w-full rounded-lg object-cover">
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                @if ($rental->afterConditionRecord)
                    <div class="rounded-xl border border-slate-200 bg-white p-5">
                        <h3 class="text-sm font-semibold text-slate-700">Condition after rental</h3>
                        <p class="mt-2 text-sm text-slate-600">{{ $rental->afterConditionRecord->condition->label() }}</p>
                        @if ($rental->afterConditionRecord->notes)
                            <p class="mt-1 text-sm text-slate-500">{{ $rental->afterConditionRecord->notes }}</p>
                        @endif
                        @if ($rental->afterConditionRecord->photos->isNotEmpty())
                            <div class="mt-3 grid grid-cols-3 gap-2">
                                @foreach ($rental->afterConditionRecord->photos as $photo)
                                    <img src="{{ $photo->url() }}" class="aspect-square w-full rounded-lg object-cover">
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        @endif

        @if ($rental->securityDeposit)
            <h2 class="mt-6 text-sm font-semibold text-slate-700">Security deposit</h2>
            <div class="mt-3 rounded-xl border border-slate-200 bg-white p-6">
                <div class="flex items-center gap-2">
                    <x-badge :color="$rental->securityDeposit->status->badgeColor()">{{ $rental->securityDeposit->status->label() }}</x-badge>
                    <span class="text-sm text-slate-500">₱{{ number_format($rental->securityDeposit->amount, 2) }} held</span>
                </div>

                @if ($rental->damageReport)
                    <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-4">
                        <p class="text-sm font-semibold text-amber-800">Damage claim: {{ $rental->damageReport->damage_type }}</p>
                        <p class="mt-1 text-sm text-amber-700">{{ $rental->damageReport->description }}</p>
                        <p class="mt-1 text-sm text-amber-700">
                            Estimated repair: ₱{{ number_format($rental->damageReport->estimated_repair_cost, 2) }} &middot;
                            Proposed deduction: ₱{{ number_format($rental->damageReport->proposed_deduction, 2) }}
                        </p>

                        @if ($rental->damageReport->photos->isNotEmpty())
                            <div class="mt-3 grid grid-cols-4 gap-2">
                                @foreach ($rental->damageReport->photos as $photo)
                                    <img src="{{ $photo->url() }}" class="aspect-square w-full rounded-lg object-cover">
                                @endforeach
                            </div>
                        @endif

                        @if ($rental->damageReport->isPending())
                            <div class="mt-4 space-y-3">
                                <button type="button" wire:click="acceptDamageClaim" wire:confirm="Accept this claim? ₱{{ number_format($rental->damageReport->proposed_deduction, 2) }} will be deducted from your deposit." class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                                    Accept claim
                                </button>

                                <div>
                                    <x-input-label for="damage_response_notes" value="Or dispute it — explain why" />
                                    <textarea wire:model="damage_response_notes" id="damage_response_notes" rows="2" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-rose-500 focus:ring-rose-500"></textarea>
                                    <x-input-error :messages="$errors->get('damage_response_notes')" class="mt-2" />
                                    <button type="button" wire:click="disputeDamageClaim" class="mt-2 rounded-lg border border-rose-200 px-4 py-2 text-sm font-semibold text-rose-600 hover:bg-rose-50">
                                        Dispute claim
                                    </button>
                                </div>
                            </div>
                        @elseif ($rental->damageReport->status->value === 'disputed')
                            <p class="mt-3 text-sm text-amber-700">You disputed this claim. An admin will review it.</p>
                        @elseif ($rental->damageReport->status->value === 'accepted')
                            <p class="mt-3 text-sm text-amber-700">
                                You accepted this claim. Refund: ₱{{ number_format($rental->securityDeposit->amount - $rental->securityDeposit->deducted_amount, 2) }}
                            </p>
                        @endif
                    </div>
                @elseif ($rental->securityDeposit->status->value === 'released')
                    <p class="mt-3 text-sm text-slate-600">Your full deposit has been released.</p>
                @elseif ($rental->securityDeposit->status->value === 'return_eligible')
                    <p class="mt-3 text-sm text-slate-600">No damage reported. Waiting for the owner to release your deposit.</p>
                @endif
            </div>
        @endif

        @if ($rental->isCompleted())
            <h2 class="mt-6 text-sm font-semibold text-slate-700">Leave a review</h2>
            <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="rounded-xl border border-slate-200 bg-white p-5">
                    <h3 class="text-sm font-semibold text-slate-700">Rate the owner</h3>
                    @if ($rental->reviewFromRenterToOwner)
                        <p class="mt-2 text-sm text-slate-600">You rated {{ $rental->reviewFromRenterToOwner->rating }}/5 stars</p>
                        @if ($rental->reviewFromRenterToOwner->comment)
                            <p class="mt-1 text-sm text-slate-500">"{{ $rental->reviewFromRenterToOwner->comment }}"</p>
                        @endif
                    @else
                        <form wire:submit="submitOwnerReview" class="mt-3 space-y-3">
                            <select wire:model="owner_rating" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @for ($i = 5; $i >= 1; $i--)
                                    <option value="{{ $i }}">{{ $i }} star{{ $i === 1 ? '' : 's' }}</option>
                                @endfor
                            </select>
                            <textarea wire:model="owner_comment" rows="2" placeholder="Optional comment" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                            <x-primary-button>Submit rating</x-primary-button>
                        </form>
                    @endif
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-5">
                    <h3 class="text-sm font-semibold text-slate-700">Rate the item</h3>
                    @if ($rental->reviewFromRenterToListing)
                        <p class="mt-2 text-sm text-slate-600">You rated {{ $rental->reviewFromRenterToListing->rating }}/5 stars</p>
                        @if ($rental->reviewFromRenterToListing->comment)
                            <p class="mt-1 text-sm text-slate-500">"{{ $rental->reviewFromRenterToListing->comment }}"</p>
                        @endif
                    @else
                        <form wire:submit="submitListingReview" class="mt-3 space-y-3">
                            <select wire:model="listing_rating" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @for ($i = 5; $i >= 1; $i--)
                                    <option value="{{ $i }}">{{ $i }} star{{ $i === 1 ? '' : 's' }}</option>
                                @endfor
                            </select>
                            <textarea wire:model="listing_comment" rows="2" placeholder="Optional comment" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                            <x-primary-button>Submit rating</x-primary-button>
                        </form>
                    @endif
                </div>
            </div>
        @endif

        <h2 class="mt-6 text-sm font-semibold text-slate-700">Disputes</h2>
        <div class="mt-3 rounded-xl border border-slate-200 bg-white p-6">
            @if (session('status'))
                <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-700">{{ session('status') }}</div>
            @endif

            @forelse ($rental->disputes as $dispute)
                <div class="mb-3 rounded-lg border border-slate-200 p-3" wire:key="dispute-{{ $dispute->id }}">
                    <div class="flex items-center gap-2">
                        <p class="text-sm font-semibold text-slate-800">{{ $dispute->reason->label() }}</p>
                        <x-badge :color="$dispute->status->badgeColor()">{{ $dispute->status->label() }}</x-badge>
                    </div>
                    <p class="mt-1 text-sm text-slate-500">{{ $dispute->description }}</p>
                    @if ($dispute->status->value === 'resolved')
                        <p class="mt-1 text-sm text-blue-700">Resolution: {{ $dispute->resolution?->label() }}</p>
                        @if ($dispute->resolution_notes)
                            <p class="mt-1 text-sm text-slate-500">{{ $dispute->resolution_notes }}</p>
                        @endif
                    @endif
                </div>
            @empty
                <p class="text-sm text-slate-400">No disputes filed for this rental.</p>
            @endforelse

            @if (! $showDisputeForm)
                <button type="button" wire:click="$set('showDisputeForm', true)" class="mt-2 text-sm font-medium text-rose-600 hover:text-rose-800">
                    Report an issue
                </button>
            @else
                <form wire:submit="createDispute" class="mt-3 space-y-3 rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <div>
                        <x-input-label for="dispute_reason" value="Reason" />
                        <select wire:model="dispute_reason" id="dispute_reason" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Select a reason</option>
                            @foreach (\App\Enums\DisputeReason::cases() as $reason)
                                <option value="{{ $reason->value }}">{{ $reason->label() }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('dispute_reason')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="dispute_description" value="Describe what happened" />
                        <textarea wire:model="dispute_description" id="dispute_description" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                        <x-input-error :messages="$errors->get('dispute_description')" class="mt-2" />
                    </div>
                    <div class="flex gap-2">
                        <x-primary-button>Submit dispute</x-primary-button>
                        <button type="button" wire:click="$set('showDisputeForm', false)" class="text-sm font-medium text-slate-500 hover:text-slate-700">Cancel</button>
                    </div>
                </form>
            @endif
        </div>

        @if ($rental->payment)
        <h2 class="mt-6 text-sm font-semibold text-slate-700">Transaction receipt</h2>
        <div class="mt-3 rounded-xl border border-slate-200 bg-white p-6">
            <div class="flex justify-between text-sm">
                <span class="text-slate-400">Transaction ID</span>
                <span class="font-mono font-medium text-slate-800">{{ $rental->payment->transaction_reference }}</span>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-4 border-t border-slate-100 pt-4 text-sm">
                <div>
                    <p class="text-slate-400">Renter</p>
                    <p class="font-medium text-slate-800">{{ $rental->renter->name }}</p>
                </div>
                <div>
                    <p class="text-slate-400">Owner</p>
                    <p class="font-medium text-slate-800">{{ $rental->owner->name }}</p>
                </div>
                <div>
                    <p class="text-slate-400">Item</p>
                    <p class="font-medium text-slate-800">{{ $rental->listing->name }}</p>
                </div>
                <div>
                    <p class="text-slate-400">Rental dates</p>
                    <p class="font-medium text-slate-800">{{ $rental->start_date->format('M d, Y') }} &ndash; {{ $rental->end_date->format('M d, Y') }}</p>
                </div>
            </div>

            <dl class="mt-4 space-y-2 border-t border-slate-100 pt-4 text-sm">
                <div class="flex justify-between">
                    <dt class="text-slate-500">Rental fee</dt>
                    <dd class="font-medium text-slate-800">₱{{ number_format($rental->rental_fee, 2) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Platform fee</dt>
                    <dd class="font-medium text-slate-800">₱{{ number_format($rental->commission_amount, 2) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Security deposit</dt>
                    <dd class="font-medium text-slate-800">₱{{ number_format($rental->security_deposit, 2) }}</dd>
                </div>
                @if ($rental->late_fee > 0)
                    <div class="flex justify-between text-rose-600">
                        <dt>Late fee</dt>
                        <dd class="font-medium">₱{{ number_format($rental->late_fee, 2) }}</dd>
                    </div>
                @endif
                <div class="flex justify-between border-t border-slate-100 pt-2 text-base">
                    <dt class="font-semibold text-slate-900">Total paid</dt>
                    <dd class="font-semibold text-slate-900">₱{{ number_format($rental->payment->amount, 2) }}</dd>
                </div>
            </dl>

            <p class="mt-4 text-xs text-slate-400">Paid on {{ $rental->paid_at->format('M d, Y \a\t g:i A') }}</p>
        </div>
        @endif
    @endif
</div>
