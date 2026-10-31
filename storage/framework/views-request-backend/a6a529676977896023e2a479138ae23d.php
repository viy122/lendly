<div class="renter-rental-detail rental-detail-page min-h-screen bg-slate-50" wire:poll.5s.keep-alive="refreshRental">
    <?php if (isset($component)) { $__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.page-header','data' => ['eyebrow' => 'Renting · My rentals','title' => 'Rental details','subtitle' => 'Rental #' . $rental->id . ' · ' . $rental->listing->name]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['eyebrow' => 'Renting · My rentals','title' => 'Rental details','subtitle' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Rental #' . $rental->id . ' · ' . $rental->listing->name)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e)): ?>
<?php $attributes = $__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e; ?>
<?php unset($__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e)): ?>
<?php $component = $__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e; ?>
<?php unset($__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e); ?>
<?php endif; ?>

    <div class="rental-detail-content w-full px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('status')): ?><p role="status" class="mb-5 rounded-xl bg-green-50 p-4 text-sm text-green-800"><?php echo e(session('status')); ?></p><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <a href="<?php echo e(route('renter.rentals.index')); ?>" wire:navigate class="inline-flex min-h-10 items-center gap-2 rounded-lg text-sm font-semibold text-slate-600 transition hover:text-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-100">
                <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'chevron-down','class' => 'h-4 w-4 rotate-90','ariaHidden' => 'true']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'chevron-down','class' => 'h-4 w-4 rotate-90','aria-hidden' => 'true']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?> Back to my rentals
            </a>
            <span class="text-xs font-medium text-slate-500">Rental #<?php echo e($rental->id); ?></span>
        </div>

        <div class="rental-detail-grid">
            <aside class="renter-rental-sidebar" aria-label="Rented item and payment receipt">
                <section class="renter-rental-item rental-panel overflow-hidden rounded-2xl border border-slate-200 bg-white">
                    <?php $itemPhotos = $rental->listing->images; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($itemPhotos->isNotEmpty()): ?>
                        <div x-data="{ selectedPhoto: <?php echo \Illuminate\Support\Js::from($itemPhotos->first()->url())->toHtml() ?> }">
                            <div class="aspect-square max-h-[30rem] bg-slate-100 p-4">
                                <img src="<?php echo e($itemPhotos->first()->url()); ?>" :src="selectedPhoto" alt="<?php echo e($rental->listing->name); ?>" class="h-full w-full object-contain" />
                            </div>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($itemPhotos->count() > 1): ?>
                                <div class="flex flex-wrap gap-2 border-b border-slate-100 p-4" aria-label="Item photos">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $itemPhotos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $image): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <button type="button" @click="selectedPhoto = <?php echo \Illuminate\Support\Js::from($image->url())->toHtml() ?>" :aria-pressed="selectedPhoto === <?php echo \Illuminate\Support\Js::from($image->url())->toHtml() ?>" :class="selectedPhoto === <?php echo \Illuminate\Support\Js::from($image->url())->toHtml() ?> ? 'border-blue-500 ring-2 ring-blue-100' : 'border-slate-200'" aria-label="View item photo <?php echo e($loop->iteration); ?>" class="h-16 w-16 shrink-0 overflow-hidden rounded-xl border-2 bg-slate-50 p-1 transition hover:border-blue-300 focus:outline-none focus:ring-4 focus:ring-blue-100">
                                            <img src="<?php echo e($image->url()); ?>" alt="<?php echo e($rental->listing->name); ?> photo <?php echo e($loop->iteration); ?>" class="h-full w-full object-contain" loading="lazy" />
                                        </button>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="flex aspect-square max-h-[30rem] flex-col items-center justify-center gap-3 bg-slate-100 text-slate-500">
                            <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'archive','class' => 'h-12 w-12 text-slate-400','ariaHidden' => 'true']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'archive','class' => 'h-12 w-12 text-slate-400','aria-hidden' => 'true']); ?>
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
                            <p class="text-sm">No item photo</p>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <div class="p-5 sm:p-6">
                        <p class="text-xs font-semibold uppercase tracking-wider text-blue-600">Your rented item</p>
                        <h2 class="mt-2 break-words text-xl font-bold tracking-tight text-slate-900 sm:text-2xl"><?php echo e($rental->listing->name); ?></h2>
                        <div class="mt-3"><?php if (isset($component)) { $__componentOriginald2d7ec366e64c2b13b8c4aa5d881be7e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald2d7ec366e64c2b13b8c4aa5d881be7e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.category-badge','data' => ['category' => $rental->listing->category]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('category-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['category' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($rental->listing->category)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald2d7ec366e64c2b13b8c4aa5d881be7e)): ?>
