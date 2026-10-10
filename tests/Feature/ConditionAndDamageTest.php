<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Enums\RentalStatus;
use App\Enums\SecurityDepositStatus;
use App\Livewire\Owner\Rentals\Show as OwnerRentalShow;
use App\Livewire\Renter\Rentals\Show as RenterRentalShow;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\RentalRequest;
use App\Models\SecurityDeposit;
use App\Models\User;
use App\Policies\RentalPolicy;
use App\Services\RentalLifecycle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ConditionAndDamageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Builds a rental whose owner confirmed return, ready to record its
     * returned condition and automatically close it.
     */
    private function returnedRental(User $owner, User $renter): Rental
    {
        $category = Category::create(['name' => 'Tools', 'slug' => 'tools']);

        $listing = Listing::create([
            'owner_id' => $owner->id,
            'category_id' => $category->id,
            'name' => 'Pressure Washer',
            'description' => 'Great for cleaning driveways.',
            'condition' => 'good',
            'price_per_day' => 100,
            'security_deposit' => 1000,
            'location' => 'Manila',
            'max_rental_duration_days' => 15,
            'status' => ListingStatus::Published,
            'is_available' => true,
            'pickup_available' => true,
        ]);

        $request = RentalRequest::create([
            'listing_id' => $listing->id,
            'renter_id' => $renter->id,
            'start_date' => now()->subDays(5),
            'end_date' => now()->subDays(2),
            'rental_days' => 3,
            'fulfillment_method' => 'pickup',
            'rental_fee' => 300,
            'commission_rate' => 10,
            'commission_amount' => 30,
            'security_deposit' => 1000,
            'total_amount' => 1330,
            'status' => 'approved',
        ]);

        $rental = Rental::create([
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
            'security_deposit' => 1000,
            'total_amount' => 1330,
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

        RentalLifecycle::confirmPickup($rental, $renter);
        RentalLifecycle::confirmPickup($rental->fresh(), $owner);
        RentalLifecycle::confirmReturn($rental->fresh(), $owner);

        return $rental->fresh();
    }

    public function test_owner_can_record_before_condition(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $rental = $this->returnedRental($owner, $renter);

        Livewire::actingAs($owner)
            ->test(OwnerRentalShow::class, ['rental' => $rental])
            ->set('before_condition', 'excellent')
            ->set('before_notes', 'No visible wear.')
            ->call('recordBeforeCondition')
            ->assertHasNoErrors();

        $record = $rental->fresh()->beforeConditionRecord;
        $this->assertNotNull($record);
        $this->assertSame('excellent', $record->condition->value);
        $this->assertSame($owner->id, $record->recorded_by);
    }

    public function test_renter_cannot_record_condition(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $rental = $this->returnedRental($owner, $renter);

        $policy = new RentalPolicy;

        // The renter Livewire component doesn't even expose these methods,
        // but the policy itself must also refuse a renter, defense-in-depth.
        $this->assertFalse($policy->recordBeforeCondition($renter, $rental));
        $this->assertFalse($policy->recordAfterCondition($renter, $rental));
        $this->assertTrue($policy->recordBeforeCondition($owner, $rental));
    }

    public function test_recording_returned_condition_without_damage_completes_and_makes_deposit_return_eligible(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $rental = $this->returnedRental($owner, $renter);

        Livewire::actingAs($owner)
            ->test(OwnerRentalShow::class, ['rental' => $rental])
            ->set('after_condition', 'good')
            ->set('after_has_damage', false)
            ->call('recordAfterCondition')
            ->assertHasNoErrors();

        $this->assertNull($rental->fresh()->damageReport);

        $this->assertSame(RentalStatus::Completed, $rental->fresh()->status);
        $this->assertNotNull($rental->fresh()->completed_at);
        $this->assertSame(SecurityDepositStatus::ReturnEligible, $rental->fresh()->securityDeposit->status);
    }

    public function test_recording_damage_computes_capped_proposed_deduction_and_files_a_report(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $rental = $this->returnedRental($owner, $renter); // deposit = ₱1000

        Livewire::actingAs($owner)
            ->test(OwnerRentalShow::class, ['rental' => $rental])
            ->set('after_condition', 'fair')
            ->set('after_has_damage', true)
            ->set('damage_type', 'Cracked casing')
            ->set('damage_description', 'The plastic housing is cracked on one side.')
            ->set('damage_estimated_cost', 1500) // exceeds the ₱1000 deposit
            ->call('recordAfterCondition')
            ->assertHasNoErrors();

        $damageReport = $rental->fresh()->damageReport;
        $this->assertNotNull($damageReport);
        $this->assertEquals(1500.00, (float) $damageReport->estimated_repair_cost);
        // Proposed deduction is capped at the deposit amount, not the (higher) repair cost.
        $this->assertEquals(1000.00, (float) $damageReport->proposed_deduction);
    }

    public function test_recording_returned_condition_with_damage_completes_and_puts_deposit_in_damage_claim_status(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $rental = $this->returnedRental($owner, $renter);

        Livewire::actingAs($owner)
            ->test(OwnerRentalShow::class, ['rental' => $rental])
            ->set('after_condition', 'fair')
            ->set('after_has_damage', true)
            ->set('damage_type', 'Scratches')
            ->set('damage_description', 'Multiple scratches on the surface.')
            ->set('damage_estimated_cost', 300)
            ->call('recordAfterCondition');

        $this->assertSame(RentalStatus::Completed, $rental->fresh()->status);
        $this->assertSame(SecurityDepositStatus::DamageClaim, $rental->fresh()->securityDeposit->status);
    }

    public function test_renter_accepting_a_damage_claim_deducts_the_proposed_amount(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $rental = $this->returnedRental($owner, $renter);

        Livewire::actingAs($owner)
            ->test(OwnerRentalShow::class, ['rental' => $rental])
            ->set('after_condition', 'fair')
            ->set('after_has_damage', true)
            ->set('damage_type', 'Scratches')
            ->set('damage_description', 'Multiple scratches.')
            ->set('damage_estimated_cost', 250)
            ->call('recordAfterCondition');

        Livewire::actingAs($renter)
            ->test(RenterRentalShow::class, ['rental' => $rental->fresh()])
            ->call('acceptDamageClaim');

        $deposit = $rental->fresh()->securityDeposit;
        $this->assertSame(SecurityDepositStatus::Deducted, $deposit->status);
        $this->assertEquals(250.00, (float) $deposit->deducted_amount);
        $this->assertEquals(750.00, (float) ($deposit->amount - $deposit->deducted_amount)); // refund
        $this->assertSame('accepted', $rental->fresh()->damageReport->status->value);
    }

    public function test_renter_disputing_a_damage_claim_keeps_it_unresolved_for_admin(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $rental = $this->returnedRental($owner, $renter);

        Livewire::actingAs($owner)
            ->test(OwnerRentalShow::class, ['rental' => $rental])
            ->set('after_condition', 'fair')
            ->set('after_has_damage', true)
            ->set('damage_type', 'Scratches')
            ->set('damage_description', 'Multiple scratches.')
            ->set('damage_estimated_cost', 250)
            ->call('recordAfterCondition');

        Livewire::actingAs($renter)
            ->test(RenterRentalShow::class, ['rental' => $rental->fresh()])
            ->set('damage_response_notes', 'The scratches were already there before I rented it.')
            ->call('disputeDamageClaim')
            ->assertHasNoErrors();

        $damageReport = $rental->fresh()->damageReport;
        $this->assertSame('disputed', $damageReport->status->value);
        $this->assertSame('The scratches were already there before I rented it.', $damageReport->renter_response_notes);
        // Deposit stays in damage_claim, unresolved, until an admin (Phase 8) settles the dispute.
        $this->assertSame(SecurityDepositStatus::DamageClaim, $rental->fresh()->securityDeposit->status);
    }

    public function test_owner_can_only_release_a_return_eligible_deposit(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $rental = $this->returnedRental($owner, $renter);

        // Deposit is still "held" — not yet return-eligible — so release should be denied.
        Livewire::actingAs($owner)
            ->test(OwnerRentalShow::class, ['rental' => $rental])
            ->call('releaseDeposit')
            ->assertForbidden();

        Livewire::actingAs($owner)
            ->test(OwnerRentalShow::class, ['rental' => $rental])
            ->set('after_condition', 'good')
            ->call('recordAfterCondition');

        $this->assertSame(RentalStatus::Completed, $rental->fresh()->status);
        $this->assertSame(SecurityDepositStatus::ReturnEligible, $rental->fresh()->securityDeposit->status);
        Livewire::actingAs($owner)
            ->test(OwnerRentalShow::class, ['rental' => $rental->fresh()])
            ->call('releaseDeposit')
            ->assertHasNoErrors();

        $this->assertSame(SecurityDepositStatus::Released, $rental->fresh()->securityDeposit->status);
    }

    public function test_admin_can_view_but_not_record_condition(): void
    {
        // An unrelated owner can't even reach mount() (RentalPolicy::view denies
        // them), so this checks the more precise boundary: admin passes the
        // view gate but is still refused the owner-only recording action.
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $admin = User::factory()->admin()->create();
        $rental = $this->returnedRental($owner, $renter);

        Livewire::actingAs($admin)
            ->test(OwnerRentalShow::class, ['rental' => $rental])
            ->call('recordAfterCondition')
            ->assertForbidden();
    }
}
