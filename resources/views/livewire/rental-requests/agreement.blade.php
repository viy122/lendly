<div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8" wire:poll.10s>
    <a href="{{ route(($isOwner ? 'owner' : 'renter').'.rental-requests.index') }}" wire:navigate class="text-sm font-medium text-blue-600">Back to rental requests</a>
    <h1 class="mt-4 text-xl font-semibold text-slate-900">Review rental agreement</h1>
    <p class="mt-2 text-sm text-slate-600">Owner: {{ $request->listing->owner->name }} &middot; Renter: {{ $request->renter->name }}</p>

    <x-input-error :messages="$errors->get('agreement')" class="mt-4" role="alert" />

    @if (! $request->isApproved())
        <p class="mt-6 rounded-lg bg-rose-50 p-4 text-sm text-rose-700" role="alert">This request is {{ strtolower($request->status->label()) }}. Its agreement can no longer be accepted.</p>
    @else
        @if ($request->agreement_terms)
            <section class="mt-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <x-rental-agreement :terms="$request->agreement_terms" />
            </section>
        @else
            <p class="mt-6 text-sm text-rose-700" role="alert">This request has no saved rental agreement. The renter must cancel this request and submit a new one for review.</p>
        @endif

        <section class="mt-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm" aria-live="polite">
            <p class="text-sm text-slate-700">Owner: {{ $request->owner_terms_accepted_at ? 'Accepted' : 'Awaiting acceptance' }}</p>
            <p class="mt-2 text-sm text-slate-700">Renter: {{ $request->renter_terms_accepted_at ? 'Accepted' : 'Awaiting acceptance' }}</p>

            @if ($request->rental)
                <p class="mt-4 font-medium text-emerald-700">Booking confirmed</p>
                <a href="{{ route(($isOwner ? 'owner' : 'renter').'.rentals.show', $request->rental) }}" wire:navigate class="mt-4 inline-flex text-sm font-medium text-blue-600">{{ $isOwner ? 'View booking' : 'View booking and pay' }}</a>
            @elseif ($isOwner ? $request->owner_terms_accepted_at : $request->renter_terms_accepted_at)
                <p class="mt-4 text-sm text-blue-700">You have accepted. Waiting for the other party to accept before finalizing the booking.</p>
            @elseif ($request->agreement_terms)
                <form wire:submit="acceptTerms" class="mt-4">
                    <label class="flex items-start gap-2 text-sm text-slate-700">
                        <input type="checkbox" wire:model="accept_terms" class="mt-0.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <span>I have read and accept this rental agreement, including the cancellation policy, cancellation fees, item rules and deposit conditions.</span>
                    </label>
                    <x-input-error :messages="$errors->get('accept_terms')" class="mt-2" role="alert" />
                    <x-primary-button class="mt-4" wire:loading.attr="disabled" wire:target="acceptTerms">Accept rental agreement</x-primary-button>
                </form>
            @endif
        </section>
    @endif
</div>
