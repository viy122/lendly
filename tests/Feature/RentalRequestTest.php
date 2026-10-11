<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Enums\RentalRequestStatus;
use App\Livewire\Listings\Show as ListingShow;
use App\Livewire\Owner\RentalRequests\Index as OwnerRentalRequestsIndex;
use App\Livewire\RentalRequests\Create;
use App\Livewire\Renter\RentalRequests\Index as RenterRentalRequestsIndex;
use App\Models\Category;
use App\Models\CommissionSetting;
use App\Models\Listing;
use App\Models\Rental;
use App\Models\RentalRequest;
use App\Models\User;
use App\Notifications\TalaNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class RentalRequestTest extends TestCase
{
    use RefreshDatabase;

    private function publishedListing(User $owner, array $overrides = []): Listing
    {
        $category = Category::create(['name' => 'Tools', 'slug' => 'tools']);

        return Listing::unguarded(fn () => Listing::create(array_merge([
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
        ], $overrides)));
    }

    public function test_renter_can_submit_a_rental_request_with_correct_cost_breakdown(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->publishedListing($owner);

        Livewire::actingAs($renter)
            ->test(Create::class, ['listing' => $listing])->set('accept_terms', true)
            ->set('start_date', now()->addDays(3)->toDateString())
            ->set('end_date', now()->addDays(5)->toDateString()) // 3 days inclusive
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

    public function test_listing_embeds_the_request_form_for_an_eligible_renter(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->publishedListing($owner);

        Livewire::actingAs($renter)
            ->test(ListingShow::class, ['listing' => $listing])
            ->assertSeeLivewire(Create::class)
            ->assertSee('aria-controls="rental-request-modal"', false)
            ->assertSee('Cancel')
            ->assertDontSee('Back to listing');
    }

    public function test_request_modal_submits_with_the_existing_costs_and_redirect(): void
    {
        Notification::fake();
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->publishedListing($owner);

        Livewire::actingAs($renter)
            ->test(Create::class, ['listing' => $listing, 'modal' => true])->set('accept_terms', true)
            ->set('start_date', now()->addDays(3)->toDateString())
            ->set('end_date', now()->addDays(5)->toDateString())
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect(route('renter.rental-requests.index'));

        $this->assertDatabaseHas('rental_requests', [
            'listing_id' => $listing->id,
            'renter_id' => $renter->id,
            'rental_days' => 3,
            'total_amount' => 830,
        ]);
        Notification::assertSentTo([$owner, $renter], TalaNotification::class);
    }

    public function test_listing_does_not_embed_a_request_form_for_guests_owners_or_unavailable_items(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->publishedListing($owner);

        Livewire::test(ListingShow::class, ['listing' => $listing])
            ->assertDontSeeLivewire(Create::class)
            ->assertSee('Log in to request');

        Livewire::actingAs($owner)
            ->test(ListingShow::class, ['listing' => $listing])
            ->assertDontSeeLivewire(Create::class)
            ->assertSee('This is your listing');

        $listing->update(['is_available' => false]);

        Livewire::actingAs($renter)
            ->test(ListingShow::class, ['listing' => $listing])
            ->assertDontSeeLivewire(Create::class)
            ->assertSee('Currently unavailable');
    }

    public function test_embedded_request_form_rechecks_the_renter_interface_on_submit(): void
    {
        Notification::fake();
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->publishedListing($owner);

        $form = Livewire::actingAs($renter)
            ->test(Create::class, ['listing' => $listing, 'modal' => true])->set('accept_terms', true);

        session()->put('active_interface', 'owner');

        $form->call('submit')->assertForbidden();
        $this->assertSame(0, RentalRequest::count());
        Notification::assertNothingSent();
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

    public static function unavailableListingStates(): array
    {
        return [
            'unavailable' => [['is_available' => false]],
            'deleted' => [['deleted_at' => '2026-10-07 00:00:00']],
            'inactive' => [['status' => ListingStatus::Inactive]],
            'pending approval' => [['status' => ListingStatus::PendingApproval]],
            'rejected' => [['status' => ListingStatus::Rejected]],
            'inactive and unavailable' => [['status' => ListingStatus::Inactive, 'is_available' => false]],
        ];
    }

    #[DataProvider('unavailableListingStates')]
    public function test_open_form_cannot_submit_after_listing_becomes_unavailable(array $changes): void
    {
        Notification::fake();

        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->publishedListing($owner);

        $form = Livewire::actingAs($renter)
            ->test(Create::class, ['listing' => $listing])->set('accept_terms', true)
            ->set('start_date', now()->addDays(3)->toDateString())
            ->set('end_date', now()->addDays(5)->toDateString());

        $listing->forceFill($changes)->save();

        $form->call('submit')
            ->assertHasErrors(['listing'])
            ->assertSee('This item is no longer available for rent. Please choose another item.')
            ->assertNoRedirect();

        $this->assertSame(0, RentalRequest::count());
        Notification::assertNothingSent();
    }

    public function test_submission_rechecks_availability_even_with_a_stale_listing_model(): void
    {
        Notification::fake();

        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->publishedListing($owner);

        $component = Livewire::actingAs($renter)
            ->test(Create::class, ['listing' => $listing])->set('accept_terms', true)
            ->instance();

        Listing::whereKey($listing->id)->update(['is_available' => false]);
        $this->assertTrue($component->listing->is_available);

        $component->submit();

        $this->assertTrue($component->getErrorBag()->has('listing'));
        $this->assertSame(0, RentalRequest::count());
        Notification::assertNothingSent();
    }

    public function test_submission_and_both_pending_notices_roll_back_if_the_second_notice_fails(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->publishedListing($owner);
        $component = Livewire::actingAs($renter)->test(Create::class, ['listing' => $listing])->set('accept_terms', true)
            ->set('start_date', now()->addDays(3)->toDateString())
            ->set('end_date', now()->addDays(5)->toDateString())->instance();
        $noticeCount = DB::table('notifications')->count();

        Event::listen(NotificationSending::class, function (NotificationSending $event) use ($renter) {
            if ($event->notification->type === 'rental_request_submitted' && $event->notifiable->is($renter)) {
                throw new RuntimeException('Pending notification storage failed.');
            }
        });

        try {
            $component->submit();
            $this->fail('The simulated notification failure must be raised.');
        } catch (RuntimeException $error) {
            $this->assertSame('Pending notification storage failed.', $error->getMessage());
        } finally {
            Event::forget(NotificationSending::class);
        }

        $this->assertSame(0, RentalRequest::count());
        $this->assertSame($noticeCount, DB::table('notifications')->count());
        $component->submit();
        $this->assertSame(1, RentalRequest::count());
        $this->assertSame($noticeCount + 2, DB::table('notifications')->count());
    }

    public function test_open_form_uses_current_listing_charges_and_one_commission_snapshot_on_submission(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->publishedListing($owner);
        $form = Livewire::actingAs($renter)->test(Create::class, ['listing' => $listing])->set('accept_terms', true)
            ->set('start_date', now()->addDays(3)->toDateString())
            ->set('end_date', now()->addDays(5)->toDateString());
        $listing->update(['price_per_day' => 125.25, 'security_deposit' => 250.50]);
        CommissionSetting::current()->update(['commission_rate' => 12.5]);

        $form->call('submit')->assertHasErrors(['accept_terms'])->assertSet('accept_terms', false)->assertNoRedirect();
        $this->assertSame(0, RentalRequest::count());
        $form->set('accept_terms', true)->call('submit')->assertHasNoErrors();
        $request = RentalRequest::sole();
        $this->assertSame(3, $request->rental_days);
        $this->assertSame('375.75', $request->rental_fee);
        $this->assertSame('12.50', $request->commission_rate);
        $this->assertSame('46.97', $request->commission_amount);
        $this->assertSame('250.50', $request->security_deposit);
        $this->assertSame('673.22', $request->total_amount);
        $this->assertNull($request->renter_terms_accepted_at);
        $this->assertNull($request->owner_terms_accepted_at);
        $this->assertSame(0, Rental::count());
    }

    public static function changedRequestConstraints(): array
    {
        return [
            'pickup withdrawn' => [['pickup_available' => false, 'delivery_available' => true], 'fulfillment_method'],
            'maximum duration shortened' => [['max_rental_duration_days' => 1], 'end_date'],
            'available dates shortened' => [['available_until' => now()->addDays(4)->toDateString()], 'end_date'],
        ];
    }

    #[DataProvider('changedRequestConstraints')]
    public function test_open_form_rechecks_changed_fulfillment_duration_and_available_dates(array $changes, string $field): void
    {
        Notification::fake();
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->publishedListing($owner, [
            'available_from' => now()->toDateString(), 'available_until' => now()->addMonth()->toDateString(),
        ]);
        $form = Livewire::actingAs($renter)->test(Create::class, ['listing' => $listing])->set('accept_terms', true)
            ->set('start_date', now()->addDays(3)->toDateString())
            ->set('end_date', now()->addDays(5)->toDateString());
        $listing->update($changes);

        $form->call('submit')->assertHasErrors([$field])->assertNoRedirect();
        $this->assertSame(0, RentalRequest::count());
        Notification::assertNothingSent();
    }

    #[DataProvider('unavailableListingStates')]
    public function test_owner_cannot_approve_after_the_listing_becomes_unavailable(array $changes): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->publishedListing($owner);
        Livewire::actingAs($renter)->test(Create::class, ['listing' => $listing])->set('accept_terms', true)
            ->set('start_date', now()->addDays(3)->toDateString())
            ->set('end_date', now()->addDays(5)->toDateString())->call('submit')->assertHasNoErrors();
        $request = RentalRequest::sole();
        $form = Livewire::actingAs($owner)->test(OwnerRentalRequestsIndex::class);
        $listing->forceFill($changes)->save();
        Notification::fake();

        $form->call('approve', $request->id)->assertHasErrors(['approve'])->assertNoRedirect();
        $this->assertSame(RentalRequestStatus::Requested, $request->fresh()->status);
        $this->assertNull($request->fresh()->agreement_terms);
        $this->assertSame(0, Rental::count());
        Notification::assertNothingSent();
    }

    #[DataProvider('unavailableListingStates')]
    public function test_unavailable_listing_cannot_open_the_request_form(array $changes): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->publishedListing($owner, $changes);

        $this->actingAs($renter)
            ->get(route('renter.rental-requests.create', $listing))
            ->assertNotFound();
    }

    public function test_cannot_request_dates_beyond_max_rental_duration(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->publishedListing($owner, ['max_rental_duration_days' => 3]);

        Livewire::actingAs($renter)
            ->test(Create::class, ['listing' => $listing])->set('accept_terms', true)
            ->set('start_date', now()->addDay()->toDateString())
            ->set('end_date', now()->addDays(10)->toDateString())
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
            ->test(Create::class, ['listing' => $listing])->set('accept_terms', true)
            ->set('start_date', now()->subDays(2)->toDateString())
            ->set('end_date', now()->toDateString())
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
            ->test(Create::class, ['listing' => $listing])->set('accept_terms', true)
            ->set('start_date', now()->addDays(5)->toDateString())
            ->set('end_date', now()->addDays(8)->toDateString())
            ->call('submit');

        $firstRequest = RentalRequest::first();

        Livewire::actingAs($owner)
            ->test(OwnerRentalRequestsIndex::class)
            ->call('approve', $firstRequest->id);

        $this->assertSame(RentalRequestStatus::Approved, $firstRequest->fresh()->status);

        // Overlapping dates for a different renter should now be rejected at request time.
        Livewire::actingAs($renterB)
            ->test(Create::class, ['listing' => $listing])->set('accept_terms', true)
            ->set('start_date', now()->addDays(6)->toDateString())
            ->set('end_date', now()->addDays(9)->toDateString())
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
            ->test(Create::class, ['listing' => $listing])->set('accept_terms', true)
            ->set('start_date', now()->addDays(5)->toDateString())
            ->set('end_date', now()->addDays(8)->toDateString())
            ->call('submit');
        $requestA = RentalRequest::where('renter_id', $renterA->id)->first();

        Livewire::actingAs($renterB)
            ->test(Create::class, ['listing' => $listing])->set('accept_terms', true)
            ->set('start_date', now()->addDays(7)->toDateString())
            ->set('end_date', now()->addDays(10)->toDateString())
            ->call('submit');
        $requestB = RentalRequest::where('renter_id', $renterB->id)->first();

        // Both pending requests coexist until the owner picks one.
        $this->assertSame(RentalRequestStatus::Requested, $requestA->fresh()->status);
        $this->assertSame(RentalRequestStatus::Requested, $requestB->fresh()->status);

        Livewire::actingAs($owner)
            ->test(OwnerRentalRequestsIndex::class)
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
            ->test(Create::class, ['listing' => $listing])->set('accept_terms', true)
            ->set('start_date', now()->addDay()->toDateString())
            ->set('end_date', now()->addDays(2)->toDateString())
            ->call('submit');

        $request = RentalRequest::first();

        Livewire::actingAs($ownerB)
            ->test(OwnerRentalRequestsIndex::class)
            ->call('approve', $request->id)
            ->assertForbidden();
    }

    public function test_renter_can_cancel_requests_without_a_booking_even_after_the_scheduled_start(): void
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
            ->assertHasNoErrors();

        $this->assertSame(RentalRequestStatus::Cancelled, $startedRequest->fresh()->status);
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
