<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['color' => 'slate']));

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

foreach (array_filter((['color' => 'slate']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $colors = [
        'slate' => 'bg-slate-100 text-slate-700',
        'teal' => 'bg-blue-100 text-blue-700',
        'green' => 'bg-emerald-100 text-emerald-700',
        'amber' => 'bg-amber-100 text-amber-700',
        'red' => 'bg-rose-100 text-rose-700',
    ];
?>

<span <?php echo e($attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ' . ($colors[$color] ?? $colors['slate'])])); ?>>
    <?php echo e($slot); ?>

</span>
<?php /**PATH C:\laragon\www\lendly\resources\views/components/badge.blade.php ENDPATH**/ ?>