@props(['id'])

<div class="mt-3 space-y-2 text-sm">
    <div class="flex justify-between gap-2">
        <span id="{{ $id }}_label" class="font-medium text-slate-700">{{ __('Password strength') }}</span>
        <span x-text="strength.label" aria-live="polite" class="text-slate-600">{{ __('Enter a password') }}</span>
    </div>

    <div role="progressbar" aria-labelledby="{{ $id }}_label" aria-valuemin="0" aria-valuemax="5" aria-valuenow="0"
        x-bind:aria-valuenow="strength.score" x-bind:aria-valuetext="strength.label"
        class="h-2 overflow-hidden rounded-full bg-slate-200">
        <div class="h-full rounded-full transition-all duration-150"
            x-bind:class="strength.score === 5 ? 'bg-emerald-500' : strength.score >= 3 ? 'bg-amber-500' : 'bg-rose-500'"
            x-bind:style="`width: ${strength.score * 20}%`"></div>
    </div>

    <ul id="{{ $id }}" class="space-y-1">
        @foreach (['length' => 'At least 8 characters', 'uppercase' => 'An uppercase letter', 'lowercase' => 'A lowercase letter', 'number' => 'A number', 'special' => 'A special character'] as $requirement => $label)
            <li x-bind:class="strength.requirements.{{ $requirement }} ? 'text-emerald-700' : 'text-slate-500'">
                <span aria-hidden="true" x-text="strength.requirements.{{ $requirement }} ? '✓' : '○'">○</span>
                <span class="sr-only" x-text="strength.requirements.{{ $requirement }} ? 'Met: ' : 'Required: '">{{ __('Required: ') }}</span>
                {{ __($label) }}
            </li>
        @endforeach
    </ul>
</div>
