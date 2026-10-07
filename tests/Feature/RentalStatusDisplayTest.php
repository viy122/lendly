<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Enums\RentalStatus;
use App\Livewire\Owner\Rentals\Index as OwnerRentalsIndex;
use App\Livewire\Owner\Rentals\Show as OwnerRentalShow;
use App\Livewire\Renter\Rentals\Index as RenterRentalsIndex;
use App\Livewire\Renter\Rentals\Show as RenterRentalShow;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Rental;
use App\Models\RentalRequest;
use App\Models\SecurityDeposit;
use App\Models\User;
use App\Services\RentalLifecycle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * FR-19: "the system shall track and display the real-time status of an
 * active rental (e.g., Ongoing, Due Soon, Overdue)". Overdue is a real
 * stored RentalStatus (see RentalLifecycle::markOverdueRentals()) — Due
 * Soon is deliberately a derived display state instead (see Rental::
 * isDueSoon()), since it's just "Active, but close to the end date."
 */
class RentalStatusDisplayTest extends TestCase
{
    use RefreshDatabase;

    private function listingFor(User $owner): Listing
    {
        $category = Category::firstOrCreate(['slug' => 'status-display-tools'], ['name' => 'Status Display Tools']);

        return Listing::create([
            'owner_id' => $owner->id,
            'category_id' => $category->id,
            'name' => 'Status Display Item',
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

    private function rentalWithStatus(User $owner, User $renter, Listing $listing, RentalStatus $status, $startDate, $endDate): Rental
    {
        $request = RentalRequest::create([
            'listing_id' => $listing->id,
            'renter_id' => $renter->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'rental_days' => 3,
            'fulfillment_method' => 'pickup',
            'rental_fee' => 300,
            'commission_rate' => 10,
            'commission_amount' => 30,
            'security_deposit' => 500,
            'total_amount' => 830,
            'status' => 'approved',
        ]);

        return Rental::create([
            'rental_request_id' => $request->id,
            'listing_id' => $listing->id,
            'owner_id' => $owner->id,
            'renter_id' => $renter->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'rental_days' => 3,
            'fulfillment_method' => 'pickup',
            'rental_fee' => 300,
            'commission_rate' => 10,
            'commission_amount' => 30,
            'security_deposit' => 500,
            'total_amount' => 830,
            'status' => $status,
            'paid_at' => $status !== RentalStatus::PaymentPending ? now() : null,
        ]);
    }

    public function test_active_rental_due_within_2_days_displays_as_due_soon(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $listing = $this->listingFor($owner);

        $dueSoon = $this->rentalWithStatus($owner, $renter, $listing, RentalStatus::Active, now()->subDays(2), now()->addDay());
        $this->assertTrue($dueSoon->isDueSoon());
        $this->assertSame('Due Soon', $dueSoon->displayStatusLabel());

        $notDueSoon = $this->rentalWithStatus($owner, $renter, $listing, RentalStatus::Active, now()->subDay(), now()->addDays(10));
        $this->assertFalse($notDueSoon->isDueSoon());
        $this->assertSame('Active Rental', $notDueSoon->displayStatusLabel());
    }

    public function test_overdue_and_completed_rentals_are_never_due_soon(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $listing = $this->listingFor($owner);

        $overdue = $this->rentalWithStatus($owner, $renter, $listing, RentalStatus::Overdue, now()->subDays(5), now()->subDay());
        $this->assertFalse($overdue->isDueSoon());
        $this->assertSame('Overdue', $overdue->displayStatusLabel());

        $completed = $this->rentalWithStatus($owner, $renter, $listing, RentalStatus::Completed, now()->subDays(10), now()->subDays(7));
        $this->assertFalse($completed->isDueSoon());
    }

    public function test_due_soon_badge_renders_on_the_renter_and_owner_rental_pages(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $listing = $this->listingFor($owner);
        $rental = $this->rentalWithStatus($owner, $renter, $listing, RentalStatus::Active, now()->subDays(2), now()->addDay());

        $this->actingAs($renter)->get(route('renter.rentals.show', $rental))->assertOk()->assertSee('Due Soon');
        $this->actingAs($owner)->get(route('owner.rentals.show', $rental))->assertOk()->assertSee('Due Soon');
    }

    public static function rentalPages(): array
    {
        return [
            'owner details' => [OwnerRentalShow::class, true, true],
            'renter details' => [RenterRentalShow::class, false, true],
            'owner list' => [OwnerRentalsIndex::class, true, false],
            'renter list' => [RenterRentalsIndex::class, false, false],
        ];
    }

    #[DataProvider('rentalPages')]
    public function test_open_pages_update_due_soon_and_overdue_without_the_scheduler(string $component, bool $isOwner, bool $details): void
    {
        $this->travelTo(now()->startOfDay()->addHours(12));
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->listingFor($owner);
        $rental = $this->rentalWithStatus($owner, $renter, $listing, RentalStatus::Active, now()->subDay(), now()->addDays(3));
        $page = Livewire::actingAs($isOwner ? $owner : $renter)
            ->test($component, $details ? ['rental' => $rental] : [])
            ->assertSee('wire:poll.5s.keep-alive', false)
            ->assertSee('Active Rental')->assertDontSee('Due Soon');

        $this->travel(1)->days();
        $page->call('$refresh')->assertSee('Due Soon');
        $this->assertSame(RentalStatus::Active, $rental->fresh()->status);

        // The item remains due soon through the whole agreed end date.
        $this->travelTo($rental->end_date->copy()->endOfDay());
        $page->call('$refresh')->assertSee('Due Soon');
        $this->assertSame(RentalStatus::Active, $rental->fresh()->status);

        $this->travel(1)->seconds();
        $page->call('$refresh')->assertSee('Overdue');
        $this->assertSame(RentalStatus::Overdue, $rental->fresh()->status);
        $this->assertSame(1, $rental->fresh()->days_overdue);
        $this->assertSame('100.00', $rental->fresh()->late_fee);
        $this->assertSame(1, $owner->notifications()->where('data->type', 'rental_overdue')->count());
        $this->assertSame(1, $renter->notifications()->where('data->type', 'rental_overdue')->count());

        // Daily late fees use the agreed booking rate, even if the owner edits
        // the listing price while a rental page is continuously open.
        $listing->update(['price_per_day' => 999]);
        $this->travel(2)->days();
        $page->call('$refresh')->assertSee('Overdue');
        $this->assertSame(3, $rental->fresh()->days_overdue);
        $this->assertSame('300.00', $rental->fresh()->late_fee);
        $this->assertSame(1, $owner->notifications()->where('data->type', 'rental_overdue')->count());
        $this->assertSame(1, $renter->notifications()->where('data->type', 'rental_overdue')->count());
    }

    #[DataProvider('rentalPages')]
    public function test_open_pages_reload_status_changes_from_another_session(string $component, bool $isOwner, bool $details): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->listingFor($owner);
        $rental = $this->rentalWithStatus($owner, $renter, $listing, RentalStatus::Paid, now(), now()->addDays(7));
        SecurityDeposit::create(['rental_id' => $rental->id, 'amount' => $rental->security_deposit]);
        $page = Livewire::actingAs($isOwner ? $owner : $renter)->test($component, $details ? ['rental' => $rental] : [])->assertSee('Paid');

        $rental->update(['status' => RentalStatus::Active, 'pickup_confirmed_by_owner_at' => now(), 'pickup_confirmed_by_renter_at' => now()]);
        $page->call('$refresh')->assertSee('Active Rental');
        $rental->update(['status' => RentalStatus::Returned, 'return_confirmed_by_owner_at' => now(), 'return_confirmed_by_renter_at' => now()]);
        $page->call('$refresh')->assertSee('Returned');
        $rental->update(['status' => RentalStatus::Completed, 'completed_at' => now()]);
        $page->call('$refresh')->assertSee('Completed');

        $this->travel(10)->days();
        $page->call('$refresh')->assertSee('Completed');
        $this->assertSame(RentalStatus::Completed, $rental->fresh()->status);
        $this->assertSame(0, $rental->fresh()->days_overdue);
        $this->assertSame(0, $owner->notifications()->count() + $renter->notifications()->count());
    }

    public function test_polling_both_sessions_and_running_scheduler_sends_overdue_notifications_only_once(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->listingFor($owner);
        $rental = $this->rentalWithStatus($owner, $renter, $listing, RentalStatus::Active, now()->subDays(4), now()->subDay());

        $ownerPage = Livewire::actingAs($owner)->test(OwnerRentalShow::class, ['rental' => $rental])->assertSee('Overdue');
        $renterPage = Livewire::actingAs($renter)->test(RenterRentalShow::class, ['rental' => $rental])->assertSee('Overdue');
        $updatedAt = $rental->fresh()->updated_at->toDateTimeString();
        $this->travel(1)->minutes();
        $this->actingAs($owner);
        $ownerPage->call('$refresh');
        $this->actingAs($renter);
        $renterPage->call('$refresh');
        $this->artisan('rentals:check-overdue')->expectsOutput('Flagged 0 rental(s) as newly overdue.')->assertSuccessful();

        $this->assertSame($updatedAt, $rental->fresh()->updated_at->toDateTimeString());
        $this->assertSame(1, $owner->notifications()->where('data->type', 'rental_overdue')->count());
        $this->assertSame(1, $renter->notifications()->where('data->type', 'rental_overdue')->count());
    }

    public function test_owner_overdue_filter_updates_before_filtering_and_does_not_touch_other_accounts(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $otherOwner = User::factory()->owner()->create();
        $otherRenter = User::factory()->renter()->create();
        $listing = $this->listingFor($owner);
        $otherListing = $this->listingFor($otherOwner);
        $otherListing->update(['name' => 'Private unrelated rental']);
        $mine = $this->rentalWithStatus($owner, $renter, $listing, RentalStatus::Active, now()->subDays(4), now()->subDay());
        $other = $this->rentalWithStatus($otherOwner, $otherRenter, $otherListing, RentalStatus::Active, now()->subDays(4), now()->subDay());

        Livewire::actingAs($owner)->test(OwnerRentalsIndex::class)->set('filter', 'overdue')
            ->assertSee('Status Display Item')->assertDontSee('Private unrelated rental');
        $this->assertSame(RentalStatus::Overdue, $mine->fresh()->status);
        $this->assertSame(RentalStatus::Active, $other->fresh()->status);
        $this->assertSame(0, $otherOwner->notifications()->count() + $otherRenter->notifications()->count());
    }

    public function test_polling_preserves_unsaved_form_input(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->listingFor($owner);
        $rental = $this->rentalWithStatus($owner, $renter, $listing, RentalStatus::Active, now()->subDay(), now()->addDays(5));

        Livewire::actingAs($owner)->test(OwnerRentalShow::class, ['rental' => $rental])
            ->set('after_notes', 'Inspection draft')->set('showDisputeForm', true)->call('$refresh')
            ->assertSet('after_notes', 'Inspection draft')->assertSet('showDisputeForm', true);
        Livewire::actingAs($renter)->test(RenterRentalShow::class, ['rental' => $rental])
            ->set('dispute_description', 'Dispute draft')->set('showDisputeForm', true)->call('$refresh')
            ->assertSet('dispute_description', 'Dispute draft')->assertSet('showDisputeForm', true);
    }

    public static function inactiveStatuses(): array
    {
        return ['unpaid' => [RentalStatus::PaymentPending], 'paid' => [RentalStatus::Paid],
            'returned' => [RentalStatus::Returned], 'completed' => [RentalStatus::Completed], 'cancelled' => [RentalStatus::Cancelled]];
    }

    #[DataProvider('inactiveStatuses')]
    public function test_synchronization_rechecks_saved_status_and_does_not_overwrite_closed_or_unstarted_rentals(RentalStatus $status): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $listing = $this->listingFor($owner);
        $staleRental = $this->rentalWithStatus($owner, $renter, $listing, RentalStatus::Active, now()->subDays(4), now()->subDay());
        Rental::whereKey($staleRental->id)->update(['status' => $status->value]);

        $this->assertFalse(RentalLifecycle::synchronizeRentalStatus($staleRental));
        $this->assertSame($status, $staleRental->fresh()->status);
        $this->assertSame(0, $staleRental->fresh()->days_overdue);
        $this->assertSame(0, $owner->notifications()->count() + $renter->notifications()->count());
    }

    public function test_unauthorized_page_cannot_trigger_status_updates(): void
    {
        $owner = User::factory()->owner()->create();
        $renter = User::factory()->renter()->create();
        $stranger = User::factory()->renter()->create();
        $listing = $this->listingFor($owner);
        $rental = $this->rentalWithStatus($owner, $renter, $listing, RentalStatus::Active, now()->subDays(4), now()->subDay());
        Livewire::actingAs($stranger)->test(RenterRentalShow::class, ['rental' => $rental])->assertForbidden();
        Livewire::actingAs($stranger)->test(RenterRentalsIndex::class)->assertDontSee('Status Display Item');
        $this->assertSame(RentalStatus::Active, $rental->fresh()->status);
        $this->assertSame(0, $owner->notifications()->count() + $renter->notifications()->count());
    }
}
