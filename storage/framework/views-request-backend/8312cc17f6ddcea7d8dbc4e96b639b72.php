<?php

use App\Livewire\Actions\Logout;
use App\Models\User;
use App\Support\AccountLockout;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

?>

<div
    x-data="{
        open: false,
        collapsed: localStorage.getItem('sidebar-collapsed') === 'true',
        toggleCollapsed() {
            this.collapsed = ! this.collapsed;
            localStorage.setItem('sidebar-collapsed', this.collapsed);
        },
    }"
    @toggle-sidebar.window="open = ! open"
    @close-sidebar.window="open = false">
    <!-- Mobile overlay -->
    <div x-show="open" x-transition.opacity @click="open = false" class="fixed inset-0 z-30 bg-slate-900/50 lg:hidden" style="display: none;"></div>

    <aside
        :class="[open ? 'translate-x-0' : '-translate-x-full lg:translate-x-0', collapsed ? 'lg:w-20' : 'lg:w-72']"
        class="fixed inset-y-0 left-0 z-40 flex w-72 shrink-0 flex-col border-r border-indigo-900 bg-gradient-to-b from-indigo-950 to-indigo-900 transition-all duration-200 ease-in-out lg:sticky lg:top-0 lg:h-screen lg:translate-x-0">
        <div class="relative flex h-24 items-center justify-between border-b border-indigo-900 px-4" :class="collapsed && 'lg:justify-center lg:px-0'">
            <a href="<?php echo e(auth()->check() ? route(auth()->user()->dashboardRouteName()) : route('listings.index')); ?>" wire:navigate class="group flex items-center gap-2.5" aria-label="Lendly dashboard">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-white/80 bg-white p-0.5 shadow-[0_5px_16px_rgba(37,99,235,0.35)] transition duration-200 group-hover:-translate-y-0.5 group-hover:shadow-[0_8px_20px_rgba(59,130,246,0.45)]">
                    <img src="<?php echo e(asset('images/lendlylogo_transparent.png')); ?>" alt="Lendly logo" class="h-full w-full scale-[1.35] object-contain" />
                </span>
                <span class="text-sm font-semibold tracking-tight text-white" x-show="!collapsed" x-cloak x-transition.opacity.duration.100ms><?php echo e(auth()->check() && ! auth()->user()->isAdmin() ? ucfirst(auth()->user()->activeInterface()).' interface' : 'Rent · Share · Earn'); ?></span>
            </a>
            <button @click="open = false" class="rounded-md p-1 text-indigo-300 hover:bg-indigo-900 hover:text-white lg:hidden" aria-label="Close sidebar">
                <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'x-mark','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'x-mark','class' => 'h-4 w-4']); ?>
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
        </div>

        <button
            type="button"
            @click="toggleCollapsed()"
            class="absolute -right-3 top-7 z-10 hidden h-6 w-6 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-400 shadow-sm hover:text-blue-600 lg:flex"
            :title="collapsed ? 'Expand sidebar' : 'Collapse sidebar'">
            <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'chevron-down','class' => 'h-3.5 w-3.5 transition-transform','xBind:class' => '{ \'-rotate-90\': !collapsed, \'rotate-90\': collapsed }']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'chevron-down','class' => 'h-3.5 w-3.5 transition-transform','x-bind:class' => '{ \'-rotate-90\': !collapsed, \'rotate-90\': collapsed }']); ?>
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

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(app()->environment('local') && auth()->user()?->isAdmin()): ?>
            <div class="border-b border-indigo-900 px-3 py-3" x-show="!collapsed" x-cloak>
                <p class="pb-2 text-xs font-semibold uppercase tracking-wider text-indigo-400">Demo: switch user</p>
                <div class="grid grid-cols-2 gap-2">
                    <button type="button" wire:click="switchDemoUser('admin')" class="min-h-10 rounded-lg bg-indigo-800 px-3 text-sm text-white">Admin</button>
                    <button type="button" wire:click="switchDemoUser('owner')" class="min-h-10 rounded-lg bg-indigo-800 px-3 text-sm text-white">Member</button>
                </div>
                <?php if (isset($component)) { $__componentOriginalf94ed9c5393ef72725d159fe01139746 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf94ed9c5393ef72725d159fe01139746 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-error','data' => ['messages' => $errors->get('account'),'class' => 'mt-2']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['messages' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($errors->get('account')),'class' => 'mt-2']); ?>
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
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <nav class="flex-1 space-y-1 overflow-y-auto overflow-x-hidden px-3 py-4">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! auth()->check() || auth()->user()->isAdmin() || auth()->user()->activeInterface() === 'renter'): ?>
            <?php if (isset($component)) { $__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.sidebar-link','data' => ['href' => route('listings.index'),'active' => request()->routeIs('listings.*') && ! request()->routeIs('listings.message', 'listings.contact'),'icon' => 'search','wire:navigate' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('sidebar-link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('listings.index')),'active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request()->routeIs('listings.*') && ! request()->routeIs('listings.message', 'listings.contact')),'icon' => 'search','wire:navigate' => true]); ?>
                <?php echo e(__('Browse')); ?>

             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300)): ?>
