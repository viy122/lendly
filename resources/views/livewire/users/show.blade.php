<div class="public-profile-page min-h-screen bg-slate-50">
    <x-page-header eyebrow="Community" title="Public profile" subtitle="Ratings and reviews from completed rentals." />

    <div class="public-profile-content w-full px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('listings.index') }}" wire:navigate class="inline-flex min-h-10 items-center gap-2 rounded-lg text-sm font-semibold text-slate-600 transition hover:text-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-100">
                <x-icon name="chevron-down" class="h-4 w-4 rotate-90" aria-hidden="true" /> Back to browse
            </a>
            <span class="inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700"><x-icon name="user-circle" class="h-4 w-4" aria-hidden="true" /> Lendly community</span>
        </div>

        <div class="public-profile-grid">
            <aside class="public-profile-identity min-w-0" aria-label="Member profile and ratings">
                <section class="overflow-hidden rounded-2xl border border-blue-200 bg-white shadow-sm" aria-labelledby="member-name">
                    <div class="border-b border-blue-100 bg-gradient-to-br from-blue-50 via-white to-indigo-50 p-6 sm:p-7">
                        @if ($user->avatarUrl())
                            <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}’s profile photo" class="h-24 w-24 rounded-2xl border-4 border-white object-cover shadow-sm" />
                        @else
                            <span class="grid h-24 w-24 place-items-center rounded-2xl border-4 border-white bg-gradient-to-br from-blue-100 to-indigo-100 text-4xl font-bold text-blue-700 shadow-sm" aria-hidden="true">{{ Str::of($user->name)->substr(0, 1)->upper() }}</span>
                        @endif
                        <p class="mb-2 mt-5 text-xs font-semibold uppercase tracking-wider text-blue-600">Community member</p>
                        <h2 id="member-name" class="break-words text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">{{ $user->name }}</h2>
                        <p class="mt-3 flex items-center gap-2 text-sm text-slate-500"><x-icon name="user-circle" class="h-4 w-4 shrink-0" aria-hidden="true" /> Member since {{ $user->created_at->format('M Y') }}</p>
                    </div>

                    <div class="p-6 sm:p-7">
                        <h3 class="text-sm font-bold text-slate-900">Rental reputation</h3>
                        <p class="mt-1 text-xs leading-5 text-slate-500">Feedback from owners and renters.</p>
                        <div class="mt-5 space-y-4">
                            @foreach (['As an owner' => $ownerSummary, 'As a renter' => $renterSummary] as $label => $summary)
                                <section class="rounded-xl border border-slate-100 bg-slate-50 p-4" aria-label="{{ $label }} ratings">
                                    <div class="flex items-center justify-between gap-3">
                                        <h4 class="flex items-center gap-2 text-sm font-semibold text-slate-800"><x-icon :name="$label === 'As an owner' ? 'archive' : 'user-circle'" class="h-4 w-4 text-blue-600" aria-hidden="true" /> {{ $label }}</h4>
                                        <x-icon name="star" class="h-5 w-5 {{ $summary ? 'fill-amber-400 text-amber-400' : 'text-slate-300' }}" aria-hidden="true" />
                                    </div>
                                    @if ($summary)
                                        <p class="mt-3 text-sm leading-6 text-slate-600">{{ number_format($summary->average, 1) }} out of 5 · {{ $summary->total }} {{ Str::plural('review', $summary->total) }}</p>
                                    @else
                                        <p class="mt-3 text-sm text-slate-500">No ratings yet</p>
                                    @endif
                                </section>
                            @endforeach
                        </div>
                    </div>
                </section>
            </aside>

            <section class="min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="received-reviews">
                <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 p-6 sm:p-7">
                    <div class="flex min-w-0 items-start gap-3">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-blue-50 text-blue-600"><x-icon name="star" class="h-5 w-5" aria-hidden="true" /></span>
                        <div class="min-w-0">
                            <h2 id="received-reviews" class="text-lg font-bold text-slate-900 sm:text-xl">Received reviews ({{ $reviews->total() }})</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-500">See what other members say about renting with {{ $user->name }}.</p>
                        </div>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-600">Completed rentals</span>
                </div>

                <div class="divide-y divide-slate-100 px-6 sm:px-7">
                    @forelse ($reviews as $review)
                        @php
                            $asOwner = $review->type === \App\Enums\ReviewType::RenterToOwner;
                            $reviewer = $asOwner ? $review->rental->renter : $review->rental->owner;
                        @endphp
                        <article class="py-6 sm:py-7" wire:key="received-review-{{ $review->id }}">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div class="flex min-w-0 items-center gap-3">
                                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-gradient-to-br from-blue-100 to-indigo-100 text-sm font-semibold text-blue-700" aria-hidden="true">{{ Str::of($reviewer?->name ?? 'Deleted account')->substr(0, 1)->upper() }}</span>
                                    <div class="min-w-0">
                                        @if ($reviewer && ! $reviewer->trashed() && ! $reviewer->isAdmin())
                                            <a href="{{ route('users.show', $reviewer) }}" wire:navigate class="break-words rounded text-sm font-semibold text-slate-900 transition hover:text-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-100">{{ $reviewer->name }}</a>
                                        @else
                                            <p class="break-words text-sm font-semibold text-slate-800">{{ $reviewer?->name ?? 'Deleted account' }}</p>
                                        @endif
                                        <p class="mt-1 text-xs text-slate-500">{{ $review->created_at->format('M d, Y') }}</p>
                                    </div>
                                </div>
                                <div class="flex flex-col items-end gap-2">
                                    <span class="flex items-center gap-1" role="img" aria-label="{{ $review->rating }} out of 5 stars">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <x-icon name="star" class="h-5 w-5 {{ $i <= $review->rating ? 'fill-amber-400 text-amber-400' : 'text-slate-200' }}" aria-hidden="true" />
                                        @endfor
                                    </span>
                                    <span class="rounded-full bg-blue-50 px-2.5 py-1 text-[11px] font-medium text-blue-700">{{ $asOwner ? 'As an owner' : 'As a renter' }}</span>
                                </div>
                            </div>
                            @if ($review->comment !== null && trim($review->comment) !== '')
                                <p class="mt-4 whitespace-pre-line break-words text-sm leading-7 text-slate-600">{{ $review->comment }}</p>
                            @else
                                <p class="mt-4 text-sm text-slate-400">Rating only</p>
                            @endif
                        </article>
                    @empty
                        <div class="flex min-h-80 flex-col items-center justify-center px-4 py-12 text-center sm:py-16">
                            <span class="grid h-20 w-20 place-items-center rounded-3xl bg-gradient-to-br from-blue-50 to-indigo-50 text-blue-400"><x-icon name="chat" class="h-9 w-9" aria-hidden="true" /></span>
                            <h3 class="mt-5 text-lg font-bold text-slate-800">No reviews yet</h3>
                            <p class="mt-2 max-w-sm text-sm leading-6 text-slate-500">Reviews received after completed rentals will appear here.</p>
                            <p class="mt-4 max-w-sm text-xs leading-5 text-slate-400">Members can optionally leave a rating and comment after a rental closes.</p>
                        </div>
                    @endforelse
                </div>
                @if ($reviews->hasPages())
                    <div class="border-t border-slate-100 p-6 sm:p-7">{{ $reviews->links() }}</div>
                @endif
            </section>
        </div>
    </div>
</div>
