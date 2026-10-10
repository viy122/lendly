<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained()->restrictOnDelete();
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('renter_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['listing_id', 'renter_id']);
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('rental_request_id')->nullable()->change();
            $table->foreignId('listing_conversation_id')->nullable()->constrained()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::table('messages')->whereNotNull('listing_conversation_id')->exists()) {
            throw new RuntimeException('Cannot roll back listing conversations while inquiry messages exist.');
        }

        Schema::table('messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('listing_conversation_id');
            $table->foreignId('rental_request_id')->nullable(false)->change();
        });

        Schema::dropIfExists('listing_conversations');
    }
};
