<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_requests', function (Blueprint $table) {
            $table->timestamp('renter_terms_accepted_at')->nullable()->after('status');
            $table->timestamp('owner_terms_accepted_at')->nullable()->after('renter_terms_accepted_at');
        });
    }

    public function down(): void
    {
        Schema::table('rental_requests', function (Blueprint $table) {
            $table->dropColumn(['renter_terms_accepted_at', 'owner_terms_accepted_at']);
        });
    }
};
