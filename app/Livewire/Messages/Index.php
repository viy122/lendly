<?php

namespace App\Livewire\Messages;

use App\Models\RentalRequest;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    public function render(): View
    {
        $threads = RentalRequest::query()
            ->where(function ($query) {
                $query->where('renter_id', auth()->id())
                    ->orWhereHas('listing', fn ($q) => $q->where('owner_id', auth()->id()));
            })
            ->whereHas('messages')
            // RentalRequest::messages() orders ascending by default (oldest
            // first, for the chat thread view) — reorder() clears that
            // before applying a descending order, otherwise the extra
            // ORDER BY on the same column is a no-op and limit(1) grabs the
            // oldest message. Ordered by id, not created_at, since two
            // messages sent in the same second (routine in a fast test run,
            // possible in production too) would otherwise tie and make the
            // "latest" pick non-deterministic.
            ->with(['listing.owner', 'renter', 'messages' => fn ($q) => $q->reorder('id', 'desc')->limit(1)])
            ->get()
            ->map(function (RentalRequest $rentalRequest) {
                return [
                    'rentalRequest' => $rentalRequest,
                    'otherParty' => $rentalRequest->otherPartyFor(auth()->user()),
                    'lastMessage' => $rentalRequest->messages->first(),
                    'unreadCount' => $rentalRequest->messages()
                        ->where('receiver_id', auth()->id())
                        ->whereNull('read_at')
                        ->count(),
                ];
            })
            ->sortByDesc(fn ($thread) => $thread['lastMessage']?->created_at)
            ->values();

        return view('livewire.messages.index', ['threads' => $threads]);
    }
}
