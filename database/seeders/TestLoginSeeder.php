<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestLoginSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrNew([
            'email' => 'test@lendly.test',
        ]);

        $user->forceFill([
            'name' => 'Test User',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => UserRole::Member,
            'status' => UserStatus::Active,
            'phone' => '0917 000 0000',
            'address' => 'Manila, Philippines',
            'suspended_at' => null,
            'suspension_reason' => null,
        ])->save();
    }
}
