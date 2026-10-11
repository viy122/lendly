<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Listing;
use App\Models\ListingConversation;
use App\Models\Rental;
use App\Models\RentalRequest;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MergeMigrationCompatibilityTest extends TestCase
{
    use DatabaseMigrations;

    public function test_either_inquiry_migration_can_upgrade_an_existing_inquiry_without_losing_history(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $category = Category::create(['name' => 'Tools', 'slug' => 'merge-tools']);
        $listing = Listing::create([
            'owner_id' => $owner->id, 'category_id' => $category->id, 'name' => 'Camera',
            'description' => 'Saved item', 'condition' => 'good', 'price_per_day' => 100,
            'location' => 'Manila', 'max_rental_duration_days' => 7,
        ]);
        $conversation = ListingConversation::create(['listing_id' => $listing->id, 'owner_id' => $owner->id, 'renter_id' => $renter->id]);
        $message = $conversation->messages()->create(['sender_id' => $renter->id, 'receiver_id' => $owner->id, 'body' => 'Keep this inquiry']);

        foreach (['2026_10_07_000007_add_listing_conversations.php', '2026_10_09_000100_create_listing_conversations_table.php'] as $file) {
            (require database_path('migrations/'.$file))->up();
            $this->assertDatabaseHas('messages', ['id' => $message->id, 'body' => 'Keep this inquiry']);
            $this->assertDatabaseHas('listing_conversations', ['id' => $conversation->id]);
        }
        $this->assertTrue(Schema::hasIndex('listing_conversations', 'listing_conversations_listing_id_renter_id_unique'));
        $this->assertSame([], DB::select('PRAGMA foreign_key_check'));
        // Remove the disposable message so teardown can exercise safe rollback.
        $message->delete();
    }

    public function test_soft_deletion_migrations_preserve_an_existing_closed_account(): void
    {
        $user = User::factory()->create();
        $user->delete();
        foreach (['2026_10_07_000003_preserve_rental_archives.php', '2026_10_09_000001_add_soft_deletes_to_users_table.php'] as $file) {
            $migration = require database_path('migrations/'.$file);
            if (str_contains($file, 'preserve_rental_archives')) {
                Schema::table('rentals', fn (Blueprint $table) => $table->dropColumn('archived_at'));
            }
            $migration->up();
            $this->assertNotNull(User::withTrashed()->findOrFail($user->id)->deleted_at);
        }
    }

    public function test_agreement_upgrade_reuses_the_existing_booking_unique_index(): void
    {
        $migration = require database_path('migrations/2026_10_07_000001_add_agreement_terms_to_rental_requests.php');
        $migration->down();
        $this->assertTrue(Schema::hasIndex('rentals', 'rentals_rental_request_id_unique'));
        $migration->up();
        $this->assertTrue(Schema::hasIndex('rentals', 'rentals_rental_request_id_unique'));
        $this->assertTrue(Schema::hasColumn('rental_requests', 'agreement_terms'));
    }

    public static function rollbackBranches(): array
    {
        return ['local branch' => ['2026_10_09_', 3], 'incoming branch' => ['2026_10_07_', 8]];
    }

    #[DataProvider('rollbackBranches')]
    public function test_partial_branch_rollback_preserves_history_owned_by_the_other_branch(string $prefix, int $steps): void
    {
        DB::table('migrations')->where('migration', 'like', $prefix.'%')->update(['batch' => 2]);
        $closed = User::factory()->create();
        $closed->delete();
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $category = Category::create(['name' => 'Tools', 'slug' => 'rollback-tools']);
        $listing = Listing::create([
            'owner_id' => $owner->id, 'category_id' => $category->id, 'name' => 'Saved camera',
            'description' => 'Keep this item', 'condition' => 'good', 'price_per_day' => 100,
            'location' => 'Manila', 'max_rental_duration_days' => 7,
        ]);
        $conversation = ListingConversation::create(['listing_id' => $listing->id, 'owner_id' => $owner->id, 'renter_id' => $renter->id]);
        $message = $conversation->messages()->create(['sender_id' => $renter->id, 'receiver_id' => $owner->id, 'body' => 'Keep this history']);

        try {
            $this->artisan('migrate:rollback', ['--step' => $steps])->assertSuccessful();
            $this->assertNotNull(User::withTrashed()->findOrFail($closed->id)->deleted_at);
            $this->assertDatabaseHas('listing_conversations', ['id' => $conversation->id]);
            $this->assertDatabaseHas('messages', ['id' => $message->id, 'body' => 'Keep this history']);
            $this->assertTrue(Schema::hasIndex('rentals', 'rentals_rental_request_id_unique'));
            $this->assertSame([], DB::select('PRAGMA foreign_key_check'));
        } catch (\RuntimeException $exception) {
            $this->fail('A partial rollback must retain the other branch\'s history: '.$exception->getMessage());
        } finally {
            $this->artisan('migrate')->assertSuccessful();
            $message->delete();
        }
    }

    public function test_duplicate_booking_preflight_can_be_retried_after_data_is_repaired(): void
    {
        $owner = User::factory()->create();
        $renter = User::factory()->create();
        $category = Category::create(['name' => 'Tools', 'slug' => 'duplicate-tools']);
        $listing = Listing::create([
            'owner_id' => $owner->id, 'category_id' => $category->id, 'name' => 'Legacy camera',
            'description' => 'Saved item', 'condition' => 'good', 'price_per_day' => 100,
            'location' => 'Manila', 'max_rental_duration_days' => 7,
        ]);
        $terms = [
            'listing_id' => $listing->id, 'renter_id' => $renter->id,
            'start_date' => today()->addDays(3), 'end_date' => today()->addDays(5), 'rental_days' => 3,
            'fulfillment_method' => 'pickup', 'rental_fee' => 300, 'commission_rate' => 10,
            'commission_amount' => 30, 'security_deposit' => 500, 'total_amount' => 830,
        ];
        $request = RentalRequest::create($terms);
        $rental = Rental::create($terms + ['rental_request_id' => $request->id, 'owner_id' => $owner->id]);
        Schema::table('rentals', fn (Blueprint $table) => $table->dropUnique(['rental_request_id']));
        Schema::table('rental_requests', fn (Blueprint $table) => $table->dropColumn('agreement_terms'));
        $duplicate = $rental->getAttributes();
        unset($duplicate['id']);
        DB::table('rentals')->insert($duplicate);
        $migration = require database_path('migrations/2026_10_07_000001_add_agreement_terms_to_rental_requests.php');

        try {
            try {
                $migration->up();
                $this->fail('Duplicate bookings must be reviewed before any schema change.');
            } catch (\RuntimeException $exception) {
                $this->assertStringContainsString('Duplicate bookings', $exception->getMessage());
            }
            $this->assertFalse(Schema::hasColumn('rental_requests', 'agreement_terms'));
            $this->assertSame(2, Rental::count());
            DB::table('rentals')->where('id', '!=', $rental->id)->delete();
            $migration->up();
            $this->assertTrue(Schema::hasIndex('rentals', 'rentals_rental_request_id_unique'));
            $this->assertSame(1, Rental::count());
        } finally {
            DB::table('rentals')->where('id', '!=', $rental->id)->delete();
            if (! Schema::hasColumn('rental_requests', 'agreement_terms')) {
                Schema::table('rental_requests', fn (Blueprint $table) => $table->json('agreement_terms')->nullable());
            }
            if (! Schema::hasIndex('rentals', 'rentals_rental_request_id_unique')) {
                Schema::table('rentals', fn (Blueprint $table) => $table->unique('rental_request_id'));
            }
        }
    }
}
