<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rentals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('renter_id')->constrained('users')->cascadeOnDelete();

            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedSmallInteger('rental_days');
            $table->string('fulfillment_method');

            $table->decimal('rental_fee', 10, 2);
            $table->decimal('commission_rate', 5, 2);
            $table->decimal('commission_amount', 10, 2);
            $table->decimal('security_deposit', 10, 2);
            $table->decimal('total_amount', 10, 2);

            $table->string('status')->default('payment_pending');
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rentals');
    }
};
