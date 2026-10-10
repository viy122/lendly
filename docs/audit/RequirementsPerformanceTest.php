<?php

namespace Tests\Audit;

use App\Livewire\Listings\Browse;
use App\Livewire\Listings\Map;
use App\Livewire\Owner\RentalRequests\Index as Approvals;
use App\Livewire\RentalRequests\Create as Requests;
use App\Models\Category;
use App\Models\Listing;
use App\Models\RentalRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

// Local, sequential timing samples; not a concurrent production load test.
class RequirementsPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_measure_search_map_request_and_approval_with_one_thousand_listings(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $category = Category::create(['name' => 'Performance cameras', 'slug' => 'performance-cameras']);
        $rows = [];
        for ($index = 0; $index < 1000; $index++) {
            $rows[] = ['owner_id' => $owner->id, 'category_id' => $category->id,
                'name' => 'Camera '.$index, 'description' => 'Timing fixture.', 'condition' => 'good',
                'price_per_day' => 100, 'location' => 'Manila', 'latitude' => 14.5995 + $index / 100000,
                'longitude' => 120.9842, 'max_rental_duration_days' => 10, 'status' => 'published',
                'created_at' => now(), 'updated_at' => now()];
        }
        Listing::insert($rows);
        Livewire::test(Browse::class);
        Livewire::test(Map::class);
        $timings = [];
        foreach (range(1, 5) as $sample) {
            $started = hrtime(true);
            Livewire::test(Browse::class)->set('keyword', 'Camera 99')->assertSee('Camera 99');
            $timings['search'][] = round((hrtime(true) - $started) / 1e6, 2);
            $started = hrtime(true);
            $markers = Livewire::test(Map::class)->set('centerLat', 14.5995)
                ->set('centerLng', 120.9842)->set('radiusKm', 5)->get('markers');
            $this->assertCount(1000, $markers);
            $timings['map'][] = round((hrtime(true) - $started) / 1e6, 2);
            $listing = Listing::findOrFail($sample);
            $form = Livewire::actingAs($renter)->test(Requests::class, ['listing' => $listing])
                ->set('start_date', now()->addDays(3)->toDateString())
                ->set('end_date', now()->addDays(5)->toDateString())->set('accept_terms', true);
            $started = hrtime(true);
            $form->call('submit')->assertHasNoErrors();
            $timings['request'][] = round((hrtime(true) - $started) / 1e6, 2);
            $request = RentalRequest::where('listing_id', $listing->id)->firstOrFail();
            $approval = Livewire::actingAs($owner)->test(Approvals::class)->set('accept_terms', true);
            $started = hrtime(true);
            $approval->call('approve', $request->id)->assertHasNoErrors();
            $timings['approval'][] = round((hrtime(true) - $started) / 1e6, 2);
        }
        file_put_contents(base_path('docs/audit/local-timings.json'), json_encode([
            'listings' => 1000, 'samples' => 5, 'database' => 'SQLite :memory:',
            'method' => 'Sequential Livewire test harness, warm views; elapsed milliseconds include assertions and component rendering. Search includes mount+keyword update; map includes mount+3 updates. Request/approval time their submit actions only.',
            'milliseconds' => $timings,
        ], JSON_PRETTY_PRINT).PHP_EOL);
    }
}
