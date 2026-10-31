<div class="listing-detail-page min-h-screen bg-slate-50" wire:poll.15s.visible x-data="{ previousBodyOverflow: '' }">
    <?php if (isset($component)) { $__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.page-header','data' => ['eyebrow' => 'Lendly marketplace','title' => $listing->name,'subtitle' => $listing->location]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['eyebrow' => 'Lendly marketplace','title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($listing->name),'subtitle' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($listing->location)]); ?>
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

    <div class="listing-detail-content w-full px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('status')): ?>
            <div class="mb-6 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-700" role="status"><?php echo e(session('status')); ?></div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <a href="<?php echo e(route('listings.index')); ?>" wire:navigate class="inline-flex min-h-10 items-center gap-2 rounded-lg text-sm font-semibold text-slate-600 transition hover:text-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-100">
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
<?php endif; ?> Back to browse
            </a>
            <div class="flex flex-wrap items-center gap-2">
                <?php if (isset($component)) { $__componentOriginald2d7ec366e64c2b13b8c4aa5d881be7e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald2d7ec366e64c2b13b8c4aa5d881be7e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.category-badge','data' => ['category' => $listing->category]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('category-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['category' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($listing->category)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald2d7ec366e64c2b13b8c4aa5d881be7e)): ?>
<?php $attributes = $__attributesOriginald2d7ec366e64c2b13b8c4aa5d881be7e; ?>
<?php unset($__attributesOriginald2d7ec366e64c2b13b8c4aa5d881be7e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald2d7ec366e64c2b13b8c4aa5d881be7e)): ?>
<?php $component = $__componentOriginald2d7ec366e64c2b13b8c4aa5d881be7e; ?>
<?php unset($__componentOriginald2d7ec366e64c2b13b8c4aa5d881be7e); ?>
<?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($listing->subcategory): ?>
                    <?php if (isset($component)) { $__componentOriginal2ddbc40e602c342e508ac696e52f8719 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2ddbc40e602c342e508ac696e52f8719 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.badge','data' => ['color' => 'slate']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['color' => 'slate']); ?><?php echo e($listing->subcategory->name); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2ddbc40e602c342e508ac696e52f8719)): ?>
<?php $attributes = $__attributesOriginal2ddbc40e602c342e508ac696e52f8719; ?>
<?php unset($__attributesOriginal2ddbc40e602c342e508ac696e52f8719); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2ddbc40e602c342e508ac696e52f8719)): ?>
<?php $component = $__componentOriginal2ddbc40e602c342e508ac696e52f8719; ?>
<?php unset($__componentOriginal2ddbc40e602c342e508ac696e52f8719); ?>
<?php endif; ?>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if (isset($component)) { $__componentOriginal2ddbc40e602c342e508ac696e52f8719 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2ddbc40e602c342e508ac696e52f8719 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.badge','data' => ['color' => 'slate']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['color' => 'slate']); ?><?php echo e($listing->condition->label()); ?> condition <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2ddbc40e602c342e508ac696e52f8719)): ?>
<?php $attributes = $__attributesOriginal2ddbc40e602c342e508ac696e52f8719; ?>
<?php unset($__attributesOriginal2ddbc40e602c342e508ac696e52f8719); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2ddbc40e602c342e508ac696e52f8719)): ?>
<?php $component = $__componentOriginal2ddbc40e602c342e508ac696e52f8719; ?>
<?php unset($__componentOriginal2ddbc40e602c342e508ac696e52f8719); ?>
<?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($averageRating !== null): ?>
                    <a href="#item-reviews" class="inline-flex min-h-10 items-center gap-1.5 rounded-lg px-2 text-sm text-slate-600 hover:text-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-100">
                        <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'star','class' => 'h-4 w-4 fill-amber-400 text-amber-400','ariaHidden' => 'true']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'star','class' => 'h-4 w-4 fill-amber-400 text-amber-400','aria-hidden' => 'true']); ?>
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
                        <span class="font-semibold text-slate-800"><?php echo e(number_format($averageRating, 1)); ?></span>
                        <span>· <?php echo e($reviews->count()); ?> review<?php echo e($reviews->count() === 1 ? '' : 's'); ?></span>
                    </a>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>

        <div class="listing-detail-grid">
            <div class="listing-detail-primary">
                <?php $photoUrls = $listing->images->map(fn ($image) => $image->url())->values(); ?>
                <section class="listing-detail-card listing-detail-gallery overflow-hidden rounded-2xl border border-slate-200 bg-white" x-data="{ photos: <?php echo \Illuminate\Support\Js::from($photoUrls)->toHtml() ?>, selectedPhoto: 0 }" aria-labelledby="item-photos-title">
                    <div class="flex items-center justify-between gap-3 p-5 sm:p-6">
                        <div class="flex items-center gap-3">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-blue-50 text-blue-600"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'camera','class' => 'h-5 w-5','ariaHidden' => 'true']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'camera','class' => 'h-5 w-5','aria-hidden' => 'true']); ?>
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
                            <h2 id="item-photos-title" class="text-base font-bold text-slate-900">Item photos</h2>
                        </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($photoUrls->isNotEmpty()): ?>
                            <span class="shrink-0 rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-500" aria-live="polite"><span x-text="selectedPhoto + 1">1</span> / <?php echo e($photoUrls->count()); ?></span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                    <div class="px-5 pb-5 sm:px-6 sm:pb-6">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($photoUrls->isNotEmpty()): ?>
                            <div class="listing-detail-photo aspect-[4/3] overflow-hidden rounded-xl bg-slate-100 p-4">
                                <img src="<?php echo e($photoUrls->first()); ?>" :src="photos[selectedPhoto]" alt="<?php echo e($listing->name); ?> — photo 1" :alt="<?php echo \Illuminate\Support\Js::from($listing->name)->toHtml() ?> + ' — photo ' + (selectedPhoto + 1)" class="h-full w-full object-contain" />
                            </div>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($photoUrls->count() > 1): ?>
                                <div class="mt-4 flex flex-wrap gap-2" aria-label="Choose an item photo">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $photoUrls; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $photoUrl): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <button type="button" @click="selectedPhoto = <?php echo e($loop->index); ?>" :aria-pressed="selectedPhoto === <?php echo e($loop->index); ?>" :class="selectedPhoto === <?php echo e($loop->index); ?> ? 'border-blue-500 ring-2 ring-blue-100' : 'border-slate-200'" aria-label="View item photo <?php echo e($loop->iteration); ?>" class="h-16 w-16 shrink-0 overflow-hidden rounded-xl border-2 bg-slate-50 p-1 transition hover:border-blue-300 focus:outline-none focus:ring-4 focus:ring-blue-100">
                                            <img src="<?php echo e($photoUrl); ?>" alt="<?php echo e($listing->name); ?> thumbnail <?php echo e($loop->iteration); ?>" class="h-full w-full object-contain" loading="lazy" />
                                        </button>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php else: ?>
                            <div class="flex h-64 flex-col items-center justify-center gap-3 rounded-xl bg-slate-100 px-6 text-center sm:h-72">
                                <span class="grid h-16 w-16 place-items-center rounded-2xl bg-white text-slate-400"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'camera','class' => 'h-8 w-8','ariaHidden' => 'true']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'camera','class' => 'h-8 w-8','aria-hidden' => 'true']); ?>
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
                                <p class="text-sm font-semibold text-slate-600">No photos added yet</p>
                                <p class="max-w-xs text-xs leading-5 text-slate-500">Check the item details below for more information.</p>
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </section>

                <?php if (isset($component)) { $__componentOriginal1867f575713b9af70e529098827d30f0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1867f575713b9af70e529098827d30f0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.listing-detail-panel','data' => ['title' => 'About this item','icon' => 'archive','class' => 'listing-detail-about']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('listing-detail-panel'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'About this item','icon' => 'archive','class' => 'listing-detail-about']); ?>
                    <p class="mt-5 whitespace-pre-line text-sm leading-7 text-slate-600"><?php echo e($listing->description); ?></p>
                    <dl class="mt-6 grid gap-5 border-t border-slate-100 pt-5 text-sm sm:grid-cols-2">
                        <div><dt class="flex items-center gap-2 text-xs font-medium text-slate-500"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'map-pin','class' => 'h-4 w-4','ariaHidden' => 'true']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'map-pin','class' => 'h-4 w-4','aria-hidden' => 'true']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?> Location</dt><dd class="mt-2 font-semibold text-slate-800"><?php echo e($listing->location); ?></dd></div>
                        <div><dt class="flex items-center gap-2 text-xs font-medium text-slate-500"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'clipboard-list','class' => 'h-4 w-4','ariaHidden' => 'true']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'clipboard-list','class' => 'h-4 w-4','aria-hidden' => 'true']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?> Maximum rental duration</dt><dd class="mt-2 font-semibold text-slate-800"><?php echo e($listing->max_rental_duration_days); ?> day<?php echo e((int) $listing->max_rental_duration_days === 1 ? '' : 's'); ?></dd></div>
                        <div><dt class="flex items-center gap-2 text-xs font-medium text-slate-500"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'archive','class' => 'h-4 w-4','ariaHidden' => 'true']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'archive','class' => 'h-4 w-4','aria-hidden' => 'true']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?> Pickup & delivery</dt><dd class="mt-2 font-semibold text-slate-800"><?php echo e(collect([$listing->pickup_available ? 'Pickup' : null, $listing->delivery_available ? 'Delivery' : null])->filter()->join(' & ') ?: 'Confirm with the owner'); ?></dd></div>
                        <div><dt class="flex items-center gap-2 text-xs font-medium text-slate-500"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'tag','class' => 'h-4 w-4','ariaHidden' => 'true']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'tag','class' => 'h-4 w-4','aria-hidden' => 'true']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?> Condition</dt><dd class="mt-2 font-semibold text-slate-800"><?php echo e($listing->condition->label()); ?></dd></div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($listing->brand): ?>
                            <div><dt class="text-xs font-medium text-slate-500">Brand</dt><dd class="mt-2 font-semibold text-slate-800"><?php echo e($listing->brand); ?></dd></div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($listing->model): ?>
                            <div><dt class="text-xs font-medium text-slate-500">Model</dt><dd class="mt-2 font-semibold text-slate-800"><?php echo e($listing->model); ?></dd></div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </dl>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($listing->rental_rules): ?>
                        <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4 sm:p-5">
                            <h3 class="flex items-center gap-2 text-sm font-semibold text-amber-900"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'exclamation-triangle','class' => 'h-4 w-4 shrink-0','ariaHidden' => 'true']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'exclamation-triangle','class' => 'h-4 w-4 shrink-0','aria-hidden' => 'true']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?> Rental rules</h3>
                            <p class="mt-2 whitespace-pre-line text-sm leading-6 text-amber-900/80"><?php echo e($listing->rental_rules); ?></p>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1867f575713b9af70e529098827d30f0)): ?>
