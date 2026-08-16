<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /** Hashing once and reusing keeps the test suite fast - bcrypt is slow by design. */
    protected static ?string $password = null;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => UserRole::Customer,
            'contact_number' => fake()->numerify('09#########'),
            'address' => fake()->address(),
            'position' => null,
            'is_active' => true,

            // Declared even though they are null. Model::shouldBeStrict() throws
            // on reading an attribute that was never loaded, and a factory-built
            // model only carries the keys the factory set - so omitting these
            // made every view touching a profile photo explode in tests while
            // working perfectly in the app, where the row is selected in full.
            'profile_photo_path' => null,
            'profile_photo_disk' => null,
            'remember_token' => Str::random(10),
        ];
    }

    public function owner(): static
    {
        return $this->state(fn () => [
            'role' => UserRole::Owner,
            'position' => 'Farm Owner',
        ]);
    }

    public function staff(): static
    {
        return $this->state(fn () => [
            'role' => UserRole::Staff,
            'position' => 'Record Keeper',
        ]);
    }

    public function customer(): static
    {
        return $this->state(fn () => [
            'role' => UserRole::Customer,
            'position' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }
}
