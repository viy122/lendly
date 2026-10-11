<?php

namespace App\Livewire\Member;

use App\Enums\ListingStatus;
use App\Enums\RentalRequestStatus;
use App\Enums\RentalStatus;
use App\Models\Rental;
use App\Models\RentalRequest;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Show only the dashboard data belonging to the selected interface. */
#[Layout('layouts.app')]
class Dashboard extends Component
{
    public function mount(): void
    {
        if (auth()->user()->isAdmin()) {
            $this->redirect(route('admin.dashboard', absolute: false));
        }
    }

    public function render(): View
    {
        $interface = auth()->user()->activeInterface();

        return view('livewire.member.dashboard', array_merge(
            ['interface' => $interface],
            $interface === 'owner' ? $this->ownerData() : $this->renterData(),
        ));
    }

    private function ownerData(): array
    {
        $listings = auth()->user()->listings();

        $pendingRequests = RentalRequest::query()
            ->whereHas('listing', fn ($query) => $query->where('owner_id', auth()->id()))
            ->where('status', RentalRequestStatus::Requested)
            ->with(['listing.images', 'renter'])
            ->latest()
            ->take(5)
            ->get();

        // Use the same retained income for totals, trends, and listing rankings.
        // Eager loading prevents one payment/dispute query per transaction.
        $paidRentals = Rental::query()
            ->where('owner_id', auth()->id())
            ->whereNotNull('paid_at')
            ->with(['payment', 'disputes'])
            ->get();

        $totalEarnings = round($paidRentals->sum(fn (Rental $rental) => $rental->ownerEarnings()), 2);

        // Grouped in PHP (not a raw DATE_FORMAT/strftime query) so this works
        // identically on MySQL in production and SQLite in tests.
        // Restate each payment month after subsequent refunds or cancellation.
        $earningsByMonth = $paidRentals
            ->filter(fn (Rental $rental) => $rental->paid_at->gte(now()->startOfMonth()->subMonths(5)))
            ->groupBy(fn (Rental $rental) => $rental->paid_at->format('Y-m'))
            ->map(fn ($group) => round($group->sum(fn (Rental $rental) => $rental->ownerEarnings()), 2));

        $monthlyEarnings = collect(range(5, 0))->map(function (int $monthsAgo) use ($earningsByMonth) {
            $date = now()->startOfMonth()->subMonths($monthsAgo);
            $key = $date->format('Y-m');
            $total = (float) ($earningsByMonth[$key] ?? 0);

            return [
                'label' => $date->format('M'),
                'value' => $total,
                'display' => '₱'.number_format($total, 2),
                'color' => 'bg-blue-600',
            ];
        });

        $rentalsByStatus = collect(RentalStatus::cases())->map(function (RentalStatus $status) {
            return [
                'label' => $status->label(),
                'value' => Rental::where('owner_id', auth()->id())->withCurrentStatus($status)->count(),
                'color' => match ($status->badgeColor()) {
                    'amber' => '#d97706',
                    'teal' => '#2563eb',
                    'green' => '#059669',
                    'red' => '#e11d48',
                    default => '#94a3b8',
                },
            ];
        })->filter(fn ($row) => $row['value'] > 0)->values();

        // Filtering "> 0" in PHP rather than a query HAVING clause — SQLite
        // (used in tests) rejects HAVING on a computed column without an
        // explicit GROUP BY, even though MySQL allows it.
        $mostRentedListing = (clone $listings)
            ->withCount('rentals')
            ->orderByDesc('rentals_count')
            ->first();
        $mostRentedListing = $mostRentedListing?->rentals_count > 0 ? $mostRentedListing : null;

        $revenueByListing = $paidRentals
            ->groupBy('listing_id')
            ->map(fn ($group) => round($group->sum(fn (Rental $rental) => $rental->ownerEarnings()), 2))
            ->filter(fn ($revenue) => $revenue > 0)
            ->sortDesc();
        $mostProfitableListing = $revenueByListing->isNotEmpty()
            ? (clone $listings)->withTrashed()->find($revenueByListing->keys()->first())
            : null;
        $mostProfitableListing?->setAttribute('revenue', $revenueByListing->first());

        $completedRentals = Rental::query()
            ->where('owner_id', auth()->id())
            ->where('status', RentalStatus::Completed)
            ->with(['listing', 'renter', 'payment', 'disputes'])
            ->orderByDesc('completed_at')
            ->orderByDesc('id')
            ->take(5)
            ->get();

        // Fixed category -> validated-palette color order (dataviz skill's
        // default categorical ramp, adjacent-pair CVD-safe) — assigned once,
        // by name, so a category's color never shifts as counts change.
        $categoryChartColors = [
            'Tools & Equipment' => '#2a78d6',
            'Photography & Video' => '#eb6834',
            'Events & Party' => '#1baf7a',
            'Outdoor & Sports' => '#eda100',
            'Vehicles' => '#e87ba4',
            'Electronics' => '#008300',
            'Home Appliances' => '#4a3aa7',
        ];

        $listingsByCategory = (clone $listings)
            ->with('category')
            ->get()
            ->groupBy(fn ($listing) => $listing->category->name)
            ->map(fn ($group, $name) => [
                'label' => $name,
                'value' => $group->count(),
                'color' => $categoryChartColors[$name] ?? '#898781',
            ])
            ->sortByDesc('value')
            ->values();

        return [
            'activeListingsCount' => (clone $listings)->where('status', ListingStatus::Published)->count(),
            'pendingListingsCount' => (clone $listings)->where('status', ListingStatus::PendingApproval)->count(),
            'recentListings' => (clone $listings)->with(['category', 'images'])->latest()->take(5)->get(),
            'pendingRequests' => $pendingRequests,
            'pendingRequestsCount' => RentalRequest::query()
                ->whereHas('listing', fn ($query) => $query->where('owner_id', auth()->id()))
                ->where('status', RentalRequestStatus::Requested)
                ->count(),
            'totalEarnings' => $totalEarnings,
            'totalRentalsCount' => Rental::where('owner_id', auth()->id())->count(),
            'activeRentalsCount' => Rental::where('owner_id', auth()->id())->withCurrentStatus(RentalStatus::Active)->count(),
            'completedRentalsCount' => Rental::where('owner_id', auth()->id())->where('status', RentalStatus::Completed)->count(),
            'completedRentalEarnings' => round($paidRentals->where('status', RentalStatus::Completed)
                ->sum(fn (Rental $rental) => $rental->ownerEarnings()), 2),
            'completedRentals' => $completedRentals,
            'averageRating' => auth()->user()->averageRatingAsOwner(),
            'totalListingViews' => (clone $listings)->sum('views_count'),
            'mostRentedListing' => $mostRentedListing,
            'mostProfitableListing' => $mostProfitableListing,
            'monthlyEarnings' => $monthlyEarnings,
            'rentalsByStatus' => $rentalsByStatus,
            'listingsByCategory' => $listingsByCategory,
        ];
    }

    private function renterData(): array
    {
        $rentalRequests = auth()->user()->rentalRequests();

        $rentalsAwaitingPayment = auth()->user()->rentalsAsRenter()
            ->where('status', RentalStatus::PaymentPending)
            ->with('listing')
            ->get();

        return [
            'activeRentalsCount' => auth()->user()->rentalsAsRenter()->where('status', RentalStatus::Active)->count(),
            'pendingCount' => (clone $rentalRequests)->where('status', RentalRequestStatus::Requested)->count(),
            'approvedCount' => (clone $rentalRequests)->where('status', RentalRequestStatus::Approved)->count(),
            'recentRequests' => (clone $rentalRequests)->with('listing.images')->latest()->take(5)->get(),
            'rentalsAwaitingPayment' => $rentalsAwaitingPayment,
        ];
    }
}
