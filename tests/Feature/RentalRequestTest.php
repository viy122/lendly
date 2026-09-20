<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Enums\RentalRequestStatus;
use App\Livewire\Owner\RentalRequests\Index as OwnerRentalRequestsIndex;
use App\Livewire\RentalRequests\Create;
use App\Livewire\Renter\RentalRequests\Index as RenterRentalRequestsIndex;
use App\Models\Category;
use App\Models\Listing;
use App\Models\RentalRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RentalRequestTest extends TestCase
{
    use RefreshDatabase;

    private function publishedListing(User $owner, array $overrides = []): Listing
    {
        $category = Category::create(['name' => 'Tools', 'slug' => 'tools']);

        return Listing::create(array_merge([
            'owner_id' => $owner->id,
            'category_id' => $category->id,
            'name' => 'Pressure Washer',
            'description' => 'Great for cleaning driveways.',
            'condition' => 'good',
            'price_per_day' => 100,
            'security_deposit' => 500,
            'location' => 'Manila',
            'max_rental_duration_days' => 10,
            'status' => ListingStatus::Published,
            'is_available' => true,
            'pickup_available' => true,
            'delivery_available' => false,
        ], $overrides));
    }

    public function test_renter_can_submit_a_rental_request_with_correct_cost_breakdown(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->publishedListing($owner);

        Livewire::actingAs($renter)
            ->test(Create::class, ['listing' => $listing])
            ->set('start_date', now()->addDays(3)->toDateString())
            ->set('end_date', now()->addDays(5)->toDateString()) // 3 days inclusive
            ->set('accept_terms', true)
            ->call('submit')
            ->assertHasNoErrors();

        $request = RentalRequest::first();

        $this->assertNotNull($request);
        $this->assertSame(3, $request->rental_days);
        $this->assertEquals(300.00, (float) $request->rental_fee); // 100/day * 3
        $this->assertEquals(30.00, (float) $request->commission_amount); // 10% default
        $this->assertEquals(830.00, (float) $request->total_amount); // 300 + 30 + 500 deposit
        $this->assertSame(RentalRequestStatus::Requested, $request->status);
    }

    public function test_renter_cannot_rent_their_own_listing(): void
    {
        $renter = User::factory()->renter()->create();

        // Owner/renter roles are normally mutually exclusive, so to exercise the
        // defensive "no self-rental" business rule directly (not just the role
        // middleware), a listing is attached to a renter-role account here.
        $listing = $this->publishedListing($renter);

        $this->actingAs($renter)
            ->get(route('renter.rental-requests.create', $listing))
            ->assertForbidden();
    }

    public function test_cannot_request_dates_beyond_max_rental_duration(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->publishedListing($owner, ['max_rental_duration_days' => 3]);

        Livewire::actingAs($renter)
            ->test(Create::class, ['listing' => $listing])
            ->set('start_date', now()->addDay()->toDateString())
            ->set('end_date', now()->addDays(10)->toDateString())
            ->set('accept_terms', true)
            ->call('submit')
            ->assertHasErrors(['end_date']);

        $this->assertSame(0, RentalRequest::count());
    }

    public function test_cannot_request_past_dates(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->publishedListing($owner);

        Livewire::actingAs($renter)
            ->test(Create::class, ['listing' => $listing])
            ->set('start_date', now()->subDays(2)->toDateString())
            ->set('end_date', now()->toDateString())
            ->set('accept_terms', true)
            ->call('submit')
            ->assertHasErrors(['start_date']);

        $this->assertSame(0, RentalRequest::count());
    }

    public function test_new_request_is_blocked_when_dates_overlap_an_approved_request(): void
    {
        $owner = User::factory()->owner()->create();
        $renterA = User::factory()->renter()->create();
        $renterB = User::factory()->renter()->create();
        $listing = $this->publishedListing($owner);

        Livewire::actingAs($renterA)
            ->test(Create::class, ['listing' => $listing])
            ->set('start_date', now()->addDays(5)->toDateString())
            ->set('end_date', now()->addDays(8)->toDateString())
            ->set('accept_terms', true)
            ->call('submit');

        $firstRequest = RentalRequest::first();

        Livewire::actingAs($owner)
            ->test(OwnerRentalRequestsIndex::class)
            ->set('accept_terms', true)
            ->call('approve', $firstRequest->id);

        $this->assertSame(RentalRequestStatus::Approved, $firstRequest->fresh()->status);

        // Overlapping dates for a different renter should now be rejected at request time.
        Livewire::actingAs($renterB)
            ->test(Create::class, ['listing' => $listing])
            ->set('start_date', now()->addDays(6)->toDateString())
            ->set('end_date', now()->addDays(9)->toDateString())
            ->set('accept_terms', true)
            ->call('submit')
            ->assertHasErrors(['start_date']);
    }

    public function test_approving_a_request_auto_rejects_other_overlapping_pending_requests(): void
    {
        $owner = User::factory()->owner()->create();
        $renterA = User::factory()->renter()->create();
        $renterB = User::factory()->renter()->create();
        $listing = $this->publishedListing($owner);

        Livewire::actingAs($renterA)
            ->test(Create::class, ['listing' => $listing])
            ->set('start_date', now()->addDays(5)->toDateString())
            ->set('end_date', now()->addDays(8)->toDateString())
            ->set('accept_terms', true)
            ->call('submit');
        $requestA = RentalRequest::where('renter_id', $renterA->id)->first();

        Livewire::actingAs($renterB)
            ->test(Create::class, ['listing' => $listing])
            ->set('start_date', now()->addDays(7)->toDateString())
            ->set('end_date', now()->addDays(10)->toDateString())
            ->set('accept_terms', true)
            ->call('submit');
        $requestB = RentalRequest::where('renter_id', $renterB->id)->first();

        // Both pending requests coexist until the owner picks one.
        $this->assertSame(RentalRequestStatus::Requested, $requestA->fresh()->status);
        $this->assertSame(RentalRequestStatus::Requested, $requestB->fresh()->status);

        Livewire::actingAs($owner)
            ->test(OwnerRentalRequestsIndex::class)
            ->set('accept_terms', true)
            ->call('approve', $requestA->id);

        $this->assertSame(RentalRequestStatus::Approved, $requestA->fresh()->status);
        $this->assertSame(RentalRequestStatus::Rejected, $requestB->fresh()->status);
        $this->assertNotNull($requestB->fresh()->rejection_reason);
    }

    public function test_owner_cannot_approve_a_request_for_another_owners_listing(): void
    {
        $ownerA = User::factory()->owner()->create();
        $ownerB = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->publishedListing($ownerA);

        Livewire::actingAs($renter)
            ->test(Create::class, ['listing' => $listing])
            ->set('start_date', now()->addDay()->toDateString())
            ->set('end_date', now()->addDays(2)->toDateString())
            ->set('accept_terms', true)
            ->call('submit');

        $request = RentalRequest::first();

        Livewire::actingAs($ownerB)
            ->test(OwnerRentalRequestsIndex::class)
            ->set('accept_terms', true)
            ->call('approve', $request->id)
            ->assertForbidden();
    }

    public function test_renter_can_cancel_a_pending_request_but_not_after_it_starts(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->publishedListing($owner);

        $futureRequest = RentalRequest::create([
            'listing_id' => $listing->id,
            'renter_id' => $renter->id,
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(6),
            'rental_days' => 2,
            'fulfillment_method' => 'pickup',
            'rental_fee' => 200,
            'commission_rate' => 10,
            'commission_amount' => 20,
            'security_deposit' => 500,
            'total_amount' => 720,
        ]);

        Livewire::actingAs($renter)
            ->test(RenterRentalRequestsIndex::class)
            ->call('cancel', $futureRequest->id);

        $this->assertSame(RentalRequestStatus::Cancelled, $futureRequest->fresh()->status);

        $startedRequest = RentalRequest::create([
            'listing_id' => $listing->id,
            'renter_id' => $renter->id,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'rental_days' => 3,
            'fulfillment_method' => 'pickup',
            'rental_fee' => 300,
            'commission_rate' => 10,
            'commission_amount' => 30,
            'security_deposit' => 500,
            'total_amount' => 830,
            'status' => RentalRequestStatus::Approved,
        ]);

        Livewire::actingAs($renter)
            ->test(RenterRentalRequestsIndex::class)
            ->call('cancel', $startedRequest->id)
            ->assertForbidden();
    }

    public function test_renter_cannot_cancel_another_renters_request(): void
    {
        $owner = User::factory()->owner()->create();
        $renterA = User::factory()->renter()->create();
        $renterB = User::factory()->renter()->create();
        $listing = $this->publishedListing($owner);

        $request = RentalRequest::create([
            'listing_id' => $listing->id,
            'renter_id' => $renterA->id,
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(6),
            'rental_days' => 2,
            'fulfillment_method' => 'pickup',
            'rental_fee' => 200,
            'commission_rate' => 10,
            'commission_amount' => 20,
            'security_deposit' => 500,
            'total_amount' => 720,
        ]);

        Livewire::actingAs($renterB)
            ->test(RenterRentalRequestsIndex::class)
            ->call('cancel', $request->id)
            ->assertForbidden();
    }
}
