<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Existing bookings and their accepted terms/history remain unchanged.
        DB::table('rental_requests')->where('status', 'approved')
            ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('rentals')
                ->whereColumn('rentals.rental_request_id', 'rental_requests.id'))
            ->update(['status' => 'awaiting_agreement']);
    }

    public function down(): void
    {
        DB::table('rental_requests')->where('status', 'awaiting_agreement')->update(['status' => 'approved']);
    }
};
