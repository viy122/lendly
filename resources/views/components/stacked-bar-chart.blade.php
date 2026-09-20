@props(['data'])

@php
    $total = collect($data)->sum('value');
@endphp

<div {{ $attributes->merge(['class' => '']) }}>
    @if ($total > 0)
        <div class="flex h-4 w-full gap-0.5 overflow-hidden rounded-full bg-slate-100">
            @foreach ($data as $i => $row)
                <div
                    class="h-full {{ $i === 0 ? 'rounded-l-full' : '' }} {{ $i === count($data) - 1 ? 'rounded-r-full' : '' }}"
                    style="width: {{ round(($row['value'] / $total) * 100, 2) }}%; background-color: {{ $row['color'] }};"
                    title="{{ $row['label'] }}: {{ $row['value'] }}"
                ></div>
            @endforeach
        </div>

        <div class="mt-4 grid grid-cols-1 gap-x-4 gap-y-2 sm:grid-cols-2">
            @foreach ($data as $row)
                <div class="flex items-center justify-between gap-2 text-xs">
                    <span class="flex min-w-0 items-center gap-2">
                        <span class="h-2.5 w-2.5 shrink-0 rounded-sm" style="background-color: {{ $row['color'] }};"></span>
                        <span class="truncate text-slate-600">{{ $row['label'] }}</span>
                    </span>
                    <span class="shrink-0 font-medium text-slate-800">{{ $row['value'] }} &middot; {{ round(($row['value'] / $total) * 100) }}%</span>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-sm text-slate-400">No data yet.</p>
    @endif
</div>
