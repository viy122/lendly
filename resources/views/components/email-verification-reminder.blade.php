@auth
    @if (! auth()->user()->isAdmin() && ! auth()->user()->hasVerifiedEmail())
        <div class="border-b border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-900 sm:px-6" role="status">
            {{ __('Your email is still unverified. You can keep using your account and verify it later.') }}
            <a href="{{ route('verification.notice') }}" wire:navigate class="ml-1 font-semibold underline focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">{{ __('Verify email') }}</a>
        </div>
    @endif
@endauth