<?php $attributes = $__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300; ?>
<?php unset($__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300)): ?>
<?php $component = $__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300; ?>
<?php unset($__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.sidebar-link','data' => ['href' => route('map'),'active' => request()->routeIs('map'),'icon' => 'map-pin','wire:navigate' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('sidebar-link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('map')),'active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request()->routeIs('map')),'icon' => 'map-pin','wire:navigate' => true]); ?>
                <?php echo e(__('Map')); ?>

             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300)): ?>
<?php $attributes = $__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300; ?>
<?php unset($__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300)): ?>
<?php $component = $__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300; ?>
<?php unset($__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300); ?>
<?php endif; ?>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->guard()->check()): ?>
            <?php if (isset($component)) { $__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.sidebar-link','data' => ['href' => route(auth()->user()->dashboardRouteName()),'active' => request()->routeIs('*.dashboard') || request()->routeIs('dashboard'),'icon' => 'home','wire:navigate' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('sidebar-link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route(auth()->user()->dashboardRouteName())),'active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request()->routeIs('*.dashboard') || request()->routeIs('dashboard')),'icon' => 'home','wire:navigate' => true]); ?>
                <?php echo e(__('Dashboard')); ?>

             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300)): ?>
<?php $attributes = $__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300; ?>
<?php unset($__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300)): ?>
<?php $component = $__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300; ?>
<?php unset($__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300); ?>
<?php endif; ?>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->isRenter() || auth()->user()->isOwner()): ?>
            <?php if (isset($component)) { $__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.sidebar-link','data' => ['href' => route('messages.index'),'active' => request()->routeIs('messages.*', 'listings.message', 'listings.contact', 'listing-conversations.show', 'rental-requests.chat'),'icon' => 'chat','wire:navigate' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('sidebar-link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('messages.index')),'active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request()->routeIs('messages.*', 'listings.message', 'listings.contact', 'listing-conversations.show', 'rental-requests.chat')),'icon' => 'chat','wire:navigate' => true]); ?>
                <?php echo e(__('Messages')); ?>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->unreadMessagesCount() > 0): ?>
                 <?php $__env->slot('badge', null, []); ?> <?php echo e(auth()->user()->unreadMessagesCount() > 9 ? '9+' : auth()->user()->unreadMessagesCount()); ?> <?php $__env->endSlot(); ?>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300)): ?>
<?php $attributes = $__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300; ?>
<?php unset($__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300)): ?>
<?php $component = $__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300; ?>
<?php unset($__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300); ?>
<?php endif; ?>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->activeInterface() === 'renter'): ?>
            <p class="flex items-center gap-1.5 px-3 pb-1 pt-4 text-xs font-semibold uppercase tracking-wider text-indigo-400" x-show="!collapsed" x-cloak>
                <span class="h-1.5 w-1.5 rounded-full bg-blue-400"></span> Renting
            </p>
            <?php if (isset($component)) { $__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.sidebar-link','data' => ['href' => route('renter.cart.index'),'active' => request()->routeIs('renter.cart.*'),'icon' => 'cart','wire:navigate' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('sidebar-link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('renter.cart.index')),'active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request()->routeIs('renter.cart.*')),'icon' => 'cart','wire:navigate' => true]); ?>
                <?php echo e(__('My Cart')); ?>

             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300)): ?>
