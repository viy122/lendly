<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'deleted_at')) {
            Schema::table('users', fn (Blueprint $table) => $table->softDeletes());
        }
    }

    public function down(): void
    {
        if (! DB::table('migrations')->where('migration', '2026_10_07_000003_preserve_rental_archives')->exists()
            && Schema::hasColumn('users', 'deleted_at')) {
            Schema::table('users', fn (Blueprint $table) => $table->dropSoftDeletes());
        }
    }
};
