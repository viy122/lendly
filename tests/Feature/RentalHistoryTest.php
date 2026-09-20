<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Enums\RentalStatus;
use App\Livewire\Owner\Rentals\Index as OwnerRentalsIndex;
use App\Livewire\Renter\Rentals\Index as RenterRentalsIndex;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Rental;
use App\Models\RentalRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

// FR-24: users can view their complete history of past bookings and transactions.
class RentalHistoryTest extends TestCase
{
    use RefreshDatabase;

    private function listingFor(User $owner): Listing
    {
        $category = Category::firstOrCreate(['slug' => 'history-tools'], ['name' => 'History Tools']);

        return Listing::create([
            'owner_id' => $owner->id,
            'category_id' => $category->id,
            'name' => 'History Item',
            'description' => 'x',
            'condition' => 'good',
            'price_per_day' => 100,
            'security_deposit' => 500,
            'location' => 'Manila',
            'max_rental_duration_days' => 10,
            'status' => ListingStatus::Published,
            'is_available' => true,
            'pickup_available' => true,
        ]);
    }

    private function rentalWithStatus(User $owner, User $renter, Listing $listing, RentalStatus $status, $startDate, $endDate): Rental
    {
        $request = RentalRequest::create([
            'listing_id' => $listing->id,
            'renter_id' => $renter->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'rental_days' => 3,
            'fulfillment_method' => 'pickup',
            'rental_fee' => 300,
            'commission_rate' => 10,
            'commission_amount' => 30,
            'security_deposit' => 500,
            'total_amount' => 830,
            'status' => 'approved',
        ]);

        return Rental::create([
            'rental_request_id' => $request->id,
            'listing_id' => $listing->id,
            'owner_id' => $owner->id,
            'renter_id' => $renter->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'rental_days' => 3,
            'fulfillment_method' => 'pickup',
            'rental_fee' => 300,
            'commission_rate' => 10,
            'commission_amount' => 30,
            'security_deposit' => 500,
            'total_amount' => 830,
            'status' => $status,
            'paid_at' => $status !== RentalStatus::PaymentPending ? now() : null,
        ]);
    }

    public function test_history_includes_every_rental_status_for_both_parties(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $listing = $this->listingFor($owner);

        $this->rentalWithStatus($owner, $renter, $listing, RentalStatus::PaymentPending, now()->addDays(5), now()->addDays(8));
        $this->rentalWithStatus($owner, $renter, $listing, RentalStatus::Completed, now()->subDays(20), now()->subDays(17));

        $cancelled = $this->rentalWithStatus($owner, $renter, $listing, RentalStatus::PaymentPending, now()->addDays(15), now()->addDays(18));
        $cancelled->update(['status' => RentalStatus::Cancelled, 'cancelled_at' => now(), 'cancellation_reason' => 'x', 'cancellation_fee' => 0]);

        $renterView = Livewire::actingAs($renter)->test(RenterRentalsIndex::class);
        $this->assertSame(3, $renterView->viewData('rentals')->total());

        $ownerView = Livewire::actingAs($owner)->test(OwnerRentalsIndex::class);
        $this->assertSame(3, $ownerView->viewData('rentals')->total());
    }

    public function test_a_renter_cannot_see_another_renters_history(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $otherRenter = User::factory()->create();
        $listing = $this->listingFor($owner);

        $this->rentalWithStatus($owner, $renter, $listing, RentalStatus::Completed, now()->subDays(10), now()->subDays(7));

        $otherView = Livewire::actingAs($otherRenter)->test(RenterRentalsIndex::class);
        $this->assertSame(0, $otherView->viewData('rentals')->total());
    }
}
