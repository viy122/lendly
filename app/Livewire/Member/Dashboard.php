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

/**
 * Owner and Renter used to be separate dashboards behind separate roles —
 * now that every member can both list and rent, this single dashboard
 * shows both sets of activity ("As Owner" / "As Renter" sections) rather
 * than forcing a member to pick which side of themselves to look at.
 */
#[Layout('layouts.app')]
class Dashboard extends Component
{
    public function render(): View
    {
        return view('livewire.member.dashboard', array_merge(
            $this->ownerData(),
            $this->renterData(),
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

        // A rental counts toward earnings as soon as it's paid, regardless of
        // how far its lifecycle has since progressed (Active/Overdue/Returned/
        // Completed all imply payment already happened) — checking paid_at
        // rather than status === Paid keeps this correct as new statuses are added.
        $paidRentals = Rental::query()->where('owner_id', auth()->id())->whereNotNull('paid_at');

        $totalEarnings = (clone $paidRentals)->sum('rental_fee');

        // Grouped in PHP (not a raw DATE_FORMAT/strftime query) so this works
        // identically on MySQL in production and SQLite in tests.
        $earningsByMonth = (clone $paidRentals)
            ->where('paid_at', '>=', now()->subMonths(5)->startOfMonth())
            ->get(['paid_at', 'rental_fee'])
            ->groupBy(fn (Rental $rental) => $rental->paid_at->format('Y-m'))
            ->map(fn ($group) => $group->sum('rental_fee'));

        $monthlyEarnings = collect(range(5, 0))->map(function (int $monthsAgo) use ($earningsByMonth) {
            $date = now()->subMonths($monthsAgo);
            $key = $date->format('Y-m');
            $total = (float) ($earningsByMonth[$key] ?? 0);

            return [
                'label' => $date->format('M'),
                'value' => $total,
                'display' => '₱'.number_format($total, 0),
                'color' => 'bg-blue-600',
            ];
        });

        $rentalsByStatus = collect(RentalStatus::cases())->map(function (RentalStatus $status) {
            return [
                'label' => $status->label(),
                'value' => Rental::where('owner_id', auth()->id())->where('status', $status)->count(),
                'color' => match ($status->badgeColor()) {
                    'amber' => 'bg-amber-500',
                    'teal' => 'bg-blue-600',
                    'green' => 'bg-emerald-600',
                    'red' => 'bg-rose-600',
                    default => 'bg-slate-400',
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

        $mostProfitableListing = (clone $listings)
            ->withSum(['rentals as revenue' => fn ($query) => $query->whereNotNull('paid_at')], 'rental_fee')
            ->orderByDesc('revenue')
            ->first();
        $mostProfitableListing = $mostProfitableListing?->revenue > 0 ? $mostProfitableListing : null;

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
            'activeRentalsCount' => Rental::where('owner_id', auth()->id())->where('status', RentalStatus::Active)->count(),
            'completedRentalsCount' => Rental::where('owner_id', auth()->id())->where('status', RentalStatus::Completed)->count(),
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
            'pendingCount' => (clone $rentalRequests)->where('status', RentalRequestStatus::Requested)->count(),
            'approvedCount' => (clone $rentalRequests)->where('status', RentalRequestStatus::Approved)->count(),
            'recentRequests' => (clone $rentalRequests)->with('listing.images')->latest()->take(5)->get(),
            'rentalsAwaitingPayment' => $rentalsAwaitingPayment,
        ];
    }
}
