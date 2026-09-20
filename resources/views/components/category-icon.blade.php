@props(['category', 'size' => 'h-10 w-10'])

@php
    $accents = [
        'amber' => 'bg-amber-100 text-amber-600',
        'violet' => 'bg-violet-100 text-violet-600',
        'fuchsia' => 'bg-fuchsia-100 text-fuchsia-600',
        'emerald' => 'bg-emerald-100 text-emerald-600',
        'rose' => 'bg-rose-100 text-rose-600',
        'cyan' => 'bg-cyan-100 text-cyan-600',
        'teal' => 'bg-teal-100 text-teal-600',
        'slate' => 'bg-slate-200 text-slate-600',
    ];

    $style = $category?->style() ?? ['icon' => 'tag', 'accent' => 'slate'];
@endphp

<span {{ $attributes->merge(['class' => 'flex shrink-0 items-center justify-center rounded-full ' . $size . ' ' . ($accents[$style['accent']] ?? $accents['slate'])]) }}>
    <x-icon :name="$style['icon']" class="h-1/2 w-1/2" />
</span>