<?php $attributes = $__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300; ?>
<?php unset($__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300)): ?>
<?php $component = $__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300; ?>
<?php unset($__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.sidebar-link','data' => ['href' => route('renter.rental-requests.index'),'active' => request()->routeIs('renter.rental-requests.*'),'icon' => 'inbox','wire:navigate' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('sidebar-link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('renter.rental-requests.index')),'active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request()->routeIs('renter.rental-requests.*')),'icon' => 'inbox','wire:navigate' => true]); ?>
                <?php echo e(__('My Requests')); ?>

             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300)): ?>
<?php $attributes = $__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300; ?>
<?php unset($__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300)): ?>
<?php $component = $__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300; ?>
<?php unset($__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.sidebar-link','data' => ['href' => route('renter.rentals.index'),'active' => request()->routeIs('renter.rentals.*'),'icon' => 'archive','wire:navigate' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('sidebar-link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('renter.rentals.index')),'active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request()->routeIs('renter.rentals.*')),'icon' => 'archive','wire:navigate' => true]); ?>
                <?php echo e(__('My Rentals')); ?>

             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300)): ?>
<?php $attributes = $__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300; ?>
<?php unset($__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300)): ?>
<?php $component = $__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300; ?>
<?php unset($__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300); ?>
<?php endif; ?>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->activeInterface() === 'owner'): ?>
            <p class="flex items-center gap-1.5 px-3 pb-1 pt-4 text-xs font-semibold uppercase tracking-wider text-indigo-400" x-show="!collapsed" x-cloak>
                <span class="h-1.5 w-1.5 rounded-full bg-violet-400"></span> Owning
            </p>
            <?php if (isset($component)) { $__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.sidebar-link','data' => ['href' => route('owner.listings.index'),'active' => request()->routeIs('owner.listings.*'),'icon' => 'tag','wire:navigate' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('sidebar-link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('owner.listings.index')),'active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request()->routeIs('owner.listings.*')),'icon' => 'tag','wire:navigate' => true]); ?>
                <?php echo e(__('My Listings')); ?>

             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300)): ?>
<?php $attributes = $__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300; ?>
<?php unset($__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300)): ?>
<?php $component = $__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300; ?>
<?php unset($__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.sidebar-link','data' => ['href' => route('owner.rental-requests.index'),'active' => request()->routeIs('owner.rental-requests.*'),'icon' => 'inbox','wire:navigate' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('sidebar-link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('owner.rental-requests.index')),'active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request()->routeIs('owner.rental-requests.*')),'icon' => 'inbox','wire:navigate' => true]); ?>
                <?php echo e(__('Rental Requests')); ?>

             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300)): ?>
<?php $attributes = $__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300; ?>
<?php unset($__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300)): ?>
<?php $component = $__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300; ?>
<?php unset($__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.sidebar-link','data' => ['href' => route('owner.rentals.index'),'active' => request()->routeIs('owner.rentals.*'),'icon' => 'archive','wire:navigate' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('sidebar-link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('owner.rentals.index')),'active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request()->routeIs('owner.rentals.*')),'icon' => 'archive','wire:navigate' => true]); ?>
                <?php echo e(__('My Rentals')); ?>

             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300)): ?>
<?php $attributes = $__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300; ?>
<?php unset($__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300)): ?>
<?php $component = $__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300; ?>
<?php unset($__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.sidebar-link','data' => ['href' => route('owner.payments.settings'),'active' => request()->routeIs('owner.payments.*'),'icon' => 'calculator','wire:navigate' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('sidebar-link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('owner.payments.settings')),'active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request()->routeIs('owner.payments.*')),'icon' => 'calculator','wire:navigate' => true]); ?>
                <?php echo e(__('Payment QR')); ?>

             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300)): ?>
