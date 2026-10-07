@props(['threads', 'activeId' => null, 'search' => ''])

<div class="w-full px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex h-[calc(100dvh-11rem)] min-h-[32rem] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <aside aria-label="Conversation list" class="w-full shrink-0 flex-col border-r border-slate-100 md:w-72 xl:w-80 {{ $activeId ? 'hidden md:flex' : 'flex' }}">
            <div class="space-y-4 px-5 pb-4 pt-5">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-xl font-bold tracking-tight text-slate-900">Chats</h2>
                    <span class="grid h-9 w-9 place-items-center rounded-full bg-blue-50 text-blue-600"><x-icon name="chat" class="h-5 w-5" aria-hidden="true" /></span>
                </div>
                <div class="relative">
                    <label for="conversation-search" class="sr-only">Search conversations</label>
                    <x-icon name="search" class="pointer-events-none absolute left-3.5 top-3 h-4 w-4 text-slate-400" aria-hidden="true" />
                    <input id="conversation-search" type="search" wire:model.live.debounce.250ms="search" placeholder="Search people or items" autocomplete="off" class="h-10 w-full rounded-full border-0 bg-slate-100 pl-10 pr-4 text-sm text-slate-700 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-blue-500" />
                </div>
            </div>

            <div class="min-h-0 flex-1 space-y-1 overflow-y-auto px-2 pb-3">
                @forelse ($threads as $thread)
                    <a
                        href="{{ $thread['url'] }}"
                        wire:navigate
                        wire:key="thread-{{ $thread['id'] }}"
                        aria-current="{{ $activeId === $thread['id'] ? 'page' : 'false' }}"
                        class="flex items-center gap-3 rounded-xl px-3 py-3 transition focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500 {{ $activeId === $thread['id'] ? 'bg-blue-50' : 'hover:bg-slate-50' }}"
                    >
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-blue-100 to-indigo-100 text-base font-semibold text-blue-700">
                            @if ($thread['otherParty']->avatarUrl())
                                <img src="{{ $thread['otherParty']->avatarUrl() }}" alt="" class="h-full w-full object-cover" />
                            @else
                                {{ Str::of($thread['otherParty']->name)->substr(0, 1)->upper() }}
                            @endif
                        </span>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ $thread['otherParty']->name }}</p>
                                @if ($thread['lastMessage'])
                                    <span class="shrink-0 text-[10px] text-slate-400">{{ $thread['lastMessage']->created_at->diffForHumans(null, true, true) }}</span>
                                @endif
                            </div>
                            <p class="truncate text-xs text-slate-500">{{ $thread['listing']->name }}</p>
                            @if ($thread['lastMessage'])
                                <p class="mt-1 truncate text-xs {{ $thread['unreadCount'] > 0 ? 'font-semibold text-slate-800' : 'text-slate-500' }}">{{ $thread['lastMessage']->sender_id === auth()->id() ? 'You: ' : '' }}{{ $thread['lastMessage']->body }}</p>
                            @endif
                        </div>

                        @if ($thread['unreadCount'] > 0)
                            <span class="flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-blue-600 px-1.5 text-[11px] font-semibold text-white">
                                {{ $thread['unreadCount'] }}
                            </span>
                        @endif
                    </a>
                @empty
                    <div class="p-6">
                        @if (trim($search) !== '')
                            <div class="py-8 text-center" role="status">
                                <x-icon name="search" class="mx-auto h-8 w-8 text-slate-300" aria-hidden="true" />
                                <p class="mt-3 text-sm font-semibold text-slate-700">No conversations found</p>
                                <p class="mt-1 text-xs leading-5 text-slate-500">Try another name or item.</p>
                                <button type="button" wire:click="$set('search', '')" class="mt-3 rounded-lg px-3 py-2 text-xs font-semibold text-blue-600 hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500">Clear search</button>
                            </div>
                        @else
                            <x-empty-state title="No conversations yet" message="Messages about items and rental requests will show up here." />
                        @endif
                    </div>
                @endforelse
            </div>
        </aside>

        <section aria-label="Conversation" class="min-w-0 flex-1 flex-col {{ $activeId ? 'flex' : 'hidden md:flex' }}">
            {{ $slot }}
        </section>
    </div>
</div>
