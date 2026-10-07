@props(['terms'])

<div class="space-y-4 text-sm text-slate-700">
    <h2 class="font-semibold text-slate-900">Rental terms &amp; agreement</h2>
    <p>
        <span class="font-medium">{{ $terms['item_name'] }}</span>:
        {{ $terms['start_date'] }} &ndash; {{ $terms['end_date'] }}
        ({{ $terms['rental_days'] }} days), {{ ucfirst($terms['fulfillment_method']) }}.
    </p>
    <dl class="grid grid-cols-2 gap-2">
        <dt>Rental fee</dt><dd class="text-right">₱{{ number_format($terms['rental_fee'], 2) }}</dd>
        <dt>Platform fee ({{ $terms['commission_rate'] }}%)</dt><dd class="text-right">₱{{ number_format($terms['commission_amount'], 2) }}</dd>
        <dt>Refundable security deposit</dt><dd class="text-right">₱{{ number_format($terms['security_deposit'], 2) }}</dd>
        <dt class="font-semibold">Total due</dt><dd class="text-right font-semibold">₱{{ number_format($terms['total_amount'], 2) }}</dd>
    </dl>
    <div>
        <h3 class="font-medium text-slate-900">Cancellation policy and fees</h3>
        <p class="mt-1">
            The renter may cancel before item hand-over. Cancellation at least {{ $terms['cancellation_window_hours'] }} hours
            before the rental start date is free. Cancellation less than {{ $terms['cancellation_window_hours'] }} hours before
            the start date incurs {{ $terms['cancellation_fee_percentage'] }}% of the rental fee
            (₱{{ number_format($terms['rental_fee'] * $terms['cancellation_fee_percentage'] / 100, 2) }}).
            The fee is calculated from the rental fee, excluding the platform fee and security deposit.
        </p>
    </div>
    <div>
        <h3 class="font-medium text-slate-900">Item rules</h3>
        <p class="mt-1 whitespace-pre-line">{{ $terms['rental_rules'] ?: 'No additional item-specific rules.' }}</p>
    </div>
    <div>
        <h3 class="font-medium text-slate-900">Return, damages and security deposit</h3>
        <p class="mt-1">
            The renter must return the item by the agreed end date. The deposit is held until return and inspection.
            If damage is reported, an amount up to the deposit may be deducted. The renter may accept or dispute the claim.
        </p>
    </div>
    <p class="font-medium">The booking is finalized only when both the owner and renter have accepted this agreement. Payment follows confirmation.</p>
</div>
