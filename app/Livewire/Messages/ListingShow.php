<?php

namespace App\Livewire\Messages;

use App\Enums\NotificationType;
use App\Models\Listing;
use App\Models\ListingConversation;
use App\Models\Message;
use App\Models\User;
use App\Notifications\TalaNotification;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.app')]
class ListingShow extends Component
{
    #[Locked]
    public ListingConversation $conversation;

    public string $body = '';

    public function mount(?Listing $listing = null, ?ListingConversation $conversation = null): void
    {
        $user = $this->member();

        if ($listing?->exists) {
            $owner = $listing->owner;
            abort_unless(! $listing->trashed() && $listing->isPublished()
                && $listing->owner_id !== $user->id
                && $owner && ! $owner->trashed() && ! $owner->isSuspended() && ! $owner->isAdmin(), 403);

            $conversation = ListingConversation::firstOrCreate(
                ['listing_id' => $listing->id, 'renter_id' => $user->id],
                ['owner_id' => $listing->owner_id],
            );
        }

        abort_unless($conversation?->exists, 404);
        $this->conversation = $conversation;
        $this->authorizeConversation();
        $this->conversation->load(['listing', 'owner', 'renter', 'messages.sender']);
        $this->conversation->messages()->where('receiver_id', $user->id)
            ->whereNull('read_at')->update(['read_at' => now()]);
    }

    private function member(): User
    {
        $user = auth()->user()?->fresh();
        abort_unless($user && ! $user->trashed() && $user->hasVerifiedEmail()
            && ! $user->isAdmin() && ! $user->isSuspended(), 403);

        return $user;
    }

    private function authorizeConversation(): void
    {
        $user = $this->member();
        abort_unless(in_array($user->id, [$this->conversation->owner_id, $this->conversation->renter_id], true), 403);
    }

    public function send(): void
    {
        $this->conversation->refresh()->load(['listing', 'owner', 'renter']);
        $this->authorizeConversation();
        $otherParty = $this->conversation->otherPartyFor(auth()->user());
        abort_if($otherParty->trashed() || $otherParty->isSuspended() || $otherParty->isAdmin(), 403);

        $this->body = trim($this->body);
        $this->validate(['body' => ['required', 'string', 'max:2000']]);

        Message::create([
            'listing_conversation_id' => $this->conversation->id,
            'sender_id' => auth()->id(),
            'receiver_id' => $otherParty->id,
            'body' => $this->body,
        ]);

        $otherParty->notify(new TalaNotification(
            NotificationType::NewMessage->value,
            'New message',
            auth()->user()->name.' sent you a message about "'.$this->conversation->listing->name.'".',
            route('messages.listing', $this->conversation),
        ));

        $this->reset('body');
        $this->conversation->load('messages.sender');
    }

    public function render(): View
    {
        $this->authorizeConversation();
        $otherParty = $this->conversation->otherPartyFor(auth()->user());

        return view('livewire.messages.listing-show', [
            'otherParty' => $otherParty,
            'canSend' => ! $otherParty->trashed() && ! $otherParty->isSuspended() && ! $otherParty->isAdmin(),
        ]);
    }
}
