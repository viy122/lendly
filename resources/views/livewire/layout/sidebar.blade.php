<?php

use App\Livewire\Actions\Logout;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }

    /**
     * Demo-only quick account switcher (local env, see the gate in the
     * view) — logs straight into the seeded admin/member accounts so both
     * account types can be demoed without repeatedly typing credentials.
     */
    public function switchDemoUser(string $role): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $user = User::where('email', "{$role}@tala.test")->first();

        if (! $user) {
            return;
        }

        Auth::login($user);
        session()->regenerate();

        $this->redirect(route($user->dashboardRouteName()), navigate: true);
    }
}; ?>

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
        <div class="flex h-24 items-center justify-between border-b border-indigo-900 px-4" :class="collapsed && 'lg:justify-center lg:px-0'">
            <a href="{{ auth()->check() ? route(auth()->user()->dashboardRouteName()) : route('listings.index') }}" wire:navigate class="flex items-center gap-2">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-gradient-to-br from-blue-500 to-indigo-600 text-[10px] font-bold text-white">L</span>
                <span class="text-sm font-semibold tracking-tight text-white" x-show="!collapsed" x-cloak x-transition.opacity.duration.100ms>Lendly</span>
            </a>
            <button @click="open = false" class="rounded-md p-1 text-indigo-300 hover:bg-indigo-900 hover:text-white lg:hidden">
                <x-icon name="x-mark" class="h-4 w-4" />
            </button>
        </div>

        <button
            type="button"
            @click="toggleCollapsed()"
            class="absolute -right-3 top-7 z-10 hidden h-6 w-6 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-400 shadow-sm hover:text-blue-600 lg:flex"
            :title="collapsed ? 'Expand sidebar' : 'Collapse sidebar'">
            <x-icon name="chevron-down" class="h-3.5 w-3.5 transition-transform" x-bind:class="{ '-rotate-90': !collapsed, 'rotate-90': collapsed }" />
        </button>

        @if (app()->environment('local'))
        <div class="border-b border-indigo-900 px-3 py-3" x-show="!collapsed" x-cloak x-transition>
            <p class="px-1 pb-1.5 text-xs font-semibold uppercase tracking-wider text-indigo-400">Demo: switch user</p>
            <div class="grid grid-cols-2 gap-1 rounded-lg bg-indigo-900 p-1">
                @foreach (['admin' => 'Admin', 'owner' => 'Renter/Owner'] as $role => $label)
                <button
                    type="button"
                    wire:click="switchDemoUser('{{ $role }}')"
                    @class([ 'rounded-md px-2 py-1.5 text-xs font-semibold transition' , 'bg-white text-blue-700 shadow-sm'=> auth()->check() && auth()->user()->email === "{$role}@tala.test",
                    'text-indigo-300 hover:text-white' => ! (auth()->check() && auth()->user()->email === "{$role}@tala.test"),
                    ])
                    >
                    {{ $label }}
                </button>
                @endforeach
            </div>
        </div>
        @endif

        <nav class="flex-1 space-y-1 overflow-y-auto overflow-x-hidden px-3 py-4">
            <x-sidebar-link :href="route('listings.index')" :active="request()->routeIs('listings.*')" icon="search" wire:navigate>
                {{ __('Browse') }}
            </x-sidebar-link>
            <x-sidebar-link :href="route('map')" :active="request()->routeIs('map')" icon="map-pin" wire:navigate>
                {{ __('Map') }}
            </x-sidebar-link>

            @auth
            <x-sidebar-link :href="route(auth()->user()->dashboardRouteName())" :active="request()->routeIs('*.dashboard') || request()->routeIs('dashboard')" icon="home" wire:navigate>
                {{ __('Dashboard') }}
            </x-sidebar-link>

            @if (auth()->user()->isRenter() || auth()->user()->isOwner())
            <x-sidebar-link :href="route('messages.index')" :active="request()->routeIs('messages.*')" icon="chat" wire:navigate>
                {{ __('Messages') }}
                @if (auth()->user()->unreadMessagesCount() > 0)
                <x-slot:badge>{{ auth()->user()->unreadMessagesCount() > 9 ? '9+' : auth()->user()->unreadMessagesCount() }}</x-slot:badge>
                @endif
            </x-sidebar-link>
            @endif

            @if (auth()->user()->isRenter())
            <p class="flex items-center gap-1.5 px-3 pb-1 pt-4 text-xs font-semibold uppercase tracking-wider text-indigo-400" x-show="!collapsed" x-cloak>
                <span class="h-1.5 w-1.5 rounded-full bg-blue-400"></span> Renting
            </p>
            <x-sidebar-link :href="route('renter.rental-requests.index')" :active="request()->routeIs('renter.rental-requests.*')" icon="inbox" wire:navigate>
                {{ __('My Requests') }}
            </x-sidebar-link>
            <x-sidebar-link :href="route('renter.rentals.index')" :active="request()->routeIs('renter.rentals.*')" icon="archive" wire:navigate>
                {{ __('My Rentals') }}
            </x-sidebar-link>
            @endif

            @if (auth()->user()->isOwner())
            <p class="flex items-center gap-1.5 px-3 pb-1 pt-4 text-xs font-semibold uppercase tracking-wider text-indigo-400" x-show="!collapsed" x-cloak>
                <span class="h-1.5 w-1.5 rounded-full bg-violet-400"></span> Owning
            </p>
            <x-sidebar-link :href="route('owner.listings.index')" :active="request()->routeIs('owner.listings.*')" icon="tag" wire:navigate>
                {{ __('My Listings') }}
            </x-sidebar-link>
            <x-sidebar-link :href="route('owner.rental-requests.index')" :active="request()->routeIs('owner.rental-requests.*')" icon="inbox" wire:navigate>
                {{ __('Rental Requests') }}
            </x-sidebar-link>
            <x-sidebar-link :href="route('owner.rentals.index')" :active="request()->routeIs('owner.rentals.*')" icon="archive" wire:navigate>
                {{ __('My Rentals') }}
            </x-sidebar-link>
            @endif

            @if (auth()->user()->isAdmin())
            <p class="flex items-center gap-1.5 px-3 pb-1 pt-4 text-xs font-semibold uppercase tracking-wider text-indigo-400" x-show="!collapsed" x-cloak>
                <span class="h-1.5 w-1.5 rounded-full bg-rose-400"></span> Administration
            </p>
            <x-sidebar-link :href="route('admin.listings.index')" :active="request()->routeIs('admin.listings.*')" icon="list" wire:navigate>
                {{ __('Listings') }}
            </x-sidebar-link>
            <x-sidebar-link :href="route('admin.rentals.index')" :active="request()->routeIs('admin.rentals.*')" icon="archive" wire:navigate>
                {{ __('Transactions') }}
            </x-sidebar-link>
            <x-sidebar-link :href="route('admin.damage-reports.index')" :active="request()->routeIs('admin.damage-reports.*')" icon="exclamation-triangle" wire:navigate>
                {{ __('Damage Reports') }}
            </x-sidebar-link>
            <x-sidebar-link :href="route('admin.disputes.index')" :active="request()->routeIs('admin.disputes.*')" icon="scale" wire:navigate>
                {{ __('Disputes') }}
            </x-sidebar-link>
            @endif
            @endauth
        </nav>

        <div class="border-t border-indigo-900 p-3">
            @auth
            <div x-show="!collapsed" x-cloak>
                <x-dropdown align="top" width="64">
                    <x-slot name="trigger">
                        <button class="flex w-full items-center gap-3 rounded-lg px-2 py-2 text-left hover:bg-indigo-900">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-blue-100 text-sm font-semibold text-blue-700">
                                @if (auth()->user()->avatar_path)
                                <img src="{{ auth()->user()->avatarUrl() }}" class="h-full w-full object-cover">
                                @else
                                {{ Str::of(auth()->user()->name)->substr(0, 1)->upper() }}
                                @endif
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium text-white" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></span>
                                <span class="block truncate text-xs text-indigo-300">{{ auth()->user()->email }}</span>
                            </span>
                            <x-icon name="chevron-down" class="h-4 w-4 shrink-0 text-indigo-400" />
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile')" wire:navigate>
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <button wire:click="logout" class="w-full text-start">
                            <x-dropdown-link>
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </button>
                    </x-slot>
                </x-dropdown>
            </div>

            <a href="{{ route('profile') }}" wire:navigate x-show="collapsed" x-cloak class="hidden items-center justify-center rounded-full p-1 hover:bg-indigo-900" title="{{ auth()->user()->name }}">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-blue-100 text-sm font-semibold text-blue-700">
                    @if (auth()->user()->avatar_path)
                    <img src="{{ auth()->user()->avatarUrl() }}" class="h-full w-full object-cover">
                    @else
                    {{ Str::of(auth()->user()->name)->substr(0, 1)->upper() }}
                    @endif
                </span>
            </a>
            @else
            <div class="space-y-2" x-show="!collapsed" x-cloak>
                <a href="{{ route('login') }}" wire:navigate class="block rounded-lg border border-indigo-700 px-4 py-2 text-center text-sm font-semibold text-white hover:bg-indigo-900">
                    {{ __('Log in') }}
                </a>
                <a href="{{ route('register') }}" wire:navigate class="block rounded-lg bg-blue-600 px-4 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                    {{ __('Get started') }}
                </a>
            </div>

            <a href="{{ route('login') }}" wire:navigate x-show="collapsed" x-cloak class="hidden items-center justify-center rounded-lg p-2 text-indigo-300 hover:bg-indigo-900" title="{{ __('Log in') }}">
                <x-icon name="user-circle" class="h-6 w-6" />
            </a>
            @endauth
        </div>
    </aside>
</div>