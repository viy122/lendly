<?php

namespace Tests\Feature;

use App\Livewire\Owner\RentalRequests\Index as OwnerRentalRequestsIndex;
use App\Livewire\RentalRequests\Create as RentalRequestCreate;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Rental;
use App\Models\RentalRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TermsAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    private function listingFor(User $owner): Listing
    {
        $category = Category::create(['name' => 'Terms Test Tools', 'slug' => 'terms-test-tools']);

        return Listing::create([
            'owner_id' => $owner->id,
            'category_id' => $category->id,
            'name' => 'Terms Test Item',
            'description' => 'x',
            'condition' => 'good',
            'price_per_day' => 100,
            'security_deposit' => 500,
            'location' => 'Manila',
            'max_rental_duration_days' => 10,
            'status' => 'published',
            'is_available' => true,
            'pickup_available' => true,
        ]);
    }

    public function test_renter_cannot_submit_a_request_without_accepting_terms(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $listing = $this->listingFor($owner);

        Livewire::actingAs($renter)
            ->test(RentalRequestCreate::class, ['listing' => $listing])
            ->set('start_date', now()->addDays(2)->toDateString())
            ->set('end_date', now()->addDays(3)->toDateString())
            ->set('accept_terms', false)
            ->call('submit')
            ->assertHasErrors(['accept_terms']);

        $this->assertSame(0, RentalRequest::count());
    }

    public function test_renter_accepting_terms_records_a_timestamp(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $listing = $this->listingFor($owner);

        Livewire::actingAs($renter)
            ->test(RentalRequestCreate::class, ['listing' => $listing])
            ->set('start_date', now()->addDays(2)->toDateString())
            ->set('end_date', now()->addDays(3)->toDateString())
            ->set('accept_terms', true)
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertNotNull(RentalRequest::first()->renter_terms_accepted_at);
    }

    public function test_owner_cannot_approve_without_accepting_terms(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $listing = $this->listingFor($owner);

        $request = RentalRequest::create([
            'listing_id' => $listing->id,
            'renter_id' => $renter->id,
            'start_date' => now()->addDays(2),
            'end_date' => now()->addDays(3),
            'rental_days' => 2,
            'fulfillment_method' => 'pickup',
            'rental_fee' => 200,
            'commission_rate' => 10,
            'commission_amount' => 20,
            'security_deposit' => 500,
            'total_amount' => 720,
            'status' => 'requested',
            'renter_terms_accepted_at' => now(),
        ]);

        Livewire::actingAs($owner)
            ->test(OwnerRentalRequestsIndex::class)
            ->set('accept_terms', false)
            ->call('approve', $request->id)
            ->assertHasErrors(['accept_terms']);

        $this->assertSame('requested', $request->fresh()->status->value);
        $this->assertSame(0, Rental::count(), 'no rental/booking should be created until the owner accepts too');
    }

    public function test_owner_accepting_terms_approves_and_records_a_timestamp(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $listing = $this->listingFor($owner);

        $request = RentalRequest::create([
            'listing_id' => $listing->id,
            'renter_id' => $renter->id,
            'start_date' => now()->addDays(2),
            'end_date' => now()->addDays(3),
            'rental_days' => 2,
            'fulfillment_method' => 'pickup',
            'rental_fee' => 200,
            'commission_rate' => 10,
            'commission_amount' => 20,
            'security_deposit' => 500,
            'total_amount' => 720,
            'status' => 'requested',
            'renter_terms_accepted_at' => now(),
        ]);

        Livewire::actingAs($owner)
            ->test(OwnerRentalRequestsIndex::class)
            ->set('accept_terms', true)
            ->call('approve', $request->id)
            ->assertHasNoErrors();

        $request->refresh();
        $this->assertSame('approved', $request->status->value);
        $this->assertNotNull($request->owner_terms_accepted_at);
        $this->assertSame(1, Rental::count());
    }
}
