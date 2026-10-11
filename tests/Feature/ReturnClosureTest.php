<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Enums\RentalStatus;
use App\Enums\SecurityDepositStatus;
use App\Livewire\Owner\Rentals\Show as OwnerRentalShow;
use App\Livewire\Renter\Rentals\Show as RenterRentalShow;
use App\Models\Category;
use App\Models\ConditionRecord;
use App\Models\Listing;
use App\Models\Rental;
use App\Models\RentalRequest;
use App\Models\SecurityDeposit;
use App\Models\User;
use App\Services\RentalLifecycle;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ReturnClosureTest extends TestCase
{
    use RefreshDatabase;

    private function activeRental(User $owner, User $renter): Rental
    {
        $category = Category::firstOrCreate(['slug' => 'return-tools'], ['name' => 'Return Tools']);
        $listing = Listing::create([
            'owner_id' => $owner->id, 'category_id' => $category->id,
            'name' => 'Return Pressure Washer', 'description' => 'Local rental.',
            'condition' => 'good', 'price_per_day' => 100, 'security_deposit' => 1000,
            'location' => 'Manila', 'max_rental_duration_days' => 10, 'status' => ListingStatus::Published,
        ]);
        $request = RentalRequest::create([
            'listing_id' => $listing->id, 'renter_id' => $renter->id,
            'start_date' => today(), 'end_date' => today()->addDays(2),
            'rental_days' => 3, 'fulfillment_method' => 'pickup', 'rental_fee' => 300,
            'commission_rate' => 10, 'commission_amount' => 30,
            'security_deposit' => 1000, 'total_amount' => 1330, 'status' => 'approved',
        ]);
        $rental = Rental::create([
            'rental_request_id' => $request->id, 'listing_id' => $listing->id,
            'owner_id' => $owner->id, 'renter_id' => $renter->id,
            'start_date' => $request->start_date, 'end_date' => $request->end_date,
            'rental_days' => 3, 'fulfillment_method' => 'pickup', 'rental_fee' => 300,
            'commission_rate' => 10, 'commission_amount' => 30,
            'security_deposit' => 1000, 'total_amount' => 1330, 'status' => 'active',
            'pickup_confirmed_by_owner_at' => now(), 'pickup_confirmed_by_renter_at' => now(),
        ]);
        SecurityDeposit::create(['rental_id' => $rental->id, 'amount' => 1000]);

        return $rental;
    }

    private function assertCompleted(Rental $rental): void
    {
        $rental->refresh();
        $this->assertSame(RentalStatus::Completed, $rental->status);
        $this->assertNotNull($rental->completed_at);
        $this->assertNotNull($rental->return_confirmed_by_owner_at);
        $this->assertNotNull($rental->afterConditionRecord);
    }

    public function test_owner_confirmation_marks_returned_without_requiring_renter_acknowledgement(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $rental = $this->activeRental($owner, $renter);

        Livewire::actingAs($owner)->test(OwnerRentalShow::class, ['rental' => $rental])
            ->call('confirmReturn')->assertHasNoErrors();

        $rental->refresh();
        $this->assertSame(RentalStatus::Returned, $rental->status);
        $this->assertNotNull($rental->return_confirmed_by_owner_at);
        $this->assertNull($rental->return_confirmed_by_renter_at);
        $this->assertNull($rental->completed_at);
        $this->assertSame(SecurityDepositStatus::Held, $rental->securityDeposit->status);
    }

    public function test_condition_saved_after_owner_confirmation_automatically_completes_and_prompts_both_parties(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $rental = $this->activeRental($owner, $renter);
        $rental->update(['status' => RentalStatus::Returned, 'return_confirmed_by_owner_at' => now()]);

        Livewire::actingAs($owner)->test(OwnerRentalShow::class, ['rental' => $rental])
            ->set('after_condition', 'good')->call('recordAfterCondition')->assertHasNoErrors()
            ->assertDontSee('Complete inspection');

        $this->assertCompleted($rental);
        $this->assertNull($rental->return_confirmed_by_renter_at);
        $this->assertSame(SecurityDepositStatus::ReturnEligible, $rental->securityDeposit->status);
        foreach ([$owner, $renter] as $participant) {
            $prompt = $participant->notifications()->where('data->type', 'review_request')->sole();
            $this->assertSame(route($participant->id === $owner->id ? 'owner.rentals.show' : 'renter.rentals.show', $rental).'#reviews', $prompt->data['url']);
            $this->assertStringContainsString('can', $prompt->data['message']);
        }
        $this->actingAs($owner)->get(route('owner.rentals.index'))->assertOk()->assertSee($rental->listing->name);
    }

    public function test_condition_can_be_saved_first_and_owner_confirmation_then_completes_the_rental(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $rental = $this->activeRental($owner, $renter);

        $component = Livewire::actingAs($owner)->test(OwnerRentalShow::class, ['rental' => $rental])
            ->assertSee('Record condition after rental')
            ->set('after_condition', 'good')->call('recordAfterCondition')->assertHasNoErrors();
        $this->assertSame(RentalStatus::Active, $rental->fresh()->status);
        $this->assertNull($rental->fresh()->completed_at);
        $this->assertSame(SecurityDepositStatus::Held, $rental->fresh()->securityDeposit->status);

        $component->call('confirmReturn')->assertHasNoErrors();

        $this->assertCompleted($rental);
        $this->assertNull($rental->return_confirmed_by_renter_at);
        $this->assertSame(SecurityDepositStatus::ReturnEligible, $rental->securityDeposit->status);
    }

    public function test_renter_acknowledgement_with_a_condition_record_cannot_close_the_rental(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $rental = $this->activeRental($owner, $renter);
        ConditionRecord::create(['rental_id' => $rental->id, 'recorded_by' => $owner->id, 'type' => 'after', 'condition' => 'good']);

        Livewire::actingAs($renter)->test(RenterRentalShow::class, ['rental' => $rental])
            ->call('confirmReturn')->assertHasNoErrors();

        $rental->refresh();
        $this->assertSame(RentalStatus::Active, $rental->status);
        $this->assertNotNull($rental->return_confirmed_by_renter_at);
        $this->assertNull($rental->return_confirmed_by_owner_at);
        $this->assertNull($rental->completed_at);
        $this->assertSame(SecurityDepositStatus::Held, $rental->securityDeposit->status);
        $this->assertSame(0, $renter->notifications()->where('data->type', 'review_request')->count());
    }

    public function test_damage_and_uploaded_photos_survive_automatic_completion_and_cap_the_deposit_claim(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $rental = $this->activeRental($owner, $renter);
        $rental->update(['status' => RentalStatus::Returned, 'return_confirmed_by_owner_at' => now()]);

        Livewire::actingAs($owner)->test(OwnerRentalShow::class, ['rental' => $rental])
            ->set('after_condition', 'fair')->set('after_has_damage', true)
            ->set('damage_type', 'Cracked case')->set('damage_description', 'Case cracked during the rental.')
            ->set('damage_estimated_cost', 1500)
            ->set('after_photos', [UploadedFile::fake()->image('returned.jpg')])
            ->set('damage_photos', [UploadedFile::fake()->image('damage.jpg')])
            ->call('recordAfterCondition')->assertHasNoErrors();

        $this->assertCompleted($rental);
        $this->assertSame(SecurityDepositStatus::DamageClaim, $rental->securityDeposit->status);
        $this->assertSame('pending', $rental->damageReport->status->value);
        $this->assertEquals(1500, $rental->damageReport->estimated_repair_cost);
        $this->assertEquals(1000, $rental->damageReport->proposed_deduction);
        $this->assertCount(1, $rental->afterConditionRecord->photos);
        $this->assertCount(1, $rental->damageReport->photos);
        Storage::disk('public')->assertExists($rental->afterConditionRecord->photos->first()->path);
        Storage::disk('public')->assertExists($rental->damageReport->photos->first()->path);
        $this->assertSame(1, $renter->notifications()->where('data->type', 'damage_claim_filed')->count());
    }

    public function test_failed_deposit_update_rolls_back_condition_damage_and_completion(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $rental = $this->activeRental($owner, $renter);
        $rental->update(['status' => RentalStatus::Returned, 'return_confirmed_by_owner_at' => now()]);
        $component = Livewire::actingAs($owner)->test(OwnerRentalShow::class, ['rental' => $rental])
            ->set('after_condition', 'fair')->set('after_has_damage', true)
            ->set('damage_type', 'Scratches')->set('damage_description', 'New scratches.')
            ->set('damage_estimated_cost', 300);
        $dispatcher = SecurityDeposit::getEventDispatcher();
        SecurityDeposit::setEventDispatcher(clone $dispatcher);
        SecurityDeposit::updating(function () {
            throw new \RuntimeException('Injected deposit failure');
        });

        try {
            $component->call('recordAfterCondition');
            $this->fail('The injected deposit failure must reach the caller.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Injected deposit failure', $exception->getMessage());
        } finally {
            SecurityDeposit::setEventDispatcher($dispatcher);
        }

        $this->assertDatabaseCount('condition_records', 0);
        $this->assertDatabaseCount('damage_reports', 0);
        $this->assertSame(RentalStatus::Returned, $rental->fresh()->status);
        $this->assertNull($rental->fresh()->completed_at);
        $this->assertSame(SecurityDepositStatus::Held, $rental->fresh()->securityDeposit->status);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_stale_condition_submission_cannot_modify_an_already_completed_rental(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $rental = $this->activeRental($owner, $renter);
        $rental->update(['status' => RentalStatus::Returned, 'return_confirmed_by_owner_at' => now()]);
        $component = Livewire::actingAs($owner)->test(OwnerRentalShow::class, ['rental' => $rental]);
        $rental->update(['status' => RentalStatus::Completed, 'completed_at' => now()]);

        $component->call('recordAfterCondition')->assertForbidden();

        $this->assertDatabaseCount('condition_records', 0);
        $this->assertSame(RentalStatus::Completed, $rental->fresh()->status);
    }

    public function test_repeated_service_return_confirmation_does_not_overwrite_timestamp_or_notify_again(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $rental = $this->activeRental($owner, $renter);
        RentalLifecycle::confirmReturn($rental, $owner);
        $confirmedAt = $rental->fresh()->return_confirmed_by_owner_at;
        $this->travel(1)->minutes();

        $this->expectException(AuthorizationException::class);
        try {
            RentalLifecycle::confirmReturn($rental, $owner);
        } finally {
            $this->assertTrue($confirmedAt->equalTo($rental->fresh()->return_confirmed_by_owner_at));
            $this->assertSame(1, $renter->notifications()->where('data->type', 'return_confirmed')->count());
        }
    }

    public function test_unrelated_user_cannot_confirm_return_through_the_service(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $rental = $this->activeRental($owner, $renter);

        $this->expectException(AuthorizationException::class);
        try {
            RentalLifecycle::confirmReturn($rental, User::factory()->create());
        } finally {
            $this->assertNull($rental->fresh()->return_confirmed_by_renter_at);
            $this->assertDatabaseCount('notifications', 0);
        }
    }

    public function test_public_completion_cannot_close_without_a_returned_condition_record(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $rental = $this->activeRental($owner, $renter);
        $rental->update(['status' => RentalStatus::Returned, 'return_confirmed_by_owner_at' => now()]);

        Livewire::actingAs($owner)->test(OwnerRentalShow::class, ['rental' => $rental])
            ->call('completeInspection')->assertForbidden();

        $this->assertSame(RentalStatus::Returned, $rental->fresh()->status);
        $this->assertNull($rental->fresh()->completed_at);
        $this->assertSame(SecurityDepositStatus::Held, $rental->fresh()->securityDeposit->status);
    }

    public function test_internal_inspection_preserves_returned_fixture_compatibility_and_prompts_both_parties(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $rental = $this->activeRental($owner, $renter);
        $rental->update(['status' => RentalStatus::Returned]);

        RentalLifecycle::completeInspection($rental);

        $this->assertSame(RentalStatus::Completed, $rental->fresh()->status);
        $this->assertNotNull($rental->fresh()->completed_at);
        $this->assertSame(SecurityDepositStatus::ReturnEligible, $rental->fresh()->securityDeposit->status);
        $this->assertSame(1, $owner->notifications()->where('data->type', 'review_request')->count());
        $this->assertSame(1, $renter->notifications()->where('data->type', 'review_request')->count());
    }

    public function test_owner_return_preserves_accrued_late_fees_before_the_daily_job_runs(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $rental = $this->activeRental($owner, $renter);
        $rental->update(['start_date' => today()->subDays(5), 'end_date' => today()->subDays(2)]);

        RentalLifecycle::confirmReturn($rental, $owner);

        $this->assertSame(RentalStatus::Returned, $rental->fresh()->status);
        $this->assertSame(2, $rental->fresh()->days_overdue);
        $this->assertEquals(200.00, $rental->fresh()->late_fee);
    }

    public function test_damage_photos_must_be_images_before_any_condition_or_damage_is_saved(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $rental = $this->activeRental($owner, $renter);
        $rental->update(['status' => RentalStatus::Returned, 'return_confirmed_by_owner_at' => now()]);

        Livewire::actingAs($owner)->test(OwnerRentalShow::class, ['rental' => $rental])
            ->set('after_has_damage', true)->set('damage_type', 'Crack')
            ->set('damage_description', 'New crack.')->set('damage_estimated_cost', 100)
            ->set('damage_photos', [UploadedFile::fake()->create('not-an-image.txt', 1, 'text/plain')])
            ->call('recordAfterCondition')->assertHasErrors(['damage_photos.0' => 'image']);

        $this->assertDatabaseCount('condition_records', 0);
        $this->assertDatabaseCount('damage_reports', 0);
        $this->assertSame(RentalStatus::Returned, $rental->fresh()->status);
    }
}
