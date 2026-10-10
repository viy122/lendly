<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $keys = ['rentals' => 'rental_request_id', 'payments' => 'rental_id', 'security_deposits' => 'rental_id'];

    public function up(): void
    {
        foreach ($this->keys as $table => $column) {
            if (DB::table($table)->select($column)->groupBy($column)->havingRaw('COUNT(*) > 1')->exists()) {
                throw new RuntimeException("Duplicate {$table}.{$column} records need review before adding uniqueness; no records were deleted.");
            }
        }
        foreach ($this->keys as $table => $column) {
            if (! Schema::hasIndex($table, "{$table}_{$column}_unique")) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->unique($column));
            }
        }
    }

    public function down(): void
    {
        foreach ($this->keys as $table => $column) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropUnique([$column]));
        }
    }
};
