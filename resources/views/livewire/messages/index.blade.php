<div>
    <x-page-header title="Messages" subtitle="Conversations with owners and renters about your listings and rentals." maxWidth="max-w-3xl" />

    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
        @if ($threads->isEmpty())
            <x-empty-state title="No conversations yet" message="Messages you send or receive about a listing or rental request will show up here." />
        @else
            <div class="space-y-3">
                @foreach ($threads as $thread)
                    <a
                        href="{{ $thread['url'] }}"
                        wire:navigate
                        wire:key="thread-{{ $thread['key'] }}"
                        class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-4 transition hover:border-blue-300 hover:shadow-sm {{ $thread['unreadCount'] > 0 ? 'bg-blue-50/50' : '' }}"
                    >
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm font-semibold text-blue-700">
                            {{ Str::of($thread['otherParty']->name)->substr(0, 1)->upper() }}
                        </span>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <p class="truncate text-sm font-semibold text-slate-800">{{ $thread['otherParty']->name }}</p>
                                @if ($thread['lastMessage'])
                                    <span class="shrink-0 text-xs text-slate-400">{{ $thread['lastMessage']->created_at->diffForHumans() }}</span>
                                @endif
                            </div>
                            <p class="truncate text-xs text-slate-500">{{ $thread['listing']->name }}</p>
                            @if ($thread['lastMessage'])
                                <p class="mt-1 truncate text-sm text-slate-600">{{ $thread['lastMessage']->body }}</p>
                            @endif
                        </div>

                        @if ($thread['unreadCount'] > 0)
                            <span class="flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-rose-600 px-1.5 text-xs font-semibold text-white">
                                {{ $thread['unreadCount'] }}
                            </span>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