<?php $attributes = $__attributesOriginald2d7ec366e64c2b13b8c4aa5d881be7e; ?>
<?php unset($__attributesOriginald2d7ec366e64c2b13b8c4aa5d881be7e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald2d7ec366e64c2b13b8c4aa5d881be7e)): ?>
<?php $component = $__componentOriginald2d7ec366e64c2b13b8c4aa5d881be7e; ?>
<?php unset($__componentOriginald2d7ec366e64c2b13b8c4aa5d881be7e); ?>
<?php endif; ?></div>
                        <dl class="mt-5 space-y-3 border-t border-slate-100 pt-5 text-sm">
                            <div class="flex items-start justify-between gap-4"><dt class="text-slate-500">Location</dt><dd class="break-words text-right font-medium text-slate-700"><?php echo e($rental->listing->location); ?></dd></div>
                            <div class="flex items-start justify-between gap-4"><dt class="text-slate-500">Handover</dt><dd class="font-medium text-slate-700"><?php echo e($rental->fulfillment_method->label()); ?></dd></div>
                        </dl>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rental->listing->trashed()): ?>
                            <p class="mt-5 text-sm text-slate-500">This listing has been removed. Its rental record is retained here.</p>
                        <?php else: ?>
                            <a href="<?php echo e(route('listings.show', $rental->listing)); ?>" wire:navigate class="mt-5 inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-100">View listing <span aria-hidden="true">↗</span></a>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </section>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rental->paid_at && $rental->payment?->status === 'paid'): ?>
                    <?php if (isset($component)) { $__componentOriginal770b391e2fc5f9f9f597f2659b8ea142 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal770b391e2fc5f9f9f597f2659b8ea142 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.rental-panel','data' => ['title' => 'Transaction receipt','icon' => 'calculator','subtitle' => 'Your payment and rental charges.','class' => 'renter-rental-receipt']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('rental-panel'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Transaction receipt','icon' => 'calculator','subtitle' => 'Your payment and rental charges.','class' => 'renter-rental-receipt']); ?>
                        <div class="mt-5 flex flex-wrap justify-between gap-x-4 gap-y-1 text-sm">
                            <span class="text-slate-400">Transaction ID</span>
                            <span class="font-mono font-medium text-slate-800"><?php echo e($rental->payment->transaction_reference); ?></span>
                        </div>
                        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 border-t border-slate-100 pt-4 text-sm">
                            <div>
                                <p class="text-slate-400">Renter</p>
                                <p class="font-medium text-slate-800"><?php echo e($rental->renter->name); ?></p>
                            </div>
                            <div>
                                <p class="text-slate-400">Owner</p>
                                <p class="font-medium text-slate-800"><?php echo e($rental->owner->name); ?></p>
                            </div>
                            <div>
                                <p class="text-slate-400">Item</p>
                                <p class="font-medium text-slate-800"><?php echo e($rental->listing->name); ?></p>
                            </div>
                            <div>
                                <p class="text-slate-400">Rental dates</p>
                                <p class="font-medium text-slate-800"><?php echo e($rental->start_date->format('M d, Y')); ?> &ndash; <?php echo e($rental->end_date->format('M d, Y')); ?></p>
                            </div>
                        </div>

                        <dl class="mt-4 space-y-2 border-t border-slate-100 pt-4 text-sm">
                            <div class="flex flex-wrap justify-between gap-x-4 gap-y-1">
                                <dt class="text-slate-500">Rental fee</dt>
                                <dd class="font-medium text-slate-800">₱<?php echo e(number_format($rental->rental_fee, 2)); ?></dd>
                            </div>
                            <div class="flex flex-wrap justify-between gap-x-4 gap-y-1">
                                <dt class="text-slate-500">Platform fee</dt>
                                <dd class="font-medium text-slate-800">₱<?php echo e(number_format($rental->commission_amount, 2)); ?></dd>
                            </div>
                            <div class="flex flex-wrap justify-between gap-x-4 gap-y-1">
                                <dt class="text-slate-500">Security deposit</dt>
                                <dd class="font-medium text-slate-800">₱<?php echo e(number_format($rental->security_deposit, 2)); ?></dd>
                            </div>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rental->currentLateFee() > 0): ?>
                                <div class="flex flex-wrap justify-between gap-x-4 gap-y-1 text-rose-600">
                                    <dt>Late fee</dt>
                                    <dd class="font-medium">₱<?php echo e(number_format($rental->currentLateFee(), 2)); ?></dd>
                                </div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <div class="flex flex-wrap justify-between gap-x-4 gap-y-1 border-t border-slate-100 pt-2 text-base">
                                <dt class="font-semibold text-slate-900">Total paid</dt>
                                <dd class="font-semibold text-slate-900">₱<?php echo e(number_format($rental->payment->amount, 2)); ?></dd>
                            </div>
                        </dl>

                        <p class="mt-4 text-xs text-slate-400">Paid on <?php echo e($rental->paid_at->format('M d, Y \a\t g:i A')); ?></p>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rental->payment->method): ?>
                            <p class="mt-2 text-xs text-slate-500">Verified <?php echo e(\App\Models\OfflinePaymentSetting::methodLabel($rental->payment->method)); ?> · Reference: <?php echo e($rental->payment->external_reference); ?></p>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(($rental->payment_destination['source'] ?? null) === 'owner_wallet'): ?>
                            <p class="mt-3 text-xs leading-5 text-slate-500">The owner received the full booking amount and holds the security deposit. Any deposit refund is arranged manually with the owner after return and inspection.</p>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal770b391e2fc5f9f9f597f2659b8ea142)): ?>
<?php $attributes = $__attributesOriginal770b391e2fc5f9f9f597f2659b8ea142; ?>
<?php unset($__attributesOriginal770b391e2fc5f9f9f597f2659b8ea142); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal770b391e2fc5f9f9f597f2659b8ea142)): ?>
<?php $component = $__componentOriginal770b391e2fc5f9f9f597f2659b8ea142; ?>
<?php unset($__componentOriginal770b391e2fc5f9f9f597f2659b8ea142); ?>
<?php endif; ?>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </aside>

            <div class="renter-rental-actions">
                <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('rentals.exchange-schedule', ['rental' => $rental]);

$__key = 'renter-exchange-'.$rental->id;

$__key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-1613724760-0', $__key);

$__html = app('livewire')->mount($__name, $__params, $__key);

echo $__html;

