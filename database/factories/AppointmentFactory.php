<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
final class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'contact_number' => '09'.fake()->numerify('#########'),
            'email' => fake()->boolean(60) ? fake()->safeEmail() : null,
            /*
             * Always in the future, because the form refuses a past date and a
             * factory that produces records the form could not have created
             * makes tests pass against states that cannot happen.
             */
            'preferred_date' => fake()->dateTimeBetween('+1 day', '+3 weeks')->format('Y-m-d'),
            'preferred_time' => fake()->randomElement(['morning', 'afternoon']),
            'party_size' => fake()->numberBetween(1, 6),
            'message' => fake()->boolean(40) ? fake()->sentence() : null,
            'broodcock_id' => null,
            'status' => AppointmentStatus::Pending,
        ];
    }

    /** A request the farm has agreed to. */
    public function confirmed(): static
    {
        return $this->state(fn (): array => [
            'status' => AppointmentStatus::Confirmed,
            'handled_at' => now(),
        ]);
    }

    /** A request the farm turned down. */
    public function declined(): static
    {
        return $this->state(fn (): array => [
            'status' => AppointmentStatus::Declined,
            'handled_at' => now(),
        ]);
    }
}
