<?php

namespace Tests\Feature;

use App\Enums\ListingAvailabilityStatus;
use App\Enums\RentalStatus;
use App\Livewire\Admin\Rentals\Show as AdminRentalShow;
use App\Livewire\Listings\Browse;
use App\Livewire\Listings\Show as ListingShow;
use App\Livewire\Owner\Listings\Index as OwnerListings;
use App\Models\Category;
use App\Models\Listing;
use App\Models\OfflinePaymentSetting;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\RentalRequest;
use App\Models\User;
use App\Services\RentalAgreement;
use App\Services\RentalLifecycle;
use App\Services\RentalPayments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\Concerns\CreatesPaymentProof;
use Tests\TestCase;

class PaidReservationTest extends TestCase
{
    use CreatesPaymentProof;
    use RefreshDatabase;

    private function bookingFixture(): array
    {
        $this->travelTo(now()->startOfDay());
        Storage::set('local', Storage::fake('paid-reservations-'.Str::random(12)));
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $admin = User::factory()->admin()->create();
        $category = Category::create(['name' => 'Tools', 'slug' => 'tools']);
        $listing = Listing::create([
            'owner_id' => $owner->id, 'category_id' => $category->id,
            'name' => 'Reserved drill', 'description' => 'An item for rent.',
            'condition' => 'good', 'price_per_day' => 100, 'security_deposit' => 500,
            'location' => 'Manila', 'max_rental_duration_days' => 30,
            'status' => 'published', 'is_available' => true, 'pickup_available' => true,
        ]);
        $request = RentalRequest::create([
            'listing_id' => $listing->id, 'renter_id' => $renter->id,
            'start_date' => now()->addDays(3), 'end_date' => now()->addDays(5), 'rental_days' => 3,
            'fulfillment_method' => 'pickup', 'rental_fee' => 300, 'commission_rate' => 10,
            'commission_amount' => 30, 'security_deposit' => 500, 'total_amount' => 830,
            'status' => 'approved', 'owner_terms_accepted_at' => now(), 'renter_terms_accepted_at' => now(),
        ]);
        $request->update(['agreement_terms' => RentalAgreement::termsFor($request)]);
        $rental = RentalAgreement::accept($request, $renter);
        OfflinePaymentSetting::create([
            'id' => 1, 'enabled' => true, 'method' => 'bank_transfer',
            'instructions' => 'Transfer the full amount using the agreed bank details.',
        ]);

        return [$owner, $renter, $admin, $listing, $rental];
    }

    private function recordPayment(Rental $rental, User $renter, User $admin): void
    {
        $proof = $this->paymentProof();
        $submission = RentalPayments::submit($rental, $renter, 'BANK-'.$rental->id, $proof);
        $page = Livewire::actingAs($admin)->test(AdminRentalShow::class, ['rental' => $rental])
            ->set('reason', 'Verified the full payment was received')
            ->set('funds_received', true)
            ->call('verifyPayment', $submission->id);
        $this->assertSame([], $page->errors()->all());
    }

    private function assertAvailability(Listing $listing, ListingAvailabilityStatus $expected): void
    {
        $this->assertSame($expected, $listing->fresh()->availabilityStatus());
        $this->assertSame($expected, Listing::withAvailability()->findOrFail($listing->id)->availabilityStatus());
    }

    public function test_recorded_payment_reserves_the_item_for_the_agreed_dates_across_listing_views(): void
    {
        [$owner, $renter, $admin, $listing, $rental] = $this->bookingFixture();
        $this->assertAvailability($listing, ListingAvailabilityStatus::OnHold);
        $page = Livewire::actingAs($renter)->test(ListingShow::class, ['listing' => $listing])
            ->assertViewHas('reservedRanges', fn ($ranges) => $ranges->isEmpty());
        $this->recordPayment($rental, $renter, $admin);

        $this->assertAvailability($listing, ListingAvailabilityStatus::Reserved);
        $this->assertSame(RentalStatus::Paid, $rental->fresh()->status);
        $this->assertSame(1, Payment::where('rental_id', $rental->id)->count());
        $this->assertSame('830.00', $rental->fresh()->payment->amount);
        $page->call('$refresh')
            ->assertViewHas('availability', ListingAvailabilityStatus::Reserved)
            ->assertViewHas('reservedRanges', fn ($ranges) => $ranges->all() === [[
                'start' => $rental->start_date->toDateString(), 'end' => $rental->end_date->toDateString(),
            ]])
            ->assertSee('Reserved '.$rental->start_date->format('M d, Y').' – '.$rental->end_date->format('M d, Y'));
        Livewire::test(Browse::class)->assertViewHas('listings', fn ($items) => $items->first()->availabilityStatus() === ListingAvailabilityStatus::Reserved)
            ->assertSee('Reserved');
        $this->withSession(['active_interface' => 'owner']);
        Livewire::actingAs($owner)->test(OwnerListings::class)->assertSee('Reserved')
            ->assertViewHas('listings', fn ($items) => $items->first()->availabilityStatus() === ListingAvailabilityStatus::Reserved);
        $this->assertTrue($listing->fresh()->is_available);
        $this->assertTrue($listing->hasApprovedOverlap($rental->start_date, $rental->end_date));
        $this->assertFalse($listing->hasApprovedOverlap($rental->end_date->copy()->addDay(), $rental->end_date->copy()->addDays(2)));
    }

