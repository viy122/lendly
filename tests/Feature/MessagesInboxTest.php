<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Livewire\Messages\Index as MessagesIndex;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Message;
use App\Models\RentalRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

// FR-33: users can view their complete message history, organized per conversation thread.
class MessagesInboxTest extends TestCase
{
    use RefreshDatabase;

    private function listingFor(User $owner): Listing
    {
        $category = Category::firstOrCreate(['slug' => 'inbox-tools'], ['name' => 'Inbox Tools']);

        return Listing::create([
            'owner_id' => $owner->id,
            'category_id' => $category->id,
            'name' => 'Inbox Item',
            'description' => 'x',
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

    private function requestFor(User $owner, User $renter, Listing $listing): RentalRequest
    {
        return RentalRequest::create([
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
            'status' => 'requested',
        ]);
    }

    public function test_inbox_shows_one_thread_per_conversation_with_the_latest_message_preview(): void
    {
        $owner = User::factory()->create();
        $renterA = User::factory()->create();
        $renterB = User::factory()->create();
        $listing = $this->listingFor($owner);

        $requestA = $this->requestFor($owner, $renterA, $listing);
        $requestB = $this->requestFor($owner, $renterB, $listing);

        Message::create(['rental_request_id' => $requestA->id, 'sender_id' => $renterA->id, 'receiver_id' => $owner->id, 'body' => 'Hi from A']);
        Message::create(['rental_request_id' => $requestA->id, 'sender_id' => $owner->id, 'receiver_id' => $renterA->id, 'body' => 'Hi back']);
        Message::create(['rental_request_id' => $requestB->id, 'sender_id' => $renterB->id, 'receiver_id' => $owner->id, 'body' => 'Hi from B']);

        $component = Livewire::actingAs($owner)->test(MessagesIndex::class);
        $threads = $component->viewData('threads');

        $this->assertCount(2, $threads, 'one thread per conversation, not per message');

        $threadA = $threads->firstWhere(fn ($t) => $t['rentalRequest']->id === $requestA->id);
        $this->assertSame('Hi back', $threadA['lastMessage']->body, 'preview must be the latest message, not the first one');

        $this->actingAs($owner)->get(route('messages.index'))
            ->assertOk()
            ->assertSee('Hi back')
            ->assertSee('Hi from B');
    }

    public function test_a_request_with_no_messages_yet_does_not_appear_in_the_inbox(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $listing = $this->listingFor($owner);
        $this->requestFor($owner, $renter, $listing);

        $component = Livewire::actingAs($owner)->test(MessagesIndex::class);
        $this->assertCount(0, $component->viewData('threads'));
    }

    public function test_unread_count_per_thread_is_correct(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $listing = $this->listingFor($owner);
        $request = $this->requestFor($owner, $renter, $listing);

        Message::create(['rental_request_id' => $request->id, 'sender_id' => $renter->id, 'receiver_id' => $owner->id, 'body' => 'One']);
        Message::create(['rental_request_id' => $request->id, 'sender_id' => $renter->id, 'receiver_id' => $owner->id, 'body' => 'Two']);

        $component = Livewire::actingAs($owner)->test(MessagesIndex::class);
        $thread = $component->viewData('threads')->first();

        $this->assertSame(2, $thread['unreadCount']);
    }
}
