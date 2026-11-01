@props(['value' => null, 'required' => false])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-slate-700']) }}>
    {{ $value ?? $slot }} @if ($required)<x-required-mark />@endif
</label>
