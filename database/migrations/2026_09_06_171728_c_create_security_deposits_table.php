<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->decimal('deducted_amount', 10, 2)->default(0);
            $table->string('status')->default('held');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_deposits');
    }
};
