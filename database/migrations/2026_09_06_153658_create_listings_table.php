<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->foreignId('subcategory_id')->nullable()->constrained('categories')->nullOnDelete();

            $table->string('name');
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->text('description');
            $table->string('condition');
            $table->unsignedSmallInteger('purchase_year')->nullable();
            $table->decimal('estimated_original_price', 10, 2)->nullable();

            $table->decimal('price_per_day', 10, 2);
            $table->decimal('price_per_hour', 10, 2)->nullable();
            $table->decimal('price_per_week', 10, 2)->nullable();
            $table->decimal('security_deposit', 10, 2)->default(0);

            $table->string('location');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->boolean('pickup_available')->default(true);
            $table->boolean('delivery_available')->default(false);
            $table->text('rental_rules')->nullable();
            $table->unsignedSmallInteger('max_rental_duration_days');

            $table->boolean('is_available')->default(true);
            $table->string('status')->default('pending_approval');
            $table->string('rejection_reason')->nullable();
            $table->unsignedInteger('views_count')->default(0);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};
