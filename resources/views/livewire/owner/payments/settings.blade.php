<div>
    <x-page-header eyebrow="Owner" title="Payment QR settings" subtitle="Let renters pay your Maya or GCash wallet using your own payment QR." />

    <div class="mx-auto max-w-5xl space-y-6 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
        @if (session('status'))
            <p role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm leading-6 text-emerald-800">{{ session('status') }}</p>
        @endif

        <div class="rounded-2xl border border-blue-100 bg-blue-50 p-5 text-sm leading-6 text-blue-900">
            <p class="font-semibold">Your wallet, your payment QR</p>
            <p class="mt-1">Upload the payment QR from your own wallet app and check the recipient name. Renters pay outside Lendly and submit proof. You must check your wallet and confirm the funds arrived; uploading a QR or proof does not mark a rental paid.</p>
        </div>

        <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_19rem]">
            <x-rental-panel title="E-wallet payment details" icon="calculator">
                <form wire:submit="save" class="mt-5 space-y-5">
                    @if ($errors->any())
                        <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                            <p class="font-semibold">Please check your payment details.</p>
                            <ul class="mt-2 list-inside list-disc space-y-1">
                                @foreach ($errors->all() as $message)
                                    <li>{{ $message }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <div>
                        <x-input-label required for="owner-payment-provider" value="E-wallet" />
                        <select id="owner-payment-provider" wire:model="provider" class="mt-2 min-h-11 w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500" aria-invalid="{{ $errors->has('provider') ? 'true' : 'false' }}">
                            <option value="maya">Maya</option>
                            <option value="gcash">GCash</option>
                        </select>
                        <x-input-error :messages="$errors->get('provider')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label required for="owner-payment-account-name" value="Wallet account name" />
                        <x-text-input id="owner-payment-account-name" wire:model="account_name" type="text" maxlength="120" required autocomplete="name" class="mt-2 min-h-11 w-full" aria-describedby="owner-payment-name-help" aria-invalid="{{ $errors->has('account_name') ? 'true' : 'false' }}" />
                        <p id="owner-payment-name-help" class="mt-2 text-xs leading-5 text-slate-500">Enter the recipient name renters should see in their wallet app.</p>
                        <x-input-error :messages="$errors->get('account_name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="owner-payment-instructions" value="Additional instructions (optional)" />
                        <textarea id="owner-payment-instructions" wire:model="instructions" rows="4" maxlength="1000" class="mt-2 w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" aria-describedby="owner-payment-instructions-help" aria-invalid="{{ $errors->has('instructions') ? 'true' : 'false' }}"></textarea>
                        <p id="owner-payment-instructions-help" class="mt-2 text-xs leading-5 text-slate-500">Explain any payment reference renters should include. Never share your wallet PIN or one-time codes.</p>
                        <x-input-error :messages="$errors->get('instructions')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label :required="$enabled && ! $settings?->qr_path" for="owner-payment-qr" value="Your payment QR image" />
                        <input id="owner-payment-qr" wire:model="qr_image" type="file" accept="image/jpeg,image/png" class="mt-2 block w-full rounded-lg border border-slate-300 bg-white text-sm text-slate-600 file:mr-3 file:cursor-pointer file:border-0 file:bg-blue-50 file:px-4 file:py-3 file:font-semibold file:text-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-100" aria-describedby="owner-payment-qr-help" aria-invalid="{{ $errors->has('qr_image') ? 'true' : 'false' }}" />
                        <p id="owner-payment-qr-help" class="mt-2 text-xs leading-5 text-slate-500">JPG or PNG, up to 5 MB and 4096 × 4096 pixels. Use a clear QR image from your own Maya or GCash wallet.</p>
                        <p wire:loading wire:target="qr_image" role="status" class="mt-2 text-sm text-blue-700">Uploading your QR image…</p>
                        @if ($qr_image)
                            <p class="mt-2 text-xs leading-5 text-slate-600">A new image is selected. Save your settings to update the payment QR.</p>
                        @endif
                        <x-input-error :messages="$errors->get('qr_image')" class="mt-2" />
                    </div>
                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <input wire:model="enabled" type="checkbox" class="mt-0.5 h-5 w-5 rounded border-slate-300 text-blue-600 focus:ring-blue-500" aria-describedby="owner-payment-enabled-help" />
                        <span class="text-sm font-semibold leading-6 text-slate-700">Use this wallet for new booking payment instructions</span>
                    </label>
                    <p id="owner-payment-enabled-help" class="text-xs leading-6 text-slate-500">When disabled, new owner-wallet bookings cannot receive payment instructions until your wallet is enabled. Bookings that already received payment instructions keep their original recipient and QR, including after replacement or removal.</p>
                    <x-input-error :messages="$errors->get('enabled')" />
                    <div class="flex justify-end border-t border-slate-100 pt-5">
                        <x-primary-button class="min-h-11" wire:loading.attr="disabled" wire:target="save,removeQr,qr_image">Save payment QR settings</x-primary-button>
                    </div>
                </form>
            </x-rental-panel>

            <x-rental-panel title="Saved payment QR" icon="camera">
                @if ($settings?->qr_path)
                    <span class="mt-4 inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $settings->enabled ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $settings->enabled ? 'Enabled for new instructions' : 'Disabled for new instructions' }}</span>
                    <div wire:key="owner-payment-qr-{{ md5($settings->qr_path) }}" class="mt-4 overflow-hidden rounded-xl border border-slate-200 bg-white p-3">
                        <img src="{{ route('owner.payments.qr') }}" alt="Your saved {{ $settings->provider === 'maya' ? 'Maya' : 'GCash' }} payment QR for {{ $settings->account_name }}" class="aspect-square w-full object-contain" />
                    </div>
                    <p class="mt-3 break-words text-sm font-semibold text-slate-800">{{ $settings->provider === 'maya' ? 'Maya' : 'GCash' }} · {{ $settings->account_name }}</p>
                    <p class="mt-2 text-xs leading-5 text-slate-500">This saved image is shown through your private account. After enabling, eligible renters can view it on their booking.</p>
                    <button type="button" wire:click="removeQr" wire:confirm="Remove your saved payment QR? New payment instructions will stop using this wallet. Existing bookings keep their original instructions." wire:loading.attr="disabled" wire:target="save,removeQr,qr_image" class="mt-4 inline-flex min-h-11 items-center justify-center rounded-lg border border-red-200 px-4 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-50 focus:outline-none focus:ring-4 focus:ring-red-100 disabled:opacity-50">Remove saved QR</button>
                @else
                    <div class="mt-4 grid aspect-square place-content-center rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center">
                        <x-icon name="camera" class="mx-auto h-9 w-9 text-slate-400" aria-hidden="true" />
                        <p class="mt-3 text-sm font-semibold text-slate-700">No payment QR saved</p>
                        <p class="mt-2 text-xs leading-5 text-slate-500">Choose your wallet image and save it to see a preview here.</p>
                    </div>
                @endif
                <p class="mt-5 border-t border-slate-100 pt-4 text-xs leading-6 text-slate-500">For wallet payments, you receive the full booking total, including the processing fee and refundable deposit. Processing-fee remittance and deposit refunds remain manual.</p>
            </x-rental-panel>
        </div>
    </div>
</div>
