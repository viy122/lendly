@props(['show' => false])

@if ($show)
    <dialog
        x-data
        x-init="$el.showModal()"
        @cancel.prevent="$wire.cancelInterfaceSelection()"
        aria-labelledby="interface-picker-title"
        aria-describedby="interface-picker-description"
        class="fixed inset-0 m-auto w-[calc(100%-2rem)] max-w-sm rounded-3xl border-0 bg-white p-6 text-slate-900 shadow-2xl backdrop:bg-slate-950/50 backdrop:backdrop-blur-sm sm:p-7"
    >
        <div class="mb-4 flex items-center justify-between">
            <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">Choose your interface</span>
            <button type="button" wire:click="cancelInterfaceSelection" wire:loading.attr="disabled" aria-label="Close interface picker" class="rounded-full p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <x-icon name="x-mark" class="h-4 w-4" />
            </button>
        </div>

        <h2 id="interface-picker-title" class="text-2xl font-bold tracking-tight text-[#071a3d]">How would you like to use Lendly?</h2>
        <p id="interface-picker-description" class="mt-2 text-sm leading-6 text-slate-500">Choose an interface to log in and continue.</p>

        <div class="mt-5 space-y-3">
            <button type="button" wire:click="chooseInterface('renter')" wire:loading.attr="disabled" class="group flex w-full items-center gap-4 rounded-2xl border border-blue-100 bg-blue-50/60 p-4 text-left transition hover:border-blue-300 hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:cursor-wait disabled:opacity-60">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-600">
                    <x-icon name="search" class="h-5 w-5" />
                </span>
                <span class="flex-1">
                    <span class="block text-sm font-bold text-slate-900">Renter interface</span>
                    <span class="mt-1 block text-xs leading-5 text-slate-500">Browse items and find your next rental.</span>
                </span>
                <span aria-hidden="true" class="text-blue-600 transition group-hover:translate-x-1">&rarr;</span>
            </button>

            <button type="button" wire:click="chooseInterface('owner')" wire:loading.attr="disabled" class="group flex w-full items-center gap-4 rounded-2xl border border-emerald-100 bg-emerald-50/60 p-4 text-left transition hover:border-emerald-300 hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 disabled:cursor-wait disabled:opacity-60">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600">
                    <x-icon name="tag" class="h-5 w-5" />
                </span>
                <span class="flex-1">
                    <span class="block text-sm font-bold text-slate-900">Owner interface</span>
                    <span class="mt-1 block text-xs leading-5 text-slate-500">Manage your listings and rental requests.</span>
                </span>
                <span aria-hidden="true" class="text-emerald-600 transition group-hover:translate-x-1">&rarr;</span>
            </button>
        </div>

        <p class="mt-5 text-center text-xs leading-5 text-slate-400">You can access both interfaces with the same account.</p>
    </dialog>
@endif
