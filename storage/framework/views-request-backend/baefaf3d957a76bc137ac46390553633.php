<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['eyebrow' => null, 'title', 'subtitle' => null, 'maxWidth' => 'w-full']));

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

foreach (array_filter((['eyebrow' => null, 'title', 'subtitle' => null, 'maxWidth' => 'w-full']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<header class="sticky top-0 z-20 overflow-visible border-b border-slate-200/70 bg-white/90 shadow-[0_8px_30px_rgba(15,45,95,0.06)] backdrop-blur-xl">
    <div class="pointer-events-none absolute inset-0 overflow-hidden">
        <div class="absolute inset-0 bg-[linear-gradient(135deg,#ffffff_0%,#eff6ff_45%,#dbeafe_100%)]"></div>
        <div class="absolute -bottom-24 -right-12 h-56 w-80 rounded-full bg-[radial-gradient(circle,rgba(37,99,235,0.36)_0%,rgba(56,189,248,0.24)_38%,rgba(147,197,253,0.12)_56%,transparent_74%)] blur-2xl"></div>
        <div class="absolute right-[18%] top-0 h-full w-44 bg-[linear-gradient(115deg,transparent,rgba(255,255,255,0.72),rgba(96,165,250,0.12),transparent)] opacity-80"></div>
        <svg class="absolute right-8 top-1/2 h-20 w-44 -translate-y-1/2 text-blue-500 opacity-[0.08]" viewBox="0 0 176 80" fill="none" aria-hidden="true">
            <circle cx="8" cy="8" r="2" fill="currentColor"/><circle cx="32" cy="8" r="2" fill="currentColor"/><circle cx="56" cy="8" r="2" fill="currentColor"/><circle cx="80" cy="8" r="2" fill="currentColor"/><circle cx="104" cy="8" r="2" fill="currentColor"/><circle cx="128" cy="8" r="2" fill="currentColor"/><circle cx="152" cy="8" r="2" fill="currentColor"/>
            <circle cx="8" cy="32" r="2" fill="currentColor"/><circle cx="32" cy="32" r="2" fill="currentColor"/><circle cx="56" cy="32" r="2" fill="currentColor"/><circle cx="80" cy="32" r="2" fill="currentColor"/><circle cx="104" cy="32" r="2" fill="currentColor"/><circle cx="128" cy="32" r="2" fill="currentColor"/><circle cx="152" cy="32" r="2" fill="currentColor"/>
            <circle cx="8" cy="56" r="2" fill="currentColor"/><circle cx="32" cy="56" r="2" fill="currentColor"/><circle cx="56" cy="56" r="2" fill="currentColor"/><circle cx="80" cy="56" r="2" fill="currentColor"/><circle cx="104" cy="56" r="2" fill="currentColor"/><circle cx="128" cy="56" r="2" fill="currentColor"/><circle cx="152" cy="56" r="2" fill="currentColor"/>
        </svg>
    </div>

    <div <?php echo e($attributes->merge(['class' => 'relative mx-auto flex min-h-24 ' . $maxWidth . ' items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:min-h-28 lg:px-8'])); ?>>
        <div class="flex min-w-0 items-center gap-3 sm:gap-4">
            <button
                type="button"
                @click="$dispatch('toggle-sidebar')"
                class="group grid h-11 w-11 shrink-0 place-items-center rounded-2xl border border-blue-100 bg-white text-blue-600 shadow-[0_7px_20px_rgba(37,99,235,0.12)] transition duration-200 hover:-translate-y-0.5 hover:border-blue-200 hover:bg-blue-50 hover:shadow-[0_10px_24px_rgba(37,99,235,0.18)] focus:outline-none focus:ring-4 focus:ring-blue-100 lg:hidden"
                aria-label="Open navigation">
                <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'menu','class' => 'h-5 w-5 transition-transform duration-200 group-hover:scale-110']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'menu','class' => 'h-5 w-5 transition-transform duration-200 group-hover:scale-110']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
            </button>

            <div class="min-w-0">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($eyebrow): ?>
                    <p class="mb-1.5 inline-flex items-center gap-2 rounded-full bg-blue-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.16em] text-blue-700 ring-1 ring-blue-100">
                        <span class="h-1.5 w-1.5 rounded-full bg-blue-600 shadow-[0_0_8px_rgba(37,99,235,0.65)]"></span>
                        <?php echo e($eyebrow); ?>

                    </p>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <h1 class="truncate text-xl font-extrabold tracking-[-0.035em] text-[#071a3d] sm:text-2xl lg:text-[1.7rem]"><?php echo e($title); ?></h1>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($subtitle): ?>
                    <p class="mt-1 max-w-3xl truncate text-xs leading-5 text-slate-500 sm:text-sm"><?php echo e($subtitle); ?></p>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->guard()->check()): ?>
            <div class="relative flex shrink-0 items-center gap-3">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! auth()->user()->isAdmin() && auth()->user()->activeInterface() === 'renter'): ?>
                    <?php ($cartCount = auth()->user()->cartItems()->count()); ?>
                    <a href="<?php echo e(route('renter.cart.index')); ?>" wire:navigate aria-label="My cart, <?php echo e($cartCount); ?> items" class="relative grid h-11 w-11 place-items-center rounded-xl border border-blue-100 bg-white text-blue-700 hover:bg-blue-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                        <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'cart','class' => 'h-5 w-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'cart','class' => 'h-5 w-5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($cartCount): ?>
                            <span class="absolute -right-1 -top-1 rounded-full bg-blue-600 px-1.5 py-0.5 text-[10px] font-bold text-white"><?php echo e($cartCount); ?></span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </a>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('notifications.bell', []);

$__key = null;

$__key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-4093374461-0', $__key);

$__html = app('livewire')->mount($__name, $__params, $__key);

echo $__html;

unset($__html);
unset($__key);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>
</header>
<?php /**PATH C:\laragon\www\lendly\resources\views/components/page-header.blade.php ENDPATH**/ ?>