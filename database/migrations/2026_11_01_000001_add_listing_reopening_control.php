<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', fn (Blueprint $table) => $table->boolean('reopen_required')->default(false));
        DB::table('listings')->whereIn('id', DB::table('rentals')->select('listing_id')->where(function ($query) {
            $query->whereIn('status', ['active', 'overdue', 'returned'])
                ->orWhere(fn ($paid) => $paid->where('status', 'paid')->where(fn ($pickup) => $pickup->whereNotNull('pickup_confirmed_by_owner_at')->orWhereNotNull('pickup_confirmed_by_renter_at')));
        }))
            ->update(['reopen_required' => true]);
    }

    public function down(): void
    {
        Schema::table('listings', fn (Blueprint $table) => $table->dropColumn('reopen_required'));
    }
};
