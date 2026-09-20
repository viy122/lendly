<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Enums\RentalStatus;
use App\Enums\SecurityDepositStatus;
use App\Livewire\Owner\RentalRequests\Index as OwnerRentalRequestsIndex;
use App\Livewire\Owner\Rentals\Show as OwnerRentalShow;
use App\Livewire\RentalRequests\Create;
use App\Livewire\Renter\Rentals\Show as RenterRentalShow;
use App\Models\Category;
use App\Models\ConditionRecord;
use App\Models\Listing;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\RentalRequest;
use App\Models\SecurityDeposit;
use App\Models\User;
use App\Services\RentalLifecycle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RentalLifecycleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Builds a paid rental through the real request -> approve -> pay flow.
     * Only usable for future dates, since the request form correctly
     * rejects past start dates (see createPaidRentalWithDates() below for
     * the overdue tests, which need a rental that already ended).
     */
    private function paidRental(User $owner, User $renter): Rental
    {
        $category = Category::create(['name' => 'Tools', 'slug' => 'tools']);

        $listing = Listing::create([
            'owner_id' => $owner->id,
            'category_id' => $category->id,
            'name' => 'Pressure Washer',
            'description' => 'Great for cleaning driveways.',
            'condition' => 'good',
            'price_per_day' => 100,
            'security_deposit' => 500,
            'location' => 'Manila',
            'max_rental_duration_days' => 15,
            'status' => ListingStatus::Published,
            'is_available' => true,
            'pickup_available' => true,
        ]);

        Livewire::actingAs($renter)
            ->test(Create::class, ['listing' => $listing])
            ->set('start_date', now()->addDays(3)->toDateString())
            ->set('end_date', now()->addDays(5)->toDateString())
            ->set('accept_terms', true)
            ->call('submit');

        $request = RentalRequest::first();

        Livewire::actingAs($owner)
            ->test(OwnerRentalRequestsIndex::class)
            ->set('accept_terms', true)
            ->call('approve', $request->id);

        $rental = Rental::where('rental_request_id', $request->id)->first();

        Livewire::actingAs($renter)
            ->test(RenterRentalShow::class, ['rental' => $rental])
            ->call('confirmPayment');

        return $rental->fresh();
    }

    /**
     * Builds a rental directly (bypassing the request form) so it can have
     * a start/end date already in the past, for exercising overdue
     * detection — the real request flow correctly refuses past dates.
     */
    private function createPaidRentalWithDates(User $owner, User $renter, string $startDate, string $endDate): Rental
    {
        $category = Category::create(['name' => 'Tools', 'slug' => 'tools']);

        $listing = Listing::create([
            'owner_id' => $owner->id,
            'category_id' => $category->id,
            'name' => 'Pressure Washer',
            'description' => 'Great for cleaning driveways.',
            'condition' => 'good',
            'price_per_day' => 100,
            'security_deposit' => 500,
            'location' => 'Manila',
            'max_rental_duration_days' => 15,
            'status' => ListingStatus::Published,
            'is_available' => true,
            'pickup_available' => true,
        ]);

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

        $rental = Rental::create([
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
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        Payment::create([
            'rental_id' => $rental->id,
            'transaction_reference' => Payment::generateReference(),
            'amount' => $rental->total_amount,
            'paid_at' => now(),
        ]);

        SecurityDeposit::create([
            'rental_id' => $rental->id,
            'amount' => $rental->security_deposit,
        ]);

        return $rental;
    }

    public function test_rental_becomes_active_only_after_both_parties_confirm_pickup(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $rental = $this->paidRental($owner, $renter);

        Livewire::actingAs($renter)
            ->test(RenterRentalShow::class, ['rental' => $rental])
            ->call('confirmPickup');

        $this->assertSame(RentalStatus::Paid, $rental->fresh()->status);
        $this->assertNotNull($rental->fresh()->pickup_confirmed_by_renter_at);
        $this->assertNull($rental->fresh()->pickup_confirmed_by_owner_at);

        Livewire::actingAs($owner)
            ->test(OwnerRentalShow::class, ['rental' => $rental->fresh()])
            ->call('confirmPickup');

        $this->assertSame(RentalStatus::Active, $rental->fresh()->status);
    }

    public function test_cannot_confirm_pickup_twice_as_the_same_party(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $rental = $this->paidRental($owner, $renter);

        Livewire::actingAs($renter)
            ->test(RenterRentalShow::class, ['rental' => $rental])
            ->call('confirmPickup');

        Livewire::actingAs($renter)
            ->test(RenterRentalShow::class, ['rental' => $rental->fresh()])
            ->call('confirmPickup')
            ->assertForbidden();
    }

    public function test_cannot_confirm_return_before_pickup_is_confirmed(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $rental = $this->paidRental($owner, $renter);

        Livewire::actingAs($renter)
            ->test(RenterRentalShow::class, ['rental' => $rental])
            ->call('confirmReturn')
            ->assertForbidden();
    }

    public function test_rental_becomes_returned_only_after_both_parties_confirm_return(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $rental = $this->paidRental($owner, $renter);

        RentalLifecycle::confirmPickup($rental, $renter);
        RentalLifecycle::confirmPickup($rental->fresh(), $owner);
        $this->assertSame(RentalStatus::Active, $rental->fresh()->status);

        Livewire::actingAs($owner)
            ->test(OwnerRentalShow::class, ['rental' => $rental->fresh()])
            ->call('confirmReturn');

        $this->assertSame(RentalStatus::Active, $rental->fresh()->status);

        Livewire::actingAs($renter)
            ->test(RenterRentalShow::class, ['rental' => $rental->fresh()])
            ->call('confirmReturn');

        $this->assertSame(RentalStatus::Returned, $rental->fresh()->status);
    }

    public function test_only_owner_can_complete_inspection_and_it_frees_the_deposit(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $rental = $this->paidRental($owner, $renter);

        RentalLifecycle::confirmPickup($rental, $renter);
        RentalLifecycle::confirmPickup($rental->fresh(), $owner);
        RentalLifecycle::confirmReturn($rental->fresh(), $renter);
        RentalLifecycle::confirmReturn($rental->fresh(), $owner);

        $rental->refresh();
        $this->assertSame(RentalStatus::Returned, $rental->status);

        Livewire::actingAs($renter)
            ->test(RenterRentalShow::class, ['rental' => $rental])
            ->call('confirmReturn') // renter has no completeInspection ability at all
            ->assertForbidden();

        // Completing inspection requires an after-rental condition record first (Phase 7).
        ConditionRecord::create([
            'rental_id' => $rental->id,
            'recorded_by' => $owner->id,
            'type' => 'after',
            'condition' => 'good',
        ]);

        Livewire::actingAs($owner)
            ->test(OwnerRentalShow::class, ['rental' => $rental])
            ->call('completeInspection');

        $rental->refresh();
        $this->assertSame(RentalStatus::Completed, $rental->status);
        $this->assertNotNull($rental->completed_at);
        $this->assertSame(SecurityDepositStatus::ReturnEligible, $rental->securityDeposit->fresh()->status);
    }

    public function test_overdue_command_flags_active_rentals_past_end_date_with_correct_late_fee(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();

        // 3-day rental (₱100/day = ₱300 total fee -> ₱100/day agreed rate) that ended 2 days ago.
        $rental = $this->createPaidRentalWithDates($owner, $renter, now()->subDays(5)->toDateString(), now()->subDays(2)->toDateString());

        RentalLifecycle::confirmPickup($rental, $renter);
        RentalLifecycle::confirmPickup($rental->fresh(), $owner);
        $this->assertSame(RentalStatus::Active, $rental->fresh()->status);

        $this->artisan('rentals:check-overdue')->assertSuccessful();

        $rental->refresh();
        $this->assertSame(RentalStatus::Overdue, $rental->status);
        $this->assertSame(2, $rental->days_overdue);
        $this->assertEquals(200.00, (float) $rental->late_fee); // 2 days x ₱100/day agreed rate
    }

    public function test_overdue_command_does_not_touch_rentals_that_are_not_active(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();

        $rental = $this->createPaidRentalWithDates($owner, $renter, now()->subDays(5)->toDateString(), now()->subDays(2)->toDateString());

        // Still just "Paid" — pickup was never confirmed, so it should not be marked overdue.
        $this->artisan('rentals:check-overdue');

        $this->assertSame(RentalStatus::Paid, $rental->fresh()->status);
        $this->assertSame(0, $rental->fresh()->days_overdue);
    }

    public function test_overdue_late_fee_grows_on_subsequent_runs(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();

        $rental = $this->createPaidRentalWithDates($owner, $renter, now()->subDays(5)->toDateString(), now()->subDays(2)->toDateString());

        RentalLifecycle::confirmPickup($rental, $renter);
        RentalLifecycle::confirmPickup($rental->fresh(), $owner);

        $this->artisan('rentals:check-overdue');
        $this->assertSame(2, $rental->fresh()->days_overdue);

        $this->travel(3)->days();

        $this->artisan('rentals:check-overdue');
        $this->assertSame(5, $rental->fresh()->days_overdue);
        $this->assertEquals(500.00, (float) $rental->fresh()->late_fee);
    }
}
