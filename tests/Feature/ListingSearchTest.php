<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Livewire\Listings\Browse;
use App\Livewire\Listings\Map;
use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ListingSearchTest extends TestCase
{
    use RefreshDatabase;

    private ?User $defaultOwner = null;

    private ?Category $defaultCategory = null;

    private function publishedListing(array $overrides = []): Listing
    {
        $this->defaultOwner ??= User::factory()->owner()->create();
        $this->defaultCategory ??= Category::create(['name' => 'Tools', 'slug' => 'tools']);

        return Listing::create(array_merge([
            'owner_id' => $this->defaultOwner->id,
            'category_id' => $this->defaultCategory->id,
            'name' => 'Generic Item',
            'description' => 'A generic rentable item.',
            'condition' => 'good',
            'price_per_day' => 100,
            'location' => 'Manila',
            'max_rental_duration_days' => 7,
            'status' => ListingStatus::Published,
            'is_available' => true,
        ], $overrides));
    }

    public function test_unpublished_and_unavailable_listings_never_appear_in_browse(): void
    {
        $this->publishedListing(['name' => 'Visible Drill', 'status' => ListingStatus::Published, 'is_available' => true]);
        $this->publishedListing(['name' => 'Pending Drill', 'status' => ListingStatus::PendingApproval]);
        $this->publishedListing(['name' => 'Unavailable Drill', 'status' => ListingStatus::Published, 'is_available' => false]);

        Livewire::test(Browse::class)
            ->assertSee('Visible Drill')
            ->assertDontSee('Pending Drill')
            ->assertDontSee('Unavailable Drill');
    }

    public function test_keyword_search_matches_name_description_and_brand(): void
    {
        $this->publishedListing(['name' => 'Bosch Pressure Washer', 'brand' => 'Bosch']);
        $this->publishedListing(['name' => 'Karcher Washer', 'brand' => 'Karcher']);

        Livewire::test(Browse::class)
            ->set('keyword', 'Bosch')
            ->assertSee('Bosch Pressure Washer')
            ->assertDontSee('Karcher Washer');
    }

    public function test_category_filter_includes_subcategory_matches(): void
    {
        $parent = Category::create(['name' => 'Photography', 'slug' => 'photography']);
        $child = Category::create(['name' => 'Cameras', 'slug' => 'cameras', 'parent_id' => $parent->id]);

        $this->publishedListing(['name' => 'DSLR Camera', 'category_id' => $child->id, 'subcategory_id' => null]);
        $this->publishedListing(['name' => 'Unrelated Tool']);

        Livewire::test(Browse::class)
            ->set('category', (string) $child->id)
            ->assertSee('DSLR Camera')
            ->assertDontSee('Unrelated Tool');
    }

    public function test_price_range_filter(): void
    {
        $this->publishedListing(['name' => 'Cheap Item', 'price_per_day' => 50]);
        $this->publishedListing(['name' => 'Expensive Item', 'price_per_day' => 900]);

        Livewire::test(Browse::class)
            ->set('minPrice', 100)
            ->set('maxPrice', 1000)
            ->assertSee('Expensive Item')
            ->assertDontSee('Cheap Item');
    }

    public function test_condition_filter(): void
    {
        $this->publishedListing(['name' => 'Like New Item', 'condition' => 'like_new']);
        $this->publishedListing(['name' => 'Fair Item', 'condition' => 'fair']);

        Livewire::test(Browse::class)
            ->set('condition', 'like_new')
            ->assertSee('Like New Item')
            ->assertDontSee('Fair Item');
    }

    public function test_cheapest_sort_orders_ascending_by_price(): void
    {
        $this->publishedListing(['name' => 'Mid Price', 'price_per_day' => 200]);
        $this->publishedListing(['name' => 'Lowest Price', 'price_per_day' => 50]);
        $this->publishedListing(['name' => 'Highest Price', 'price_per_day' => 900]);

        $rendered = Livewire::test(Browse::class)->set('sort', 'cheapest')->html();

        $this->assertTrue(
            strpos($rendered, 'Lowest Price') < strpos($rendered, 'Mid Price')
            && strpos($rendered, 'Mid Price') < strpos($rendered, 'Highest Price')
        );
    }

    public function test_popular_sort_orders_by_views_count_descending(): void
    {
        $this->publishedListing(['name' => 'Rarely Viewed', 'views_count' => 2]);
        $this->publishedListing(['name' => 'Most Viewed', 'views_count' => 500]);

        $rendered = Livewire::test(Browse::class)->set('sort', 'popular')->html();

        $this->assertTrue(strpos($rendered, 'Most Viewed') < strpos($rendered, 'Rarely Viewed'));
    }

    public function test_map_only_includes_listings_with_coordinates(): void
    {
        $this->publishedListing(['name' => 'Pinned Item', 'latitude' => 14.5995, 'longitude' => 120.9842]);
        $this->publishedListing(['name' => 'Unpinned Item', 'latitude' => null, 'longitude' => null]);

        $markers = Livewire::test(Map::class)->get('markers');

        $this->assertCount(1, $markers);
        $this->assertSame('Pinned Item', $markers[0]['name']);
    }

    public function test_map_radius_filter_excludes_listings_beyond_the_radius(): void
    {
        // Manila
        $this->publishedListing(['name' => 'Nearby Item', 'latitude' => 14.5995, 'longitude' => 120.9842]);
        // Tagaytay, ~55km away
        $this->publishedListing(['name' => 'Far Item', 'latitude' => 14.0997, 'longitude' => 120.9425]);

        $markers = Livewire::test(Map::class)
            ->set('centerLat', 14.5995)
            ->set('centerLng', 120.9842)
            ->set('radiusKm', 10)
            ->get('markers');

        $this->assertCount(1, $markers);
        $this->assertSame('Nearby Item', $markers[0]['name']);
    }
}
