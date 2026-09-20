<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('damage_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_id')->constrained()->cascadeOnDelete();
            $table->foreignId('condition_record_id')->nullable()->constrained()->nullOnDelete();
            $table->string('damage_type');
            $table->text('description');
            $table->decimal('estimated_repair_cost', 10, 2);
            $table->decimal('proposed_deduction', 10, 2);
            $table->string('status')->default('pending');
            $table->text('renter_response_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('damage_reports');
    }
};
