@props(['terms', 'rentalRequest' => null])

<div class="rental-agreement-preview">
    <article class="rental-agreement-paper" aria-label="Rental agreement document">
        <header class="rental-agreement-heading">
            <p class="rental-agreement-brand">{{ config('app.name', 'Lendly') }}</p>
            <h2>Rental terms &amp; agreement</h2>
            <p class="rental-agreement-subtitle">{{ $terms['item_name'] }}</p>
            @if ($rentalRequest)
                <p class="rental-agreement-reference">Rental request #{{ $rentalRequest->id }}</p>
            @endif
        </header>

        <div class="rental-agreement-body">
            @if ($rentalRequest)
                <div class="rental-agreement-parties">
                    <p><strong>Owner:</strong> {{ $rentalRequest->listing->owner->name }}</p>
                    <p><strong>Renter:</strong> {{ $rentalRequest->renter->name }}</p>
                </div>
            @endif

            <section>
                <h3><span>01.</span> Rental particulars</h3>
                <p>
                    <strong>{{ $terms['item_name'] }}</strong>:
                    @if (($terms['rental_unit'] ?? 'day') === 'hour')
                        {{ \Carbon\Carbon::parse($terms['starts_at'])->setTimezone('Asia/Manila')->format('M d, Y g:i A') }} &ndash; {{ \Carbon\Carbon::parse($terms['ends_at'])->setTimezone('Asia/Manila')->format('M d, Y g:i A') }}
                        ({{ $terms['rental_hours'] }} hours, Philippine time),
                    @else
                        {{ $terms['start_date'] }} &ndash; {{ $terms['end_date'] }} ({{ $terms['rental_days'] }} days),
                    @endif
                    {{ ucfirst($terms['fulfillment_method']) }}.
                </p>
            </section>

            <section>
                <h3><span>02.</span> Fees and payment</h3>
                <dl class="rental-agreement-charges">
                    <dt>Rental fee</dt><dd>₱{{ number_format($terms['rental_fee'], 2) }}</dd>
                    <dt>Platform fee (processing · {{ $terms['commission_rate'] }}%)</dt><dd>₱{{ number_format($terms['commission_amount'], 2) }}</dd>
                    <dt>Refundable security deposit</dt><dd>₱{{ number_format($terms['security_deposit'], 2) }}</dd>
                    <dt class="rental-agreement-total">Total due</dt><dd class="rental-agreement-total">₱{{ number_format($terms['total_amount'], 2) }}</dd>
                </dl>
            </section>

            <section>
                <h3><span>03.</span> Cancellation policy and fees</h3>
                @if ($terms['cancellation_requires_review'] ?? false)
                <p>
                    An unpacked booking can be cancelled without a fee at least {{ $terms['cancellation_window_hours'] }} hours before its start reference.
                    Within that window or once packed, submit a reason for owner or administrator review. If approved, the cancellation fee is {{ $terms['cancellation_fee_percentage'] }}% of the rental fee
                    (₱{{ number_format($terms['rental_fee'] * $terms['cancellation_fee_percentage'] / 100, 2) }}), charged once. The processing fee and deposit are excluded from its calculation.
                    The reservation remains in place during review. Ordinary cancellation closes at Waiting for courier, Shipped, or either party’s handover confirmation.
                </p>
                <p>Start reference: {{ \Carbon\Carbon::parse($terms['cancellation_reference_at'])->setTimezone('Asia/Manila')->format('M d, Y g:i A') }} (Philippine time), replaced by a pickup/delivery schedule only after both parties confirm it.</p>
                <p>For a paid cancellation, arrange a manual refund of the total paid, including processing fee and deposit, less the cancellation fee. A status update does not transfer funds.</p>
                @else
                <p>
                    The renter may cancel before item hand-over. Cancellation at least {{ $terms['cancellation_window_hours'] }} hours
                    before the rental start date is free. Cancellation less than {{ $terms['cancellation_window_hours'] }} hours before
                    the start date incurs {{ $terms['cancellation_fee_percentage'] }}% of the rental fee
                    (₱{{ number_format($terms['rental_fee'] * $terms['cancellation_fee_percentage'] / 100, 2) }}).
                    The fee is calculated from the rental fee, excluding the platform fee and security deposit.
                </p>
                @endif
            </section>

            <section>
                <h3><span>04.</span> Item rules</h3>
                <p class="whitespace-pre-line">{{ $terms['rental_rules'] ?: 'No additional item-specific rules.' }}</p>
            </section>

            <section>
                <h3><span>05.</span> Return, damages and security deposit</h3>
                <p>
                    The renter must return the item by the agreed end date. The deposit is held until return and inspection.
                    If damage is reported, an amount up to the deposit may be deducted. The renter may accept or dispute the claim.
                </p>
            </section>

            @foreach ($terms['clauses'] ?? [] as $clause)
                <section>
                    <h3><span>{{ str_pad($loop->iteration + 5, 2, '0', STR_PAD_LEFT) }}.</span> {{ $clause['title'] }}</h3>
                    @foreach ($clause['paragraphs'] as $paragraph)
                        <p>{{ $paragraph }}</p>
                    @endforeach
                </section>
            @endforeach

            <section>
                <h3><span>{{ str_pad(count($terms['clauses'] ?? []) + 6, 2, '0', STR_PAD_LEFT) }}.</span> Acceptance and confirmation</h3>
                <p>The booking is finalized only when both the owner and renter have accepted this agreement. Payment follows confirmation.</p>
                @if (isset($terms['payment_window_hours']))<p>Submit payment proof within {{ $terms['payment_window_hours'] }} hours of confirmation or before the rental start reference, whichever comes first. An unpaid reservation expires without a fee; pending proof pauses expiry during review.</p>@endif
            </section>

            @if ($rentalRequest)
                <section>
                    <h3>Recorded acceptance</h3>
                    <div class="rental-agreement-acceptances">
                        <div>
                            <h4>Owner acceptance</h4>
                            <p>{{ $rentalRequest->listing->owner->name }}</p>
                            <p class="rental-agreement-acceptance-status">
                                @if ($rentalRequest->owner_terms_accepted_at)
                                    Accepted on {{ $rentalRequest->owner_terms_accepted_at->setTimezone('Asia/Manila')->format('M d, Y · g:i A') }} (Philippine time)
                                @else
                                    Awaiting acceptance
                                @endif
                            </p>
                        </div>
                        <div>
                            <h4>Renter acceptance</h4>
                            <p>{{ $rentalRequest->renter->name }}</p>
                            <p class="rental-agreement-acceptance-status">
                                @if ($rentalRequest->renter_terms_accepted_at)
                                    Accepted on {{ $rentalRequest->renter_terms_accepted_at->setTimezone('Asia/Manila')->format('M d, Y · g:i A') }} (Philippine time)
                                @else
                                    Awaiting acceptance
                                @endif
                            </p>
                        </div>
                    </div>
                </section>
            @endif
        </div>

        <footer class="rental-agreement-footer">End of agreement</footer>
    </article>
</div>
