<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('condition_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by')->constrained('users')->cascadeOnDelete();
            $table->string('type');
            $table->string('condition');
            $table->text('notes')->nullable();
            $table->boolean('has_damage')->default(false);
            $table->timestamps();

            $table->unique(['rental_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('condition_records');
    }
};
