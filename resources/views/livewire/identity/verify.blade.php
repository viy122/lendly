<div x-data="identityVerification($wire, @js(['available' => $available, 'publicKey' => $available ? $publicKey : '', 'sdkUrl' => $available ? $sdkUrl : '']))">
    <x-page-header eyebrow="Account" title="Verify your identity" subtitle="One identity check for renting and lending on Lendly." />

    <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
        <x-registration-progress :step="3" class="max-w-xl" />
        <a href="{{ route('profile') }}" wire:navigate class="mb-6 inline-flex min-h-10 items-center gap-2 rounded-lg text-sm font-semibold text-slate-600 transition hover:text-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-100">
            <x-icon name="chevron-down" class="h-4 w-4 rotate-90" aria-hidden="true" /> Back to profile
        </a>

        @if ($verified)
            <section class="rounded-3xl border border-emerald-200 bg-white p-6 shadow-sm sm:p-10" aria-labelledby="identity-result-title" role="status">
                <span class="mb-5 grid h-14 w-14 place-items-center rounded-2xl bg-emerald-50 text-emerald-700"><x-icon name="user-circle" class="h-8 w-8" aria-hidden="true" /></span>
                <p class="mb-2 text-xs font-bold uppercase tracking-wider text-emerald-700">Identity verified</p>
                <h2 id="identity-result-title" class="text-2xl font-bold tracking-tight text-slate-900">You’re verified for renting and lending.</h2>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">Your National ID, personal details and live selfie have been checked. Your identity verification belongs to your member account and covers both interfaces.</p>
                <a href="{{ route(auth()->user()->dashboardRouteName()) }}" wire:navigate class="mt-6 inline-flex min-h-11 items-center justify-center rounded-lg bg-blue-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-100">Continue to dashboard</a>
            </section>
        @elseif ($testVerified && $environment === 'sandbox')
            <section class="rounded-3xl border border-amber-200 bg-white p-6 shadow-sm sm:p-10" aria-labelledby="identity-test-title" role="status">
                <span class="mb-5 grid h-14 w-14 place-items-center rounded-2xl bg-amber-50 text-amber-700"><x-icon name="clipboard-list" class="h-7 w-7" aria-hidden="true" /></span>
                <p class="mb-2 text-xs font-bold uppercase tracking-wider text-amber-700">Test verification completed</p>
                <h2 id="identity-test-title" class="text-2xl font-bold tracking-tight text-slate-900">Your test identity check is complete.</h2>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">This was a test check. It does not grant a verified identity badge on your public profile.</p>
                <a href="{{ route(auth()->user()->dashboardRouteName()) }}" wire:navigate class="mt-6 inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-200 px-5 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-blue-100">Continue to dashboard</a>
            </section>
        @elseif (! $available)
            <section class="overflow-hidden rounded-3xl border border-blue-200 bg-white shadow-sm" aria-labelledby="identity-unavailable-title">
                <div class="border-b border-blue-100 bg-gradient-to-br from-blue-50 via-white to-indigo-50 p-6 sm:p-10">
                    <span class="mb-5 grid h-14 w-14 place-items-center rounded-2xl border border-blue-100 bg-white text-blue-600 shadow-sm"><x-icon name="user-circle" class="h-8 w-8" aria-hidden="true" /></span>
                    <p class="mb-2 text-xs font-bold uppercase tracking-wider text-blue-700">Member identity</p>
                    <h2 id="identity-unavailable-title" class="max-w-2xl text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Automatic identity verification is not available yet</h2>
                    <p class="mt-4 max-w-2xl text-sm leading-7 text-slate-600">Your account is created and your email is confirmed. Your identity check is still pending. When verification is available, a single check of your National ID, personal details and live selfie will cover both renting and lending. You can continue using your account.</p>
                </div>
                <div class="p-6 sm:px-10 sm:py-7">
                    <h3 class="font-bold text-slate-900">Need to check your National ID?</h3>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">You can use the government’s public QR checker. A check there does not verify your Lendly account.</p>
                    <a href="https://everify.gov.ph/check" target="_blank" rel="noopener noreferrer" class="mt-4 inline-flex min-h-11 items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700 transition hover:bg-blue-100 focus:outline-none focus:ring-4 focus:ring-blue-100">Open official National ID Check <span class="sr-only">(opens in a new tab)</span><x-icon name="chevron-down" class="h-4 w-4 -rotate-90" aria-hidden="true" /></a>
                    <p class="mt-5 text-xs leading-5 text-slate-500">Lendly is not collecting your ID details or selfie on this page while verification is unavailable.</p>
                </div>
            </section>
        @else
            <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_18rem]">
                <div class="min-w-0 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 bg-gradient-to-br from-blue-50 via-white to-indigo-50 p-6 sm:p-7">
                        <p class="text-xs font-bold uppercase tracking-wider text-blue-700">One check, both interfaces</p>
                        <h2 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">Build trust with your community.</h2>
                        <p class="mt-3 text-sm leading-6 text-slate-600">Use your own Philippine National ID. Your ID details must match your account name.</p>
                        @if ($environment === 'sandbox')
                            <p class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm leading-6 text-amber-800"><strong>Test verification.</strong> Completing this check does not give your public profile a verified identity badge.</p>
                        @endif
                    </div>

                    <form @submit.prevent="start()" class="space-y-7 p-6 sm:p-7" aria-label="Identity verification" :aria-busy="busy || reading">
                        @if ($errors->any())
                            <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert" tabindex="-1">
                                <p class="font-semibold">Your identity check needs attention.</p>
                                <ul class="mt-2 list-inside list-disc space-y-1">
                                    @foreach ($errors->all() as $message)
                                        <li>{{ $message }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <fieldset :disabled="busy" class="space-y-4 disabled:opacity-70">
                            <legend class="mb-1 flex items-center gap-3 text-base font-bold text-slate-900"><span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-blue-50 text-xs text-blue-700" aria-hidden="true">1</span> Enter your National ID details</legend>
                            <p id="identity-name-help" class="text-sm leading-6 text-slate-500">Enter your name exactly as it appears on your National ID. Leave optional fields blank if they do not apply.</p>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <x-input-label for="identity-first-name" value="First name" />
                                    <x-text-input id="identity-first-name" wire:model="first_name" type="text" maxlength="100" autocomplete="given-name" required class="mt-2 block min-h-11 w-full" aria-describedby="identity-name-help" aria-invalid="{{ $errors->has('first_name') ? 'true' : 'false' }}" />
                                </div>
                                <div>
                                    <x-input-label for="identity-middle-name" value="Middle name (optional)" />
                                    <x-text-input id="identity-middle-name" wire:model="middle_name" type="text" maxlength="100" autocomplete="additional-name" class="mt-2 block min-h-11 w-full" aria-describedby="identity-name-help" aria-invalid="{{ $errors->has('middle_name') ? 'true' : 'false' }}" />
                                </div>
                                <div>
                                    <x-input-label for="identity-last-name" value="Last name" />
                                    <x-text-input id="identity-last-name" wire:model="last_name" type="text" maxlength="100" autocomplete="family-name" required class="mt-2 block min-h-11 w-full" aria-invalid="{{ $errors->has('last_name') ? 'true' : 'false' }}" />
                                </div>
                                <div>
                                    <x-input-label for="identity-suffix" value="Suffix (optional)" />
                                    <x-text-input id="identity-suffix" wire:model="suffix" type="text" maxlength="30" autocomplete="honorific-suffix" placeholder="e.g. Jr., III" class="mt-2 block min-h-11 w-full" aria-invalid="{{ $errors->has('suffix') ? 'true' : 'false' }}" />
                                </div>
                                <div class="sm:col-span-2">
                                    <x-input-label for="identity-birth-date" value="Date of birth" />
                                    <x-text-input id="identity-birth-date" wire:model="birth_date" type="date" autocomplete="bday" max="{{ now()->toDateString() }}" required class="mt-2 block min-h-11 w-full sm:max-w-xs" aria-invalid="{{ $errors->has('birth_date') ? 'true' : 'false' }}" />
                                </div>
                            </div>
                        </fieldset>

                        <fieldset :disabled="busy" class="space-y-4 border-t border-slate-100 pt-6 disabled:opacity-70">
                            <legend class="float-left mb-4 flex w-full items-center gap-3 text-base font-bold text-slate-900"><span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-blue-50 text-xs text-blue-700" aria-hidden="true">2</span> Read your National ID QR code</legend>
                            <p id="identity-qr-help" class="clear-both text-sm leading-6 text-slate-500">Choose a clear image of the QR code, or scan it with your camera. Images are read on your device and are not uploaded to Lendly.</p>
                            <div class="flex flex-wrap gap-3">
                                <label class="relative inline-flex min-h-11 cursor-pointer items-center justify-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700 transition hover:bg-blue-100 focus-within:ring-4 focus-within:ring-blue-100" :class="{ 'pointer-events-none opacity-50': !consent || reading || busy }">
                                    <x-icon name="folder" class="h-4 w-4" aria-hidden="true" /> Choose QR image
                                    <input type="file" accept="image/png,image/jpeg,image/webp,image/gif,image/bmp" class="sr-only" :disabled="!consent || reading || busy" @change="scanFile($event.target.files[0]); $event.target.value = ''" aria-describedby="identity-qr-help identity-image-help" />
                                </label>
                                <button type="button" @click="startCamera()" :disabled="!consent || reading || busy || cameraOpen" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-blue-100 disabled:cursor-not-allowed disabled:opacity-50"><x-icon name="camera" class="h-4 w-4" aria-hidden="true" /> Scan with camera</button>
                            </div>
                            <p id="identity-image-help" class="text-xs leading-5 text-slate-500">PNG, JPG, WebP, GIF or BMP · Up to 8 MB and 20 megapixels. Accept the consent below to use a scanner.</p>
                            <div x-cloak x-show="cameraOpen" class="overflow-hidden rounded-xl border border-slate-200 bg-slate-900" wire:ignore>
                                <video x-ref="video" autoplay muted playsinline class="aspect-video w-full object-contain" aria-label="Live camera preview for your National ID QR code"></video>
                                <div class="flex flex-wrap items-center justify-between gap-3 p-3 text-sm text-white">
                                    <p>Hold the QR code steady inside the camera view.</p>
                                    <button type="button" @click="stopCamera()" class="min-h-10 rounded-lg border border-white/40 px-3 py-2 font-semibold focus:outline-none focus:ring-2 focus:ring-white">Stop camera</button>
                                </div>
                            </div>
                            <p x-cloak x-show="reading" role="status" class="text-sm text-blue-700">Reading the QR image on your device…</p>
                            <details class="rounded-xl border border-slate-200 p-4">
                                <summary class="cursor-pointer rounded text-sm font-semibold text-slate-700 focus:outline-none focus:ring-4 focus:ring-blue-100">Already have the QR code text?</summary>
                                <div class="mt-4">
                                    <x-input-label for="identity-qr-value" value="National ID QR code text" />
                                    <textarea id="identity-qr-value" wire:model="qr_value" rows="3" maxlength="20000" spellcheck="false" autocomplete="off" autocapitalize="off" class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" aria-describedby="identity-qr-text-help" aria-invalid="{{ $errors->has('qr_value') ? 'true' : 'false' }}"></textarea>
                                    <p id="identity-qr-text-help" class="mt-2 text-xs leading-5 text-slate-500">Paste the complete, unmodified contents of your National ID QR code. Reading a QR code alone does not verify its authenticity.</p>
                                </div>
                            </details>
                        </fieldset>

                        <section class="border-t border-slate-100 pt-6" aria-labelledby="identity-consent-title">
                            <h3 id="identity-consent-title" class="flex items-center gap-3 text-base font-bold text-slate-900"><span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-blue-50 text-xs text-blue-700" aria-hidden="true">3</span> Consent and live selfie</h3>
                            <p id="identity-consent-help" class="mt-3 text-sm leading-6 text-slate-500">Lendly compares your entered details with the official service’s result for your National ID and live selfie. Your QR contents and selfie session are sent to that service, which uses your camera for its liveness check. Lendly does not store your ID image, QR contents, date of birth or selfie.</p>
                            <label class="mt-4 flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <input type="checkbox" x-model="consent" @change="$wire.set('consent', $event.target.checked, false)" class="mt-0.5 h-5 w-5 shrink-0 rounded border-slate-300 text-blue-600 focus:ring-blue-500" aria-describedby="identity-consent-help" aria-invalid="{{ $errors->has('consent') ? 'true' : 'false' }}" />
                                <span class="text-sm leading-6 text-slate-700">I consent to the processing of my National ID details and live selfie for this identity check.</span>
                            </label>
                        </section>

                        <p x-cloak x-show="error" x-text="error" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm leading-6 text-red-800" role="alert"></p>
                        <p x-cloak x-show="notice" x-text="notice" class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm leading-6 text-blue-800" role="status"></p>
                        <p x-cloak x-show="busy" x-text="phase" class="text-sm font-medium leading-6 text-blue-700" role="status" aria-live="polite"></p>
                        <div class="flex flex-wrap items-center gap-3 border-t border-slate-100 pt-5">
                            <x-primary-button x-bind:disabled="!consent || busy || reading" class="min-h-11 justify-center disabled:cursor-not-allowed disabled:opacity-50"><x-icon name="camera" class="h-4 w-4" aria-hidden="true" /><span x-text="busy ? 'Verification in progress…' : 'Continue to live selfie'">Continue to live selfie</span></x-primary-button>
                            <button type="button" x-cloak x-show="busy" @click="cancel()" :disabled="cancelling" x-text="cancelling ? 'Cancelling…' : 'Cancel verification'" class="min-h-11 rounded-lg px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-100 focus:outline-none focus:ring-4 focus:ring-blue-100 disabled:opacity-50">Cancel verification</button>
                        </div>
                        <p class="text-xs leading-5 text-slate-500">Your camera opens only when you choose to scan a QR code or continue to the live selfie. Follow the selfie window’s instructions to finish or close it.</p>
                    </form>
                </div>

                <aside class="space-y-5 rounded-2xl border border-blue-100 bg-blue-50/60 p-6" aria-labelledby="identity-how-title">
                    <h2 id="identity-how-title" class="font-bold text-slate-900">Before you begin</h2>
                    <ul class="space-y-4 text-sm leading-6 text-slate-600">
                        <li class="flex gap-3"><x-icon name="user-circle" class="mt-0.5 h-5 w-5 shrink-0 text-blue-600" aria-hidden="true" /><span>Use your own National ID and your matching Lendly account name.</span></li>
                        <li class="flex gap-3"><x-icon name="camera" class="mt-0.5 h-5 w-5 shrink-0 text-blue-600" aria-hidden="true" /><span>Use a device with a camera and find a well-lit space for your live selfie.</span></li>
                        <li class="flex gap-3"><x-icon name="clipboard-list" class="mt-0.5 h-5 w-5 shrink-0 text-blue-600" aria-hidden="true" /><span>Your result covers both renting and lending. No separate role checks are needed.</span></li>
                    </ul>
                    <p class="border-t border-blue-100 pt-4 text-xs leading-6 text-slate-500">Identity verification is separate from email verification. A badge helps members identify you; it does not guarantee a rental or listing.</p>
                </aside>
            </div>
        @endif
    </div>
</div>
