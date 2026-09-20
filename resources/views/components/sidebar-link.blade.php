@props(['active' => false, 'icon' => null])

@php
$classes = $active
    ? 'flex items-center gap-3 rounded-lg bg-gradient-to-r from-blue-600 to-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm'
    : 'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-indigo-200 hover:bg-indigo-900 hover:text-white';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }} x-bind:class="collapsed && 'lg:justify-center lg:px-0'" x-bind:title="collapsed ? '{{ trim($slot) }}' : null">
    @if ($icon)
        <x-icon :name="$icon" class="h-5 w-5 shrink-0 {{ $active ? 'text-white' : 'text-indigo-400' }}" />
    @endif
    <span class="truncate" x-bind:class="collapsed && 'lg:hidden'">{{ $slot }}</span>
</a>
