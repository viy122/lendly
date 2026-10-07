<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // SQLite must disable foreign keys outside a transaction while rebuilding tables.
    public $withinTransaction = false;

    private array $references = [
        'listings' => ['owner_id' => 'users'],
        'rental_requests' => ['listing_id' => 'listings', 'renter_id' => 'users'],
        'rentals' => ['rental_request_id' => 'rental_requests', 'listing_id' => 'listings', 'owner_id' => 'users', 'renter_id' => 'users'],
        'condition_records' => ['recorded_by' => 'users'],
        'disputes' => ['raised_by' => 'users'],
        'messages' => ['sender_id' => 'users', 'receiver_id' => 'users'],
    ];

    public function up(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->softDeletes());
        Schema::table('rentals', fn (Blueprint $table) => $table->timestamp('archived_at')->nullable());

        DB::table('rentals')->where('status', 'completed')->update([
            'archived_at' => DB::raw('COALESCE(completed_at, updated_at)'),
        ]);

        $this->changeDeletionRule('restrict');
    }

    public function down(): void
    {
        $this->changeDeletionRule('cascade');
        Schema::table('rentals', fn (Blueprint $table) => $table->dropColumn('archived_at'));
        Schema::table('users', fn (Blueprint $table) => $table->dropSoftDeletes());
    }

    private function changeDeletionRule(string $rule): void
    {
        foreach ($this->references as $name => $references) {
            Schema::table($name, function (Blueprint $table) use ($references, $rule) {
                foreach ($references as $column => $parent) {
                    $table->dropForeign([$column]);
                    $table->foreign($column)->references('id')->on($parent)->onDelete($rule);
                }
            });
        }
    }
};
