@props(['step' => 1])

<ol {{ $attributes->merge(['class' => 'mb-6 grid grid-cols-3 gap-2']) }} aria-label="Registration progress">
    @foreach ([1 => 'Account', 2 => 'Email', 3 => 'ID review'] as $number => $label)
        <li @if ($number === (int) $step) aria-current="step" @endif class="flex min-w-0 flex-col items-center gap-1 rounded-xl border px-2 py-3 text-center text-xs font-semibold sm:flex-row sm:gap-2 sm:px-3 sm:text-left {{ $number <= $step ? 'border-blue-200 bg-blue-50 text-blue-700' : 'border-slate-200 bg-white text-slate-500' }}">
            <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full {{ $number === (int) $step ? 'bg-blue-600 text-white' : 'bg-white text-slate-500 ring-1 ring-slate-200' }}" aria-hidden="true">{{ $number }}</span>
            <span><span class="sr-only">Step {{ $number }}: </span>{{ $label }}@if ($number < $step)<span class="sr-only"> completed</span>@endif</span>
        </li>
    @endforeach
</ol>
