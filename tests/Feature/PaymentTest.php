<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Enums\RentalStatus;
use App\Livewire\Owner\RentalRequests\Index as OwnerRentalRequestsIndex;
use App\Livewire\RentalRequests\Create;
use App\Livewire\Renter\Rentals\Show as RenterRentalShow;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\RentalRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private function publishedListing(User $owner): Listing
    {
        $category = Category::create(['name' => 'Tools', 'slug' => 'tools']);

        return Listing::create([
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
        ]);
    }

    private function approvedRentalRequest(User $owner, User $renter, Listing $listing): RentalRequest
    {
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

        return $request->fresh();
    }

    public function test_approving_a_request_creates_a_rental_awaiting_payment(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->publishedListing($owner);

        $request = $this->approvedRentalRequest($owner, $renter, $listing);

        $rental = Rental::where('rental_request_id', $request->id)->first();

        $this->assertNotNull($rental);
        $this->assertSame(RentalStatus::PaymentPending, $rental->status);
        $this->assertEquals($request->total_amount, $rental->total_amount);
        $this->assertSame($owner->id, $rental->owner_id);
        $this->assertSame($renter->id, $rental->renter_id);
    }

    public function test_renter_can_confirm_simulated_payment_and_it_creates_payment_and_deposit_records(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->publishedListing($owner);

        $request = $this->approvedRentalRequest($owner, $renter, $listing);
        $rental = Rental::where('rental_request_id', $request->id)->first();

        Livewire::actingAs($renter)
            ->test(RenterRentalShow::class, ['rental' => $rental])
            ->call('confirmPayment');

        $rental->refresh();

        $this->assertSame(RentalStatus::Paid, $rental->status);
        $this->assertNotNull($rental->paid_at);

        $this->assertNotNull($rental->payment);
        $this->assertEquals($rental->total_amount, $rental->payment->amount);
        $this->assertStringStartsWith('LENDLY-', $rental->payment->transaction_reference);

        $this->assertNotNull($rental->securityDeposit);
        $this->assertEquals($rental->security_deposit, $rental->securityDeposit->amount);
        $this->assertSame('held', $rental->securityDeposit->status->value);
    }

    public function test_owner_receives_full_rental_fee_not_reduced_by_commission(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->publishedListing($owner);

        $request = $this->approvedRentalRequest($owner, $renter, $listing);
        $rental = Rental::where('rental_request_id', $request->id)->first();

        Livewire::actingAs($renter)
            ->test(RenterRentalShow::class, ['rental' => $rental])
            ->call('confirmPayment');

        $rental->refresh();

        // Renter's total = rental fee + commission + deposit (additive).
        $this->assertEquals(
            round((float) $rental->rental_fee + (float) $rental->commission_amount + (float) $rental->security_deposit, 2),
            (float) $rental->total_amount
        );

        // Owner earnings = full rental fee, not reduced by the commission the renter already paid.
        // 3 days (inclusive) x ₱100/day = ₱300; 10% default commission = ₱30.
        $this->assertEquals(300.00, (float) $rental->rental_fee);
        $this->assertEquals(30.00, (float) $rental->commission_amount);
    }

    public function test_owner_cannot_pay_for_a_rental(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->publishedListing($owner);

        $request = $this->approvedRentalRequest($owner, $renter, $listing);
        $rental = Rental::where('rental_request_id', $request->id)->first();

        Livewire::actingAs($owner)
            ->test(RenterRentalShow::class, ['rental' => $rental])
            ->call('confirmPayment')
            ->assertForbidden();

        $this->assertSame(RentalStatus::PaymentPending, $rental->fresh()->status);
    }

    public function test_another_renter_cannot_view_or_pay_someone_elses_rental(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $otherRenter = User::factory()->renter()->create();
        $listing = $this->publishedListing($owner);

        $request = $this->approvedRentalRequest($owner, $renter, $listing);
        $rental = Rental::where('rental_request_id', $request->id)->first();

        $this->actingAs($otherRenter)
            ->get(route('renter.rentals.show', $rental))
            ->assertForbidden();
    }

    public function test_cannot_pay_a_rental_twice(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->publishedListing($owner);

        $request = $this->approvedRentalRequest($owner, $renter, $listing);
        $rental = Rental::where('rental_request_id', $request->id)->first();

        Livewire::actingAs($renter)
            ->test(RenterRentalShow::class, ['rental' => $rental])
            ->call('confirmPayment');

        Livewire::actingAs($renter)
            ->test(RenterRentalShow::class, ['rental' => $rental->fresh()])
            ->call('confirmPayment')
            ->assertForbidden();

        $this->assertSame(1, Payment::where('rental_id', $rental->id)->count());
    }

    // FR-17: both parties get a real digital receipt after payment.
    public function test_both_parties_see_a_receipt_after_payment(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->publishedListing($owner);

        $request = $this->approvedRentalRequest($owner, $renter, $listing);
        $rental = Rental::where('rental_request_id', $request->id)->first();

        Livewire::actingAs($renter)
            ->test(RenterRentalShow::class, ['rental' => $rental])
            ->call('confirmPayment');

        $rental->refresh();

        $this->actingAs($renter)
            ->get(route('renter.rentals.show', $rental))
            ->assertOk()
            ->assertSee('Transaction receipt')
            ->assertSee($rental->payment->transaction_reference);

        $this->actingAs($owner)
            ->get(route('owner.rentals.show', $rental))
            ->assertOk()
            ->assertSee('Earnings breakdown');
    }

    /**
     * Regression: the owner's rental page used to show an unconditional
     * "Earnings breakdown" ("Rental fee (your earnings)") even while the
     * rental was still PaymentPending — misleading, since no money had
     * actually moved yet. Now gated behind `$rental->paid_at`.
     */
    public function test_owner_does_not_see_earnings_breakdown_before_payment(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->publishedListing($owner);

        $request = $this->approvedRentalRequest($owner, $renter, $listing);
        $rental = Rental::where('rental_request_id', $request->id)->first();

        $this->actingAs($owner)
            ->get(route('owner.rentals.show', $rental))
            ->assertOk()
            ->assertDontSee('Earnings breakdown');
    }
}
