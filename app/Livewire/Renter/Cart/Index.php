<?php

namespace App\Livewire\Renter\Cart;

use App\Models\CartItem;
use App\Models\RentalWaitlistEntry;
use App\Models\User;
use App\Services\RentalCart;
use App\Services\RentalPeriod;
use App\Services\RentalWaitlist;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.marketplace')]
class Index extends Component
{
    public array $selected = [];

    public array $edits = [];

    public bool $accept_terms = false;

    #[Locked]
    public array $reviewed = [];

    public function mount(): void
    {
        $items = $this->items();
        $this->selected = $items->pluck('id')->all();
        foreach ($items as $item) {
            $this->edits[$item->id] = $this->details($item);
        }
    }

    public function updatedSelected(): void
    {
        $this->member();
        $this->accept_terms = false;
        $this->resetErrorBag();
    }

    public function updatedEdits(): void
    {
        $this->member();
        $this->accept_terms = false;
    }

    public function updateItem(int $id): void
    {
        $member = $this->member();
        $this->accept_terms = false;
        $this->resetErrorBag();
        try {
            $item = RentalCart::update($member, $id, is_array($this->edits[$id] ?? null) ? $this->edits[$id] : []);
            $this->edits[$id] = $this->details($item);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError('items.'.$id.'.'.$field, $message);
                }
            }
        }
    }

    public function joinWaitlist(int $id): void
    {
        $item = CartItem::where('user_id', $this->member()->id)->findOrFail($id);
        RentalWaitlist::join($this->member(), $item->listing_id, $this->details($item));
        session()->flash('status', 'Joined the waitlist for the saved dates. No reservation or payment has been made.');
    }

    public function leaveWaitlist(int $id): void
    {
        RentalWaitlistEntry::where('user_id', $this->member()->id)->findOrFail($id)->delete();
        session()->flash('status', 'Left the waitlist.');
    }

    public function remove(int $id): void
    {
        RentalCart::remove($this->member(), $id);
        $this->selected = array_values(array_filter($this->selected, fn ($selected) => (string) $selected !== (string) $id));
        unset($this->edits[$id]);
        $this->accept_terms = false;
        $this->resetErrorBag();
    }

    public function checkout(): void
    {
        $member = $this->member();
        $this->resetErrorBag();
        $items = $this->items();
        foreach ($items as $item) {
            if (in_array((string) $item->id, array_map(fn ($id) => is_scalar($id) ? (string) $id : '', $this->selected), true)
                && ($this->edits[$item->id] ?? []) !== $this->details($item)) {
                $this->accept_terms = false;
                $this->addError('items.'.$item->id.'.dates', 'Save your date and handover changes before sending requests.');
            }
        }
        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        try {
            RentalCart::checkout($member, $this->selected, $this->accept_terms, $this->reviewed);
        } catch (ValidationException $exception) {
            $this->accept_terms = false;
            foreach ($this->items() as $item) {
                if (in_array($item->id, $this->selected)) {
                    $this->edits[$item->id] = $this->details($item);
                }
            }
            throw $exception;
        }
        session()->flash('status', 'Rental requests sent! Each owner will review your request.');
        $this->redirect(route('renter.rental-requests.index'), navigate: true);
    }

    public function selectAll(): void
    {
        $ids = $this->items()->pluck('id')->all();
        $selected = array_map(fn ($id) => is_scalar($id) ? (string) $id : '', $this->selected);
        $this->selected = count(array_diff(array_map('strval', $ids), $selected)) === 0 ? [] : $ids;
        $this->accept_terms = false;
        $this->resetErrorBag();
    }

    public function selectOwner(int $ownerId): void
    {
        $items = $this->items();
        $ownerIds = $items->filter(fn (CartItem $item) => $item->listing?->owner_id === $ownerId)->pluck('id')->all();
        $selected = $items->whereIn('id', $this->selected)->pluck('id')->all();
        $this->selected = count(array_diff($ownerIds, $selected)) === 0
            ? array_values(array_diff($selected, $ownerIds))
            : array_values(array_unique([...$selected, ...$ownerIds]));
        sort($this->selected);
        $this->accept_terms = false;
        $this->resetErrorBag();
    }

    private function member(): User
    {
        $member = User::find(auth()->id());
        abort_unless($member && ! $member->isAdmin() && ! $member->isSuspended() && $member->hasVerifiedEmail()
            && $member->activeInterface() === 'renter', 403);

        return $member;
    }

    private function items(): Collection
    {
        return CartItem::where('user_id', $this->member()->id)->with(['listing.images', 'listing.owner'])->orderBy('id')->get();
    }

    private function details(CartItem $item): array
    {
        return RentalPeriod::details($item);
    }

    public function render(): View
    {
        $items = $this->items();
        $reviewed = $items->mapWithKeys(function (CartItem $item) {
            $terms = $item->terms;
            ksort($terms);

            return [$item->id => $this->details($item) + ['terms' => $terms]];
        })->all();
        if ($this->reviewed && $this->reviewed !== $reviewed) {
            $this->accept_terms = false;
        }
        $this->reviewed = $reviewed;

        return view('livewire.renter.cart.index', [
            'waitlistEntries' => RentalWaitlistEntry::where('user_id', $this->member()->id)->with('listing')->orderBy('created_at')->orderBy('id')->get(),
            'items' => $items, 'groups' => $items->groupBy('listing.owner_id'),
            'selectedTotal' => round($items->whereIn('id', $this->selected)->sum(fn (CartItem $item) => $item->totalAmount()), 2),
        ]);
    }
}
