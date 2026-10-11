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
use PHPUnit\Framework\Attributes\DataProvider;
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

    public function test_location_filter_matches_part_of_the_address_and_combines_with_other_filters(): void
    {
        $this->publishedListing(['name' => 'Matching Bosch Drill', 'brand' => 'Bosch', 'location' => 'Barangay Poblacion, Makati City', 'price_per_day' => 200]);
        $this->publishedListing(['name' => 'Manila Bosch Drill', 'brand' => 'Bosch', 'location' => 'Manila', 'price_per_day' => 200]);
        $this->publishedListing(['name' => 'Costly Bosch Drill', 'brand' => 'Bosch', 'location' => 'Makati', 'price_per_day' => 900]);
        $this->publishedListing(['name' => 'Makati Tent', 'location' => 'Makati', 'price_per_day' => 200]);
        $this->publishedListing(['name' => 'Hidden Bosch Drill', 'location' => 'Makati', 'status' => ListingStatus::PendingApproval]);
        $this->publishedListing(['name' => 'Unavailable Bosch Drill', 'location' => 'Makati', 'is_available' => false]);

        Livewire::test(Browse::class)
            ->assertSeeHtml('aria-label="Filter by city or area"')
            ->set('keyword', 'Bosch')
            ->set('category', (string) $this->defaultCategory->id)
            ->set('minPrice', 100)
            ->set('maxPrice', 300)
            ->set('location', '  Makati  ')
            ->assertSee('Matching Bosch Drill')
            ->assertDontSee('Manila Bosch Drill')
            ->assertDontSee('Costly Bosch Drill')
            ->assertDontSee('Makati Tent')
            ->assertDontSee('Hidden Bosch Drill')
            ->assertDontSee('Unavailable Bosch Drill')
            ->set('location', 'No such area')
            ->assertSee('No listings match your filters');
    }

    public function test_location_can_be_loaded_from_the_url_and_cleared_with_all_filters(): void
    {
        $this->publishedListing(['name' => 'Makati Camera', 'location' => 'Makati']);
        $this->publishedListing(['name' => 'Manila Camera', 'location' => 'Manila']);

        $page = Livewire::withQueryParams(['location' => 'Makati'])->test(Browse::class)
            ->assertSet('location', 'Makati')
            ->assertSee('Makati Camera')->assertDontSee('Manila Camera');

        $page->set('keyword', 'Camera')->set('minPrice', 50)->set('maxPrice', 150)
            ->set('condition', 'good')->set('sort', 'cheapest')->call('resetFilters')
            ->assertSet('location', '')->assertSet('keyword', '')->assertSet('category', '')
            ->assertSet('condition', '')->assertSet('brand', '')
            ->assertSet('minPrice', null)->assertSet('maxPrice', null)->assertSet('sort', 'recent')
            ->assertSee('Makati Camera')->assertSee('Manila Camera');
    }

    public function test_changing_location_resets_pagination_and_blank_location_keeps_browsing_available(): void
    {
        for ($i = 1; $i <= 13; $i++) {
            $this->publishedListing(['name' => 'Manila Item '.$i]);
        }
        $this->publishedListing(['name' => 'Only Makati Item', 'location' => 'Makati']);

        Livewire::test(Browse::class)->call('setPage', 2)->assertSet('paginators.page', 2)
            ->set('location', 'Makati')->assertSet('paginators.page', 1)->assertSee('Only Makati Item')
            ->assertViewHas('listings', fn ($listings) => $listings->total() === 1)
            ->set('location', '   ')->assertViewHas('listings', fn ($listings) => $listings->total() === 14);
    }

    public function test_zero_maximum_price_is_applied_instead_of_being_ignored(): void
    {
        $this->publishedListing(['name' => 'Paid drill', 'price_per_day' => 100]);

        Livewire::test(Browse::class)->set('maxPrice', 0)
            ->assertDontSee('Paid drill')->assertSee('No listings match your filters');
    }

    public function test_map_and_browse_apply_the_same_keyword_and_category_filters(): void
    {
        $otherCategory = Category::create(['name' => 'Outdoors', 'slug' => 'outdoors']);
        $child = Category::create(['name' => 'Power Tools', 'slug' => 'power-tools', 'parent_id' => $otherCategory->id]);
        $coordinates = ['latitude' => 0, 'longitude' => 0];
        $this->publishedListing(array_merge($coordinates, ['name' => 'Name match Bosch', 'subcategory_id' => $child->id]));
        $this->publishedListing(array_merge($coordinates, ['name' => 'Description match', 'description' => 'Bosch equipment', 'subcategory_id' => $child->id]));
        $this->publishedListing(array_merge($coordinates, ['name' => 'Brand match', 'brand' => 'Bosch', 'category_id' => $child->id]));
        $this->publishedListing(array_merge($coordinates, ['name' => 'Wrong category Bosch', 'category_id' => $otherCategory->id]));
        $this->publishedListing(array_merge($coordinates, ['name' => 'Wrong keyword', 'category_id' => $child->id]));
        $this->publishedListing(array_merge($coordinates, ['name' => 'Pending Bosch', 'category_id' => $child->id, 'status' => ListingStatus::PendingApproval]));
        $this->publishedListing(array_merge($coordinates, ['name' => 'Unavailable Bosch', 'category_id' => $child->id, 'is_available' => false]));

        foreach ([Browse::class, Map::class] as $component) {
            Livewire::test($component)->set('keyword', 'Bosch')->set('category', (string) $child->id)
                ->assertSee('Name match Bosch')->assertSee('Description match')->assertSee('Brand match')
                ->assertDontSee('Wrong category Bosch')->assertDontSee('Wrong keyword')
                ->assertDontSee('Pending Bosch')->assertDontSee('Unavailable Bosch');
        }
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

    public function test_map_pins_include_the_first_photo_daily_price_distance_and_detail_url(): void
    {
        $listing = $this->publishedListing(['name' => 'Camera & Tripod', 'price_per_day' => 1234.5, 'latitude' => 14.5995, 'longitude' => 120.9842]);
        $listing->images()->create(['path' => 'listings/second.jpg', 'sort_order' => 2]);
        $first = $listing->images()->create(['path' => 'listings/first.jpg', 'sort_order' => 1]);
        $markers = Livewire::test(Map::class)->set('centerLat', 14.5995)->set('centerLng', 120.9842)->get('markers');

        $this->assertSame($listing->id, $markers[0]['id']);
        $this->assertSame('Camera & Tripod', $markers[0]['name']);
        $this->assertSame('1,234.50', $markers[0]['price']);
        $this->assertSame($first->url(), $markers[0]['image']);
        $this->assertSame('0.0 km away', $markers[0]['distance']);
        $this->assertSame(route('listings.show', $listing), $markers[0]['url']);
    }

    public function test_map_pins_without_photos_or_selected_location_still_include_item_details(): void
    {
        $listing = $this->publishedListing(['latitude' => 14.5995, 'longitude' => 120.9842]);
        $markers = Livewire::test(Map::class)->get('markers');

        $this->assertNull($markers[0]['image']);
        $this->assertNull($markers[0]['distance']);
        $this->assertSame('100.00', $markers[0]['price']);
        $this->assertSame(route('listings.show', $listing), $markers[0]['url']);
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

    public static function zeroLocationsAndRadii(): array
    {
        $cases = [];
        foreach (['equator' => [0.0, 120.0], 'prime meridian' => [14.0, 0.0], 'origin' => [0.0, 0.0]] as $label => [$lat, $lng]) {
            foreach ([5, 10, 25, 50] as $radius) {
                $cases[$label.' '.$radius.' km'] = [$lat, $lng, $radius];
            }
        }

        return $cases;
    }

    #[DataProvider('zeroLocationsAndRadii')]
    public function test_zero_coordinates_enable_distance_sorting_and_each_radius(float $lat, float $lng, int $radius): void
    {
        $this->publishedListing(['name' => 'At selected location', 'latitude' => $lat, 'longitude' => $lng]);
        $this->publishedListing(['name' => 'Inside radius', 'latitude' => $lat + ($radius - 0.5) / 111.195, 'longitude' => $lng]);
        $this->publishedListing(['name' => 'Outside radius', 'latitude' => $lat + ($radius + 0.5) / 111.195, 'longitude' => $lng]);

        $page = Livewire::test(Map::class)->set('centerLat', $lat)->set('centerLng', $lng)
            ->assertSee('Nearest available')->assertSee('Within 5 km')->assertSee('Within 10 km')
            ->assertSee('Within 25 km')->assertSee('Within 50 km')->set('radiusKm', $radius);
        $markers = $page->get('markers');
        $this->assertSame(['At selected location', 'Inside radius'], array_column($markers, 'name'));
        $this->assertEqualsWithDelta(0, $markers[0]['distanceKm'], 0.0001);
        $this->assertSame('0.0 km away', $markers[0]['distance']);
        $this->assertLessThan($radius, $markers[1]['distanceKm']);

        $page->set('radiusKm', null);
        $this->assertSame(['At selected location', 'Inside radius', 'Outside radius'], array_column($page->get('markers'), 'name'));
        $page->call('clearLocation')->assertSet('centerLat', null)->assertSet('centerLng', null)
            ->assertSet('radiusKm', null)->assertSee('Available listings')->assertDontSee('Within 5 km');
        $this->assertCount(3, $page->get('markers'));
        foreach ($page->get('markers') as $marker) {
            $this->assertNull($marker['distanceKm']);
            $this->assertNull($marker['distance']);
        }
    }

    public static function incompleteLocations(): array
    {
        return [[null, null], [0.0, null], [null, 0.0], [14.0, null], [null, 120.0]];
    }

    #[DataProvider('incompleteLocations')]
    public function test_radius_is_inactive_until_both_coordinates_are_set(?float $lat, ?float $lng): void
    {
        $this->publishedListing(['name' => 'Origin item', 'latitude' => 0, 'longitude' => 0]);
        $this->publishedListing(['name' => 'Distant item', 'latitude' => 45, 'longitude' => 45]);
        $page = Livewire::test(Map::class)->set('centerLat', $lat)->set('centerLng', $lng)->set('radiusKm', 5)
            ->assertSee('Available listings')->assertDontSee('Within 5 km');
        $this->assertCount(2, $page->get('markers'));
        foreach ($page->get('markers') as $marker) {
            $this->assertNull($marker['distanceKm']);
        }
    }

    public static function sortingLocations(): array
    {
        return ['Manila' => [14.5995, 120.9842], 'equator' => [0.0, 120.0], 'prime meridian' => [14.0, 0.0], 'origin' => [0.0, 0.0]];
    }

    #[DataProvider('sortingLocations')]
    public function test_visible_results_automatically_sort_and_reorder_when_location_changes(float $lat, float $lng): void
    {
        $near = $this->publishedListing(['name' => 'Closest drill', 'latitude' => $lat, 'longitude' => $lng]);
        $this->publishedListing(['name' => 'Middle drill', 'latitude' => $lat + 0.1, 'longitude' => $lng]);
        $far = $this->publishedListing(['name' => 'Farthest drill', 'latitude' => $lat + 0.3, 'longitude' => $lng]);

        $page = Livewire::test(Map::class)->set('centerLat', $lat)->set('centerLng', $lng)
            ->assertSee('sorted nearest to farthest.')
            ->assertSeeInOrder(['Closest drill', 'Middle drill', 'Farthest drill'])
            ->assertSee('0.0 km away')->assertSeeHtml(route('listings.show', $near));
        $this->assertSame(['Closest drill', 'Middle drill', 'Farthest drill'], array_column($page->get('markers'), 'name'));

        $page->set('centerLat', $lat + 0.3)
            ->assertSeeInOrder(['Farthest drill', 'Middle drill', 'Closest drill'])
            ->assertSeeHtml(route('listings.show', $far));
        $this->assertSame(['Farthest drill', 'Middle drill', 'Closest drill'], array_column($page->get('markers'), 'name'));

        $page->set('radiusKm', 25)->assertSeeInOrder(['Farthest drill', 'Middle drill'])->assertDontSee('Closest drill');
        $this->assertCount(2, $page->get('markers'));
        $page->set('keyword', 'Middle')->assertSee('Middle drill')->assertDontSee('Farthest drill');
        $page->set('keyword', 'No matching name')->assertSee('No matching listings')
            ->assertSee('Try a larger radius or adjust your search filters.');
        $page->set('keyword', '')->call('clearLocation')->assertSee('Set your location')
            ->assertDontSee('km away')->assertDontSee('sorted nearest to farthest.')
            ->assertSeeInOrder(['Farthest drill', 'Middle drill', 'Closest drill']);
    }

    public function test_equal_distances_have_a_consistent_order_and_use_unrounded_values(): void
    {
        $first = $this->publishedListing(['name' => 'First equal item', 'latitude' => 0, 'longitude' => 0.01]);
        $this->publishedListing(['name' => 'Farther rounded item', 'latitude' => 0, 'longitude' => 0.01001]);
        $last = $this->publishedListing(['name' => 'Last equal item', 'latitude' => 0, 'longitude' => 0.01]);
        $page = Livewire::test(Map::class)->set('centerLat', 0)->set('centerLng', 0)
            ->assertSeeInOrder(['First equal item', 'Last equal item', 'Farther rounded item']);
        $markers = $page->get('markers');
        $this->assertSame($first->id, $markers[0]['id']);
        $this->assertSame($last->id, $markers[1]['id']);
        $this->assertSame($markers[0]['distance'], $markers[2]['distance']);
        $this->assertLessThan($markers[2]['distanceKm'], $markers[0]['distanceKm']);
        $page->call('$refresh')->assertSeeInOrder(['First equal item', 'Last equal item', 'Farther rounded item']);
    }
}