<?php $attributes = $__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300; ?>
<?php unset($__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300)): ?>
<?php $component = $__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300; ?>
<?php unset($__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300); ?>
<?php endif; ?>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->isAdmin()): ?>
            <p class="flex items-center gap-1.5 px-3 pb-1 pt-4 text-xs font-semibold uppercase tracking-wider text-indigo-400" x-show="!collapsed" x-cloak>
                <span class="h-1.5 w-1.5 rounded-full bg-rose-400"></span> Administration
            </p>
            <?php if (isset($component)) { $__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.sidebar-link','data' => ['href' => route('admin.listings.index'),'active' => request()->routeIs('admin.listings.*'),'icon' => 'list','wire:navigate' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('sidebar-link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.listings.index')),'active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request()->routeIs('admin.listings.*')),'icon' => 'list','wire:navigate' => true]); ?>
                <?php echo e(__('Listings')); ?>

             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300)): ?>
<?php $attributes = $__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300; ?>
<?php unset($__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300)): ?>
<?php $component = $__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300; ?>
<?php unset($__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.sidebar-link','data' => ['href' => route('admin.rentals.index'),'active' => request()->routeIs('admin.rentals.*'),'icon' => 'archive','wire:navigate' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('sidebar-link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.rentals.index')),'active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request()->routeIs('admin.rentals.*')),'icon' => 'archive','wire:navigate' => true]); ?>
                <?php echo e(__('Transactions')); ?>

             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300)): ?>
<?php $attributes = $__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300; ?>
<?php unset($__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300)): ?>
<?php $component = $__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300; ?>
<?php unset($__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.sidebar-link','data' => ['href' => route('admin.damage-reports.index'),'active' => request()->routeIs('admin.damage-reports.*'),'icon' => 'exclamation-triangle','wire:navigate' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('sidebar-link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.damage-reports.index')),'active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request()->routeIs('admin.damage-reports.*')),'icon' => 'exclamation-triangle','wire:navigate' => true]); ?>
                <?php echo e(__('Damage Reports')); ?>

             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300)): ?>
<?php $attributes = $__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300; ?>
<?php unset($__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300)): ?>
<?php $component = $__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300; ?>
<?php unset($__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.sidebar-link','data' => ['href' => route('admin.disputes.index'),'active' => request()->routeIs('admin.disputes.*'),'icon' => 'scale','wire:navigate' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('sidebar-link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.disputes.index')),'active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request()->routeIs('admin.disputes.*')),'icon' => 'scale','wire:navigate' => true]); ?>
                <?php echo e(__('Disputes')); ?>

             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300)): ?>
<?php $attributes = $__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300; ?>
<?php unset($__attributesOriginal3d3185cbc95d2b4d3b41182ae7d7a300); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300)): ?>
<?php $component = $__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300; ?>
<?php unset($__componentOriginal3d3185cbc95d2b4d3b41182ae7d7a300); ?>
<?php endif; ?>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </nav>

        <div class="border-t border-indigo-900 p-3">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->guard()->check()): ?>
            <div>
                <?php if (isset($component)) { $__componentOriginaldf8083d4a852c446488d8d384bbc7cbe = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldf8083d4a852c446488d8d384bbc7cbe = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dropdown','data' => ['align' => 'top','width' => '64']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dropdown'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['align' => 'top','width' => '64']); ?>
                     <?php $__env->slot('trigger', null, []); ?> 
                        <button type="button" aria-label="<?php echo e(__('Account menu')); ?>" :aria-expanded="open" class="flex w-full items-center gap-3 rounded-lg px-2 py-2 text-left hover:bg-indigo-900" :class="collapsed && 'justify-center px-0'">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-blue-100 text-sm font-semibold text-blue-700">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->avatar_path): ?>
                                <img src="<?php echo e(auth()->user()->avatarUrl()); ?>" class="h-full w-full object-cover">
                                <?php else: ?>
                                <?php echo e(Str::of(auth()->user()->name)->substr(0, 1)->upper()); ?>

                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </span>
                            <span class="min-w-0 flex-1" x-show="!collapsed" x-cloak>
                                <span class="block truncate text-sm font-medium text-white" x-data="<?php echo e(json_encode(['name' => auth()->user()->name])); ?>" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></span>
                                <span class="block truncate text-xs text-indigo-300"><?php echo e(auth()->user()->email); ?></span>
                            </span>
                            <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'chevron-down','class' => 'h-4 w-4 shrink-0 text-indigo-400','xShow' => '!collapsed','xCloak' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'chevron-down','class' => 'h-4 w-4 shrink-0 text-indigo-400','x-show' => '!collapsed','x-cloak' => true]); ?>
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
                     <?php $__env->endSlot(); ?>

                     <?php $__env->slot('content', null, []); ?> 
                        <?php if (isset($component)) { $__componentOriginal68cb1971a2b92c9735f83359058f7108 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal68cb1971a2b92c9735f83359058f7108 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dropdown-link','data' => ['href' => route('profile'),'wire:navigate' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dropdown-link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('profile')),'wire:navigate' => true]); ?>
                            <?php echo e(__('Profile')); ?>

                         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal68cb1971a2b92c9735f83359058f7108)): ?>
