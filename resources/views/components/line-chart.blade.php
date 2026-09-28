@props(['data', 'color' => '#2563eb'])

@php
    $rows = collect($data)->values();
    $values = $rows->pluck('value');
    $max = $values->max() ?: 1;
    $min = min($values->min() ?: 0, 0);
    $range = max($max - $min, 1);
    $count = $rows->count();
    $vbWidth = 300;
    $vbHeight = 100;
    $padX = 6;
    $padY = 12;

    $points = $rows->map(function ($row, $i) use ($count, $vbWidth, $vbHeight, $min, $range, $padX, $padY) {
        $x = $count > 1 ? $padX + ($i / ($count - 1)) * ($vbWidth - $padX * 2) : $vbWidth / 2;
        $y = ($vbHeight - $padY) - ((($row['value'] - $min) / $range) * ($vbHeight - $padY * 2));

        return ['x' => round($x, 2), 'y' => round($y, 2)];
    });

    $linePoints = $points->map(fn ($p) => $p['x'].','.$p['y'])->implode(' ');
    $areaPoints = $count > 0
        ? '0,'.$vbHeight.' '.$linePoints.' '.$vbWidth.','.$vbHeight
        : '';
@endphp

<div {{ $attributes->merge(['class' => '']) }}>
    @if ($count > 0)
        <div class="aspect-[3/1] w-full">
            <svg viewBox="0 0 {{ $vbWidth }} {{ $vbHeight }}" class="h-full w-full" aria-hidden="true">
                <polygon points="{{ $areaPoints }}" fill="{{ $color }}" fill-opacity="0.08" />
                <polyline points="{{ $linePoints }}" fill="none" stroke="{{ $color }}" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" />
                @foreach ($points as $p)
                    <circle cx="{{ $p['x'] }}" cy="{{ $p['y'] }}" r="3" fill="white" stroke="{{ $color }}" stroke-width="2" />
                @endforeach
            </svg>
        </div>

        <div class="mt-2 flex justify-between gap-1 text-center text-xs">
            @foreach ($rows as $row)
                <div class="flex-1">
                    <p class="font-semibold text-slate-700">{{ $row['display'] ?? $row['value'] }}</p>
                    <p class="text-slate-400">{{ $row['label'] }}</p>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-sm text-slate-400">No data yet.</p>
    @endif
</div>
