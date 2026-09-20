<div class="mx-auto max-w-2xl px-4 py-8 sm:px-6 lg:px-8">
    <a href="{{ route('messages.index') }}" wire:navigate class="flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-700">
        <x-icon name="chevron-down" class="h-3.5 w-3.5 rotate-90" /> Back to messages
    </a>

    <div class="mt-3 flex items-center gap-3">
        <h1 class="text-xl font-semibold text-slate-900">Chat with {{ $otherParty->name }}</h1>
    </div>
    <p class="mt-1 text-sm text-slate-500">About: {{ $rentalRequest->listing->name }}</p>

    @if (session('status'))
        <div class="mt-4 rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-700">{{ session('status') }}</div>
    @endif

    <div class="mt-6 flex h-96 flex-col rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex-1 space-y-3 overflow-y-auto">
            @forelse ($rentalRequest->messages as $message)
                <div class="flex {{ $message->sender_id === auth()->id() ? 'justify-end' : 'justify-start' }}" wire:key="message-{{ $message->id }}">
                    <div class="max-w-xs rounded-lg px-3 py-2 text-sm {{ $message->sender_id === auth()->id() ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-800' }}">
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

        <form wire:submit="send" class="mt-4 flex gap-2 border-t border-slate-100 pt-4">
            <input type="text" wire:model="body" placeholder="Type a message..." class="flex-1 rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <x-primary-button>Send</x-primary-button>
        </form>
        <x-input-error :messages="$errors->get('body')" class="mt-2" />
    </div>

</div>
