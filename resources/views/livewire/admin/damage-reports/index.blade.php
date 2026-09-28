<div>
    <x-page-header eyebrow="Administration" title="Damage reports" subtitle="Review damage claims filed by owners. Disputed claims await resolution in a later phase." />

    <div class="w-full px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-wrap gap-2">
            @foreach (['disputed' => 'Disputed', 'pending' => 'Pending', 'accepted' => 'Accepted', 'all' => 'All'] as $value => $label)
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
            @if ($damageReports->isEmpty())
                <x-empty-state title="Nothing here" message="No damage reports match this filter right now." />
            @else
                <div class="space-y-4">
                    @foreach ($damageReports as $report)
                        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm" wire:key="report-{{ $report->id }}">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <p class="font-semibold text-slate-800">{{ $report->rental->listing->name }}</p>
                                        <x-badge :color="$report->status->badgeColor()">{{ $report->status->label() }}</x-badge>
                                    </div>
                                    <p class="mt-1 text-sm text-slate-500">{{ $report->damage_type }}: {{ $report->description }}</p>
                                    <p class="mt-1 text-xs text-slate-400">
                                        Owner: {{ $report->rental->owner->name }} &middot; Renter: {{ $report->rental->renter->name }}
                                    </p>
                                    <p class="mt-1 text-xs text-slate-400">
                                        Estimated repair ₱{{ number_format($report->estimated_repair_cost, 2) }} &middot; Proposed deduction ₱{{ number_format($report->proposed_deduction, 2) }}
                                    </p>
                                    @if ($report->renter_response_notes)
                                        <p class="mt-2 text-sm text-rose-600">Renter's dispute: {{ $report->renter_response_notes }}</p>
                                    @endif
                                </div>
                            </div>

                            @if ($report->photos->isNotEmpty())
                                <div class="mt-3 grid grid-cols-6 gap-2">
                                    @foreach ($report->photos as $photo)
                                        <img src="{{ $photo->url() }}" class="aspect-square w-full rounded-lg object-cover">
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $damageReports->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
