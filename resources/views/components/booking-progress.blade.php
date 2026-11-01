@props(['rental', 'owner' => false, 'reviewer' => false])

<section class="my-5 space-y-3 rounded-xl border border-slate-200 bg-white p-5" aria-label="Booking progress">
    <p class="text-sm font-semibold text-slate-800">{{ $rental->periodLabel() }}</p>
    <p class="text-sm text-slate-600">{{ $rental->rental_unit === 'hour' ? $rental->rental_hours.' hours' : $rental->rental_days.' days' }} · Preparation: {{ ucfirst(str_replace('_', ' ', $rental->fulfillment_stage ?? 'reserved')) }}</p>
    @if ($rental->isPaymentPending() && $rental->payment_due_at)
        <p class="text-sm text-amber-800">Pay or submit proof by {{ $rental->payment_due_at->copy()->setTimezone('Asia/Manila')->format('M d, Y g:i A') }} (Philippine time). Unpaid reservations expire without a fee. Pending proof keeps the reservation during review.</p>
    @endif
    @if (($rental->rentalRequest?->agreement_terms['cancellation_requires_review'] ?? false) && in_array($rental->fulfillment_stage, ['waiting_for_courier', 'shipped'], true))
        <p class="text-sm text-slate-600">Ordinary cancellation is closed because delivery preparation has reached {{ str_replace('_', ' ', $rental->fulfillment_stage) }}. Contact support or use the dispute process for a problem.</p>
    @endif
    @php($cancellation = $rental->cancellationRequest)
    @if ($cancellation)
        <div class="rounded-lg bg-amber-50 p-4 text-sm text-amber-900" role="status">
            <p class="font-semibold">Cancellation request: {{ $cancellation->status }}</p>
            <p class="mt-1 whitespace-pre-line">{{ $cancellation->reason }}</p>
            <p class="mt-1">Cancellation fee if approved: ₱{{ number_format($cancellation->fee, 2) }}.</p>
            @if ($cancellation->status === 'pending')<p class="mt-1">The reservation remains in place. Packing, courier updates and handover are paused until review.</p>@endif
            @if ($cancellation->review_notes)<p class="mt-1 whitespace-pre-line">Review: {{ $cancellation->review_notes }}</p>@endif
        </div>
        @if ($reviewer && $cancellation->status === 'pending')
            <form wire:submit="reviewCancellation(true)" class="space-y-3">
                <x-input-label required for="cancellation-review-notes" value="Cancellation review notes" />
                <textarea id="cancellation-review-notes" wire:model="cancellation_review_notes" required maxlength="1000" rows="3" class="w-full rounded-lg border-slate-300"></textarea>
                <x-input-error :messages="$errors->get('review_notes')" />
                <div class="flex flex-wrap gap-3">
                    <x-primary-button wire:loading.attr="disabled">Approve cancellation</x-primary-button>
                    <x-secondary-button type="button" wire:click="reviewCancellation(false)" wire:loading.attr="disabled">Decline cancellation</x-secondary-button>
                </div>
            </form>
        @endif
    @endif
    @if ($owner && $rental->isPaid() && $rental->isCancellableByRenter() && $cancellation?->status !== 'pending')
        <x-input-error :messages="$errors->get('fulfillment')" />
        @if ($rental->fulfillment_stage === 'reserved')
            <x-secondary-button type="button" wire:click="advanceFulfillment('packed')" wire:loading.attr="disabled">Mark as packed</x-secondary-button>
        @elseif ($rental->fulfillment_stage === 'packed' && $rental->fulfillment_method->value === 'delivery')
            <x-secondary-button type="button" wire:click="advanceFulfillment('waiting_for_courier')" wire:confirm="{{ ($rental->rentalRequest?->agreement_terms['cancellation_requires_review'] ?? false) ? 'Cancellation will close at this stage. Is the item waiting for courier collection?' : 'Is the item waiting for courier collection? Your saved cancellation policy remains in effect.' }}" wire:loading.attr="disabled">Waiting for courier</x-secondary-button>
        @endif
    @endif
    @if ($owner && $rental->isPaid() && $rental->fulfillment_stage === 'waiting_for_courier')
        <x-secondary-button type="button" wire:click="advanceFulfillment('shipped')" wire:loading.attr="disabled">Confirm shipped</x-secondary-button>
    @endif
    @if ($rental->isCancelled() && $rental->cancellation_refund_amount > 0)
        <p class="text-sm text-slate-700">Refund to arrange: <strong>₱{{ number_format($rental->cancellation_refund_amount, 2) }}</strong>, including the processing fee and deposit, less the cancellation fee. This record does not send money; confirm the actual refund with the payment recipient.</p>
    @endif
</section>
