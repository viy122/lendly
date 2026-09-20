@props(['label', 'value', 'hint' => null, 'icon' => null, 'accent' => 'blue'])

@php
    $accents = [
        'blue' => ['border' => 'border-l-blue-600', 'iconBg' => 'bg-blue-50 text-blue-600'],
        'indigo' => ['border' => 'border-l-indigo-600', 'iconBg' => 'bg-indigo-50 text-indigo-600'],
        'emerald' => ['border' => 'border-l-emerald-600', 'iconBg' => 'bg-emerald-50 text-emerald-600'],
        'amber' => ['border' => 'border-l-amber-600', 'iconBg' => 'bg-amber-50 text-amber-600'],
        'rose' => ['border' => 'border-l-rose-600', 'iconBg' => 'bg-rose-50 text-rose-600'],
    ];

    $style = $accents[$accent] ?? $accents['blue'];
@endphp

<div {{ $attributes->merge(['class' => 'rounded-xl border border-slate-200 border-l-4 ' . $style['border'] . ' bg-white p-5 shadow-sm']) }}>
    <div class="flex items-start justify-between gap-3">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $label }}</p>
        @if ($icon)
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $style['iconBg'] }}">
                <x-icon :name="$icon" class="h-4 w-4" />
            </span>
        @endif
    </div>
    <p class="mt-2 text-2xl font-bold text-slate-900">{{ $value }}</p>
    @if ($hint)
        <p class="mt-1 text-xs text-slate-400">{{ $hint }}</p>
    @endif
</div>
