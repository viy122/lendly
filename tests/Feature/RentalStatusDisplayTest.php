<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Enums\RentalStatus;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Rental;
use App\Models\RentalRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FR-19: "the system shall track and display the real-time status of an
 * active rental (e.g., Ongoing, Due Soon, Overdue)". Overdue is a real
 * stored RentalStatus (see RentalLifecycle::markOverdueRentals()) — Due
 * Soon is deliberately a derived display state instead (see Rental::
 * isDueSoon()), since it's just "Active, but close to the end date."
 */
class RentalStatusDisplayTest extends TestCase
{
    use RefreshDatabase;

    private function listingFor(User $owner): Listing
    {
        $category = Category::firstOrCreate(['slug' => 'status-display-tools'], ['name' => 'Status Display Tools']);

        return Listing::create([
            'owner_id' => $owner->id,
            'category_id' => $category->id,
            'name' => 'Status Display Item',
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

    public function test_active_rental_due_within_2_days_displays_as_due_soon(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $listing = $this->listingFor($owner);

        $dueSoon = $this->rentalWithStatus($owner, $renter, $listing, RentalStatus::Active, now()->subDays(2), now()->addDay());
        $this->assertTrue($dueSoon->isDueSoon());
        $this->assertSame('Due Soon', $dueSoon->displayStatusLabel());

        $notDueSoon = $this->rentalWithStatus($owner, $renter, $listing, RentalStatus::Active, now()->subDay(), now()->addDays(10));
        $this->assertFalse($notDueSoon->isDueSoon());
        $this->assertSame('Active Rental', $notDueSoon->displayStatusLabel());
    }

    public function test_overdue_and_completed_rentals_are_never_due_soon(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $listing = $this->listingFor($owner);

        $overdue = $this->rentalWithStatus($owner, $renter, $listing, RentalStatus::Overdue, now()->subDays(5), now()->subDay());
        $this->assertFalse($overdue->isDueSoon());
        $this->assertSame('Overdue', $overdue->displayStatusLabel());

        $completed = $this->rentalWithStatus($owner, $renter, $listing, RentalStatus::Completed, now()->subDays(10), now()->subDays(7));
        $this->assertFalse($completed->isDueSoon());
    }

    public function test_due_soon_badge_renders_on_the_renter_and_owner_rental_pages(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $listing = $this->listingFor($owner);
        $rental = $this->rentalWithStatus($owner, $renter, $listing, RentalStatus::Active, now()->subDays(2), now()->addDay());

        $this->actingAs($renter)->get(route('renter.rentals.show', $rental))->assertOk()->assertSee('Due Soon');
        $this->actingAs($owner)->get(route('owner.rentals.show', $rental))->assertOk()->assertSee('Due Soon');
    }
}
