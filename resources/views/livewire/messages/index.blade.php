<div>
    <x-page-header title="Messages" subtitle="Conversations with owners and renters about your listings and rentals." />

    <x-messages.shell :threads="$threads">
        <div class="flex flex-1 flex-col items-center justify-center gap-2 p-6 text-slate-400">
            <x-icon name="chat" class="h-10 w-10" />
            <p class="text-sm">Select a conversation to start chatting</p>
        </div>
    </x-messages.shell>
</div>