<?php $attributes = $__attributesOriginal1867f575713b9af70e529098827d30f0; ?>
<?php unset($__attributesOriginal1867f575713b9af70e529098827d30f0); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1867f575713b9af70e529098827d30f0)): ?>
<?php $component = $__componentOriginal1867f575713b9af70e529098827d30f0; ?>
<?php unset($__componentOriginal1867f575713b9af70e529098827d30f0); ?>
<?php endif; ?>

                <?php if (isset($component)) { $__componentOriginal1867f575713b9af70e529098827d30f0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1867f575713b9af70e529098827d30f0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.listing-detail-panel','data' => ['title' => 'Renter reviews','icon' => 'star','class' => 'listing-detail-reviews','id' => 'item-reviews']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('listing-detail-panel'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Renter reviews','icon' => 'star','class' => 'listing-detail-reviews','id' => 'item-reviews']); ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($reviews->isNotEmpty()): ?>
                        <div class="mt-5 flex flex-wrap items-center gap-2 border-b border-slate-100 pb-5 text-sm">
                            <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'star','class' => 'h-5 w-5 fill-amber-400 text-amber-400','ariaHidden' => 'true']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'star','class' => 'h-5 w-5 fill-amber-400 text-amber-400','aria-hidden' => 'true']); ?>
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
                            <span class="text-xl font-bold text-slate-900"><?php echo e(number_format($averageRating, 1)); ?></span>
                            <span class="text-slate-500">from <?php echo e($reviews->count()); ?> review<?php echo e($reviews->count() === 1 ? '' : 's'); ?></span>
                        </div>
                        <div class="divide-y divide-slate-100">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $reviews; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $review): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <article class="py-5 last:pb-0">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div><h3 class="text-sm font-semibold text-slate-800"><?php echo e($review->rental->renter->name); ?></h3><p class="mt-1 text-xs text-slate-500"><?php echo e($review->created_at->format('M d, Y')); ?></p></div>
                                        <span class="flex items-center gap-0.5" role="img" aria-label="<?php echo e($review->rating); ?> out of 5 stars">
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php for($i = 1; $i <= 5; $i++): ?>
                                                <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'star','class' => 'h-4 w-4 '.e($i <= $review->rating ? 'fill-amber-400 text-amber-400' : 'text-slate-300').'','ariaHidden' => 'true']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'star','class' => 'h-4 w-4 '.e($i <= $review->rating ? 'fill-amber-400 text-amber-400' : 'text-slate-300').'','aria-hidden' => 'true']); ?>
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
                                            <?php endfor; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </span>
                                    </div>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($review->comment): ?>
                                        <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-600"><?php echo e($review->comment); ?></p>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </article>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="mt-5 rounded-xl bg-slate-50 p-5 text-sm"><p class="font-medium text-slate-700">No reviews yet</p><p class="mt-1 leading-6 text-slate-500">Reviews from renters will appear here after their rentals.</p></div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1867f575713b9af70e529098827d30f0)): ?>
