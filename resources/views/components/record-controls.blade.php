@props(['showSearch' => true, 'searchModel' => 'recordSearch', 'placeholder' => 'Search records', 'filters' => [], 'filterModel' => 'filter'])
<div class="my-4 flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4" role="search" aria-label="Record controls">
    @if ($showSearch)
    <label class="min-w-48 flex-1 text-xs font-semibold text-slate-600">Search records
        <input type="search" wire:model.live.debounce.350ms="{{ $searchModel }}" maxlength="120" placeholder="{{ $placeholder }}" class="mt-1 block w-full rounded-lg border-slate-300 text-sm font-normal" />
    </label>
    @endif
    @if ($filters)
        <label class="text-xs font-semibold text-slate-600">Filter records
            <select aria-label="Filter records" wire:model.live="{{ $filterModel }}" class="mt-1 block w-full rounded-lg border-slate-300 text-sm font-normal">
                @foreach ($filters as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
            </select>
        </label>
    @endif
    <label class="text-xs font-semibold text-slate-600">Sort records
        <select aria-label="Sort records" wire:model.live="recordSort" class="mt-1 block w-full rounded-lg border-slate-300 text-sm font-normal">
            <option value="default">Default order</option><option value="newest">Newest first</option><option value="oldest">Oldest first</option>
        </select>
    </label>
</div>
