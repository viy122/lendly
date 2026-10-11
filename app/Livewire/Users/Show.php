<?php

namespace App\Livewire\Users;

use App\Enums\ReviewType;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.marketplace')]
class Show extends Component
{
    use WithPagination;

    #[Locked]
    public int $userId;

    public function mount(User $user): void
    {
        abort_if($user->isAdmin(), 404);
        $this->userId = $user->id;
    }

    public function render(): View
    {
        // Keep contact details and private transaction data out of the public view.
        $user = User::select(['id', 'name', 'avatar_path', 'role', 'status', 'email_verified_at', 'created_at'])->findOrFail($this->userId);
        abort_if($user->isAdmin() || $user->isSuspended() || ! $user->hasVerifiedEmail(), 404);
        $receivedReviews = $user->receivedReviews();
        $summary = (clone $receivedReviews)->selectRaw('type, COUNT(*) as total, AVG(rating) as average')
            ->groupBy('type')->get()->keyBy(fn ($review) => $review->type->value);

        return view('livewire.users.show', [
            'user' => $user,
            'ownerSummary' => $summary->get(ReviewType::RenterToOwner->value),
            'renterSummary' => $summary->get(ReviewType::OwnerToRenter->value),
            'reviews' => $receivedReviews
                ->with(['rental:id,owner_id,renter_id', 'rental.owner:id,name,role,status,email_verified_at,deleted_at', 'rental.renter:id,name,role,status,email_verified_at,deleted_at'])
                ->latest()->orderByDesc('id')->paginate(10),
        ]);
    }
}
