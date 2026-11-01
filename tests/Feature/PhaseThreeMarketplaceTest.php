<?php

namespace Tests\Feature;

use App\Livewire\Listings\Show;
use App\Livewire\RentalRequests\Create;
use App\Livewire\Renter\Cart\Index;
use App\Models\Category;
use App\Models\Listing;
use App\Models\RentalRequest;
use App\Models\RentalWaitlistEntry;
use App\Models\User;
use App\Services\RentalCart;
use App\Services\RentalWaitlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PhaseThreeMarketplaceTest extends TestCase
{
    use RefreshDatabase;

    private function listing(array $overrides = []): Listing
    {
        return Listing::create($overrides + [
            'owner_id' => User::factory()->identityVerified()->create()->id,
            'category_id' => Category::firstOrCreate(['slug' => 'phase-three'], ['name' => 'Tools'])->id,
            'name' => 'Camera', 'description' => 'With a case', 'condition' => 'good',
            'price_per_day' => 200, 'price_per_hour' => 50, 'location' => 'Manila',
            'available_from' => now()->toDateString(), 'available_until' => now()->addMonth()->toDateString(),
            'available_time_from' => '08:00', 'available_time_until' => '20:00',
            'status' => 'published', 'is_available' => true,
        ]);
    }

    private function details(): array
    {
        return ['start_date' => now()->addDays(3)->toDateString(), 'end_date' => now()->addDays(3)->toDateString(),
            'start_time' => '09:00', 'end_time' => '11:00', 'rental_unit' => 'hour', 'fulfillment_method' => 'pickup'];
    }

    private function hold(Listing $listing): RentalRequest
    {
        return RentalRequest::create($this->details() + ['listing_id' => $listing->id,
            'renter_id' => User::factory()->identityVerified()->create()->id, 'status' => 'approved',
            'rental_days' => 1, 'rental_hours' => 2, 'rental_fee' => 100, 'commission_rate' => 10,
            'commission_amount' => 10, 'security_deposit' => 0, 'total_amount' => 110]);
    }

    public function test_both_identity_messages_share_one_product_notice(): void
    {
        $listing = $this->listing(['owner_id' => User::factory()->create()->id]);
        $component = Livewire::actingAs(User::factory()->create())->test(Show::class, ['listing' => $listing]);
        $component->assertSee('You can browse and save rental drafts.')->assertSee('The owner must complete their ID review');
        $html = $component->html();
        $this->assertSame(1, substr_count($html, 'id="listing-identity-notice"'));
        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        $notice = (new \DOMXPath($document))->query('//*[@id="listing-identity-notice"]')->item(0);
        $this->assertStringContainsString('You can browse and save rental drafts.', $notice->textContent);
        $this->assertStringContainsString('The owner must complete their ID review', $notice->textContent);
    }

    public function test_verified_participants_have_no_identity_notice_and_guests_see_only_owner_requirement(): void
    {
        $listing = $this->listing();
        Livewire::actingAs(User::factory()->identityVerified()->create())->test(Show::class, ['listing' => $listing])
            ->assertDontSee('id="listing-identity-notice"', false);
        auth()->logout();
        $listing->owner->forceFill(['identity_verified_at' => null])->save();
        Livewire::test(Show::class, ['listing' => $listing])->assertSee('The owner must complete their ID review')
            ->assertDontSee('Verify your identity');
    }

    public function test_product_shows_owner_hours_item_details_and_rate_explanation(): void
    {
        $listing = $this->listing(['brand' => 'Canon', 'model' => 'M50', 'purchase_year' => 2024,
            'estimated_original_price' => 25000, 'rental_rules' => 'Keep dry']);
        Livewire::test(Show::class, ['listing' => $listing])->assertSee('Pickup & return hours (Philippine time)', false)
            ->assertSee('8:00 AM')->assertSee('8:00 PM')->assertSee('Canon')->assertSee('M50')
            ->assertSee('Purchase year')->assertSee('2024')->assertSee('25,000.00')->assertSee('Keep dry')
            ->assertSee('Hourly rentals use whole hours')->assertSee('Date availability only.');
    }

    public function test_cart_hides_waitlist_for_available_dates_and_direct_action_is_rejected(): void
    {
        $renter = User::factory()->identityVerified()->create();
        $item = RentalCart::save($renter, $this->listing()->id, ['rental_unit' => 'day', 'end_date' => now()->addDays(4)->toDateString()] + $this->details());
        Livewire::actingAs($renter)->test(Index::class)->assertSee('2 days, Philippine time')->assertDontSee('Join waitlist for saved dates')
            ->call('joinWaitlist', $item->id)->assertHasErrors(['waitlist']);
        $this->assertDatabaseCount('rental_waitlist_entries', 0);
    }

    public function test_waitlist_appears_for_conflicts_and_disappears_when_hold_is_released(): void
    {
        $renter = User::factory()->identityVerified()->create();
        $listing = $this->listing();
        $item = RentalCart::save($renter, $listing->id, $this->details());
        $hold = $this->hold($listing);
        $component = Livewire::actingAs($renter)->test(Index::class)->assertSee('Join waitlist for saved dates');
        $component->call('joinWaitlist', $item->id)->assertHasNoErrors();
        $this->assertDatabaseCount('rental_waitlist_entries', 1);
        $hold->update(['status' => 'cancelled']);
        $component->call('$refresh')->assertDontSee('Join waitlist for saved dates');
        $component->call('joinWaitlist', $item->id)->assertHasErrors(['waitlist']);
    }

    public function test_paused_unverified_or_invalid_saved_period_cannot_be_waitlisted(): void
    {
        $renter = User::factory()->identityVerified()->create();
        $listing = $this->listing();
        $this->hold($listing);
        $this->assertTrue(RentalWaitlist::canJoin($renter, $listing->id, $this->details()));
        $this->assertFalse(RentalWaitlist::canJoin(User::factory()->create(), $listing->id, $this->details()));
        $this->assertFalse(RentalWaitlist::canJoin($renter, $listing->id, ['start_date' => 'invalid']));
        $listing->update(['reopen_required' => true]);
        $this->assertFalse(RentalWaitlist::canJoin($renter, $listing->id, $this->details()));
        $listing->update(['reopen_required' => false]);
        $listing->owner->forceFill(['identity_verified_at' => null])->save();
        $this->assertFalse(RentalWaitlist::canJoin($renter, $listing->id, $this->details()));
    }

    public function test_unsaved_cart_edits_do_not_control_waitlist_for_saved_dates(): void
    {
        $renter = User::factory()->identityVerified()->create();
        $listing = $this->listing();
        $item = RentalCart::save($renter, $listing->id, $this->details());
        $this->hold($listing);
        Livewire::actingAs($renter)->test(Index::class)
            ->set('edits.'.$item->id.'.start_date', now()->addDays(6)->toDateString())
            ->assertSee('Join waitlist for saved dates')->call('joinWaitlist', $item->id)->assertHasNoErrors();
        $this->assertDatabaseHas('rental_waitlist_entries', ['user_id' => $renter->id]);
        $this->assertSame($this->details()['start_date'], RentalWaitlistEntry::where('user_id', $renter->id)->first()->details['start_date']);
    }

    public function test_request_form_also_consolidates_notices_and_only_offers_waitlist_for_valid_conflicts(): void
    {
        $renter = User::factory()->identityVerified()->create();
        $listing = $this->listing();
        $component = Livewire::actingAs($renter)->test(Create::class, ['listing' => $listing])
            ->set('rental_unit', 'hour')->set('start_date', $this->details()['start_date'])
            ->set('end_date', $this->details()['end_date'])->set('start_time', '09:00')->set('end_time', '11:00')
            ->assertDontSee('Join waitlist for these dates');
        $this->hold($listing);
        $component->call('$refresh')->assertSee('Join waitlist for these dates');
        $listing->owner->forceFill(['identity_verified_at' => null])->save();
        $renter->forceFill(['identity_verified_at' => null])->save();
        $component->call('$refresh')->assertDontSee('Join waitlist for these dates')
            ->assertSee('This owner has not completed ID verification.')->assertSee('You can browse and save rental drafts.');
        $this->assertSame(1, substr_count($component->html(), 'id="rental-owner-verification-notice"'));
    }
}
