<?php

namespace Tests\Feature;

use App\Enums\NotificationType;
use App\Enums\RentalAdminActionType;
use App\Enums\RentalStatus;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Listings\Browse;
use App\Livewire\Listings\Map;
use App\Livewire\Owner\Listings\Form;
use App\Livewire\Owner\RentalRequests\Index as Approvals;
use App\Livewire\Owner\Rentals\Show;
use App\Livewire\RentalRequests\Create as Requests;
use App\Livewire\Renter\RentalRequests\Index;
use App\Livewire\Renter\Rentals\Show as RenterRental;
use App\Models\Category;
use App\Models\Listing;
use App\Models\OfflinePaymentSetting;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\RentalRequest;
use App\Models\SecurityDeposit;
use App\Models\User;
use App\Notifications\TalaNotification;
use App\Services\RentalAdministration;
use App\Services\RentalAgreement;
use App\Services\RentalLifecycle;
use App\Services\RentalPayments;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use Tests\Concerns\CompletesOfflinePayment;
use Tests\TestCase;

/**
 * Regression tests for the user-selected requirements repairs.
 * All data is disposable SQLite data; no real email is sent.
 */
class RentalRequirementsFixTest extends TestCase
{
    use CompletesOfflinePayment;
    use RefreshDatabase;

    private function listing(User $owner, array $attributes = []): Listing
    {
        $category = Category::firstOrCreate(['slug' => 'audit-tools'], ['name' => 'Audit tools']);

        return Listing::create(array_merge([
            'owner_id' => $owner->id, 'category_id' => $category->id,
            'name' => 'Audit camera', 'description' => 'Disposable audit fixture.',
            'condition' => 'good', 'price_per_day' => 100, 'security_deposit' => 500,
            'location' => 'Manila', 'latitude' => 14.5995, 'longitude' => 120.9842,
            'max_rental_duration_days' => 10, 'status' => 'published',
        ], $attributes));
    }

    private function request(User $renter, Listing $listing): RentalRequest
    {
        return RentalRequest::create([
            'listing_id' => $listing->id, 'renter_id' => $renter->id,
            'start_date' => now()->addDays(3), 'end_date' => now()->addDays(5),
            'rental_days' => 3, 'fulfillment_method' => 'pickup',
            'rental_fee' => 300, 'commission_rate' => 10, 'commission_amount' => 30,
            'security_deposit' => 500, 'total_amount' => 830,
            'status' => 'requested', 'renter_terms_accepted_at' => now(),
        ]);
    }

    private function booking(User $owner, User $renter, string $status = 'payment_pending'): Rental
    {
        $listing = $this->listing($owner);
        $request = $this->request($renter, $listing);
        $request->update([
            'status' => 'approved', 'owner_terms_accepted_at' => now(),
            'agreement_terms' => RentalAgreement::termsFor($request),
        ]);

        return Rental::create(array_merge($request->only([
            'listing_id', 'renter_id', 'start_date', 'end_date', 'rental_days',
            'fulfillment_method', 'rental_fee', 'commission_rate', 'commission_amount',
            'security_deposit', 'total_amount',
        ]), ['rental_request_id' => $request->id, 'owner_id' => $owner->id, 'status' => $status]));
    }

    public function test_fr23_owner_cannot_submit_a_review_attributed_to_the_renter(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $rental = $this->booking($owner, $renter, 'completed');

        Livewire::actingAs($owner)->test(RenterRental::class, ['rental' => $rental])
            ->set('owner_rating', 5)->set('owner_comment', 'Owner reviewing themselves.')
            ->call('submitOwnerReview')->assertForbidden();
    }

    public function test_fr11_unavailable_listing_cannot_receive_a_request_from_an_open_form(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $listing = $this->listing($owner);
        $form = Livewire::actingAs($renter)->test(Requests::class, ['listing' => $listing])
            ->set('start_date', now()->addDays(3)->toDateString())
            ->set('end_date', now()->addDays(5)->toDateString())->set('accept_terms', true);

        $listing->update(['is_available' => false]);
        $form->call('submit');

        $this->assertSame(0, RentalRequest::count(), 'An unavailable listing accepted a new request.');
    }

