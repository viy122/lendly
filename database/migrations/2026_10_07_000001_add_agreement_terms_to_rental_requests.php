<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasIndex('rentals', 'rentals_rental_request_id_unique')
            && DB::table('rentals')->select('rental_request_id')->groupBy('rental_request_id')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Duplicate bookings need review before adding uniqueness; no records were deleted.');
        }
        if (! Schema::hasColumn('rental_requests', 'agreement_terms')) {
            Schema::table('rental_requests', fn (Blueprint $table) => $table->json('agreement_terms')->nullable());
        }
        if (! Schema::hasIndex('rentals', 'rentals_rental_request_id_unique')) {
            Schema::table('rentals', fn (Blueprint $table) => $table->unique('rental_request_id'));
        }
    }

    public function down(): void
    {
        if (! DB::table('migrations')->where('migration', '2026_10_09_000200_enforce_single_booking_payment_and_deposit')->exists()
            && Schema::hasIndex('rentals', 'rentals_rental_request_id_unique')) {
            Schema::table('rentals', fn (Blueprint $table) => $table->dropUnique(['rental_request_id']));
        }

        Schema::table('rental_requests', function (Blueprint $table) {
            $table->dropColumn('agreement_terms');
        });
    }
};
