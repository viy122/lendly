<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->timestamp('pickup_confirmed_by_owner_at')->nullable()->after('paid_at');
            $table->timestamp('pickup_confirmed_by_renter_at')->nullable()->after('pickup_confirmed_by_owner_at');
            $table->timestamp('return_confirmed_by_owner_at')->nullable()->after('pickup_confirmed_by_renter_at');
            $table->timestamp('return_confirmed_by_renter_at')->nullable()->after('return_confirmed_by_owner_at');
            $table->timestamp('completed_at')->nullable()->after('return_confirmed_by_renter_at');
            $table->unsignedInteger('days_overdue')->default(0)->after('completed_at');
            $table->decimal('late_fee', 10, 2)->default(0)->after('days_overdue');
        });
    }

    public function down(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->dropColumn([
                'pickup_confirmed_by_owner_at',
                'pickup_confirmed_by_renter_at',
                'return_confirmed_by_owner_at',
                'return_confirmed_by_renter_at',
                'completed_at',
                'days_overdue',
                'late_fee',
            ]);
        });
    }
};
