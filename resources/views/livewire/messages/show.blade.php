<div>
    <x-page-header title="Messages" subtitle="Conversations with owners and renters about your listings and rentals." />

    <x-messages.shell :threads="$threads" :active-id="$rentalRequest->id">
        <a href="{{ route('messages.index') }}" wire:navigate class="flex items-center gap-1 border-b border-slate-100 px-4 py-3 text-sm font-medium text-slate-500 hover:text-slate-700 md:hidden">
            <x-icon name="chevron-down" class="h-3.5 w-3.5 rotate-90" /> Back to conversations
        </a>

        <div class="flex items-center gap-3 border-b border-slate-100 px-4 py-3">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm font-semibold text-blue-700">
                {{ Str::of($otherParty->name)->substr(0, 1)->upper() }}
            </span>
            <div class="min-w-0">
                <p class="truncate text-sm font-semibold text-slate-900">{{ $otherParty->name }}</p>
                <p class="truncate text-xs text-slate-500">{{ $rentalRequest->listing->name }}</p>
            </div>
        </div>

        @if (session('status'))
            <div class="mx-4 mt-3 rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-700">{{ session('status') }}</div>
        @endif

        <div class="flex-1 space-y-3 overflow-y-auto p-4" x-data x-init="$el.scrollTop = $el.scrollHeight" wire:key="thread-body-{{ $rentalRequest->id }}">
            @forelse ($rentalRequest->messages as $message)
                <div class="flex {{ $message->sender_id === auth()->id() ? 'justify-end' : 'justify-start' }}" wire:key="message-{{ $message->id }}">
                    <div class="max-w-md rounded-lg px-3 py-2 text-sm {{ $message->sender_id === auth()->id() ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-800' }}">
                        <p>{{ $message->body }}</p>
                        <p class="mt-1 text-xs {{ $message->sender_id === auth()->id() ? 'text-blue-100' : 'text-slate-400' }}">
                            {{ $message->created_at->format('M d, g:i A') }}
                        </p>
                    </div>
                </div>
            @empty
                <p class="text-center text-sm text-slate-400">No messages yet. Say hello!</p>
            @endforelse
        </div>

        <form wire:submit="send" class="flex gap-2 border-t border-slate-100 p-4">
            <input type="text" wire:model="body" placeholder="Type a message..." class="flex-1 rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <x-primary-button>Send</x-primary-button>
        </form>
        <x-input-error :messages="$errors->get('body')" class="mx-4 mb-3" />
    </x-messages.shell>
</div>
