<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Enums\RentalRequestStatus;
use App\Livewire\Messages\Show as MessagesShow;
use App\Livewire\Owner\RentalRequests\Index as OwnerRentalRequestsIndex;
use App\Livewire\RentalRequests\Create;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Message;
use App\Models\Rental;
use App\Models\RentalRequest;
use App\Models\User;
use App\Services\RentalLifecycle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationsAndChatTest extends TestCase
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

    private function assertStatusNotification(User $user, string $type, string $status, string $url): array
    {
        $notifications = $user->fresh()->notifications->where('data.type', $type);
        $this->assertCount(1, $notifications);

        $notification = $notifications->first();
        $this->assertNull($notification->read_at);
        $this->assertStringContainsString($status, $notification->data['title']);
        $this->assertStringContainsString('Pressure Washer', $notification->data['message']);
        $this->assertSame($url, $notification->data['url']);

        return $notification->data;
    }

    public function test_both_parties_are_notified_when_a_rental_request_is_pending(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->publishedListing($owner);

        Livewire::actingAs($renter)
            ->test(Create::class, ['listing' => $listing])
            ->set('start_date', now()->addDays(3)->toDateString())
            ->set('end_date', now()->addDays(5)->toDateString())
            ->call('submit');

        $this->assertSame(RentalRequestStatus::Requested, RentalRequest::first()->status);
        $this->assertSame(1, $owner->notifications()->count());
        $this->assertSame(1, $renter->notifications()->count());
        $ownerData = $this->assertStatusNotification($owner, 'rental_request_submitted', 'New rental request', route('owner.rental-requests.index'));
        $renterData = $this->assertStatusNotification($renter, 'rental_request_submitted', 'pending', route('renter.rental-requests.index'));
        $this->assertStringContainsString('pending', $ownerData['message']);
        $this->assertStringContainsString($renter->name, $ownerData['message']);
        $this->assertStringContainsString('pending', $renterData['message']);
    }

    public function test_both_parties_are_notified_when_request_is_approved(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->publishedListing($owner);

        Livewire::actingAs($renter)
            ->test(Create::class, ['listing' => $listing])
            ->set('start_date', now()->addDays(3)->toDateString())
            ->set('end_date', now()->addDays(5)->toDateString())
            ->call('submit');

        $request = RentalRequest::first();

        Livewire::actingAs($owner)
            ->test(OwnerRentalRequestsIndex::class)
            ->call('approve', $request->id)
            ->assertHasNoErrors();

        $this->assertSame(RentalRequestStatus::Approved, $request->fresh()->status);
        $this->assertNull($request->fresh()->rental);
        $ownerData = $this->assertStatusNotification($owner, 'request_approved', 'approved', route('owner.rental-requests.agreement', $request));
        $renterData = $this->assertStatusNotification($renter, 'request_approved', 'approved', route('renter.rental-requests.agreement', $request));
        $this->assertStringContainsString($renter->name, $ownerData['message']);
        $this->assertStringContainsString('review and accept the rental agreement', $renterData['message']);

        Livewire::actingAs($owner)->test(OwnerRentalRequestsIndex::class)
            ->call('approve', $request->id)->assertForbidden();

        $this->assertSame(2, $owner->notifications()->count());
        $this->assertSame(2, $renter->notifications()->count());
    }

    public function test_both_parties_are_notified_when_request_is_declined(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->publishedListing($owner);

        Livewire::actingAs($renter)
            ->test(Create::class, ['listing' => $listing])
            ->set('start_date', now()->addDays(3)->toDateString())
            ->set('end_date', now()->addDays(5)->toDateString())
            ->call('submit');

        $request = RentalRequest::first();

        Livewire::actingAs($owner)
            ->test(OwnerRentalRequestsIndex::class)
            ->call('startRejecting', $request->id)
            ->set('rejection_reason', 'Not available after all.')
            ->call('confirmReject')
            ->assertHasNoErrors();

        $this->assertSame(RentalRequestStatus::Rejected, $request->fresh()->status);
        foreach ([$owner, $renter] as $user) {
            $interface = $user->id === $owner->id ? 'owner' : 'renter';
            $data = $this->assertStatusNotification($user, 'request_rejected', 'declined', route($interface.'.rental-requests.index'));
            $this->assertStringContainsString('Not available after all.', $data['message']);
        }

        Livewire::actingAs($owner)->test(OwnerRentalRequestsIndex::class)
            ->call('startRejecting', $request->id)
            ->set('rejection_reason', 'Another reason.')
            ->call('confirmReject')->assertForbidden();

        $this->assertSame(2, $owner->notifications()->count());
        $this->assertSame(2, $renter->notifications()->count());
    }

    public function test_automatic_overlap_decline_notifies_both_parties_and_leaves_other_requests_pending(): void
    {
        $owner = User::factory()->owner()->create();
        $listing = $this->publishedListing($owner);
        $renters = User::factory()->renter()->count(3)->create();
        $requests = [];

        foreach ($renters as $index => $renter) {
            $start = $index === 2 ? 8 : 3;
            Livewire::actingAs($renter)->test(Create::class, ['listing' => $listing])
                ->set('start_date', now()->addDays($start)->toDateString())
                ->set('end_date', now()->addDays($start + 2)->toDateString())
                ->call('submit')->assertHasNoErrors();
            $requests[] = RentalRequest::where('renter_id', $renter->id)->firstOrFail();
        }

        Livewire::actingAs($owner)->test(OwnerRentalRequestsIndex::class)
            ->call('approve', $requests[0]->id)->assertHasNoErrors();

        $this->assertSame(RentalRequestStatus::Approved, $requests[0]->fresh()->status);
        $this->assertSame(RentalRequestStatus::Rejected, $requests[1]->fresh()->status);
        $this->assertSame(RentalRequestStatus::Requested, $requests[2]->fresh()->status);
        $ownerData = $this->assertStatusNotification($owner, 'request_rejected', 'automatically declined', route('owner.rental-requests.index'));
        $renterData = $this->assertStatusNotification($renters[1], 'request_rejected', 'declined', route('renter.rental-requests.index'));
        $this->assertStringContainsString($renters[1]->name, $ownerData['message']);
        $this->assertStringContainsString('These dates were booked by another renter.', $ownerData['message']);
        $this->assertStringContainsString('These dates were booked by another renter.', $renterData['message']);
        $this->assertSame(5, $owner->notifications()->count());
        $this->assertSame(2, $renters[0]->notifications()->count());
        $this->assertSame(2, $renters[1]->notifications()->count());
        $this->assertSame(1, $renters[2]->notifications()->count());
    }

    public function test_invalid_or_unauthorized_decisions_do_not_send_status_notifications(): void
    {
        $owner = User::factory()->owner()->create();
        $stranger = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->publishedListing($owner);

        Livewire::actingAs($renter)->test(Create::class, ['listing' => $listing])
            ->call('submit')->assertHasNoErrors();
        $request = RentalRequest::firstOrFail();

        Livewire::actingAs($owner)->test(OwnerRentalRequestsIndex::class)
            ->call('startRejecting', $request->id)->call('confirmReject')->assertHasErrors(['rejection_reason']);
        Livewire::actingAs($stranger)->test(OwnerRentalRequestsIndex::class)
            ->call('approve', $request->id)->assertForbidden();
        Livewire::actingAs($stranger)->test(OwnerRentalRequestsIndex::class)
            ->call('startRejecting', $request->id)->set('rejection_reason', 'Unavailable')
            ->call('confirmReject')->assertForbidden();

        $this->assertSame(RentalRequestStatus::Requested, $request->fresh()->status);
        $this->assertSame(1, $owner->notifications()->count());
        $this->assertSame(1, $renter->notifications()->count());
        $this->assertSame(0, $stranger->notifications()->count());
    }

    public function test_both_parties_notified_when_pickup_confirmed_by_both(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->publishedListing($owner);

        $request = RentalRequest::create([
            'listing_id' => $listing->id,
            'renter_id' => $renter->id,
            'start_date' => now()->addDays(3),
            'end_date' => now()->addDays(5),
            'rental_days' => 3,
            'fulfillment_method' => 'pickup',
            'rental_fee' => 300,
            'commission_rate' => 10,
            'commission_amount' => 30,
            'security_deposit' => 500,
            'total_amount' => 830,
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
            'security_deposit' => 500,
            'total_amount' => 830,
        ]);

        // Pickup confirmation requires a paid booking, as enforced by the UI and service.
        $rental->update(['status' => 'paid', 'paid_at' => now()]);

        RentalLifecycle::confirmPickup($rental, $renter);
        // Owner gets a "renter confirmed pickup" notice only — one party
        // confirming isn't enough to start the rental yet.
        $this->assertCount(1, $owner->fresh()->notifications);

        RentalLifecycle::confirmPickup($rental->fresh(), $owner);

        $ownerMessages = $owner->fresh()->notifications->pluck('data.message')->implode(' | ');
        $renterMessages = $renter->fresh()->notifications->pluck('data.message')->implode(' | ');
        $this->assertStringContainsString('started', $ownerMessages);
        $this->assertStringContainsString('started', $renterMessages);
    }

    public function test_renter_and_owner_can_message_each_other_on_a_rental_request(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->publishedListing($owner);

        $request = RentalRequest::create([
            'listing_id' => $listing->id,
            'renter_id' => $renter->id,
            'start_date' => now()->addDays(3),
            'end_date' => now()->addDays(5),
            'rental_days' => 3,
            'fulfillment_method' => 'pickup',
            'rental_fee' => 300,
            'commission_rate' => 10,
            'commission_amount' => 30,
            'security_deposit' => 500,
            'total_amount' => 830,
        ]);

        Livewire::actingAs($renter)
            ->test(MessagesShow::class, ['rentalRequest' => $request])
            ->set('body', 'Hi, is this still available?')
            ->call('send')
            ->assertHasNoErrors();

        Livewire::actingAs($owner)
            ->test(MessagesShow::class, ['rentalRequest' => $request->fresh()])
            ->set('body', 'Yes, it is!')
            ->call('send')
            ->assertHasNoErrors();

        $this->assertSame(2, Message::where('rental_request_id', $request->id)->count());

        $firstMessage = Message::first();
        $this->assertSame($renter->id, $firstMessage->sender_id);
        $this->assertSame($owner->id, $firstMessage->receiver_id);
    }

    // FR-32: recipient is notified of each new incoming message, not just left to notice an unread badge.
    public function test_recipient_is_notified_of_a_new_message(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->publishedListing($owner);

        $request = RentalRequest::create([
            'listing_id' => $listing->id,
            'renter_id' => $renter->id,
            'start_date' => now()->addDays(3),
            'end_date' => now()->addDays(5),
            'rental_days' => 3,
            'fulfillment_method' => 'pickup',
            'rental_fee' => 300,
            'commission_rate' => 10,
            'commission_amount' => 30,
            'security_deposit' => 500,
            'total_amount' => 830,
        ]);

        Livewire::actingAs($renter)
            ->test(MessagesShow::class, ['rentalRequest' => $request])
            ->set('body', 'Hi, is this still available?')
            ->call('send');

        $notification = $owner->fresh()->notifications()->first();
        $this->assertNotNull($notification);
        $this->assertSame('new_message', $notification->data['type']);
        $this->assertStringContainsString($renter->name, $notification->data['message']);
    }

    public function test_viewing_the_thread_marks_received_messages_as_read(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->publishedListing($owner);

        $request = RentalRequest::create([
            'listing_id' => $listing->id,
            'renter_id' => $renter->id,
            'start_date' => now()->addDays(3),
            'end_date' => now()->addDays(5),
            'rental_days' => 3,
            'fulfillment_method' => 'pickup',
            'rental_fee' => 300,
            'commission_rate' => 10,
            'commission_amount' => 30,
            'security_deposit' => 500,
            'total_amount' => 830,
        ]);

        Message::create([
            'rental_request_id' => $request->id,
            'sender_id' => $renter->id,
            'receiver_id' => $owner->id,
            'body' => 'Hello?',
        ]);

        $this->assertSame(1, $owner->fresh()->unreadMessagesCount());

        Livewire::actingAs($owner)->test(MessagesShow::class, ['rentalRequest' => $request]);

        $this->assertSame(0, $owner->fresh()->unreadMessagesCount());
    }

    public function test_unrelated_user_cannot_view_or_send_messages_on_a_rental_request(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $stranger = User::factory()->renter()->create();
        $listing = $this->publishedListing($owner);

        $request = RentalRequest::create([
            'listing_id' => $listing->id,
            'renter_id' => $renter->id,
            'start_date' => now()->addDays(3),
            'end_date' => now()->addDays(5),
            'rental_days' => 3,
            'fulfillment_method' => 'pickup',
            'rental_fee' => 300,
            'commission_rate' => 10,
            'commission_amount' => 30,
            'security_deposit' => 500,
            'total_amount' => 830,
        ]);

        $this->actingAs($stranger)
            ->get(route('rental-requests.chat', $request))
            ->assertForbidden();
    }

    public function test_overdue_command_notifies_both_parties(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->publishedListing($owner);

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
            'security_deposit' => 500,
            'total_amount' => 830,
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
            'security_deposit' => 500,
            'total_amount' => 830,
            'status' => 'active',
        ]);

        $this->artisan('rentals:check-overdue');

        $this->assertTrue($renter->fresh()->notifications->pluck('data.type')->contains('rental_overdue'));
        $this->assertTrue($owner->fresh()->notifications->pluck('data.type')->contains('rental_overdue'));
    }
}
