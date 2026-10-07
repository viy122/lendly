<div wire:poll.5s.visible>
    <x-page-header title="Messages" subtitle="Conversations with owners and renters about your listings and rentals." />

    <x-messages.shell :threads="$threads" :search="$search">
        <div class="flex flex-1 flex-col items-center justify-center bg-slate-50/50 p-8 text-center">
            <span class="grid h-20 w-20 place-items-center rounded-3xl bg-gradient-to-br from-blue-100 to-indigo-100 text-blue-600"><x-icon name="chat" class="h-9 w-9" aria-hidden="true" /></span>
            <h2 class="mt-5 text-xl font-bold text-slate-900">Your conversations</h2>
            <p class="mt-2 max-w-xs text-sm leading-6 text-slate-500">Select a chat to ask about an item or keep your rental plans moving.</p>
        </div>
    </x-messages.shell>
</div>
