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
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('renter_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['listing_id', 'renter_id', 'owner_id']);
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->unsignedBigInteger('rental_request_id')->nullable()->change();
            $table->foreignId('listing_conversation_id')->nullable()->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        DB::table('messages')->whereNotNull('listing_conversation_id')->delete();

        Schema::table('messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('listing_conversation_id');
            $table->unsignedBigInteger('rental_request_id')->nullable(false)->change();
        });

        Schema::dropIfExists('listing_conversations');
    }
};