unset($__html);
unset($__key);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
                <section class="renter-rental-overview rental-panel rounded-2xl border border-slate-200 bg-white p-5 sm:p-6">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div><p class="text-xs font-semibold uppercase tracking-wider text-blue-600">Rental overview</p><h2 class="mt-2 text-xl font-bold tracking-tight text-slate-900">Booking details</h2></div>
                        <?php if (isset($component)) { $__componentOriginal2ddbc40e602c342e508ac696e52f8719 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2ddbc40e602c342e508ac696e52f8719 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.badge','data' => ['color' => $rental->displayStatusColor()]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($rental->displayStatusColor())]); ?><?php echo e($rental->displayStatusLabel()); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2ddbc40e602c342e508ac696e52f8719)): ?>
<?php $attributes = $__attributesOriginal2ddbc40e602c342e508ac696e52f8719; ?>
<?php unset($__attributesOriginal2ddbc40e602c342e508ac696e52f8719); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2ddbc40e602c342e508ac696e52f8719)): ?>
<?php $component = $__componentOriginal2ddbc40e602c342e508ac696e52f8719; ?>
<?php unset($__componentOriginal2ddbc40e602c342e508ac696e52f8719); ?>
<?php endif; ?>
                    </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rental->archived_at): ?>
                        <p class="mt-3 text-sm text-slate-500">Archived on <?php echo e($rental->archived_at->format('M d, Y')); ?>. This completed transaction remains in your rental history.</p>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <div class="mt-5 flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 pb-5">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-blue-50 text-blue-600"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'user-circle','class' => 'h-6 w-6','ariaHidden' => 'true']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'user-circle','class' => 'h-6 w-6','aria-hidden' => 'true']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?></span>
                            <div class="min-w-0"><p class="text-xs text-slate-500">Owned by</p><p class="mt-1 break-words text-sm font-semibold text-slate-800"><?php echo e($rental->owner->name); ?></p>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! $rental->owner->trashed() && ! $rental->owner->isAdmin() && ! $rental->owner->isSuspended() && $rental->owner->hasVerifiedEmail()): ?>
                                    <a href="<?php echo e(route('users.show', $rental->owner)); ?>" wire:navigate class="text-xs font-semibold text-blue-700 hover:underline">View public profile</a>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </div>
                        <a href="<?php echo e(route('rental-requests.chat', $rental->rental_request_id)); ?>" wire:navigate class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-100"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'chat','class' => 'h-4 w-4','ariaHidden' => 'true']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'chat','class' => 'h-4 w-4','aria-hidden' => 'true']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?> Message owner</a>
                    </div>
                    <dl class="rental-detail-dates mt-5 grid gap-4">
                        <div><dt class="text-xs text-slate-500">Start date</dt><dd class="mt-1 text-sm font-semibold text-slate-800"><?php echo e($rental->start_date->format('M d, Y')); ?></dd></div>
                        <div><dt class="text-xs text-slate-500">End date</dt><dd class="mt-1 text-sm font-semibold text-slate-800"><?php echo e($rental->end_date->format('M d, Y')); ?></dd></div>
                        <div><dt class="text-xs text-slate-500">Duration</dt><dd class="mt-1 text-sm font-semibold text-slate-800"><?php echo e($rental->rental_days); ?> day<?php echo e((int) $rental->rental_days === 1 ? '' : 's'); ?></dd></div>
                    </dl>
                    <div class="mt-5 flex flex-wrap items-center justify-between gap-2 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3"><p class="text-sm font-medium text-blue-700">Total amount</p><p class="text-xl font-bold tracking-tight text-blue-700">₱<?php echo e(number_format($rental->total_amount, 2)); ?></p></div>
                </section>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rental->isPaymentPending()): ?>
                    <?php if (isset($component)) { $__componentOriginal770b391e2fc5f9f9f597f2659b8ea142 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal770b391e2fc5f9f9f597f2659b8ea142 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.rental-panel','data' => ['title' => 'Payment summary','icon' => 'calculator','subtitle' => 'Complete an offline payment and submit proof for verification.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('rental-panel'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Payment summary','icon' => 'calculator','subtitle' => 'Complete an offline payment and submit proof for verification.']); ?>
                        <dl class="mt-4 space-y-2 text-sm">
                            <div class="flex flex-wrap justify-between gap-x-4 gap-y-1">
                                <dt class="text-slate-500">Rental fee</dt>
                                <dd class="font-medium text-slate-800">₱<?php echo e(number_format($rental->rental_fee, 2)); ?></dd>
                            </div>
                            <div class="flex flex-wrap justify-between gap-x-4 gap-y-1">
                                <dt class="text-slate-500">Platform fee</dt>
                                <dd class="font-medium text-slate-800">₱<?php echo e(number_format($rental->commission_amount, 2)); ?></dd>
                            </div>
                            <div class="flex flex-wrap justify-between gap-x-4 gap-y-1">
                                <dt class="text-slate-500">Security deposit (refundable)</dt>
                                <dd class="font-medium text-slate-800">₱<?php echo e(number_format($rental->security_deposit, 2)); ?></dd>
                            </div>
                            <div class="flex flex-wrap justify-between gap-x-4 gap-y-1 border-t border-slate-100 pt-2 text-base">
                                <dt class="font-semibold text-slate-900">Total</dt>
                                <dd class="font-semibold text-slate-900">₱<?php echo e(number_format($rental->total_amount, 2)); ?></dd>
                            </div>
                        </dl>

                        <?php $pendingPayment = $rental->paymentSubmissions->firstWhere('status', 'pending'); ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($pendingPayment): ?>
                            <div role="status" class="mt-4 rounded-xl bg-amber-50 p-4 text-sm text-amber-800">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(($pendingPayment->payment_destination['source'] ?? null) === 'owner_wallet'): ?>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($pendingPayment->recipient_confirmed_at): ?>
                                        The owner has acknowledged receipt. Your payment proof is awaiting final admin review. Please do not pay again. Hand-over becomes available after admin approval.
                                    <?php else: ?>
                                        Your payment proof is awaiting the owner's receipt acknowledgement, followed by final admin review. Please do not pay again. Hand-over becomes available after admin approval.
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php else: ?>
                                    Your payment proof is awaiting verification. Please do not pay again. Hand-over becomes available after an admin verifies receipt of the full booking amount.
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        <?php elseif($paymentDestination && auth()->user()->can('pay', $rental)): ?>
                            <?php if (isset($component)) { $__componentOriginal43f0e8186dc0c43a10a770ada7d6d97f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal43f0e8186dc0c43a10a770ada7d6d97f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.payment-destination','data' => ['destination' => $paymentDestination,'rental' => $rental]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('payment-destination'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['destination' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($paymentDestination),'rental' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($rental)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal43f0e8186dc0c43a10a770ada7d6d97f)): ?>
<?php $attributes = $__attributesOriginal43f0e8186dc0c43a10a770ada7d6d97f; ?>
<?php unset($__attributesOriginal43f0e8186dc0c43a10a770ada7d6d97f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal43f0e8186dc0c43a10a770ada7d6d97f)): ?>
<?php $component = $__componentOriginal43f0e8186dc0c43a10a770ada7d6d97f; ?>
<?php unset($__componentOriginal43f0e8186dc0c43a10a770ada7d6d97f); ?>
<?php endif; ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(($paymentDestination['source'] ?? null) === 'owner_wallet'): ?>
                                <p class="mt-3 text-sm leading-6 text-slate-600">Open your e-wallet app and scan or import this QR. Confirm the recipient account name and the full total above before paying. Complete the payment in your e-wallet, then upload its transfer confirmation below. The owner acknowledges receipt before final admin review. Submitting proof does not mark this booking paid.</p>
                            <?php else: ?>
                                <p class="mt-3 text-sm text-slate-500">Pay the full total above using these instructions. Upload the transfer confirmation or official cash receipt. Proof submission does not mark this booking paid.</p>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <form wire:submit="submitPaymentProof" class="mt-4 space-y-3">
                                <div><?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'payment-reference','value' => 'Transfer or receipt reference']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'payment-reference','value' => 'Transfer or receipt reference']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $attributes = $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $component = $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?><?php if (isset($component)) { $__componentOriginal18c21970322f9e5c938bc954620c12bb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal18c21970322f9e5c938bc954620c12bb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.text-input','data' => ['id' => 'payment-reference','wire:model' => 'payment_reference','maxlength' => '100','class' => 'mt-1 w-full']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'payment-reference','wire:model' => 'payment_reference','maxlength' => '100','class' => 'mt-1 w-full']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal18c21970322f9e5c938bc954620c12bb)): ?>
<?php $attributes = $__attributesOriginal18c21970322f9e5c938bc954620c12bb; ?>
<?php unset($__attributesOriginal18c21970322f9e5c938bc954620c12bb); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal18c21970322f9e5c938bc954620c12bb)): ?>
<?php $component = $__componentOriginal18c21970322f9e5c938bc954620c12bb; ?>
<?php unset($__componentOriginal18c21970322f9e5c938bc954620c12bb); ?>
<?php endif; ?><?php if (isset($component)) { $__componentOriginalf94ed9c5393ef72725d159fe01139746 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf94ed9c5393ef72725d159fe01139746 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-error','data' => ['messages' => $errors->get('payment_reference')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['messages' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->get('payment_reference'))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $attributes = $__attributesOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__attributesOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $component = $__componentOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__componentOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?></div>
                                <div><?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'payment-proof','value' => 'Payment proof (JPG, PNG or PDF, up to 5 MB)']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'payment-proof','value' => 'Payment proof (JPG, PNG or PDF, up to 5 MB)']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $attributes = $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $component = $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?><input id="payment-proof" type="file" wire:model="payment_proof" accept="image/jpeg,image/png,application/pdf" class="mt-2 block w-full text-sm" /><?php if (isset($component)) { $__componentOriginalf94ed9c5393ef72725d159fe01139746 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf94ed9c5393ef72725d159fe01139746 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-error','data' => ['messages' => $errors->get('payment_proof')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['messages' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->get('payment_proof'))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $attributes = $__attributesOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__attributesOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $component = $__componentOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__componentOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?></div>
                                <p wire:loading wire:target="payment_proof" class="text-sm text-slate-500">Uploading proof…</p>
                                <?php if (isset($component)) { $__componentOriginald411d1792bd6cc877d687758b753742c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald411d1792bd6cc877d687758b753742c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.primary-button','data' => ['wire:loading.attr' => 'disabled']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('primary-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:loading.attr' => 'disabled']); ?>Submit payment proof <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $attributes = $__attributesOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__attributesOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $component = $__componentOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__componentOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?>
                            </form>
                        <?php else: ?>
                            <p class="mt-4 text-sm text-slate-600">Payment is unavailable until support provides payment instructions and the booking agreement is confirmed. Contact support before sending any funds.</p>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rental->isCancellableByRenter()): ?>
                            <button type="button" wire:click="$toggle('showCancelForm')" class="mt-3 w-full text-center text-xs font-medium text-slate-500 hover:text-slate-700">
                                Cancel this booking instead
                            </button>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal770b391e2fc5f9f9f597f2659b8ea142)): ?>
