<?php

namespace Tests\Feature;

use App\Enums\DamageReportStatus;
use App\Enums\DisputeResolution;
use App\Enums\ListingStatus;
use App\Enums\SecurityDepositStatus;
use App\Livewire\Admin\Disputes\Index as AdminDisputesIndex;
use App\Livewire\Owner\Rentals\Show as OwnerRentalShow;
use App\Livewire\Renter\Rentals\Show as RenterRentalShow;
use App\Models\Category;
use App\Models\Dispute;
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

class ReviewsAndDisputeTest extends TestCase
{
    use RefreshDatabase;

    private function completedRental(User $owner, User $renter, array $overrides = []): Rental
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

        $rental = Rental::create(array_merge([
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
            'days_overdue' => 0,
        ], $overrides));

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
        RentalLifecycle::confirmReturn($rental->fresh(), $renter);
        RentalLifecycle::confirmReturn($rental->fresh(), $owner);

        return $rental->fresh();
    }

    /**
     * Saving the returned condition automatically completes the rental.
     */
    private function markRentalCompleted(Rental $rental, User $owner): void
    {
        Livewire::actingAs($owner)
            ->test(OwnerRentalShow::class, ['rental' => $rental])
            ->set('after_condition', 'good')
            ->call('recordAfterCondition')->assertHasNoErrors();
    }

    public function test_renter_can_review_owner_and_listing_after_completion(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $rental = $this->completedRental($owner, $renter);

        $this->markRentalCompleted($rental, $owner);

        Livewire::actingAs($renter)
            ->test(RenterRentalShow::class, ['rental' => $rental->fresh()])
            ->set('owner_rating', 5)
            ->set('owner_comment', 'Great owner!')
            ->call('submitOwnerReview')
            ->assertHasNoErrors();

        Livewire::actingAs($renter)
            ->test(RenterRentalShow::class, ['rental' => $rental->fresh()])
            ->set('listing_rating', 4)
            ->call('submitListingReview')
            ->assertHasNoErrors();

        $rental->refresh();
        $this->assertEquals(5, $rental->reviewFromRenterToOwner->rating);
        $this->assertEquals(4, $rental->reviewFromRenterToListing->rating);
        $this->assertEquals(5.0, $owner->averageRatingAsOwner());
        $this->assertEquals(4.0, $rental->listing->fresh()->averageRating());
    }

    public function test_renter_cannot_review_the_same_rental_twice(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $rental = $this->completedRental($owner, $renter);

        $this->markRentalCompleted($rental, $owner);

        Livewire::actingAs($renter)
            ->test(RenterRentalShow::class, ['rental' => $rental->fresh()])
            ->set('owner_rating', 5)
            ->call('submitOwnerReview');

        Livewire::actingAs($renter)
            ->test(RenterRentalShow::class, ['rental' => $rental->fresh()])
            ->set('owner_rating', 2)
            ->call('submitOwnerReview')
            ->assertForbidden();

        $this->assertSame(5, $rental->fresh()->reviewFromRenterToOwner->rating);
    }

    public function test_owner_can_review_renter_after_completion(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $rental = $this->completedRental($owner, $renter);

        $this->markRentalCompleted($rental, $owner);

        Livewire::actingAs($owner)
            ->test(OwnerRentalShow::class, ['rental' => $rental->fresh()])
            ->set('renter_rating', 3)
            ->set('renter_comment', 'Returned a bit late.')
            ->call('submitRenterReview')
            ->assertHasNoErrors();

        $this->assertEquals(3, $rental->fresh()->reviewFromOwnerToRenter->rating);
        $this->assertEquals(3.0, $renter->averageRatingAsRenter());
    }

    public function test_disputing_a_damage_claim_creates_a_formal_dispute_for_admin(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $rental = $this->completedRental($owner, $renter);

        Livewire::actingAs($owner)
            ->test(OwnerRentalShow::class, ['rental' => $rental])
            ->set('after_condition', 'fair')
            ->set('after_has_damage', true)
            ->set('damage_type', 'Scratches')
            ->set('damage_description', 'Scratches on the surface.')
            ->set('damage_estimated_cost', 400)
            ->call('recordAfterCondition');

        Livewire::actingAs($renter)
            ->test(RenterRentalShow::class, ['rental' => $rental->fresh()])
            ->set('damage_response_notes', 'That scratch was already there.')
            ->call('disputeDamageClaim');

        $dispute = Dispute::first();
        $this->assertNotNull($dispute);
        $this->assertSame($rental->id, $dispute->rental_id);
        $this->assertNotNull($dispute->damage_report_id);
        $this->assertTrue($dispute->isOpen());
    }

    public function test_admin_rejecting_a_disputed_claim_releases_the_deposit(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $admin = User::factory()->admin()->create();
        $rental = $this->completedRental($owner, $renter);

        Livewire::actingAs($owner)
            ->test(OwnerRentalShow::class, ['rental' => $rental])
            ->set('after_condition', 'fair')
            ->set('after_has_damage', true)
            ->set('damage_type', 'Scratches')
            ->set('damage_description', 'Scratches on the surface.')
            ->set('damage_estimated_cost', 400)
            ->call('recordAfterCondition');

        Livewire::actingAs($renter)
            ->test(RenterRentalShow::class, ['rental' => $rental->fresh()])
            ->set('damage_response_notes', 'Not my fault.')
            ->call('disputeDamageClaim');

        $dispute = Dispute::first();

        Livewire::actingAs($admin)
            ->test(AdminDisputesIndex::class)
            ->call('startResolving', $dispute->id)
            ->set('resolution', DisputeResolution::RejectedClaim->value)
            ->set('resolution_notes', 'Photos show pre-existing wear.')
            ->call('confirmResolve')
            ->assertHasNoErrors();

        $dispute->refresh();
        $this->assertTrue($dispute->status->value === 'resolved');
        $this->assertSame(DisputeResolution::RejectedClaim, $dispute->resolution);

        $rental->refresh();
        $this->assertSame(DamageReportStatus::Rejected, $rental->damageReport->status);
        $this->assertSame(SecurityDepositStatus::ReturnEligible, $rental->securityDeposit->status);
        $this->assertEquals(0.0, (float) $rental->securityDeposit->deducted_amount);
    }

    public function test_admin_approving_a_disputed_claim_deducts_the_deposit(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $admin = User::factory()->admin()->create();
        $rental = $this->completedRental($owner, $renter);

        Livewire::actingAs($owner)
            ->test(OwnerRentalShow::class, ['rental' => $rental])
            ->set('after_condition', 'fair')
            ->set('after_has_damage', true)
            ->set('damage_type', 'Scratches')
            ->set('damage_description', 'Scratches on the surface.')
            ->set('damage_estimated_cost', 400)
            ->call('recordAfterCondition');

        Livewire::actingAs($renter)
            ->test(RenterRentalShow::class, ['rental' => $rental->fresh()])
            ->set('damage_response_notes', 'Not my fault.')
            ->call('disputeDamageClaim');

        $dispute = Dispute::first();

        Livewire::actingAs($admin)
            ->test(AdminDisputesIndex::class)
            ->call('startResolving', $dispute->id)
            ->set('resolution', DisputeResolution::ApprovedClaim->value)
            ->call('confirmResolve');

        $rental->refresh();
        $this->assertSame(DamageReportStatus::Accepted, $rental->damageReport->status);
        $this->assertSame(SecurityDepositStatus::Deducted, $rental->securityDeposit->status);
        $this->assertEquals(400.00, (float) $rental->securityDeposit->deducted_amount);
    }

    public function test_only_admin_can_resolve_disputes(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $rental = $this->completedRental($owner, $renter);

        $dispute = Dispute::create([
            'rental_id' => $rental->id,
            'raised_by' => $renter->id,
            'reason' => 'item_not_as_described',
            'description' => 'Not what was pictured.',
        ]);

        $this->actingAs($renter)
            ->get(route('admin.disputes.index'))
            ->assertForbidden();
    }

    public function test_general_dispute_can_be_raised_without_a_damage_report(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $rental = $this->completedRental($owner, $renter);

        Livewire::actingAs($renter)
            ->test(RenterRentalShow::class, ['rental' => $rental])
            ->set('dispute_reason', 'item_not_as_described')
            ->set('dispute_description', 'The item was much older than pictured.')
            ->call('createDispute')
            ->assertHasNoErrors();

        $dispute = Dispute::first();
        $this->assertNotNull($dispute);
        $this->assertNull($dispute->damage_report_id);
        $this->assertSame($renter->id, $dispute->raised_by);
    }
}