    public function test_submitted_or_rejected_payment_proof_does_not_reserve_the_item(): void
    {
        [, $renter, $admin, $listing, $rental] = $this->bookingFixture();
        $proof = $this->paymentProof();
        $submission = RentalPayments::submit($rental, $renter, 'BANK-PENDING', $proof);
        $this->assertAvailability($listing, ListingAvailabilityStatus::OnHold);
        $this->assertSame(0, $listing->paidReservations()->count());
        RentalPayments::review($rental, $admin, $submission->id, false, 'Payment was not received', false);
        $this->assertAvailability($listing, ListingAvailabilityStatus::OnHold);
        $this->assertSame('rejected', $submission->fresh()->status);
        $this->assertSame(0, Payment::count());
    }

    public function test_paid_status_alone_does_not_reserve_an_item_without_a_recorded_transaction(): void
    {
        [, , , $listing, $rental] = $this->bookingFixture();
        $rental->update(['status' => RentalStatus::Paid, 'paid_at' => now()]);
        $this->assertAvailability($listing, ListingAvailabilityStatus::OnHold);
        $payment = Payment::create([
            'rental_id' => $rental->id, 'transaction_reference' => Payment::generateReference(),
            'amount' => $rental->total_amount, 'paid_at' => now(), 'status' => 'pending',
        ]);
        $this->assertAvailability($listing, ListingAvailabilityStatus::OnHold);
        $payment->update(['status' => 'paid']);
        $this->assertAvailability($listing, ListingAvailabilityStatus::Reserved);
    }

    public function test_failed_payment_transaction_does_not_leave_a_reservation(): void
    {
        [, $renter, $admin, $listing, $rental] = $this->bookingFixture();
        $submission = RentalPayments::submit($rental, $renter, 'BANK-ROLLBACK', $this->paymentProof());
        $eventName = 'eloquent.updating: '.Rental::class;
        Event::listen($eventName, function (Rental $updated) {
            if ($updated->status === RentalStatus::Paid) {
                throw new RuntimeException('Booking payment update failed.');
            }
        });
        try {
            RentalPayments::review($rental, $admin, $submission->id, true, 'Funds received', true);
            $this->fail('The simulated transaction failure must be raised.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Booking payment update failed.', $exception->getMessage());
        } finally {
            Event::forget($eventName);
        }
        $this->assertAvailability($listing, ListingAvailabilityStatus::OnHold);
        $this->assertSame(0, Payment::count());
        $this->assertNull($rental->fresh()->securityDeposit);
        $this->assertSame('pending', $submission->fresh()->status);
        RentalPayments::review($rental, $admin, $submission->id, true, 'Funds received', true);
        $this->assertAvailability($listing, ListingAvailabilityStatus::Reserved);
    }

    public function test_reservation_includes_the_end_date_and_expires_without_another_payment_action(): void
    {
        [, $renter, $admin, $listing, $rental] = $this->bookingFixture();
        $this->recordPayment($rental, $renter, $admin);
        $this->travelTo($rental->end_date->copy()->endOfDay());
        $this->assertAvailability($listing, ListingAvailabilityStatus::Reserved);
        $this->travelTo($rental->end_date->copy()->addDay()->startOfDay());
        $this->assertAvailability($listing, ListingAvailabilityStatus::Available);
        $this->assertSame(0, $listing->paidReservations()->count());
        $this->assertNotNull($rental->fresh()->payment);
    }

    public function test_cancellation_releases_its_reservation_without_removing_another_paid_booking(): void
    {
        [$owner, $renter, $admin, $listing, $rental] = $this->bookingFixture();
        $this->recordPayment($rental, $renter, $admin);
        $request = $rental->rentalRequest->replicate(['owner_terms_accepted_at', 'renter_terms_accepted_at']);
        $request->start_date = now()->addDays(10);
        $request->end_date = now()->addDays(12);
        $request->owner_terms_accepted_at = now();
        $request->renter_terms_accepted_at = now();
        $request->save();
        $request->update(['agreement_terms' => RentalAgreement::termsFor($request)]);
        $second = RentalAgreement::accept($request, $renter);
        $this->recordPayment($second, $renter, $admin);
        $this->actingAs($renter);
        RentalLifecycle::cancel($rental);
        $this->assertAvailability($listing, ListingAvailabilityStatus::Reserved);
        $this->assertSame([$second->id], $listing->paidReservations()->pluck('id')->all());
        $this->assertFalse($listing->hasApprovedOverlap($rental->start_date, $rental->end_date));
        $this->assertTrue($listing->hasApprovedOverlap($second->start_date, $second->end_date));
        RentalLifecycle::cancel($second);
        $this->assertAvailability($listing, ListingAvailabilityStatus::Available);
        $this->assertSame(2, Payment::count());
    }

    public static function returnedStates(): array
    {
        return [['returned'], ['completed']];
    }

    #[DataProvider('returnedStates')]
    public function test_returned_and_completed_rentals_release_reserved_dates(string $status): void
    {
        [, $renter, $admin, $listing, $rental] = $this->bookingFixture();
        $this->recordPayment($rental, $renter, $admin);
        $rental->update(['status' => $status]);
        $this->assertAvailability($listing, ListingAvailabilityStatus::Available);
        $this->assertSame(0, $listing->paidReservations()->count());
    }

    public function test_paid_dates_remain_reserved_after_handover_and_an_unreturned_item_stays_rented_after_expiry(): void
    {
        [$owner, $renter, $admin, $listing, $rental] = $this->bookingFixture();
        $this->recordPayment($rental, $renter, $admin);
        RentalLifecycle::confirmPickup($rental, $owner);
        $this->assertAvailability($listing, ListingAvailabilityStatus::Reserved);
        $this->assertSame(RentalStatus::Paid, $rental->fresh()->status);
        RentalLifecycle::confirmPickup($rental, $renter);
        $this->assertAvailability($listing, ListingAvailabilityStatus::Reserved);
        $this->travelTo($rental->end_date->copy()->addDay()->startOfDay());
        $this->assertAvailability($listing, ListingAvailabilityStatus::Rented);
    }
}
