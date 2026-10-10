@props(['messages'])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'text-sm text-rose-600 space-y-1']) }}>
        @foreach (collect($messages)->flatten() as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif
