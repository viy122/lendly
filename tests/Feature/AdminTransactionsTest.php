<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Enums\RentalStatus;
use App\Livewire\Admin\Rentals\Index as AdminRentalsIndex;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Rental;
use App\Models\RentalRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

// FR-28: admin can view and manage all rental transactions on the platform.
class AdminTransactionsTest extends TestCase
{
    use RefreshDatabase;

    private function listingFor(User $owner, string $name): Listing
    {
        $category = Category::firstOrCreate(['slug' => 'admin-tx-tools'], ['name' => 'Admin Tx Tools']);

        return Listing::create([
            'owner_id' => $owner->id,
            'category_id' => $category->id,
            'name' => $name,
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

    private function rentalWithStatus(User $owner, User $renter, Listing $listing, RentalStatus $status): Rental
    {
        $request = RentalRequest::create([
            'listing_id' => $listing->id,
            'renter_id' => $renter->id,
            'start_date' => now()->addDays(3),
            'end_date' => now()->addDays(6),
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
            'start_date' => now()->addDays(3),
            'end_date' => now()->addDays(6),
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

    public function test_only_admin_can_view_the_transactions_page(): void
    {
        $member = User::factory()->create();

        $this->actingAs($member)->get(route('admin.rentals.index'))->assertForbidden();
    }

    public function test_status_filter_and_total_value_reflect_only_matching_rentals(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $listing = $this->listingFor($owner, 'Filter Test Item');

        $paid = $this->rentalWithStatus($owner, $renter, $listing, RentalStatus::Paid);
        $this->rentalWithStatus($owner, $renter, $listing, RentalStatus::PaymentPending);

        $component = Livewire::actingAs($admin)->test(AdminRentalsIndex::class);
        $this->assertSame(2, $component->viewData('rentals')->total());

        $component->set('status', 'paid');
        $this->assertSame(1, $component->viewData('rentals')->total());
        $this->assertEquals((float) $paid->total_amount, $component->viewData('totalTransactionValue'));
    }

    public function test_search_matches_item_name_and_owner_name(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $ownerA = User::factory()->create(['name' => 'Owner Alpha']);
        $ownerB = User::factory()->create(['name' => 'Owner Beta']);
        $renter = User::factory()->create();
        $listingA = $this->listingFor($ownerA, 'Camera Alpha');
        $listingB = $this->listingFor($ownerB, 'Drill Beta');

        $this->rentalWithStatus($ownerA, $renter, $listingA, RentalStatus::Paid);
        $this->rentalWithStatus($ownerB, $renter, $listingB, RentalStatus::Paid);

        $component = Livewire::actingAs($admin)->test(AdminRentalsIndex::class);

        $component->set('search', 'Alpha');
        $this->assertSame(1, $component->viewData('rentals')->total());

        $component->set('search', 'Drill');
        $this->assertSame(1, $component->viewData('rentals')->total());
    }
}