<?php $attributes = $__attributesOriginal770b391e2fc5f9f9f597f2659b8ea142; ?>
<?php unset($__attributesOriginal770b391e2fc5f9f9f597f2659b8ea142); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal770b391e2fc5f9f9f597f2659b8ea142)): ?>
<?php $component = $__componentOriginal770b391e2fc5f9f9f597f2659b8ea142; ?>
<?php unset($__componentOriginal770b391e2fc5f9f9f597f2659b8ea142); ?>
<?php endif; ?>

                    <?php echo $__env->make('livewire.renter.rentals._cancel-form', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php else: ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rental->isOverdue()): ?>
                        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-5 text-sm leading-6 text-rose-700" role="status">
                            <p class="font-semibold">This rental is overdue by <?php echo e($rental->currentOverdueDays()); ?> day<?php echo e($rental->currentOverdueDays() === 1 ? '' : 's'); ?>.</p>
                            <p class="mt-1">Late fee so far: ₱<?php echo e(number_format($rental->currentLateFee(), 2)); ?></p>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rental->isCancelled()): ?>
                        <div class="rounded-2xl border border-slate-200 bg-white p-5 text-sm leading-6" role="status">
                            <p class="font-semibold text-slate-700">This booking was cancelled.</p>
                            <?php if (isset($component)) { $__componentOriginal83532addefd2917721b7cf17b9a23c69 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal83532addefd2917721b7cf17b9a23c69 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.cancellation-details','data' => ['record' => $rental]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('cancellation-details'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['record' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($rental)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal83532addefd2917721b7cf17b9a23c69)): ?>
<?php $attributes = $__attributesOriginal83532addefd2917721b7cf17b9a23c69; ?>
<?php unset($__attributesOriginal83532addefd2917721b7cf17b9a23c69); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal83532addefd2917721b7cf17b9a23c69)): ?>
<?php $component = $__componentOriginal83532addefd2917721b7cf17b9a23c69; ?>
<?php unset($__componentOriginal83532addefd2917721b7cf17b9a23c69); ?>
<?php endif; ?>
                        </div>
                    <?php elseif($rental->isCancellableByRenter()): ?>
                        <?php if (isset($component)) { $__componentOriginal770b391e2fc5f9f9f597f2659b8ea142 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal770b391e2fc5f9f9f597f2659b8ea142 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.rental-panel','data' => ['title' => 'Booking options','icon' => 'x-mark','subtitle' => 'Review any cancellation fees before cancelling.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('rental-panel'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Booking options','icon' => 'x-mark','subtitle' => 'Review any cancellation fees before cancelling.']); ?>
                            <button type="button" wire:click="$toggle('showCancelForm')" class="mt-5 inline-flex min-h-10 items-center rounded-lg border border-rose-200 px-3 text-sm font-semibold text-rose-700 transition hover:bg-rose-50 focus:outline-none focus:ring-4 focus:ring-rose-100">
                                Cancel this booking
                            </button>
                         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal770b391e2fc5f9f9f597f2659b8ea142)): ?>
<?php $attributes = $__attributesOriginal770b391e2fc5f9f9f597f2659b8ea142; ?>
<?php unset($__attributesOriginal770b391e2fc5f9f9f597f2659b8ea142); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal770b391e2fc5f9f9f597f2659b8ea142)): ?>
<?php $component = $__componentOriginal770b391e2fc5f9f9f597f2659b8ea142; ?>
<?php unset($__componentOriginal770b391e2fc5f9f9f597f2659b8ea142); ?>
<?php endif; ?>

                        <?php echo $__env->make('livewire.renter.rentals._cancel-form', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rental->awaitingPickupConfirmation()): ?>
                        <?php if (isset($component)) { $__componentOriginal770b391e2fc5f9f9f597f2659b8ea142 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal770b391e2fc5f9f9f597f2659b8ea142 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.rental-panel','data' => ['title' => $rental->fulfillment_method->label().' confirmation','icon' => 'inbox','subtitle' => 'Both you and the owner need to confirm the handover before the rental starts.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('rental-panel'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($rental->fulfillment_method->label().' confirmation'),'icon' => 'inbox','subtitle' => 'Both you and the owner need to confirm the handover before the rental starts.']); ?>
                            <ul class="mt-4 space-y-2 rounded-xl border border-slate-100 bg-slate-50 p-4 text-sm">
                                <li class="<?php echo e($rental->pickup_confirmed_by_owner_at ? 'text-blue-700' : 'text-slate-500'); ?>">
                                    <?php echo e($rental->pickup_confirmed_by_owner_at ? '✓ Owner confirmed' : 'Waiting on owner confirmation'); ?>

                                </li>
                                <li class="<?php echo e($rental->pickup_confirmed_by_renter_at ? 'text-blue-700' : 'text-slate-500'); ?>">
                                    <?php echo e($rental->pickup_confirmed_by_renter_at ? '✓ You confirmed' : 'Waiting on your confirmation'); ?>

                                </li>
                            </ul>

                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! $rental->pickup_confirmed_by_renter_at): ?>
                                <button type="button" wire:click="confirmPickup" wire:loading.attr="disabled" wire:target="confirmPickup" class="mt-5 inline-flex min-h-11 items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-100 disabled:cursor-wait disabled:opacity-60">
                                    <?php echo e($rental->fulfillment_method->value === 'delivery' ? 'Confirm delivery received' : 'Confirm I picked up the item'); ?>

                                </button>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal770b391e2fc5f9f9f597f2659b8ea142)): ?>