<?php $attributes = $__attributesOriginal68cb1971a2b92c9735f83359058f7108; ?>
<?php unset($__attributesOriginal68cb1971a2b92c9735f83359058f7108); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal68cb1971a2b92c9735f83359058f7108)): ?>
<?php $component = $__componentOriginal68cb1971a2b92c9735f83359058f7108; ?>
<?php unset($__componentOriginal68cb1971a2b92c9735f83359058f7108); ?>
<?php endif; ?>

                        <button type="button" wire:click="logout" class="block w-full px-4 py-2 text-start text-sm leading-5 text-slate-700 transition duration-150 ease-in-out hover:bg-blue-50 hover:text-blue-700 focus:bg-blue-50 focus:outline-none">
                            <?php echo e(__('Log Out')); ?>

                        </button>
                     <?php $__env->endSlot(); ?>
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginaldf8083d4a852c446488d8d384bbc7cbe)): ?>
<?php $attributes = $__attributesOriginaldf8083d4a852c446488d8d384bbc7cbe; ?>
<?php unset($__attributesOriginaldf8083d4a852c446488d8d384bbc7cbe); ?>
<?php endif; ?>
<?php if (isset($__componentOriginaldf8083d4a852c446488d8d384bbc7cbe)): ?>
<?php $component = $__componentOriginaldf8083d4a852c446488d8d384bbc7cbe; ?>
<?php unset($__componentOriginaldf8083d4a852c446488d8d384bbc7cbe); ?>
<?php endif; ?>
            </div>

            <?php else: ?>
            <div class="space-y-2" x-show="!collapsed" x-cloak>
                <a href="<?php echo e(route('login')); ?>" wire:navigate class="block rounded-lg border border-indigo-700 px-4 py-2 text-center text-sm font-semibold text-white hover:bg-indigo-900">
                    <?php echo e(__('Log in')); ?>

                </a>
                <a href="<?php echo e(route('register')); ?>" wire:navigate class="block rounded-lg bg-blue-600 px-4 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                    <?php echo e(__('Get started')); ?>

                </a>
            </div>

            <a href="<?php echo e(route('login')); ?>" wire:navigate x-show="collapsed" x-cloak class="hidden items-center justify-center rounded-lg p-2 text-indigo-300 hover:bg-indigo-900" title="<?php echo e(__('Log in')); ?>">
                <?php if (isset($component)) { $__componentOriginalce262628e3a8d44dc38fd1f3965181bc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalce262628e3a8d44dc38fd1f3965181bc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.icon','data' => ['name' => 'user-circle','class' => 'h-6 w-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'user-circle','class' => 'h-6 w-6']); ?>
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
            </a>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </aside>
</div><?php /**PATH C:\laragon\www\lendly\resources\views\livewire/layout/sidebar.blade.php ENDPATH**/ ?>