    public function test_nfr01_interrupted_final_acceptance_does_not_finalize_terms_without_a_booking(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $request = $this->request($renter, $this->listing($owner));
        Livewire::actingAs($owner)->test(Approvals::class)->call('approve', $request->id);
        RentalAgreement::accept($request, $owner);
        Rental::creating(fn () => throw new \RuntimeException('Audit: booking storage unavailable'));

        try {
            RentalAgreement::accept($request, $renter);
            $this->fail('The injected failure did not run.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Audit: booking storage unavailable', $exception->getMessage());
        } finally {
            app('events')->forget('eloquent.creating: '.Rental::class);
        }

        $this->assertSame('approved', $request->fresh()->status->value);
        $this->assertNotNull($request->fresh()->owner_terms_accepted_at);
        $this->assertNull($request->fresh()->renter_terms_accepted_at);
        $this->assertSame(0, Rental::count());
    }

    public function test_nfr03_interrupted_payment_does_not_leave_a_paid_record_with_an_unpaid_booking(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $rental = $this->booking($owner, $renter);
        SecurityDeposit::creating(fn () => throw new \RuntimeException('Audit: deposit storage unavailable'));

        try {
            $this->completeOfflinePayment($rental);
            $this->fail('The injected failure did not run.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Audit: deposit storage unavailable', $exception->getMessage());
        } finally {
            app('events')->forget('eloquent.creating: '.SecurityDeposit::class);
        }

        $this->assertSame(0, Payment::where('rental_id', $rental->id)->count());
        $this->assertSame(RentalStatus::PaymentPending, $rental->fresh()->status);
    }

    public function test_nfr19_zero_latitude_still_filters_by_distance(): void
    {
        $owner = User::factory()->create();
        $this->listing($owner, ['name' => 'Nearby', 'latitude' => 0, 'longitude' => 120]);
        $this->listing($owner, ['name' => 'Far away', 'latitude' => 1, 'longitude' => 120]);

        $markers = Livewire::test(Map::class)->set('centerLat', 0)->set('centerLng', 120)
            ->set('radiusKm', 5)->get('markers');

        $this->assertCount(1, $markers);
        $this->assertSame('Nearby', $markers[0]['name']);
    }

    public function test_nfr03_two_payment_actions_loaded_before_either_finishes_record_only_one_payment(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $rental = $this->booking($owner, $renter);
        Storage::fake('local');
        OfflinePaymentSetting::create(['method' => 'bank_transfer', 'instructions' => 'Test payment instructions', 'enabled' => true]);
        $submission = RentalPayments::submit($rental, $renter, 'TRANSFER-STALE', $this->paymentProof());
        $admin = User::factory()->admin()->create();
        $first = $rental->fresh();
        $second = $rental->fresh();
        RentalAdministration::perform($first, $admin, RentalAdminActionType::PaymentVerified, 'Funds received', $submission->id, true);
        try {
            RentalAdministration::perform($second, $admin, RentalAdminActionType::PaymentVerified, 'Funds received', $submission->id, true);
            $this->fail('A stale payment verification must be rejected.');
        } catch (AuthorizationException) {
            $this->assertSame(RentalStatus::Paid, $rental->fresh()->status);
        }

        $this->assertSame(1, Payment::where('rental_id', $rental->id)->count());
        $this->assertSame(1, SecurityDeposit::where('rental_id', $rental->id)->count());
    }

    public function test_nfr01_interleaved_approvals_cannot_book_the_same_dates_twice(): void
    {
        $owner = User::factory()->create();
        $listing = $this->listing($owner);
        $first = $this->request(User::factory()->create(), $listing);
        $second = $this->request(User::factory()->create(), $listing);
        $this->actingAs($owner);
        $interleaved = false;

        RentalRequest::updating(function (RentalRequest $request) use ($first, $second, &$interleaved) {
            if ($request->id === $first->id && $request->status->value === 'approved' && ! $interleaved) {
                $interleaved = true;
                $otherAction = app(Approvals::class);
                $otherAction->approve($second->id);
            }
        });

        try {
            $action = app(Approvals::class);
            $action->approve($first->id);
        } finally {
            app('events')->forget('eloquent.updating: '.RentalRequest::class);
        }

        $this->assertTrue($interleaved, 'The controlled interleaving did not run.');
        $approved = RentalRequest::where('listing_id', $listing->id)->where('status', 'approved')->sole();
        RentalAgreement::accept($approved, $owner);
        RentalAgreement::accept($approved, $approved->renter);
        $this->assertSame(1, Rental::where('listing_id', $listing->id)->count());
    }

