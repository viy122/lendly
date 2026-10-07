<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offline_payment_settings', function (Blueprint $table) {
            $table->id();
            $table->string('method')->default('bank_transfer');
            $table->text('instructions')->nullable();
            $table->boolean('enabled')->default(false);
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
        Schema::create('payment_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_id')->constrained()->restrictOnDelete();
            $table->foreignId('submitted_by')->constrained('users')->restrictOnDelete();
            $table->string('method');
            $table->text('instructions');
            $table->string('external_reference', 100);
            $table->decimal('amount', 10, 2);
            $table->string('proof_path');
            $table->string('status')->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('review_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['rental_id', 'status']);
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->string('method')->nullable();
            $table->string('external_reference', 100)->nullable()->unique();
            $table->foreignId('payment_submission_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['payment_submission_id']);
            $table->dropForeign(['verified_by']);
            $table->dropUnique(['payment_submission_id']);
            $table->dropUnique(['external_reference']);
            $table->dropColumn(['method', 'external_reference', 'payment_submission_id', 'verified_by']);
        });
        Schema::dropIfExists('payment_submissions');
        Schema::dropIfExists('offline_payment_settings');
    }
};
