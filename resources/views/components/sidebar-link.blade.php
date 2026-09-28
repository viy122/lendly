@props(['active' => false, 'icon' => null])

@php
$classes = $active
    ? 'flex items-center gap-3 rounded-lg bg-gradient-to-r from-blue-600 to-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm'
    : 'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-indigo-200 hover:bg-indigo-900 hover:text-white';

// Raw string interpolation of $slot into a JS attribute breaks the
// instant the slot contains more than plain text (e.g. the Messages
// link's conditional <x-slot:badge>) — a stray quote or newline in the
// rendered slot closes the JS string literal early and Alpine throws a
// SyntaxError on every page. @js() JSON-encodes it safely instead.
$tooltip = trim(strip_tags((string) $slot));
@endphp

<a {{ $attributes->merge(['class' => $classes]) }} x-bind:class="collapsed && 'lg:justify-center lg:px-0'" x-bind:title="collapsed ? @js($tooltip) : null">
    @if ($icon)
        <x-icon :name="$icon" class="h-5 w-5 shrink-0 {{ $active ? 'text-white' : 'text-indigo-400' }}" />
    @endif
    <span class="flex-1 truncate" x-bind:class="collapsed && 'lg:hidden'">{{ $slot }}</span>
    @isset($badge)
        <span class="flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-semibold text-white" x-show="!collapsed" x-cloak>
            {{ $badge }}
        </span>
    @endisset
</a>
