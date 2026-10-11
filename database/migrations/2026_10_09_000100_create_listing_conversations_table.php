<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        if (! Schema::hasTable('listing_conversations')) {
            Schema::create('listing_conversations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('listing_id')->constrained()->restrictOnDelete();
                $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
                $table->foreignId('renter_id')->constrained('users')->restrictOnDelete();
                $table->timestamps();
                $table->unique(['listing_id', 'renter_id']);
            });
        }

        if (! Schema::hasColumn('messages', 'listing_conversation_id')) {
            Schema::table('messages', function (Blueprint $table) {
                $table->foreignId('rental_request_id')->nullable()->change();
                $table->foreignId('listing_conversation_id')->nullable()->constrained()->restrictOnDelete();
            });
        }

        // Upgrade incoming inquiry tables without discarding existing history.
        if (! Schema::hasIndex('listing_conversations', 'listing_conversations_listing_id_renter_id_unique')) {
            if (DB::table('listing_conversations')->select('listing_id', 'renter_id')->groupBy('listing_id', 'renter_id')->havingRaw('COUNT(*) > 1')->exists()) {
                throw new RuntimeException('Duplicate listing inquiries need review before adding uniqueness; no records were deleted.');
            }
            Schema::table('listing_conversations', fn (Blueprint $table) => $table->unique(['listing_id', 'renter_id']));
        }
        Schema::table('listing_conversations', function (Blueprint $table) {
            foreach (['listing_id' => 'listings', 'owner_id' => 'users', 'renter_id' => 'users'] as $column => $parent) {
                $table->dropForeign([$column]);
                $table->foreign($column)->references('id')->on($parent)->restrictOnDelete();
            }
        });
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['listing_conversation_id']);
            $table->foreign('listing_conversation_id')->references('id')->on('listing_conversations')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::table('migrations')->where('migration', '2026_10_07_000007_add_listing_conversations')->exists()) {
            return;
        }

        if (Schema::hasColumn('messages', 'listing_conversation_id')) {
            if (DB::table('messages')->whereNotNull('listing_conversation_id')->exists()) {
                throw new RuntimeException('Cannot roll back listing conversations while inquiry messages exist.');
            }

            Schema::table('messages', function (Blueprint $table) {
                $table->dropConstrainedForeignId('listing_conversation_id');
                $table->foreignId('rental_request_id')->nullable(false)->change();
            });
        }

        Schema::dropIfExists('listing_conversations');
    }
};
