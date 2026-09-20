@props(['data'])

@php
    $max = collect($data)->max('value') ?: 1;
@endphp

<div {{ $attributes->merge(['class' => 'space-y-3']) }}>
    @forelse ($data as $row)
        <div>
            <div class="flex items-center justify-between text-xs">
                <span class="text-slate-500">{{ $row['label'] }}</span>
                <span class="font-medium text-slate-700">{{ $row['display'] ?? $row['value'] }}</span>
            </div>
            <div class="mt-1 h-2 w-full rounded-full bg-slate-100">
                <div
                    class="h-2 rounded-full {{ $row['color'] ?? 'bg-blue-600' }} transition-all"
                    style="width: {{ $max > 0 ? max(round(($row['value'] / $max) * 100), $row['value'] > 0 ? 2 : 0) : 0 }}%"
                ></div>
            </div>
        </div>
    @empty
        <p class="text-sm text-slate-400">No data yet.</p>
    @endforelse
</div>
