@props(['strength' => false])

<div @if ($strength) x-data="passwordStrength($wire, @js($attributes->wire('model')->value()))" @else x-data="{}" @endif>
    <x-text-input
        {{ $attributes->merge(['type' => 'password']) }}
        :aria-describedby="$strength ? $attributes->get('id').'_requirements' : null"
        x-on:copy.prevent
        x-on:cut.prevent
        x-on:paste.prevent
        x-on:drop.prevent
    />

    @if ($strength)
        <x-password-strength :id="$attributes->get('id').'_requirements'" />
    @endif
</div>
