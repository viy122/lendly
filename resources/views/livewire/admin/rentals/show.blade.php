<div>
    <x-page-header eyebrow="Administration" :title="'Transaction #'.$rental->id" :subtitle="$rental->listing->name">
        <x-slot:actions>
            <a href="{{ route('admin.rentals.index') }}" wire:navigate class="text-sm font-medium text-blue-700 hover:text-blue-900">Back to transactions</a>
        </x-slot:actions>
    </x-page-header>

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-center gap-3">
            <x-badge :color="$rental->displayStatusColor()">{{ $rental->displayStatusLabel() }}</x-badge>
            @if ($rental->listing->trashed())
                <span class="text-sm text-slate-500">Listing removed</span>
            @else
                <a href="{{ route('listings.show', $rental->listing) }}" wire:navigate class="text-sm font-medium text-blue-600 hover:text-blue-800">View listing</a>
            @endif
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            @foreach (['Owner' => $rental->owner, 'Renter' => $rental->renter] as $role => $participant)
                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-900">{{ $role }}</h2>
                    <p class="mt-2 font-medium text-slate-800">{{ $participant->name }}</p>
                    @if ($participant->trashed())
                        <p class="mt-1 text-sm text-slate-500">Account closed</p>
                    @else
                        <dl class="mt-2 space-y-1 text-sm text-slate-600">
                            <div><dt class="inline font-medium">Email:</dt> <dd class="inline break-all">{{ $participant->email }}</dd></div>
                            <div><dt class="inline font-medium">Phone:</dt> <dd class="inline">{{ $participant->phone ?: 'Not provided' }}</dd></div>
                            <div><dt class="inline font-medium">Address:</dt> <dd class="inline">{{ $participant->address ?: 'Not provided' }}</dd></div>
                        </dl>
                        <a href="{{ route('admin.dashboard', ['search' => $participant->email]) }}" wire:navigate class="mt-3 inline-block text-sm font-medium text-blue-600 hover:text-blue-800">Manage {{ strtolower($role) }} account</a>
                    @endif
                </section>
            @endforeach
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-base font-semibold text-slate-900">Booked terms</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex flex-wrap justify-between gap-2"><dt class="text-slate-500">Rental dates</dt><dd class="font-medium text-slate-800">{{ $rental->start_date->format('M d, Y') }} &ndash; {{ $rental->end_date->format('M d, Y') }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-slate-500">Duration</dt><dd class="font-medium text-slate-800">{{ $rental->rental_days }} days</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-slate-500">Fulfillment</dt><dd class="font-medium text-slate-800">{{ $rental->fulfillment_method->label() }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-slate-500">Agreed daily rate</dt><dd class="font-medium text-slate-800">₱{{ number_format($rental->agreedDailyRate(), 2) }}</dd></div>
                    @foreach (['Rental fee' => $rental->rental_fee, 'Commission' => $rental->commission_amount, 'Security deposit' => $rental->security_deposit, 'Booked total' => $rental->total_amount, 'Late fee' => $rental->currentLateFee()] as $label => $amount)
                        <div class="flex justify-between gap-2"><dt class="text-slate-500">{{ $label }}</dt><dd class="font-medium text-slate-800">₱{{ number_format($amount, 2) }}</dd></div>
                    @endforeach
                    <div class="flex justify-between gap-2"><dt class="text-slate-500">Commission rate</dt><dd class="font-medium text-slate-800">{{ number_format($rental->commission_rate, 2) }}%</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-slate-500">Days overdue</dt><dd class="font-medium text-slate-800">{{ $rental->currentOverdueDays() }}</dd></div>
                    <div class="flex flex-wrap justify-between gap-2"><dt class="text-slate-500">Renter accepted terms</dt><dd class="font-medium text-slate-800">{{ $rental->rentalRequest->renter_terms_accepted_at?->format('M d, Y g:i A') ?? 'Not recorded' }}</dd></div>
                    <div class="flex flex-wrap justify-between gap-2"><dt class="text-slate-500">Owner accepted terms</dt><dd class="font-medium text-slate-800">{{ $rental->rentalRequest->owner_terms_accepted_at?->format('M d, Y g:i A') ?? 'Not recorded' }}</dd></div>
                </dl>
            </section>

            <div class="space-y-6">
                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-900">Payment receipt</h2>
                    @if ($rental->payment)
                        <dl class="mt-4 space-y-3 text-sm">
                            <div class="flex flex-wrap justify-between gap-2"><dt class="text-slate-500">Reference</dt><dd class="break-all font-medium text-slate-800">{{ $rental->payment->transaction_reference }}</dd></div>
                            <div class="flex justify-between gap-2"><dt class="text-slate-500">Amount paid</dt><dd class="font-medium text-slate-800">₱{{ number_format($rental->payment->amount, 2) }}</dd></div>
                            <div class="flex justify-between gap-2"><dt class="text-slate-500">Payment status</dt><dd class="font-medium text-slate-800">{{ ucfirst($rental->payment->status) }}</dd></div>
                            <div class="flex flex-wrap justify-between gap-2"><dt class="text-slate-500">Paid on</dt><dd class="font-medium text-slate-800">{{ $rental->payment->paid_at?->format('M d, Y g:i A') ?? 'Not recorded' }}</dd></div>
                        </dl>
                    @else
                        <p class="mt-3 text-sm text-slate-500">No payment recorded.</p>
                    @endif
                </section>
                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-900">Deposit settlement</h2>
                    @if ($rental->securityDeposit)
                        <dl class="mt-4 space-y-3 text-sm">
                            <div class="flex justify-between gap-2"><dt class="text-slate-500">Status</dt><dd><x-badge :color="$rental->securityDeposit->status->badgeColor()">{{ $rental->securityDeposit->status->label() }}</x-badge></dd></div>
                            <div class="flex justify-between gap-2"><dt class="text-slate-500">Deposit amount</dt><dd class="font-medium text-slate-800">₱{{ number_format($rental->securityDeposit->amount, 2) }}</dd></div>
                            <div class="flex justify-between gap-2"><dt class="text-slate-500">Deducted amount</dt><dd class="font-medium text-slate-800">₱{{ number_format($rental->securityDeposit->deducted_amount, 2) }}</dd></div>
                        </dl>
                    @else
                        <p class="mt-3 text-sm text-slate-500">No deposit recorded.</p>
                    @endif
                </section>
            </div>
        </div>

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-semibold text-slate-900">Rental lifecycle</h2>
            <ol class="mt-4 space-y-3 text-sm">
                @foreach ($timeline as $event => $date)
                    <li class="flex flex-wrap justify-between gap-2 border-b border-slate-100 pb-3 last:border-0 last:pb-0">
                        <span class="font-medium text-slate-700">{{ $event }}</span>
                        <time datetime="{{ $date->toIso8601String() }}" class="text-slate-500">{{ $date->format('M d, Y g:i A') }}</time>
                    </li>
                @endforeach
            </ol>
            @if ($rental->isCancelled())
                <div class="mt-4 rounded-lg bg-slate-50 p-4 text-sm text-slate-600">
                    <p><span class="font-medium">Cancellation reason:</span> {{ $rental->cancellation_reason ?: 'Not recorded' }}</p>
                    <p class="mt-1"><span class="font-medium">Cancellation fee:</span> ₱{{ number_format($rental->cancellation_fee, 2) }}</p>
                </div>
            @endif
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-semibold text-slate-900">Condition evidence</h2>
            <div class="mt-4 space-y-5">
                @forelse ($rental->conditionRecords->sortBy('created_at') as $record)
                    <article class="rounded-lg border border-slate-200 p-4">
                        <h3 class="font-medium text-slate-800">{{ $record->type->label() }} &middot; {{ $record->condition->label() }}</h3>
                        <p class="mt-1 text-xs text-slate-500">Recorded by {{ $record->recordedBy->name }} on {{ $record->created_at->format('M d, Y g:i A') }}</p>
                        <p class="mt-2 whitespace-pre-line text-sm text-slate-600">{{ $record->notes ?: 'No notes recorded.' }}</p>
                        <p class="mt-1 text-sm text-slate-600">Damage recorded: {{ $record->has_damage ? 'Yes' : 'No' }}</p>
                        @if ($record->photos->isNotEmpty())
                            <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6">
                                @foreach ($record->photos as $photo)
                                    <a href="{{ $photo->url() }}"><img src="{{ $photo->url() }}" alt="{{ $record->type->label() }} condition photo {{ $loop->iteration }}" loading="lazy" class="aspect-square w-full rounded-lg object-cover"></a>
                                @endforeach
                            </div>
                        @endif
                    </article>
                @empty
                    <p class="text-sm text-slate-500">No condition records.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-semibold text-slate-900">Damage claim</h2>
            @if ($rental->damageReport)
                <div class="mt-3 flex flex-wrap items-center gap-3">
                    <h3 class="font-medium text-slate-800">{{ $rental->damageReport->damage_type }}</h3>
                    <x-badge :color="$rental->damageReport->status->badgeColor()">{{ $rental->damageReport->status->label() }}</x-badge>
                </div>
                <p class="mt-2 whitespace-pre-line text-sm text-slate-600">{{ $rental->damageReport->description }}</p>
                <p class="mt-2 text-sm text-slate-600">Estimated repair: ₱{{ number_format($rental->damageReport->estimated_repair_cost, 2) }} &middot; Proposed deduction: ₱{{ number_format($rental->damageReport->proposed_deduction, 2) }}</p>
                @if ($rental->damageReport->renter_response_notes)
                    <p class="mt-2 whitespace-pre-line text-sm text-slate-600">Renter response: {{ $rental->damageReport->renter_response_notes }}</p>
                @endif
                @if ($rental->damageReport->photos->isNotEmpty())
                    <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6">
                        @foreach ($rental->damageReport->photos as $photo)
                            <a href="{{ $photo->url() }}"><img src="{{ $photo->url() }}" alt="Damage evidence photo {{ $loop->iteration }}" loading="lazy" class="aspect-square w-full rounded-lg object-cover"></a>
                        @endforeach
                    </div>
                @endif
            @else
                <p class="mt-3 text-sm text-slate-500">No damage claim.</p>
            @endif
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-base font-semibold text-slate-900">Disputes and decisions</h2>
                @if ($rental->disputes->isNotEmpty())
                    <a href="{{ route('admin.disputes.index', ['rental' => $rental->id, 'filter' => 'all']) }}" wire:navigate class="text-sm font-medium text-blue-600 hover:text-blue-800">Manage transaction disputes</a>
                @endif
            </div>
            <div class="mt-4 space-y-4">
                @forelse ($rental->disputes->sortBy('created_at') as $dispute)
                    <article class="rounded-lg border border-slate-200 p-4">
                        <div class="flex flex-wrap items-center gap-3">
                            <h3 class="font-medium text-slate-800">Dispute #{{ $dispute->id }}: {{ $dispute->reason->label() }}</h3>
                            <x-badge :color="$dispute->status->badgeColor()">{{ $dispute->status->label() }}</x-badge>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Raised by {{ $dispute->raisedBy->name }} on {{ $dispute->created_at->format('M d, Y g:i A') }}</p>
                        <p class="mt-2 whitespace-pre-line text-sm text-slate-600">{{ $dispute->description }}</p>
                        @if ($dispute->isOpen())
                            <a href="{{ route('admin.disputes.index', ['rental' => $rental->id, 'filter' => 'all']) }}" wire:navigate class="mt-3 inline-block text-sm font-medium text-blue-600 hover:text-blue-800">Review and resolve dispute #{{ $dispute->id }}</a>
                        @else
                            <p class="mt-3 text-sm font-medium text-slate-700">{{ $dispute->resolution?->label() ?? 'Resolved' }}</p>
                            <p class="mt-1 text-xs text-slate-500">Resolved by {{ $dispute->resolvedBy?->name ?? 'Not recorded' }} on {{ $dispute->resolved_at?->format('M d, Y g:i A') ?? 'Not recorded' }}</p>
                            <p class="mt-2 whitespace-pre-line text-sm text-slate-600">{{ $dispute->resolution_notes ?: 'No resolution notes recorded.' }}</p>
                        @endif
                    </article>
                @empty
                    <p class="text-sm text-slate-500">No disputes.</p>
                @endforelse
            </div>
        </section>
    </div>
</div>