<?php $attributes = $__attributesOriginal1867f575713b9af70e529098827d30f0; ?>
<?php unset($__attributesOriginal1867f575713b9af70e529098827d30f0); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1867f575713b9af70e529098827d30f0)): ?>
<?php $component = $__componentOriginal1867f575713b9af70e529098827d30f0; ?>
<?php unset($__componentOriginal1867f575713b9af70e529098827d30f0); ?>
<?php endif; ?>
            </div>

            <aside class="listing-detail-sidebar" aria-label="Rental rates, availability, and owner">
                <section class="listing-detail-card listing-detail-booking overflow-hidden rounded-2xl border border-blue-200 bg-white" aria-labelledby="rental-rates-title">
                    <div class="border-b border-blue-100 bg-blue-50/70 p-5 sm:p-6">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h2 id="rental-rates-title" class="text-base font-bold text-slate-900">Rental rates</h2>
                            <?php if (isset($component)) { $__componentOriginal2ddbc40e602c342e508ac696e52f8719 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2ddbc40e602c342e508ac696e52f8719 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.badge','data' => ['color' => $availability->badgeColor()]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($availability->badgeColor())]); ?><?php echo e($availability->label()); ?> <?php echo $__env->renderComponent(); ?>
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
                        <p class="mt-4 flex flex-wrap items-baseline gap-x-2 text-3xl font-extrabold tracking-tight text-slate-900">₱<?php echo e(number_format($listing->price_per_day, 2)); ?> <span class="text-sm font-medium tracking-normal text-slate-500">/ day</span></p>
                    </div>
                    <div class="p-5 sm:p-6">
                        <dl class="space-y-4 text-sm">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($listing->price_per_hour): ?>
                                <div class="flex flex-wrap justify-between gap-x-4 gap-y-1"><dt class="text-slate-500">Per hour</dt><dd class="font-semibold text-slate-800">₱<?php echo e(number_format($listing->price_per_hour, 2)); ?></dd></div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($listing->price_per_week): ?>
                                <div class="flex flex-wrap justify-between gap-x-4 gap-y-1"><dt class="text-slate-500">Per week</dt><dd class="font-semibold text-slate-800">₱<?php echo e(number_format($listing->price_per_week, 2)); ?></dd></div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <div class="flex flex-wrap justify-between gap-x-4 gap-y-1 border-t border-slate-100 pt-4"><dt class="text-slate-500">Security deposit</dt><dd class="font-semibold text-slate-800">₱<?php echo e(number_format($listing->security_deposit, 2)); ?></dd></div>
                        </dl>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! $listing->is_available || ! $listing->hasRequestableDates() || ! $listing->isPublished()): ?>
                            <button type="button" disabled class="mt-6 min-h-12 w-full cursor-not-allowed rounded-xl bg-slate-100 px-4 py-3 text-sm font-semibold text-slate-500">Currently unavailable</button>
                            <p class="mt-3 text-center text-xs leading-5 text-slate-500">This item is paused or has no upcoming available dates.</p>
                        <?php elseif(! auth()->check()): ?>
                            <div class="mt-6 flex items-stretch gap-2">
                                <a href="<?php echo e(route('login')); ?>" wire:navigate class="flex min-h-12 min-w-0 flex-1 items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-100"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'clipboard-list','class' => 'h-4 w-4','ariaHidden' => 'true']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'clipboard-list','class' => 'h-4 w-4','aria-hidden' => 'true']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?> Log in to request</a>
                                <a href="<?php echo e(route('listings.message', $listing)); ?>" wire:navigate aria-label="Message owner" title="Message owner" class="grid min-h-12 w-12 shrink-0 place-items-center rounded-xl border border-blue-200 bg-blue-50 text-blue-600 transition hover:border-blue-300 hover:bg-blue-100 focus:outline-none focus:ring-4 focus:ring-blue-100"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'chat','class' => 'h-5 w-5','ariaHidden' => 'true']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'chat','class' => 'h-5 w-5','aria-hidden' => 'true']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?></a>
                            </div>
                            <p class="mt-3 text-center text-xs leading-5 text-slate-500">Log in to choose your rental dates and send a request.</p>
                        <?php elseif(auth()->id() === $listing->owner_id): ?>
                            <button type="button" disabled class="mt-6 min-h-12 w-full cursor-not-allowed rounded-xl bg-slate-100 px-4 py-3 text-sm font-semibold text-slate-500">This is your listing</button>
                        <?php elseif($listing->isPublished() && auth()->user()->isRenter() && auth()->user()->activeInterface() === 'renter'): ?>
                            <div class="mt-6 flex items-stretch gap-2">
                                <button type="button" x-on:click="previousBodyOverflow = document.body.style.overflow; $refs.rentalRequestModal.showModal(); document.body.style.overflow = 'hidden'" aria-haspopup="dialog" aria-controls="rental-request-modal" class="flex min-h-12 min-w-0 flex-1 items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-100"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'clipboard-list','class' => 'h-4 w-4','ariaHidden' => 'true']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'clipboard-list','class' => 'h-4 w-4','aria-hidden' => 'true']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?> Request to rent</button>
                                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('message', $listing)): ?>
                                    <a href="<?php echo e(route('listings.message', $listing)); ?>" wire:navigate aria-label="Message owner" title="Message owner" class="grid min-h-12 w-12 shrink-0 place-items-center rounded-xl border border-blue-200 bg-blue-50 text-blue-600 transition hover:border-blue-300 hover:bg-blue-100 focus:outline-none focus:ring-4 focus:ring-blue-100"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'chat','class' => 'h-5 w-5','ariaHidden' => 'true']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'chat','class' => 'h-5 w-5','aria-hidden' => 'true']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?></a>
                                <?php endif; ?>
                            </div>
                            <p class="mt-3 text-center text-xs leading-5 text-slate-500">Choose your dates next. Your request needs the owner’s approval.</p>
                        <?php else: ?>
                            <button type="button" disabled class="mt-6 min-h-12 w-full cursor-not-allowed rounded-xl bg-slate-100 px-4 py-3 text-sm font-semibold text-slate-500">Only renters can request items</button>
                            <p class="mt-3 text-center text-xs leading-5 text-slate-500">Use the renter interface to send a rental request.</p>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </section>

                <?php if (isset($component)) { $__componentOriginal1867f575713b9af70e529098827d30f0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1867f575713b9af70e529098827d30f0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.listing-detail-panel','data' => ['title' => 'Availability','icon' => 'clipboard-list','subtitle' => 'Check the calendar before requesting dates.','class' => 'listing-detail-availability']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('listing-detail-panel'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Availability','icon' => 'clipboard-list','subtitle' => 'Check the calendar before requesting dates.','class' => 'listing-detail-availability']); ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($listing->available_from && $listing->available_until): ?>
                        <p class="mt-4 text-sm font-medium text-slate-700">Available <?php echo e($listing->available_from->format('M d, Y')); ?> – <?php echo e($listing->available_until->format('M d, Y')); ?></p>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <div class="mt-5" wire:key="availability-calendar-<?php echo e($listing->id); ?>-<?php echo e(md5($bookedRanges->toJson().$reservedRanges->toJson().$listing->available_from?->toDateString().$listing->available_until?->toDateString())); ?>" x-data="availabilityCalendar(<?php echo \Illuminate\Support\Js::from($bookedRanges)->toHtml() ?>, <?php echo \Illuminate\Support\Js::from($reservedRanges)->toHtml() ?>, <?php echo \Illuminate\Support\Js::from($listing->available_from?->toDateString())->toHtml() ?>, <?php echo \Illuminate\Support\Js::from($listing->available_until?->toDateString())->toHtml() ?>)">
                        <div class="flex items-center justify-between gap-2">
                            <button type="button" @click="prevMonth" aria-label="Previous month" class="grid h-10 w-10 shrink-0 place-items-center rounded-xl border border-slate-200 text-slate-500 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-100"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
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
<?php endif; ?></button>
                            <p class="text-center text-sm font-semibold text-slate-800" x-text="monthLabel" aria-live="polite"></p>
                            <button type="button" @click="nextMonth" aria-label="Next month" class="grid h-10 w-10 shrink-0 place-items-center rounded-xl border border-slate-200 text-slate-500 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-100"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'chevron-down','class' => 'h-4 w-4 -rotate-90','ariaHidden' => 'true']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'chevron-down','class' => 'h-4 w-4 -rotate-90','aria-hidden' => 'true']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?></button>
                        </div>
                        <div class="mb-2 mt-5 grid grid-cols-7 gap-1 text-center text-xs font-medium text-slate-500"><span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span></div>
                        <div class="space-y-1">
                            <template x-for="(week, wi) in weeks" :key="wi">
                                <div class="grid grid-cols-7 gap-1">
                                    <template x-for="(day, di) in week" :key="di">
                                        <div class="flex h-10 items-center justify-center rounded-lg text-xs"
                                            :class="{
                                                'bg-amber-100 font-semibold text-amber-800': day && isReserved(day),
                                                'bg-rose-100 font-semibold text-rose-700': day && isBooked(day) && !isReserved(day),
                                                'text-slate-400': day && isPast(day) && !isBooked(day),
                                                'bg-blue-50 font-medium text-blue-700': day && !isPast(day) && !isBooked(day) && !isOutsideWindow(day) && <?php echo \Illuminate\Support\Js::from((bool) $listing->is_available && $listing->isPublished())->toHtml() ?>,
                                                'bg-slate-100 text-slate-500': day && !isPast(day) && !isBooked(day) && (isOutsideWindow(day) || !<?php echo \Illuminate\Support\Js::from((bool) $listing->is_available && $listing->isPublished())->toHtml() ?>),
                                            }"
                                            :aria-label="day ? day.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) + ': ' + (isReserved(day) ? 'Reserved' : isBooked(day) ? 'On hold' : isPast(day) ? 'Past date' : !isOutsideWindow(day) && <?php echo \Illuminate\Support\Js::from((bool) $listing->is_available && $listing->isPublished())->toHtml() ?> ? 'Available' : 'Unavailable') : null"
                                            x-text="day ? day.getDate() : ''"></div>
                                    </template>
                                </div>
                            </template>
                        </div>
                        <div class="mt-5 flex flex-wrap items-center gap-x-4 gap-y-2 border-t border-slate-100 pt-4 text-xs text-slate-500">
                            <span class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-amber-200" aria-hidden="true"></span> Reserved</span>
                            <span class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-rose-200" aria-hidden="true"></span> On hold</span>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($listing->is_available): ?>
                                <span class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-blue-100" aria-hidden="true"></span> Available</span>
                            <?php else: ?>
                                <span class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-slate-200" aria-hidden="true"></span> Unavailable</span>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <span class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-slate-200" aria-hidden="true"></span> Outside available dates</span>
                        </div>
                        <p class="mt-3 text-xs text-slate-500">Approved requests hold dates. A recorded payment reserves the item for the agreed rental dates.</p>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($paidReservationRanges->isNotEmpty()): ?>
                            <p class="mt-3 text-xs font-semibold text-amber-700">Reserved dates</p>
                            <ul class="mt-3 space-y-1 text-xs font-medium text-amber-700" aria-label="Paid reservation dates">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $paidReservationRanges; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reservation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <li>Reserved <?php echo e($reservation->start_date->format('M d, Y')); ?> – <?php echo e($reservation->end_date->format('M d, Y')); ?></li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </ul>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1867f575713b9af70e529098827d30f0)): ?>
