<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Listing;
use App\Models\Rental;
use App\Models\RentalRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CancellationHistoryMigrationTest extends TestCase
{
    use DatabaseMigrations;

    public function test_upgrade_preserves_and_copies_existing_cancellation_evidence(): void
    {
        $migration = require database_path('migrations/2026_10_07_000005_record_request_cancellations.php');
        $migration->down();
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $category = Category::create(['name' => 'Tools', 'slug' => 'tools']);
        $listing = Listing::create([
            'owner_id' => $owner->id, 'category_id' => $category->id,
            'name' => 'Historical item', 'description' => 'Drill', 'condition' => 'good',
            'price_per_day' => 100, 'security_deposit' => 500, 'location' => 'Manila',
            'max_rental_duration_days' => 10, 'status' => 'published', 'pickup_available' => true,
        ]);
        $terms = [
            'listing_id' => $listing->id, 'renter_id' => $renter->id,
            'start_date' => now()->addDay(), 'end_date' => now()->addDays(3), 'rental_days' => 3,
            'fulfillment_method' => 'pickup', 'rental_fee' => 1000, 'commission_rate' => 10,
            'commission_amount' => 100, 'security_deposit' => 500, 'total_amount' => 1600,
        ];
        $pendingCancellation = RentalRequest::create($terms + ['status' => 'cancelled']);
        $bookedCancellation = RentalRequest::create($terms + ['status' => 'cancelled']);
        $activeRequest = RentalRequest::create($terms);
        $rental = Rental::create($terms + [
            'owner_id' => $owner->id, 'rental_request_id' => $bookedCancellation->id,
            'status' => 'cancelled', 'cancelled_at' => now()->subHour(),
            'cancellation_reason' => str_repeat('r', 500), 'cancellation_fee' => 200,
        ]);
        $savedRental = $rental->refresh()->getAttributes();
        $savedRequests = DB::table('rental_requests')->orderBy('id')->get();

        $migration->up();

        $this->assertSame($savedRental, $rental->fresh()->getAttributes());
        $bookedCancellation->refresh();
        $this->assertSame($rental->cancellation_reason, $bookedCancellation->cancellation_reason);
        $this->assertSame($rental->cancellation_fee, $bookedCancellation->cancellation_fee);
        $this->assertTrue($rental->cancelled_at->equalTo($bookedCancellation->cancelled_at));
        $pendingCancellation->refresh();
        $this->assertNull($pendingCancellation->cancelled_at);
        $this->assertNull($pendingCancellation->cancellation_reason);
        $this->assertSame('0.00', $pendingCancellation->cancellation_fee);
        $activeRequest->refresh();
        $this->assertNull($activeRequest->cancelled_at);
        $this->assertNull($activeRequest->cancellation_fee);
        $this->assertSame([], DB::select('PRAGMA foreign_key_check'));

        $migration->down();
        $this->assertEquals($savedRequests, DB::table('rental_requests')->orderBy('id')->get());
        $this->assertSame($savedRental, $rental->fresh()->getAttributes());
        $migration->up();
    }
}
