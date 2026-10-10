<?php

namespace Tests\Feature;

use App\Enums\DisputeStatus;
use App\Enums\ListingStatus;
use App\Enums\RentalStatus;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Member\Dashboard as MemberDashboard;
use App\Models\Category;
use App\Models\Dispute;
use App\Models\Listing;
use App\Models\Rental;
use App\Models\RentalRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private function listingFor(User $owner): Listing
    {
        $category = Category::firstOrCreate(['slug' => 'tools'], ['name' => 'Tools']);

        return Listing::create([
            'owner_id' => $owner->id,
            'category_id' => $category->id,
            'name' => 'Pressure Washer',
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

    private function paidRental(User $owner, User $renter, Listing $listing, RentalStatus $status): Rental
    {
        $request = RentalRequest::create([
            'listing_id' => $listing->id,
            'renter_id' => $renter->id,
            'start_date' => now()->subDays(10),
            'end_date' => now()->subDays(7),
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
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'rental_days' => 3,
            'fulfillment_method' => 'pickup',
            'rental_fee' => 300,
            'commission_rate' => 10,
            'commission_amount' => 30,
            'security_deposit' => 500,
            'total_amount' => 830,
            'status' => $status,
            'paid_at' => now()->subDays(9),
        ]);
    }

    public function test_owner_earnings_count_rentals_that_have_progressed_past_paid(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->listingFor($owner);

        // These are all "paid" in the sense that matters for earnings, even
        // though only one of them has the literal status Paid — this is the
        // exact undercount bug this phase fixed.
        $this->paidRental($owner, $renter, $listing, RentalStatus::Paid);
        $this->paidRental($owner, $renter, $listing, RentalStatus::Active);
        $this->paidRental($owner, $renter, $listing, RentalStatus::Completed);

        Livewire::actingAs($owner)->test(MemberDashboard::class)->assertViewHas('totalEarnings', 900.0);
    }

    public function test_owner_dashboard_identifies_most_rented_and_most_profitable_listing(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $popularListing = $this->listingFor($owner);
        $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
        $otherListing = Listing::create([
            'owner_id' => $owner->id,
            'category_id' => $category->id,
            'name' => 'Projector',
            'description' => 'x',
            'condition' => 'good',
            'price_per_day' => 50,
            'security_deposit' => 200,
            'location' => 'Manila',
            'max_rental_duration_days' => 10,
            'status' => ListingStatus::Published,
            'is_available' => true,
            'pickup_available' => true,
        ]);

        $this->paidRental($owner, $renter, $popularListing, RentalStatus::Completed);
        $this->paidRental($owner, $renter, $popularListing, RentalStatus::Completed);
        $this->paidRental($owner, $renter, $otherListing, RentalStatus::Completed);

        $component = Livewire::actingAs($owner)->test(MemberDashboard::class);

        $this->assertSame($popularListing->id, $component->viewData('mostRentedListing')->id);
        $this->assertSame($popularListing->id, $component->viewData('mostProfitableListing')->id);
    }

    public function test_owner_dashboard_has_no_most_rented_listing_when_there_are_no_rentals(): void
    {
        $owner = User::factory()->owner()->create();
        $this->listingFor($owner);

        $component = Livewire::actingAs($owner)->test(MemberDashboard::class);

        $this->assertNull($component->viewData('mostRentedListing'));
        $this->assertNull($component->viewData('mostProfitableListing'));
    }

    public function test_admin_dashboard_aggregates_platform_wide_figures(): void
    {
        $admin = User::factory()->admin()->create();
        $ownerA = User::factory()->owner()->create();
        $ownerB = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();

        $listingA = $this->listingFor($ownerA);
        $listingB = $this->listingFor($ownerB);

        $this->paidRental($ownerA, $renter, $listingA, RentalStatus::Completed);
        $this->paidRental($ownerB, $renter, $listingB, RentalStatus::Active);
        $this->paidRental($ownerA, $renter, $listingA, RentalStatus::Overdue);

        Dispute::create([
            'rental_id' => Rental::first()->id,
            'raised_by' => $renter->id,
            'reason' => 'item_not_as_described',
            'description' => 'x',
            'status' => DisputeStatus::Open,
        ]);

        $component = Livewire::actingAs($admin)->test(AdminDashboard::class);

        $component->assertViewHas('activeRentalsCount', 0);
        $component->assertViewHas('completedRentalsCount', 1);
        $component->assertViewHas('overdueRentalsCount', 2);
        $component->assertViewHas('openDisputesCount', 1);
        // 3 paid rentals x ₱830 total_amount each.
        $component->assertViewHas('transactionValue', 2490.0);
        // 3 paid rentals x ₱30 commission each.
        $component->assertViewHas('platformRevenue', 90.0);
    }

    public function test_only_admin_can_view_the_admin_dashboard(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)->get(route('admin.dashboard'))->assertForbidden();
    }
}