<?php $attributes = $__attributesOriginal770b391e2fc5f9f9f597f2659b8ea142; ?>
<?php unset($__attributesOriginal770b391e2fc5f9f9f597f2659b8ea142); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal770b391e2fc5f9f9f597f2659b8ea142)): ?>
<?php $component = $__componentOriginal770b391e2fc5f9f9f597f2659b8ea142; ?>
<?php unset($__componentOriginal770b391e2fc5f9f9f597f2659b8ea142); ?>
<?php endif; ?>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rental->awaitingReturnConfirmation()): ?>
                        <?php if (isset($component)) { $__componentOriginal770b391e2fc5f9f9f597f2659b8ea142 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal770b391e2fc5f9f9f597f2659b8ea142 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.rental-panel','data' => ['title' => 'Return confirmation','icon' => 'archive','subtitle' => 'The owner confirms receipt and records the returned condition to close the rental. You can also acknowledge your return below.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('rental-panel'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Return confirmation','icon' => 'archive','subtitle' => 'The owner confirms receipt and records the returned condition to close the rental. You can also acknowledge your return below.']); ?>
                            <ul class="mt-4 space-y-2 rounded-xl border border-slate-100 bg-slate-50 p-4 text-sm">
                                <li class="<?php echo e($rental->return_confirmed_by_owner_at ? 'text-blue-700' : 'text-slate-500'); ?>">
                                    <?php echo e($rental->return_confirmed_by_owner_at ? '✓ Owner confirmed' : 'Waiting on owner confirmation'); ?>

                                </li>
                                <li class="<?php echo e($rental->return_confirmed_by_renter_at ? 'text-blue-700' : 'text-slate-500'); ?>">
                                    <?php echo e($rental->return_confirmed_by_renter_at ? '✓ You confirmed' : 'Your acknowledgment is optional'); ?>

                                </li>
                            </ul>

                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! $rental->return_confirmed_by_renter_at): ?>
                                <button type="button" wire:click="confirmReturn" wire:loading.attr="disabled" wire:target="confirmReturn" class="mt-5 inline-flex min-h-11 items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-100 disabled:cursor-wait disabled:opacity-60">
                                    Confirm I returned the item
                                </button>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal770b391e2fc5f9f9f597f2659b8ea142)): ?>
