<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Tala Admin',
            'email' => 'admin@tala.test',
        ]);

        User::factory()->owner()->create([
            'name' => 'Demo Owner',
            'email' => 'owner@tala.test',
        ]);

        User::factory()->renter()->create([
            'name' => 'Demo Renter',
            'email' => 'renter@tala.test',
        ]);

        $this->call([
            TestLoginSeeder::class,
            CategorySeeder::class,
            MemberSeeder::class,
            ListingSeeder::class,
            TransactionSeeder::class,
            DemoContentSeeder::class,
        ]);
    }
}
