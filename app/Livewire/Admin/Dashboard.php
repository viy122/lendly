<?php

namespace App\Livewire\Admin;

use App\Enums\DisputeStatus;
use App\Enums\ListingStatus;
use App\Enums\RentalStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Dispute;
use App\Models\Listing;
use App\Models\Rental;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function toggleSuspension(int $userId): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        $user = User::findOrFail($userId);

        abort_if($user->isAdmin(), 403, 'Admin accounts cannot be suspended.');
        abort_if($user->id === auth()->id(), 403);

        if ($user->isSuspended()) {
            $user->update(['status' => UserStatus::Active, 'suspended_at' => null, 'suspension_reason' => null]);
        } else {
            $user->update(['status' => UserStatus::Suspended, 'suspended_at' => now(), 'suspension_reason' => 'Suspended by admin']);
        }
    }

    public function render(): View
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        $users = User::query()
            ->when($this->search, fn ($query) => $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")
            ))
            ->orderByDesc('created_at')
            ->paginate(10);

        $paidRentals = Rental::query()->whereNotNull('paid_at');

        $revenueByMonth = (clone $paidRentals)
            ->where('paid_at', '>=', now()->subMonths(5)->startOfMonth())
            ->get(['paid_at', 'commission_amount'])
            ->groupBy(fn (Rental $rental) => $rental->paid_at->format('Y-m'))
            ->map(fn ($group) => $group->sum('commission_amount'));

        $monthlyRevenue = collect(range(5, 0))->map(function (int $monthsAgo) use ($revenueByMonth) {
            $date = now()->subMonths($monthsAgo);
            $key = $date->format('Y-m');
            $total = (float) ($revenueByMonth[$key] ?? 0);

            return [
                'label' => $date->format('M'),
                'value' => $total,
                'display' => '₱'.number_format($total, 0),
                'color' => 'bg-blue-600',
            ];
        });

        // Every member can act as both an owner and a renter, so these are
        // deliberately not a partition (a member with both a listing and a
        // rental request counts in both bars) — they measure actual usage
        // of each capability, not a mutually-exclusive account type anymore.
        $activeOwnersCount = User::has('listings')->count();
        $activeRentersCount = User::has('rentalRequests')->count();

        $usersByRole = [
            ['label' => 'Active as Owner', 'value' => $activeOwnersCount, 'color' => 'bg-blue-600'],
            ['label' => 'Active as Renter', 'value' => $activeRentersCount, 'color' => 'bg-slate-500'],
            ['label' => 'Admins', 'value' => User::where('role', UserRole::Admin)->count(), 'color' => 'bg-amber-500'],
        ];

        $rentalsByStatus = collect(RentalStatus::cases())->map(fn (RentalStatus $status) => [
            'label' => $status->label(),
            'value' => Rental::withCurrentStatus($status)->count(),
            'color' => match ($status->badgeColor()) {
                'amber' => '#d97706',
                'teal' => '#2563eb',
                'green' => '#059669',
                'red' => '#e11d48',
                default => '#94a3b8',
            },
        ])->filter(fn ($row) => $row['value'] > 0)->values();

        return view('livewire.admin.dashboard', [
            'users' => $users,
            'totalUsers' => User::count(),
            'activeOwnersCount' => $activeOwnersCount,
            'activeRentersCount' => $activeRentersCount,
            'suspendedUsers' => User::where('status', UserStatus::Suspended)->count(),
            'activeListingsCount' => Listing::where('status', ListingStatus::Published)->count(),
            'pendingListingsCount' => Listing::where('status', ListingStatus::PendingApproval)->count(),
            'activeRentalsCount' => Rental::withCurrentStatus(RentalStatus::Active)->count(),
            'completedRentalsCount' => Rental::where('status', RentalStatus::Completed)->count(),
            'overdueRentalsCount' => Rental::withCurrentStatus(RentalStatus::Overdue)->count(),
            'openDisputesCount' => Dispute::where('status', DisputeStatus::Open)->count(),
            'transactionValue' => (clone $paidRentals)->sum('total_amount'),
            'platformRevenue' => (clone $paidRentals)->sum('commission_amount'),
            'monthlyRevenue' => $monthlyRevenue,
            'usersByRole' => $usersByRole,
            'rentalsByStatus' => $rentalsByStatus,
        ]);
    }
}
