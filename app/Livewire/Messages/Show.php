<?php

namespace App\Livewire\Messages;

use App\Enums\NotificationType;
use App\Livewire\Messages\Concerns\ListsThreads;
use App\Models\Message;
use App\Models\RentalRequest;
use App\Notifications\TalaNotification;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Show extends Component
{
    use ListsThreads;

    public RentalRequest $rentalRequest;

    public string $body = '';

    public function mount(RentalRequest $rentalRequest): void
    {
        $this->authorize('converse', $rentalRequest);

        $this->rentalRequest = $rentalRequest->load(['listing.owner', 'renter', 'messages.sender']);

        $this->rentalRequest->messages()
            ->where('receiver_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function send(): void
    {
        $this->authorize('converse', $this->rentalRequest);
        $this->validate(['body' => ['required', 'string', 'max:2000']]);

        $otherParty = $this->rentalRequest->otherPartyFor(auth()->user());
        $this->authorize('converse', $this->rentalRequest);
        abort_if($otherParty->trashed() || $otherParty->isSuspended(), 403, 'This account is unavailable for new messages.');

        Message::create([
            'rental_request_id' => $this->rentalRequest->id,
            'sender_id' => auth()->id(),
            'receiver_id' => $otherParty->id,
            'body' => $this->body,
        ]);

        $otherParty->notify(new TalaNotification(
            NotificationType::NewMessage->value,
            'New message',
            auth()->user()->name.' sent you a message about "'.$this->rentalRequest->listing->name.'".',
            route('rental-requests.chat', $this->rentalRequest),
        ));

        $this->reset('body');
        $this->rentalRequest->refresh()->load('messages.sender');
        $this->dispatch('message-sent');
    }

    public function render(): View
    {
        $this->rentalRequest->refresh();
        $this->authorize('converse', $this->rentalRequest);
        $this->rentalRequest->messages()->where('receiver_id', auth()->id())
            ->whereNull('read_at')->update(['read_at' => now()]);
        $this->rentalRequest->load(['listing.images', 'listing.owner', 'renter', 'messages.sender']);

        return view('livewire.messages.show', [
            'listing' => $this->rentalRequest->listing,
            'messages' => $this->rentalRequest->messages,
            'activeId' => $this->rentalRequest->id,
            'otherParty' => $this->rentalRequest->otherPartyFor(auth()->user()),
            'threads' => $this->threadsForCurrentUser(),
        ]);
    }
}
