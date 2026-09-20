<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('member')->after('email');
            $table->string('status')->default('active')->after('role');
            $table->string('phone')->nullable()->after('status');
            $table->string('address')->nullable()->after('phone');
            $table->string('avatar_path')->nullable()->after('address');
            $table->unsignedTinyInteger('trust_score')->default(100)->after('avatar_path');
            $table->string('suspension_reason')->nullable()->after('trust_score');
            $table->timestamp('suspended_at')->nullable()->after('suspension_reason');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role',
                'status',
                'phone',
                'address',
                'avatar_path',
                'trust_score',
                'suspension_reason',
                'suspended_at',
            ]);
        });
    }
};
