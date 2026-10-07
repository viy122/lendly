<div>
    <x-page-header eyebrow="Administration" title="Disputes" subtitle="Review and resolve disputes raised by renters and owners." />

    <div class="w-full px-4 py-8 sm:px-6 lg:px-8">
        @if ($rentalId !== null)
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
                <a href="{{ route('admin.rentals.show', $rentalId) }}" wire:navigate class="font-semibold">Transaction #{{ $rentalId }}</a>
                <button type="button" wire:click="$set('rentalId', null)" class="font-semibold">Show disputes for all transactions</button>
            </div>
        @endif
        <div class="flex flex-wrap gap-2">
            @foreach (['open' => 'Open', 'resolved' => 'Resolved', 'all' => 'All'] as $value => $label)
                <button
                    type="button"
                    wire:click="$set('filter', '{{ $value }}')"
                    class="rounded-full px-3 py-1.5 text-xs font-semibold {{ $filter === $value ? 'bg-blue-600 text-white shadow-sm' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}"
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <div class="mt-6">
            @if ($disputes->isEmpty())
                <x-empty-state title="Nothing here" message="No disputes match this filter right now." />
            @else
                <div class="space-y-4">
                    @foreach ($disputes as $dispute)
                        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm" wire:key="dispute-{{ $dispute->id }}">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <p class="font-semibold text-slate-800">{{ $dispute->reason->label() }}</p>
                                        <x-badge :color="$dispute->status->badgeColor()">{{ $dispute->status->label() }}</x-badge>
                                        @if ($dispute->damage_report_id)
                                            <x-badge color="amber">Damage claim</x-badge>
                                        @endif
                                    </div>
                                    <p class="mt-1 text-sm text-slate-500">{{ $dispute->description }}</p>
                                    <p class="mt-1 text-xs text-slate-400">
                                        Raised by {{ $dispute->raisedBy->name }} &middot; Item: {{ $dispute->rental->listing->name }} &middot;
                                        Owner: {{ $dispute->rental->owner->name }} &middot; Renter: {{ $dispute->rental->renter->name }}
                                    </p>

                                    @if ($dispute->damageReport)
                                        <div class="mt-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600">
                                            <p><span class="font-medium">Damage:</span> {{ $dispute->damageReport->damage_type }} — {{ $dispute->damageReport->description }}</p>
                                            <p class="mt-1">Estimated repair: ₱{{ number_format($dispute->damageReport->estimated_repair_cost, 2) }} &middot; Proposed deduction: ₱{{ number_format($dispute->damageReport->proposed_deduction, 2) }}</p>
                                            @if ($dispute->damageReport->renter_response_notes)
                                                <p class="mt-1 italic">Renter: "{{ $dispute->damageReport->renter_response_notes }}"</p>
                                            @endif
                                            @if ($dispute->damageReport->photos->isNotEmpty())
                                                <div class="mt-2 grid grid-cols-6 gap-2">
                                                    @foreach ($dispute->damageReport->photos as $photo)
                                                        <img src="{{ $photo->url() }}" class="aspect-square w-full rounded-lg object-cover">
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    @endif

                                    @if ($dispute->status->value === 'resolved')
                                        <p class="mt-2 text-sm text-blue-700">
                                            Resolved: {{ $dispute->resolution?->label() }} by {{ $dispute->resolvedBy?->name }} on {{ $dispute->resolved_at?->format('M d, Y') }}
                                        </p>
                                        @if ($dispute->resolution_notes)
                                            <p class="mt-1 text-sm text-slate-500">{{ $dispute->resolution_notes }}</p>
                                        @endif
                                    @endif
                                </div>

                                @if ($dispute->isOpen())
                                    <button type="button" wire:click="startResolving({{ $dispute->id }})" class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-blue-700">
                                        Resolve
                                    </button>
                                @endif
                            </div>

                            @if ($resolving === $dispute->id)
                                <form wire:submit="confirmResolve" class="mt-4 space-y-3 rounded-lg border border-slate-200 bg-slate-50 p-4">
                                    <div>
                                        <x-input-label for="resolution" value="Resolution" />
                                        <select wire:model.live="resolution" id="resolution" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <option value="">Select a resolution</option>
                                            @foreach (\App\Enums\DisputeResolution::cases() as $option)
                                                <option value="{{ $option->value }}">{{ $option->label() }}</option>
                                            @endforeach
                                        </select>
                                        <x-input-error :messages="$errors->get('resolution')" class="mt-2" />
                                    </div>

                                    @if ($dispute->damage_report_id && $resolution === 'partial_compensation')
                                        <div>
                                            <x-input-label for="partial_amount" value="Deduction amount (₱)" />
                                            <x-text-input wire:model="partial_amount" id="partial_amount" type="number" step="0.01" min="0" :max="$dispute->rental->securityDeposit?->amount" class="mt-1 block w-40" />
                                            <x-input-error :messages="$errors->get('partial_amount')" class="mt-2" />
                                        </div>
                                    @endif

                                    <div>
                                        <x-input-label for="resolution_notes" value="Notes (visible to both parties)" />
                                        <textarea wire:model="resolution_notes" id="resolution_notes" rows="2" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                                    </div>

                                    <div class="flex gap-2">
                                        <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-blue-700">Confirm resolution</button>
                                        <button type="button" wire:click="$set('resolving', null)" class="text-xs font-medium text-slate-500 hover:text-slate-700">Cancel</button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $disputes->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
