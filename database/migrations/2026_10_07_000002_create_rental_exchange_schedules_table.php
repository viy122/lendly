<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_exchange_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_id')->constrained()->cascadeOnDelete();
            $table->foreignId('proposed_by')->constrained('users');
            $table->string('fulfillment_method');
            $table->dateTime('scheduled_at');
            $table->string('address', 500);
            $table->text('notes')->nullable();
            $table->timestamp('owner_confirmed_at')->nullable();
            $table->timestamp('renter_confirmed_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            $table->index(['rental_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_exchange_schedules');
    }
};
