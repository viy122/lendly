<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['align' => 'right', 'width' => '48', 'contentClasses' => 'py-1 bg-white']));

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

foreach (array_filter((['align' => 'right', 'width' => '48', 'contentClasses' => 'py-1 bg-white']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
$alignmentClasses = match ($align) {
    'left' => 'ltr:origin-top-left rtl:origin-top-right start-0',
    'top' => 'origin-bottom-left bottom-full start-0 mb-2',
    default => 'ltr:origin-top-right rtl:origin-top-left end-0',
};

$width = match ($width) {
    '48' => 'w-48',
    '64' => 'w-64',
    default => $width,
};

$spacingClasses = $align === 'top' ? '' : 'mt-2';
?>

<div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.stop="open = false" @close.stop="open = false">
    <div @click="open = ! open">
        <?php echo e($trigger); ?>

    </div>

    <div x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="absolute z-50 <?php echo e($spacingClasses); ?> <?php echo e($width); ?> rounded-xl shadow-lg <?php echo e($alignmentClasses); ?>"
            style="display: none;"
            @click="open = false">
        <div class="rounded-xl border border-slate-200 <?php echo e($contentClasses); ?>">
            <?php echo e($content); ?>

        </div>
    </div>
</div>
<?php /**PATH C:\laragon\www\lendly\resources\views/components/dropdown.blade.php ENDPATH**/ ?>