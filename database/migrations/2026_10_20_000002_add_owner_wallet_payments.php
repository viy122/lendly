<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owner_payment_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->restrictOnDelete();
            $table->string('provider', 20);
            $table->string('account_name', 120);
            $table->text('instructions')->nullable();
            $table->string('qr_path')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });
        Schema::table('rentals', fn (Blueprint $table) => $table->json('payment_destination')->nullable());
        Schema::table('payment_submissions', function (Blueprint $table) {
            $table->json('payment_destination')->nullable();
            $table->timestamp('recipient_confirmed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('payment_submissions', fn (Blueprint $table) => $table->dropColumn(['payment_destination', 'recipient_confirmed_at']));
        Schema::table('rentals', fn (Blueprint $table) => $table->dropColumn('payment_destination'));
        Schema::dropIfExists('owner_payment_settings');
    }
};