    public function test_fr40_booking_confirmation_notifies_both_parties(): void
    {
        Notification::fake();
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $request = $this->request($renter, $this->listing($owner));

        Livewire::actingAs($owner)->test(Approvals::class)->call('approve', $request->id);
        RentalAgreement::accept($request, $owner);
        RentalAgreement::accept($request, $renter);

        Notification::assertSentTo($renter, TalaNotification::class);
        Notification::assertSentTo($owner, TalaNotification::class);
    }

    public function test_fr14_approval_rejects_pending_requests_that_touch_the_booked_end_date(): void
    {
        $owner = User::factory()->create();
        $listing = $this->listing($owner);
        $first = $this->request(User::factory()->create(), $listing);
        $second = $this->request(User::factory()->create(), $listing);
        $second->update(['start_date' => $first->end_date, 'end_date' => $first->end_date->copy()->addDays(2)]);

        Livewire::actingAs($owner)->test(Approvals::class)->call('approve', $first->id);
        RentalAgreement::accept($first, $owner);
        RentalAgreement::accept($first, $first->renter);

        $this->assertSame('rejected', $second->fresh()->status->value);
        $this->assertSame(1, Rental::count());
    }

    public function test_fr11_approval_rechecks_current_fulfillment_and_duration_limits(): void
    {
        foreach ([['pickup_available' => false], ['max_rental_duration_days' => 2]] as $changedOptions) {
            $owner = User::factory()->create();
            $listing = $this->listing($owner);
            $request = $this->request(User::factory()->create(), $listing);
            $listing->update($changedOptions);

            Livewire::actingAs($owner)->test(Approvals::class)
                ->call('approve', $request->id)->assertHasErrors('approve');

            $this->assertSame('requested', $request->fresh()->status->value);
            $this->assertDatabaseMissing('rentals', ['rental_request_id' => $request->id]);
        }
    }

    public function test_fr14_open_form_requires_accepting_the_new_price_after_a_listing_change(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $listing = $this->listing($owner);
        $form = Livewire::actingAs($renter)->test(Requests::class, ['listing' => $listing])
            ->set('start_date', today()->addDays(3)->toDateString())
            ->set('end_date', today()->addDays(5)->toDateString())->set('accept_terms', true);
        $listing->update(['price_per_day' => 200]);

        $form->call('submit')->assertHasErrors('accept_terms');
        $this->assertDatabaseCount('rental_requests', 0);
        $this->assertFalse($form->get('accept_terms'));
        $form->set('accept_terms', true)->call('submit')->assertHasNoErrors();
        $this->assertEquals(600, RentalRequest::firstOrFail()->rental_fee);
    }

    public function test_fr22_condition_first_damage_cannot_be_settled_before_return_closure(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $rental = $this->booking($owner, $renter, 'active');
        SecurityDeposit::create(['rental_id' => $rental->id, 'amount' => 500]);
        Livewire::actingAs($owner)->test(Show::class, ['rental' => $rental])
            ->set('after_has_damage', true)->set('damage_type', 'Crack')
            ->set('damage_description', 'New damage on return.')->set('damage_estimated_cost', 100)
            ->call('recordAfterCondition')->assertHasNoErrors();

        Livewire::actingAs($renter)->test(RenterRental::class, ['rental' => $rental->fresh()])
            ->assertDontSee('Accept claim')->call('acceptDamageClaim')->assertForbidden();
        RentalLifecycle::confirmReturn($rental->fresh(), $owner);
        Livewire::actingAs($renter)->test(RenterRental::class, ['rental' => $rental->fresh()])
            ->call('acceptDamageClaim')->assertHasNoErrors();
        $this->assertSame('completed', $rental->fresh()->status->value);
        $this->assertSame('accepted', $rental->fresh()->damageReport->status->value);
        $this->assertSame('deducted', $rental->fresh()->securityDeposit->status->value);
    }

