<?php

namespace Tests\Feature;

use App\Livewire\Admin\Payments\Settings;
use App\Livewire\Listings\Browse;
use App\Livewire\Owner\RentalRequests\Index;
use App\Livewire\Owner\Rentals\Show;
use App\Livewire\RentalRequests\Create;
use App\Models\Category;
use App\Models\CommissionSetting;
use App\Models\Listing;
use App\Models\OfflinePaymentSetting;
use App\Models\PaymentSubmission;
use App\Models\Rental;
use App\Models\RentalRequest;
use App\Models\RentalWaitlistEntry;
use App\Models\User;
use App\Services\RentalAgreement;
use App\Services\RentalCart;
use App\Services\RentalLifecycle;
use App\Services\RentalPayments;
use App\Services\RentalRequestSubmission;
use App\Services\RentalWaitlist;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\CreatesPaymentProof;
use Tests\TestCase;

class RentalBookingRulesTest extends TestCase
{
    use CreatesPaymentProof;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 11, 1)->startOfDay());
    }

    public function test_hourly_quote_uses_hours_and_philippine_time(): void
    {
        $listing = $this->listing();
        $quote = RentalRequestSubmission::preview(User::factory()->identityVerified()->create(), $listing->id, $this->hours());
        $this->assertSame(3, $quote['attributes']['rental_hours']);
        $this->assertSame(150.0, $quote['attributes']['rental_fee']);
        $this->assertSame(15.0, $quote['attributes']['commission_amount']);
        $this->assertSame('2026-11-03 01:00:00', $quote['attributes']['starts_at']);
        $this->assertSame('2026-11-03 04:00:00', $quote['attributes']['ends_at']);
    }

    public function test_owner_maximum_days_is_enforced_instead_of_obsolete_hour_limits(): void
    {
        $listing = $this->listing(['max_rental_duration_hours' => 2]);
        $quote = RentalRequestSubmission::preview(User::factory()->identityVerified()->create(), $listing->id, $this->hours());
        $this->assertSame(3, $quote['attributes']['rental_hours']);
    }

    public function test_fractional_hours_and_times_outside_owner_hours_are_rejected(): void
    {
        $listing = $this->listing();
        foreach ([['end_time' => '12:30'], ['start_time' => '07:00']] as $change) {
            try {
                RentalRequestSubmission::preview(User::factory()->identityVerified()->create(), $listing->id, $this->hours($change));
                $this->fail('Invalid hourly period was accepted.');
            } catch (ValidationException $exception) {
                $this->assertNotEmpty($exception->errors());
            }
        }
    }

    public function test_same_date_hourly_booking_is_blocked_even_when_the_times_are_adjacent(): void
    {
        $listing = $this->listing();
        $this->booking($listing, $this->hours());
        $renter = User::factory()->identityVerified()->create();
        $this->expectException(ValidationException::class);
        RentalRequestSubmission::preview($renter, $listing->id, $this->hours(['start_time' => '12:00', 'end_time' => '14:00']));
    }

    public function test_hourly_overdue_fee_is_charged_per_started_hour(): void
    {
        $rental = $this->booking($this->listing(), $this->hours());
        $rental->update(['status' => 'active']);
        $this->travelTo(now()->setDate(2026, 11, 3)->setTime(5, 1));
        $this->assertTrue($rental->isOverdue());
        $this->assertSame(100.0, $rental->currentLateFee());
    }

    public function test_new_agreement_has_one_processing_fee_and_twenty_four_hour_policy(): void
    {
        $rental = $this->booking($this->listing(), $this->days());
        $terms = $rental->rentalRequest->agreement_terms;
        $this->assertSame(24, $terms['cancellation_window_hours']);
        $this->assertTrue($terms['cancellation_requires_review']);
        $this->assertSame(750.0, (float) $rental->total_amount);
    }

    public function test_packed_cancellation_is_reviewed_without_releasing_dates_early(): void
    {
        $rental = $this->booking($this->listing(), $this->days());
        $rental->update(['status' => 'paid', 'paid_at' => now()]);
        RentalLifecycle::advanceFulfillment($rental, $rental->owner, 'packed');
        $this->actingAs($rental->renter);
        RentalLifecycle::cancel($rental, 'Plans changed');
        $this->assertSame('paid', $rental->fresh()->status->value);
        $this->assertSame('pending', $rental->fresh()->cancellationRequest->status);
        $this->assertSame(100.0, (float) $rental->fresh()->cancellationRequest->fee);
        RentalLifecycle::reviewCancellation($rental, $rental->owner, true, 'Agreed');
        $this->assertTrue($rental->fresh()->isCancelled());
        $this->assertSame(100.0, (float) $rental->fresh()->cancellation_fee);
    }

    public function test_waiting_for_courier_blocks_cancellation(): void
    {
        $rental = $this->booking($this->listing(), $this->days(['fulfillment_method' => 'delivery']));
        $rental->update(['status' => 'paid', 'paid_at' => now()]);
        RentalLifecycle::advanceFulfillment($rental, $rental->owner, 'packed');
        RentalLifecycle::advanceFulfillment($rental, $rental->owner, 'waiting_for_courier');
        $this->assertFalse($rental->fresh()->isCancellableByRenter());
    }

    public function test_hourly_cart_keeps_times_and_uses_the_same_quote_at_checkout(): void
    {
        $listing = $this->listing();
        $renter = User::factory()->identityVerified()->create();
        $this->actingAs($renter);
        $item = RentalCart::save($renter, $listing->id, $this->hours());
        $this->assertSame(150.0, $item->rentalFee());
        $ids = RentalCart::checkout($renter, [$item->id], true);
        $request = RentalRequest::findOrFail($ids[0]);
        $this->assertSame('hour', $request->rental_unit);
        $this->assertSame('2026-11-03 01:00:00', $request->starts_at->toDateTimeString());
    }

    public function test_daily_booking_blocks_hourly_period_on_the_same_dates(): void
    {
        $listing = $this->listing();
        $this->booking($listing, $this->days());
        $this->expectException(ValidationException::class);
        RentalRequestSubmission::preview(User::factory()->identityVerified()->create(), $listing->id, $this->hours());
    }

    public function test_pending_cancellation_freezes_fulfillment_and_rejection_keeps_the_booking(): void
    {
        $rental = $this->booking($this->listing(), $this->days(['fulfillment_method' => 'delivery']));
        $rental->update(['status' => 'paid']);
        RentalLifecycle::advanceFulfillment($rental, $rental->owner, 'packed');
        $this->actingAs($rental->renter);
        RentalLifecycle::cancel($rental, 'Cannot attend');
        try {
            RentalLifecycle::advanceFulfillment($rental, $rental->owner, 'waiting_for_courier');
            $this->fail('Pending cancellation did not block fulfillment.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('fulfillment', $exception->errors());
        }
        RentalLifecycle::reviewCancellation($rental, $rental->owner, false, 'Please contact support');
        $this->assertSame('rejected', $rental->fresh()->cancellationRequest->status);
        $this->assertTrue($rental->fresh()->isPaid());
    }

    public function test_fee_settings_change_future_agreements_only(): void
    {
        $rental = $this->booking($this->listing(), $this->days());
        $admin = User::factory()->create(['role' => 'admin']);
        Livewire::actingAs($admin)->test(Settings::class)
            ->set('commission_rate', 7)->set('cancellation_fee_percentage', 15)->set('cancellation_window_hours', 12)
            ->call('saveFees')->assertHasNoErrors();
        $this->assertSame(7.0, (float) CommissionSetting::current()->commission_rate);
        $this->assertSame(24, $rental->rentalRequest->agreement_terms['cancellation_window_hours']);
        $this->assertSame(10.0, (float) $rental->commission_rate);
    }

    public function test_waitlist_is_idempotent_and_notifies_once_only_when_the_dates_are_free(): void
    {
        $listing = $this->listing();
        $booking = $this->booking($listing, $this->days());
        $renter = User::factory()->identityVerified()->create();
        $entry = RentalWaitlist::join($renter, $listing->id, $this->days());
        RentalWaitlist::join($renter, $listing->id, $this->days());
        $this->assertSame(1, RentalWaitlistEntry::count());
        $this->assertSame(0, RentalWaitlist::notifyAvailable());
        $this->actingAs($booking->renter);
        RentalLifecycle::cancel($booking, 'Changed plans');
        RentalWaitlist::notifyAvailable();
        $this->assertNotNull($entry->fresh()->notified_at);
        $this->assertSame(0, RentalWaitlist::notifyAvailable());
        $this->assertSame(1, $renter->notifications()->where('data->title', 'Your waitlisted dates are available')->count());
    }

    public function test_waitlist_requires_identity_and_cannot_be_used_for_an_available_period(): void
    {
        $listing = $this->listing();
        $this->expectException(ValidationException::class);
        RentalWaitlist::join(User::factory()->identityVerified()->create(), $listing->id, $this->days());
    }

    public function test_expired_unpaid_reservation_releases_dates_without_a_fee(): void
    {
        $rental = $this->booking($this->listing(), $this->days());
        $this->assertNotNull($rental->payment_due_at);
        $this->travel(25)->hours();
        $this->assertSame(1, RentalPayments::expireReservations());
        $this->assertTrue($rental->fresh()->isCancelled());
        $this->assertSame(0.0, (float) $rental->fresh()->cancellation_fee);
        $this->assertFalse($rental->listing->hasApprovedOverlap($rental->start_date, $rental->end_date));
    }

    public function test_pending_proof_pauses_expiry_but_rejection_does_not_hold_dates_forever(): void
    {
        $rental = $this->booking($this->listing(), $this->days());
        $submission = PaymentSubmission::create(['rental_id' => $rental->id, 'submitted_by' => $rental->renter_id,
            'method' => 'gcash', 'instructions' => 'Pay owner', 'external_reference' => 'TEST-EXPIRY', 'amount' => $rental->total_amount,
            'proof_path' => 'test-proof.png', 'status' => 'pending']);
        $this->travel(25)->hours();
        $this->assertSame(0, RentalPayments::expireReservations());
        $submission->update(['status' => 'rejected']);
        $this->assertSame(1, RentalPayments::expireReservations());
    }

    public function test_hourly_request_screen_and_checkout_preserve_the_displayed_period(): void
    {
        $listing = $this->listing();
        $renter = User::factory()->identityVerified()->create();
        Livewire::actingAs($renter)->test(Create::class, ['listing' => $listing])
            ->set('rental_unit', 'hour')->set('start_date', '2026-11-03')->set('end_date', '2026-11-03')
            ->set('start_time', '09:00')->set('end_time', '12:00')->set('accept_terms', true)
            ->assertSee('Philippine time')->call('submit')->assertHasNoErrors();
        $request = RentalRequest::where('renter_id', $renter->id)->firstOrFail();
        $this->assertSame(3, $request->rental_hours);
        $this->assertSame(150.0, (float) $request->rental_fee);
    }

    public function test_unavailable_dates_can_be_saved_as_a_cart_draft_but_not_checked_out(): void
    {
        $listing = $this->listing();
        $this->booking($listing, $this->days());
        $renter = User::factory()->identityVerified()->create();
        $this->actingAs($renter);
        $item = RentalCart::save($renter, $listing->id, $this->days());
        $this->assertNotNull($item->id);
        $this->expectException(ValidationException::class);
        RentalCart::checkout($renter, [$item->id], true);
    }

    public function test_a_renter_cannot_approve_their_own_cancellation(): void
    {
        $rental = $this->booking($this->listing(), $this->days());
        $rental->update(['status' => 'paid']);
        RentalLifecycle::advanceFulfillment($rental, $rental->owner, 'packed');
        $this->actingAs($rental->renter);
        RentalLifecycle::cancel($rental, 'Cannot attend');
        $this->expectException(HttpException::class);
        RentalLifecycle::reviewCancellation($rental, $rental->renter, true, 'Approve my request');
    }

    public function test_owner_approval_protects_the_date_from_an_adjacent_hourly_request(): void
    {
        $listing = $this->listing();
        $first = RentalRequestSubmission::submit(User::factory()->identityVerified()->create(), $listing->id, $this->hours(), RentalRequestSubmission::termsFor($listing), true);
        $second = RentalRequestSubmission::submit(User::factory()->identityVerified()->create(), $listing->id, $this->hours(['start_time' => '12:00', 'end_time' => '14:00']), RentalRequestSubmission::termsFor($listing), true);
        Livewire::actingAs($listing->owner)->test(Index::class)
            ->call('approve', $first->id)->assertHasNoErrors();
        $this->assertTrue($second->fresh()->isPending());
        Livewire::actingAs($listing->owner)->test(Index::class)
            ->call('approve', $second->id)->assertHasErrors('approve');
        $this->assertTrue($second->fresh()->isPending());
    }

    public function test_owner_can_review_cancellation_from_the_booking_screen(): void
    {
        $rental = $this->booking($this->listing(), $this->hours());
        $rental->update(['status' => 'paid', 'paid_at' => now()]);
        RentalLifecycle::advanceFulfillment($rental, $rental->owner, 'packed');
        $this->actingAs($rental->renter);
        RentalLifecycle::cancel($rental, 'Cannot attend');
        Livewire::actingAs($rental->owner)->test(Show::class, ['rental' => $rental->fresh()])
            ->assertSee('3 hours')->assertSee('Approve cancellation')
            ->set('cancellation_review_notes', 'Approved before shipping')->call('reviewCancellation', true)->assertHasNoErrors()
            ->assertSee('Refund to arrange');
        $this->assertSame(335.0, (float) $rental->fresh()->cancellation_refund_amount);
    }

    public function test_a_quote_cannot_overflow_the_booking_amount_columns(): void
    {
        $listing = $this->listing(['security_deposit' => 99999999.99]);
        $this->expectException(ValidationException::class);
        RentalRequestSubmission::preview(User::factory()->identityVerified()->create(), $listing->id, $this->days());
    }

    public function test_marketplace_hour_filter_uses_hourly_prices_and_includes_hour_only_items(): void
    {
        $listing = $this->listing(['allows_daily_rentals' => false]);
        Livewire::test(Browse::class)->set('rentalUnit', 'hour')->set('maxPrice', 60)
            ->assertViewHas('listings', fn ($items) => $items->pluck('id')->contains($listing->id))
            ->assertSee('/hour');
    }

    public function test_an_overnight_hourly_rental_uses_elapsed_duration_for_the_day_limit(): void
    {
        $listing = $this->listing(['max_rental_duration_days' => 1, 'available_time_from' => '00:00', 'available_time_until' => '23:59']);
        $quote = RentalRequestSubmission::preview(User::factory()->identityVerified()->create(), $listing->id,
            $this->hours(['start_time' => '22:00', 'end_time' => '02:00', 'end_date' => '2026-11-04']));
        $this->assertSame(4, $quote['attributes']['rental_hours']);
        $this->assertSame(1, $quote['attributes']['rental_days']);
        $this->assertSame(200.0, $quote['attributes']['rental_fee']);
    }

    public function test_hourly_agreement_states_the_actual_late_fee_calculation(): void
    {
        $rental = $this->booking($this->listing(), $this->hours());
        $clause = collect($rental->rentalRequest->agreement_terms['clauses'])->firstWhere('title', 'Return, overdue charges and changes to dates');
        $this->assertNotNull($clause);
        $this->assertStringContainsString('each started overdue hour', implode(' ', $clause['paragraphs']));
        $this->assertStringNotContainsString('UTC calendar', implode(' ', $clause['paragraphs']));
    }

    public function test_hourly_midnight_end_can_touch_a_daily_reservation_in_both_booking_orders(): void
    {
        foreach ([true, false] as $dailyFirst) {
            $listing = $this->listing(['available_time_from' => '00:00', 'available_time_until' => '23:59']);
            $daily = $this->days(['start_date' => '2026-11-04', 'end_date' => '2026-11-04']);
            $hourly = $this->hours(['start_time' => '22:00', 'end_time' => '00:00', 'end_date' => '2026-11-04']);
            $this->booking($listing, $dailyFirst ? $daily : $hourly);
            $quote = RentalRequestSubmission::preview(User::factory()->identityVerified()->create(), $listing->id, $dailyFirst ? $hourly : $daily);
            $this->assertSame($dailyFirst ? 100.0 : 500.0, $quote['attributes']['rental_fee']);
        }
    }

    public function test_legacy_agreement_keeps_cancellation_before_handover_at_courier_stage(): void
    {
        $rental = $this->booking($this->listing(), $this->days(['fulfillment_method' => 'delivery']));
        $terms = $rental->rentalRequest->agreement_terms;
        unset($terms['cancellation_requires_review']);
        $terms['cancellation_window_hours'] = 48;
        $rental->rentalRequest->update(['agreement_terms' => $terms]);
        $rental->update(['status' => 'paid', 'fulfillment_stage' => 'waiting_for_courier']);
        $rental = $rental->fresh();
        $this->assertTrue($rental->isCancellableByRenter());
        $this->actingAs($rental->renter);
        RentalLifecycle::cancel($rental, 'Legacy cancellation');
        $this->assertTrue($rental->fresh()->isCancelled());
    }

    public function test_deadline_crossing_during_submission_cannot_attach_proof_to_a_cancelled_booking(): void
    {
        Storage::fake('local');
        $rental = $this->booking($this->listing(), $this->days());
        OfflinePaymentSetting::create(['id' => 1, 'method' => 'bank_transfer', 'instructions' => 'Pay the test recipient', 'enabled' => true]);
        RentalPayments::destinationFor($rental, $rental->renter);
        $this->travelTo($rental->payment_due_at->copy()->subSecond());
        Gate::after(function ($user, $ability, $result) use ($rental) {
            if ($ability === 'pay' && $result === true) {
                $this->travelTo($rental->payment_due_at->copy()->addSecond());
            }
        });
        try {
            RentalPayments::submit($rental, $rental->renter, 'BOUNDARY-PAYMENT', $this->paymentProof());
        } catch (AuthorizationException) {
        }
        $this->assertDatabaseCount('payment_submissions', 0);
        $this->assertDatabaseCount('payments', 0);
    }

    private function listing(array $overrides = []): Listing
    {
        return Listing::create($overrides + [
            'owner_id' => User::factory()->identityVerified()->create()->id,
            'category_id' => Category::firstOrCreate(['slug' => 'booking-rules'], ['name' => 'Tools'])->id,
            'name' => 'Camera', 'description' => 'Camera', 'condition' => 'good', 'location' => 'Manila',
            'price_per_day' => 500, 'price_per_hour' => 50, 'security_deposit' => 200,
            'min_rental_duration_hours' => 1, 'max_rental_duration_hours' => 8,
            'available_time_from' => '08:00', 'available_time_until' => '18:00',
            'max_rental_duration_days' => 7, 'status' => 'published', 'is_available' => true,
            'pickup_available' => true, 'delivery_available' => true,
        ]);
    }

    private function hours(array $overrides = []): array
    {
        return $overrides + $this->days() + ['rental_unit' => 'hour', 'start_time' => '09:00', 'end_time' => '12:00'];
    }

    private function days(array $overrides = []): array
    {
        return $overrides + ['start_date' => '2026-11-03', 'end_date' => '2026-11-03', 'fulfillment_method' => 'pickup'];
    }

    private function booking(Listing $listing, array $details): Rental
    {
        $renter = User::factory()->identityVerified()->create();
        $request = RentalRequestSubmission::submit($renter, $listing->id, $details, RentalRequestSubmission::termsFor($listing), true);
        $request->update(['status' => 'approved', 'agreement_terms' => RentalAgreement::termsFor($request)]);
        RentalAgreement::accept($request, $listing->owner);

        return RentalAgreement::accept($request, $renter);
    }
}
