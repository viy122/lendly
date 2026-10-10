<div wire:poll.30s="refreshRental" class="mx-auto max-w-2xl px-4 py-8 sm:px-6 lg:px-8">
    <a href="{{ route('owner.rentals.index') }}" wire:navigate class="flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-700">
        <x-icon name="chevron-down" class="h-3.5 w-3.5 rotate-90" /> Back to my rentals
    </a>

    <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <h1 class="text-xl font-semibold text-slate-900">{{ $rental->listing->name }}</h1>
            <x-badge :color="$rental->displayStatusColor()">{{ $rental->displayStatusLabel() }}</x-badge>
        </div>
        <a href="{{ route('rental-requests.chat', $rental->rental_request_id) }}" wire:navigate class="text-xs font-medium text-blue-600 hover:text-blue-800">
            Message renter
        </a>
    </div>
    <p class="mt-1 text-sm text-slate-500">
        {{ $rental->start_date->format('M d, Y') }} &ndash; {{ $rental->end_date->format('M d, Y') }} &middot; Renter:
        @if (! $rental->renter->trashed() && ! $rental->renter->isSuspended() && $rental->renter->hasVerifiedEmail())
            <a href="{{ route('users.show', $rental->renter) }}" wire:navigate class="text-blue-600 hover:text-blue-800">{{ $rental->renter->name }}</a>
        @else
            {{ $rental->renter->name }}
        @endif
    </p>

    @if ($rental->isPaymentPending())
        <div class="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-700">
            Waiting for the renter to complete simulated payment.
        </div>
    @endif

    @if ($rental->isOverdue())
        <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
            <p class="font-semibold">This rental is overdue by {{ $rental->currentOverdueDays() }} day{{ $rental->currentOverdueDays() === 1 ? '' : 's' }}.</p>
            <p class="mt-1">Late fee so far: ₱{{ number_format($rental->currentLateFee(), 2) }}</p>
        </div>
    @endif

    @if ($rental->isCancelled())
        <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm">
            <p class="font-semibold text-slate-700">The renter cancelled this booking.</p>
            <p class="mt-1 text-slate-600">Reason: {{ $rental->cancellation_reason }}</p>
            <p class="mt-1 text-slate-600">Cancellation fee: ₱{{ number_format($rental->cancellation_fee, 2) }}</p>
            <p class="mt-1 text-xs text-slate-400">Cancelled {{ $rental->cancelled_at->format('M d, Y \a\t g:i A') }}</p>
        </div>
    @endif

    <!-- Before-rental condition -->
    @if ($rental->beforeConditionRecord)
        <div class="mt-6 rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="text-sm font-semibold text-slate-700">Condition before rental</h2>
            <p class="mt-2 text-sm text-slate-600">Condition: <span class="font-medium">{{ $rental->beforeConditionRecord->condition->label() }}</span></p>
            @if ($rental->beforeConditionRecord->notes)
                <p class="mt-1 text-sm text-slate-500">{{ $rental->beforeConditionRecord->notes }}</p>
            @endif
            @if ($rental->beforeConditionRecord->photos->isNotEmpty())
                <div class="mt-3 grid grid-cols-4 gap-2">
                    @foreach ($rental->beforeConditionRecord->photos as $photo)
                        <img src="{{ $photo->url() }}" class="aspect-square w-full rounded-lg object-cover">
                    @endforeach
                </div>
            @endif
        </div>
    @elseif (! $rental->isPaymentPending())
        <div class="mt-6 rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="text-sm font-semibold text-slate-700">Record condition before rental</h2>
            <p class="mt-1 text-sm text-slate-500">Document the item's condition before handing it over.</p>

            <form wire:submit="recordBeforeCondition" class="mt-4 space-y-4">
                <div>
                    <x-input-label for="before_condition" value="Condition" />
                    <select wire:model="before_condition" id="before_condition" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach (\App\Enums\ListingCondition::cases() as $option)
                            <option value="{{ $option->value }}">{{ $option->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="before_notes" value="Notes (optional)" />
                    <textarea wire:model="before_notes" id="before_notes" rows="2" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="e.g. Minor scratch on handle."></textarea>
                </div>
                <div>
                    <x-input-label value="Photos (optional)" />
                    <input type="file" wire:model="before_photos" multiple accept="image/*" class="mt-1 block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-blue-700 hover:file:bg-blue-100">
                    <x-input-error :messages="$errors->get('before_photos.*')" class="mt-2" />
                </div>
                <x-primary-button>Save condition record</x-primary-button>
            </form>
        </div>
    @endif

    @if ($rental->awaitingPickupConfirmation())
        <div class="mt-6 rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="text-sm font-semibold text-slate-700">Pickup confirmation</h2>
            <p class="mt-1 text-sm text-slate-500">Both you and the renter need to confirm the item was handed over before the rental starts.</p>
            <ul class="mt-3 space-y-1 text-sm">
                <li class="{{ $rental->pickup_confirmed_by_owner_at ? 'text-blue-700' : 'text-slate-400' }}">
                    {{ $rental->pickup_confirmed_by_owner_at ? '✓ You confirmed' : 'Waiting on your confirmation' }}
                </li>
                <li class="{{ $rental->pickup_confirmed_by_renter_at ? 'text-blue-700' : 'text-slate-400' }}">
                    {{ $rental->pickup_confirmed_by_renter_at ? '✓ Renter confirmed' : 'Waiting on renter confirmation' }}
                </li>
            </ul>

            @if (! $rental->pickup_confirmed_by_owner_at)
                <button type="button" wire:click="confirmPickup" class="mt-4 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                    Confirm pickup handed over
                </button>
            @endif
        </div>
    @endif

    @if ($rental->awaitingReturnConfirmation())
        <div class="mt-6 rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="text-sm font-semibold text-slate-700">Return confirmation</h2>
            <p class="mt-1 text-sm text-slate-500">Confirm you received the item. The rental closes automatically once you also save its returned condition.</p>
            <ul class="mt-3 space-y-1 text-sm">
                <li class="{{ $rental->return_confirmed_by_owner_at ? 'text-blue-700' : 'text-slate-400' }}">
                    {{ $rental->return_confirmed_by_owner_at ? '✓ You confirmed' : 'Waiting on your confirmation' }}
                </li>
            </ul>

            @if (! $rental->return_confirmed_by_owner_at)
                <button type="button" wire:click="confirmReturn" class="mt-4 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                    Confirm item returned
                </button>
            @endif
        </div>
    @endif

    <!-- After-rental condition -->
    @if ($rental->afterConditionRecord)
        <div class="mt-6 rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="text-sm font-semibold text-slate-700">Condition after rental</h2>
            <p class="mt-2 text-sm text-slate-600">Condition: <span class="font-medium">{{ $rental->afterConditionRecord->condition->label() }}</span></p>
            @if ($rental->afterConditionRecord->notes)
                <p class="mt-1 text-sm text-slate-500">{{ $rental->afterConditionRecord->notes }}</p>
            @endif
            @if ($rental->afterConditionRecord->photos->isNotEmpty())
                <div class="mt-3 grid grid-cols-4 gap-2">
                    @foreach ($rental->afterConditionRecord->photos as $photo)
                        <img src="{{ $photo->url() }}" class="aspect-square w-full rounded-lg object-cover">
                    @endforeach
                </div>
            @endif

            @if ($rental->damageReport)
                <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-4">
                    <div class="flex items-center gap-2">
                        <p class="text-sm font-semibold text-amber-800">Damage reported</p>
                        <x-badge :color="$rental->damageReport->status->badgeColor()">{{ $rental->damageReport->status->label() }}</x-badge>
                    </div>
                    <p class="mt-1 text-sm text-amber-700">{{ $rental->damageReport->damage_type }}: {{ $rental->damageReport->description }}</p>
                    <p class="mt-1 text-sm text-amber-700">Estimated repair: ₱{{ number_format($rental->damageReport->estimated_repair_cost, 2) }} &middot; Proposed deduction: ₱{{ number_format($rental->damageReport->proposed_deduction, 2) }}</p>
                    @if ($rental->damageReport->renter_response_notes)
                        <p class="mt-2 text-sm text-amber-700"><span class="font-medium">Renter's response:</span> {{ $rental->damageReport->renter_response_notes }}</p>
                    @endif
                </div>
            @endif
        </div>
    @elseif ($rental->awaitingReturnConfirmation() || $rental->isReturned())
        <div class="mt-6 rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="text-sm font-semibold text-slate-700">Record condition after rental</h2>
            <p class="mt-1 text-sm text-slate-500">Document the returned item's condition. Saving this record closes the rental once you confirm its return.</p>

            <form wire:submit="recordAfterCondition" class="mt-4 space-y-4">
                <div>
                    <x-input-label for="after_condition" value="Condition" />
                    <select wire:model="after_condition" id="after_condition" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach (\App\Enums\ListingCondition::cases() as $option)
                            <option value="{{ $option->value }}">{{ $option->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="after_notes" value="Notes (optional)" />
                    <textarea wire:model="after_notes" id="after_notes" rows="2" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                </div>
                <div>
                    <x-input-label value="Photos (optional)" />
                    <input type="file" wire:model="after_photos" multiple accept="image/*" class="mt-1 block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-blue-700 hover:file:bg-blue-100">
                    <x-input-error :messages="$errors->get('after_photos.*')" class="mt-2" />
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" wire:model.live="after_has_damage" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                    New damage found on this item
                </label>

                @if ($after_has_damage)
                    <div class="space-y-4 rounded-lg border border-rose-200 bg-rose-50 p-4">
                        <div>
                            <x-input-label for="damage_type" value="Damage type" />
                            <x-text-input wire:model="damage_type" id="damage_type" type="text" placeholder="e.g. Cracked casing" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('damage_type')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="damage_description" value="Description" />
                            <textarea wire:model="damage_description" id="damage_description" rows="2" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-rose-500 focus:ring-rose-500"></textarea>
                            <x-input-error :messages="$errors->get('damage_description')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="damage_estimated_cost" value="Estimated repair cost (₱)" />
                            <x-text-input wire:model="damage_estimated_cost" id="damage_estimated_cost" type="number" step="0.01" class="mt-1 block w-40" />
                            <x-input-error :messages="$errors->get('damage_estimated_cost')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label value="Damage photos" />
                            <input type="file" wire:model="damage_photos" multiple accept="image/*" class="mt-1 block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-white file:px-4 file:py-2 file:text-sm file:font-medium file:text-rose-700 hover:file:bg-rose-100">
                            <x-input-error :messages="$errors->get('damage_photos.*')" class="mt-2" />
                        </div>
                    </div>
                @endif

                <x-primary-button>Save condition record</x-primary-button>
            </form>
        </div>
    @endif

    @if ($rental->isCompleted())
        <div class="mt-6 rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="text-sm font-semibold text-slate-700">Security deposit</h2>
            <div class="mt-2 flex items-center gap-2">
                <x-badge :color="$rental->securityDeposit->status->badgeColor()">{{ $rental->securityDeposit->status->label() }}</x-badge>
                <span class="text-sm text-slate-500">₱{{ number_format($rental->securityDeposit->amount, 2) }} held</span>
            </div>

            @if ($rental->securityDeposit->status->value === 'return_eligible')
                <button type="button" wire:click="releaseDeposit" wire:confirm="Release the full deposit back to the renter?" class="mt-4 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                    Release deposit
                </button>
            @elseif ($rental->securityDeposit->status->value === 'deducted')
                <p class="mt-2 text-sm text-slate-600">
                    Deducted ₱{{ number_format($rental->securityDeposit->deducted_amount, 2) }} &middot;
                    Refunded to renter: ₱{{ number_format($rental->securityDeposit->amount - $rental->securityDeposit->deducted_amount, 2) }}
                </p>
            @elseif ($rental->securityDeposit->status->value === 'damage_claim')
                <p class="mt-2 text-sm text-slate-600">Waiting for the renter to accept or dispute the damage claim above.</p>
            @endif
        </div>

        <div class="mt-6 rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="text-sm font-semibold text-slate-700">Rate the renter</h2>
            @if ($rental->reviewFromOwnerToRenter)
                <p class="mt-2 text-sm text-slate-600">You rated {{ $rental->reviewFromOwnerToRenter->rating }}/5 stars</p>
                @if ($rental->reviewFromOwnerToRenter->comment)
                    <p class="mt-1 text-sm text-slate-500">"{{ $rental->reviewFromOwnerToRenter->comment }}"</p>
                @endif
            @else
                <form wire:submit="submitRenterReview" class="mt-3 space-y-3">
                    <select wire:model="renter_rating" class="block w-full max-w-xs rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @for ($i = 5; $i >= 1; $i--)
                            <option value="{{ $i }}">{{ $i }} star{{ $i === 1 ? '' : 's' }}</option>
                        @endfor
                    </select>
                    <textarea wire:model="renter_comment" rows="2" placeholder="Optional comment" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                    <x-primary-button>Submit rating</x-primary-button>
                </form>
            @endif
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

    @if ($rental->paid_at)
        @if ($rental->payment)
            <section class="mt-6 rounded-xl border border-slate-200 bg-white p-5">
                <h2 class="text-sm font-semibold text-slate-700">Transaction receipt</h2>
                <p class="mt-2 font-mono text-sm">{{ $rental->payment->transaction_reference }}</p>
                <p class="mt-1 text-sm text-slate-500">Paid {{ $rental->payment->paid_at->format('M d, Y g:i A') }}</p>
                <p class="mt-2 text-sm">Owner: {{ $rental->owner->name }} · Renter: {{ $rental->renter->name }}</p>
                <p class="mt-2 font-semibold">Payment total: ₱{{ number_format($rental->payment->amount, 2) }}</p>
            </section>
        @else
            <p class="mt-6 text-sm text-slate-500">No receipt is available for this payment.</p>
        @endif
        <h2 class="mt-6 text-sm font-semibold text-slate-700">Earnings breakdown</h2>
        <div class="mt-3 rounded-xl border border-slate-200 bg-white p-6">
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-slate-500">Rental fee (your earnings)</dt>
                    <dd class="font-medium text-slate-800">₱{{ number_format($rental->rental_fee, 2) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Security deposit (held, not your revenue)</dt>
                    <dd class="font-medium text-slate-800">₱{{ number_format($rental->security_deposit, 2) }}</dd>
                </div>
                @if ($rental->currentLateFee() > 0)
                    <div class="flex justify-between text-rose-600">
                        <dt>Late fee charged to renter</dt>
                        <dd class="font-medium">₱{{ number_format($rental->currentLateFee(), 2) }}</dd>
                    </div>
                @endif
            </dl>
        </div>
    @endif
</div>
