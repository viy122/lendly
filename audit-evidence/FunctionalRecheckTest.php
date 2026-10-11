<?php

use App\Livewire\Owner\Listings\Form;
use App\Livewire\Owner\RentalRequests\Index as OwnerRequests;
use App\Livewire\RentalRequests\Create;
use App\Models\Category;
use App\Models\Listing;
use App\Models\RentalRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

// Mixed checks: remaining-gap checks reproduce limitations; the zero-location check verifies preservation.
class FunctionalRecheckTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $owner = User::factory()->create();
        $renter = User::factory()->unverified()->create();
        $category = Category::create(['name' => 'Recheck Tools', 'slug' => 'recheck-tools']);
        $listing = Listing::create([
            'owner_id' => $owner->id, 'category_id' => $category->id,
            'name' => 'Recheck Item', 'description' => 'Recheck fixture', 'condition' => 'good',
            'price_per_day' => 100, 'security_deposit' => 500,
            'location' => 'Test location', 'latitude' => 0.0, 'longitude' => 120.9842,
            'max_rental_duration_days' => 10, 'status' => 'published', 'is_available' => true,
        ]);
        return [$owner, $renter, $listing];
    }

    public function test_remaining_gap_unverified_renter_can_submit(): void
    {
        [, $renter, $listing] = $this->fixture();
        $this->actingAs($renter)->withSession(['active_interface' => 'renter']);
        $this->get(route('renter.rental-requests.create', $listing))->assertOk();
        Livewire::test(Create::class, ['listing' => $listing])->call('submit')->assertHasNoErrors();
        $this->assertSame(1, RentalRequest::count());
        $this->assertNull($renter->fresh()->email_verified_at);
    }

    public function test_remaining_gap_authenticated_session_without_interface_can_access_both_areas(): void
    {
        [$owner] = $this->fixture();
        $this->actingAs($owner);
        session()->forget('active_interface');
        $this->get('/owner/listings')->assertOk();
        $this->get('/renter/rental-requests')->assertOk();
        $this->assertFalse(session()->has('active_interface'));
    }

    public function test_zero_coordinate_is_preserved_when_editing(): void
    {
        [$owner, , $listing] = $this->fixture();
        $this->actingAs($owner)->withSession(['active_interface' => 'owner']);
        $form = Livewire::test(Form::class, ['listing' => $listing]);
        $this->assertSame(0.0, $form->get('latitude'));
        $form->call('save')->assertHasNoErrors();
        $this->assertSame(0.0, (float) $listing->fresh()->latitude);
    }

    public function test_remaining_gap_owner_can_approve_a_request_after_listing_is_paused(): void
    {
        [$owner, $renter, $listing] = $this->fixture();
        $this->actingAs($renter)->withSession(['active_interface' => 'renter']);
        Livewire::test(Create::class, ['listing' => $listing])->call('submit')->assertHasNoErrors();
        $request = RentalRequest::firstOrFail();
        $listing->update(['status' => 'inactive', 'is_available' => false]);
        $this->actingAs($owner)->withSession(['active_interface' => 'owner']);
        Livewire::test(OwnerRequests::class)->call('approve', $request->id)->assertHasNoErrors();
        $this->assertSame('approved', $request->fresh()->status->value);
    }
}
