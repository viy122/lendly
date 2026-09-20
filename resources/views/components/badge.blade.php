@props(['color' => 'slate'])

@php
    $colors = [
        'slate' => 'bg-slate-100 text-slate-700',
        'teal' => 'bg-blue-100 text-blue-700',
        'green' => 'bg-emerald-100 text-emerald-700',
        'amber' => 'bg-amber-100 text-amber-700',
        'red' => 'bg-rose-100 text-rose-700',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ' . ($colors[$color] ?? $colors['slate'])]) }}>
    {{ $slot }}
</span>
