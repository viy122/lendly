@if (auth()->check() && ! auth()->user()->isAdmin() && ! auth()->user()->fresh()?->hasVerifiedIdentity())
    <div role="status" class="mx-4 my-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900 sm:mx-6">
        You can browse and save rental drafts. Approval is required to rent or list items.
        <a href="{{ route('identity.verify') }}" wire:navigate class="font-semibold underline underline-offset-4">Verify your identity</a>
    </div>
@endif
