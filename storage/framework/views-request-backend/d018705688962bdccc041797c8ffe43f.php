<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['terms', 'rentalRequest' => null]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['terms', 'rentalRequest' => null]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div class="rental-agreement-preview">
    <article class="rental-agreement-paper" aria-label="Rental agreement document">
        <header class="rental-agreement-heading">
            <p class="rental-agreement-brand"><?php echo e(config('app.name', 'Lendly')); ?></p>
            <h2>Rental terms &amp; agreement</h2>
            <p class="rental-agreement-subtitle"><?php echo e($terms['item_name']); ?></p>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rentalRequest): ?>
                <p class="rental-agreement-reference">Rental request #<?php echo e($rentalRequest->id); ?></p>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </header>

        <div class="rental-agreement-body">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rentalRequest): ?>
                <div class="rental-agreement-parties">
                    <p><strong>Owner:</strong> <?php echo e($rentalRequest->listing->owner->name); ?></p>
                    <p><strong>Renter:</strong> <?php echo e($rentalRequest->renter->name); ?></p>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <section>
                <h3><span>01.</span> Rental particulars</h3>
                <p>
                    <strong><?php echo e($terms['item_name']); ?></strong>:
                    <?php echo e($terms['start_date']); ?> &ndash; <?php echo e($terms['end_date']); ?>

                    (<?php echo e($terms['rental_days']); ?> days), <?php echo e(ucfirst($terms['fulfillment_method'])); ?>.
                </p>
            </section>

            <section>
                <h3><span>02.</span> Fees and payment</h3>
                <dl class="rental-agreement-charges">
                    <dt>Rental fee</dt><dd>₱<?php echo e(number_format($terms['rental_fee'], 2)); ?></dd>
                    <dt>Platform fee (<?php echo e($terms['commission_rate']); ?>%)</dt><dd>₱<?php echo e(number_format($terms['commission_amount'], 2)); ?></dd>
                    <dt>Refundable security deposit</dt><dd>₱<?php echo e(number_format($terms['security_deposit'], 2)); ?></dd>
                    <dt class="rental-agreement-total">Total due</dt><dd class="rental-agreement-total">₱<?php echo e(number_format($terms['total_amount'], 2)); ?></dd>
                </dl>
            </section>

            <section>
                <h3><span>03.</span> Cancellation policy and fees</h3>
                <p>
                    The renter may cancel before item hand-over. Cancellation at least <?php echo e($terms['cancellation_window_hours']); ?> hours
                    before the rental start date is free. Cancellation less than <?php echo e($terms['cancellation_window_hours']); ?> hours before
                    the start date incurs <?php echo e($terms['cancellation_fee_percentage']); ?>% of the rental fee
                    (₱<?php echo e(number_format($terms['rental_fee'] * $terms['cancellation_fee_percentage'] / 100, 2)); ?>).
                    The fee is calculated from the rental fee, excluding the platform fee and security deposit.
                </p>
            </section>

            <section>
                <h3><span>04.</span> Item rules</h3>
                <p class="whitespace-pre-line"><?php echo e($terms['rental_rules'] ?: 'No additional item-specific rules.'); ?></p>
            </section>

            <section>
                <h3><span>05.</span> Return, damages and security deposit</h3>
                <p>
                    The renter must return the item by the agreed end date. The deposit is held until return and inspection.
                    If damage is reported, an amount up to the deposit may be deducted. The renter may accept or dispute the claim.
                </p>
            </section>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $terms['clauses'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $clause): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <section>
                    <h3><span><?php echo e(str_pad($loop->iteration + 5, 2, '0', STR_PAD_LEFT)); ?>.</span> <?php echo e($clause['title']); ?></h3>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $clause['paragraphs']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $paragraph): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <p><?php echo e($paragraph); ?></p>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </section>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <section>
                <h3><span><?php echo e(str_pad(count($terms['clauses'] ?? []) + 6, 2, '0', STR_PAD_LEFT)); ?>.</span> Acceptance and confirmation</h3>
                <p>The booking is finalized only when both the owner and renter have accepted this agreement. Payment follows confirmation.</p>
            </section>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rentalRequest): ?>
                <section>
                    <h3>Recorded acceptance</h3>
                    <div class="rental-agreement-acceptances">
                        <div>
                            <h4>Owner acceptance</h4>
                            <p><?php echo e($rentalRequest->listing->owner->name); ?></p>
                            <p class="rental-agreement-acceptance-status">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rentalRequest->owner_terms_accepted_at): ?>
                                    Accepted on <?php echo e($rentalRequest->owner_terms_accepted_at->setTimezone('Asia/Manila')->format('M d, Y · g:i A')); ?> (Philippine time)
                                <?php else: ?>
                                    Awaiting acceptance
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </p>
                        </div>
                        <div>
                            <h4>Renter acceptance</h4>
                            <p><?php echo e($rentalRequest->renter->name); ?></p>
                            <p class="rental-agreement-acceptance-status">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rentalRequest->renter_terms_accepted_at): ?>
                                    Accepted on <?php echo e($rentalRequest->renter_terms_accepted_at->setTimezone('Asia/Manila')->format('M d, Y · g:i A')); ?> (Philippine time)
                                <?php else: ?>
                                    Awaiting acceptance
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </p>
                        </div>
                    </div>
                </section>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>

        <footer class="rental-agreement-footer">End of agreement</footer>
    </article>
</div>
<?php /**PATH C:\laragon\www\lendly\resources\views/components/rental-agreement.blade.php ENDPATH**/ ?>