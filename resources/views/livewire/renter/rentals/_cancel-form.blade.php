@if ($showCancelForm)
    @php($preview = $this->cancellationPreview())
    <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 p-4">
        <p class="text-sm font-semibold text-rose-800">Cancel this booking</p>

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
            <x-input-label for="cancellation_reason" value="Reason for cancellation" />
            <textarea wire:model="cancellation_reason" id="cancellation_reason" rows="2" class="mt-1 block w-full rounded-lg border-rose-300 text-sm shadow-sm focus:border-rose-500 focus:ring-rose-500"></textarea>
            <x-input-error :messages="$errors->get('cancellation_reason')" class="mt-2" />
        </div>

        <div class="mt-3 flex justify-end gap-2">
            <button type="button" wire:click="$set('showCancelForm', false)" class="text-xs font-medium text-slate-500 hover:text-slate-700">
                Never mind
            </button>
            <button type="button" wire:click="cancelRental" wire:confirm="Are you sure you want to cancel this booking?" class="rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-700">
                Confirm cancellation
            </button>
        </div>
    </div>
@endif