<?php $attributes = $__attributesOriginal770b391e2fc5f9f9f597f2659b8ea142; ?>
<?php unset($__attributesOriginal770b391e2fc5f9f9f597f2659b8ea142); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal770b391e2fc5f9f9f597f2659b8ea142)): ?>
<?php $component = $__componentOriginal770b391e2fc5f9f9f597f2659b8ea142; ?>
<?php unset($__componentOriginal770b391e2fc5f9f9f597f2659b8ea142); ?>
<?php endif; ?>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rental->isReturned() && ! $rental->afterConditionRecord): ?>
                        <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5 text-sm leading-6 text-blue-700" role="status">
                            Item returned. Waiting for the owner to record the returned condition; the rental will then close automatically.
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rental->beforeConditionRecord || $rental->afterConditionRecord): ?>
                        <div class="space-y-6">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rental->beforeConditionRecord): ?>
                                <?php if (isset($component)) { $__componentOriginal770b391e2fc5f9f9f597f2659b8ea142 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal770b391e2fc5f9f9f597f2659b8ea142 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.rental-panel','data' => ['title' => 'Condition before rental','icon' => 'clipboard-list']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('rental-panel'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Condition before rental','icon' => 'clipboard-list']); ?>
                                    <p class="mt-2 text-sm text-slate-600"><?php echo e($rental->beforeConditionRecord->condition->label()); ?></p>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rental->beforeConditionRecord->notes): ?>
                                        <p class="mt-1 text-sm text-slate-500"><?php echo e($rental->beforeConditionRecord->notes); ?></p>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rental->beforeConditionRecord->photos->isNotEmpty()): ?>
                                        <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $rental->beforeConditionRecord->photos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $photo): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <img src="<?php echo e($photo->url()); ?>" alt="Rental evidence photo <?php echo e($loop->iteration); ?>" loading="lazy" class="aspect-square w-full rounded-lg object-cover">
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </div>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal770b391e2fc5f9f9f597f2659b8ea142)): ?>
<?php $attributes = $__attributesOriginal770b391e2fc5f9f9f597f2659b8ea142; ?>
<?php unset($__attributesOriginal770b391e2fc5f9f9f597f2659b8ea142); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal770b391e2fc5f9f9f597f2659b8ea142)): ?>
<?php $component = $__componentOriginal770b391e2fc5f9f9f597f2659b8ea142; ?>
<?php unset($__componentOriginal770b391e2fc5f9f9f597f2659b8ea142); ?>
<?php endif; ?>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rental->afterConditionRecord): ?>
                                <?php if (isset($component)) { $__componentOriginal770b391e2fc5f9f9f597f2659b8ea142 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal770b391e2fc5f9f9f597f2659b8ea142 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.rental-panel','data' => ['title' => 'Condition after rental','icon' => 'clipboard-list']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('rental-panel'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Condition after rental','icon' => 'clipboard-list']); ?>
                                    <p class="mt-2 text-sm text-slate-600"><?php echo e($rental->afterConditionRecord->condition->label()); ?></p>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rental->afterConditionRecord->notes): ?>
                                        <p class="mt-1 text-sm text-slate-500"><?php echo e($rental->afterConditionRecord->notes); ?></p>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rental->afterConditionRecord->photos->isNotEmpty()): ?>
                                        <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $rental->afterConditionRecord->photos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $photo): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <img src="<?php echo e($photo->url()); ?>" alt="Rental evidence photo <?php echo e($loop->iteration); ?>" loading="lazy" class="aspect-square w-full rounded-lg object-cover">
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </div>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal770b391e2fc5f9f9f597f2659b8ea142)): ?>
<?php $attributes = $__attributesOriginal770b391e2fc5f9f9f597f2659b8ea142; ?>
<?php unset($__attributesOriginal770b391e2fc5f9f9f597f2659b8ea142); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal770b391e2fc5f9f9f597f2659b8ea142)): ?>
<?php $component = $__componentOriginal770b391e2fc5f9f9f597f2659b8ea142; ?>
<?php unset($__componentOriginal770b391e2fc5f9f9f597f2659b8ea142); ?>
<?php endif; ?>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rental->securityDeposit): ?>
                        <?php if (isset($component)) { $__componentOriginal770b391e2fc5f9f9f597f2659b8ea142 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal770b391e2fc5f9f9f597f2659b8ea142 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.rental-panel','data' => ['title' => 'Security deposit','icon' => 'calculator']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('rental-panel'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Security deposit','icon' => 'calculator']); ?>
                            <div class="mt-4 flex flex-wrap items-center gap-2">
                                <?php if (isset($component)) { $__componentOriginal2ddbc40e602c342e508ac696e52f8719 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2ddbc40e602c342e508ac696e52f8719 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.badge','data' => ['color' => $rental->securityDeposit->status->badgeColor()]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($rental->securityDeposit->status->badgeColor())]); ?><?php echo e($rental->securityDeposit->status->label()); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2ddbc40e602c342e508ac696e52f8719)): ?>
<?php $attributes = $__attributesOriginal2ddbc40e602c342e508ac696e52f8719; ?>
<?php unset($__attributesOriginal2ddbc40e602c342e508ac696e52f8719); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2ddbc40e602c342e508ac696e52f8719)): ?>
<?php $component = $__componentOriginal2ddbc40e602c342e508ac696e52f8719; ?>
<?php unset($__componentOriginal2ddbc40e602c342e508ac696e52f8719); ?>
<?php endif; ?>
                                <span class="text-sm text-slate-500">₱<?php echo e(number_format($rental->securityDeposit->amount, 2)); ?> held</span>
                            </div>

                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rental->damageReport): ?>
                                <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-4">
                                    <p class="text-sm font-semibold text-amber-800">Damage claim: <?php echo e($rental->damageReport->damage_type); ?></p>
                                    <p class="mt-1 text-sm text-amber-700"><?php echo e($rental->damageReport->description); ?></p>
                                    <p class="mt-1 text-sm text-amber-700">
                                        Estimated repair: ₱<?php echo e(number_format($rental->damageReport->estimated_repair_cost, 2)); ?> &middot;
                                        Proposed deduction: ₱<?php echo e(number_format($rental->damageReport->proposed_deduction, 2)); ?>

                                    </p>

                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rental->damageReport->photos->isNotEmpty()): ?>
                                        <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $rental->damageReport->photos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $photo): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <img src="<?php echo e($photo->url()); ?>" alt="Rental evidence photo <?php echo e($loop->iteration); ?>" loading="lazy" class="aspect-square w-full rounded-lg object-cover">
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </div>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rental->damageReport->isPending() && $rental->isCompleted()): ?>
                                        <div class="mt-4 space-y-3">
                                            <button type="button" wire:click="acceptDamageClaim" wire:loading.attr="disabled" wire:target="acceptDamageClaim" wire:confirm="Accept this claim? ₱<?php echo e(number_format($rental->damageReport->proposed_deduction, 2)); ?> will be deducted from your deposit." class="mt-5 inline-flex min-h-11 items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-100 disabled:cursor-wait disabled:opacity-60">
                                                Accept claim
                                            </button>

                                            <div>
                                                <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'damage_response_notes','value' => 'Or dispute it — explain why']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'damage_response_notes','value' => 'Or dispute it — explain why']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $attributes = $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $component = $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
                                                <textarea wire:model="damage_response_notes" id="damage_response_notes" rows="2" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-rose-500 focus:ring-rose-500"></textarea>
                                                <?php if (isset($component)) { $__componentOriginalf94ed9c5393ef72725d159fe01139746 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf94ed9c5393ef72725d159fe01139746 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-error','data' => ['messages' => $errors->get('damage_response_notes'),'class' => 'mt-2']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['messages' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->get('damage_response_notes')),'class' => 'mt-2']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $attributes = $__attributesOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__attributesOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $component = $__componentOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__componentOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
                                                <button type="button" wire:click="disputeDamageClaim" wire:loading.attr="disabled" wire:target="disputeDamageClaim" class="mt-3 inline-flex min-h-11 items-center justify-center rounded-xl border border-rose-200 px-4 py-2.5 text-sm font-semibold text-rose-700 transition hover:bg-rose-50 focus:outline-none focus:ring-4 focus:ring-rose-100 disabled:opacity-60">
                                                    Dispute claim
                                                </button>
                                            </div>
                                        </div>
                                    <?php elseif($rental->damageReport->status->value === 'disputed'): ?>
                                        <p class="mt-3 text-sm text-amber-700">You disputed this claim. An admin will review it.</p>
                                    <?php elseif($rental->damageReport->status->value === 'accepted'): ?>
                                        <p class="mt-3 text-sm text-amber-700">
                                            You accepted this claim. Refund: ₱<?php echo e(number_format($rental->securityDeposit->amount - $rental->securityDeposit->deducted_amount, 2)); ?>

                                        </p>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                            <?php elseif($rental->securityDeposit->status->value === 'released'): ?>
                                <p class="mt-3 text-sm text-slate-600">Your full deposit has been released.</p>
                            <?php elseif($rental->securityDeposit->status->value === 'return_eligible'): ?>
                                <p class="mt-3 text-sm text-slate-600">No damage reported. Waiting for the owner to release your deposit.</p>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal770b391e2fc5f9f9f597f2659b8ea142)): ?>