    public function test_fr22_overdue_job_cannot_reopen_a_rental_completed_after_it_was_loaded(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $rental = $this->booking($owner, $renter, 'active');
        $rental->update(['end_date' => today()->subDays(2)]);
        SecurityDeposit::create(['rental_id' => $rental->id, 'amount' => 500]);
        Livewire::actingAs($owner)->test(Show::class, ['rental' => $rental])
            ->call('recordAfterCondition')->assertHasNoErrors();
        $interleaved = false;
        Rental::retrieved(function (Rental $loaded) use ($rental, $owner, &$interleaved) {
            if ($loaded->id === $rental->id && ! $interleaved) {
                $interleaved = true;
                RentalLifecycle::confirmReturn($loaded, $owner);
            }
        });

        try {
            $this->assertSame(0, RentalLifecycle::markOverdueRentals());
        } finally {
            app('events')->forget('eloquent.retrieved: '.Rental::class);
        }
        $this->assertTrue($interleaved);
        $this->assertSame('completed', $rental->fresh()->status->value);
        $this->assertEquals(200, $rental->fresh()->late_fee);
    }

    public function test_fr22_completion_preserves_an_already_settled_legacy_damage_claim(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $rental = $this->booking($owner, $renter, 'active');
        SecurityDeposit::create(['rental_id' => $rental->id, 'amount' => 500]);
        Livewire::actingAs($owner)->test(Show::class, ['rental' => $rental])
            ->set('after_has_damage', true)->set('damage_type', 'Crack')
            ->set('damage_description', 'Legacy condition-first claim.')->set('damage_estimated_cost', 100)
            ->call('recordAfterCondition')->assertHasNoErrors();
        RentalLifecycle::acceptDamageClaim($rental->fresh()->damageReport);
        Notification::fake();

        RentalLifecycle::confirmReturn($rental->fresh(), $owner);

        $this->assertSame('completed', $rental->fresh()->status->value);
        $this->assertSame('deducted', $rental->fresh()->securityDeposit->status->value);
        $this->assertEquals(100, $rental->fresh()->securityDeposit->deducted_amount);
        $isClaimPrompt = fn (TalaNotification $notice) => $notice->type === NotificationType::DamageClaimFiled->value;
        Notification::assertNotSentTo($renter, TalaNotification::class, $isClaimPrompt);
    }

    public function test_fr34_expired_pending_request_can_be_cancelled_and_does_not_trap_account_closure(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $request = $this->request($renter, $this->listing($owner));
        $request->update(['start_date' => today()->subDays(4), 'end_date' => today()->subDays(2)]);

        Livewire::actingAs($renter)->test(Index::class)
            ->call('cancel', $request->id)->assertHasNoErrors();
        $this->assertSame('cancelled', $request->fresh()->status->value);
        $renter->closeAccount();
        $this->assertSoftDeleted('users', ['id' => $renter->id]);
        $this->assertDatabaseHas('rental_requests', ['id' => $request->id]);
    }

    public function test_fr42_future_paid_reservation_is_visible_without_blocking_today(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $rental = $this->booking($owner, $renter);
        $this->completeOfflinePayment($rental);

        $this->assertSame('Available', $rental->listing->availabilityLabel());
        $this->assertSame('Reserved', $rental->listing->availabilityLabel($rental->start_date));
        $this->get(route('listings.show', $rental->listing))->assertOk()->assertSee('Reserved dates');
    }

    public function test_fr36_pending_request_cancellation_notifies_the_owner_and_renter(): void
    {
        Notification::fake();
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $request = $this->request($renter, $this->listing($owner));

        Livewire::actingAs($renter)->test(Index::class)->call('cancel', $request->id);

        $this->assertSame('cancelled', $request->fresh()->status->value);
        $isCancellation = fn (TalaNotification $notice) => $notice->type === NotificationType::RentalCancelled->value;
        Notification::assertSentTo($owner, TalaNotification::class, $isCancellation);
        Notification::assertSentTo($renter, TalaNotification::class, $isCancellation);
    }

    public function test_fr34_request_page_cannot_cancel_a_booking_after_either_handover_confirmation(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $rental = $this->booking($owner, $renter, 'paid');
        RentalLifecycle::confirmPickup($rental, $owner);

        Livewire::actingAs($renter)->test(Index::class)
            ->call('cancel', $rental->rental_request_id)->assertForbidden();

        $this->assertSame('paid', $rental->fresh()->status->value);
        $this->assertSame('approved', $rental->rentalRequest->fresh()->status->value);
    }

