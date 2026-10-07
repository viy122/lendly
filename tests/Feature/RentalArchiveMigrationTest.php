<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Listing;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\RentalRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RentalArchiveMigrationTest extends TestCase
{
    // Exercise schema upgrades outside test transactions, like the real migrator.
    use DatabaseMigrations;

    public function test_archive_migration_preserves_and_backfills_existing_transactions(): void
    {
        $migration = require database_path('migrations/2026_10_07_000003_preserve_rental_archives.php');
        $this->assertFalse($migration->withinTransaction);
        $migration->down();
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $category = Category::create(['name' => 'Tools', 'slug' => 'tools']);
        $listing = Listing::create([
            'owner_id' => $owner->id, 'category_id' => $category->id, 'name' => 'Historical item',
            'description' => 'Preserved item details', 'condition' => 'good', 'price_per_day' => 100,
            'security_deposit' => 500, 'location' => 'Manila', 'max_rental_duration_days' => 10,
            'status' => 'published', 'is_available' => true, 'pickup_available' => true,
        ]);
        $terms = [
            'listing_id' => $listing->id, 'renter_id' => $renter->id,
            'start_date' => now()->subDays(8), 'end_date' => now()->subDays(5), 'rental_days' => 3,
            'fulfillment_method' => 'pickup', 'rental_fee' => 300, 'commission_rate' => 10,
            'commission_amount' => 30, 'security_deposit' => 500, 'total_amount' => 830,
        ];
        $completedAt = now()->subDays(4)->startOfSecond();
        $rentals = [];
        foreach (['completed', 'completed', 'active'] as $index => $status) {
            $request = RentalRequest::create($terms + ['status' => 'approved']);
            $rentals[] = Rental::create($terms + [
                'rental_request_id' => $request->id, 'owner_id' => $owner->id,
                'status' => $status, 'completed_at' => $index === 0 ? $completedAt : null,
            ]);
        }
        $payment = Payment::create(['rental_id' => $rentals[0]->id, 'transaction_reference' => Payment::generateReference(), 'amount' => 830, 'paid_at' => $completedAt]);
        $savedRequests = DB::table('rental_requests')->get()->toArray();
        $savedRentals = DB::table('rentals')->get()->toArray();
        $migration->up();

        $this->assertTrue($rentals[0]->fresh()->archived_at->equalTo($completedAt));
        $this->assertTrue($rentals[1]->fresh()->archived_at->equalTo($rentals[1]->updated_at));
        $this->assertNull($rentals[2]->fresh()->archived_at);
        $this->assertSame(3, Rental::count());
        $this->assertEquals($savedRequests, DB::table('rental_requests')->get()->toArray());
        foreach ($savedRentals as $saved) {
            $attributes = Rental::findOrFail($saved->id)->getAttributes();
            unset($attributes['archived_at']);
            $this->assertEquals((array) $saved, $attributes);
        }
        $this->assertSame('830.00', $payment->fresh()->amount);
        $this->assertSame([], DB::select('PRAGMA foreign_key_check'));

        // Rolling back the constraints and columns must also preserve shared history.
        $migration->down();
        $this->assertEquals($savedRentals, DB::table('rentals')->get()->toArray());
        $this->assertNotNull($payment->fresh());
        $migration->up();
    }
}
