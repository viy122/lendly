<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Livewire\Messages\Index;
use App\Livewire\Messages\ListingChat;
use App\Models\Category;
use App\Models\Listing;
use App\Models\ListingConversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ListingMessagingTest extends TestCase
{
    use RefreshDatabase;

    private function listingFor(User $owner): Listing
    {
        return Listing::create([
            'owner_id' => $owner->id,
            'category_id' => Category::firstOrCreate(['slug' => 'chat-tools'], ['name' => 'Chat Tools'])->id,
            'name' => 'Home Cinema Projector',
            'description' => 'A projector for movie nights.',
            'condition' => 'good',
            'price_per_day' => 600,
            'security_deposit' => 3000,
            'location' => 'Taal, Batangas',
            'max_rental_duration_days' => 5,
            'status' => ListingStatus::Published,
        ]);
    }

    public function test_message_button_opens_an_item_conversation_without_creating_a_booking(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $listing = $this->listingFor($owner);
        $listing->images()->create(['path' => 'listings/projector.jpg', 'sort_order' => 0]);

        $this->actingAs($renter)->get(route('listings.show', $listing))
            ->assertOk()->assertSee('Request to rent')->assertSee('aria-label="Message owner"', false);
        $this->get(route('listings.message', $listing))->assertOk()
            ->assertSee($owner->name)->assertSee($listing->name)
            ->assertSee('listings/projector.jpg')->assertSee('600.00')->assertSee('View details');
        $this->get(route('listings.message', $listing))->assertOk();

        $this->assertDatabaseCount('listing_conversations', 1);
        $this->assertDatabaseCount('rental_requests', 0);
        $this->assertDatabaseCount('messages', 0);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_renter_and_owner_can_exchange_messages_and_see_the_same_item_card(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $listing = $this->listingFor($owner);

        Livewire::actingAs($renter)->test(ListingChat::class, ['listing' => $listing])
            ->set('body', 'Is this suitable for outdoor use?')->call('send')->assertHasNoErrors()->assertSet('body', '');
        $conversation = ListingConversation::firstOrFail();

        $this->assertDatabaseHas('messages', [
            'listing_conversation_id' => $conversation->id,
            'rental_request_id' => null,
            'sender_id' => $renter->id,
            'receiver_id' => $owner->id,
        ]);
        $this->withSession(['active_interface' => 'owner']);
        $this->assertSame(1, $owner->unreadMessagesCount());
        Livewire::actingAs($owner)->test(Index::class)->assertSee('Is this suitable for outdoor use?')->assertSee($renter->name);
        $this->assertSame(route('listing-conversations.show', $conversation), $owner->notifications->first()->data['url']);

        Livewire::actingAs($owner)->test(ListingChat::class, ['conversation' => $conversation])
            ->assertSee($listing->name)->assertSee('View details')
            ->assertSee('Is this suitable for outdoor use?')
            ->set('body', 'Yes, after sunset.')->call('send')->assertHasNoErrors();
        $this->assertSame(0, $owner->unreadMessagesCount());

        $this->withSession(['active_interface' => 'renter']);
        $this->assertSame(1, $renter->unreadMessagesCount());
        Livewire::actingAs($renter)->test(ListingChat::class, ['conversation' => $conversation])
            ->assertSee('Yes, after sunset.')->assertSee($listing->name);
        $this->assertSame(0, $renter->unreadMessagesCount());
        $this->assertDatabaseCount('messages', 2);
    }

    public function test_only_participants_in_the_correct_interface_can_read_or_send(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $stranger = User::factory()->create();
        $listing = $this->listingFor($owner);
        $conversation = ListingConversation::create(['listing_id' => $listing->id, 'owner_id' => $owner->id, 'renter_id' => $renter->id]);

        $this->actingAs($stranger)->get(route('listing-conversations.show', $conversation))->assertForbidden();
        $this->actingAs($owner)->get(route('listings.message', $listing))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get(route('listings.message', $listing))->assertForbidden();

        $this->withSession(['active_interface' => 'owner']);
        Livewire::actingAs($renter)->test(ListingChat::class, ['conversation' => $conversation])->assertForbidden();
        $this->withSession(['active_interface' => 'renter']);
        Livewire::actingAs($owner)->test(ListingChat::class, ['conversation' => $conversation])->assertForbidden();

        $component = Livewire::actingAs($renter)->test(ListingChat::class, ['conversation' => $conversation])->set('body', 'Wrong interface');
        $this->withSession(['active_interface' => 'owner']);
        $component->call('send')->assertForbidden();
        $this->assertDatabaseCount('messages', 0);
    }

    public function test_guests_must_log_in_and_unpublished_items_cannot_start_chats(): void
    {
        $listing = $this->listingFor(User::factory()->create());
        $this->get(route('listings.message', $listing))->assertRedirect(route('login'));
        $listing->update(['status' => ListingStatus::PendingApproval]);
        $this->actingAs(User::factory()->create())->get(route('listings.message', $listing))->assertForbidden();
        $this->assertDatabaseCount('listing_conversations', 0);
    }

    public function test_blank_and_overlong_messages_are_not_sent(): void
    {
        $listing = $this->listingFor(User::factory()->create());
        $component = Livewire::actingAs(User::factory()->create())->test(ListingChat::class, ['listing' => $listing]);
        $component->set('body', '   ')->call('send')->assertHasErrors(['body' => 'required']);
        $component->set('body', str_repeat('a', 2001))->call('send')->assertHasErrors(['body' => 'max']);
        $this->assertDatabaseCount('messages', 0);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_polling_receives_new_messages_and_marks_them_read(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $listing = $this->listingFor($owner);
        $component = Livewire::actingAs($renter)->test(ListingChat::class, ['listing' => $listing]);
        $conversation = ListingConversation::firstOrFail();
        $message = $conversation->messages()->create(['sender_id' => $owner->id, 'receiver_id' => $renter->id, 'body' => 'It comes with a remote.']);

        $component->call('$refresh')->assertSee('It comes with a remote.');
        $this->assertNotNull($message->fresh()->read_at);
    }

    public function test_inbox_badges_and_notifications_follow_each_interface(): void
    {
        $member = User::factory()->create();
        $other = User::factory()->create();
        $ownerListing = $this->listingFor($member);
        $renterListing = $this->listingFor($other);

        Livewire::actingAs($other)->test(ListingChat::class, ['listing' => $ownerListing])
            ->set('body', 'Question as renter')->call('send');
        $ownerConversation = ListingConversation::firstOrFail();
        Livewire::actingAs($member)->test(ListingChat::class, ['listing' => $renterListing]);
        $renterConversation = ListingConversation::latest('id')->firstOrFail();
        Livewire::actingAs($other)->test(ListingChat::class, ['conversation' => $renterConversation])
            ->set('body', 'Reply as owner')->call('send');

        $this->actingAs($member)->withSession(['active_interface' => 'owner']);
        Livewire::test(Index::class)->assertSee('Question as renter')->assertDontSee('Reply as owner');
        $this->assertSame(1, $member->unreadMessagesCount());
        $this->get('/notifications')->assertSee(route('listing-conversations.show', $ownerConversation))
            ->assertDontSee(route('listing-conversations.show', $renterConversation));

        $this->withSession(['active_interface' => 'renter']);
        Livewire::test(Index::class)->assertSee('Reply as owner')->assertDontSee('Question as renter');
        $this->assertSame(1, $member->unreadMessagesCount());
        $this->get('/notifications')->assertSee(route('listing-conversations.show', $renterConversation))
            ->assertDontSee(route('listing-conversations.show', $ownerConversation));
    }

    public function test_item_context_and_history_remain_after_a_listing_is_archived(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $listing = $this->listingFor($owner);
        Livewire::actingAs($renter)->test(ListingChat::class, ['listing' => $listing])
            ->set('body', 'Is this still available?')->call('send');
        $listing->delete();

        $this->actingAs($owner)->get(route('listing-conversations.show', ListingConversation::firstOrFail()))
            ->assertOk()->assertSee('Is this still available?')->assertSee($listing->name)
            ->assertSee('This item is no longer listed');
    }
}
