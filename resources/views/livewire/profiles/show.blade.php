<div>
    <x-page-header :title="$user->name" subtitle="Ratings and reviews from completed rentals." maxWidth="max-w-3xl">
        <x-slot name="actions">
            <a href="{{ route('listings.index') }}" wire:navigate class="text-sm font-semibold text-blue-700 hover:text-blue-900">Browse listings</a>
        </x-slot>
    </x-page-header>

    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex items-center gap-4">
            @if ($user->avatarUrl())
                <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" class="h-16 w-16 rounded-full object-cover">
            @else
                <span aria-hidden="true" class="flex h-16 w-16 items-center justify-center rounded-full bg-blue-100 text-2xl font-bold text-blue-700">{{ Str::of($user->name)->substr(0, 1)->upper() }}</span>
            @endif
            <p class="font-semibold text-slate-800">{{ $user->name }}</p>
        </div>

        <div class="mt-6 grid gap-4 sm:grid-cols-2">
            <x-stat-card label="As an owner" :value="$ownerAverageRating !== null ? number_format($ownerAverageRating, 1).' / 5' : 'No ratings yet'" icon="star" accent="amber" />
            <x-stat-card label="As a renter" :value="$renterAverageRating !== null ? number_format($renterAverageRating, 1).' / 5' : 'No ratings yet'" icon="star" accent="amber" />
        </div>

        <h2 class="mt-8 text-lg font-semibold text-slate-900">Received reviews <span class="text-sm font-normal text-slate-400">({{ $reviews->total() }})</span></h2>
        @if ($reviews->isEmpty())
            <div class="mt-3">
                <x-empty-state title="No reviews yet" message="Reviews from completed rentals will appear here." />
            </div>
        @else
            <div class="mt-3 space-y-4">
                @foreach ($reviews as $review)
                    @php
                        $author = $review->type === \App\Enums\ReviewType::RenterToOwner ? $review->rental->renter : $review->rental->owner;
                        $authorName = $author?->trashed() ? 'Deleted user' : ($author && ! $author->isAdmin() && ! $author->isSuspended() && $author->hasVerifiedEmail() ? $author->name : 'Unavailable user');
                    @endphp
                    <article wire:key="review-{{ $review->id }}" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-sm font-semibold text-slate-800">{{ $authorName }}</p>
                            <p class="flex items-center gap-1 text-sm font-medium text-slate-700">
                                <x-icon name="star" class="h-4 w-4 fill-amber-400 text-amber-400" />
                                {{ $review->rating }} / 5
                            </p>
                        </div>
                        <p class="mt-1 text-xs text-slate-400">{{ $review->type->label() }} · <time datetime="{{ $review->created_at->toDateString() }}">{{ $review->created_at->format('M d, Y') }}</time></p>
                        @if ($review->comment !== null && $review->comment !== '')
                            <p class="mt-2 whitespace-pre-line break-words text-sm text-slate-600">{{ $review->comment }}</p>
                        @endif
                    </article>
                @endforeach
            </div>

            <div class="mt-6">{{ $reviews->links() }}</div>
        @endif
    </div>
</div>
