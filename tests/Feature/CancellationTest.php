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
use App\Services\RentalLifecycle;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public static function handOverConfirmations(): array
    {
        return [
            'owner pickup' => ['owner', 'pickup'],
            'renter pickup' => ['renter', 'pickup'],
            'owner delivery' => ['owner', 'delivery'],
            'renter delivery' => ['renter', 'delivery'],
        ];
    }

    #[DataProvider('handOverConfirmations')]
    public function test_first_hand_over_confirmation_blocks_cancellation_from_an_open_form(string $party, string $method): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $listing = $this->listingFor($owner);
        $rental = $this->rentalWithRequest($owner, $renter, $listing, RentalStatus::Paid, now()->addDay());
        $rental->update(['fulfillment_method' => $method]);
        SecurityDeposit::create(['rental_id' => $rental->id, 'amount' => 500]);

        $page = Livewire::actingAs($renter)
            ->test(RenterRentalShow::class, ['rental' => $rental])
            ->set('showCancelForm', true)
            ->assertSee('Confirm cancellation');

        RentalLifecycle::confirmPickup($rental, $party === 'owner' ? $owner : $renter);
        Notification::fake();

        $page->call('$refresh')
            ->assertDontSee('Cancel this booking')
            ->assertDontSee('Confirm cancellation');
        $this->assertNull($page->instance()->cancellationPreview());
        $this->assertFalse($renter->can('cancel', $rental->fresh()));

        // Calling the action directly must fail even if a form was already open.
        $page->set('cancellation_reason', 'Too late')
            ->call('cancelRental')
            ->assertForbidden();

        $rental->refresh();
        $this->assertSame(RentalStatus::Paid, $rental->status);
        $this->assertFalse($rental->bothConfirmedPickup());
        $this->assertNull($rental->cancelled_at);
        $this->assertNull($rental->cancellation_reason);
        $this->assertEquals(0, (float) $rental->cancellation_fee);
        $this->assertSame(RentalRequestStatus::Approved, $rental->rentalRequest->status);
        $this->assertSame('held', $rental->securityDeposit->status->value);
        Notification::assertNothingSent();
    }

    public function test_cancellation_service_rechecks_hand_over_when_given_a_stale_booking(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $listing = $this->listingFor($owner);
        $rental = $this->rentalWithRequest($owner, $renter, $listing, RentalStatus::Paid, now()->addDay());
        SecurityDeposit::create(['rental_id' => $rental->id, 'amount' => 500]);
        $this->actingAs($renter);

        RentalLifecycle::confirmPickup($rental->fresh(), $owner);
        $this->assertTrue($rental->isCancellableByRenter());
        Notification::fake();

        try {
            RentalLifecycle::cancel($rental, 'Stale cancellation');
            $this->fail('Cancellation must check the persisted hand-over inside the transaction.');
        } catch (AuthorizationException $exception) {
            $this->assertSame(RentalStatus::Paid, $rental->fresh()->status);
            $this->assertSame(RentalRequestStatus::Approved, $rental->rentalRequest->fresh()->status);
            $this->assertSame('held', $rental->securityDeposit->fresh()->status->value);
            $this->assertNull($rental->fresh()->cancelled_at);
            Notification::assertNothingSent();
        }
    }

    public function test_pre_hand_over_cancellation_uses_the_saved_agreement_policy(): void
    {
        $this->travelTo(now()->startOfDay());
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $listing = $this->listingFor($owner);
        $rental = $this->rentalWithRequest($owner, $renter, $listing, RentalStatus::Paid, now()->addDays(3));
        $rental->rentalRequest->update(['agreement_terms' => [
            'cancellation_window_hours' => 96,
            'cancellation_fee_percentage' => 15,
        ]]);
        SecurityDeposit::create(['rental_id' => $rental->id, 'amount' => 500]);

        $page = Livewire::actingAs($renter)->test(RenterRentalShow::class, ['rental' => $rental]);
        $this->assertSame(150.0, $page->instance()->cancellationPreview()['fee']);
        $page->set('cancellation_reason', 'Change of plans')->call('cancelRental')->assertHasNoErrors();

        $rental->refresh();
        $this->assertSame(RentalStatus::Cancelled, $rental->status);
        $this->assertEquals(150, (float) $rental->cancellation_fee);
        $this->assertSame(RentalRequestStatus::Cancelled, $rental->rentalRequest->status);
        $this->assertSame('refunded', $rental->securityDeposit->status->value);
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

    public function test_cancellation_reason_is_optional(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $listing = $this->listingFor($owner);
        $rental = $this->rentalWithRequest($owner, $renter, $listing, RentalStatus::PaymentPending, now()->addDays(10));

        Livewire::actingAs($renter)
            ->test(RenterRentalShow::class, ['rental' => $rental])
            ->set('cancellation_reason', '')
            ->call('cancelRental')
            ->assertHasNoErrors()
            ->assertSee('No reason provided.');

        $this->assertSame(RentalStatus::Cancelled, $rental->fresh()->status);
        $this->assertNull($rental->fresh()->cancellation_reason);
    }
}
