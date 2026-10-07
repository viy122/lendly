<div class="rental-detail-page min-h-screen bg-[radial-gradient(circle_at_92%_8%,rgba(191,219,254,0.32),transparent_28%),#f8fafc]">
    <x-page-header
        eyebrow="Owning · My rentals"
        title="Manage rental"
        :subtitle="'Rental #' . $rental->id . ' · ' . $rental->listing->name"
        maxWidth="max-w-6xl"
    />

    <div class="mx-auto max-w-6xl px-4 py-7 sm:px-6 lg:px-8 lg:py-10">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('owner.rentals.index') }}" wire:navigate class="group inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3.5 py-2 text-sm font-semibold text-slate-600 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:text-blue-700 hover:shadow-md">
                <x-icon name="chevron-down" class="h-3.5 w-3.5 rotate-90 transition-transform group-hover:-translate-x-0.5" />
                Back to my rentals
            </a>
            <span class="rounded-full bg-blue-50 px-3 py-1.5 text-[11px] font-bold uppercase tracking-[0.14em] text-blue-700 ring-1 ring-blue-100">Rental #{{ $rental->id }}</span>
        </div>

        <section class="relative overflow-hidden rounded-[2rem] border border-blue-100 bg-white shadow-[0_22px_65px_rgba(15,45,95,0.11)]">
            <div class="pointer-events-none absolute -right-24 -top-28 h-72 w-72 rounded-full bg-[radial-gradient(circle,rgba(59,130,246,0.20),rgba(125,211,252,0.09)_45%,transparent_72%)] blur-2xl"></div>
            <div class="relative grid gap-0 md:grid-cols-[15rem_minmax(0,1fr)]">
                <div class="relative min-h-56 overflow-hidden bg-slate-100 md:min-h-full">
                    @if ($rental->listing->images->first())
                        <img src="{{ $rental->listing->images->first()->url() }}" alt="{{ $rental->listing->name }}" class="absolute inset-0 h-full w-full object-cover transition duration-500 hover:scale-105" />
                    @else
                        <div class="absolute inset-0 flex flex-col items-center justify-center bg-gradient-to-br from-blue-50 to-slate-100 text-slate-400">
                            <x-icon name="archive" class="h-9 w-9 text-blue-300" />
                            <span class="mt-2 text-xs font-medium">No item photo</span>
                        </div>
                    @endif
                    <div class="absolute inset-x-0 bottom-0 h-20 bg-gradient-to-t from-blue-950/45 to-transparent md:hidden"></div>
                </div>

                <div class="p-5 sm:p-7 lg:p-8">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-blue-600">Your listed item</p>
                            <div class="mt-2 flex flex-wrap items-center gap-3">
                                <h1 class="text-2xl font-extrabold tracking-[-0.035em] text-[#071a3d] sm:text-3xl">{{ $rental->listing->name }}</h1>
                                <x-badge :color="$rental->displayStatusColor()">{{ $rental->displayStatusLabel() }}</x-badge>
                            </div>
                            <p class="mt-2 flex items-center gap-1.5 text-sm text-slate-500">
                                <x-icon name="user-circle" class="h-4 w-4 text-slate-400" />
                                Rented by <span class="font-semibold text-slate-700">{{ $rental->renter->name }}</span>
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('listings.show', $rental->listing) }}" wire:navigate class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-bold text-slate-600 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">
                                View listing <span aria-hidden="true">↗</span>
                            </a>
                            <a href="{{ route('rental-requests.chat', $rental->rental_request_id) }}" wire:navigate class="inline-flex items-center gap-2 rounded-xl bg-[#075cf5] px-3.5 py-2.5 text-xs font-bold text-white shadow-[0_8px_20px_rgba(7,92,245,0.24)] transition hover:-translate-y-0.5 hover:bg-blue-700">
                                <x-icon name="chat" class="h-4 w-4" /> Message renter
                            </a>
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/80 p-3.5">
                            <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">Rental dates</p>
                            <p class="mt-1.5 text-sm font-bold text-slate-800">{{ $rental->start_date->format('M d') }} – {{ $rental->end_date->format('M d, Y') }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/80 p-3.5">
                            <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">Duration</p>
                            <p class="mt-1.5 text-sm font-bold text-slate-800">{{ $rental->rental_days }} day{{ (int) $rental->rental_days === 1 ? '' : 's' }}</p>
                        </div>
                        <div class="rounded-2xl border border-blue-100 bg-blue-50/80 p-3.5">
                            <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-blue-500">Your earnings</p>
                            <p class="mt-1.5 text-base font-extrabold text-blue-700">₱{{ number_format($rental->rental_fee, 2) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="mx-auto max-w-4xl">

    @if ($rental->isPaymentPending())
        <div class="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-700">
            Waiting for the renter to complete simulated payment.
        </div>
    @endif

    @if ($rental->isOverdue())
        <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
            <p class="font-semibold">This rental is overdue by {{ $rental->days_overdue }} day{{ $rental->days_overdue === 1 ? '' : 's' }}.</p>
            <p class="mt-1">Late fee so far: ₱{{ number_format($rental->late_fee, 2) }}</p>
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
            <p class="mt-1 text-sm text-slate-500">Both you and the renter need to confirm the item was returned.</p>
            <ul class="mt-3 space-y-1 text-sm">
                <li class="{{ $rental->return_confirmed_by_owner_at ? 'text-blue-700' : 'text-slate-400' }}">
                    {{ $rental->return_confirmed_by_owner_at ? '✓ You confirmed' : 'Waiting on your confirmation' }}
                </li>
                <li class="{{ $rental->return_confirmed_by_renter_at ? 'text-blue-700' : 'text-slate-400' }}">
                    {{ $rental->return_confirmed_by_renter_at ? '✓ Renter confirmed' : 'Waiting on renter confirmation' }}
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
    @elseif ($rental->isReturned())
        <div class="mt-6 rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="text-sm font-semibold text-slate-700">Record condition after rental</h2>
            <p class="mt-1 text-sm text-slate-500">Document the item's condition now that it's been returned.</p>

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
                        </div>
                    </div>
                @endif

                <x-primary-button>Save condition record</x-primary-button>
            </form>
        </div>
    @endif

    @if ($rental->isReturned() && $rental->afterConditionRecord)
        <div class="mt-6 rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="text-sm font-semibold text-slate-700">Complete inspection</h2>
            <p class="mt-1 text-sm text-slate-500">
                Close out this rental. If damage was reported above, the deposit will be marked as a damage claim awaiting the renter's response.
            </p>
            <button type="button" wire:click="completeInspection" wire:confirm="Mark this rental as completed?" class="mt-4 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                Complete inspection
            </button>
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
                @if ($rental->late_fee > 0)
                    <div class="flex justify-between text-rose-600">
                        <dt>Late fee charged to renter</dt>
                        <dd class="font-medium">₱{{ number_format($rental->late_fee, 2) }}</dd>
                    </div>
                @endif
            </dl>
        </div>
    @endif
        </div>
    </div>
</div>
