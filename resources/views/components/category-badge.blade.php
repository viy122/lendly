@props(['category', 'withIcon' => true])

@php
    $accents = [
        'amber' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
        'violet' => 'bg-violet-50 text-violet-700 ring-violet-600/20',
        'fuchsia' => 'bg-fuchsia-50 text-fuchsia-700 ring-fuchsia-600/20',
        'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        'rose' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
        'cyan' => 'bg-cyan-50 text-cyan-700 ring-cyan-600/20',
        'teal' => 'bg-teal-50 text-teal-700 ring-teal-600/20',
        'slate' => 'bg-slate-100 text-slate-700 ring-slate-600/10',
    ];

    $style = $category?->style() ?? ['icon' => 'tag', 'accent' => 'slate'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset ' . ($accents[$style['accent']] ?? $accents['slate'])]) }}>
    @if ($withIcon)
        <x-icon :name="$style['icon']" class="h-3.5 w-3.5" />
    @endif
    {{ $category?->name }}
</span>
