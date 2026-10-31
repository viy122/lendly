<div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8" wire:poll.10s>
    <a href="<?php echo e(route(($isOwner ? 'owner' : 'renter').'.rental-requests.index')); ?>" wire:navigate class="text-sm font-medium text-blue-600">Back to rental requests</a>
    <h1 class="mt-4 text-xl font-semibold text-slate-900">Review rental agreement</h1>
    <p class="mt-2 text-sm text-slate-600">Owner: <?php echo e($request->listing->owner->name); ?> &middot; Renter: <?php echo e($request->renter->name); ?></p>

    <?php if (isset($component)) { $__componentOriginalf94ed9c5393ef72725d159fe01139746 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf94ed9c5393ef72725d159fe01139746 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-error','data' => ['messages' => $errors->get('agreement'),'class' => 'mt-4','role' => 'alert']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['messages' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->get('agreement')),'class' => 'mt-4','role' => 'alert']); ?>
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

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! $request->isApproved()): ?>
        <p class="mt-6 rounded-lg bg-rose-50 p-4 text-sm text-rose-700" role="alert">This request is <?php echo e(strtolower($request->status->label())); ?>. Its agreement can no longer be accepted.</p>
    <?php else: ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($request->agreement_terms): ?>
            <section class="mt-6" aria-label="Agreement preview">
                <?php if (isset($component)) { $__componentOriginal890b9b03cad434ff11052c98022e51d9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal890b9b03cad434ff11052c98022e51d9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.rental-agreement','data' => ['terms' => $request->agreement_terms,'rentalRequest' => $request]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('rental-agreement'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['terms' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($request->agreement_terms),'rental-request' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($request)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal890b9b03cad434ff11052c98022e51d9)): ?>
<?php $attributes = $__attributesOriginal890b9b03cad434ff11052c98022e51d9; ?>
<?php unset($__attributesOriginal890b9b03cad434ff11052c98022e51d9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal890b9b03cad434ff11052c98022e51d9)): ?>
<?php $component = $__componentOriginal890b9b03cad434ff11052c98022e51d9; ?>
<?php unset($__componentOriginal890b9b03cad434ff11052c98022e51d9); ?>
<?php endif; ?>
            </section>
        <?php else: ?>
            <p class="mt-6 text-sm text-rose-700" role="alert">This request has no saved rental agreement. The renter must cancel this request and submit a new one for review.</p>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <section class="mt-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm" aria-live="polite">
            <p class="text-sm text-slate-700">Owner: <?php echo e($request->owner_terms_accepted_at ? 'Accepted' : 'Awaiting acceptance'); ?></p>
            <p class="mt-2 text-sm text-slate-700">Renter: <?php echo e($request->renter_terms_accepted_at ? 'Accepted' : 'Awaiting acceptance'); ?></p>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($request->rental): ?>
                <p class="mt-4 font-medium text-emerald-700">Booking confirmed</p>
                <a href="<?php echo e(route(($isOwner ? 'owner' : 'renter').'.rentals.show', $request->rental)); ?>" wire:navigate class="mt-4 inline-flex text-sm font-medium text-blue-600"><?php echo e($isOwner ? 'View booking' : 'View booking and pay'); ?></a>
            <?php elseif($isOwner ? $request->owner_terms_accepted_at : $request->renter_terms_accepted_at): ?>
                <p class="mt-4 text-sm text-blue-700">You have accepted. Waiting for the other party to accept before finalizing the booking.</p>
            <?php elseif($request->agreement_terms): ?>
                <form wire:submit="acceptTerms" class="mt-4">
                    <label class="flex items-start gap-2 text-sm text-slate-700">
                        <input type="checkbox" wire:model="accept_terms" class="mt-0.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <span>I have read and accept this rental agreement and all its terms and conditions, including the cancellation policy and fees, item rules and deposit conditions.</span>
                    </label>
                    <?php if (isset($component)) { $__componentOriginalf94ed9c5393ef72725d159fe01139746 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf94ed9c5393ef72725d159fe01139746 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-error','data' => ['messages' => $errors->get('accept_terms'),'class' => 'mt-2','role' => 'alert']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['messages' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->get('accept_terms')),'class' => 'mt-2','role' => 'alert']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.primary-button','data' => ['class' => 'mt-4','wire:loading.attr' => 'disabled','wire:target' => 'acceptTerms']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('primary-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'mt-4','wire:loading.attr' => 'disabled','wire:target' => 'acceptTerms']); ?>Accept rental agreement <?php echo $__env->renderComponent(); ?>
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
        </section>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>
<?php /**PATH C:\laragon\www\lendly\resources\views/livewire/rental-requests/agreement.blade.php ENDPATH**/ ?>