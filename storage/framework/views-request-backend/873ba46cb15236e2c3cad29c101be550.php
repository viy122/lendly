<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

        <title><?php echo e(config('app.name', 'Lendly')); ?></title>
        <link rel="icon" type="image/png" href="<?php echo e(asset('images/lendly-icon.png')); ?>">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    </head>
    <body class="bg-slate-50 font-sans text-slate-900 antialiased">
        <div x-data class="min-h-screen lg:flex">
            <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('layout.sidebar', []);

$__key = null;

$__key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-3882822994-0', $__key);

$__html = app('livewire')->mount($__name, $__params, $__key);

echo $__html;

unset($__html);
unset($__key);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>

            <div class="min-w-0 flex-1">
                <main>
                    <?php if (isset($component)) { $__componentOriginal1639eed67d073c26b018b7c103ffe3cc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1639eed67d073c26b018b7c103ffe3cc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.email-verification-reminder','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('email-verification-reminder'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1639eed67d073c26b018b7c103ffe3cc)): ?>
<?php $attributes = $__attributesOriginal1639eed67d073c26b018b7c103ffe3cc; ?>
<?php unset($__attributesOriginal1639eed67d073c26b018b7c103ffe3cc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1639eed67d073c26b018b7c103ffe3cc)): ?>
<?php $component = $__componentOriginal1639eed67d073c26b018b7c103ffe3cc; ?>
<?php unset($__componentOriginal1639eed67d073c26b018b7c103ffe3cc); ?>
<?php endif; ?>
                    <?php echo e($slot); ?>

                </main>
            </div>
        </div>
    </body>
</html>
<?php /**PATH C:\laragon\www\lendly\resources\views/layouts/marketplace.blade.php ENDPATH**/ ?>