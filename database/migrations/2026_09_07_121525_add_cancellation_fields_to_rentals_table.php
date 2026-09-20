<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->timestamp('cancelled_at')->nullable()->after('completed_at');
            $table->string('cancellation_reason')->nullable()->after('cancelled_at');
            $table->decimal('cancellation_fee', 10, 2)->nullable()->after('cancellation_reason');
        });
    }

    public function down(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->dropColumn(['cancelled_at', 'cancellation_reason', 'cancellation_fee']);
        });
    }
};
