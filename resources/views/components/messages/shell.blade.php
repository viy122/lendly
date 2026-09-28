@props(['threads', 'activeId' => null])

<div class="w-full px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex h-[calc(100vh-9.5rem)] min-h-[32rem] overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex w-full max-w-xs shrink-0 flex-col border-r border-slate-200 {{ $activeId ? 'hidden md:flex' : 'flex' }}">
            <div class="border-b border-slate-100 px-4 py-3">
                <h2 class="text-sm font-semibold text-slate-800">Conversations</h2>
            </div>

            <div class="flex-1 overflow-y-auto">
                @forelse ($threads as $thread)
                    <a
                        href="{{ route('rental-requests.chat', $thread['rentalRequest']) }}"
                        wire:navigate
                        wire:key="thread-{{ $thread['rentalRequest']->id }}"
                        class="flex items-center gap-3 border-b border-slate-50 px-4 py-3 transition hover:bg-slate-50 {{ $activeId === $thread['rentalRequest']->id ? 'bg-blue-50' : '' }}"
                    >
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm font-semibold text-blue-700">
                            {{ Str::of($thread['otherParty']->name)->substr(0, 1)->upper() }}
                        </span>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <p class="truncate text-sm font-semibold text-slate-800">{{ $thread['otherParty']->name }}</p>
                                @if ($thread['lastMessage'])
                                    <span class="shrink-0 text-xs text-slate-400">{{ $thread['lastMessage']->created_at->diffForHumans(null, true) }}</span>
                                @endif
                            </div>
                            <p class="truncate text-xs text-slate-500">{{ $thread['rentalRequest']->listing->name }}</p>
                            @if ($thread['lastMessage'])
                                <p class="mt-0.5 truncate text-xs text-slate-500">{{ $thread['lastMessage']->body }}</p>
                            @endif
                        </div>

                        @if ($thread['unreadCount'] > 0)
                            <span class="flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-rose-600 px-1.5 text-[11px] font-semibold text-white">
                                {{ $thread['unreadCount'] }}
                            </span>
                        @endif
                    </a>
                @empty
                    <div class="p-6">
                        <x-empty-state title="No conversations yet" message="Messages you send or receive about a rental request will show up here." />
                    </div>
                @endforelse
            </div>
        </div>

        <div class="flex min-w-0 flex-1 flex-col {{ $activeId ? 'flex' : 'hidden md:flex' }}">
            {{ $slot }}
        </div>
    </div>
</div>
