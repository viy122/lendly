<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentalCartAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_requires_a_verified_active_renter_interface(): void
    {
        $url = route('renter.cart.index');
        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs(User::factory()->unverified()->create())->get($url)->assertRedirect(route('verification.notice'));
        $this->actingAs(User::factory()->admin()->create())->get($url)->assertForbidden();
        $this->actingAs(User::factory()->create(['status' => 'suspended']))->get($url)->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->withSession(['active_interface' => 'owner'])->get($url)->assertForbidden();
        $this->withSession(['active_interface' => 'renter'])->get($url)->assertOk()->assertSee('My cart');
    }

    public function test_header_count_and_cart_contents_are_scoped_to_the_signed_in_member(): void
    {
        $renter = User::factory()->create();
        $own = $this->draft($renter, 'Visible cart item');
        $this->draft(User::factory()->create(), 'Someone else cart item');

        $this->actingAs($renter)->get(route('renter.cart.index'))->assertOk()
            ->assertSee('My cart, 1 items', false)->assertSee($own->listing->name)
            ->assertDontSee('Someone else cart item')->assertSee('My Cart');
    }

    public function test_closing_account_removes_its_cart_without_removing_other_members_drafts(): void
    {
        $renter = User::factory()->create();
        $own = $this->draft($renter, 'Personal draft');
        $other = $this->draft(User::factory()->create(), 'Other draft');

        $renter->closeAccount();

        $this->assertModelMissing($own);
        $this->assertModelExists($other);
        $this->assertDatabaseCount('rental_requests', 0);
    }

    private function draft(User $user, string $name): CartItem
    {
        $category = Category::firstOrCreate(['slug' => 'cart-access'], ['name' => 'Cart tools']);
        $listing = Listing::create([
            'owner_id' => User::factory()->create()->id, 'category_id' => $category->id, 'name' => $name,
            'description' => 'Tool', 'condition' => 'good', 'location' => 'Manila',
            'price_per_day' => 100, 'security_deposit' => 200, 'max_rental_duration_days' => 10,
            'status' => 'published', 'is_available' => true, 'pickup_available' => true, 'delivery_available' => false,
        ]);

        return CartItem::create([
            'user_id' => $user->id, 'listing_id' => $listing->id,
            'start_date' => now()->addDay(), 'end_date' => now()->addDays(2), 'fulfillment_method' => 'pickup',
            'terms' => ['price_per_day' => '100.00', 'security_deposit' => '200.00', 'rental_rules' => null, 'commission_rate' => '10.00'],
        ]);
    }
}
