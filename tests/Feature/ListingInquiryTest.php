<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Livewire\Messages\Index as MessagesIndex;
use App\Livewire\Messages\ListingShow;
use App\Models\Category;
use App\Models\Listing;
use App\Models\ListingConversation;
use App\Models\Message;
use App\Models\RentalRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ListingInquiryTest extends TestCase
{
    use RefreshDatabase;

    private function listingFor(User $owner): Listing
    {
        $category = Category::firstOrCreate(['slug' => 'inquiry-tools'], ['name' => 'Inquiry Tools']);

        return Listing::create([
            'owner_id' => $owner->id,
            'category_id' => $category->id,
            'name' => 'Inquiry Pressure Washer',
            'description' => 'Available for local pickup.',
            'condition' => 'good',
            'price_per_day' => 100,
            'location' => 'Manila',
            'max_rental_duration_days' => 10,
            'status' => ListingStatus::Published,
        ]);
    }

    public function test_member_can_contact_an_owner_without_creating_a_rental_request(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $listing = $this->listingFor($owner);

        $this->actingAs($renter)->get('/listings/'.$listing->id.'/contact')->assertOk();

        $this->assertDatabaseHas('listing_conversations', [
            'listing_id' => $listing->id,
            'owner_id' => $owner->id,
            'renter_id' => $renter->id,
        ]);
        $this->assertDatabaseCount('rental_requests', 0);
    }

    private function conversationFor(Listing $listing, User $renter): ListingConversation
    {
        return ListingConversation::create([
            'listing_id' => $listing->id,
            'owner_id' => $listing->owner_id,
            'renter_id' => $renter->id,
        ]);
    }

    public function test_contacting_the_same_listing_reuses_the_current_members_conversation(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $anotherRenter = User::factory()->create();
        $listing = $this->listingFor($owner);

        $this->actingAs($renter)->get('/listings/'.$listing->id.'/contact')->assertOk();
        $this->get('/listings/'.$listing->id.'/contact')->assertOk();
        $this->actingAs($anotherRenter)->get('/listings/'.$listing->id.'/contact')->assertOk();

        $this->assertDatabaseCount('listing_conversations', 2);
        $this->assertSame(1, ListingConversation::where('renter_id', $renter->id)->count());
        $this->assertDatabaseCount('messages', 0);
    }

    public function test_guests_unverified_members_admins_and_owners_cannot_start_inquiries(): void
    {
        $owner = User::factory()->create();
        $listing = $this->listingFor($owner);
        $url = '/listings/'.$listing->id.'/contact';

        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs(User::factory()->unverified()->create())->get($url)
            ->assertRedirect(route('verification.notice'));
        $this->actingAs(User::factory()->admin()->create())->get($url)->assertForbidden();
        $this->actingAs($owner)->get($url)->assertForbidden();

        $this->assertDatabaseCount('listing_conversations', 0);
    }

    public function test_suspended_members_cannot_start_inquiries(): void
    {
        $listing = $this->listingFor(User::factory()->create());

        $this->actingAs(User::factory()->suspended()->create())
            ->get('/listings/'.$listing->id.'/contact')->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertDatabaseCount('listing_conversations', 0);
    }

    public function test_unpublished_removed_or_inactive_owner_listings_cannot_start_inquiries(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $listing = $this->listingFor($owner);
        $this->actingAs($renter);

        $listing->update(['status' => ListingStatus::PendingApproval]);
        $this->get('/listings/'.$listing->id.'/contact')->assertForbidden();
        $listing->update(['status' => ListingStatus::Published]);
        $owner->update(['status' => 'suspended']);
        $this->get('/listings/'.$listing->id.'/contact')->assertForbidden();
        $owner->update(['status' => 'active']);
        $owner->delete();
        $this->get('/listings/'.$listing->id.'/contact')->assertForbidden();
        $listing->delete();
        $this->get('/listings/'.$listing->id.'/contact')->assertNotFound();

        $this->assertDatabaseCount('listing_conversations', 0);
    }

    public function test_only_the_participants_can_open_an_inquiry_conversation(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $conversation = $this->conversationFor($this->listingFor($owner), $renter);
        $url = '/messages/listings/'.$conversation->id;

        $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get($url)->assertForbidden();
        $this->actingAs($renter)->get($url)->assertOk()->assertSee($owner->name);
        $this->actingAs($owner)->get($url)->assertOk()->assertSee($renter->name);
    }

    public function test_both_participants_can_send_messages_and_each_recipient_gets_an_inquiry_link(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $listing = $this->listingFor($owner);
        $conversation = $this->conversationFor($listing, $renter);

        Livewire::actingAs($renter)->test(ListingShow::class, ['conversation' => $conversation])
            ->set('body', 'Is the washer available this weekend?')->call('send')->assertHasNoErrors();
        Livewire::actingAs($owner)->test(ListingShow::class, ['conversation' => $conversation->fresh()])
            ->set('body', 'Yes, pickup is available.')->call('send')->assertHasNoErrors();

        $this->assertDatabaseHas('messages', [
            'listing_conversation_id' => $conversation->id,
            'rental_request_id' => null,
            'sender_id' => $renter->id,
            'receiver_id' => $owner->id,
            'body' => 'Is the washer available this weekend?',
        ]);
        $this->assertDatabaseHas('messages', [
            'listing_conversation_id' => $conversation->id,
            'sender_id' => $owner->id,
            'receiver_id' => $renter->id,
            'body' => 'Yes, pickup is available.',
        ]);
        foreach ([$owner, $renter] as $recipient) {
            $notification = $recipient->fresh()->notifications()->sole();
            $this->assertSame('new_message', $notification->data['type']);
            $this->assertSame(route('messages.listing', $conversation), $notification->data['url']);
            $this->assertStringContainsString($listing->name, $notification->data['message']);
        }
        $this->assertDatabaseCount('rental_requests', 0);
    }

    public function test_opening_an_inquiry_marks_only_that_members_received_messages_as_read(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $listing = $this->listingFor($owner);
        $conversation = $this->conversationFor($listing, $renter);
        $incoming = $conversation->messages()->create(['sender_id' => $renter->id, 'receiver_id' => $owner->id, 'body' => 'Hello']);
        $outgoing = $conversation->messages()->create(['sender_id' => $owner->id, 'receiver_id' => $renter->id, 'body' => 'Hi']);
        $anotherRenter = User::factory()->create();
        $another = $this->conversationFor($listing, $anotherRenter)->messages()->create([
            'sender_id' => $anotherRenter->id, 'receiver_id' => $owner->id, 'body' => 'Another question',
        ]);

        $this->assertSame(2, $owner->unreadMessagesCount());
        $this->actingAs($owner)->get('/messages/listings/'.$conversation->id)->assertOk();

        $this->assertNotNull($incoming->fresh()->read_at);
        $this->assertNull($outgoing->fresh()->read_at);
        $this->assertNull($another->fresh()->read_at);
        $this->assertSame(1, $owner->unreadMessagesCount());
    }

    public function test_sending_validates_message_body_and_rechecks_member_access(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $conversation = $this->conversationFor($this->listingFor($owner), $renter);
        $component = Livewire::actingAs($renter)->test(ListingShow::class, ['conversation' => $conversation]);
        $component->set('body', '   ')->call('send')->assertHasErrors(['body' => 'required']);
        $component->set('body', str_repeat('a', 2001))->call('send')->assertHasErrors(['body' => 'max']);

        $component->set('body', 'Should be blocked');
        $renter->update(['role' => 'admin']);
        $component->call('send')->assertForbidden();

        $this->assertDatabaseCount('messages', 0);
    }

    public function test_inbox_contains_inquiries_and_request_chats_with_latest_previews_and_correct_links(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $listing = $this->listingFor($owner);
        $request = RentalRequest::create([
            'listing_id' => $listing->id, 'renter_id' => $renter->id,
            'start_date' => now()->addDays(3), 'end_date' => now()->addDays(5),
            'rental_days' => 3, 'fulfillment_method' => 'pickup', 'rental_fee' => 300,
            'commission_rate' => 10, 'commission_amount' => 30,
            'security_deposit' => 500, 'total_amount' => 830,
        ]);
        Message::create(['rental_request_id' => $request->id, 'sender_id' => $renter->id, 'receiver_id' => $owner->id, 'body' => 'Request chat preview']);
        $conversation = $this->conversationFor($listing, $renter);
        $conversation->messages()->create(['sender_id' => $renter->id, 'receiver_id' => $owner->id, 'body' => 'First inquiry']);
        $conversation->messages()->create(['sender_id' => $owner->id, 'receiver_id' => $renter->id, 'body' => 'Latest inquiry']);
        $stranger = User::factory()->create();
        $this->conversationFor($listing, $stranger)->messages()->create([
            'sender_id' => $stranger->id, 'receiver_id' => $owner->id, 'body' => 'Private stranger inquiry',
        ]);

        $component = Livewire::actingAs($renter)->test(MessagesIndex::class)
            ->assertSee('Request chat preview')->assertSee('Latest inquiry')->assertDontSee('Private stranger inquiry');
        $threads = $component->viewData('threads');
        $this->assertCount(2, $threads);
        $inquiry = $threads->firstWhere(fn ($thread) => ($thread['listingConversation'] ?? null)?->id === $conversation->id);
        $this->assertSame('Latest inquiry', $inquiry['lastMessage']->body);
        $this->assertSame(1, $inquiry['unreadCount']);
        $this->actingAs($renter)->get(route('messages.index'))->assertOk()
            ->assertSee(route('messages.listing', $conversation), false)
            ->assertSee(route('rental-requests.chat', $request), false);
    }

    public function test_existing_inquiry_history_remains_accessible_after_listing_removal(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $listing = $this->listingFor($owner);
        $conversation = $this->conversationFor($listing, $renter);
        $conversation->messages()->create(['sender_id' => $renter->id, 'receiver_id' => $owner->id, 'body' => 'Historic inquiry']);
        $listing->delete();

        $this->actingAs($renter)->get('/listings/'.$listing->id.'/contact')->assertNotFound();
        $this->get('/messages/listings/'.$conversation->id)->assertOk()->assertSee('Historic inquiry')->assertSee($listing->name);
        $this->get(route('messages.index'))->assertOk()->assertSee('Historic inquiry');
    }

    public function test_closed_or_suspended_participant_history_is_readable_but_cannot_receive_new_messages(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $conversation = $this->conversationFor($this->listingFor($owner), $renter);
        $message = $conversation->messages()->create(['sender_id' => $owner->id, 'receiver_id' => $renter->id, 'body' => 'Saved reply']);
        $owner->update(['status' => 'suspended']);
        Livewire::actingAs($renter)->test(ListingShow::class, ['conversation' => $conversation])
            ->set('body', 'Blocked reply')->call('send')->assertForbidden();
        $owner->delete();

        $this->actingAs($renter)->get('/messages/listings/'.$conversation->id)->assertOk()->assertSee('Saved reply')->assertSee($owner->name);
        $this->get(route('messages.index'))->assertOk()->assertSee('Saved reply');
        Livewire::actingAs($renter)->test(ListingShow::class, ['conversation' => $conversation])
            ->set('body', 'Blocked reply')->call('send')->assertForbidden();
        $this->assertSame($owner->id, $message->fresh()->sender->id);
        $this->assertDatabaseCount('messages', 1);
    }

    public function test_rollback_refuses_to_discard_existing_inquiry_history(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $conversation = $this->conversationFor($this->listingFor($owner), $renter);
        $conversation->messages()->create(['sender_id' => $renter->id, 'receiver_id' => $owner->id, 'body' => 'Keep this history']);
        $migration = require database_path('migrations/2026_10_09_000100_create_listing_conversations_table.php');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('inquiry messages exist');

        try {
            $migration->down();
        } finally {
            $this->assertDatabaseHas('messages', ['listing_conversation_id' => $conversation->id, 'body' => 'Keep this history']);
            $this->assertDatabaseHas('listing_conversations', ['id' => $conversation->id]);
        }
    }
}
