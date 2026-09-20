<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Livewire\Owner\Listings\Form as OwnerListingForm;
use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use App\Services\MarketInsight;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MarketInsightTest extends TestCase
{
    use RefreshDatabase;

    private function publishedListing(User $owner, Category $category, array $overrides = []): Listing
    {
        return Listing::create(array_merge([
            'owner_id' => $owner->id,
            'category_id' => $category->id,
            'name' => 'Pressure Washer',
            'description' => 'x',
            'condition' => 'good',
            'price_per_day' => 100,
            'security_deposit' => 500,
            'location' => 'Manila',
            'max_rental_duration_days' => 10,
            'status' => ListingStatus::Published,
            'is_available' => true,
            'pickup_available' => true,
        ], $overrides));
    }

    public function test_market_insight_computes_stats_from_similar_published_listings(): void
    {
        $owner = User::factory()->owner()->create();
        $category = Category::create(['name' => 'Tools', 'slug' => 'tools']);

        $this->publishedListing($owner, $category, ['price_per_day' => 400]);
        $this->publishedListing($owner, $category, ['price_per_day' => 500]);
        $this->publishedListing($owner, $category, ['price_per_day' => 550]);

        $insight = MarketInsight::forListing($category->id, null);

        $this->assertSame(3, $insight['count']);
        $this->assertEquals(483.33, $insight['average']);
        $this->assertEquals(400.00, $insight['min']);
        $this->assertEquals(550.00, $insight['max']);
        $this->assertEquals($insight['average'], $insight['suggested']);
    }

    public function test_market_insight_excludes_unpublished_listings(): void
    {
        $owner = User::factory()->owner()->create();
        $category = Category::create(['name' => 'Tools', 'slug' => 'tools']);

        $this->publishedListing($owner, $category, ['price_per_day' => 400]);
        $this->publishedListing($owner, $category, ['price_per_day' => 9000, 'status' => ListingStatus::PendingApproval]);

        $insight = MarketInsight::forListing($category->id, null);

        $this->assertSame(1, $insight['count']);
        $this->assertEquals(400.00, $insight['average']);
    }

    public function test_market_insight_excludes_the_listing_being_edited(): void
    {
        $owner = User::factory()->owner()->create();
        $category = Category::create(['name' => 'Tools', 'slug' => 'tools']);

        $other = $this->publishedListing($owner, $category, ['price_per_day' => 400]);
        $selfListing = $this->publishedListing($owner, $category, ['price_per_day' => 9999]);

        $insight = MarketInsight::forListing($category->id, null, excludingListingId: $selfListing->id);

        $this->assertSame(1, $insight['count']);
        $this->assertEquals(400.00, $insight['average']);
    }

    public function test_smart_pricing_warning_triggers_only_when_significantly_above_average(): void
    {
        $this->assertFalse(MarketInsight::isSignificantlyHigher(550, 500)); // 10% over
        $this->assertTrue(MarketInsight::isSignificantlyHigher(700, 500)); // 40% over
        $this->assertFalse(MarketInsight::isSignificantlyHigher(500, 0)); // no market data
    }

    public function test_owner_listing_form_shows_pricing_warning_for_a_high_price(): void
    {
        $owner = User::factory()->owner()->create();
        $otherOwner = User::factory()->owner()->create();
        $category = Category::create(['name' => 'Tools', 'slug' => 'tools']);

        $this->publishedListing($otherOwner, $category, ['price_per_day' => 450]);
        $this->publishedListing($otherOwner, $category, ['price_per_day' => 500]);

        $component = Livewire::actingAs($owner)
            ->test(OwnerListingForm::class)
            ->set('category_id', $category->id)
            ->set('price_per_day', 900);

        $this->assertTrue($component->instance()->isPriceSignificantlyHigh());
    }
}
