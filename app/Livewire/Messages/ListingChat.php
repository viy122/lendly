<?php

namespace App\Livewire\Messages;

use App\Enums\NotificationType;
use App\Livewire\Messages\Concerns\ListsThreads;
use App\Models\Listing;
use App\Models\ListingConversation;
use App\Notifications\TalaNotification;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.app')]
class ListingChat extends Component
{
    use ListsThreads;

    #[Locked]
    public ListingConversation $conversation;

    public string $body = '';

    public function mount(?Listing $listing = null, ?ListingConversation $conversation = null): void
    {
        if ($conversation?->exists) {
            $this->authorize('converse', $conversation);
            $this->conversation = $conversation;

            return;
        }

        abort_unless($listing?->exists, 404);
        $this->authorize('message', $listing);

        $this->conversation = ListingConversation::firstOrCreate([
            'listing_id' => $listing->id,
            'renter_id' => auth()->id(),
            'owner_id' => $listing->owner_id,
        ]);
    }

    public function send(): void
    {
        $this->conversation->refresh();
        $this->authorize('converse', $this->conversation);
        $this->body = trim($this->body);
        $this->validate(['body' => ['required', 'string', 'max:2000']]);

        $otherParty = $this->conversation->otherPartyFor(auth()->user());
        abort_if($otherParty->trashed() || $otherParty->isSuspended(), 403);

        $this->conversation->messages()->create([
            'sender_id' => auth()->id(),
            'receiver_id' => $otherParty->id,
            'body' => $this->body,
        ]);

        $otherParty->notify(new TalaNotification(
            NotificationType::NewMessage->value,
            'New message',
            auth()->user()->name.' sent you a message about "'.$this->conversation->listing->name.'".',
            route('listing-conversations.show', $this->conversation),
        ));

        $this->reset('body');
        $this->dispatch('message-sent');
    }

    public function render(): View
    {
        $this->conversation->refresh();
        $this->authorize('converse', $this->conversation);
        $this->conversation->messages()->where('receiver_id', auth()->id())
            ->whereNull('read_at')->update(['read_at' => now()]);
        $this->conversation->load(['listing.images', 'owner', 'renter', 'messages']);

        return view('livewire.messages.show', [
            'listing' => $this->conversation->listing,
            'messages' => $this->conversation->messages,
            'activeId' => 'listing-'.$this->conversation->id,
            'otherParty' => $this->conversation->otherPartyFor(auth()->user()),
            'threads' => $this->threadsForCurrentUser(),
        ]);
    }
}
