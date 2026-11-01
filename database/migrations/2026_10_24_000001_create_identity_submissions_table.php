<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('identity_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('status', 20)->default('pending')->index();
            $table->string('front_path')->nullable();
            $table->string('back_path')->nullable();
            $table->string('selfie_path')->nullable();
            $table->string('profile_hash', 64);
            $table->double('face_similarity')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->dateTime('consented_at');
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('identity_submissions');
    }
};