<?php $attributes = $__attributesOriginal1867f575713b9af70e529098827d30f0; ?>
<?php unset($__attributesOriginal1867f575713b9af70e529098827d30f0); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1867f575713b9af70e529098827d30f0)): ?>
<?php $component = $__componentOriginal1867f575713b9af70e529098827d30f0; ?>
<?php unset($__componentOriginal1867f575713b9af70e529098827d30f0); ?>
<?php endif; ?>

                <?php if (isset($component)) { $__componentOriginal1867f575713b9af70e529098827d30f0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1867f575713b9af70e529098827d30f0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.listing-detail-panel','data' => ['title' => 'Listed by','icon' => 'user-circle','class' => 'listing-detail-owner']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('listing-detail-panel'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Listed by','icon' => 'user-circle','class' => 'listing-detail-owner']); ?>
                    <div class="mt-5 flex items-center gap-3">
                        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-blue-100 text-base font-bold text-blue-700" aria-hidden="true"><?php echo e(Str::of($listing->owner->name)->substr(0, 1)->upper()); ?></span>
                        <div class="min-w-0"><p class="text-sm font-semibold text-slate-800"><?php echo e($listing->owner->name); ?></p>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! $listing->owner->trashed() && ! $listing->owner->isAdmin() && ! $listing->owner->isSuspended() && $listing->owner->hasVerifiedEmail()): ?>
                                <a href="<?php echo e(route('users.show', $listing->owner)); ?>" wire:navigate class="text-xs font-semibold text-blue-700 hover:underline">View public profile</a>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($ownerAverageRating !== null): ?>
                                <p class="mt-1 flex flex-wrap items-center gap-1.5 text-xs text-slate-500"><?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'star','class' => 'h-4 w-4 fill-amber-400 text-amber-400','ariaHidden' => 'true']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'star','class' => 'h-4 w-4 fill-amber-400 text-amber-400','aria-hidden' => 'true']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $attributes = $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__attributesOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc)): ?>
