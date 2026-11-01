@props(['owner' => null, 'ownerMessage' => 'The owner must complete their ID review before this item can receive new rental requests.'])
@php
    $viewerNeedsReview = auth()->check() && ! auth()->user()->isAdmin() && ! auth()->user()->fresh()?->hasVerifiedIdentity();
    $ownerNeedsReview = $owner && ! $owner->hasVerifiedIdentity();
@endphp
@if ($viewerNeedsReview || $ownerNeedsReview)
    <div {{ $attributes->merge(['class' => 'mx-4 my-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900 sm:mx-6']) }} role="status">
        <p class="font-semibold">Identity verification</p>
        @if ($viewerNeedsReview)
            <p>You can browse and save rental drafts. Approval is required to rent or list items.
                <a href="{{ route('identity.verify') }}" wire:navigate class="font-semibold underline underline-offset-4">Verify your identity</a>
            </p>
        @endif
        @if ($ownerNeedsReview)
            <p class="{{ $viewerNeedsReview ? 'mt-1' : '' }}">{{ $ownerMessage }}</p>
        @endif
    </div>
@endif
