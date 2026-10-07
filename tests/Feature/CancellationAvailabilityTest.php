<?php

namespace Tests\Feature;

use App\Enums\ListingAvailabilityStatus;
use App\Enums\ListingStatus;
use App\Enums\RentalRequestStatus;
use App\Enums\RentalStatus;
use App\Livewire\Listings\Show as ListingShow;
use App\Livewire\Owner\Listings\Index as OwnerListingsIndex;
use App\Livewire\Owner\RentalRequests\Index as OwnerRequestsIndex;
use App\Livewire\Renter\RentalRequests\Index as RenterRequestsIndex;
use App\Livewire\Renter\Rentals\Show as RenterRentalShow;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\RentalRequest;
use App\Models\User;
use App\Services\RentalLifecycle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class CancellationAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private function listingFor(User $owner): Listing
    {
        $category = Category::firstOrCreate(['slug' => 'cancellation-tools'], ['name' => 'Cancellation tools']);

        return Listing::create([
            'owner_id' => $owner->id, 'category_id' => $category->id,
            'name' => 'Cancellation item', 'description' => 'A rental item.',
            'condition' => 'good', 'price_per_day' => 100, 'security_deposit' => 500,
            'location' => 'Manila', 'max_rental_duration_days' => 30,
            'status' => ListingStatus::Published, 'is_available' => true,
        ]);
    }

    private function requestFor(Listing $listing, User $renter, RentalRequestStatus $status = RentalRequestStatus::Requested, int $daysAhead = 5): RentalRequest
    {
        return RentalRequest::create([
            'listing_id' => $listing->id, 'renter_id' => $renter->id,
            'start_date' => now()->addDays($daysAhead), 'end_date' => now()->addDays($daysAhead + 2),
            'rental_days' => 3, 'fulfillment_method' => 'pickup',
            'rental_fee' => 300, 'commission_rate' => 10, 'commission_amount' => 30,
            'security_deposit' => 500, 'total_amount' => 830, 'status' => $status,
        ]);
    }

    private function rentalFor(RentalRequest $request, RentalStatus $status = RentalStatus::Paid): Rental
    {
        $rental = Rental::create([
            'rental_request_id' => $request->id, 'listing_id' => $request->listing_id,
            'owner_id' => $request->listing->owner_id, 'renter_id' => $request->renter_id,
            ...$request->only([
                'start_date', 'end_date', 'rental_days', 'fulfillment_method',
                'rental_fee', 'commission_rate', 'commission_amount', 'security_deposit', 'total_amount',
            ]),
            'status' => $status,
            'paid_at' => $status === RentalStatus::PaymentPending ? null : now(),
        ]);

        if ($status !== RentalStatus::PaymentPending) {
            Payment::create([
                'rental_id' => $rental->id, 'transaction_reference' => Payment::generateReference(),
                'amount' => $rental->total_amount, 'paid_at' => $rental->paid_at,
            ]);
        }

        return $rental;
    }

    public function test_pending_request_cancellation_notifies_owner_once_and_links_to_cancelled_history(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->listingFor($owner);
        $request = $this->requestFor($listing, $renter);

        $component = Livewire::actingAs($renter)->test(RenterRequestsIndex::class);
        $component->call('cancel', $request->id)->assertHasNoErrors();

        $this->assertSame(RentalRequestStatus::Cancelled, $request->fresh()->status);
        $this->assertSame(ListingAvailabilityStatus::Available, $listing->fresh()->availabilityStatus());
        $this->assertTrue($listing->fresh()->is_available);
        $notice = $owner->notifications()->sole();
        $this->assertNull($notice->read_at);
        $this->assertSame('rental_cancelled', $notice->data['type']);
        $this->assertStringContainsString($renter->name, $notice->data['message']);
        $this->assertStringContainsString('#'.$request->id, $notice->data['message']);
        $this->assertStringContainsString($listing->name, $notice->data['message']);
        $this->assertStringContainsString('Item availability: Available.', $notice->data['message']);
        $this->assertSame(route('owner.rental-requests.index', ['filter' => 'cancelled']), $notice->data['url']);

        $component->call('cancel', $request->id)->assertForbidden();
        $this->assertSame(1, $owner->notifications()->count());
        Livewire::withQueryParams(['filter' => 'cancelled'])->actingAs($owner)->test(OwnerRequestsIndex::class)
            ->assertSet('filter', 'cancelled')
            ->assertViewHas('rentalRequests', fn ($requests) => $requests->pluck('id')->all() === [$request->id]);
    }

    public function test_approved_request_cancellation_restores_available_and_releases_calendar_dates(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->listingFor($owner);
        $request = $this->requestFor($listing, $renter, RentalRequestStatus::Approved);
        $page = Livewire::actingAs($renter)->test(ListingShow::class, ['listing' => $listing])
            ->assertViewHas('availability', ListingAvailabilityStatus::OnHold)
            ->assertViewHas('bookedRanges', fn ($ranges) => $ranges->count() === 1);

        Livewire::actingAs($renter)->test(RenterRequestsIndex::class)
            ->call('cancel', $request->id)->assertHasNoErrors();

        $this->assertFalse($listing->hasApprovedOverlap($request->start_date, $request->end_date));
        $page->call('$refresh')
            ->assertViewHas('availability', ListingAvailabilityStatus::Available)
            ->assertViewHas('bookedRanges', fn ($ranges) => $ranges->isEmpty());
        $this->assertSame(1, $owner->notifications()->count());
        $this->withSession(['active_interface' => 'owner']);
        Livewire::actingAs($owner)->test(OwnerListingsIndex::class)
            ->assertViewHas('listings', fn ($listings) => $listings->first()->availabilityStatus() === ListingAvailabilityStatus::Available)
            ->assertSee('Available');
    }

    #[DataProvider('cancellableBookingStatuses')]
    public function test_confirmed_booking_cancellation_notifies_owner_and_restores_available(RentalStatus $status): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->listingFor($owner);
        $request = $this->requestFor($listing, $renter, RentalRequestStatus::Approved);
        $rental = $this->rentalFor($request, $status);
        $this->assertSame($status === RentalStatus::Paid ? ListingAvailabilityStatus::Reserved : ListingAvailabilityStatus::OnHold, $listing->availabilityStatus());

        Livewire::actingAs($renter)->test(RenterRentalShow::class, ['rental' => $rental])
            ->set('cancellation_reason', 'Plans changed')->call('cancelRental')->assertHasNoErrors();

        $this->assertSame(RentalStatus::Cancelled, $rental->fresh()->status);
        $this->assertSame(RentalRequestStatus::Cancelled, $request->fresh()->status);
        $this->assertSame(ListingAvailabilityStatus::Available, $listing->fresh()->availabilityStatus());
        $this->assertFalse($listing->hasApprovedOverlap($request->start_date, $request->end_date));
        $this->assertSame(ListingStatus::Published, $listing->fresh()->status);
        $notice = $owner->notifications()->sole();
        $this->assertSame('rental_cancelled', $notice->data['type']);
        $this->assertStringContainsString('Item availability: Available.', $notice->data['message']);
        $this->assertSame(route('owner.rentals.show', $rental), $notice->data['url']);
    }

    public static function cancellableBookingStatuses(): array
    {
        return ['unpaid' => [RentalStatus::PaymentPending], 'paid' => [RentalStatus::Paid]];
    }

    public function test_cancelling_one_booking_preserves_another_reservation_and_its_dates(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->listingFor($owner);
        $cancelledRequest = $this->requestFor($listing, $renter, RentalRequestStatus::Approved);
        $rental = $this->rentalFor($cancelledRequest);
        $remaining = $this->requestFor($listing, $renter, RentalRequestStatus::Approved, 12);

        $this->actingAs($renter);
        RentalLifecycle::cancel($rental, 'Plans changed');

        $this->assertSame(ListingAvailabilityStatus::OnHold, $listing->fresh()->availabilityStatus());
        $this->assertFalse($listing->hasApprovedOverlap($cancelledRequest->start_date, $cancelledRequest->end_date));
        $this->assertTrue($listing->hasApprovedOverlap($remaining->start_date, $remaining->end_date));
        $this->assertStringContainsString('Item availability: On hold.', $owner->notifications()->sole()->data['message']);
        Livewire::actingAs($renter)->test(ListingShow::class, ['listing' => $listing])
            ->assertViewHas('bookedRanges', fn ($ranges) => $ranges->all() === [[
                'start' => $remaining->start_date->toDateString(), 'end' => $remaining->end_date->toDateString(),
            ]]);

        $pending = $this->requestFor($listing, $renter);
        Livewire::actingAs($renter)->test(RenterRequestsIndex::class)->call('cancel', $pending->id);
        $this->assertSame(ListingAvailabilityStatus::OnHold, $listing->fresh()->availabilityStatus());
        $this->assertSame(RentalRequestStatus::Approved, $remaining->fresh()->status);
    }

    public function test_cancellation_does_not_mark_an_item_with_an_overdue_rental_available(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->listingFor($owner);
        $ongoingRequest = $this->requestFor($listing, $renter, RentalRequestStatus::Approved, -10);
        $ongoing = $this->rentalFor($ongoingRequest, RentalStatus::Overdue);
        $cancelled = $this->requestFor($listing, $renter, RentalRequestStatus::Approved);

        Livewire::actingAs($renter)->test(RenterRequestsIndex::class)->call('cancel', $cancelled->id);

        $this->assertSame(ListingAvailabilityStatus::Rented, $listing->fresh()->availabilityStatus());
        $this->assertSame(RentalStatus::Overdue, $ongoing->fresh()->status);
        $this->assertStringContainsString('Item availability: Rented.', $owner->notifications()->sole()->data['message']);
    }

    #[DataProvider('unavailableListings')]
    public function test_cancellation_preserves_owner_and_moderation_availability(array $attributes, bool $deleted): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->listingFor($owner);
        $request = $this->requestFor($listing, $renter, RentalRequestStatus::Approved);
        $rental = $this->rentalFor($request);
        $listing->update($attributes);
        if ($deleted) {
            $listing->delete();
        }
        $before = $listing->fresh();

        $this->actingAs($renter);
        RentalLifecycle::cancel($rental, 'Plans changed');

        $this->assertSame(ListingAvailabilityStatus::Unavailable, $listing->fresh()->availabilityStatus());
        $this->assertSame($before->is_available, $listing->fresh()->is_available);
        $this->assertSame($before->status, $listing->fresh()->status);
        $this->assertSame($deleted, $listing->fresh()->trashed());
        $this->assertSame(1, $owner->notifications()->count());
    }

    public static function unavailableListings(): array
    {
        return [
            'owner paused requests' => [['is_available' => false], false],
            'inactive listing' => [['status' => ListingStatus::Inactive], false],
            'pending moderation' => [['status' => ListingStatus::PendingApproval], false],
            'rejected listing' => [['status' => ListingStatus::Rejected], false],
            'removed listing' => [[], true],
        ];
    }

    public function test_failed_owner_notification_rolls_back_request_cancellation_and_date_release(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->listingFor($owner);
        $request = $this->requestFor($listing, $renter, RentalRequestStatus::Approved);
        Notification::shouldReceive('send')->once()->andThrow(new RuntimeException('Cannot store notification'));
        $this->actingAs($renter);

        try {
            app(RenterRequestsIndex::class)->cancel($request->id);
            $this->fail('The notification failure must propagate.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Cannot store notification', $exception->getMessage());
        }

        $this->assertSame(RentalRequestStatus::Approved, $request->fresh()->status);
        $this->assertSame(ListingAvailabilityStatus::OnHold, $listing->fresh()->availabilityStatus());
        $this->assertTrue($listing->hasApprovedOverlap($request->start_date, $request->end_date));
        $this->assertSame(0, $owner->notifications()->count());
    }

    public function test_request_cancellation_cannot_bypass_confirmed_booking_cancellation(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->listingFor($owner);
        $request = $this->requestFor($listing, $renter, RentalRequestStatus::Approved);
        $rental = $this->rentalFor($request);

        Livewire::actingAs($renter)->test(RenterRequestsIndex::class)
            ->call('cancel', $request->id)->assertForbidden();

        $this->assertSame(RentalRequestStatus::Approved, $request->fresh()->status);
        $this->assertSame(RentalStatus::Paid, $rental->fresh()->status);
        $this->assertSame(ListingAvailabilityStatus::Reserved, $listing->fresh()->availabilityStatus());
        $this->assertSame(0, $owner->notifications()->count());
    }
}
