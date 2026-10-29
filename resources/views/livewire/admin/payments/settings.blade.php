<div>
    @unless ($modal)
        <x-page-header eyebrow="Administration" title="Offline payment settings" subtitle="Publish the payment method and recipient or collection instructions renters should use." />
    @endunless
    <div class="mx-auto max-w-3xl space-y-5 px-4 py-6 sm:px-6 {{ $modal ? '' : 'lg:py-8' }}">
        @if ($modal)
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 id="offline-payment-title" class="text-xl font-semibold text-slate-900">Configure offline payment instructions</h2>
                    <p id="offline-payment-description" class="mt-1 text-sm text-slate-500">Publish the payment method and recipient or collection instructions renters should use.</p>
                </div>
                <button type="button" x-on:click="$dispatch('close-offline-payment')" aria-label="Close payment settings" class="grid h-10 w-10 shrink-0 place-items-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-4 focus:ring-blue-100">
                    <x-icon name="x-mark" class="h-5 w-5" aria-hidden="true" />
                </button>
            </div>
        @else
            <a href="{{ route('admin.rentals.index') }}" wire:navigate class="text-sm font-semibold text-blue-700">← Back to transactions</a>
        @endif
        @if (session('status'))<p role="status" class="rounded-xl bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</p>@endif
        <x-rental-panel title="Processing fees and reservation rules" icon="calculator" subtitle="These rates apply to future quotes and agreements. Previously accepted agreements keep their saved terms.">
            <form wire:submit="saveFees" class="mt-4 space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><x-input-label for="processing-rate" value="Processing fee (%)" /><x-text-input id="processing-rate" type="number" min="0" max="100" step="0.01" wire:model="commission_rate" class="mt-1 w-full" /><x-input-error :messages="$errors->get('commission_rate')" /></div>
                    <div><x-input-label for="cancellation-rate" value="Cancellation fee (%)" /><x-text-input id="cancellation-rate" type="number" min="0" max="100" step="0.01" wire:model="cancellation_fee_percentage" class="mt-1 w-full" /><x-input-error :messages="$errors->get('cancellation_fee_percentage')" /></div>
                    <div><x-input-label for="cancellation-hours" value="Late cancellation window (hours)" /><x-text-input id="cancellation-hours" type="number" min="1" max="168" wire:model="cancellation_window_hours" class="mt-1 w-full" /><x-input-error :messages="$errors->get('cancellation_window_hours')" /></div>
                    <div><x-input-label for="payment-hours" value="Payment reservation limit (hours)" /><x-text-input id="payment-hours" type="number" min="1" max="168" wire:model="payment_window_hours" class="mt-1 w-full" /><x-input-error :messages="$errors->get('payment_window_hours')" /></div>
                </div>
                <p class="text-sm text-slate-500">Both percentage fees use the rental charge only. The processing fee is the existing platform fee, charged once. Approved cancellations return the processing fee and deposit as part of the manual refund, less the rental-only cancellation fee. Unpaid reservations expire without a fee.</p>
                <x-primary-button wire:loading.attr="disabled" wire:target="saveFees">Save fee rules</x-primary-button>
            </form>
        </x-rental-panel>
        <x-rental-panel title="Supported payment method" icon="calculator">
            <form wire:submit="save" class="mt-4 space-y-4">
                <div>
                    <x-input-label for="payment-method" value="Payment method" />
                    <select id="payment-method" wire:model="method" class="mt-1 w-full rounded-lg border-slate-300"><option value="bank_transfer">Bank transfer</option><option value="cash">Cash collection</option></select>
                    <x-input-error :messages="$errors->get('method')" />
                </div>
                <div>
                    <x-input-label for="payment-instructions" value="Payment instructions" />
                    <p class="mt-1 text-sm text-slate-500">For bank transfers, include the bank, account name and account number. For cash, include the collection location, hours and how to obtain an official receipt. State how the full booking total is collected.</p>
                    <textarea id="payment-instructions" wire:model="instructions" rows="7" maxlength="3000" class="mt-2 w-full rounded-lg border-slate-300"></textarea>
                    <x-input-error :messages="$errors->get('instructions')" />
                </div>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="enabled" class="rounded border-slate-300">Accept new payment proof submissions</label>
                <p class="text-sm text-slate-500">Payment is completed only after an admin matches the proof and reference to funds actually received in the collection records. Existing submissions retain the instructions used when submitted.</p>
                <div class="flex flex-wrap items-center justify-end gap-3">
                    @if ($modal)
                        <button type="button" x-on:click="$dispatch('close-offline-payment')" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-blue-100">Close</button>
                    @endif
                    <x-primary-button class="min-h-11" wire:loading.attr="disabled" wire:target="save">Save payment instructions</x-primary-button>
                </div>
            </form>
        </x-rental-panel>
    </div>
</div>
