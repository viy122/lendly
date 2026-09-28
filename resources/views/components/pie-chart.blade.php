@props(['data'])

@php
    $rows = collect($data)->values();
    $total = $rows->sum('value');

    $cumulative = 0;
    $segments = $rows->map(function ($row) use (&$cumulative, $total) {
        $start = $total > 0 ? ($cumulative / $total) * 360 : 0;
        $cumulative += $row['value'];
        $end = $total > 0 ? ($cumulative / $total) * 360 : 0;

        return "{$row['color']} {$start}deg {$end}deg";
    })->implode(', ');
@endphp

<div {{ $attributes->merge(['class' => '']) }}>
    @if ($total > 0)
        <div class="flex flex-col items-center gap-6 sm:flex-row">
            <div class="relative h-36 w-36 shrink-0 rounded-full" style="background: conic-gradient({{ $segments }});">
                <div class="absolute inset-[14%] flex flex-col items-center justify-center rounded-full bg-white text-center">
                    <span class="text-xl font-bold text-slate-900">{{ $total }}</span>
                    <span class="text-[10px] uppercase tracking-wide text-slate-400">Total</span>
                </div>
            </div>

            <div class="w-full flex-1 space-y-2">
                @foreach ($rows as $row)
                    <div class="flex items-center justify-between gap-2 text-sm">
                        <span class="flex min-w-0 items-center gap-2">
                            <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background-color: {{ $row['color'] }};"></span>
                            <span class="truncate text-slate-600">{{ $row['label'] }}</span>
                        </span>
                        <span class="shrink-0 font-semibold text-slate-800">
                            {{ $row['value'] }}
                            <span class="font-normal text-slate-400">({{ round(($row['value'] / $total) * 100) }}%)</span>
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <p class="text-sm text-slate-400">No data yet.</p>
    @endif
</div>
