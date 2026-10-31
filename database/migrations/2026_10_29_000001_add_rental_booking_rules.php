<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->boolean('allows_daily_rentals')->default(true);
            $table->unsignedSmallInteger('min_rental_duration_hours')->default(1);
            $table->unsignedSmallInteger('max_rental_duration_hours')->default(24);
            $table->string('available_time_from', 5)->default('00:00');
            $table->string('available_time_until', 5)->default('23:59');
        });
        foreach (['cart_items', 'rental_requests', 'rentals'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->string('rental_unit')->default('day');
                $table->unsignedInteger('rental_hours')->nullable();
                $table->dateTime('starts_at')->nullable();
                $table->dateTime('ends_at')->nullable();
            });
        }
        Schema::table('commission_settings', function (Blueprint $table) {
            $table->unsignedSmallInteger('cancellation_window_hours')->default(24);
            $table->decimal('cancellation_fee_percentage', 5, 2)->default(20);
            $table->unsignedSmallInteger('payment_window_hours')->default(24);
        });
        Schema::table('rentals', function (Blueprint $table) {
            $table->string('fulfillment_stage')->default('reserved');
            $table->dateTime('payment_due_at')->nullable()->index();
            $table->decimal('cancellation_refund_amount', 10, 2)->nullable();
        });
        Schema::create('cancellation_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users');
            $table->text('reason');
            $table->decimal('fee', 10, 2);
            $table->string('status')->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->text('review_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('rental_waitlist_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('details');
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();
            $table->unique(['listing_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_waitlist_entries');
        Schema::dropIfExists('cancellation_requests');
        Schema::table('rentals', function (Blueprint $table) {
            $table->dropIndex(['payment_due_at']);
            $table->dropColumn(['fulfillment_stage', 'payment_due_at', 'cancellation_refund_amount']);
        });
        Schema::table('commission_settings', fn (Blueprint $table) => $table->dropColumn(['cancellation_window_hours', 'cancellation_fee_percentage', 'payment_window_hours']));
        foreach (['cart_items', 'rental_requests', 'rentals'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn(['rental_unit', 'rental_hours', 'starts_at', 'ends_at']));
        }
        Schema::table('listings', fn (Blueprint $table) => $table->dropColumn(['allows_daily_rentals', 'min_rental_duration_hours', 'max_rental_duration_hours', 'available_time_from', 'available_time_until']));
    }
};
