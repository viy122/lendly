<div class="relative" x-data x-on:click.outside="$wire.open = false">
    <button type="button" wire:click="toggle" class="relative inline-flex items-center justify-center rounded-md border border-blue-200 bg-white p-1 text-slate-500 hover:bg-slate-100 hover:text-slate-700">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
        </svg>
        @if ($unreadCount > 0)
            <span class="absolute -right-0.5 -top-0.5 flex h-3 w-3 items-center justify-center rounded-full bg-rose-600 text-[8px] font-semibold text-white">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </button>

    <div x-show="$wire.open" x-cloak class="absolute right-0 z-50 mt-2 w-80 rounded-lg border border-slate-200 bg-white shadow-lg">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <p class="text-sm font-semibold text-slate-800">Notifications</p>
            @if ($unreadCount > 0)
                <button type="button" wire:click="markAllAsRead" class="text-xs font-medium text-blue-600 hover:text-blue-800">Mark all read</button>
            @endif
        </div>

        <div class="max-h-96 overflow-y-auto">
            @forelse ($notifications as $notification)
                <a
                    href="{{ $notification->data['url'] ?? '#' }}"
                    wire:navigate
                    wire:click="markAsRead('{{ $notification->id }}')"
                    class="block border-b border-slate-50 px-4 py-3 text-sm hover:bg-slate-50 {{ $notification->read_at ? 'opacity-60' : '' }}"
                >
                    <p class="font-medium text-slate-800">{{ $notification->data['title'] }}</p>
                    <p class="mt-0.5 text-xs text-slate-500">{{ $notification->data['message'] }}</p>
                    <p class="mt-1 text-xs text-slate-400">{{ $notification->created_at->diffForHumans() }}</p>
                </a>
            @empty
                <p class="px-4 py-6 text-center text-sm text-slate-400">You're all caught up.</p>
            @endforelse
        </div>

        <a href="{{ route('notifications.index') }}" wire:navigate class="block border-t border-slate-100 px-4 py-2 text-center text-xs font-medium text-blue-600 hover:text-blue-800">
            View all notifications
        </a>
    </div>
</div>
