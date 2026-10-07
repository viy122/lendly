<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_requests', function (Blueprint $table) {
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->decimal('cancellation_fee', 10, 2)->nullable();
        });
        // The existing renter form accepts 500 characters and admin notes accept 1000.
        Schema::table('rentals', fn (Blueprint $table) => $table->text('cancellation_reason')->nullable()->change());

        // Unfinalized requests never incurred a fee. Their original cancellation
        // time and reason were not recorded; leave those unknown rather than inventing them.
        DB::table('rental_requests')->where('status', 'cancelled')
            ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('rentals')
                ->whereColumn('rentals.rental_request_id', 'rental_requests.id'))
            ->update(['cancellation_fee' => 0]);

        DB::table('rentals')->where('status', 'cancelled')->orderBy('id')
            ->chunkById(100, function ($rentals) {
                foreach ($rentals as $rental) {
                    DB::table('rental_requests')->where('id', $rental->rental_request_id)->update([
                        'cancelled_at' => $rental->cancelled_at,
                        'cancellation_reason' => $rental->cancellation_reason,
                        'cancellation_fee' => $rental->cancellation_fee,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('rental_requests', fn (Blueprint $table) => $table->dropColumn([
            'cancelled_at', 'cancellation_reason', 'cancellation_fee',
        ]));
        // Retain the wider reason column so rollback cannot truncate saved history.
    }
};
