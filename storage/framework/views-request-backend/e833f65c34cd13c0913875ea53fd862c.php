<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['record']));

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

foreach (array_filter((['record']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div class="mt-3 space-y-1 text-sm text-slate-600">
    <p class="whitespace-pre-line break-words">Reason: <?php echo e($record->cancellation_reason ?? 'No reason provided.'); ?></p>
    <p>Cancellation fee: <?php echo e($record->cancellation_fee !== null ? '₱'.number_format($record->cancellation_fee, 2) : 'Not recorded'); ?></p>
    <p class="text-xs text-slate-500">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($record->cancelled_at): ?>
            Cancelled <?php echo e($record->cancelled_at->format('M d, Y \a\t g:i A')); ?>

        <?php else: ?>
            Cancellation time not recorded.
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </p>
</div>
<?php /**PATH C:\laragon\www\lendly\resources\views/components/cancellation-details.blade.php ENDPATH**/ ?>