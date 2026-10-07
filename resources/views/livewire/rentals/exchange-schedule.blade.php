<section id="exchange-arrangements" class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6" wire:poll.10s>
    <h2 class="text-lg font-semibold text-slate-900">{{ $rental->fulfillment_method->label() }} arrangements</h2>
    <p class="mt-1 text-sm text-slate-500">Agree on a time and address, then both confirm the same schedule. All times are in Asia/Manila.</p>
    <p class="mt-1 text-xs text-slate-500">Confirm item hand-over separately when the item is actually handed over or received.</p>
    @if ($rental->isCancelled())
        <p class="mt-3 text-sm text-rose-700" role="status">This booking was cancelled. The saved schedule no longer applies.</p>
    @endif
    <x-input-error :messages="$errors->get('schedule')" class="mt-3" role="alert" />

    @if ($schedule)
        <div class="mt-4 space-y-2 text-sm text-slate-700" aria-live="polite">
            <p class="font-semibold {{ $schedule->confirmed_at ? 'text-emerald-700' : 'text-amber-700' }}">{{ $schedule->confirmed_at ? 'Schedule confirmed by both parties' : 'Proposed schedule — awaiting confirmation' }}</p>
            <p>Time: <span class="font-medium">{{ $schedule->scheduled_at->setTimezone(\App\Services\ExchangeArrangements::TIMEZONE)->format('M d, Y \a\t g:i A') }}</span> (Asia/Manila)</p>
            <p class="break-words">Address: {{ $schedule->address }}</p>
            @if ($schedule->notes)<p class="whitespace-pre-line break-words">Instructions: {{ $schedule->notes }}</p>@endif
            <p>Proposed by {{ $schedule->proposer->name }}</p>
            <p>Owner: {{ $schedule->owner_confirmed_at ? 'Confirmed' : 'Awaiting confirmation' }} &middot; Renter: {{ $schedule->renter_confirmed_at ? 'Confirmed' : 'Awaiting confirmation' }}</p>
            @if ($canArrange && ! $schedule->confirmed_at && ! ($isOwner ? $schedule->owner_confirmed_at : $schedule->renter_confirmed_at))
                <x-primary-button wire:click="confirm({{ $schedule->id }})" wire:loading.attr="disabled" wire:target="confirm">Confirm this schedule</x-primary-button>
            @endif
        </div>
    @else
        <p class="mt-4 text-sm text-slate-500">No pickup or delivery schedule has been proposed.</p>
    @endif

    @if ($canArrange)
        @if ($editing)
            <form wire:submit="propose" class="mt-5 space-y-4 border-t border-slate-100 pt-4">
                <p class="text-sm text-slate-600">{{ $schedule ? 'Changing the schedule requires fresh confirmation from both parties.' : 'Propose the arrangements for this booking.' }}</p>
                <div>
                    <x-input-label for="exchange-time-{{ $rentalId }}" value="Pickup or delivery date and time (Asia/Manila)" />
                    <x-text-input id="exchange-time-{{ $rentalId }}" wire:model="exchange_time" type="datetime-local" required class="mt-1 block w-full" />
                    <p class="mt-1 text-xs text-slate-500">Choose a future time during {{ $rental->start_date->format('M d, Y') }} &ndash; {{ $rental->end_date->format('M d, Y') }}.</p>
                    <x-input-error :messages="$errors->get('exchange_time')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="exchange-address-{{ $rentalId }}" :value="$rental->fulfillment_method->label().' address'" />
                    <textarea id="exchange-address-{{ $rentalId }}" wire:model="address" required maxlength="500" rows="2" class="mt-1 block w-full rounded-lg border-slate-300"></textarea>
                    <x-input-error :messages="$errors->get('address')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="exchange-notes-{{ $rentalId }}" value="Instructions (optional)" />
                    <textarea id="exchange-notes-{{ $rentalId }}" wire:model="notes" maxlength="1000" rows="2" class="mt-1 block w-full rounded-lg border-slate-300"></textarea>
                    <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                </div>
                <div class="flex flex-wrap gap-3">
                    <x-primary-button wire:loading.attr="disabled" wire:target="propose">Send schedule proposal</x-primary-button>
                    <button type="button" wire:click="$set('editing', false)" class="text-sm font-medium text-slate-600">Cancel changes</button>
                </div>
            </form>
        @else
            <button type="button" wire:click="startProposal" class="mt-5 min-h-11 rounded-lg border border-blue-200 px-4 py-2 text-sm font-semibold text-blue-700">{{ $schedule ? 'Propose a different schedule' : 'Propose pickup or delivery schedule' }}</button>
        @endif
    @else
        <p class="mt-4 text-xs text-slate-500">Arrangements can be changed only before item hand-over while the booking awaits payment or pickup/delivery.</p>
    @endif
</section>
