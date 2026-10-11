<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Either branch may already have installed listing inquiries.
        if (! Schema::hasTable('listing_conversations')) {
            Schema::create('listing_conversations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('listing_id')->constrained()->restrictOnDelete();
                $table->foreignId('renter_id')->constrained('users')->restrictOnDelete();
                $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
                $table->timestamps();
                $table->unique(['listing_id', 'renter_id']);
            });
        }

        if (! Schema::hasColumn('messages', 'listing_conversation_id')) {
            Schema::table('messages', function (Blueprint $table) {
                $table->unsignedBigInteger('rental_request_id')->nullable()->change();
                $table->foreignId('listing_conversation_id')->nullable()->constrained()->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (DB::table('migrations')->where('migration', '2026_10_09_000100_create_listing_conversations_table')->exists()) {
            return;
        }

        if (Schema::hasColumn('messages', 'listing_conversation_id')) {
            if (DB::table('messages')->whereNotNull('listing_conversation_id')->exists()) {
                throw new RuntimeException('Cannot roll back listing conversations while inquiry messages exist.');
            }

            Schema::table('messages', function (Blueprint $table) {
                $table->dropConstrainedForeignId('listing_conversation_id');
                $table->unsignedBigInteger('rental_request_id')->nullable(false)->change();
            });
        }

        Schema::dropIfExists('listing_conversations');
    }
};
