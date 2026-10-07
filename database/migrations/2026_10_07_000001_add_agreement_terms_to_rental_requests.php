<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_requests', function (Blueprint $table) {
            $table->json('agreement_terms')->nullable();
        });

        Schema::table('rentals', function (Blueprint $table) {
            $table->unique('rental_request_id');
        });
    }

    public function down(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->dropUnique(['rental_request_id']);
        });

        Schema::table('rental_requests', function (Blueprint $table) {
            $table->dropColumn('agreement_terms');
        });
    }
};
