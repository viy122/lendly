<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_admin_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_id')->constrained()->restrictOnDelete();
            $table->foreignId('performed_by')->constrained('users')->restrictOnDelete();
            $table->string('action');
            $table->text('notes');
            $table->json('before_state');
            $table->json('after_state');
            $table->timestamps();
            $table->index(['rental_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_admin_actions');
    }
};
