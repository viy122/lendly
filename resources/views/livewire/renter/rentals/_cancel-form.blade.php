@if ($showCancelForm && $rental->isCancellableByRenter())
    @php($preview = $this->cancellationPreview())
    <section class="rounded-2xl border border-rose-200 bg-rose-50 p-5 sm:p-6" aria-labelledby="cancel-booking-title">
        <h2 id="cancel-booking-title" class="flex items-center gap-3 text-base font-bold text-rose-900"><span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-white text-rose-600"><x-icon name="x-mark" class="h-5 w-5" aria-hidden="true" /></span>Cancel this booking</h2>

        @if ($preview)
            <p class="mt-2 text-sm text-rose-700">
                @if ($preview['fee'] > 0)
                    A cancellation fee of <strong>₱{{ number_format($preview['fee'], 2) }}</strong> will apply.
                @else
                    No cancellation fee will apply.
                @endif
            </p>
            <p class="mt-1 text-xs text-rose-600">{{ $preview['reason'] }}</p>
        @endif

        <div class="mt-3">
            <x-input-label for="cancellation_reason" value="Reason for cancellation (optional)" />
            <textarea wire:model="cancellation_reason" id="cancellation_reason" rows="2" maxlength="500" class="mt-1 block w-full rounded-lg border-rose-300 text-sm shadow-sm focus:border-rose-500 focus:ring-rose-500"></textarea>
            <x-input-error :messages="$errors->get('cancellation_reason')" class="mt-2" />
        </div>

        <div class="mt-4 flex flex-wrap items-center justify-end gap-3">
            <button type="button" wire:click="$set('showCancelForm', false)" class="inline-flex min-h-11 items-center rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-white focus:outline-none focus:ring-4 focus:ring-rose-100">
                Never mind
            </button>
            <button type="button" wire:click="cancelRental" wire:loading.attr="disabled" wire:target="cancelRental" wire:confirm="Are you sure you want to cancel this booking?" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-700 focus:outline-none focus:ring-4 focus:ring-rose-100 disabled:cursor-wait disabled:opacity-60">
                Confirm cancellation
            </button>
        </div>
    </section>
@endif
