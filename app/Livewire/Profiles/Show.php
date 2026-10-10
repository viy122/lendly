<?php

namespace App\Livewire\Profiles;

use App\Enums\RentalStatus;
use App\Enums\ReviewType;
use App\Models\Review;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.marketplace')]
class Show extends Component
{
    use WithPagination;

    public User $user;

    public function mount(User $user): void
    {
        $this->user = $user;
    }

    public function render(): View
    {
        abort_if($this->user->isAdmin() || $this->user->isSuspended() || ! $this->user->hasVerifiedEmail() || $this->user->trashed(), 404);

        $reviews = Review::query()
            ->whereHas('rental', fn ($query) => $query->where('status', RentalStatus::Completed))
            ->where(fn ($query) => $query
                ->where(fn ($query) => $query->where('type', ReviewType::RenterToOwner)
                    ->whereHas('rental', fn ($rental) => $rental->where('owner_id', $this->user->id)))
                ->orWhere(fn ($query) => $query->where('type', ReviewType::OwnerToRenter)
                    ->whereHas('rental', fn ($rental) => $rental->where('renter_id', $this->user->id))));

        return view('livewire.profiles.show', [
            'ownerAverageRating' => (clone $reviews)->where('type', ReviewType::RenterToOwner)->avg('rating'),
            'renterAverageRating' => (clone $reviews)->where('type', ReviewType::OwnerToRenter)->avg('rating'),
            'reviews' => $reviews->with([
                'rental:id,owner_id,renter_id',
                'rental.owner:id,name,role,status,email_verified_at,deleted_at',
                'rental.renter:id,name,role,status,email_verified_at,deleted_at',
            ])->latest()->orderByDesc('id')->paginate(12),
        ]);
    }
}
