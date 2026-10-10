<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
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

    public function test_owner_is_notified_when_a_rental_request_is_submitted(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->publishedListing($owner);

        Livewire::actingAs($renter)
            ->test(Create::class, ['listing' => $listing])
            ->set('start_date', now()->addDays(3)->toDateString())
            ->set('end_date', now()->addDays(5)->toDateString())
            ->set('accept_terms', true)
            ->call('submit');

        $this->assertSame(1, $owner->fresh()->notifications()->count());
        $this->assertSame('rental_request_submitted', $owner->fresh()->notifications()->first()->data['type']);
    }

    public function test_renter_is_notified_when_request_is_approved_or_rejected(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->publishedListing($owner);

        Livewire::actingAs($renter)
            ->test(Create::class, ['listing' => $listing])
            ->set('start_date', now()->addDays(3)->toDateString())
            ->set('end_date', now()->addDays(5)->toDateString())
            ->set('accept_terms', true)
            ->call('submit');

        $request = RentalRequest::first();

        Livewire::actingAs($owner)
            ->test(OwnerRentalRequestsIndex::class)
            ->set('accept_terms', true)
            ->call('approve', $request->id);

        $renterNotifications = $renter->fresh()->notifications;
        $this->assertTrue($renterNotifications->pluck('data.type')->contains('request_approved'));
    }

    public function test_renter_is_notified_when_request_is_rejected(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->publishedListing($owner);

        Livewire::actingAs($renter)
            ->test(Create::class, ['listing' => $listing])
            ->set('start_date', now()->addDays(3)->toDateString())
            ->set('end_date', now()->addDays(5)->toDateString())
            ->set('accept_terms', true)
            ->call('submit');

        $request = RentalRequest::first();

        Livewire::actingAs($owner)
            ->test(OwnerRentalRequestsIndex::class)
            ->call('startRejecting', $request->id)
            ->set('rejection_reason', 'Not available after all.')
            ->call('confirmReject');

        $this->assertTrue($renter->fresh()->notifications->pluck('data.type')->contains('request_rejected'));
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
            'status' => 'paid',
            'paid_at' => now(),
        ]);

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
