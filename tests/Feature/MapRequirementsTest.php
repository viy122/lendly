<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Livewire\Listings\Map;
use App\Livewire\Owner\Listings\Form;
use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MapRequirementsTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->owner = User::factory()->owner()->create();
        $this->category = Category::create(['name' => 'Tools', 'slug' => 'tools']);
    }

    private function listing(array $attributes = []): Listing
    {
        return Listing::forceCreate(array_merge([
            'owner_id' => $this->owner->id,
            'category_id' => $this->category->id,
            'name' => 'Map listing',
            'description' => 'A rentable tool.',
            'condition' => 'good',
            'price_per_day' => 125,
            'location' => 'Selected location',
            'latitude' => 0,
            'longitude' => 0,
            'max_rental_duration_days' => 7,
            'status' => ListingStatus::Published,
            'is_available' => true,
        ], $attributes));
    }

    public static function zeroCoordinates(): array
    {
        return [
            'zero latitude' => [0.0, 120.0],
            'zero longitude' => [14.0, 0.0],
            'both zero' => [0.0, 0.0],
        ];
    }

    #[DataProvider('zeroCoordinates')]
    public function test_radius_applies_with_zero_coordinates(float $lat, float $lng): void
    {
        $near = $this->listing(['latitude' => $lat + .01, 'longitude' => $lng]);
        $this->listing(['latitude' => $lat + 1, 'longitude' => $lng]);

        $component = Livewire::test(Map::class)->set('centerLat', $lat)->set('centerLng', $lng);

        foreach ([5, 10, 25, 50] as $radius) {
            $markers = $component->set('radiusKm', $radius)->get('markers');
            $this->assertSame([$near->id], array_column($markers, 'id'));
        }
    }

    #[DataProvider('zeroCoordinates')]
    public function test_zero_coordinate_location_shows_distance_controls(float $lat, float $lng): void
    {
        Livewire::test(Map::class)->set('centerLat', $lat)->set('centerLng', $lng)
            ->assertSee('Location set')->assertSee('Nearest available')->assertSee('Within 5 km');
    }

    #[DataProvider('zeroCoordinates')]
    public function test_zero_coordinate_location_sorts_nearest_first(float $lat, float $lng): void
    {
        $near = $this->listing(['latitude' => $lat, 'longitude' => $lng, 'created_at' => now()->subDay()]);
        $far = $this->listing(['latitude' => $lat + 1, 'longitude' => $lng]);

        $markers = Livewire::test(Map::class)->set('centerLat', $lat)->set('centerLng', $lng)->get('markers');

        $this->assertSame([$near->id, $far->id], array_column($markers, 'id'));
        $this->assertNotNull($markers[0]['distanceKm']);
        $this->assertEqualsWithDelta(0, $markers[0]['distanceKm'], .001);
        $this->assertEqualsWithDelta(111.2, $markers[1]['distanceKm'], .1);
    }

    #[DataProvider('zeroCoordinates')]
    public function test_owner_edit_preserves_stored_zero_coordinates(float $lat, float $lng): void
    {
        $listing = $this->listing(['latitude' => $lat, 'longitude' => $lng]);

        Livewire::actingAs($this->owner)->test(Form::class, ['listing' => $listing])
            ->assertSet('latitude', $lat)->assertSet('longitude', $lng)
            ->call('save')->assertHasNoErrors();

        $listing->refresh();
        $this->assertSame($lat, (float) $listing->latitude);
        $this->assertSame($lng, (float) $listing->longitude);
    }

    public static function missingCoordinates(): array
    {
        return ['no location' => [null, null], 'missing longitude' => [0.0, null], 'missing latitude' => [null, 0.0]];
    }

    #[DataProvider('missingCoordinates')]
    public function test_missing_location_ignores_radius_and_keeps_browsing(?float $lat, ?float $lng): void
    {
        $this->listing(['created_at' => now()->subDay()]);
        $latest = $this->listing(['latitude' => 1]);
        $component = Livewire::test(Map::class)->set('centerLat', $lat)->set('centerLng', $lng)->set('radiusKm', 5);
        $markers = $component->get('markers');

        $this->assertCount(2, $markers);
        $this->assertSame($latest->id, $markers[0]['id']);
        $this->assertSame([null, null], array_column($markers, 'distanceKm'));
        $component->assertSee('Available listings')->assertDontSee('Nearest available')->assertDontSee('Within 5 km');
    }

    public function test_marker_payload_keeps_photo_name_price_and_matching_detail_url(): void
    {
        $listing = $this->listing(['name' => '<img src=x onerror=alert(1)>', 'price_per_day' => 1234.5]);
        $listing->images()->create(['path' => 'listings/example.jpg', 'sort_order' => 0]);
        $marker = Livewire::test(Map::class)->get('markers')[0];

        $this->assertSame($listing->id, $marker['id']);
        $this->assertSame('<img src=x onerror=alert(1)>', $marker['name']);
        $this->assertSame('1,234.50', $marker['price']);
        $this->assertStringEndsWith('/storage/listings/example.jpg', $marker['image']);
        $this->assertStringEndsWith('/listings/'.$listing->id, $marker['url']);
    }

    public function test_map_keyword_and_category_filters_keep_only_matching_available_pins(): void
    {
        $other = Category::create(['name' => 'Cameras', 'slug' => 'cameras']);
        $match = $this->listing(['name' => 'Drill', 'brand' => 'Bosch']);
        $this->listing(['name' => 'Drill', 'brand' => 'Bosch', 'category_id' => $other->id]);
        $this->listing(['name' => 'Drill', 'brand' => 'Bosch', 'is_available' => false]);
        $this->listing(['name' => 'Drill', 'brand' => 'Bosch', 'status' => ListingStatus::PendingApproval]);
        $this->listing(['name' => 'Drill', 'brand' => 'Bosch', 'latitude' => null]);
        $this->listing(['name' => 'Unrelated tool']);

        $markers = Livewire::test(Map::class)->set('keyword', 'Bosch')
            ->set('category', (string) $this->category->id)->get('markers');

        $this->assertSame([$match->id], array_column($markers, 'id'));
    }
}