    public function test_fr36_cancelling_a_paid_booking_releases_dates_and_notifies_both_parties(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $rental = $this->booking($owner, $renter);
        $this->completeOfflinePayment($rental);
        Notification::fake();

        Livewire::actingAs($renter)->test(RenterRental::class, ['rental' => $rental])
            ->set('cancellation_reason', 'Plans changed')->call('cancelRental')->assertHasNoErrors();

        $this->assertSame('cancelled', $rental->fresh()->status->value);
        $this->assertSame('cancelled', $rental->rentalRequest->fresh()->status->value);
        $this->assertFalse($rental->listing->hasApprovedOverlap($rental->start_date, $rental->end_date));
        $this->assertSame('Available', $rental->listing->availabilityLabel($rental->start_date));
        $this->assertSame('refunded', $rental->fresh()->securityDeposit->status->value);
        $isCancellation = fn (TalaNotification $notice) => $notice->type === NotificationType::RentalCancelled->value;
        Notification::assertSentTo($owner, TalaNotification::class, $isCancellation);
        Notification::assertSentTo($renter, TalaNotification::class, $isCancellation);
    }

    public function test_fr19_overdue_details_show_current_days_and_fees_before_the_daily_job(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $rental = $this->booking($owner, $renter, 'active');
        $rental->update(['end_date' => today()->subDays(2)->toDateString()]);

        $this->actingAs($renter)->get(route('renter.rentals.show', $rental))
            ->assertOk()->assertSee('overdue by 2 days')->assertSee('200.00');
        $this->actingAs($owner)->get(route('owner.rentals.show', $rental))
            ->assertOk()->assertSee('overdue by 2 days')->assertSee('200.00');
    }

    public function test_fr19_admin_overdue_filter_and_dashboard_use_the_current_status(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $rental = $this->booking($owner, $renter, 'active');
        $rental->update(['end_date' => today()->subDay()]);

        $page = Livewire::actingAs($admin)->test(\App\Livewire\Admin\Rentals\Index::class)->set('status', 'overdue');
        $this->assertSame(1, $page->viewData('rentals')->total());
        $page->set('status', 'active');
        $this->assertSame(0, $page->viewData('rentals')->total());
        $dashboard = Livewire::actingAs($admin)->test(Dashboard::class);
        $this->assertSame(1, $dashboard->viewData('overdueRentalsCount'));
        $this->assertSame(0, $dashboard->viewData('activeRentalsCount'));
    }

    public function test_fr17_owner_receipt_includes_the_payment_reference(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $rental = $this->booking($owner, $renter);
        $this->completeOfflinePayment($rental);

        $this->actingAs($owner)->get(route('owner.rentals.show', $rental))
            ->assertOk()->assertSee($rental->fresh()->payment->transaction_reference);
    }

    public function test_fr42_paid_booking_displays_reserved_status(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $rental = $this->booking($owner, $renter);
        $dates = ['start_date' => now()->toDateString(), 'end_date' => now()->addDays(2)->toDateString()];
        $rental->update($dates);
        $rental->rentalRequest->update($dates);
        $this->completeOfflinePayment($rental);

        $this->get(route('listings.show', $rental->listing))->assertOk()->assertSee('Reserved');
    }

    public function test_fr34_cancellation_is_blocked_after_owner_marks_the_item_handed_over(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $rental = $this->booking($owner, $renter, 'paid');
        RentalLifecycle::confirmPickup($rental, $owner);

        Livewire::actingAs($renter)->test(RenterRental::class, ['rental' => $rental->fresh()])
            ->set('cancellation_reason', 'Already received the physical item.')
            ->call('cancelRental')->assertForbidden();
    }

    public function test_fr45_closed_rental_prompts_both_parties_on_their_rental_pages(): void
    {
        Notification::fake();
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $rental = $this->booking($owner, $renter, 'returned');
        SecurityDeposit::create(['rental_id' => $rental->id, 'amount' => $rental->security_deposit]);
        RentalLifecycle::completeInspection($rental);

        $isPrompt = fn (TalaNotification $notification) => $notification->type === NotificationType::ReviewRequest->value;
        Notification::assertSentTo($renter, TalaNotification::class, $isPrompt);
        $this->actingAs($owner)->get(route('owner.rentals.show', $rental))->assertOk()->assertSee('Rate the renter');
        $this->actingAs($renter)->get(route('renter.rentals.show', $rental))->assertOk()->assertSee('Rate the owner')->assertSee('Rate the item');
    }