<?php $attributes = $__attributesOriginal770b391e2fc5f9f9f597f2659b8ea142; ?>
<?php unset($__attributesOriginal770b391e2fc5f9f9f597f2659b8ea142); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal770b391e2fc5f9f9f597f2659b8ea142)): ?>
<?php $component = $__componentOriginal770b391e2fc5f9f9f597f2659b8ea142; ?>
<?php unset($__componentOriginal770b391e2fc5f9f9f597f2659b8ea142); ?>
<?php endif; ?>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rental->isCompleted() && auth()->id() === $rental->renter_id && $rental->owner_id !== $rental->renter_id): ?>
                        <div id="reviews" class="renter-rental-reviews grid gap-6">
                            <?php if (isset($component)) { $__componentOriginal770b391e2fc5f9f9f597f2659b8ea142 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal770b391e2fc5f9f9f597f2659b8ea142 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.rental-panel','data' => ['title' => 'Rate the owner','icon' => 'star','subtitle' => 'Optional. Your rating and comment will be published on the owner\'s public profile.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('rental-panel'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Rate the owner','icon' => 'star','subtitle' => 'Optional. Your rating and comment will be published on the owner\'s public profile.']); ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rental->reviewFromRenterToOwner): ?>
                                    <p class="mt-2 text-sm text-slate-600">You rated <?php echo e($rental->reviewFromRenterToOwner->rating); ?>/5 stars</p>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rental->reviewFromRenterToOwner->comment): ?>
                                        <p class="mt-1 text-sm text-slate-500">"<?php echo e($rental->reviewFromRenterToOwner->comment); ?>"</p>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php else: ?>
                                    <form wire:submit="submitOwnerReview" class="mt-3 space-y-3">
                                        <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'owner_rating','value' => 'Rating']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'owner_rating','value' => 'Rating']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $attributes = $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $component = $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
                                        <select id="owner_rating" wire:model="owner_rating" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php for($i = 5; $i >= 1; $i--): ?>
                                                <option value="<?php echo e($i); ?>"><?php echo e($i); ?> star<?php echo e($i === 1 ? '' : 's'); ?></option>
                                            <?php endfor; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </select>
                                        <?php if (isset($component)) { $__componentOriginalf94ed9c5393ef72725d159fe01139746 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf94ed9c5393ef72725d159fe01139746 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-error','data' => ['messages' => $errors->get('owner_rating')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['messages' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->get('owner_rating'))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $attributes = $__attributesOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__attributesOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $component = $__componentOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__componentOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
                                        <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'owner_comment','value' => 'Comment (optional)']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'owner_comment','value' => 'Comment (optional)']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $attributes = $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $component = $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
                                        <textarea id="owner_comment" wire:model="owner_comment" rows="2" placeholder="Optional comment" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                                        <?php if (isset($component)) { $__componentOriginalf94ed9c5393ef72725d159fe01139746 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf94ed9c5393ef72725d159fe01139746 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-error','data' => ['messages' => $errors->get('owner_comment')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['messages' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->get('owner_comment'))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $attributes = $__attributesOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__attributesOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $component = $__componentOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__componentOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
                                        <?php if (isset($component)) { $__componentOriginald411d1792bd6cc877d687758b753742c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald411d1792bd6cc877d687758b753742c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.primary-button','data' => ['class' => 'min-h-11 rounded-xl','wire:loading.attr' => 'disabled']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('primary-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'min-h-11 rounded-xl','wire:loading.attr' => 'disabled']); ?>Submit rating <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $attributes = $__attributesOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__attributesOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $component = $__componentOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__componentOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?>
                                    </form>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal770b391e2fc5f9f9f597f2659b8ea142)): ?>
<?php $attributes = $__attributesOriginal770b391e2fc5f9f9f597f2659b8ea142; ?>
<?php unset($__attributesOriginal770b391e2fc5f9f9f597f2659b8ea142); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal770b391e2fc5f9f9f597f2659b8ea142)): ?>
<?php $component = $__componentOriginal770b391e2fc5f9f9f597f2659b8ea142; ?>
<?php unset($__componentOriginal770b391e2fc5f9f9f597f2659b8ea142); ?>
<?php endif; ?>

                            <?php if (isset($component)) { $__componentOriginal770b391e2fc5f9f9f597f2659b8ea142 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal770b391e2fc5f9f9f597f2659b8ea142 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.rental-panel','data' => ['title' => 'Rate the item','icon' => 'star']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('rental-panel'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Rate the item','icon' => 'star']); ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rental->reviewFromRenterToListing): ?>
                                    <p class="mt-2 text-sm text-slate-600">You rated <?php echo e($rental->reviewFromRenterToListing->rating); ?>/5 stars</p>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rental->reviewFromRenterToListing->comment): ?>
                                        <p class="mt-1 text-sm text-slate-500">"<?php echo e($rental->reviewFromRenterToListing->comment); ?>"</p>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php else: ?>
                                    <form wire:submit="submitListingReview" class="mt-3 space-y-3">
                                        <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'listing_rating','value' => 'Rating']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'listing_rating','value' => 'Rating']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $attributes = $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $component = $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
                                        <select id="listing_rating" wire:model="listing_rating" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php for($i = 5; $i >= 1; $i--): ?>
                                                <option value="<?php echo e($i); ?>"><?php echo e($i); ?> star<?php echo e($i === 1 ? '' : 's'); ?></option>
                                            <?php endfor; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </select>
                                        <?php if (isset($component)) { $__componentOriginalf94ed9c5393ef72725d159fe01139746 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf94ed9c5393ef72725d159fe01139746 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-error','data' => ['messages' => $errors->get('listing_rating')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['messages' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->get('listing_rating'))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $attributes = $__attributesOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__attributesOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $component = $__componentOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__componentOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
                                        <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'listing_comment','value' => 'Comment (optional)']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'listing_comment','value' => 'Comment (optional)']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $attributes = $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $component = $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
                                        <textarea id="listing_comment" wire:model="listing_comment" rows="2" placeholder="Optional comment" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                                        <?php if (isset($component)) { $__componentOriginalf94ed9c5393ef72725d159fe01139746 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf94ed9c5393ef72725d159fe01139746 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-error','data' => ['messages' => $errors->get('listing_comment')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['messages' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->get('listing_comment'))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $attributes = $__attributesOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__attributesOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $component = $__componentOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__componentOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
                                        <?php if (isset($component)) { $__componentOriginald411d1792bd6cc877d687758b753742c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald411d1792bd6cc877d687758b753742c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.primary-button','data' => ['class' => 'min-h-11 rounded-xl','wire:loading.attr' => 'disabled']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('primary-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'min-h-11 rounded-xl','wire:loading.attr' => 'disabled']); ?>Submit rating <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $attributes = $__attributesOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__attributesOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $component = $__componentOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__componentOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?>
                                    </form>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal770b391e2fc5f9f9f597f2659b8ea142)): ?>
