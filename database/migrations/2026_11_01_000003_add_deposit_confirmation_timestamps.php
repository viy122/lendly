<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('security_deposits', function (Blueprint $table) {
            $table->timestamp('released_at')->nullable();
            $table->timestamp('received_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('security_deposits', fn (Blueprint $table) => $table->dropColumn(['released_at', 'received_at']));
    }
};