<?php $component = $__componentOriginalce262628e3a8d44dc38fd1f3965181bc; ?>
<?php unset($__componentOriginalce262628e3a8d44dc38fd1f3965181bc); ?>
<?php endif; ?><span class="font-semibold text-slate-700"><?php echo e(number_format($ownerAverageRating, 1)); ?></span> owner rating</p>
                            <?php else: ?>
                                <p class="mt-1 text-xs text-slate-500">No owner ratings yet</p>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </div>
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1867f575713b9af70e529098827d30f0)): ?>
<?php $attributes = $__attributesOriginal1867f575713b9af70e529098827d30f0; ?>
<?php unset($__attributesOriginal1867f575713b9af70e529098827d30f0); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1867f575713b9af70e529098827d30f0)): ?>
<?php $component = $__componentOriginal1867f575713b9af70e529098827d30f0; ?>
<?php unset($__componentOriginal1867f575713b9af70e529098827d30f0); ?>
<?php endif; ?>
            </aside>
        </div>
    </div>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($listing->isPublished() && $listing->is_available && $listing->hasRequestableDates() && auth()->check() && auth()->id() !== $listing->owner_id && auth()->user()->isRenter() && auth()->user()->activeInterface() === 'renter'): ?>
        <dialog
            id="rental-request-modal"
            x-ref="rentalRequestModal"
            aria-labelledby="rental-request-title"
            aria-describedby="rental-request-description"
            x-on:close-rental-request.stop="$el.close()"
            x-on:close="document.body.style.overflow = previousBodyOverflow"
            x-on:livewire:navigating.window="if ($el.open) { $el.close(); document.body.style.overflow = previousBodyOverflow; }"
            x-on:click="if ($event.target === $el) { const bounds = $el.getBoundingClientRect(); if ($event.clientX < bounds.left || $event.clientX > bounds.right || $event.clientY < bounds.top || $event.clientY > bounds.bottom) $el.close(); }"
            class="m-auto max-h-[calc(100dvh-2rem)] w-[calc(100%-2rem)] max-w-2xl overflow-y-auto rounded-2xl border-0 bg-slate-50 p-0 text-slate-900 shadow-xl backdrop:bg-slate-900/50"
        >
            <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('rental-requests.create', ['listing' => $listing,'modal' => true]);

$__key = 'rental-request-'.$listing->id;

$__key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-2719478901-0', $__key);

$__html = app('livewire')->mount($__name, $__params, $__key);

echo $__html;

unset($__html);
unset($__key);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
        </dialog>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>
<?php /**PATH C:\laragon\www\lendly\resources\views/livewire/listings/show.blade.php ENDPATH**/ ?>