<?php $attributes = $__attributesOriginal770b391e2fc5f9f9f597f2659b8ea142; ?>
<?php unset($__attributesOriginal770b391e2fc5f9f9f597f2659b8ea142); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal770b391e2fc5f9f9f597f2659b8ea142)): ?>
<?php $component = $__componentOriginal770b391e2fc5f9f9f597f2659b8ea142; ?>
<?php unset($__componentOriginal770b391e2fc5f9f9f597f2659b8ea142); ?>
<?php endif; ?>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    <?php if (isset($component)) { $__componentOriginal770b391e2fc5f9f9f597f2659b8ea142 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal770b391e2fc5f9f9f597f2659b8ea142 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.rental-panel','data' => ['title' => 'Disputes','icon' => 'chat','subtitle' => 'Keep any issues and resolutions in one place.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('rental-panel'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Disputes','icon' => 'chat','subtitle' => 'Keep any issues and resolutions in one place.']); ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('status')): ?>
                            <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-700"><?php echo e(session('status')); ?></div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $rental->disputes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dispute): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <div class="mb-3 rounded-lg border border-slate-200 p-3" wire:key="dispute-<?php echo e($dispute->id); ?>">
                                <div class="mt-4 flex flex-wrap items-center gap-2">
                                    <p class="text-sm font-semibold text-slate-800"><?php echo e($dispute->reason->label()); ?></p>
                                    <?php if (isset($component)) { $__componentOriginal2ddbc40e602c342e508ac696e52f8719 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2ddbc40e602c342e508ac696e52f8719 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.badge','data' => ['color' => $dispute->status->badgeColor()]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($dispute->status->badgeColor())]); ?><?php echo e($dispute->status->label()); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2ddbc40e602c342e508ac696e52f8719)): ?>
<?php $attributes = $__attributesOriginal2ddbc40e602c342e508ac696e52f8719; ?>
<?php unset($__attributesOriginal2ddbc40e602c342e508ac696e52f8719); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2ddbc40e602c342e508ac696e52f8719)): ?>
<?php $component = $__componentOriginal2ddbc40e602c342e508ac696e52f8719; ?>
<?php unset($__componentOriginal2ddbc40e602c342e508ac696e52f8719); ?>
<?php endif; ?>
                                </div>
                                <p class="mt-1 text-sm text-slate-500"><?php echo e($dispute->description); ?></p>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($dispute->status->value === 'resolved'): ?>
                                    <p class="mt-1 text-sm text-blue-700">Resolution: <?php echo e($dispute->resolution?->label()); ?></p>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($dispute->resolution_notes): ?>
                                        <p class="mt-1 text-sm text-slate-500"><?php echo e($dispute->resolution_notes); ?></p>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <p class="text-sm text-slate-400">No disputes filed for this rental.</p>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! $showDisputeForm): ?>
                            <button type="button" wire:click="$set('showDisputeForm', true)" class="mt-4 inline-flex min-h-10 items-center rounded-lg border border-rose-200 bg-rose-50 px-3 text-sm font-semibold text-rose-700 transition hover:bg-rose-100 focus:outline-none focus:ring-4 focus:ring-rose-100">
                                Report an issue
                            </button>
                        <?php else: ?>
                            <form wire:submit="createDispute" class="mt-3 space-y-3 rounded-lg border border-slate-200 bg-slate-50 p-4">
                                <div>
                                    <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'dispute_reason','value' => 'Reason']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'dispute_reason','value' => 'Reason']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $attributes = $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $component = $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
                                    <select wire:model="dispute_reason" id="dispute_reason" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">Select a reason</option>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = \App\Enums\DisputeReason::cases(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reason): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($reason->value); ?>"><?php echo e($reason->label()); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </select>
                                    <?php if (isset($component)) { $__componentOriginalf94ed9c5393ef72725d159fe01139746 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf94ed9c5393ef72725d159fe01139746 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-error','data' => ['messages' => $errors->get('dispute_reason'),'class' => 'mt-2']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['messages' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->get('dispute_reason')),'class' => 'mt-2']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $attributes = $__attributesOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__attributesOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $component = $__componentOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__componentOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
                                </div>
                                <div>
                                    <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'dispute_description','value' => 'Describe what happened']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'dispute_description','value' => 'Describe what happened']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $attributes = $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $component = $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
                                    <textarea wire:model="dispute_description" id="dispute_description" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                                    <?php if (isset($component)) { $__componentOriginalf94ed9c5393ef72725d159fe01139746 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf94ed9c5393ef72725d159fe01139746 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-error','data' => ['messages' => $errors->get('dispute_description'),'class' => 'mt-2']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['messages' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->get('dispute_description')),'class' => 'mt-2']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $attributes = $__attributesOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__attributesOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf94ed9c5393ef72725d159fe01139746)): ?>
<?php $component = $__componentOriginalf94ed9c5393ef72725d159fe01139746; ?>
<?php unset($__componentOriginalf94ed9c5393ef72725d159fe01139746); ?>
<?php endif; ?>
                                </div>
                                <div class="flex flex-wrap items-center gap-3">
                                    <?php if (isset($component)) { $__componentOriginald411d1792bd6cc877d687758b753742c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald411d1792bd6cc877d687758b753742c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.primary-button','data' => ['class' => 'min-h-11 rounded-xl','wire:loading.attr' => 'disabled']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('primary-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'min-h-11 rounded-xl','wire:loading.attr' => 'disabled']); ?>Submit dispute <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $attributes = $__attributesOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__attributesOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $component = $__componentOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__componentOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?>
                                    <button type="button" wire:click="$set('showDisputeForm', false)" class="text-sm font-medium text-slate-500 hover:text-slate-700">Cancel</button>
                                </div>
                            </form>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal770b391e2fc5f9f9f597f2659b8ea142)): ?>
<?php $attributes = $__attributesOriginal770b391e2fc5f9f9f597f2659b8ea142; ?>
<?php unset($__attributesOriginal770b391e2fc5f9f9f597f2659b8ea142); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal770b391e2fc5f9f9f597f2659b8ea142)): ?>
<?php $component = $__componentOriginal770b391e2fc5f9f9f597f2659b8ea142; ?>
<?php unset($__componentOriginal770b391e2fc5f9f9f597f2659b8ea142); ?>
<?php endif; ?>

                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rental->paymentSubmissions->isNotEmpty()): ?>
                    <?php if (isset($component)) { $__componentOriginal770b391e2fc5f9f9f597f2659b8ea142 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal770b391e2fc5f9f9f597f2659b8ea142 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.rental-panel','data' => ['title' => 'Payment proof history','icon' => 'calculator']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('rental-panel'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Payment proof history','icon' => 'calculator']); ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $rental->paymentSubmissions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $submission): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="mt-4 space-y-2 border-t border-slate-100 pt-4 text-sm" wire:key="renter-proof-<?php echo e($submission->id); ?>">
                                <p class="font-semibold"><?php echo e(ucfirst($submission->status)); ?> · <?php echo e($submission->external_reference); ?> · ₱<?php echo e(number_format($submission->amount, 2)); ?></p>
                                <a href="<?php echo e(route('payment-proofs.show', $submission)); ?>" class="font-semibold text-blue-700">Download submitted proof</a>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($submission->review_notes): ?><p class="whitespace-pre-line text-slate-600"><?php echo e($submission->review_notes); ?></p><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($submission->status === 'rejected' && $rental->isPaymentPending()): ?><p class="text-slate-500">Correct the issue described above and submit new proof. Confirm whether funds were received before making another payment.</p><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal770b391e2fc5f9f9f597f2659b8ea142)): ?>
<?php $attributes = $__attributesOriginal770b391e2fc5f9f9f597f2659b8ea142; ?>
<?php unset($__attributesOriginal770b391e2fc5f9f9f597f2659b8ea142); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal770b391e2fc5f9f9f597f2659b8ea142)): ?>
<?php $component = $__componentOriginal770b391e2fc5f9f9f597f2659b8ea142; ?>
<?php unset($__componentOriginal770b391e2fc5f9f9f597f2659b8ea142); ?>
<?php endif; ?>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php /**PATH C:\laragon\www\lendly\resources\views/livewire/renter/rentals/show.blade.php ENDPATH**/ ?>