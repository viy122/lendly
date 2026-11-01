<?php

namespace Tests\Feature;

use App\Livewire\Owner\Rentals\Show as OwnerRental;
use App\Livewire\Renter\Rentals\Show as RenterRental;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Rental;
use App\Models\RentalRequest;
use App\Models\SecurityDeposit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PhaseSevenJourneyTest extends TestCase
{
    use RefreshDatabase;

    public static function staleDisputeScreens(): array
    {
        return ['renter to stranger' => [false, false], 'owner to stranger' => [true, false],
            'renter to admin' => [false, true], 'owner to admin' => [true, true]];
    }

    #[DataProvider('staleDisputeScreens')]
    public function test_stale_dispute_forms_cannot_write_after_the_authenticated_account_changes(bool $ownerScreen, bool $administrator): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $listing = Listing::create(['owner_id' => $owner->id,
            'category_id' => Category::create(['name' => 'Journey tools', 'slug' => 'journey-tools'])->id,
            'name' => 'Journey camera', 'description' => 'With case', 'condition' => 'good',
            'location' => 'Manila', 'price_per_day' => 100, 'status' => 'published']);
        $details = ['listing_id' => $listing->id, 'renter_id' => $renter->id,
            'start_date' => now()->addDays(3), 'end_date' => now()->addDays(4), 'rental_days' => 2,
            'fulfillment_method' => 'pickup', 'rental_fee' => 200, 'commission_rate' => 10,
            'commission_amount' => 20, 'security_deposit' => 500, 'total_amount' => 720];
        $request = RentalRequest::create($details + ['status' => 'approved']);
        $rental = Rental::create($details + ['rental_request_id' => $request->id, 'owner_id' => $owner->id, 'status' => 'completed']);
        SecurityDeposit::create(['rental_id' => $rental->id, 'amount' => 500, 'status' => 'return_eligible']);
        $component = Livewire::actingAs($ownerScreen ? $owner : $renter)->test($ownerScreen ? OwnerRental::class : RenterRental::class, ['rental' => $rental])
            ->set('dispute_reason', 'incorrect_charge')->set('dispute_description', 'A stale form must not create an issue.');
        $replacement = $administrator ? User::factory()->admin()->create() : User::factory()->create();
        $this->actingAs($replacement);
        $component->call('createDispute')->assertForbidden();
        $this->assertDatabaseCount('disputes', 0);
        $this->assertDatabaseCount('notifications', 0);
    }
}
