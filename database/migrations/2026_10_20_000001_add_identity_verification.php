<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('identity_verified_at')->nullable();
            $table->text('identity_reference')->nullable();
            $table->string('identity_verification_environment', 20)->nullable();
        });

        Schema::create('identity_verification_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('pending');
            $table->string('input_hash', 64);
            $table->string('liveness_session_hash', 64)->nullable()->unique();
            $table->timestamp('expires_at');
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('identity_verification_attempts');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['identity_verified_at', 'identity_reference', 'identity_verification_environment']);
        });
    }
};
