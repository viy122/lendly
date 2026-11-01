<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disputes', fn (Blueprint $table) => $table->string('other_resolution', 1000)->nullable());
    }

    public function down(): void
    {
        Schema::table('disputes', fn (Blueprint $table) => $table->dropColumn('other_resolution'));
    }
};