    public function test_fr24_deleting_one_account_preserves_the_other_partys_transaction_history(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $rental = $this->booking($owner, $renter, 'completed');
        $rentalId = $rental->id;
        $this->actingAs($renter);

        Volt::test('profile.delete-user-form')->set('password', 'password')->call('deleteUser')->assertHasNoErrors();

        $this->assertDatabaseHas('rentals', ['id' => $rentalId, 'owner_id' => $owner->id]);
    }

    public function test_fr19_past_due_active_rental_displays_overdue_without_waiting_for_the_daily_job(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $rental = $this->booking($owner, $renter, 'active');
        $rental->update(['end_date' => now()->subDay()->toDateString()]);

        $this->assertSame('Overdue', $rental->fresh()->displayStatusLabel());
    }

    public function test_fr24_removing_a_listing_does_not_break_the_renters_history_page(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $rental = $this->booking($owner, $renter, 'completed');
        Livewire::actingAs($owner)->test(\App\Livewire\Owner\Listings\Index::class)
            ->call('delete', $rental->listing_id);

        try {
            $response = $this->actingAs($renter)->get(route('renter.rentals.index'));
        } catch (\Throwable $exception) {
            $this->fail('Rental history failed after listing removal: '.$exception->getMessage());
        }
        $response->assertOk();
    }

    public function test_fr51_manila_location_sorts_markers_nearest_first(): void
    {
        $owner = User::factory()->create();
        $this->listing($owner, ['name' => 'Tagaytay', 'latitude' => 14.0997, 'longitude' => 120.9425]);
        $this->listing($owner, ['name' => 'Manila']);

        $markers = Livewire::test(Map::class)->set('centerLat', 14.5995)->set('centerLng', 120.9842)->get('markers');
        $this->assertSame(['Manila', 'Tagaytay'], array_column($markers, 'name'));
        $this->assertEqualsWithDelta(0, $markers[0]['distanceKm'], .001);
        $this->assertGreaterThan(50, $markers[1]['distanceKm']);
    }

    public function test_nfr20_no_location_ignores_radius_and_allows_browsing(): void
    {
        $owner = User::factory()->create();
        $this->listing($owner);
        $this->listing($owner, ['name' => 'Tagaytay', 'latitude' => 14.0997]);
        $markers = Livewire::test(Map::class)->set('radiusKm', 5)->get('markers');

        $this->assertCount(2, $markers);
        $this->assertSame([null, null], array_column($markers, 'distanceKm'));
    }

    public function test_fr52_map_and_browse_use_the_same_keyword_matches(): void
    {
        $owner = User::factory()->create();
        $this->listing($owner, ['name' => 'Found camera']);
        $this->listing($owner, ['name' => 'Ladder', 'description' => 'No match.']);
        $markers = Livewire::test(Map::class)->set('keyword', 'camera')->get('markers');

        $this->assertCount(1, $markers);
        $this->assertSame('Found camera', $markers[0]['name']);
        Livewire::test(Browse::class)->set('keyword', 'camera')
            ->assertSee('Found camera')->assertDontSee('Ladder');
    }

    public function test_fr06_owner_can_upload_multiple_listing_photos(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create();
        $category = Category::create(['name' => 'Photo tools', 'slug' => 'photo-tools']);
        Livewire::actingAs($owner)->test(Form::class)
            ->set('category_id', $category->id)->set('name', 'Photo audit listing')
            ->set('description', 'Two uploaded images.')->set('price_per_day', 100)
            ->set('location', 'Manila')->set('latitude', 14.5995)->set('longitude', 120.9842)
            ->set('available_from', today()->toDateString())->set('available_until', today()->addMonth()->toDateString())
            ->set('photos', [
                UploadedFile::fake()->image('first.png'),
                UploadedFile::fake()->image('second.png'),
            ])->call('save')->assertHasNoErrors();

        $listing = Listing::where('name', 'Photo audit listing')->firstOrFail();
        $this->assertCount(2, $listing->images);
        foreach ($listing->images as $image) {
            Storage::disk('public')->assertExists($image->path);
        }
    }
}
