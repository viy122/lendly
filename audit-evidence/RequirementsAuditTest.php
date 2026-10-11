<?php

use App\Livewire\Listings\Map;
use App\Livewire\Owner\RentalRequests\Index as OwnerRequests;
use App\Livewire\RentalRequests\Create;
use App\Livewire\Renter\RentalRequests\Index as RenterRequests;
use App\Livewire\Renter\Rentals\Show as RenterRental;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Rental;
use App\Models\RentalRequest;
use App\Models\User;
use App\Services\RentalLifecycle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

// Audit characterization probes: passing means the documented gap was reproduced.
// These run only against PHPUnit's in-memory SQLite database.
class RequirementsAuditTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $category = Category::create(['name' => 'Audit Tools', 'slug' => 'audit-tools']);
        $listing = Listing::create([
            'owner_id' => $owner->id, 'category_id' => $category->id,
            'name' => 'Audit Item', 'description' => 'Audit fixture', 'condition' => 'good',
            'price_per_day' => 100, 'security_deposit' => 500,
            'location' => 'Manila', 'latitude' => 14.5995, 'longitude' => 120.9842,
            'max_rental_duration_days' => 10, 'status' => 'published', 'is_available' => true,
        ]);
        $request = RentalRequest::create([
            'listing_id' => $listing->id, 'renter_id' => $renter->id,
            'start_date' => now()->addDays(3), 'end_date' => now()->addDays(4),
            'rental_days' => 2, 'fulfillment_method' => 'pickup',
            'rental_fee' => 200, 'commission_rate' => 10, 'commission_amount' => 20,
            'security_deposit' => 500, 'total_amount' => 720, 'status' => 'requested',
            'renter_terms_accepted_at' => now(),
        ]);
        return [$owner, $renter, $listing, $request];
    }

    private function approve(User $owner, RentalRequest $request): Rental
    {
        Livewire::actingAs($owner)->test(OwnerRequests::class)
            ->set('accept_terms', true)->call('approve', $request->id)->assertHasNoErrors();
        return Rental::where('rental_request_id', $request->id)->firstOrFail();
    }

    public function test_gap_unverified_renter_can_submit_request(): void
    {
        [$owner, $renter, $listing] = $this->fixture();
        $renter->forceFill(['email_verified_at' => null])->save();
        $this->actingAs($renter)->get(route('renter.rental-requests.create', $listing))->assertOk();
        Livewire::actingAs($renter)->test(Create::class, ['listing' => $listing])
            ->set('accept_terms', true)->call('submit')->assertHasNoErrors();
        $this->assertSame(2, RentalRequest::count());
    }

    public function test_gap_approval_does_not_recheck_renter_terms(): void
    {
        [$owner, $renter, $listing, $request] = $this->fixture();
        $request->update(['renter_terms_accepted_at' => null]);
        $this->approve($owner, $request);
        $this->assertNull($request->fresh()->renter_terms_accepted_at);
        $this->assertSame(1, Rental::count());
    }

    public function test_gap_owner_has_no_booking_confirmation_notification(): void
    {
        [$owner, $renter, $listing, $request] = $this->fixture();
        $this->approve($owner, $request);
        $this->assertSame(0, $owner->notifications()->count());
        $this->assertSame(1, $renter->notifications()->count());
    }

    public function test_gap_pending_request_cancellation_does_not_notify_owner(): void
    {
        [$owner, $renter, $listing, $request] = $this->fixture();
        Livewire::actingAs($renter)->test(RenterRequests::class)->call('cancel', $request->id);
        $this->assertSame('cancelled', $request->fresh()->status->value);
        $this->assertSame(0, $owner->notifications()->count());
    }

    public function test_gap_cancellation_is_allowed_after_owner_logged_handover(): void
    {
        [$owner, $renter, $listing, $request] = $this->fixture();
        $rental = $this->approve($owner, $request);
        $rental->update(['status' => 'paid', 'paid_at' => now()]);
        RentalLifecycle::confirmPickup($rental, $owner);
        $this->assertNotNull($rental->fresh()->pickup_confirmed_by_owner_at);
        $this->assertTrue($rental->fresh()->isCancellableByRenter());
        Livewire::actingAs($renter)->test(RenterRental::class, ['rental' => $rental->fresh()])
            ->set('cancellation_reason', 'Audit after handover')->call('cancelRental');
        $this->assertSame('cancelled', $rental->fresh()->status->value);
    }

    public function test_gap_zero_coordinate_does_not_compute_distance(): void
    {
        $this->fixture();
        $map = Livewire::test(Map::class)->set('centerLat', 0.0)->set('centerLng', 120.9842);
        $this->assertNull($map->get('markers')[0]['distanceKm']);
    }

    public function test_gap_soft_deleted_listing_is_missing_from_rental_relation(): void
    {
        [$owner, $renter, $listing, $request] = $this->fixture();
        $rental = $this->approve($owner, $request);
        $listing->delete();
        $this->assertNull($rental->fresh()->listing);
        $this->assertNotNull(Listing::withTrashed()->find($listing->id));
    }

    public function test_gap_owner_can_submit_renter_to_owner_review_on_own_rental(): void
    {
        [$owner, $renter, $listing, $request] = $this->fixture();
        $rental = $this->approve($owner, $request);
        $rental->update(['status' => 'completed', 'completed_at' => now()]);
        Livewire::actingAs($owner)->test(RenterRental::class, ['rental' => $rental->fresh()])
            ->set('owner_rating', 5)->set('owner_comment', 'Audit self review')->call('submitOwnerReview');
        $this->assertNotNull($rental->fresh()->reviewFromRenterToOwner);
    }

    public function test_gap_submit_does_not_recheck_listing_availability(): void
    {
        [$owner, $renter, $listing] = $this->fixture();
        $form = Livewire::actingAs($renter)->test(Create::class, ['listing' => $listing]);
        $listing->update(['is_available' => false, 'status' => 'inactive']);
        $form->set('accept_terms', true)->call('submit')->assertHasNoErrors();
        $this->assertSame(2, RentalRequest::count());
    }

    public function test_gap_local_demo_switcher_can_enter_admin_without_admin_password(): void
    {
        $this->app['env'] = 'local';
        $member = User::factory()->create();
        $admin = User::factory()->admin()->create(['email' => 'admin@tala.test']);
        \Livewire\Volt\Volt::actingAs($member)->test('layout.sidebar')->call('switchDemoUser', 'admin');
        $this->assertAuthenticatedAs($admin);
    }

    public function test_gap_account_deletion_removes_other_partys_rental_history(): void
    {
        [$owner, $renter, $listing, $request] = $this->fixture();
        $rental = $this->approve($owner, $request);
        $owner->delete();
        $this->assertNull(Rental::find($rental->id));
        $this->assertNotNull(User::find($renter->id));
    }

    public function test_gap_paid_cancelled_booking_remains_in_full_owner_earnings(): void
    {
        [$owner, $renter, $listing, $request] = $this->fixture();
        $rental = $this->approve($owner, $request);
        $rental->update(['status' => 'paid', 'paid_at' => now()]);
        RentalLifecycle::cancel($rental, 'Audit cancellation');
        $this->assertSame(0.0, (float) $rental->fresh()->cancellation_fee);
        Livewire::actingAs($owner)->test(App\Livewire\Member\Dashboard::class)
            ->assertViewHas('totalEarnings', fn ($value) => (float) $value === 200.0);
    }
}
