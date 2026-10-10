<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestAccountSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['name' => 'Tala Admin', 'email' => 'admin@tala.test', 'role' => 'admin'],
            ['name' => 'Demo Member', 'email' => 'member@tala.test', 'role' => 'member'],
        ];

        foreach ($accounts as $account) {
            User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'role' => $account['role'],
                    'status' => 'active',
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                ],
            );
        }
    }
}
