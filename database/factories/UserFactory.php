<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Owner and Renter are unified into a single Member role — kept as two
     * factory method names (both producing the same state) purely so
     * existing/future test code reads clearly about which capability of the
     * account a given test is exercising.
     */
    public function renter(): static
    {
        return $this->state(fn (array $attributes) => ['role' => UserRole::Member]);
    }

    public function owner(): static
    {
        return $this->state(fn (array $attributes) => ['role' => UserRole::Member]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => ['role' => UserRole::Admin]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserStatus::Suspended,
            'suspended_at' => now(),
            'suspension_reason' => 'Suspended for testing',
        ]);
    }
}
