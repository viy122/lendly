<?php

namespace Tests\Feature;

use App\Livewire\Listings\Show;
use App\Livewire\Owner\Listings\Index;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Rental;
use App\Models\RentalRequest;
use App\Models\User;
use App\Services\ListingAvailability;
use App\Services\RentalAgreement;
use App\Services\RentalCart;
use App\Services\RentalLifecycle;
use App\Services\RentalPeriod;
use App\Services\RentalRequestSubmission;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class PhaseOneAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-01 17:00', 'Asia/Manila'));
    }

    public function test_same_day_hourly_request_and_cart_use_future_philippine_times_and_hourly_rate(): void
    {
        $listing = $this->listing();
        $renter = User::factory()->identityVerified()->create();
        $quote = RentalRequestSubmission::preview($renter, $listing->id, $this->period());
        $this->assertSame('2026-11-01 10:00:00', $quote['attributes']['starts_at']);
        $this->assertSame(100.0, $quote['attributes']['rental_fee']);
        $this->assertSame(2, $quote['attributes']['rental_hours']);
        $this->actingAs($renter);
        $cart = RentalCart::save($renter, $listing->id, $this->period());
        $this->assertSame(100.0, $cart->rentalFee());
    }

    public function test_daily_rental_keeps_calendar_day_billing_and_records_selected_times(): void
    {
        $attributes = RentalPeriod::attributes($this->listing(), $this->period(['rental_unit' => 'day']));
        $this->assertSame(1, $attributes['rental_days']);
        $this->assertNull($attributes['rental_hours']);
        $this->assertSame('2026-11-01 12:00:00', $attributes['ends_at']);
    }

    public function test_past_or_equal_start_is_rejected_even_when_today_is_within_owner_window(): void
    {
        foreach (['16:00', '17:00'] as $time) {
            $this->rejects(fn () => RentalPeriod::attributes($this->listing(), $this->period(['start_time' => $time])));
        }
    }

    public function test_maximum_days_remains_a_cap_and_obsolete_hour_limits_do_not_restrict_a_short_rental(): void
    {
        $listing = $this->listing(['min_rental_duration_hours' => 8, 'max_rental_duration_hours' => 1]);
        $this->assertSame(2, RentalPeriod::attributes($listing, $this->period())['rental_hours']);
        $this->rejects(fn () => RentalPeriod::attributes($listing, $this->period(['end_date' => '2026-11-03'])));
        $this->rejects(fn () => RentalPeriod::attributes($listing, $this->period(['rental_unit' => 'day', 'end_date' => '2026-11-02'])));
    }

    public function test_adjacent_times_are_blocked_on_the_same_date_until_the_prior_commitment_is_released(): void
    {
        $listing = $this->listing();
        $this->booking($listing);
        $this->rejects(fn () => RentalRequestSubmission::preview(User::factory()->identityVerified()->create(), $listing->id, $this->period(['start_time' => '20:00', 'end_time' => '22:00'])));
    }

    public function test_return_inspection_and_explicit_reopening_allow_another_rental_today_without_rewriting_history(): void
    {
        $listing = $this->listing();
        $rental = $this->booking($listing);
        $rental->update(['status' => 'active']);
        $this->travelTo(Carbon::parse('2026-11-01 22:00', 'Asia/Manila'));
        $this->assertFalse($listing->fresh()->acceptsNewRequests());
        $this->rejects(fn () => ListingAvailability::set($listing, true));
        $this->actingAs($listing->owner);
        RentalLifecycle::confirmReturn($rental, $listing->owner);
        $this->rejects(fn () => ListingAvailability::set($listing, true));
        RentalLifecycle::completeInspection($rental->fresh());
        $this->assertTrue($listing->fresh()->reopen_required);
        $this->assertFalse($listing->fresh()->acceptsNewRequests());
        $page = Livewire::test(Show::class, ['listing' => $listing->fresh()])->assertViewHas('requestsOpen', false);
        $saved = $rental->fresh()->only(['starts_at', 'ends_at', 'rental_fee', 'total_amount']);
        Livewire::actingAs($listing->owner)->test(Index::class)->call('setAvailability', $listing->id, true)->assertHasNoErrors();
        $this->assertFalse($listing->fresh()->reopen_required);
        $page->call('$refresh')->assertViewHas('requestsOpen', true);
        $quote = RentalRequestSubmission::preview(User::factory()->identityVerified()->create(), $listing->id, $this->period(['start_time' => '22:30', 'end_time' => '23:30']));
        $this->assertSame(50.0, $quote['attributes']['rental_fee']);
        $this->assertEquals($saved, $rental->fresh()->only(['starts_at', 'ends_at', 'rental_fee', 'total_amount']));
    }

    public function test_reopening_keeps_other_future_commitments_blocked(): void
    {
        $listing = $this->listing(['reopen_required' => true]);
        RentalRequest::create(['listing_id' => $listing->id, 'renter_id' => User::factory()->create()->id, 'start_date' => '2026-11-03', 'end_date' => '2026-11-03', 'rental_days' => 1, 'fulfillment_method' => 'pickup', 'rental_fee' => 50, 'commission_rate' => 10, 'commission_amount' => 5, 'security_deposit' => 0, 'total_amount' => 55, 'status' => 'approved']);
        ListingAvailability::set($listing, true);
        $this->assertTrue($listing->fresh()->hasApprovedOverlap('2026-11-03', '2026-11-03'));
    }

    public function test_a_stale_cart_cannot_bypass_the_owners_reopening_requirement(): void
    {
        $listing = $this->listing();
        $renter = User::factory()->identityVerified()->create();
        $this->actingAs($renter);
        $cart = RentalCart::save($renter, $listing->id, $this->period());
        $listing->update(['reopen_required' => true]);
        $this->rejects(fn () => RentalCart::checkout($renter, [$cart->id], true));
        $this->assertDatabaseCount('rental_requests', 0);
    }

    public function test_owner_approval_cannot_bypass_return_and_reopening_checks(): void
    {
        $listing = $this->listing();
        $request = RentalRequestSubmission::submit(User::factory()->identityVerified()->create(), $listing->id, $this->period(), RentalRequestSubmission::termsFor($listing), true);
        $listing->update(['reopen_required' => true]);
        Livewire::actingAs($listing->owner)->test(\App\Livewire\Owner\RentalRequests\Index::class)
            ->call('approve', $request->id)->assertHasErrors('approve');
        $this->assertTrue($request->fresh()->isPending());
    }

    public function test_daily_selected_return_time_drives_overdue_status_without_reopening_the_item(): void
    {
        $listing = $this->listing();
        $period = RentalPeriod::attributes($listing, $this->period(['rental_unit' => 'day']));
        $rental = $this->booking($listing);
        $rental->update($period + ['status' => 'active']);
        $this->travelTo(Carbon::parse('2026-11-01 20:01', 'Asia/Manila'));
        $this->assertTrue($rental->fresh()->isOverdue());
        $this->assertTrue(RentalLifecycle::synchronizeRentalStatus($rental));
        $this->assertFalse($listing->fresh()->acceptsNewRequests());
    }

    public function test_hourly_overnight_window_is_checked_and_a_twenty_four_hour_cap_is_elapsed_time(): void
    {
        $listing = $this->listing();
        $attributes = RentalPeriod::attributes($listing, $this->period(['start_time' => '18:00', 'end_date' => '2026-11-02', 'end_time' => '18:00']));
        $this->assertSame(24, $attributes['rental_hours']);
        $listing->available_time_from = '08:00';
        $this->rejects(fn () => RentalPeriod::attributes($listing, $this->period(['start_time' => '18:00', 'end_date' => '2026-11-02', 'end_time' => '09:00'])));
    }

    public function test_owner_schedule_reports_free_gaps_without_exposing_another_renters_times_in_public_calendar(): void
    {
        $listing = $this->listing();
        $this->booking($listing);
        $this->assertSame(['17:00–18:00', '20:00–23:59'], ListingAvailability::freePeriodsToday($listing->fresh()));
        $listing->update(['reopen_required' => true]);
        $this->assertSame(['17:00–18:00', '20:00–23:59'], ListingAvailability::freePeriodsToday($listing->fresh()));
        $this->assertFalse($listing->fresh()->acceptsNewRequests());
        $response = $this->get('/listings/'.$listing->id)->assertOk();
        $response->assertSee('Date availability only.');
        $response->assertDontSee('Booked:');
    }

    private function rejects(callable $action): void
    {
        try {
            $action();
            $this->fail('An unavailable or invalid rental period was accepted.');
        } catch (ValidationException $exception) {
            $this->assertNotEmpty($exception->errors());
        }
    }

    private function listing(array $overrides = []): Listing
    {
        return Listing::create($overrides + ['owner_id' => User::factory()->identityVerified()->create()->id, 'category_id' => Category::firstOrCreate(['slug' => 'phase-one'], ['name' => 'Tools'])->id, 'name' => 'Phase One Camera', 'description' => 'Camera', 'condition' => 'good', 'location' => 'Manila', 'price_per_day' => 500, 'price_per_hour' => 50, 'security_deposit' => 0, 'max_rental_duration_days' => 1, 'status' => 'published', 'available_from' => '2026-11-01', 'available_until' => '2026-12-31']);
    }

    private function period(array $overrides = []): array
    {
        return $overrides + ['start_date' => '2026-11-01', 'end_date' => '2026-11-01', 'start_time' => '18:00', 'end_time' => '20:00', 'rental_unit' => 'hour', 'fulfillment_method' => 'pickup'];
    }

    private function booking(Listing $listing): Rental
    {
        $renter = User::factory()->identityVerified()->create();
        $request = RentalRequestSubmission::submit($renter, $listing->id, $this->period(), RentalRequestSubmission::termsFor($listing), true);
        $request->update(['status' => 'approved', 'agreement_terms' => RentalAgreement::termsFor($request)]);
        RentalAgreement::accept($request, $listing->owner);

        return RentalAgreement::accept($request, $renter);
    }
}
