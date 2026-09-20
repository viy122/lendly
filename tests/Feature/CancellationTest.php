<?php

namespace Tests\Feature;

use App\Enums\RentalRequestStatus;
use App\Enums\RentalStatus;
use App\Livewire\Renter\Rentals\Show as RenterRentalShow;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Rental;
use App\Models\RentalRequest;
use App\Models\SecurityDeposit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CancellationTest extends TestCase
{
    use RefreshDatabase;

    private function listingFor(User $owner): Listing
    {
        $category = Category::create(['name' => 'Cancel Test Tools', 'slug' => 'cancel-test-tools']);

        return Listing::create([
            'owner_id' => $owner->id,
            'category_id' => $category->id,
            'name' => 'Cancellable Item',
            'description' => 'x',
            'condition' => 'good',
            'price_per_day' => 100,
            'security_deposit' => 500,
            'location' => 'Manila',
            'max_rental_duration_days' => 10,
            'status' => 'published',
            'is_available' => true,
            'pickup_available' => true,
        ]);
    }

    private function rentalWithRequest(User $owner, User $renter, Listing $listing, RentalStatus $status, $startDate): Rental
    {
        $request = RentalRequest::create([
            'listing_id' => $listing->id,
            'renter_id' => $renter->id,
            'start_date' => $startDate,
            'end_date' => now()->addDays(5),
            'rental_days' => 3,
            'fulfillment_method' => 'pickup',
            'rental_fee' => 1000,
            'commission_rate' => 10,
            'commission_amount' => 100,
            'security_deposit' => 500,
            'total_amount' => 1600,
            'status' => RentalRequestStatus::Approved,
        ]);

        return Rental::create([
            'rental_request_id' => $request->id,
            'listing_id' => $listing->id,
            'owner_id' => $owner->id,
            'renter_id' => $renter->id,
            'start_date' => $startDate,
            'end_date' => now()->addDays(5),
            'rental_days' => 3,
            'fulfillment_method' => 'pickup',
            'rental_fee' => 1000,
            'commission_rate' => 10,
            'commission_amount' => 100,
            'security_deposit' => 500,
            'total_amount' => 1600,
            'status' => $status,
            'paid_at' => $status === RentalStatus::Paid ? now() : null,
        ]);
    }

    public function test_renter_can_cancel_before_paying_with_no_fee_when_well_ahead_of_start_date(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $listing = $this->listingFor($owner);
        $rental = $this->rentalWithRequest($owner, $renter, $listing, RentalStatus::PaymentPending, now()->addDays(10));

        Livewire::actingAs($renter)
            ->test(RenterRentalShow::class, ['rental' => $rental])
            ->set('cancellation_reason', 'No longer needed')
            ->call('cancelRental')
            ->assertHasNoErrors();

        $rental->refresh();
        $this->assertSame(RentalStatus::Cancelled, $rental->status);
        $this->assertEquals(0.0, (float) $rental->cancellation_fee);
        $this->assertSame(RentalRequestStatus::Cancelled, $rental->rentalRequest->fresh()->status);
    }

    /**
     * Regression test: a rental cancelled before payment has no Payment
     * record, but the "else" branch of the rental show page used to
     * unconditionally render a "Transaction receipt" assuming one always
     * existed — crashing with "Attempt to read property on null" the moment
     * a renter viewed a booking they'd cancelled pre-payment. The view now
     * guards that section behind `@if ($rental->payment)`.
     */
    public function test_viewing_a_pre_payment_cancelled_rental_does_not_crash(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $listing = $this->listingFor($owner);
        $rental = $this->rentalWithRequest($owner, $renter, $listing, RentalStatus::PaymentPending, now()->addDays(10));

        Livewire::actingAs($renter)->test(RenterRentalShow::class, ['rental' => $rental])
            ->set('cancellation_reason', 'x')
            ->call('cancelRental');

        $this->actingAs($renter)
            ->get(route('renter.rentals.show', $rental->fresh()))
            ->assertOk()
            ->assertDontSee('Transaction receipt');
    }

    public function test_renter_pays_a_cancellation_fee_when_cancelling_a_paid_booking_close_to_start_date(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $listing = $this->listingFor($owner);
        $rental = $this->rentalWithRequest($owner, $renter, $listing, RentalStatus::Paid, now()->addHours(10));
        SecurityDeposit::create(['rental_id' => $rental->id, 'amount' => 500]);

        Livewire::actingAs($renter)
            ->test(RenterRentalShow::class, ['rental' => $rental])
            ->set('cancellation_reason', 'Change of plans')
            ->call('cancelRental')
            ->assertHasNoErrors();

        $rental->refresh();
        $this->assertSame(RentalStatus::Cancelled, $rental->status);
        $this->assertEquals(200.0, (float) $rental->cancellation_fee, '20% of the ₱1000 rental fee since cancelled within 48h of start');
        $this->assertSame('refunded', $rental->securityDeposit->fresh()->status->value);
    }

    public function test_no_cancellation_fee_when_cancelling_a_paid_booking_well_ahead_of_start_date(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $listing = $this->listingFor($owner);
        $rental = $this->rentalWithRequest($owner, $renter, $listing, RentalStatus::Paid, now()->addDays(10));
        SecurityDeposit::create(['rental_id' => $rental->id, 'amount' => 500]);

        Livewire::actingAs($renter)
            ->test(RenterRentalShow::class, ['rental' => $rental])
            ->set('cancellation_reason', 'Change of plans')
            ->call('cancelRental');

        $this->assertEquals(0.0, (float) $rental->fresh()->cancellation_fee);
    }

    public function test_renter_cannot_cancel_after_pickup_is_active(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $listing = $this->listingFor($owner);
        $rental = $this->rentalWithRequest($owner, $renter, $listing, RentalStatus::Active, now()->subDay());

        Livewire::actingAs($renter)
            ->test(RenterRentalShow::class, ['rental' => $rental])
            ->set('cancellation_reason', 'Too late')
            ->call('cancelRental')
            ->assertForbidden();

        $this->assertSame(RentalStatus::Active, $rental->fresh()->status);
    }

    public function test_only_the_renter_can_cancel_not_the_owner_or_a_stranger(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $stranger = User::factory()->create();
        $listing = $this->listingFor($owner);
        $rental = $this->rentalWithRequest($owner, $renter, $listing, RentalStatus::PaymentPending, now()->addDays(10));

        $this->assertTrue($renter->can('cancel', $rental));
        $this->assertFalse($owner->can('cancel', $rental));
        $this->assertFalse($stranger->can('cancel', $rental));
    }

    public function test_cancellation_reason_is_required(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $listing = $this->listingFor($owner);
        $rental = $this->rentalWithRequest($owner, $renter, $listing, RentalStatus::PaymentPending, now()->addDays(10));

        Livewire::actingAs($renter)
            ->test(RenterRentalShow::class, ['rental' => $rental])
            ->set('cancellation_reason', '')
            ->call('cancelRental')
            ->assertHasErrors(['cancellation_reason']);

        $this->assertSame(RentalStatus::PaymentPending, $rental->fresh()->status);
    }
}
