<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Livewire\Messages\Index as MessagesIndex;
use App\Livewire\Messages\ListingChat;
use App\Livewire\Messages\Show as MessagesShow;
use App\Models\Category;
use App\Models\Listing;
use App\Models\ListingConversation;
use App\Models\Message;
use App\Models\RentalRequest;
use App\Models\User;
use App\Notifications\TalaNotification;
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

    public function test_search_filters_both_chat_types_by_person_or_item_without_replacing_the_active_chat(): void
    {
        $owner = User::factory()->create();
        $alice = User::factory()->create(['name' => 'Alice Santos']);
        $bob = User::factory()->create(['name' => 'Bob Reyes']);
        $drill = $this->listingFor($owner);
        $drill->update(['name' => 'Power Drill']);
        $camera = $this->listingFor($owner);
        $camera->update(['name' => 'Cinema Camera']);
        $request = $this->requestFor($owner, $alice, $drill);
        Message::create(['rental_request_id' => $request->id, 'sender_id' => $alice->id, 'receiver_id' => $owner->id, 'body' => 'Drill question']);
        $conversation = ListingConversation::create(['listing_id' => $camera->id, 'renter_id' => $bob->id, 'owner_id' => $owner->id]);
        $conversation->messages()->create(['sender_id' => $bob->id, 'receiver_id' => $owner->id, 'body' => 'Camera question']);

        $component = Livewire::actingAs($owner)->test(MessagesIndex::class)->assertSee('Search people or items');
        $this->assertCount(2, $component->viewData('threads'));
        $component->set('search', 'ALICE')->assertSee('Drill question')->assertDontSee('Camera question');
        $component->set('search', '  camera  ')->assertSee('Camera question')->assertDontSee('Drill question');
        $component->set('search', 'nonexistent')->assertSee('No conversations found');
        $this->assertCount(0, $component->viewData('threads'));
        $component->set('search', '');
        $this->assertCount(2, $component->viewData('threads'));

        $listingChat = Livewire::actingAs($owner)->test(ListingChat::class, ['conversation' => $conversation])
            ->set('search', 'Alice')->assertSee('Camera question');
        $this->assertSame([$request->id], $listingChat->viewData('threads')->pluck('id')->all());
        $rentalChat = Livewire::actingAs($owner)->test(MessagesShow::class, ['rentalRequest' => $request])
            ->set('search', 'Bob')->assertSee('Drill question');
        $this->assertSame(['listing-'.$conversation->id], $rentalChat->viewData('threads')->pluck('id')->all());
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

    public function test_messages_and_chat_access_are_separate_for_each_interface(): void
    {
        $member = User::factory()->create();
        $other = User::factory()->create();
        $ownerRequest = $this->requestFor($member, $other, $this->listingFor($member));
        $renterRequest = $this->requestFor($other, $member, $this->listingFor($other));

        foreach ([$ownerRequest, $renterRequest] as $request) {
            Message::create(['rental_request_id' => $request->id, 'sender_id' => $other->id, 'receiver_id' => $member->id, 'body' => 'Message for request '.$request->id]);
        }

        $this->withSession(['active_interface' => 'owner']);
        $threads = Livewire::actingAs($member)->test(MessagesIndex::class)->viewData('threads');
        $this->assertSame([$ownerRequest->id], $threads->pluck('rentalRequest.id')->all());
        $this->assertSame(1, $member->unreadMessagesCount());
        Livewire::actingAs($member)->test(MessagesShow::class, ['rentalRequest' => $renterRequest])->assertForbidden();

        $this->withSession(['active_interface' => 'renter']);
        $threads = Livewire::actingAs($member)->test(MessagesIndex::class)->viewData('threads');
        $this->assertSame([$renterRequest->id], $threads->pluck('rentalRequest.id')->all());
        $this->assertSame(1, $member->unreadMessagesCount());
        Livewire::actingAs($member)->test(MessagesShow::class, ['rentalRequest' => $ownerRequest])->assertForbidden();
    }

    public function test_notifications_only_show_activity_for_the_selected_interface(): void
    {
        $member = User::factory()->create();
        $other = User::factory()->create();
        $ownerRequest = $this->requestFor($member, $other, $this->listingFor($member));
        $renterRequest = $this->requestFor($other, $member, $this->listingFor($other));

        foreach ([
            'Owner request' => route('owner.rental-requests.index'),
            'Renter request' => route('renter.rental-requests.index'),
            'Owner chat' => route('rental-requests.chat', $ownerRequest),
            'Renter chat' => route('rental-requests.chat', $renterRequest),
        ] as $title => $url) {
            $member->notify(new TalaNotification('new_message', $title, 'Activity', $url));
        }

        $this->actingAs($member)->withSession(['active_interface' => 'owner']);
        $this->get('/notifications')->assertOk()->assertSee('Owner request')->assertSee('Owner chat')
            ->assertDontSee('Renter request')->assertDontSee('Renter chat');

        $this->withSession(['active_interface' => 'renter']);
        $this->get('/notifications')->assertOk()->assertSee('Renter request')->assertSee('Renter chat')
            ->assertDontSee('Owner request')->assertDontSee('Owner chat');
    }
}
